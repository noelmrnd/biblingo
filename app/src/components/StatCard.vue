<template>
  <div class="card-duo bg-slate-900/90 border-slate-800 p-3 text-center space-y-2">
    <div class="flex items-center justify-center gap-2">
      <slot name="icon">
        <span v-if="emoji" class="text-2xl leading-none">{{ emoji }}</span>
        <component v-else-if="icon" :is="icon" class="w-5 h-5 stroke-[2.5]" :class="iconColorClass" />
      </slot>
      <div class="text-2xl font-bold" :class="colorClass">{{ value }}</div>
    </div>
    <div class="text-slate-300 text-base font-medium">{{ label }}</div>
  </div>
</template>

<script setup>
defineProps({
  value: { type: [String, Number], required: true },
  label: { type: String, required: true },
  colorClass: { type: String, default: 'text-white' },
  // Atajos para el caso simple de un solo icono fijo (sin logica condicional):
  // evitan el slot #icon boilerplate. Para casos con estados condicionales
  // (ej. racha perdida/congelada/activa) seguir usando el slot #icon.
  icon: { type: [Object, Function], default: null },
  emoji: { type: String, default: null },
  iconColorClass: { type: String, default: 'text-white' }
});
</script>
