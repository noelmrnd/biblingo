<template>
  <div class="relative text-center pt-6 pb-8 px-6 -mx-4 -mt-4 overflow-hidden bg-[radial-gradient(ellipse_at_top,_rgba(255,150,0,0.18),_transparent_62%)]">
    <!-- Llama animada (o congelada si la racha ya se perdió); su color/tamaño escala
         segun el hito mas alto alcanzado, para que dia 100 se vea distinto a dia 1 -->
    <div class="inline-block relative my-3">
      <div
        class="inline-block filter"
        :class="[user.is_streak_lost ? 'text-7xl' : `${tier.sizeClass} animate-flame-pulse`]"
        :style="user.is_streak_lost ? '' : `filter: drop-shadow(0 0 20px ${tier.glow})`"
      >
        {{ user.is_streak_lost ? '🥶' : tier.emoji }}
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

    <p v-if="user.is_streak_lost" class="text-slate-200 text-base mt-3">
      Se rompió tu racha.
    </p>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { getStreakTier } from '../constants';

const props = defineProps({
  user: { type: Object, required: true }
});

const tier = computed(() => getStreakTier(props.user.streak_count ?? 0));
</script>
