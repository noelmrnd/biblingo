<template>
  <div class="w-full">
    <AppButton
      color="green"
      size="lg"
      block
      :disabled="loading"
      @click="openReactionModal"
    >
      <BookOpen class="w-6 h-6 stroke-[2.5]" />
      <span>Marcar lectura de hoy</span>
    </AppButton>

    <!-- Modal de Reacción al Marcar Lectura -->
    <ReactionModal
      :is-open="showReactionModal"
      :loading="loading"
      @close="showReactionModal = false"
      @confirm="handleReactionConfirmed"
    />
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { BookOpen } from '@lucide/vue';
import AppButton from './AppButton.vue';
import confetti from 'canvas-confetti';
import ReactionModal from './ReactionModal.vue';
import { ApiService } from '../services/api';
import { NotificationService } from '../services/notifications';
import { ToastService } from '../services/toast';
import { StorageService } from '../services/storage';

const props = defineProps({
  user: { type: Object, required: true }
});

const emit = defineEmits(['reading-logged']);

const loading = ref(false);
const showReactionModal = ref(false);

const openReactionModal = () => {
  if (loading.value) return;
  showReactionModal.value = true;
};

const handleReactionConfirmed = async (reaction) => {
  if (loading.value) return;
  loading.value = true;
  try {
    const res = await ApiService.logReading(props.user.id, reaction);
    if (res.success) {
      showReactionModal.value = false;

      // Efecto Confeti 🎉
      confetti({
        particleCount: 100,
        spread: 70,
        origin: { y: 0.6 },
        colors: ['#58CC02', '#FF9600', '#1CB0F6', '#FFD700']
      });

      // Programar ráfaga de 7 días de notificaciones locales (pasando true porque ya leyó hoy)
      const savedTime = (await StorageService.get('reminder_time')) || props.user.reminder_time || '20:00';
      NotificationService.schedule7DayBurst(savedTime, res.streak_count, true);

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
