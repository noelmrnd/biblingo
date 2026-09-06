<template>
  <header class="flex-none z-30 bg-brand-dark/90 backdrop-blur-md border-b border-brand-border px-4 py-3 pt-safe flex items-center justify-between shadow-md">
    <div class="flex items-center gap-3">
      <div class="w-8 h-8">
        <img src="/assets/logo-256.png" alt="Biblingo Logo" class="w-full h-full object-contain" />
      </div>
      <span class="font-extrabold text-xl tracking-tight text-white">Biblingo</span>
    </div>

    <div class="flex items-center gap-2">
      <!-- Protectores de racha disponibles -->
      <div
        v-if="streakFreezes > 0"
        class="flex items-center gap-1.5 bg-slate-900 border border-sky-500/30 px-3 py-1 rounded-full shadow-inner"
      >
        <span class="text-base leading-none">🧊</span>
        <span class="font-bold text-sky-300 text-lg">{{ streakFreezes }}</span>
      </div>

      <!-- Racha activa en la barra superior -->
      <div class="flex items-center gap-2 bg-slate-900 border border-amber-500/30 px-4 py-1 rounded-full shadow-inner">
        <span class="text-lg animate-flame-pulse">🔥</span>
        <span class="font-bold text-amber-400 text-xl">{{ streakCount }}</span>
      </div>
    </div>
  </header>
</template>

<script setup>
defineProps({
  streakCount: {
    type: Number,
    default: 0
  },
  streakFreezes: {
    type: Number,
    default: 0
  }
});
</script>
