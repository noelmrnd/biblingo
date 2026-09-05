<template>
  <div class="card-duo space-y-4">
    <h3 class="font-extrabold text-white text-lg flex items-center gap-3">
      <Calendar class="w-5 h-5 text-amber-400 stroke-[2.5]" />
      <span>Esta semana</span>
    </h3>
    <div class="grid grid-cols-7 gap-1.5 text-center">
      <div
        v-for="(day, index) in weekDays"
        :key="index"
        class="flex flex-col items-center space-y-2"
      >
        <span class="text-base font-extrabold text-slate-300">{{ day.label }}</span>
        <div
          :class="[
            day.isRead ? 'bg-brand-green text-white border-emerald-600 shadow-emerald-500/30' : 'bg-slate-800 text-slate-600 border-slate-700',
            day.isToday ? 'ring-2 ring-amber-400 ring-offset-2 ring-offset-slate-900' : ''
          ]"
          class="w-10 h-10 rounded-2xl border-2 flex items-center justify-center text-base font-black shadow-md transition-all"
        >
          <span v-if="day.isRead">✓</span>
          <span v-else>{{ day.dateNum }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Calendar } from '@lucide/vue';

defineProps({
  weekDays: { type: Array, required: true }
});
</script>
