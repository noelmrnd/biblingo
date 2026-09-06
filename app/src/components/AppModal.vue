<template>
  <Teleport to="body">
    <Transition name="modal-fade">
      <div
        v-if="isOpen"
        class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/75 backdrop-blur-sm p-0 sm:p-4"
        @click.self="onBackdropClick"
      >
        <!-- Sheet / Modal Card -->
        <div
          :class="[maxWidth]"
          class="modal-card bg-slate-900 border-t sm:border border-slate-700/80 rounded-t-3xl sm:rounded-3xl shadow-2xl w-full pb-safe-cond flex flex-col max-h-[90vh] sm:max-h-[85vh] overflow-hidden"
        >
          <div class="p-5">

            <!-- Header (Fijo arriba si hay título, icono o slot header) -->
            <div v-if="$slots.header || title || $slots.icon" class="flex-shrink-0 pb-6">
              <slot name="header">
                <div class="flex items-start justify-between gap-3">
                  <div class="flex items-start gap-3 min-w-0">
                    <slot name="icon" />
                    <div class="space-y-1 min-w-0">
                      <h3 v-if="title" class="text-xl text-white font-black leading-tight">
                        {{ title }}
                      </h3>
                      <p v-if="description" class="text-base text-slate-300 font-medium">
                        {{ description }}
                      </p>
                    </div>
                  </div>
                  <button
                    v-if="showClose"
                    @click="onClose"
                    :disabled="loading"
                    class="p-2 -mr-1 -mt-1 text-slate-400 hover:text-white rounded-full transition-colors cursor-pointer disabled:opacity-50 flex-shrink-0"
                    aria-label="Cerrar"
                  >
                    <X class="w-5 h-5 stroke-[2.5]" />
                  </button>
                </div>
              </slot>
            </div>

            <!-- Body / Contenido Principal -->
            <div v-if="$slots.default" class="flex-1 overflow-y-auto min-h-0 no-scrollbar overscroll-contain">
              <slot />
            </div>

            <!-- Footer (Acciones) -->
            <div v-if="$slots.footer" class="flex-shrink-0 pt-3 border-t border-slate-800/80">
              <slot name="footer" />
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, watch, onBeforeUnmount } from 'vue';
import { X } from '@lucide/vue';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    default: ''
  },
  description: {
    type: String,
    default: ''
  },
  loading: {
    type: Boolean,
    default: false
  },
  showClose: {
    type: Boolean,
    default: true
  },
  closeOnBackdrop: {
    type: Boolean,
    default: undefined
  },
  maxWidth: {
    type: String,
    default: 'max-w-md'
  }
});

const emit = defineEmits(['close']);

const canCloseOnBackdrop = computed(() => {
  if (props.closeOnBackdrop !== undefined) {
    return props.closeOnBackdrop;
  }
  return props.showClose;
});

const onClose = () => {
  if (props.loading) return;
  emit('close');
};

const onBackdropClick = () => {
  if (props.loading || !canCloseOnBackdrop.value) return;
  emit('close');
};

const handleKeyDown = (e) => {
  if (e.key === 'Escape' && props.isOpen && !props.loading && canCloseOnBackdrop.value) {
    onClose();
  }
};

watch(() => props.isOpen, (open) => {
  if (open) {
    window.addEventListener('keydown', handleKeyDown);
  } else {
    window.removeEventListener('keydown', handleKeyDown);
  }
}, { immediate: true });

onBeforeUnmount(() => {
  window.removeEventListener('keydown', handleKeyDown);
});
</script>

<style scoped>
/* Backdrop fade */
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.3s cubic-bezier(0.16, 1, 0.3, 1);
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
