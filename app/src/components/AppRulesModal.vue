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
          <strong class="text-white font-bold">Racha:</strong> lee al menos una vez al día para mantenerla. Si dejas pasar un día completo sin registrar lectura (y no tienes protectores), la racha se reinicia a 0.
        </p>
      </div>

      <div class="flex gap-3">
        <span class="text-xl leading-none shrink-0">🧊</span>
        <p class="text-slate-300 text-base font-medium">
          <strong class="text-white font-bold">Protectores de racha:</strong> ganas uno automáticamente cada 7 días consecutivos de racha activa (máximo 2 acumulados). Si te saltas exactamente un día, un protector lo cubre solo y tu racha sigue intacta.
        </p>
      </div>

      <div class="flex gap-3">
        <Heart class="w-5 h-5 text-rose-400 stroke-[2.5] shrink-0 mt-0.5" />
        <p class="text-slate-300 text-base font-medium">
          <strong class="text-white font-bold">Reacciones:</strong> al registrar tu lectura puedes elegir cómo te sentiste. Se acumulan en tu perfil para que veas cuáles se repiten más.
        </p>
      </div>

      <div class="flex gap-3">
        <BellRing class="w-5 h-5 text-sky-400 stroke-[2.5] shrink-0 mt-0.5" />
        <p class="text-slate-300 text-base font-medium">
          <strong class="text-white font-bold">Toques:</strong> solo puedes darle un toque a amigos que te siguen y sigues de vuelta (mutuo), y que aún no hayan leído hoy. Máximo uno por persona por día.
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
