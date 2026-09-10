<template>
  <AppModal
    :is-open="isOpen"
    :loading="loading"
    title="¿Cómo estuvo tu lectura?"
    description="Elige tu reacción sobre lo que leíste hoy:"
    @close="onClose"
  >
    <div class="space-y-2.5 py-2">
      <button
        v-for="item in READING_REACTIONS"
        :key="item.id"
        type="button"
        @click="selectReaction(item.id)"
        :disabled="loading"
        :class="[
          selectedReaction === item.id
            ? 'border-brand-green bg-emerald-500/15 ring-2 ring-inset ring-brand-green/50 shadow-lg shadow-emerald-950/40 scale-[1.01]'
            : 'border-slate-800 bg-slate-950/60 hover:bg-slate-800/80 hover:border-slate-700 active:scale-[0.99]'
        ]"
        class="w-full text-left p-3.5 rounded-2xl border transition-all duration-200 flex items-center justify-between gap-3 cursor-pointer select-none group"
      >
        <div class="flex items-center gap-3.5 min-w-0">
          <span class="text-2xl filter drop-shadow-sm flex-shrink-0 transition-transform duration-200 group-hover:scale-110 group-active:scale-95">
            {{ item.emoji }}
          </span>
          <div class="min-w-0">
            <p class="font-black text-base text-white tracking-wide truncate">
              {{ item.label }}
            </p>
            <p class="text-sm text-slate-400 font-medium truncate">
              {{ item.desc }}
            </p>
          </div>
        </div>

        <!-- Radio check indicator -->
        <div
          :class="[
            selectedReaction === item.id
              ? 'bg-brand-green border-brand-green text-white scale-105'
              : 'border-slate-800 bg-slate-900/80 text-transparent'
          ]"
          class="w-6 h-6 rounded-full border flex items-center justify-center flex-shrink-0 transition-all duration-200 shadow-inner"
        >
          <Check class="w-3.5 h-3.5 stroke-[3]" />
        </div>
      </button>
    </div>

    <template #footer>
      <AppButton
        color="green"
        size="lg"
        block
        :disabled="!selectedReaction || loading"
        :loading="loading"
        loading-text="Registrando lectura..."
        text="Registrar lectura"
        :icon="BookOpen"
        @click="onConfirm"
      />
    </template>
  </AppModal>
</template>

<script setup>
import { ref, watch } from 'vue';
import AppModal from './AppModal.vue';
import AppButton from './AppButton.vue';
import { Check, BookOpen } from '@lucide/vue';
import { READING_REACTIONS } from '../constants';
import { HapticsService } from '../services/haptics';

const props = defineProps({
  isOpen: { type: Boolean, default: false },
  loading: { type: Boolean, default: false }
});

const emit = defineEmits(['close', 'confirm']);

const selectedReaction = ref(null);

// Reiniciar selección al abrir modal
watch(() => props.isOpen, (newVal) => {
  if (newVal) {
    selectedReaction.value = null;
  }
});

const selectReaction = (id) => {
  if (props.loading) return;
  HapticsService.light();
  selectedReaction.value = id;
};

const onClose = () => {
  if (props.loading) return;
  emit('close');
};

const onConfirm = () => {
  if (!selectedReaction.value || props.loading) return;
  emit('confirm', selectedReaction.value);
};
</script>
