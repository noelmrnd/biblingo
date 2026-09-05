<template>
  <div 
    ref="containerRef"
    class="swipe-item-wrapper relative overflow-hidden rounded-3xl select-none touch-pan-y bg-transparent"
  >
    <!-- Acción en fondo (revelada exclusivamente en la franja derecha con cobertura bajo la curvatura de la tarjeta) -->
    <div 
      v-if="!disabled"
      class="absolute top-0 bottom-0 right-0 z-0 overflow-hidden bg-rose-600 flex items-stretch justify-end"
      :class="{ 'transition-[width] duration-200 ease-out': !isDragging }"
      :style="{ width: `${currentRedWidth}px` }"
    >
      <slot name="action" :trigger="handleAction">
        <button
          type="button"
          @click.stop="handleAction"
          :style="{ width: `${Math.max(actionWidth, Math.abs(translateX))}px` }"
          class="h-full bg-rose-600 hover:bg-rose-500 active:bg-rose-700 text-white font-extrabold flex flex-col items-center justify-center gap-1.5 transition-colors px-4 py-2 cursor-pointer shadow-inner shrink-0"
          aria-label="Eliminar"
        >
          <Trash2 class="w-5 h-5 stroke-[2.5]" />
          <span class="text-xs font-semibold tracking-wider uppercase">Eliminar</span>
        </button>
      </slot>
    </div>

    <!-- Contenido frontal deslizable -->
    <div
      class="swipe-item-content relative z-10 w-full"
      :class="{ 'transition-transform duration-200 ease-out': !isDragging }"
      :style="{ transform: `translateX(${translateX}px)` }"
      @touchstart="handleTouchStart"
      @touchmove="handleTouchMove"
      @touchend="handleTouchEnd"
      @touchcancel="handleTouchEnd"
      @mousedown="handleMouseDown"
      @click.capture="handleContentClick"
    >
      <slot />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onBeforeUnmount } from 'vue';
import { Trash2 } from '@lucide/vue';

const props = defineProps({
  disabled: { type: Boolean, default: false },
  actionWidth: { type: Number, default: 84 },
  swipeThreshold: { type: Number, default: 42 },
  isOpen: { type: Boolean, default: false }
});

const emit = defineEmits(['action', 'open', 'close']);

const containerRef = ref(null);
const translateX = ref(props.isOpen ? -props.actionWidth : 0);
const isDragging = ref(false);
const isOpenInternal = ref(props.isOpen);

const currentRedWidth = computed(() => {
  if (translateX.value >= 0) return 0;
  const abs = Math.abs(translateX.value);
  const extra = Math.min(32, abs);
  return abs + extra;
});

watch(() => props.isOpen, (val) => {
  isOpenInternal.value = val;
  translateX.value = val ? -props.actionWidth : 0;
});

let startX = 0;
let startY = 0;
let axisLocked = null; // 'horizontal' | 'vertical' | null
let initialTranslateX = 0;
let hasDragged = false;

const open = () => {
  translateX.value = -props.actionWidth;
  isOpenInternal.value = true;
  emit('open');
};

const close = () => {
  translateX.value = 0;
  isOpenInternal.value = false;
  emit('close');
};

const handleAction = () => {
  emit('action');
  close();
};

const handleContentClick = (e) => {
  if (hasDragged) {
    e.stopPropagation();
    e.preventDefault();
    hasDragged = false;
    return;
  }
  if (isOpen.value) {
    e.stopPropagation();
    e.preventDefault();
    close();
  }
};

// --- Manejo táctil (Touch Events) ---
const handleTouchStart = (e) => {
  if (props.disabled || e.touches.length > 1) return;
  const touch = e.touches[0];
  startX = touch.clientX;
  startY = touch.clientY;
  initialTranslateX = translateX.value;
  axisLocked = null;
  hasDragged = false;
  isDragging.value = false;
};

