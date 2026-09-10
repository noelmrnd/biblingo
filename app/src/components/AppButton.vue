<template>
  <button
    :type="type"
    :disabled="disabled || readonly || loading"
    @click="handleClick"
    :class="[
      'font-bold rounded-2xl transition-all duration-150 shadow-md active:shadow-sm active:scale-[0.97] active:brightness-95 inline-flex items-center justify-center cursor-pointer select-none box-border',
      'disabled:opacity-50 disabled:cursor-not-allowed disabled:pointer-events-none',
      'whitespace-nowrap',
      size === 'lg' ? 'gap-3' : 'gap-2',
      block ? 'w-full' : '',
      readonly ? '!opacity-100 !cursor-default': '',
      sizeClasses,
      colorClass,
    ]"
  >
    <span v-if="loading" :class="iconSizeClass" class="border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
    <component v-else-if="icon && iconPosition === 'start'" :is="icon" :class="iconSizeClass" />
    <span v-if="text">{{ loadingText && loading ? loadingText : text }}</span>
    <slot/>
    <component v-if="!loading && icon && iconPosition === 'end'" :is="icon" :class="iconSizeClass" />
  </button>
</template>

<script setup>
import { computed } from 'vue';
import { HapticsService } from '@/services/haptics';

const props = defineProps({
  color: {
    type: String,
    default: 'green',
    validator: (val) => ['green', 'blue', 'card', 'nudge', 'rose'].includes(val)
  },
  size: {
    type: String,
    default: 'md',
    validator: (val) => ['sm', 'md', 'lg'].includes(val)
  },
  block: {
    type: Boolean,
    default: false
  },
  type: {
    type: String,
    default: 'button'
  },
  disabled: {
    type: Boolean,
    default: false
  },
  readonly: {
    type: Boolean,
    default: false
  },
  loading: {
    type: Boolean,
    default: false
  },
  haptic: {
    type: String,
    default: 'light',
    validator: (val) => ['light', 'medium', 'heavy', 'success', 'warning', 'error', 'none'].includes(val)
  },
  // Contenido estandar del boton (icon + texto). El <slot/> del template
  // sigue disponible para casos que necesiten markup custom (no usado hoy
  // por ningun caller).
  icon: { type: [Object, Function], default: null },
  text: { type: String, default: '' },
  // Texto a mostrar en lugar de `text` mientras `loading` es true, ej. "Enviando...".
  loadingText: { type: String, default: '' },
  iconPosition: {
    type: String,
    default: 'start',
    validator: (val) => ['start', 'end'].includes(val)
  }
});

const handleClick = () => {
  if (props.disabled || props.readonly || props.loading) return;
  if (props.haptic !== 'none') HapticsService[props.haptic]();
};

const sizeClasses = computed(() => {
  switch (props.size) {
    case 'sm':
      return 'h-10 min-h-[40px] px-3.5 text-sm';
    case 'lg':
      return 'h-16 min-h-[64px] px-6 text-xl';
    case 'md':
    default:
      return 'h-[52px] min-h-[52px] px-5 text-base';
  }
});

// Coincide con el tamaño que cada caller usaba manualmente antes de migrar al
// fallback icon+text: w-6 h-6 en botones "lg", w-5 h-5 en el resto.
const iconSizeClass = computed(() => props.size === 'lg' ? 'w-6 h-6 stroke-[2.5]' : 'w-5 h-5 stroke-[2.5]');

const colorClass = computed(() => {
  switch (props.color) {
    case 'blue':
      return 'bg-brand-blue hover:bg-sky-500 text-white';
    case 'card':
      return 'bg-brand-card hover:bg-slate-800 text-slate-200 border border-brand-border';
    case 'nudge':
      return 'bg-brand-nudge hover:bg-brand-nudge-dark text-white';
    case 'rose':
      return 'bg-rose-600 hover:bg-rose-500 text-white';
    case 'green':
    default:
      return 'bg-brand-green hover:bg-brand-green-dark text-white';
  }
});
</script>
