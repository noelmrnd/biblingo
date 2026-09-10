<template>
  <div class="card-duo space-y-4">
    <div class="flex items-center justify-between">
      <SectionTitle :title="monthLabel" :icon="Calendar" icon-color-class="text-amber-400" />
      <div class="flex items-center gap-1 -mr-1">
        <IconButton
          :haptic="false"
          @click="monthOffset--"
          aria-label="Mes anterior"
        >
          <ChevronLeft class="w-5 h-5 stroke-[2.5]" />
        </IconButton>
        <IconButton
          :haptic="false"
          @click="monthOffset++"
          :disabled="monthOffset >= 0"
          aria-label="Mes siguiente"
        >
          <ChevronRight class="w-5 h-5 stroke-[2.5]" />
        </IconButton>
      </div>
    </div>
    <div class="grid grid-cols-7 gap-2 text-center">
      <span
        v-for="label in weekdayLabels"
        :key="label"
        class="text-sm font-bold text-slate-500"
      >{{ label }}</span>
    </div>

    <div class="grid grid-cols-7 gap-2">
      <div v-for="n in leadingBlanks" :key="`blank-${n}`" class="aspect-square" />

      <div
        v-for="day in monthDays"
        :key="day.dateStr"
        :class="[
          day.isRead ? 'bg-brand-green text-white border-emerald-600 shadow-emerald-500/30' : '',
          day.isFrozen ? 'bg-brand-freeze text-white border-brand-freeze-dark shadow-brand-freeze/30' : '',
          !day.isRead && !day.isFrozen ? 'bg-slate-800 text-slate-600 border-slate-700' : '',
          day.isToday ? 'ring-2 ring-amber-400 ring-offset-2 ring-offset-slate-900' : ''
        ]"
        class="aspect-square rounded-2xl border flex items-center justify-center text-base font-bold transition-all"
      >
        <Check v-if="day.isRead" class="w-5 h-5 stroke-[2.5]" />
        <ShieldCheck v-else-if="day.isFrozen" class="w-5 h-5 stroke-[2.5]" />
        <span v-else>{{ day.dateNum }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onActivated } from 'vue';
import { Calendar, ChevronLeft, ChevronRight, ShieldCheck, Check } from '@lucide/vue';
import { ApiService } from '@/services/api';
import IconButton from './IconButton.vue';
import SectionTitle from './SectionTitle.vue';
import { toLocalDateString } from '@/utils/dateFormatter';
import { monthCache } from '@/utils/monthlyCalendarCache';
import { useMidnightRefresh } from '@/composables/useMidnightRefresh';

// today es reactivo (via useMidnightRefresh): sin esto, si la app queda
// abierta con el calendario ya montado y pasa la medianoche, "hoy" se
// congela en el dia de ayer hasta que el componente se destruya/remonte.
const { today, checkNow: checkMidnight } = useMidnightRefresh();
// Si cambia el dia mientras se ve el mes actual, recargar (puede haber
// cruzado a un mes nuevo, o simplemente conviene refrescar datos del dia).
watch(today, () => {
  if (monthOffset.value === 0) loadMonth();
});
// 0 = mes actual, negativo = meses hacia atras. No se permite ir a futuro.
const monthOffset = ref(0);
const displayedMonth = computed(() => new Date(today.value.getFullYear(), today.value.getMonth() + monthOffset.value, 1));

const weekdayLabels = ['L', 'M', 'X', 'J', 'V', 'S', 'D'];
// Mapa dateStr -> frozen (true/false), para lookup O(1) en monthDays.
const daysByDate = ref(new Map());

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
  const todayStr = toLocalDateString(today.value);

  return Array.from({ length: daysInMonth }, (_, i) => {
    const d = new Date(year, month, i + 1);
    const dateStr = toLocalDateString(d);

    return {
      dateNum: i + 1,
      dateStr,
      isToday: dateStr === todayStr,
      isRead: daysByDate.value.has(dateStr) && !daysByDate.value.get(dateStr),
      isFrozen: daysByDate.value.get(dateStr) === true
    };
  });
});

const MONTH_CHANGE_DEBOUNCE_MS = 350;
let debounceTimer = null;
// Evita que una respuesta vieja (de un mes que ya se dejo atras a clicks rapidos)
// sobreescriba los datos del mes que se esta viendo ahora.
let requestSeq = 0;

// Meses pasados no cambian, cachean para siempre. El mes en curso si puede
// quedar desactualizado (se leyo desde otro dispositivo) — TTL mas alto que el
// de refreshProfile porque no es realista que alguien vuelva a esta app tan
// rapido despues de leer en otra.
const CURRENT_MONTH_TTL_MS = 5 * 60 * 1000;
const currentMonthKey = computed(() => `${today.value.getFullYear()}-${today.value.getMonth() + 1}`);

const loadMonth = async () => {
  const seq = ++requestSeq;
  const year = displayedMonth.value.getFullYear();
  const month = displayedMonth.value.getMonth() + 1;
  const cacheKey = `${year}-${month}`;

  const cached = monthCache.get(cacheKey);
  if (cached) {
    const isStale = cacheKey === currentMonthKey.value && (Date.now() - cached.cachedAt > CURRENT_MONTH_TTL_MS);
    if (!isStale) {
      daysByDate.value = cached.daysByDate;
      return;
    }
  }

  try {
    const res = await ApiService.getReadingCalendar(year, month);
    if (seq !== requestSeq) return;
    const days = res.success ? (res.days || []) : [];
    const map = new Map(days.map(d => [d.read_date, d.is_frozen_day]));
    daysByDate.value = map;
    monthCache.set(cacheKey, { daysByDate: map, cachedAt: Date.now() });
  } catch (e) {
    if (seq !== requestSeq) return;
    console.warn('No se pudo cargar el calendario mensual:', e.message);
  }
};

watch(monthOffset, () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(loadMonth, MONTH_CHANGE_DEBOUNCE_MS);
});
// onMounted + onActivated: MonthlyTracker nace detras de un v-if (initialLoading)
// dentro de DashboardView, que ya esta en keep-alive (App.vue) cuando ese v-if
// se activa por primera vez — Vue no dispara onActivated para un hijo que recien
// nace mientras su ancestro keep-alive ya estaba activo, solo en reactivaciones
// reales (volver de otro tab). Sin onMounted, la primera carga nunca pedia el calendario.
onMounted(loadMonth);
onActivated(() => {
  checkMidnight();
  loadMonth();
});

// Actualizacion optimista al registrar lectura hoy: evita un round-trip solo para
// marcar un check que ya sabemos que es cierto (si se esta viendo el mes actual).
const markTodayRead = () => {
  const todayStr = toLocalDateString(today.value);
  if (monthOffset.value === 0 && !daysByDate.value.has(todayStr)) {
    daysByDate.value = new Map(daysByDate.value).set(todayStr, false);
    monthCache.set(currentMonthKey.value, { daysByDate: daysByDate.value, cachedAt: Date.now() });
  }
};

defineExpose({ markTodayRead });
</script>
