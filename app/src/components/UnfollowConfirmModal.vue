<template>
  <AppModal
    :is-open="isOpen"
    :loading="loading"
    :title="displayName ? `¿Dejar de seguir a ${displayName}?` : '¿Dejar de seguir?'"
    description="Ya no verás su progreso en tu ranking."
    @close="$emit('close')"
    :show-close="false"
  >
    <template #icon>
      <div class="w-11 h-11 rounded-2xl bg-rose-500/10 border border-rose-500/30 flex items-center justify-center shrink-0">
        <UserMinus class="w-5 h-5 text-rose-400 stroke-[2.5]" />
      </div>
    </template>

    <template #footer>
      <div class="flex items-center gap-3 w-full">
        <div class="flex-1">
          <AppButton color="dark" block :disabled="loading" @click="$emit('close')">
            Cancelar
          </AppButton>
        </div>
        <div class="flex-1">
          <AppButton color="rose" block :disabled="loading" @click="$emit('confirm')">
            {{ loading ? 'Procesando...' : 'Dejar de seguir' }}
          </AppButton>
        </div>
      </div>
    </template>
  </AppModal>
</template>

<script setup>
import { UserMinus } from '@lucide/vue';
import AppButton from './AppButton.vue';
import AppModal from './AppModal.vue';

defineProps({
  isOpen: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
  displayName: { type: String, default: '' }
});

defineEmits(['close', 'confirm']);
</script>
