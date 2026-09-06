<template>
  <!-- Loading Indicator Centrado -->
  <div v-if="initialLoading" class="flex flex-col items-center justify-center py-24 space-y-4 text-center">
    <div class="relative w-16 h-16 flex items-center justify-center">
      <div class="absolute inset-0 rounded-full border-4 border-slate-800 border-t-brand-green animate-spin"></div>
      <BookOpen class="w-6 h-6 text-brand-green stroke-[2.5]" />
    </div>
    <p class="text-slate-300 font-extrabold text-base tracking-wide">Cargando racha...</p>
  </div>

  <div v-else class="space-y-4">
    <!-- Header Racha Hero (Emoji de fuego permitido) -->
    <div class="relative card-duo bg-slate-900 bg-[radial-gradient(ellipse_at_top_right,_rgba(255,150,0,0.22),_transparent_65%)] border-amber-500/30 text-center py-8 px-6 overflow-hidden">
      <!-- Llama animada (o congelada si la racha ya se perdió) -->
      <div class="inline-block relative my-3">
        <div
          class="text-7xl inline-block filter drop-shadow-[0_0_20px_rgba(255,150,0,0.8)]"
          :class="user.is_streak_lost ? '' : 'animate-flame-pulse'"
        >
          {{ user.is_streak_lost ? '🥶' : '🔥' }}
        </div>
        <div class="absolute -bottom-2 right-0 bg-amber-400 text-slate-950 font-black text-base px-2.5 py-0.5 rounded-full shadow">
          x{{ user.is_streak_lost ? 0 : user.streak_count }}
        </div>
      </div>

      <div class="mt-2 space-y-1">
        <h2 class="text-4xl font-extrabold text-white">
          {{ user.is_streak_lost ? 0 : user.streak_count }} {{ (user.is_streak_lost ? 0 : user.streak_count) === 1 ? 'día' : 'días' }}
        </h2>
        <p class="text-amber-400 font-extrabold text-base uppercase tracking-wider">
          {{ user.is_streak_lost ? 'Racha perdida' : 'Racha de lectura activa' }}
        </p>
      </div>

      <!-- Subtítulo / Badge de Estado -->
      <div v-if="hasReadToday === true" class="inline-flex flex-wrap items-center justify-center gap-2 bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 px-4 py-1.5 rounded-full text-base font-extrabold mt-3 shadow-inner">
        <CheckCircle2 class="w-5 h-5 text-emerald-400 stroke-[2.5]" />
        <span>¡Perfecto, ya leíste hoy!</span>
      </div>
      <p v-else-if="user.is_streak_lost" class="text-slate-200 text-base mt-3">
        Se rompió tu racha.
      </p>

      <!-- Protectores de racha disponibles -->
      <div
        v-if="user.streak_freezes > 0"
        class="inline-flex items-center gap-2 bg-sky-500/10 text-sky-300 border border-sky-500/30 px-3 py-1 rounded-full text-sm font-bold mt-3"
      >
        <span class="text-base leading-none">🧊</span>
        <span>{{ user.streak_freezes }} protector{{ user.streak_freezes > 1 ? 'es' : '' }} de racha</span>
      </div>
    </div>

    <!-- Botón de Lectura de Hoy (Solo visible cuando se haya confirmado que falta por leer) -->
    <ReadingButton
      v-if="hasReadToday === false"
      :user="user"
      @reading-logged="onReadingLogged"
    />

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

      <div class="flex gap-3">
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

<script>
// Estado de sesión para controlar que initialLoading solo ocurra la primera vez que se abre la app
let isFirstAppLoad = true;
let lastLoadedUserId = null;
</script>

<script setup>
import { ref, onMounted } from 'vue';
import { BookOpen, CheckCircle2 } from '@lucide/vue';
import ReadingButton from '../components/ReadingButton.vue';
import { NotificationService } from '../services/notifications';
import { StorageService } from '../services/storage';
import { ToastService } from '../services/toast';
import { useCurrentUser } from '../composables/useCurrentUser';

const props = defineProps({
  user: { type: Object, required: true }
});

// Reiniciar flag si cambió de usuario
if (lastLoadedUserId !== props.user?.id) {
  isFirstAppLoad = true;
  lastLoadedUserId = props.user?.id;
}

const initialLoading = ref(isFirstAppLoad);
const hasReadToday = ref(props.user?.has_read_today ?? null);
const { refreshProfile, mergeUser } = useCurrentUser();

const onReadingLogged = ({ res }) => {
  hasReadToday.value = true;
  mergeUser({
    streak_count: res.streak_count,
    max_streak_count: res.max_streak_count,
    streak_freezes: res.streak_freezes,
    streak_freezes_used: res.streak_freezes_used,
    last_read_date: res.last_read_date,
    last_read_label: res.last_read_label,
    has_read_today: true,
    is_streak_lost: false
  });

  if (res.used_freeze) {
    const remaining = res.streak_freezes > 0
      ? `Te quedan ${res.streak_freezes}.`
      : 'Ya no te quedan más.';
    ToastService.info(`Se usó un protector de racha 🧊. ${remaining}`);
  }
};

// Usa el mismo singleton/TTL que Profile: si App o Profile ya pidieron el estado
// hace poco, esto no repite la peticion, solo lee el usuario ya actualizado.
const loadReadingStatus = async () => {
  try {
    const updated = await refreshProfile();
    if (updated) {
      hasReadToday.value = updated.has_read_today;
      if (updated.has_read_today) {
        NotificationService.clearLocalNotifications();
      }
    }
    return updated;
  } finally {
    isFirstAppLoad = false;
    initialLoading.value = false;
  }
};

onMounted(async () => {
  const updated = await loadReadingStatus();
  const savedTime = (await StorageService.get('reminder_time')) || props.user.reminder_time || '20:00';
  const streakCount = updated ? updated.streak_count : props.user.streak_count;
  NotificationService.schedule7DayBurst(savedTime, streakCount, hasReadToday.value);
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
