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
        :min="book.current_unit + 1"
        :max="book.total_units"
        :disabled="loading"
        class="w-full rounded-xl bg-slate-950/60 border border-slate-800 px-4 py-3 text-white text-lg font-bold text-center focus:outline-none focus:border-brand-green"
      />
      <p v-if="pageError" class="text-rose-400 text-sm font-semibold text-center">{{ pageError }}</p>
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
              :disabled="loading || isBookFullyRead(book_)"
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
              :disabled="loading || alreadyReadChapters.has(book_.startsAt + n - 1)"
              @click="toggleChapter(book_.startsAt + n - 1)"
              :class="alreadyReadChapters.has(book_.startsAt + n - 1)
                ? 'bg-brand-green/40 text-white/70 border-brand-green/40 cursor-not-allowed'
                : checkedChapters.has(book_.startsAt + n - 1)
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
// Ya guardados en el servidor: se muestran marcados y bloqueados (el backend
// solo hace OR de bits, nunca desmarca — permitir destildarlos aca seria un
// "desmarcar" que no tiene efecto real, solo confunde). Los recien tildados en
// este borrador (todavia no enviados) si se pueden destildar para corregir un click.
const alreadyReadChapters = new Set(props.book.read_chapters || []);
const checkedChapters = ref(new Set(props.book.read_chapters || []));

const pageError = computed(() => {
  const n = Number(currentPage.value);
  if (!currentPage.value || Number.isNaN(n)) return null;
  if (n <= props.book.current_unit) return `Debe ser mayor a ${props.book.current_unit}.`;
  if (n > props.book.total_units) return `No puede superar ${props.book.total_units} páginas.`;
  return null;
});

const isValid = computed(() => {
  if (props.book.tracking_mode === 'linear') {
    return currentPage.value !== '' && !pageError.value;
  }
  // Debe marcar al menos 1 capitulo NUEVO (no basta con re-marcar lo ya leido).
  return [...checkedChapters.value].some((c) => !alreadyReadChapters.has(c));
});

const toggleChapter = (chapterNumber) => {
  if (props.loading || alreadyReadChapters.has(chapterNumber)) return;
  if (checkedChapters.value.has(chapterNumber)) {
    checkedChapters.value.delete(chapterNumber);
  } else {
    checkedChapters.value.add(chapterNumber);
  }
  // Forzar reactividad: Set mutado in-place no dispara el computed por si solo.
  checkedChapters.value = new Set(checkedChapters.value);
};

const bookChapterNumbers = (book_) => Array.from({ length: book_.chapters }, (_, i) => book_.startsAt + i);

const isBookFullyRead = (book_) => bookChapterNumbers(book_).every((c) => alreadyReadChapters.has(c));

const isBookFullyChecked = (book_) => bookChapterNumbers(book_).every((c) => checkedChapters.value.has(c));

// Checkbox del libro completo: marca todos los capitulos pendientes de una vez,
// o los destilda (solo los que no vienen ya guardados, igual que toggleChapter individual).
const toggleWholeBook = (book_) => {
  if (props.loading || isBookFullyRead(book_)) return;
  const chapters = bookChapterNumbers(book_);
  const next = new Set(checkedChapters.value);
  if (isBookFullyChecked(book_)) {
    chapters.forEach((c) => { if (!alreadyReadChapters.has(c)) next.delete(c); });
  } else {
    chapters.forEach((c) => next.add(c));
  }
  checkedChapters.value = next;
};

const getPayload = () => {
  if (props.book.tracking_mode === 'linear') {
    return { currentPage: Number(currentPage.value) };
  }
  // Solo los nuevos: el backend hace OR puro (mandar los ya leidos es inofensivo
  // pero innecesario, hasta 1189 numeros de mas en el body sin aportar nada).
  return { chapters: [...checkedChapters.value].filter((c) => !alreadyReadChapters.has(c)) };
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
