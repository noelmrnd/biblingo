<template>
  <Teleport to="body">
    <Transition name="modal-fade">
      <div
        v-if="isOpen"
        class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/75 backdrop-blur-sm p-0 sm:p-4"
        @click.self="onClose"
      >
        <!-- Sheet / Modal Card -->
        <div
          class="modal-card bg-slate-900 border-t sm:border border-slate-700/80 rounded-t-3xl sm:rounded-3xl shadow-2xl w-full max-w-md p-5 pb-safe flex flex-col max-h-[90vh] sm:max-h-[85vh] overflow-hidden"
        >
          <!-- Header (Fijo arriba) -->
          <div class="flex-shrink-0 pb-2">
            <div class="flex items-start justify-between gap-3">
              <div class="space-y-1">
                <h3 class="text-xl font-black text-white tracking-tight flex items-center gap-2">
                  <span>¿Cómo estuvo tu lectura?</span>
                </h3>
                <p class="text-sm text-slate-300 font-medium">
                  Elige tu reacción sobre lo que leíste hoy:
                </p>
              </div>
              <button
                @click="onClose"
                :disabled="loading"
                class="p-2 -mr-1 -mt-1 text-slate-400 hover:text-white rounded-full transition-colors cursor-pointer disabled:opacity-50"
                aria-label="Cerrar"
              >
                <X class="w-5 h-5 stroke-[2.5]" />
              </button>
            </div>
          </div>

          <!-- Opciones de Reacción con Scroll (con espacio horizontal para que el contorno/ring no se recorte) -->
          <div class="flex-1 overflow-y-auto min-h-0 space-y-2.5 py-2 px-2 -mx-2 no-scrollbar overscroll-contain">
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
              class="w-full text-left p-3.5 rounded-2xl border-2 transition-all duration-200 flex items-center justify-between gap-3 cursor-pointer select-none group"
            >
              <div class="flex items-center gap-3.5 min-w-0">
                <span class="text-2xl filter drop-shadow-sm flex-shrink-0 transition-transform duration-200 group-hover:scale-110 group-active:scale-95">
                  {{ item.emoji }}
                </span>
                <div class="min-w-0">
                  <p class="font-black text-base text-white tracking-wide truncate">
                    {{ item.label }}
                  </p>
                  <p class="text-xs text-slate-400 font-medium truncate">
                    {{ item.desc }}
                  </p>
                </div>
              </div>

              <!-- Radio check indicator -->
              <div
                :class="[
                  selectedReaction === item.id
                    ? 'bg-brand-green border-brand-green text-white scale-105'
                    : 'border-slate-700 bg-slate-900/80 text-transparent'
                ]"
                class="w-6 h-6 rounded-full border-2 flex items-center justify-center flex-shrink-0 transition-all duration-200 shadow-inner"
              >
                <Check class="w-3.5 h-3.5 stroke-[3]" />
              </div>
            </button>
          </div>

          <!-- Botón de Confirmación (Fijo abajo, siempre visible) -->
          <div class="flex-shrink-0 pt-3 border-t border-slate-800/80">
            <button
              @click="onConfirm"
              :disabled="!selectedReaction || loading"
              class="btn-3d-green w-full py-4 text-base font-black rounded-2xl flex items-center justify-center gap-2 transition-all disabled:opacity-40 disabled:cursor-not-allowed disabled:pointer-events-none"
            >
              <span v-if="loading" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
              <BookOpen v-else class="w-5 h-5 stroke-[2.5]" />
              <span>{{ loading ? 'Registrando lectura...' : 'Registrar lectura' }}</span>
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, watch } from 'vue';
import { X, Check, BookOpen } from '@lucide/vue';
import { READING_REACTIONS } from '../constants';

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

<style scoped>
/* Backdrop fade */
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.5s cubic-bezier(0.16, 1, 0.3, 1);
}

.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}

/* Card slide-up & slide-down (bottom-sheet on mobile) */
.modal-fade-enter-active .modal-card {
  animation: sheet-enter 0.30s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.modal-fade-leave-active .modal-card {
  animation: sheet-leave 0.25s cubic-bezier(0.4, 0, 1, 1) forwards;
}

@keyframes sheet-enter {
  0% {
    opacity: 0;
    transform: translateY(100%);
  }
  100% {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes sheet-leave {
  0% {
    opacity: 1;
    transform: translateY(0);
  }
  100% {
    opacity: 0;
    transform: translateY(100%);
  }
}

/* Sm screens and larger: subtle scale + translateY popup */
@media (min-width: 640px) {
  @keyframes sheet-enter {
    0% {
      opacity: 0;
      transform: translateY(24px) scale(0.94);
    }
    100% {
      opacity: 1;
      transform: translateY(0) scale(1);
    }
  }

  @keyframes sheet-leave {
    0% {
      opacity: 1;
      transform: translateY(0) scale(1);
    }
    100% {
      opacity: 0;
      transform: translateY(24px) scale(0.94);
    }
  }
}
</style>
