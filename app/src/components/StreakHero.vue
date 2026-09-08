<template>
  <div
    class="relative text-center p-5 -mx-5 -mt-5 overflow-hidden"
    :style="(user.is_streak_lost || user.will_use_freeze_today)
      ? 'background: radial-gradient(ellipse at top, rgba(56,189,248,0.18), transparent 65%)'
      : 'background: radial-gradient(ellipse at top, rgba(255,150,0,0.18), transparent 65%)'"
  >
    <!-- Llama animada (o congelada si la racha ya se perdió); su color/tamaño escala
         segun el hito mas alto alcanzado, para que dia 100 se vea distinto a dia 1 -->
    <div class="inline-block relative my-3">
      <div
        class="inline-block filter"
        :class="[(user.is_streak_lost || user.will_use_freeze_today) ? 'text-7xl' : `${tier.sizeClass} animate-flame-pulse`]"
        :style="(user.is_streak_lost || user.will_use_freeze_today) ? '' : `filter: drop-shadow(0 0 20px ${tier.glow})`"
      >
        {{ user.is_streak_lost ? '🥶' : (user.will_use_freeze_today ? '🧊' : tier.emoji) }}
      </div>
    </div>

    <div class="mt-2 space-y-1">
      <h2 class="text-4xl font-extrabold text-white">
        {{ user.is_streak_lost ? 0 : user.streak_count }} {{ (user.is_streak_lost ? 0 : user.streak_count) === 1 ? 'día' : 'días' }}
      </h2>
      <p
        class="font-extrabold text-base uppercase tracking-wider"
        :class="(user.is_streak_lost || user.will_use_freeze_today) ? 'text-sky-300' : 'text-amber-400'"
      >
        {{ user.is_streak_lost ? 'Racha perdida' : (user.will_use_freeze_today ? 'Racha congelada' : 'Racha de lectura activa') }}
      </p>
    </div>

    <p v-if="user.is_streak_lost" class="text-sky-100 text-base">
      Se rompió tu racha.
    </p>

    <p v-else-if="user.will_use_freeze_today" class="text-sky-100 text-base">
      ¡Lee hoy para recuperarla!
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
