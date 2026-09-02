<template>
  <!-- Loading Indicator Centrado -->
  <div v-if="initialLoading" class="flex flex-col items-center justify-center py-24 space-y-4 text-center">
    <div class="relative w-16 h-16 flex items-center justify-center">
      <div class="absolute inset-0 rounded-full border-4 border-slate-800 border-t-brand-green animate-spin"></div>
      <span class="text-2xl">🦉</span>
    </div>
    <p class="text-slate-300 font-extrabold text-base tracking-wide">Cargando racha...</p>
  </div>

  <div v-else class="space-y-4">
    <!-- Header Racha Hero -->
    <div class="relative card-duo bg-slate-900 bg-[radial-gradient(ellipse_at_top_right,_rgba(255,150,0,0.22),_transparent_65%)] border-amber-500/30 text-center py-8 px-6 overflow-hidden">
      <!-- Llama animada -->
      <div class="inline-block relative my-3">
        <div class="text-7xl animate-flame-pulse inline-block filter drop-shadow-[0_0_20px_rgba(255,150,0,0.8)]">
          🔥
        </div>
        <div class="absolute -bottom-2 right-0 bg-amber-400 text-slate-950 font-black text-base px-2.5 py-0.5 rounded-full shadow">
          x{{ user.streak_count }}
        </div>
      </div>

      <div class="mt-2 space-y-1">
        <h2 class="text-4xl font-extrabold text-white tracking-tight">
          {{ user.streak_count }} {{ user.streak_count === 1 ? 'día' : 'días' }}
        </h2>
        <p class="text-amber-400 font-extrabold text-base uppercase tracking-wider">
          Racha de lectura activa
        </p>
      </div>

      <!-- Subtítulo / Badge de Estado -->
      <div v-if="hasReadToday" class="inline-flex items-center gap-1.5 bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 px-4 py-1.5 rounded-full text-base font-extrabold mt-3 shadow-inner">
        <span>✅</span> Lectura de hoy asegurada
      </div>
      <p v-else class="text-slate-200 text-base mt-3">
        Racha máxima histórica: <span class="text-amber-400 font-bold">{{ user.max_streak_count }} días</span>
      </p>
    </div>

    <!-- Botón de Lectura de Hoy (Solo visible cuando falta leer) -->
    <div v-if="!hasReadToday">
      <button
        @click="logReadingToday"
        :disabled="loading"
        class="btn-3d-green w-full text-lg py-5 font-black rounded-2xl flex items-center justify-center gap-3 transition-transform"
      >
        <span class="text-2xl">📖</span>
        <span>Marcar lectura de hoy</span>
      </button>
    </div>

    <!-- Tracker semanal de 7 días (Lun - Dom) -->
    <div class="card-duo space-y-4">
      <h3 class="font-extrabold text-white text-lg flex items-center gap-2">
        <span>📅</span> Esta semana
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

    <!-- Temporizador de lectura de 10 minutos -->
    <!--
    <div class="card-duo bg-slate-900/90 border-slate-700 space-y-4 text-center">
      <div class="flex items-center justify-between text-left">
        <div>
          <h4 class="font-extrabold text-white text-sm">Cronómetro de Lectura</h4>
          <p class="text-slate-400 text-xs">Meta recomendada: 10 minutos</p>
        </div>
        <span class="text-2xl">⏱️</span>
      </div>

      <div class="text-4xl font-black font-mono tracking-wider text-amber-400 py-2">
        {{ formatTimer(timerSeconds) }}
      </div>

      <div class="flex gap-2">
        <button 
          @click="toggleTimer" 
          :class="isTimerRunning ? 'btn-3d-orange' : 'btn-3d-blue'"
          class="flex-1 py-2.5 text-sm"
        >
          {{ isTimerRunning ? 'Pausar' : 'Iniciar 10 Min' }}
        </button>
        <button 
          @click="resetTimer" 
          class="btn-3d-dark text-xs py-2.5 px-4"
        >
          Reiniciar
        </button>
      </div>
    </div>
    -->

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import confetti from 'canvas-confetti';
import { ApiService } from '../services/api';
import { NotificationService } from '../services/notifications';
import { ToastService } from '../services/toast';

const props = defineProps({
  user: { type: Object, required: true }
});

const emit = defineEmits(['user-updated']);

const loading = ref(false);
const initialLoading = ref(true);
const hasReadToday = ref(false);
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
    const dateStr = d.toISOString().split('T')[0];
    const isToday = (i === currentDayOfWeek);
    const isRead = historyDates.value.includes(dateStr) || (isToday && hasReadToday.value);

    return {
      label,
      dateNum: d.getDate(),
      dateStr,
      isToday,
      isRead
    };
  });
});

const loadReadingStatus = async () => {
  try {
    const res = await ApiService.getReadingStatus(props.user.id);
    if (res.success) {
      hasReadToday.value = res.has_read_today;
      historyDates.value = res.history || [];
      emit('user-updated', {
        ...props.user,
        streak_count: res.streak_count,
        max_streak_count: res.max_streak_count,
        last_read_date: res.last_read_date
      });
    }
  } catch (e) {
    console.warn('No se pudo actualizar estado:', e.message);
  } finally {
    initialLoading.value = false;
  }
};

const logReadingToday = async () => {
  if (hasReadToday.value || loading.value) return;
  loading.value = true;
  try {
    const res = await ApiService.logReading(props.user.id);
    if (res.success) {
      hasReadToday.value = true;
      emit('user-updated', {
        ...props.user,
        streak_count: res.streak_count,
        max_streak_count: res.max_streak_count,
        last_read_date: res.last_read_date
      });

      // Efecto Confeti 🎉
      confetti({
        particleCount: 100,
        spread: 70,
        origin: { y: 0.6 },
        colors: ['#58CC02', '#FF9600', '#1CB0F6', '#FFD700']
      });

      // Programar ráfaga de 7 días de notificaciones locales
      NotificationService.schedule7DayBurst('20:00', res.streak_count);
      ToastService.success('¡Lectura de hoy registrada! 🔥📖');
    }
  } catch (e) {
    ToastService.error(e.message || 'Error al registrar la lectura.');
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  loadReadingStatus();
  const savedTime = localStorage.getItem('biblingo_reminder_time') || props.user.reminder_time || '20:00';
  NotificationService.schedule7DayBurst(savedTime, props.user.streak_count);
});



// const timerSeconds = ref(600); // 10 mins
// const isTimerRunning = ref(false);
// let timerInterval = null;

// const formatTimer = (sec) => {
//   const m = Math.floor(sec / 60);
//   const s = sec % 60;
//   return `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
// };

// const toggleTimer = () => {
//   if (isTimerRunning.value) {
//     clearInterval(timerInterval);
//     isTimerRunning.value = false;
//   } else {
//     isTimerRunning.value = true;
//     timerInterval = setInterval(() => {
//       if (timerSeconds.value > 0) {
//         timerSeconds.value--;
//       } else {
//         clearInterval(timerInterval);
//         isTimerRunning.value = false;
//         alert('¡Felicidades! Completaste tus 10 minutos de lectura diaria. 🔥');
//         logReadingToday();
//       }
//     }, 1000);
//   }
// };

// const resetTimer = () => {
//   clearInterval(timerInterval);
//   isTimerRunning.value = false;
//   timerSeconds.value = 600;
// };

// onUnmounted(() => {
//   if (timerInterval) clearInterval(timerInterval);
// });
</script>
