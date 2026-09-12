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
        <AppTextInput v-model="newBookTotalPages" type="number" min="1" :max="MAX_BOOK_TOTAL_PAGES" placeholder="Ej. 120" />
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

    <div v-if="activeBook" class="mt-5 pt-4 border-t border-slate-800/70 space-y-1">
      <p class="text-sm font-bold text-slate-400 uppercase tracking-wide pb-1">Privacidad de lectura</p>
      <div class="flex items-center justify-between gap-3 py-2.5 border-b border-slate-800/70">
        <div class="min-w-0">
          <p class="text-base font-semibold text-white">Mostrar nombre</p>
          <p class="text-sm text-slate-400 font-medium">Publicar el nombre del libro en tu perfil.</p>
        </div>
        <AppToggle v-model="privacyPrefs.show_current_book" />
      </div>
      <div class="flex items-center justify-between gap-3 py-2.5">
        <div class="min-w-0">
          <p class="text-base font-semibold text-white">Mostrar avance</p>
          <p class="text-sm text-slate-400 font-medium">Porcentaje que llevas leído de tu libro.</p>
        </div>
        <AppToggle v-model="privacyPrefs.show_reading_progress" />
      </div>

      <AppButton
        color="green"
        block
        class="mt-3"
        :disabled="savePrivacyAction.loading.value || !privacyDirty"
        :text="savePrivacyAction.loading.value ? 'Guardando...' : 'Guardar privacidad'"
        @click="savePrivacyPrefs"
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
import { ref, reactive, computed, watch, onMounted } from 'vue';
import { BookOpen } from '@lucide/vue';
import ExpandableCard from './ExpandableCard.vue';
import AppButton from './AppButton.vue';
import AppFormField from './AppFormField.vue';
import AppTextInput from './AppTextInput.vue';
import AppToggle from './AppToggle.vue';
import ConfirmActionModal from './ConfirmActionModal.vue';
import { ApiService } from '@/services/api';
import { useAsyncAction } from '@/composables/useAsyncAction';
import { useActiveBook } from '@/composables/useActiveBook';
import { useCurrentUser } from '@/composables/useCurrentUser';
import { isBibleTitle, detectTrackingMode, MAX_BOOK_TOTAL_PAGES } from '@/utils/bookTracking';

// Encapsula sus propios endpoints (getActiveBook/createBook/removeActiveBook).
// activeBook vive en el composable compartido (singleton, mismo patron que
// useCurrentUser) — la seccion de privacidad de abajo tambien lo lee.
const { activeBook, setActiveBook } = useActiveBook();
const { user, mergeUser } = useCurrentUser();

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
  const pages = Number(newBookTotalPages.value);
  return pages > 0 && pages <= MAX_BOOK_TOTAL_PAGES;
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

const DEFAULT_PRIVACY_PREFS = { show_current_book: true, show_reading_progress: true };
const privacyPrefs = reactive({ ...DEFAULT_PRIVACY_PREFS });
const savedPrivacyPrefs = ref({ ...DEFAULT_PRIVACY_PREFS });
const savePrivacyAction = useAsyncAction();
const privacyDirty = computed(() =>
  privacyPrefs.show_current_book !== savedPrivacyPrefs.value.show_current_book ||
  privacyPrefs.show_reading_progress !== savedPrivacyPrefs.value.show_reading_progress
);

watch(user, (newUser) => {
  if (newUser && (newUser.show_current_book !== undefined || newUser.show_reading_progress !== undefined)) {
    const merged = {
      show_current_book: newUser.show_current_book ?? privacyPrefs.show_current_book,
      show_reading_progress: newUser.show_reading_progress ?? privacyPrefs.show_reading_progress,
    };
    Object.assign(privacyPrefs, merged);
    savedPrivacyPrefs.value = { ...merged };
  }
}, { immediate: true });

const savePrivacyPrefs = async () => {
  if (!privacyDirty.value) return;
  const changed = {};
  if (privacyPrefs.show_current_book !== savedPrivacyPrefs.value.show_current_book) {
    changed.show_current_book = privacyPrefs.show_current_book;
  }
  if (privacyPrefs.show_reading_progress !== savedPrivacyPrefs.value.show_reading_progress) {
    changed.show_reading_progress = privacyPrefs.show_reading_progress;
  }

  const res = await savePrivacyAction.run(() => ApiService.updatePrivacyPrefs(changed), {
    successMsg: 'Privacidad de lectura guardada.',
    errorMsg: 'No se pudo guardar la privacidad.'
  });

  if (res === undefined) return;

  savedPrivacyPrefs.value = { ...privacyPrefs };
  mergeUser(changed);
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
