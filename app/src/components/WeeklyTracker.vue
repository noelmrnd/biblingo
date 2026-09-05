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
import { ref, computed, onMounted } from 'vue';
import { Calendar } from '@lucide/vue';
import { ApiService } from '../services/api';
import { toLocalDateString } from '../utils/dateFormatter';

const props = defineProps({
  userId: { type: String, required: true },
  // Si se pasa, carga el historial de ese amigo (validado por friendship en el backend)
  // en vez del historial propio.
  friendId: { type: String, default: null }
});

const hasReadToday = ref(null);
const historyDates = ref([]);

const weekDays = computed(() => {
  const labels = ['L', 'M', 'X', 'J', 'V', 'S', 'D'];
  const today = new Date();
  const currentDayOfWeek = (today.getDay() + 6) % 7; // 0 = Lunes, 6 = Domingo

  const monday = new Date(today);
  monday.setDate(today.getDate() - currentDayOfWeek);

  return labels.map((label, i) => {
    const d = new Date(monday);
    d.setDate(monday.getDate() + i);
    const dateStr = toLocalDateString(d);
    const isToday = (i === currentDayOfWeek);
    const isRead = historyDates.value.includes(dateStr) || (isToday && hasReadToday.value);

    return { label, dateNum: d.getDate(), dateStr, isToday, isRead };
  });
});

onMounted(async () => {
  try {
    const res = props.friendId
      ? await ApiService.getFriendHistory(props.userId, props.friendId)
      : await ApiService.getReadingStatus(props.userId);
    if (res.success) {
      hasReadToday.value = res.has_read_today;
      historyDates.value = res.history || [];
    }
  } catch (e) {
    console.warn('No se pudo cargar el historial semanal:', e.message);
  }
});
</script>
