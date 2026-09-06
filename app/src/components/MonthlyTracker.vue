<template>
  <div class="card-duo space-y-4">
    <div class="flex items-center justify-between">
      <h3 class="font-extrabold text-white text-lg flex items-center gap-3">
        <Calendar class="w-5 h-5 text-amber-400 stroke-[2.5]" />
        <span>{{ monthLabel }}</span>
      </h3>
      <div class="flex items-center gap-1">
        <button
          type="button"
          @click="monthOffset--"
          class="p-1.5 text-slate-400 hover:text-white rounded-full transition-colors cursor-pointer"
          aria-label="Mes anterior"
        >
          <ChevronLeft class="w-5 h-5 stroke-[2.5]" />
        </button>
        <button
          type="button"
          @click="monthOffset++"
          :disabled="monthOffset >= 0"
          class="p-1.5 text-slate-400 hover:text-white rounded-full transition-colors cursor-pointer disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:text-slate-400"
          aria-label="Mes siguiente"
        >
          <ChevronRight class="w-5 h-5 stroke-[2.5]" />
        </button>
      </div>
    </div>
    <div class="grid grid-cols-7 gap-1.5 text-center">
      <span
        v-for="label in weekdayLabels"
        :key="label"
        class="text-xs font-extrabold text-slate-500"
      >{{ label }}</span>
    </div>

    <div class="grid grid-cols-7 gap-1.5 justify-items-center">
      <div v-for="n in leadingBlanks" :key="`blank-${n}`" class="w-10 h-10" />

      <div
        v-for="day in monthDays"
        :key="day.dateStr"
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
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Calendar, ChevronLeft, ChevronRight } from '@lucide/vue';
import { ApiService } from '../services/api';
import { toLocalDateString } from '../utils/dateFormatter';

const today = new Date();
// 0 = mes actual, negativo = meses hacia atras. No se permite ir a futuro.
const monthOffset = ref(0);
const displayedMonth = computed(() => new Date(today.getFullYear(), today.getMonth() + monthOffset.value, 1));

const weekdayLabels = ['L', 'M', 'X', 'J', 'V', 'S', 'D'];
const readDates = ref([]);

const monthLabel = computed(() => {
  const label = displayedMonth.value.toLocaleDateString('es', { month: 'long', year: 'numeric' });
  return label.charAt(0).toUpperCase() + label.slice(1);
});

// Espacios vacios antes del dia 1 para alinear la grilla con el dia de la semana
// correcto (Lunes = 0).
const leadingBlanks = computed(() => {
  return (displayedMonth.value.getDay() + 6) % 7;
});

const monthDays = computed(() => {
  const year = displayedMonth.value.getFullYear();
  const month = displayedMonth.value.getMonth();
  const daysInMonth = new Date(year, month + 1, 0).getDate();
  const todayStr = toLocalDateString(today);

  return Array.from({ length: daysInMonth }, (_, i) => {
    const d = new Date(year, month, i + 1);
    const dateStr = toLocalDateString(d);

    return { dateNum: i + 1, dateStr, isToday: dateStr === todayStr, isRead: readDates.value.includes(dateStr) };
  });
});

const MONTH_CHANGE_DEBOUNCE_MS = 350;
let debounceTimer = null;
// Evita que una respuesta vieja (de un mes que ya se dejo atras a clicks rapidos)
// sobreescriba los datos del mes que se esta viendo ahora.
let requestSeq = 0;

const loadMonth = async () => {
  const seq = ++requestSeq;
  const year = displayedMonth.value.getFullYear();
  const month = displayedMonth.value.getMonth() + 1;
  try {
    const res = await ApiService.getReadingCalendar(year, month);
    if (seq !== requestSeq) return;
    readDates.value = res.success ? (res.dates || []) : [];
  } catch (e) {
    if (seq !== requestSeq) return;
    console.warn('No se pudo cargar el calendario mensual:', e.message);
  }
};

watch(monthOffset, () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(loadMonth, MONTH_CHANGE_DEBOUNCE_MS);
});
onMounted(loadMonth);

// Actualizacion optimista al registrar lectura hoy: evita un round-trip solo para
// marcar un check que ya sabemos que es cierto (si se esta viendo el mes actual).
const markTodayRead = () => {
  const todayStr = toLocalDateString(today);
  if (monthOffset.value === 0 && !readDates.value.includes(todayStr)) {
    readDates.value = [...readDates.value, todayStr];
  }
};

defineExpose({ markTodayRead });
</script>
