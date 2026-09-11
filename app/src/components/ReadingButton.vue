<template>
  <div class="w-full">
    <!-- Ya leyó hoy y no hay libro activo: nada mas que registrar por hoy -->
    <AppButton
      v-if="hasReadToday && !activeBook"
      color="green"
      size="lg"
      block
      disabled
      :icon="CheckCircle2"
      text="¡Perfecto, ya leíste hoy!"
    />

    <!-- Ya leyó hoy pero hay libro activo: puede seguir sumando avance el resto
         del dia, sin volver a tocar la racha ni la reaccion (ver BookController::updateProgress) -->
    <AppButton
      v-else-if="hasReadToday"
      color="green"
      size="lg"
      block
      :disabled="loadingExtraProgress || loadingActiveBook"
      :icon="BookOpen"
      text="Registrar más avance"
      @click="openExtraProgressModal"
    />

    <AppButton
      v-else
      color="green"
      size="lg"
      block
      :disabled="loading || loadingActiveBook"
      :icon="BookOpen"
      text="Registrar lectura de hoy"
      @click="openReactionModal"
    />

    <!-- Modal de Registro de Lectura: avance del libro activo (si hay) + reacción -->
    <ReadingFlowModal
      :is-open="showReactionModal"
      :loading="loading"
      :active-book="activeBook"
      @close="showReactionModal = false"
      @confirm="handleReactionConfirmed"
    />

    <!-- Modal de Avance Extra: solo el paso de progreso, sin reaccion -->
    <AppModal
      :is-open="showExtraProgressModal"
      :loading="loadingExtraProgress"
      title="Más avance de hoy"
      :description="activeBook?.title"
      @close="showExtraProgressModal = false"
    >
      <BookProgressStep v-if="activeBook" ref="extraProgressStepRef" :book="activeBook" :loading="loadingExtraProgress" />

      <template #footer>
        <AppButton
          color="green"
          size="lg"
          block
          :disabled="loadingExtraProgress || !isExtraProgressValid"
          :loading="loadingExtraProgress"
          loading-text="Guardando..."
          text="Guardar avance"
          :icon="BookOpen"
          @click="submitExtraProgress"
        />
      </template>
    </AppModal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onActivated } from 'vue';
import { BookOpen, CheckCircle2 } from '@lucide/vue';
import AppButton from './AppButton.vue';
import AppModal from './AppModal.vue';
import BookProgressStep from './BookProgressStep.vue';
import confetti from 'canvas-confetti';
import ReadingFlowModal from './ReadingFlowModal.vue';
import { ApiService } from '@/services/api';
import { NotificationService } from '@/services/notifications';
import { ToastService } from '@/services/toast';
import { StorageService } from '@/services/storage';
import { HapticsService } from '@/services/haptics';
import { useCurrentUser } from '@/composables/useCurrentUser';
import { getBadgeById } from '@/constants';
import { BadgeCelebrationService } from '@/services/badgeCelebration';

const { refreshProfile } = useCurrentUser();

const props = defineProps({
  user: { type: Object, required: true },
  hasReadToday: { type: Boolean, default: false }
});

const emit = defineEmits(['reading-logged']);

const loading = ref(false);
const showReactionModal = ref(false);
const activeBook = ref(null);
// Evita que un click justo despues de activarse la vista (antes de que resuelva
// getActiveBook) muestre el flujo sin el paso de progreso por creer que no hay
// libro activo — bloquea el boton hasta tener la respuesta, como initialLoading en DashboardTabView.
const loadingActiveBook = ref(true);

const loadActiveBook = async () => {
  loadingActiveBook.value = true;
  try {
    const res = await ApiService.getActiveBook();
    if (res.success) {
      activeBook.value = res.book;
    }
  } catch (e) {
    console.warn('No se pudo cargar el libro activo:', e.message);
  } finally {
    loadingActiveBook.value = false;
  }
};

// onMounted cubre la primera aparicion: este componente nace via v-if (recien
// cuando hasReadToday deja de ser null) DESPUES de que DashboardTabView ya esta
// activo dentro del keep-alive, asi que no hay transicion de activacion que
// dispare onActivated esa primera vez (se quedaba con loadingActiveBook en true
// para siempre — el boton nunca se habilitaba). onActivated cubre las vueltas
// siguientes al tab, para reflejar un cambio de libro hecho en Ajustes.
onMounted(loadActiveBook);
onActivated(loadActiveBook);

