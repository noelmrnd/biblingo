<template>
  <div class="space-y-6">
    <button
      type="button"
      @click="$emit('back')"
      class="flex items-center gap-2 text-slate-300 hover:text-white font-bold text-base cursor-pointer"
    >
      <ArrowLeft class="w-5 h-5 stroke-[2.5]" />
      <span>Amigos</span>
    </button>

    <div class="relative card-duo bg-slate-900 bg-[radial-gradient(ellipse_at_top_right,_rgba(28,176,246,0.22),_transparent_65%)] border-sky-500/30 text-center py-6 space-y-3 overflow-hidden">
      <div class="w-20 h-20 bg-gradient-to-tr from-brand-blue to-sky-400 rounded-full flex items-center justify-center shadow-xl mx-auto border-4 border-slate-800 text-white font-black text-3xl">
        {{ (friend.display_name || '?').charAt(0).toUpperCase() }}
      </div>
      <div>
        <h2 class="text-2xl font-extrabold text-white">{{ friend.display_name }}</h2>
        <p class="text-slate-300 text-base font-medium">{{ friend.last_read_label }}</p>
      </div>
    </div>

    <div class="grid grid-cols-2 gap-3">
      <div class="card-duo bg-slate-900/90 border-slate-800 p-4 text-center space-y-2">
        <div class="flex items-center justify-center gap-3">
          <span v-if="friend.is_streak_lost" class="text-3xl leading-none">🥶</span>
          <Flame v-else class="w-7 h-7 text-amber-400 stroke-[2.5]" />
          <div class="text-3xl font-extrabold text-amber-400">{{ friend.streak_count }}</div>
        </div>
        <div class="text-slate-300 text-base font-semibold uppercase tracking-wider">Racha<br/>actual</div>
      </div>
      <div class="card-duo bg-slate-900/90 border-slate-800 p-4 text-center space-y-2">
        <div class="flex items-center justify-center gap-3">
          <Zap class="w-7 h-7 text-purple-400 stroke-[2.5]" />
          <div class="text-3xl font-extrabold text-purple-400">{{ friend.max_streak_count }}</div>
        </div>
        <div class="text-slate-300 text-base font-semibold uppercase tracking-wider">Racha<br/>máxima</div>
      </div>
    </div>

    <!-- Dar un toque -->
    <button
      v-if="!friend.has_read_today"
      @click="$emit('nudge', friend)"
      :disabled="nudged || nudgeLoading"
      class="w-full bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 disabled:from-slate-800 disabled:to-slate-800 disabled:text-slate-500 text-slate-950 font-bold py-3.5 px-4 rounded-2xl flex items-center justify-center gap-2 shadow-md active:scale-95 transition-all cursor-pointer border border-amber-400/40 disabled:border-slate-700"
    >
      <BellRing class="w-5 h-5 stroke-[2.5]" />
      <span>{{ nudged ? 'Toque enviado' : 'Dar un toque' }}</span>
    </button>

    <!-- Eliminar amigo -->
    <button
      type="button"
      @click="$emit('remove', friend)"
      class="w-full bg-slate-800 hover:bg-rose-950/40 text-slate-400 hover:text-rose-300 font-bold py-3.5 px-4 rounded-2xl border-2 border-slate-700 hover:border-rose-800 transition-colors text-base flex items-center justify-center gap-3 cursor-pointer"
    >
      <UserMinus class="w-5 h-5 text-rose-400 stroke-[2.5]" />
      <span>Eliminar amigo</span>
    </button>
  </div>
</template>

<script setup>
import { ArrowLeft, Flame, Zap, BellRing, UserMinus } from '@lucide/vue';

defineProps({
  friend: { type: Object, required: true },
  nudged: { type: Boolean, default: false },
  nudgeLoading: { type: Boolean, default: false }
});

defineEmits(['back', 'nudge', 'remove']);
</script>
