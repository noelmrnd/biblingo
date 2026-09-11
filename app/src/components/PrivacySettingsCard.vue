<template>
  <!-- Privacidad de lectura: solo tiene sentido con un libro activo -->
  <ExpandableCard
    v-model="isExpanded"
    title="Privacidad de lectura"
    description="Qué ve la gente en tu perfil"
    icon-bg-class="bg-purple-500/10 border-purple-500/30"
    icon-color-class="text-purple-400"
    :icon="Eye"
  >
    <div class="space-y-1">
      <p v-if="!activeBook" class="text-sm text-slate-400 font-medium pb-1">
        No tienes un libro activo, no hay nada que mostrar u ocultar todavía.
      </p>
      <div class="flex items-center justify-between gap-3 py-2.5 border-b border-slate-800/70">
        <div class="min-w-0">
          <p class="text-base font-bold" :class="activeBook ? 'text-white' : 'text-slate-500'">Mostrar libro actual</p>
          <p class="text-sm text-slate-400 font-medium">El libro que estás leyendo.</p>
        </div>
        <AppToggle v-model="privacyPrefs.show_current_book" :disabled="!activeBook" />
      </div>
      <div class="flex items-center justify-between gap-3 py-2.5">
        <div class="min-w-0">
          <p class="text-base font-bold" :class="activeBook ? 'text-white' : 'text-slate-500'">Mostrar % de avance</p>
          <p class="text-sm text-slate-400 font-medium">Cuánto llevas leído de tu libro.</p>
        </div>
        <AppToggle v-model="privacyPrefs.show_reading_progress" :disabled="!activeBook" />
      </div>
    </div>

    <AppButton
      color="green"
      block
      :disabled="!activeBook || savePrivacyAction.loading.value || !privacyDirty"
      :text="savePrivacyAction.loading.value ? 'Guardando...' : 'Guardar privacidad'"
      @click="savePrivacyPrefs"
    />
  </ExpandableCard>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import { Eye } from '@lucide/vue';
import ExpandableCard from './ExpandableCard.vue';
import AppButton from './AppButton.vue';
import AppToggle from './AppToggle.vue';
import { ApiService } from '@/services/api';
import { useAsyncAction } from '@/composables/useAsyncAction';
import { useCurrentUser } from '@/composables/useCurrentUser';
import { useActiveBook } from '@/composables/useActiveBook';

// Encapsula su propio endpoint (updatePrivacyPrefs). Lee/escribe el usuario via
// el singleton compartido useCurrentUser (mismo objeto que App.vue ya mantiene)
// en vez de recibirlo por prop y emitir user-updated de vuelta — evita el viaje
// de ida y vuelta a traves de SettingsView. activeBook viene del otro singleton
// (useActiveBook), que BookSettingsCard escribe.
const { user, mergeUser } = useCurrentUser();
const { activeBook } = useActiveBook();

const isExpanded = ref(false);

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
</script>
