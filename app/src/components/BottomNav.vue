<template>
  <nav class="flex-none z-30 bg-slate-950/95 backdrop-blur-lg border-t border-brand-border pt-2 pb-safe-sm px-4">
    <div class="max-w-md mx-auto flex justify-between items-center">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        type="button"
        @click="router.push(tab.to)"
        :class="[
          isActive(tab)
            ? tab.activeColor
            : 'text-slate-400 hover:text-slate-200'
        ]"
        class="flex-1 py-1 px-1 flex flex-col items-center justify-center gap-0.5 rounded-2xl transition-colors duration-200 cursor-pointer select-none relative active:scale-95 transition-transform"
        :aria-label="tab.label"
      >
        <!-- Glow radial en el tab activo -->
        <div
          v-if="isActive(tab)"
          :style="{
            background: `radial-gradient(ellipse at center, ${tab.glowColor} 0%, transparent 70%)`
          }"
          class="absolute -inset-y-1 -inset-x-2 blur-md pointer-events-none rounded-3xl"
        ></div>

        <!-- Icono -->
        <component
          :is="tab.icon"
          class="w-6 h-6 stroke-[2.5] pointer-events-none relative z-10"
        />

        <!-- Texto -->
        <span class="text-sm font-bold tracking-wide pointer-events-none relative z-10">
          {{ tab.label }}
        </span>
      </button>
    </div>
  </nav>
</template>

<script setup>
import { useRoute, useRouter } from 'vue-router';
import { Flame, UsersRound, UserRound } from '@lucide/vue';

const route = useRoute();
const router = useRouter();

const tabs = [
  {
    id: 'dashboard',
    label: 'Racha',
    to: { name: 'dashboard' },
    icon: Flame,
    activeColor: 'text-brand-flame',
    glowColor: 'rgba(255,150,0,0.35)'
  },
  {
    id: 'friends',
    label: 'Amigos',
    to: { name: 'friends' },
    icon: UsersRound,
    activeColor: 'text-brand-green',
    glowColor: 'rgba(88,204,2,0.35)',
    matchNames: ['friends', 'friend-profile']
  },
  {
    id: 'profile',
    label: 'Perfil',
    to: { name: 'profile' },
    icon: UserRound,
    activeColor: 'text-brand-blue',
    glowColor: 'rgba(28,176,246,0.35)'
  }
];

const isActive = (tab) => {
  return (tab.matchNames || [tab.id]).includes(route.name);
};
</script>
