<template>
  <AppPage>
    <template #header>
      <AppHeader />
    </template>

    <!-- Loading Indicator Centrado -->
    <div v-if="initialLoading" class="flex flex-col items-center justify-center py-24 space-y-4 text-center">
      <div class="relative w-16 h-16 flex items-center justify-center">
        <div class="absolute inset-0 rounded-full border-4 border-slate-800 border-t-brand-green animate-spin"></div>
        <BookOpen class="w-6 h-6 text-brand-green stroke-[2.5]" />
      </div>
      <p class="text-slate-300 font-extrabold text-base tracking-wide">Cargando racha...</p>
    </div>

    <div v-else class="space-y-4">
      <StreakHero :user="user" :has-read-today="hasReadToday" />

      <!-- Botón de Lectura de Hoy (Solo visible cuando se haya confirmado que falta por leer) -->
      <ReadingButton
        v-if="hasReadToday === false"
        :user="user"
        @reading-logged="onReadingLogged"
      />

      <ReadingTimer />

      <MonthlyTracker />
    </div>
  </AppPage>
</template>

<script>
// Estado de sesión para controlar que initialLoading solo ocurra la primera vez que se abre la app
let isFirstAppLoad = true;
let lastLoadedUserId = null;
</script>

<script setup>
import { ref, onMounted } from 'vue';
import { BookOpen } from '@lucide/vue';
import AppPage from '../components/AppPage.vue';
import AppHeader from '../components/AppHeader.vue';
import ReadingButton from '../components/ReadingButton.vue';
import ReadingTimer from '../components/ReadingTimer.vue';
import StreakHero from '../components/StreakHero.vue';
import MonthlyTracker from '../components/MonthlyTracker.vue';
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
</script>
