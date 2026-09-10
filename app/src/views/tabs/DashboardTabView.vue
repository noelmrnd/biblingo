<template>
  <AppPage app-header>
    <!-- Loading Indicator Centrado -->
    <div v-if="initialLoading" class="flex items-center justify-center py-24">
      <AppSpinner size="lg" />
    </div>

    <template v-else>
      <StreakHero :user="user" />

      <!-- Botón de Lectura de Hoy: readonly una vez que ya se registró -->
      <ReadingButton
        v-if="hasReadToday !== null"
        :user="user"
        :has-read-today="hasReadToday"
        @reading-logged="onReadingLogged"
      />

      <MonthlyTracker ref="monthlyTrackerRef" />
    </template>
  </AppPage>
</template>

<script>
// Estado de sesión para controlar que initialLoading solo ocurra la primera vez que se abre la app
let isFirstAppLoad = true;
let lastLoadedUserId = null;
</script>

<script setup>
import { ref, onActivated } from 'vue';
import AppPage from '@/components/AppPage.vue';
import AppSpinner from '@/components/AppSpinner.vue';
import ReadingButton from '@/components/ReadingButton.vue';
import StreakHero from '@/components/StreakHero.vue';
import MonthlyTracker from '@/components/MonthlyTracker.vue';
import { NotificationService } from '@/services/notifications.js';
import { ToastService } from '@/services/toast.js';
import { useCurrentUser } from '@/composables/useCurrentUser.js';
import { getBadgeById } from '@/constants.js';
import { BadgeCelebrationService } from '@/services/badgeCelebration.js';

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
const monthlyTrackerRef = ref(null);
const { refreshProfile } = useCurrentUser();

const onReadingLogged = ({ res }) => {
  hasReadToday.value = true;
  monthlyTrackerRef.value?.markTodayRead();

  if (res.used_freeze && props.user.notification_prefs?.freeze_used !== false) {
    const used = res.freezes_used_this_time || 1;
    const remaining = res.streak_freezes > 0
      ? `Te ${res.streak_freezes === 1 ? 'queda' : 'quedan'} ${res.streak_freezes}.`
      : 'Ya no te quedan más.';
    ToastService.info(`Se ${used === 1 ? 'usó un protector' : `usaron ${used} protectores`} de racha 🧊. ${remaining}`);
  }

  // Puede haber mas de una si el usuario ya estaba por encima de varios umbrales
  // antes de que existiera este sistema (backfill): BadgeCelebrationService las
  // encola y las muestra una por una, no hace falta escalonarlas a mano aca.
  (res.new_badges || []).forEach((badgeId) => {
    const badge = getBadgeById(badgeId);
    if (!badge) return;
    BadgeCelebrationService.celebrate(badge);
  });

  refreshProfile({ force: true });
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

// onActivated (no onMounted): DashboardView queda en keep-alive (App.vue), asi
// que tras la primera vez esto es lo que dispara el refresh en segundo plano
// cada vez que se vuelve a este tab.
onActivated(() => {
  loadReadingStatus();
});
</script>