const openReactionModal = () => {
  if (loading.value || loadingActiveBook.value) return;
  showReactionModal.value = true;
};

// Avance extra: mismo libro, mismo dia, pero sin racha ni reaccion (ver
// BookController::updateProgress) — disponible solo despues de haber marcado
// la lectura de hoy, como reemplazo del boton principal (no coexisten).
const showExtraProgressModal = ref(false);
const loadingExtraProgress = ref(false);
const extraProgressStepRef = ref(null);
const isExtraProgressValid = computed(() => extraProgressStepRef.value?.isValid ?? false);

const openExtraProgressModal = () => {
  if (loadingExtraProgress.value || loadingActiveBook.value) return;
  showExtraProgressModal.value = true;
};

const submitExtraProgress = async () => {
  if (!extraProgressStepRef.value?.isValid || loadingExtraProgress.value) return;
  loadingExtraProgress.value = true;
  try {
    const payload = extraProgressStepRef.value.getPayload();
    const res = await ApiService.updateBookProgress(payload);
    if (res.success) {
      activeBook.value = res.book;
      showExtraProgressModal.value = false;
      ToastService.success('¡Avance guardado! 📖');
      // pages_read cambio en el servidor — refrescar el usuario compartido
      // para que Perfil (y cualquier otra vista) lo vea actualizado sin recargar.
      refreshProfile({ force: true });

      // Este flujo no pasa por handleReactionConfirmed (no hay reaccion), asi que
      // la celebracion de medallas ganadas por paginas/libro terminado va aca.
      (res.new_badges || []).forEach((badgeId) => {
        const badge = getBadgeById(badgeId);
        if (!badge) return;
        BadgeCelebrationService.celebrate(badge);
      });
    }
  } catch (e) {
    ToastService.error(e.message || 'No se pudo guardar tu avance.');
  } finally {
    loadingExtraProgress.value = false;
  }
};

const handleReactionConfirmed = async ({ reaction, progress }) => {
  if (loading.value) return;
  loading.value = true;
  try {
    const res = await ApiService.logReading(reaction, progress);
    if (res.success) {
      showReactionModal.value = false;

      // Refresca el libro activo: si se mando avance en este mismo log, current_unit
      // ya cambio en el servidor y el boton de "avance extra" necesita el valor nuevo.
      if (progress && activeBook.value) {
        try {
          const bookRes = await ApiService.getActiveBook();
          if (bookRes.success) activeBook.value = bookRes.book;
        } catch (e) {
          console.warn('No se pudo refrescar el libro activo:', e.message);
        }
      }

      // Caso 2: Limpiar las notificaciones locales entregadas y el badge solo tras haber leído
      await NotificationService.clearLocalNotifications();

      // Efecto Confeti 🎉 (mas grande y en varias rafagas si gano alguna medalla,
      // para que se sienta distinto a un dia cualquiera)
      if (res.new_badges?.length > 0) {
        HapticsService.heavy();
        const burst = (particleCount, angle, originX) => confetti({
          particleCount,
          spread: 100,
          angle,
          origin: { x: originX, y: 0.6 },
          colors: ['#4EC313', '#FF640A', '#1D6CED', '#FFD700']
        });
        burst(120, 60, 0.2);
        burst(120, 120, 0.8);
        setTimeout(() => burst(140, 90, 0.5), 200);
      } else {
        HapticsService.medium();
        confetti({
          particleCount: 100,
          spread: 70,
          origin: { y: 0.6 },
          colors: ['#4EC313', '#FF640A', '#1D6CED', '#FFD700']
        });
      }

      // Programar ráfaga de 7 días de notificaciones locales (pasando true porque ya leyó hoy),
      // solo si el usuario no apagó la categoria "Recordatorio de lectura".
      if (props.user.notification_prefs?.daily_reminder !== false) {
        const savedTime = (await StorageService.get('reminder_time')) || props.user.reminder_time || '20:00';
        NotificationService.schedule7DayBurst(savedTime, res.streak_count, true);
      }

      emit('reading-logged', {
        res,
        reaction: res.reaction || reaction
      });
    }
  } catch (e) {
    ToastService.error(e.message || 'Error al registrar la lectura.');
  } finally {
    loading.value = false;
  }
};
</script>
