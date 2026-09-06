<template>
  <ExpandableCard
    :collapsible="false"
    :title="title"
    icon-bg-class="bg-rose-500/10 border-rose-500/30"
  >
    <template #icon>
      <Heart class="w-5 h-5 text-rose-400 stroke-[2.5]" />
    </template>

    <div v-if="breakdown.length > 0" class="flex flex-wrap gap-2">
      <div
        v-for="r in breakdown"
        :key="r.id"
        class="flex items-center gap-1.5 bg-slate-950/70 border border-slate-800 rounded-xl px-3 py-1.5"
      >
        <span class="text-lg leading-none">{{ r.emoji }}</span>
        <span class="text-slate-200 text-sm font-bold">{{ r.count }}</span>
        <span class="text-slate-400 text-sm font-medium">{{ r.label }}</span>
      </div>
    </div>
    <p v-else class="text-slate-400 text-base font-medium">{{ emptyLabel }}</p>
  </ExpandableCard>
</template>

<script setup>
import { computed } from 'vue';
import { Heart } from '@lucide/vue';
import ExpandableCard from './ExpandableCard.vue';
import { READING_REACTIONS } from '../constants';

const props = defineProps({
  reactionCounts: { type: Object, default: () => ({}) },
  title: { type: String, default: 'Reacciones' },
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
