<template>
  <button
    type="button"
    :disabled="disabled"
    @click="handleClick"
    :class="[
      sizeClass,
      'text-slate-400 hover:text-white rounded-full transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed'
    ]"
  >
    <slot />
  </button>
</template>

<script setup>
import { computed } from 'vue';
import { HapticsService } from '../services/haptics';

const props = defineProps({
  size: {
    type: String,
    default: 'md',
    validator: (val) => ['sm', 'md'].includes(val)
  },
  disabled: {
    type: Boolean,
    default: false
  },
  haptic: {
    type: Boolean,
    default: true
  }
});

const emit = defineEmits(['click']);

const sizeClass = computed(() => (props.size === 'sm' ? 'p-1.5' : 'p-2'));

const handleClick = (e) => {
  if (props.disabled) return;
  if (props.haptic) HapticsService.light();
  emit('click', e);
};
</script>
