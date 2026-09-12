<template>
  <div class="text-center space-y-1">
    <div class="flex items-center justify-center gap-2">
      <component :is="icon" class="w-5 h-5 stroke-[2.5]" :class="iconColorClass" />
      <span class="text-2xl font-bold" :class="colorClass">{{ value }}</span>
    </div>
    <p class="text-slate-300 text-base font-medium" :class="labelClass">{{ label }}</p>
  </div>
</template>

<script setup>
defineProps({
  value: { type: [String, Number], required: true },
  label: { type: String, required: true },
  labelClass: { type: String },
  colorClass: { type: String, default: 'text-white' },
  icon: { type: [Object, Function], default: null },
  iconColorClass: { type: String, default: 'text-white' }
});
</script>
