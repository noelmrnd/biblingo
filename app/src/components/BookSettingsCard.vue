<template>
  <!-- Tu libro (control de lectura, opcional) -->
  <ExpandableCard
    v-model="isExpanded"
    title="Tu libro"
    description="Qué estás leyendo ahora"
    icon-bg-class="bg-purple-500/10 border-purple-500/30"
    icon-color-class="text-purple-400"
    :icon="BookOpen"
  >
    <!-- Ya hay un libro activo: solo estado + acciones, sin inputs (cambiar
         implica quitar el actual primero, ver confirmRemoveBook). -->
    <div v-if="activeBook" class="space-y-3">
      <p class="text-sm text-slate-400 font-medium">
        Leyendo: <span class="font-semibold text-white">{{ activeBook.title }}</span>
        <span v-if="activeBook.tracking_mode === 'linear'"> ({{ activeBook.current_unit }}/{{ activeBook.total_units }} páginas)</span>
        <span v-else> ({{ activeBook.current_unit }}/{{ activeBook.total_units }} capítulos)</span>
      </p>

      <AppButton
        color="green"
        block
        :disabled="removeBookAction.loading.value"
        text="Cambiar libro"
        @click="openConfirmModal('change')"
      />

      <button
        type="button"
        :disabled="removeBookAction.loading.value"
        @click="openConfirmModal('remove')"
        class="w-full text-center text-sm font-semibold text-slate-500 hover:text-rose-400 py-1 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
      >
        Quitar libro (dejar de registrar avance)
      </button>
    </div>

    <!-- Sin libro activo: nada que perder, inputs directos. -->
    <div v-else class="space-y-3">
      <AppFormField label="Nombre del libro">
        <AppTextInput v-model="newBookTitle" autocapitalize="words" placeholder="Ej. El Principito" />
      </AppFormField>

      <AppFormField v-if="!isNewBookBible" label="Número de páginas">
        <AppTextInput v-model="newBookTotalPages" type="number" min="1" placeholder="Ej. 320" />
      </AppFormField>
      <p v-else class="text-sm text-slate-400">
        Podrás llevar el registro de tu lectura por capítulos.
      </p>

      <AppButton
        color="green"
        block
        :disabled="saveBookAction.loading.value || !isNewBookValid"
        :text="saveBookAction.loading.value ? 'Guardando...' : 'Guardar libro'"
        @click="saveNewBook"
      />
    </div>
  </ExpandableCard>

  <!-- Modal de Confirmación: "Cambiar" y "Quitar" ejecutan la misma accion
       (removeActiveBook) — solo cambia el mensaje segun la intencion. -->
  <ConfirmActionModal
    :is-open="!!removeIntent"
    :icon="BookOpen"
    :title="removeIntent === 'change' ? '¿Cambiar de libro?' : '¿Quitar tu libro actual?'"
    :description="removeIntent === 'change'
      ? 'Se quitará el libro actual para que puedas registrar uno nuevo. Tu contador de páginas leídas en total no se perderá.'
      : 'Dejarás de registrar el avance de este libro. Tu contador de páginas leídas en total no se perderá.'"
    :confirm-label="removeIntent === 'change' ? 'Cambiar libro' : 'Quitar libro'"
    @close="removeIntent = null"
    @confirm="confirmRemoveBook"
  />
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { BookOpen } from '@lucide/vue';
import ExpandableCard from './ExpandableCard.vue';
import AppButton from './AppButton.vue';
import AppFormField from './AppFormField.vue';
import AppTextInput from './AppTextInput.vue';
import ConfirmActionModal from './ConfirmActionModal.vue';
import { ApiService } from '@/services/api';
import { useAsyncAction } from '@/composables/useAsyncAction';
import { useActiveBook } from '@/composables/useActiveBook';
import { isBibleTitle, detectTrackingMode } from '@/utils/bookTracking';

// Encapsula sus propios endpoints (getActiveBook/createBook/removeActiveBook).
// activeBook vive en el composable compartido (singleton, mismo patron que
// useCurrentUser) para que PrivacySettingsCard lo lea sin props/emit entre ambos.
const { activeBook, setActiveBook } = useActiveBook();

const isExpanded = ref(false);
const newBookTitle = ref('');
const newBookTotalPages = ref('');
const saveBookAction = useAsyncAction();
const removeBookAction = useAsyncAction();

// 'change' | 'remove' | null. Ambos disparan la misma llamada (confirmRemoveBook),
// solo cambia el texto del modal — "cambiar" es "quitar" con otra intencion
// comunicada, no una accion distinta (ver conversacion de diseño).
const removeIntent = ref(null);
const openConfirmModal = (intent) => {
  removeIntent.value = intent;
};

const isNewBookBible = computed(() => isBibleTitle(newBookTitle.value));

const isNewBookValid = computed(() => {
  if (newBookTitle.value.trim() === '') return false;
  if (isNewBookBible.value) return true;
  return Number(newBookTotalPages.value) > 0;
});

const saveNewBook = async () => {
  const title = newBookTitle.value.trim();
  const res = await saveBookAction.run(
    () => ApiService.createBook(title, detectTrackingMode(title), isNewBookBible.value ? null : Number(newBookTotalPages.value)),
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
  removeIntent.value = null;
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
