<template>
  <ConfirmActionModal
    :is-open="isOpen"
    :loading="loading"
    :icon="UserMinus"
    :title="displayName ? `¿Dejar de seguir a ${displayName}?` : '¿Dejar de seguir?'"
    description="Ya no verás su progreso en tu ranking."
    confirm-label="Dejar de seguir"
    @close="$emit('close')"
    @confirm="$emit('confirm')"
  />
</template>

<script setup>
import { UserMinus } from '@lucide/vue';
import ConfirmActionModal from './ConfirmActionModal.vue';

defineProps({
  isOpen: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
  displayName: { type: String, default: '' }
});

defineEmits(['close', 'confirm']);
</script>
