<template>
  <div class="card-duo space-y-4">
    <h3 class="font-extrabold text-white text-lg flex items-center gap-3">
      <Calendar class="w-5 h-5 text-amber-400 stroke-[2.5]" />
      <span>{{ monthLabel }}</span>
    </h3>
    <div class="grid grid-cols-7 gap-1.5 text-center">
      <span
        v-for="label in weekdayLabels"
        :key="label"
        class="text-xs font-extrabold text-slate-500"
      >{{ label }}</span>

      <div v-for="n in leadingBlanks" :key="`blank-${n}`" />

      <div
        v-for="day in monthDays"
        :key="day.dateStr"
        class="flex items-center justify-center"
      >
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
  // Id del usuario cuyo historial se muestra: el propio, o el de un amigo. Solo
  // necesario si no se pasan preloadedHistory/preloadedHasReadToday.
  targetId: { type: String, default: null },
  // Si el padre ya pidio esta info como parte de otra respuesta, se pasa aqui para
  // no repetir la peticion.
  preloadedHistory: { type: Array, default: null },
  preloadedHasReadToday: { type: Boolean, default: null }
});

const hasReadToday = ref(props.preloadedHasReadToday);
const historyDates = ref(props.preloadedHistory || []);

const weekdayLabels = ['L', 'M', 'X', 'J', 'V', 'S', 'D'];

const today = new Date();
const monthLabel = computed(() => {
  const label = today.toLocaleDateString('es', { month: 'long', year: 'numeric' });
  return label.charAt(0).toUpperCase() + label.slice(1);
});

// Espacios vacios antes del dia 1 para alinear la grilla con el dia de la semana
// correcto (Lunes = 0).
const leadingBlanks = computed(() => {
  const firstOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
  return (firstOfMonth.getDay() + 6) % 7;
});

const monthDays = computed(() => {
  const year = today.getFullYear();
  const month = today.getMonth();
  const daysInMonth = new Date(year, month + 1, 0).getDate();
  const todayStr = toLocalDateString(today);

  return Array.from({ length: daysInMonth }, (_, i) => {
    const d = new Date(year, month, i + 1);
    const dateStr = toLocalDateString(d);
    const isToday = dateStr === todayStr;
    const isRead = historyDates.value.includes(dateStr) || (isToday && hasReadToday.value);

    return { dateNum: i + 1, dateStr, isToday, isRead };
  });
});

onMounted(async () => {
  // Ya viene precargado desde el padre, no hay nada que pedir.
  if (props.preloadedHistory !== null) return;

  try {
    const res = await ApiService.getFriendProfile(props.targetId);
    if (res.success) {
      hasReadToday.value = res.has_read_today;
      historyDates.value = res.history || [];
    }
  } catch (e) {
    console.warn('No se pudo cargar el historial mensual:', e.message);
  }
});
</script>
