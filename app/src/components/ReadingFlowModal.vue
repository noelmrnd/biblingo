<template>
  <!-- Paso 1: avance del libro activo (solo si hay uno). Paso 2: reaccion (siempre).
       Ambos pasos se recolectan aca y se mandan juntos en un solo submit (@confirm),
       para que el backend los registre atomicamente junto con la racha — ver
       ReadingButton::handleReactionConfirmed. Nunca se llama a la API entre pasos. -->
  <AppModal
    v-if="step === 'progress' && activeBook"
    :is-open="isOpen"
    :loading="loading"
    title="Tu avance de hoy"
    :description="activeBook.title"
    @close="onClose"
  >
    <BookProgressStep ref="progressStepRef" :book="activeBook" :loading="loading" />

    <template #footer>
      <AppButton
        color="green"
        size="lg"
        block
        :disabled="!isProgressValid"
        text="Siguiente"
        :icon="ChevronRight"
        icon-position="end"
        @click="goToReaction"
      />
    </template>
  </AppModal>

  <ReactionModal
    v-else
    :is-open="isOpen"
    :loading="loading"
    @close="onClose"
    @confirm="onReactionConfirm"
  />
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import { ChevronRight } from '@lucide/vue';
import AppModal from './AppModal.vue';
import AppButton from './AppButton.vue';
import BookProgressStep from './BookProgressStep.vue';
import ReactionModal from './ReactionModal.vue';

const props = defineProps({
  isOpen: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
  activeBook: { type: Object, default: null }
});

const emit = defineEmits(['close', 'confirm']);

const step = ref('progress');
const progressStepRef = ref(null);
const progressPayload = ref(null);

// Al abrir, arranca en 'progress' si hay libro activo, si no directo a la reaccion.
watch(() => props.isOpen, (open) => {
  if (open) {
    step.value = props.activeBook ? 'progress' : 'reaction';
    progressPayload.value = null;
  }
});

const isProgressValid = computed(() => progressStepRef.value?.isValid ?? false);

const goToReaction = () => {
  if (!progressStepRef.value?.isValid) return;
  progressPayload.value = progressStepRef.value.getPayload();
  step.value = 'reaction';
};

const onReactionConfirm = (reaction) => {
  emit('confirm', { reaction, progress: progressPayload.value });
};

const onClose = () => {
  if (props.loading) return;
  emit('close');
};
</script>
