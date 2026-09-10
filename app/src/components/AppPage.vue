<template>
  <div class="contents">
    <header class="flex-none z-30 bg-brand-dark/90 backdrop-blur-md border-b border-brand-border shadow-md">
      <div class="pt-safe-cond"></div>
      <div class="h-16 px-4 flex items-center gap-3">
      <template v-if="appHeader">
        <div class="flex items-center gap-3 flex-1">
          <div class="w-8 h-8">
            <img src="/assets/logo-256.png" alt="Libringo Logo" class="w-full h-full object-contain" />
          </div>
          <span class="font-extrabold text-xl tracking-tight text-white">Libringo</span>
        </div>

        <button
          type="button"
          @click="() => { HapticsService.light(); isRulesModalOpen = true; }"
          class="flex items-center gap-2 cursor-pointer"
          aria-label="Cómo funciona la racha"
        >
          <!-- Protectores de racha disponibles -->
          <div class="flex items-center gap-1.5 bg-slate-900 border border-brand-freeze/30 px-3 py-1 rounded-full shadow-inner">
            <ShieldCheck class="w-5 h-5 text-brand-freeze-light stroke-[2.5]" />
            <span class="font-semibold text-brand-freeze-light text-lg">{{ streakFreezes }}</span>
          </div>

          <!-- Racha activa en la barra superior -->
          <div class="flex items-center gap-1.5 bg-slate-900 border border-amber-500/30 px-3 py-1 rounded-full shadow-inner">
            <Flame class="w-5 h-5 text-brand-flame stroke-[2.5]" />
            <span class="font-semibold text-brand-flame text-lg">{{ streakCount }}</span>
          </div>
        </button>
      </template>

      <template v-else>
        <button
          v-if="backRoute"
          type="button"
          @click="goBack"
          class="w-8 h-8 -ml-1 flex items-center justify-center text-slate-300 hover:text-white rounded-full transition-colors cursor-pointer"
          aria-label="Volver"
        >
          <ArrowLeft class="w-6 h-6 stroke-[2.5]" />
        </button>
        <span class="font-extrabold text-xl tracking-tight text-white truncate">{{ title }}</span>
      </template>
      </div>
    </header>

    <main
      class="flex-1 overflow-y-auto w-full no-scrollbar transition-[padding] duration-200"
      :style="keyboardHeight > 0 ? { paddingBottom: `${keyboardHeight}px` } : undefined"
    >
      <div class="p-5 space-y-5">
        <slot />
      </div>
    </main>

    <AppRulesModal
      :is-open="isRulesModalOpen"
      :streak-count="streakCount"
      :streak-freezes="streakFreezes"
      @close="isRulesModalOpen = false"
    />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { ArrowLeft, ShieldCheck, Flame } from '@lucide/vue';
import { keyboardHeight } from '@/utils/keyboard';
import { useCurrentUser } from '@/composables/useCurrentUser';
import AppRulesModal from './AppRulesModal.vue';
import { HapticsService } from '@/services/haptics';

const props = defineProps({
  // true: header global (logo + racha). false: header propio con title/backRoute.
  appHeader: { type: Boolean, default: false },
  title: { type: String, default: '' },
  // null: sin boton de volver. object: ruta fija (tambien fallback si backWhenAvailable
  // no encuentra historial al que volver, ej. entrada directa por deep link).
  backRoute: { type: [String, Object], default: null },
  // true: si hay historial real dentro de la app, usar router.back() en vez de backRoute.
  backWhenAvailable: { type: Boolean, default: false }
});

const router = useRouter();
const { user } = useCurrentUser();
const isRulesModalOpen = ref(false);

const goBack = () => {
  if (props.backWhenAvailable && window.history.state?.back) {
    router.back();
  } else {
    router.push(props.backRoute);
  }
};

const streakCount = computed(() => user.value?.is_streak_lost ? 0 : (user.value?.streak_count || 0));
const streakFreezes = computed(() => user.value?.streak_freezes || 0);
</script>
