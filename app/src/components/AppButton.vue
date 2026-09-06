<template>
  <button
    :type="type"
    :disabled="disabled || readonly || loading"
    :class="[
      'font-extrabold rounded-2xl transition-all duration-150 shadow-md active:shadow-sm active:scale-[0.97] active:brightness-95 inline-flex items-center justify-center gap-2 cursor-pointer select-none box-border',
      'disabled:opacity-50 disabled:cursor-not-allowed disabled:pointer-events-none',
      'whitespace-nowrap',
      block ? 'w-full' : '',
      readonly ? '!opacity-100 !cursor-default': '',
      sizeClasses,
      colorClass,
    ]"
  >
    <slot />
  </button>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  color: {
    type: String,
    default: 'green',
    validator: (val) => ['green', 'blue', 'dark', 'orange', 'rose'].includes(val)
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
  }
});

const sizeClasses = computed(() => {
  switch (props.size) {
    case 'sm':
      return 'h-10 min-h-[40px] px-3.5 text-sm';
    case 'lg':
      return 'h-16 min-h-[64px] px-6 text-lg';
    case 'md':
    default:
      return 'h-[52px] min-h-[52px] px-5 text-base';
  }
});

const colorClass = computed(() => {
  switch (props.color) {
    case 'blue':
      return 'bg-brand-blue hover:bg-sky-500 text-white';
    case 'dark':
      return 'bg-brand-card hover:bg-slate-800 text-slate-200';
    case 'orange':
      return 'bg-brand-flame hover:bg-brand-flame-dark text-white';
    case 'rose':
      return 'bg-rose-600 hover:bg-rose-500 text-white';
    case 'green':
    default:
      return 'bg-brand-green hover:bg-brand-green-dark text-white';
  }
});
</script>
