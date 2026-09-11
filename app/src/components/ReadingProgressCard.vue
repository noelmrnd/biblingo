<template>
  <div v-if="currentBookTitle" class="space-y-3">
    <SectionTitle title="Leyendo ahora" :icon="BookOpen" icon-color-class="text-brand-days" />

    <AppCard>
      <div class="flex items-center justify-between gap-3 min-w-0">
        <p class="text-base font-bold text-white truncate">{{ currentBookTitle }}</p>
        <span v-if="hasProgress" class="text-base font-semibold text-white shrink-0">{{ progressPercent }}%</span>
      </div>

      <div v-if="hasProgress" class="pb-1">
        <div class="h-2 rounded-full bg-slate-700 overflow-hidden">
          <div class="h-full rounded-full bg-brand-days transition-all" :style="{ width: `${progressPercent}%` }"/>
        </div>
      </div>
    </AppCard>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { BookOpen } from '@lucide/vue';
import SectionTitle from './SectionTitle.vue';
import AppCard from './AppCard.vue';

const props = defineProps({
  currentBookTitle: { type: String, default: null },
  currentUnit: { type: Number, default: null },
  totalUnits: { type: Number, default: null }
});

const hasProgress = computed(() => !!props.totalUnits);
const progressPercent = computed(() => {
  if (!hasProgress.value) return 0;
  return Math.min(100, Math.max(0, Math.round((props.currentUnit / props.totalUnits) * 100)));
});
</script>
