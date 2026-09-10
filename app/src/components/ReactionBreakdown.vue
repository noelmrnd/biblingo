<template>
  <div class="space-y-3">
    <SectionTitle title="Reacciones" :icon="Heart" icon-color-class="text-rose-500" _icon-color-class="text-brand-reaction" />

    <AppCard>
      <div v-if="breakdown.length > 0" class="flex flex-wrap gap-2">
        <div
          v-for="r in breakdown"
          :key="r.id"
          class="flex items-center gap-1.5 bg-slate-950/70 border border-slate-800 rounded-xl px-3 py-2"
        >
          <span class="text-lg leading-none">{{ r.emoji }}</span>
          <span class="text-slate-200 text-sm font-bold">{{ r.count }}</span>
          <span class="text-slate-400 text-sm font-medium">{{ r.label }}</span>
        </div>
      </div>
      <p v-else class="text-slate-400 text-base font-medium">{{ emptyLabel }}</p>
    </AppCard>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { Heart } from '@lucide/vue';
import SectionTitle from './SectionTitle.vue';
import AppCard from './AppCard.vue';
import { READING_REACTIONS } from '../constants';

const props = defineProps({
  reactionCounts: { type: Object, default: () => ({}) },
  emptyLabel: { type: String, default: 'Aún no ha registrado reacciones.' }
});

const breakdown = computed(() => {
  const counts = props.reactionCounts || {};
  return READING_REACTIONS
    .map((r) => ({ ...r, count: counts[r.id] || 0 }))
    .filter((r) => r.count > 0)
    .sort((a, b) => b.count - a.count);
});
</script>