const handleTouchMove = (e) => {
  if (props.disabled || !startX) return;
  const touch = e.touches[0];
  const deltaX = touch.clientX - startX;
  const deltaY = touch.clientY - startY;

  if (axisLocked === null) {
    if (Math.abs(deltaY) > 7 && Math.abs(deltaY) > Math.abs(deltaX)) {
      axisLocked = 'vertical';
      return;
    } else if (Math.abs(deltaX) > 7 && Math.abs(deltaX) > Math.abs(deltaY)) {
      axisLocked = 'horizontal';
      isDragging.value = true;
    } else {
      return;
    }
  }

  if (axisLocked === 'horizontal') {
    if (e.cancelable) e.preventDefault();
    hasDragged = true;

    let targetX = initialTranslateX + deltaX;

    // Resistencia elástica hacia la derecha
    if (targetX > 0) {
      targetX = targetX * 0.2;
    } else if (targetX < -props.actionWidth) {
      // Resistencia elástica si pasa del ancho de la acción
      const over = Math.abs(targetX + props.actionWidth);
      targetX = -props.actionWidth - (over * 0.35);
    }

    translateX.value = targetX;
  }
};

const handleTouchEnd = () => {
  if (props.disabled || axisLocked !== 'horizontal') {
    axisLocked = null;
    startX = 0;
    return;
  }

  isDragging.value = false;
  axisLocked = null;
  startX = 0;

  // Si se desliza más de la mitad del ancho de la acción, se abre
  if (translateX.value < -props.swipeThreshold) {
    // Si se desliza de forma completa (> 180px), disparar acción directamente
    if (translateX.value < -props.actionWidth * 2.2) {
      handleAction();
    } else {
      open();
    }
  } else {
    close();
  }
};

// --- Manejo para Ratón / Escritorio (Mouse Events) ---
let isMouseDown = false;

const handleMouseDown = (e) => {
  if (props.disabled || e.button !== 0) return;
  isMouseDown = true;
  startX = e.clientX;
  startY = e.clientY;
  initialTranslateX = translateX.value;
  axisLocked = null;
  hasDragged = false;
  isDragging.value = false;

  window.addEventListener('mousemove', handleMouseMove);
  window.addEventListener('mouseup', handleMouseUp);
};

const handleMouseMove = (e) => {
  if (!isMouseDown) return;
  const deltaX = e.clientX - startX;
  const deltaY = e.clientY - startY;

  if (axisLocked === null) {
    if (Math.abs(deltaY) > 5 && Math.abs(deltaY) > Math.abs(deltaX)) {
      axisLocked = 'vertical';
      return;
    } else if (Math.abs(deltaX) > 5 && Math.abs(deltaX) > Math.abs(deltaY)) {
      axisLocked = 'horizontal';
      isDragging.value = true;
    } else {
      return;
    }
  }

  if (axisLocked === 'horizontal') {
    hasDragged = true;
    let targetX = initialTranslateX + deltaX;

    if (targetX > 0) {
      targetX = targetX * 0.2;
    } else if (targetX < -props.actionWidth) {
      const over = Math.abs(targetX + props.actionWidth);
      targetX = -props.actionWidth - (over * 0.35);
    }

    translateX.value = targetX;
  }
};

const handleMouseUp = () => {
  if (!isMouseDown) return;
  isMouseDown = false;
  isDragging.value = false;

  window.removeEventListener('mousemove', handleMouseMove);
  window.removeEventListener('mouseup', handleMouseUp);

  if (axisLocked === 'horizontal') {
    if (translateX.value < -props.swipeThreshold) {
      if (translateX.value < -props.actionWidth * 2.2) {
        handleAction();
      } else {
        open();
      }
    } else {
      close();
    }
  }
  axisLocked = null;
};

onBeforeUnmount(() => {
  window.removeEventListener('mousemove', handleMouseMove);
  window.removeEventListener('mouseup', handleMouseUp);
});

defineExpose({ open, close });
</script>
