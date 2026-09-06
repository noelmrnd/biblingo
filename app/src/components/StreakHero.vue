<template>
  <div class="relative card-duo bg-slate-900 bg-[radial-gradient(ellipse_at_top_right,_rgba(255,150,0,0.22),_transparent_65%)] border-amber-500/30 text-center py-8 px-6 overflow-hidden">
    <!-- Llama animada (o congelada si la racha ya se perdió) -->
    <div class="inline-block relative my-3">
      <div
        class="text-7xl inline-block filter drop-shadow-[0_0_20px_rgba(255,150,0,0.8)]"
        :class="user.is_streak_lost ? '' : 'animate-flame-pulse'"
      >
        {{ user.is_streak_lost ? '🥶' : '🔥' }}
      </div>
      <div class="absolute -bottom-2 right-0 bg-amber-400 text-slate-950 font-black text-base px-2.5 py-0.5 rounded-full shadow">
        x{{ user.is_streak_lost ? 0 : user.streak_count }}
      </div>
    </div>

    <div class="mt-2 space-y-1">
      <h2 class="text-4xl font-extrabold text-white">
        {{ user.is_streak_lost ? 0 : user.streak_count }} {{ (user.is_streak_lost ? 0 : user.streak_count) === 1 ? 'día' : 'días' }}
      </h2>
      <p class="text-amber-400 font-extrabold text-base uppercase tracking-wider">
        {{ user.is_streak_lost ? 'Racha perdida' : 'Racha de lectura activa' }}
      </p>
    </div>

    <!-- Subtítulo / Badge de Estado -->
    <div v-if="hasReadToday === true" class="inline-flex flex-wrap items-center justify-center gap-2 bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 px-4 py-1.5 rounded-full text-base font-extrabold mt-3 shadow-inner">
      <CheckCircle2 class="w-5 h-5 text-emerald-400 stroke-[2.5]" />
      <span>¡Perfecto, ya leíste hoy!</span>
    </div>
    <p v-else-if="user.is_streak_lost" class="text-slate-200 text-base mt-3">
      Se rompió tu racha.
    </p>
  </div>
</template>

<script setup>
import { CheckCircle2 } from '@lucide/vue';

defineProps({
  user: { type: Object, required: true },
  hasReadToday: { type: Boolean, default: null }
});
</script>
