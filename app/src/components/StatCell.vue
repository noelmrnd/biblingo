<template>
  <div class="text-center space-y-1">
    <div class="flex items-center justify-center gap-2">
      <slot name="icon">
        <span v-if="emoji" class="text-2xl leading-none">{{ emoji }}</span>
        <component v-else-if="icon" :is="icon" class="w-5 h-5 stroke-[2.5]" :class="iconColorClass" />
      </slot>
      <span class="text-2xl font-bold" :class="colorClass">{{ value }}</span>
    </div>
    <p class="text-slate-300 text-sm font-semibold">{{ label }}</p>
  </div>
</template>

<script setup>
defineProps({
  value: { type: [String, Number], required: true },
  label: { type: String, required: true },
  colorClass: { type: String, default: 'text-white' },
  icon: { type: [Object, Function], default: null },
  emoji: { type: String, default: null },
  iconColorClass: { type: String, default: 'text-white' }
});
</script>
