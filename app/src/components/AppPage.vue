<template>
  <div class="contents">
    <header class="flex-none z-30 bg-brand-dark/90 backdrop-blur-md border-b border-brand-border shadow-md">
      <div class="pt-safe-cond"></div>
      <div class="h-16 px-4 flex items-center gap-3">
      <template v-if="appHeader">
        <div class="flex items-center gap-3 flex-1">
          <div class="w-8 h-8">
            <img src="/assets/logo-256.png" alt="Biblingo Logo" class="w-full h-full object-contain" />
          </div>
          <span class="font-extrabold text-xl tracking-tight text-white">Biblingo</span>
        </div>

        <div class="flex items-center gap-2">
          <!-- Protectores de racha disponibles -->
          <div
            v-if="streakFreezes > 0"
            class="flex items-center gap-1.5 bg-slate-900 border border-sky-500/30 px-3 py-1 rounded-full shadow-inner"
          >
            <span class="text-base leading-none">🧊</span>
            <span class="font-bold text-sky-300 text-lg">{{ streakFreezes }}</span>
          </div>

          <!-- Racha activa en la barra superior -->
          <div class="flex items-center gap-2 bg-slate-900 border border-amber-500/30 px-4 py-1 rounded-full shadow-inner">
            <span class="text-lg animate-flame-pulse">🔥</span>
            <span class="font-bold text-amber-400 text-xl">{{ streakCount }}</span>
          </div>
        </div>
      </template>

      <template v-else>
        <button
          type="button"
          @click="router.push(backTo)"
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
      <div class="max-w-md mx-auto p-4 space-y-4">
        <slot />
      </div>
    </main>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { ArrowLeft } from '@lucide/vue';
import { keyboardHeight } from '../utils/keyboard';
import { useCurrentUser } from '../composables/useCurrentUser';

const props = defineProps({
  // true: header global (logo + racha). false: header propio con title/backTo.
  appHeader: { type: Boolean, default: false },
  title: { type: String, default: '' },
  backTo: { type: [String, Object], default: null }
});

const router = useRouter();
const { user } = useCurrentUser();

const streakCount = computed(() => user.value?.is_streak_lost ? 0 : (user.value?.streak_count || 0));
const streakFreezes = computed(() => user.value?.streak_freezes || 0);
</script>
