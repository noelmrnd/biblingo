<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :class="[
      'font-extrabold rounded-2xl border-b-4 active:translate-y-1 transition-all duration-75 shadow-lg inline-flex items-center justify-center gap-2 cursor-pointer select-none box-border',
      'disabled:opacity-50 disabled:cursor-not-allowed disabled:pointer-events-none disabled:active:translate-y-0',
      'whitespace-nowrap',
      block ? 'w-full' : '',
      sizeClasses,
      colorClass
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
    validator: (val) => ['green', 'blue', 'dark', 'orange'].includes(val)
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
      return 'bg-brand-blue hover:bg-sky-500 text-white border-sky-700';
    case 'dark':
      return 'bg-brand-card hover:bg-slate-800 text-slate-200 border-slate-900';
    case 'orange':
      return 'bg-brand-flame hover:bg-brand-flame-dark text-white border-amber-700';
    case 'green':
    default:
      return 'bg-brand-green hover:bg-brand-green-dark text-white border-emerald-700';
  }
});
</script>
