<template>
  <div class="card-duo bg-slate-900/90 border-slate-800 p-3 text-center space-y-2">
    <div class="flex items-center justify-center gap-2">
      <component :is="icon" class="w-5 h-5 stroke-[2.5]" :class="iconColorClass" />
      <div class="text-2xl font-bold" :class="colorClass">{{ value }}</div>
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
  // Atajos para el caso simple de un solo icono fijo (sin logica condicional):
  // evitan el slot #icon boilerplate. Para casos con estados condicionales
  // (ej. racha perdida/congelada/activa) seguir usando el slot #icon.
  icon: { type: [Object, Function], default: null },
  iconColorClass: { type: String, default: 'text-white' }
});
</script>
