<template>
  <div class="space-y-3">
    <SectionTitle title="Últimos 7 días" :icon="Calendar" icon-color-class="text-amber-400" />
    <AppCard>
      <div class="grid grid-cols-7 gap-1.5 text-center">
        <div
          v-for="(day, index) in weekDays"
          :key="index"
          class="flex flex-col items-center space-y-3"
        >
          <span class="text-sm font-bold text-slate-500">{{ day.label }}</span>
          <div
            :class="[
              day.isRead ? 'bg-brand-green text-white border-emerald-600' : '',
              day.isFrozen ? 'bg-brand-freeze text-white border-brand-freeze-dark' : '',
              !day.isRead && !day.isFrozen ? 'bg-slate-800 text-slate-600 border-slate-700' : '',
              day.isToday ? 'ring-2 ring-amber-400 ring-offset-2 ring-offset-slate-900' : ''
            ]"
            class="w-10 h-10 rounded-2xl border flex items-center justify-center text-base font-bold transition-all"
          >
            <Check v-if="day.isRead" class="w-5 h-5 stroke-[2.5]" />
            <ShieldCheck v-else-if="day.isFrozen" class="w-5 h-5 stroke-[2.5]" />
            <span v-else>{{ day.dateNum }}</span>
          </div>
        </div>
      </div>
    </AppCard>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Calendar, ShieldCheck, Check } from '@lucide/vue';
import { toLocalDateString } from '../utils/dateFormatter';
import SectionTitle from './SectionTitle.vue';
import AppCard from './AppCard.vue';
import { useMidnightRefresh } from '../composables/useMidnightRefresh';

// today reactivo: sin esto, "hoy" quedaria congelado en el dia de ayer si la
// vista sigue montada pasada la medianoche (ver useMidnightRefresh).
const { today } = useMidnightRefresh();

const props = defineProps({
  // El padre (ej. FriendProfileView) ya trae esta info como parte del perfil completo.
  history: { type: Array, required: true }
});

// Mapa dateStr -> is_frozen_day, para lookup O(1) en weekDays.
const historyByDate = ref(new Map(props.history.map(d => [d.read_date, d.is_frozen_day])));

// El padre (ej. FriendProfileView) puede reutilizar esta misma instancia al
// navegar entre perfiles (misma ruta, distinto id) — sin esto, el tracker se
// quedaria pegado con los datos del primer amigo visto.
watch(() => props.history, (history) => {
  historyByDate.value = new Map(history.map(d => [d.read_date, d.is_frozen_day]));
});

const weekDays = computed(() => {
  const labels = ['D', 'L', 'M', 'X', 'J', 'V', 'S']; // indexado por Date#getDay() (0 = Domingo)
  const todayStr = toLocalDateString(today.value);

  // Ultimos 7 dias terminando hoy, no la semana calendario: evita celdas de
  // dias futuros vacias cuando hoy es lunes/martes.
  return Array.from({ length: 7 }, (_, i) => {
    const d = new Date(today.value);
    d.setDate(today.value.getDate() - (6 - i));
    const dateStr = toLocalDateString(d);
    const isToday = (dateStr === todayStr);
    const isFrozen = historyByDate.value.get(dateStr) === true;
    const isRead = historyByDate.value.has(dateStr) && !isFrozen;

    return { label: labels[d.getDay()], dateNum: d.getDate(), dateStr, isToday, isRead, isFrozen };
  });
});
</script>
