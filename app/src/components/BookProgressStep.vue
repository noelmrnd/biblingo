<template>
  <div class="space-y-4 py-2">
    <!-- Modo lineal: pide la pagina nueva, prellenada con la siguiente a la actual -->
    <div v-if="book.tracking_mode === 'linear'" class="space-y-3">
      <p class="text-slate-300 text-base font-medium leading-relaxed">
        Vas en la página <span class="font-bold text-white">{{ book.current_unit }}</span> de
        <span class="font-bold text-white">{{ book.total_units }}</span>. ¿En qué página te quedaste hoy?
      </p>
      <input
        v-model="currentPage"
        type="number"
        min="0"
        :max="book.total_units"
        :disabled="loading"
        class="w-full rounded-xl bg-slate-950/60 border border-slate-800 px-4 py-3 text-white text-lg font-bold text-center focus:outline-none focus:border-brand-green"
      />
      <p v-if="pageError" class="text-rose-400 text-sm font-semibold text-center">{{ pageError }}</p>
      <p
        v-else-if="currentPage !== '' && Number(currentPage) < book.current_unit"
        class="text-amber-400 text-sm font-semibold text-center"
      >
        Vas a retroceder tu progreso a la página {{ currentPage }}.
      </p>
    </div>

    <!-- Modo bitmask (Biblia): grid de capitulos agrupado por libro, prellenado con lo ya leido -->
    <div v-else class="space-y-4">
      <p class="text-slate-300 text-base font-medium leading-relaxed">
        Marca los capítulos que leíste.
      </p>
      <div class="max-h-80 overflow-y-auto space-y-5 pr-1 no-scrollbar">
        <div v-for="book_ in BIBLE_BOOKS_WITH_OFFSET" :key="book_.name">
          <label class="flex items-center gap-2 mb-3 cursor-pointer select-none w-fit">
            <input
              type="checkbox"
              :checked="isBookFullyChecked(book_)"
              :disabled="loading"
              @change="toggleWholeBook(book_)"
              class="app-checkbox"
            />
            <span class="text-base font-semibold text-slate-400">{{ book_.name }}</span>
          </label>
          <div class="flex flex-wrap gap-1.5">
            <button
              v-for="n in book_.chapters"
              :key="n"
              type="button"
              :disabled="loading"
              @click="toggleChapter(book_.startsAt + n - 1)"
              :class="checkedChapters.has(book_.startsAt + n - 1)
                ? 'bg-brand-green text-white border-brand-green cursor-pointer'
                : 'bg-slate-950/60 text-slate-400 border-slate-800 hover:border-slate-700 cursor-pointer'"
              class="w-9 h-9 rounded-lg border text-sm font-bold transition-colors"
            >
              {{ n }}
            </button>
          </div>
        </div>
      </div>
      <p class="text-sm text-slate-400 text-center">
        {{ formatNumber(checkedChapters.size) }} de {{ formatNumber(BIBLE_CHAPTERS) }} capítulos marcados
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import {BIBLE_BOOKS_WITH_OFFSET, BIBLE_CHAPTERS} from '@/constants';
import {formatNumber} from "../utils/numberFormatter.js";

const props = defineProps({
  book: { type: Object, required: true },
  loading: { type: Boolean, default: false }
});

const currentPage = ref(String((props.book.current_unit || 0) + 1));
// Snapshot de lo ya guardado en el servidor al abrir el paso: contra esto se
// calcula que capitulos son "nuevos" (chapters) y cuales se destildaron
// (unchapters) al armar el payload. checkedChapters es el estado editable.
const savedChapters = new Set(props.book.read_chapters || []);
const checkedChapters = ref(new Set(props.book.read_chapters || []));

const pageError = computed(() => {
  const n = Number(currentPage.value);
  if (!currentPage.value || Number.isNaN(n)) return null;
  if (n < 0) return 'No puede ser negativo.';
  if (n > props.book.total_units) return `No puede superar ${props.book.total_units} páginas.`;
  return null;
});

const isValid = computed(() => {
  if (props.book.tracking_mode === 'linear') {
    return currentPage.value !== '' && !pageError.value && Number(currentPage.value) !== props.book.current_unit;
  }
  // Debe haber algun cambio respecto a lo guardado (agregar o quitar capitulos).
  const hasAdded = [...checkedChapters.value].some((c) => !savedChapters.has(c));
  const hasRemoved = [...savedChapters].some((c) => !checkedChapters.value.has(c));
  return hasAdded || hasRemoved;
});

const toggleChapter = (chapterNumber) => {
  if (props.loading) return;
  if (checkedChapters.value.has(chapterNumber)) {
    checkedChapters.value.delete(chapterNumber);
  } else {
    checkedChapters.value.add(chapterNumber);
  }
  // Forzar reactividad: Set mutado in-place no dispara el computed por si solo.
  checkedChapters.value = new Set(checkedChapters.value);
};

const bookChapterNumbers = (book_) => Array.from({ length: book_.chapters }, (_, i) => book_.startsAt + i);

const isBookFullyChecked = (book_) => bookChapterNumbers(book_).every((c) => checkedChapters.value.has(c));

// Checkbox del libro completo: marca todos sus capitulos de una vez, o los
// destilda todos si ya estaban todos marcados.
const toggleWholeBook = (book_) => {
  if (props.loading) return;
  const chapters = bookChapterNumbers(book_);
  const next = new Set(checkedChapters.value);
  if (isBookFullyChecked(book_)) {
    chapters.forEach((c) => next.delete(c));
  } else {
    chapters.forEach((c) => next.add(c));
  }
  checkedChapters.value = next;
};

const getPayload = () => {
  if (props.book.tracking_mode === 'linear') {
    return { currentPage: Number(currentPage.value) };
  }
  return {
    chapters: [...checkedChapters.value].filter((c) => !savedChapters.has(c)),
    unchapters: [...savedChapters].filter((c) => !checkedChapters.value.has(c)),
  };
};

defineExpose({ isValid, getPayload });
</script>

<style scoped>
/* <input type="checkbox"> nativo: el widget del navegador ignora border-radius
   sin resetear appearance primero — se dibuja a mano con border/bg + un check SVG
   como background-image cuando esta marcado. */
.app-checkbox {
  appearance: none;
  width: 1.25rem;
  height: 1.25rem;
  border-radius: 0.3rem;
  border: 2px solid theme('colors.slate.600');
  background-color: rgba(2, 6, 23, 0.6);
  background-repeat: no-repeat;
  background-position: center;
  cursor: pointer;
}

.app-checkbox:checked {
  background-color: theme('colors.brand.green');
  border-color: theme('colors.brand.green');
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='none'%3E%3Cpath d='M13.5 4.5l-7 7-3-3' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
}

.app-checkbox:disabled {
  cursor: not-allowed;
  opacity: 0.6;
}
</style>
