<template>
  <div class="space-y-1.5">
    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider">{{ label }}</label>
    <slot />
    <p v-if="error" class="text-rose-400 text-xs font-semibold">{{ error }}</p>
  </div>
</template>

<script setup>
defineProps({
  label: { type: String, required: true },
  error: { type: String, default: '' }
});
</script>
