<template>
  <AppModal
    :is-open="isOpen"
    title="Cómo funciona"
    description="Racha, protectores, reacciones y toques"
    @close="$emit('close')"
  >
    <template #icon>
      <div class="w-11 h-11 rounded-2xl bg-purple-500/10 border border-purple-500/30 flex items-center justify-center shrink-0">
        <HelpCircle class="w-5 h-5 text-purple-400 stroke-[2.5]" />
      </div>
    </template>

    <div class="space-y-4">
      <div class="flex gap-3">
        <Flame class="w-5 h-5 text-amber-400 stroke-[2.5] shrink-0 mt-0.5" />
        <p class="text-slate-300 text-base font-medium">
          <strong class="text-white font-bold">Racha:</strong>
          es la cantidad de días seguidos en los que has registrado una lectura.
          Si termina el día y no la registras, tu racha se pierde y vuelve a 0.
        </p>
      </div>

      <div class="flex gap-3">
        <span class="text-xl leading-none shrink-0">🧊</span>
        <p class="text-slate-300 text-base font-medium">
          <strong class="text-white font-bold">Protectores de racha:</strong>
          te ayudan a mantener tu racha si un día no lees.
          Se usan automáticamente cuando termina el día sin registrar tu lectura.
          Ganas 1 por cada 7 días de lectura y puedes tener hasta 2.
        </p>
      </div>

      <div class="flex gap-3">
        <Heart class="w-5 h-5 text-rose-400 stroke-[2.5] shrink-0 mt-0.5" />
        <p class="text-slate-300 text-base font-medium">
          <strong class="text-white font-bold">Reacciones:</strong>
          podrás indicar cómo te hizo sentir tu lectura.
          Desde tu perfil podrás ver qué emociones experimentas con más frecuencia.
        </p>
      </div>

      <div class="flex gap-3">
        <BellRing class="w-5 h-5 text-sky-400 stroke-[2.5] shrink-0 mt-0.5" />
        <p class="text-slate-300 text-base font-medium">
          <strong class="text-white font-bold">Toques:</strong>
          son recordatorios que puedes enviar a las personas que sigues (y también te siguen) para que no olviden su lectura del día.
        </p>
      </div>
    </div>
  </AppModal>
</template>

<script setup>
import { HelpCircle, Flame, Heart, BellRing } from '@lucide/vue';
import AppModal from './AppModal.vue';

defineProps({
  isOpen: { type: Boolean, default: false }
});

defineEmits(['close']);
</script>
