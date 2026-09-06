<template>
  <div class="card-duo bg-slate-900/90 border-slate-800 p-3 text-center space-y-2">
    <div class="flex items-center justify-center gap-1.5">
      <slot name="icon" />
      <div class="text-2xl font-extrabold" :class="colorClass">{{ value }}</div>
    </div>
    <div class="text-slate-300 text-sm font-semibold">{{ label }}</div>
  </div>
</template>

<script setup>
defineProps({
  value: { type: [String, Number], required: true },
  label: { type: String, required: true },
  colorClass: { type: String, default: 'text-white' }
});
</script>
