<template>
  <div :class="cardClass" class="card-duo transition-all duration-200 relative overflow-hidden">
    <div
      @click="collapsible && toggle()"
      :class="collapsible ? 'cursor-pointer select-none' : ''"
      class="flex items-center justify-between gap-3"
    >
      <div class="flex items-center gap-3 min-w-0">
        <div :class="iconBgClass" class="w-10 h-10 rounded-2xl border flex items-center justify-center shrink-0">
          <component :is="icon" class="w-5 h-5 stroke-[2.5]" :class="iconColorClass" />
        </div>
        <div class="min-w-0">
          <h3 class="font-semibold text-white text-lg truncate">{{ title }}</h3>
          <p v-if="description" class="text-slate-300 text-base font-medium">{{ description }}</p>
        </div>
      </div>

      <component
        v-if="collapsible"
        :is="modelValue ? ChevronUp : ChevronDown"
        class="w-6 h-6 text-slate-400 stroke-[2.5] transition-transform duration-200 shrink-0"
      />
      <div v-else class="shrink-0">
        <slot name="action" />
      </div>
    </div>

    <div v-if="(!collapsible && $slots.default) || (collapsible && modelValue)" class="space-y-4 mt-4">
      <slot />
    </div>
  </div>
</template>

<script setup>
import { ChevronDown, ChevronUp } from '@lucide/vue';

const props = defineProps({
  title: { type: String, required: true },
  description: { type: String, default: '' },
  // Componente de icono importado, ej. import { UserCheck } from '@lucide/vue'.
  icon: { type: [Object, Function], required: true },
  // Clases completas de Tailwind, literales (no armadas dinamicamente) para que
  // Tailwind las detecte al escanear el codigo.
  iconBgClass: { type: String, default: 'bg-slate-500/10 border-slate-500/30' },
  iconColorClass: { type: String, default: 'text-slate-300' },
  // Clases extra para el contenedor de la card, ej. un fondo con gradiente y borde
  // de color propio (ver "Invitar amigos"). Se suman a "card-duo".
  cardClass: { type: String, default: '' },
  collapsible: { type: Boolean, default: true },
  modelValue: { type: Boolean, default: false }
});

const emit = defineEmits(['update:modelValue']);

const toggle = () => emit('update:modelValue', !props.modelValue);
</script>
