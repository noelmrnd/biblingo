<template>
  <button
    @click="handleClick"
    :class="variantClass"
    class="w-full font-bold rounded-2xl border-2 transition-colors flex items-center justify-center cursor-pointer"
  >
    <component :is="icon" :class="iconClass" class="stroke-[2.5]" />
    <span><slot /></span>
  </button>
</template>

<script setup>
import { computed } from 'vue';
import { HapticsService } from '../services/haptics';

const props = defineProps({
  icon: { type: [Object, Function], required: true },
  variant: {
    type: String,
    default: 'default',
    validator: (val) => ['default', 'danger', 'danger-subtle'].includes(val)
  },
  haptic: {
    type: String,
    default: 'light',
    validator: (val) => ['light', 'warning'].includes(val)
  }
});

const emit = defineEmits(['click']);

const variantClass = computed(() => {
  switch (props.variant) {
    case 'danger':
      return 'py-3.5 px-4 gap-3 text-base bg-slate-800 hover:bg-rose-950/40 text-slate-400 hover:text-rose-300 border-slate-700 hover:border-rose-800';
    case 'danger-subtle':
      return 'py-3 px-4 gap-2.5 text-sm bg-transparent hover:bg-rose-950/20 text-rose-500/70 hover:text-rose-400 border-transparent';
    case 'default':
    default:
      return 'py-3.5 px-4 gap-3 text-base bg-slate-800/90 hover:bg-slate-700/80 text-slate-300 hover:text-white border-slate-700';
  }
});

const iconClass = computed(() => (props.variant === 'danger-subtle' ? 'w-4 h-4' : 'w-5 h-5'));

const handleClick = (e) => {
  HapticsService[props.haptic]();
  emit('click', e);
};
</script>
