<template>
  <div class="space-y-3">
    <h3 class="font-bold text-white text-lg flex items-center gap-3">
      <component :is="icon" class="w-6 h-6 stroke-[2.5]" :class="iconColorClass" />
      <span>{{ title }}</span>
    </h3>
    <slot/>
  </div>
</template>

<script setup>
defineProps({
  title: { type: String, required: true },
  // Componente de icono importado, ej. import { Trophy } from '@lucide/vue'. Se pide
  // el componente (no un nombre string) para que cada caller solo importe los iconos
  // que usa y Rollup pueda seguir haciendo tree-shaking del resto.
  icon: { type: [Object, Function], required: true },
  // Clase completa de color, ej. "text-amber-400". Literal (no armada dinamicamente)
  // para que Tailwind la detecte al escanear el codigo, igual que iconBgClass en
  // ExpandableCard.
  iconColorClass: { type: String, default: 'text-amber-400' }
});
</script>
