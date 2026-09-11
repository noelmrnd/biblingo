<template>
  <!-- Tu libro (control de lectura, opcional) -->
  <ExpandableCard
    v-model="isExpanded"
    title="Tu libro"
    description="Qué estás leyendo y cuánto avanzaste"
    icon-bg-class="bg-purple-500/10 border-purple-500/30"
    icon-color-class="text-purple-400"
    :icon="BookOpen"
  >
    <div class="space-y-3">
      <p v-if="activeBook" class="text-sm text-slate-400 font-medium">
        Leyendo ahora: <span class="font-bold text-white">{{ activeBook.title }}</span>
        <span v-if="activeBook.tracking_mode === 'linear'"> ({{ activeBook.current_unit }}/{{ activeBook.total_units }} páginas)</span>
        <span v-else> ({{ activeBook.current_unit }}/{{ activeBook.total_units }} capítulos)</span>
      </p>

      <div class="space-y-1.5">
        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Nombre del libro</label>
        <input
          v-model="newBookTitle"
          type="text"
          placeholder="Ej. Génesis, Biblia, etc."
          class="w-full bg-slate-900 border border-slate-800 focus:border-brand-green text-white font-bold rounded-2xl px-4 py-3 text-base focus:outline-none transition-colors"
        />
      </div>

      <div v-if="!isNewBookBible" class="space-y-1.5">
        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Número de páginas</label>
        <input
          v-model="newBookTotalPages"
          type="number"
          min="1"
          placeholder="Ej. 320"
          class="w-full bg-slate-900 border border-slate-800 focus:border-brand-green text-white font-bold rounded-2xl px-4 py-3 text-base focus:outline-none transition-colors"
        />
      </div>
      <p v-else class="text-sm text-slate-400">
        La Biblia se registra por capítulos, no por páginas.
      </p>

      <AppButton
        color="green"
        block
        :disabled="saveBookAction.loading.value || !isNewBookValid"
        :text="saveBookAction.loading.value ? 'Guardando...' : (activeBook ? 'Cambiar libro' : 'Guardar libro')"
        @click="saveNewBook"
      />

      <button
        v-if="activeBook"
        type="button"
        :disabled="removeBookAction.loading.value"
        @click="isRemoveBookModalOpen = true"
        class="w-full text-center text-sm font-semibold text-slate-500 hover:text-rose-400 py-1 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
      >
        Quitar libro (dejar de registrar avance)
      </button>
    </div>
  </ExpandableCard>

  <!-- Modal Confirmación de Quitar Libro -->
  <ConfirmActionModal
    :is-open="isRemoveBookModalOpen"
    :icon="BookOpen"
    title="¿Quitar tu libro actual?"
    description="Dejarás de registrar tu avance hasta que agregues uno nuevo. Tu avance guardado hasta hoy no se pierde."
    confirm-label="Quitar libro"
    @close="isRemoveBookModalOpen = false"
    @confirm="confirmRemoveBook"
  />
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { BookOpen } from '@lucide/vue';
import ExpandableCard from './ExpandableCard.vue';
import AppButton from './AppButton.vue';
import ConfirmActionModal from './ConfirmActionModal.vue';
import { ApiService } from '@/services/api';
import { useAsyncAction } from '@/composables/useAsyncAction';
import { useActiveBook } from '@/composables/useActiveBook';

// Encapsula sus propios endpoints (getActiveBook/createBook/removeActiveBook).
// activeBook vive en el composable compartido (singleton, mismo patron que
// useCurrentUser) para que PrivacySettingsCard lo lea sin props/emit entre ambos.
const { activeBook, setActiveBook } = useActiveBook();

const isExpanded = ref(false);
const newBookTitle = ref('');
const newBookTotalPages = ref('');
const saveBookAction = useAsyncAction();
const removeBookAction = useAsyncAction();
const isRemoveBookModalOpen = ref(false);

// Mismo match flexible que BookEntity::detectTrackingMode en el backend (sin
// acentos/mayusculas): solo para decidir si mostramos el input de paginas.
const isNewBookBible = computed(() => {
  const normalized = newBookTitle.value
    .normalize('NFD').replace(/[̀-ͯ]/g, '')
    .toLowerCase().trim();
  return normalized.includes('biblia');
});

const isNewBookValid = computed(() => {
  if (newBookTitle.value.trim() === '') return false;
  if (isNewBookBible.value) return true;
  return Number(newBookTotalPages.value) > 0;
});

const saveNewBook = async () => {
  const title = newBookTitle.value.trim();
  const res = await saveBookAction.run(
    () => ApiService.createBook(title, isNewBookBible.value ? null : Number(newBookTotalPages.value)),
    { successMsg: '¡Libro guardado! 📖', errorMsg: 'No se pudo guardar tu libro.' }
  );
  if (res === undefined) return;
  setActiveBook(res.book);
  newBookTitle.value = '';
  newBookTotalPages.value = '';
};

const confirmRemoveBook = async () => {
  const res = await removeBookAction.run(() => ApiService.removeActiveBook(), {
    successMsg: 'Libro quitado. Puedes agregar uno nuevo cuando quieras.',
    errorMsg: 'No se pudo quitar el libro.'
  });
  if (res === undefined) return;
  setActiveBook(null);
  isRemoveBookModalOpen.value = false;
};

onMounted(async () => {
  try {
    const res = await ApiService.getActiveBook();
    if (res.success) setActiveBook(res.book);
  } catch (e) {
    console.warn('No se pudo cargar el libro activo:', e.message);
  }
});
</script>
