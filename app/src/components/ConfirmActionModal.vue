<template>
  <AppModal
    :is-open="isOpen"
    :loading="loading"
    :title="title"
    :description="description"
    @close="$emit('close')"
    :show-close="false"
  >
    <template #icon>
      <div class="w-11 h-11 rounded-2xl bg-rose-500/10 border border-rose-500/30 flex items-center justify-center shrink-0">
        <component :is="icon" class="w-5 h-5 text-rose-400 stroke-[2.5]" />
      </div>
    </template>

    <template #footer>
      <div class="flex items-center gap-3 w-full">
        <div class="flex-1">
          <AppButton color="dark" block :disabled="loading" @click="$emit('close')">
            {{ cancelLabel }}
          </AppButton>
        </div>
        <div class="flex-1">
          <AppButton :color="confirmColor" block :disabled="loading" @click="$emit('confirm')">
            {{ loading ? loadingLabel : confirmLabel }}
          </AppButton>
        </div>
      </div>
    </template>
  </AppModal>
</template>

<script setup>
import AppButton from './AppButton.vue';
import AppModal from './AppModal.vue';

defineProps({
  isOpen: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
  icon: { type: [Object, Function], required: true },
  title: { type: String, required: true },
  description: { type: String, default: '' },
  confirmLabel: { type: String, default: 'Confirmar' },
  loadingLabel: { type: String, default: 'Procesando...' },
  cancelLabel: { type: String, default: 'Cancelar' },
  confirmColor: { type: String, default: 'rose' }
});

defineEmits(['close', 'confirm']);
</script>
