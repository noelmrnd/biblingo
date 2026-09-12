<template>
  <AppModal
    :is-open="isOpen"
    :loading="loading"
    :title="`¿Bloquear a ${displayName}?`"
    description="Dejarán de seguirse mutuamente y no podrá volver a seguirte ni ver tu perfil."
    @close="onClose"
  >
    <template #icon>
      <div class="w-11 h-11 rounded-2xl bg-rose-500/10 border border-rose-500/30 flex items-center justify-center shrink-0">
        <ShieldOff class="w-5 h-5 text-rose-400 stroke-[2.5]" />
      </div>
    </template>

    <div class="space-y-2">
      <p class="text-sm font-semibold text-slate-400">Motivo (opcional)</p>
      <button
        v-for="option in BLOCK_REASONS"
        :key="option.id"
        type="button"
        @click="selectedReason = selectedReason === option.id ? null : option.id"
        class="w-full flex items-center justify-between gap-3 rounded-xl border px-4 py-3 text-left transition-colors cursor-pointer"
        :class="selectedReason === option.id
          ? 'bg-rose-500/10 border-rose-500/40'
          : 'bg-slate-950/60 border-slate-800 hover:border-slate-700'"
      >
        <span class="text-sm font-semibold" :class="selectedReason === option.id ? 'text-rose-300' : 'text-slate-200'">
          {{ option.label }}
        </span>
        <div
          class="w-4 h-4 rounded-full border-2 shrink-0"
          :class="selectedReason === option.id ? 'border-rose-400 bg-rose-400' : 'border-slate-600'"
        ></div>
      </button>
    </div>

    <template #footer>
      <div class="flex items-center gap-3 w-full">
        <div class="flex-1">
          <AppButton color="card" block :disabled="loading" text="Cancelar" @click="onClose" />
        </div>
        <div class="flex-1">
          <AppButton
            color="rose"
            haptic="warning"
            block
            :disabled="loading"
            :text="loading ? 'Bloqueando...' : 'Bloquear'"
            @click="$emit('confirm', selectedReason)"
          />
        </div>
      </div>
    </template>
  </AppModal>
</template>

<script setup>
import { ref, watch } from 'vue';
import { ShieldOff } from '@lucide/vue';
import AppModal from './AppModal.vue';
import AppButton from './AppButton.vue';
import { BLOCK_REASONS } from '@/constants';

const props = defineProps({
  isOpen: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
  displayName: { type: String, default: '' }
});

const emit = defineEmits(['close', 'confirm']);

const selectedReason = ref(null);

// Limpia la seleccion cada vez que se reabre el modal para otro usuario.
watch(() => props.isOpen, (open) => {
  if (open) selectedReason.value = null;
});

const onClose = () => {
  if (props.loading) return;
  emit('close');
};
</script>
