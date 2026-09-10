<template>
  <Teleport to="body">
    <Transition name="celebration-fade" @after-enter="onEntered">
      <div
        v-if="badgeCelebrationState.visible && badge"
        class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-950/90 backdrop-blur-sm p-6"
        @click.self="dismiss"
      >
        <div class="w-full max-w-sm flex flex-col items-center text-center">
          <!-- Halo pulsante detras de la medalla -->
          <div class="relative flex items-center justify-center mb-6">
            <div class="absolute w-40 h-40 rounded-full bg-amber-400/25 blur-2xl celebration-halo"></div>
            <div
              class="relative w-32 h-32 rounded-full border-4 border-amber-400/60 bg-amber-500/10 flex items-center justify-center text-6xl leading-none celebration-badge-pop shadow-[0_0_60px_rgba(251,191,36,0.35)]"
            >
              {{ badge.emoji }}
            </div>
          </div>

          <p class="text-amber-400 text-sm font-black tracking-widest uppercase celebration-text-in" style="animation-delay: 0.15s">
            ¡Nuevo logro!
          </p>
          <h3 class="text-white text-2xl font-black mt-1 celebration-text-in" style="animation-delay: 0.22s">
            {{ badge.label }}
          </h3>
          <p class="text-slate-300 text-base font-medium mt-2 celebration-text-in" style="animation-delay: 0.28s">
            {{ badge.description }}
          </p>

          <AppButton
            color="green"
            size="lg"
            block
            class="mt-8 celebration-text-in"
            style="animation-delay: 0.34s"
            text="Continuar"
            @click="dismiss"
          />
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue';
import AppButton from './AppButton.vue';
import { badgeCelebrationState, BadgeCelebrationService } from '@/services/badgeCelebration';
import { HapticsService } from '@/services/haptics';

const badge = computed(() => badgeCelebrationState.value.badge);

const dismiss = () => {
  HapticsService.light();
  BadgeCelebrationService.dismiss();
};

const onEntered = () => {
  HapticsService.heavy();
};
</script>

<style scoped>
.celebration-fade-enter-active,
.celebration-fade-leave-active {
  transition: opacity 0.25s ease;
}
.celebration-fade-enter-from,
.celebration-fade-leave-to {
  opacity: 0;
}

.celebration-badge-pop {
  animation: badge-pop 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) both;
}

@keyframes badge-pop {
  0% {
    opacity: 0;
    transform: scale(0.3) rotate(-15deg);
  }
  60% {
    opacity: 1;
    transform: scale(1.12) rotate(4deg);
  }
  100% {
    opacity: 1;
    transform: scale(1) rotate(0deg);
  }
}

.celebration-halo {
  animation: halo-pulse 1.8s ease-in-out infinite;
}

@keyframes halo-pulse {
  0%, 100% {
    opacity: 0.5;
    transform: scale(1);
  }
  50% {
    opacity: 0.9;
    transform: scale(1.15);
  }
}

.celebration-text-in {
  opacity: 0;
  animation: text-in 0.4s ease-out both;
}

@keyframes text-in {
  0% {
    opacity: 0;
    transform: translateY(10px);
  }
  100% {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
