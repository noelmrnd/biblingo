<template>
  <button
    type="button"
    role="switch"
    :aria-checked="modelValue"
    :disabled="disabled"
    @click="$emit('update:modelValue', !modelValue)"
    :class="modelValue ? 'bg-brand-green' : 'bg-slate-700'"
    class="relative w-12 h-7 rounded-full transition-colors shrink-0 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
  >
    <span
      :class="modelValue ? 'translate-x-5' : 'translate-x-0.5'"
      class="absolute top-0.5 left-0 w-6 h-6 bg-white rounded-full shadow-md transition-transform"
    />
  </button>
</template>

<script setup>
defineProps({
  modelValue: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false }
});

defineEmits(['update:modelValue']);
</script>
