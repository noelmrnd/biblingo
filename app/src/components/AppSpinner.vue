<template>
  <div :class="sizeClass" class="border-slate-700 border-t-brand-green rounded-full animate-spin" :style="{ borderWidth }"></div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  size: {
    type: String,
    default: 'md',
    validator: (val) => ['sm', 'md', 'lg'].includes(val)
  }
});

const sizeClass = computed(() => {
  switch (props.size) {
    case 'sm': return 'w-5 h-5';
    case 'lg': return 'w-10 h-10';
    case 'md':
    default: return 'w-7 h-7';
  }
});

const borderWidth = computed(() => props.size === 'lg' ? '4px' : '3px');
</script>
