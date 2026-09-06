<template>
  <ExpandableCard
    :collapsible="false"
    title="Logros"
    icon-bg-class="bg-amber-500/10 border-amber-500/30"
  >
    <template #icon>
      <Medal class="w-5 h-5 text-amber-400 stroke-[2.5]" />
    </template>

    <div class="grid grid-cols-4 gap-4">
      <button
        v-for="badge in badges"
        :key="badge.id"
        type="button"
        @click="selected = badge"
        class="flex flex-col items-center gap-1.5 cursor-pointer"
      >
        <div
          :class="badge.earnedAt ? 'bg-amber-500/10 border-amber-500/30' : 'bg-slate-950/70 border-slate-800 opacity-30 grayscale'"
          class="w-16 h-16 rounded-full border flex items-center justify-center text-3xl leading-none"
        >
          {{ badge.emoji }}
        </div>
        <span class="text-slate-300 text-xs font-semibold text-center leading-tight line-clamp-2">{{ badge.label }}</span>
      </button>
    </div>

    <AppModal
      :is-open="!!selected"
      :title="selected?.label"
      :description="selected?.description"
      @close="selected = null"
    >
      <template v-if="selected" #icon>
        <div
          :class="selected.earnedAt ? 'bg-amber-500/10 border-amber-500/30' : 'bg-slate-950/70 border-slate-800 opacity-30 grayscale'"
          class="w-14 h-14 rounded-2xl border flex items-center justify-center text-3xl leading-none shrink-0"
        >
          {{ selected.emoji }}
        </div>
      </template>
      <p v-if="selected" class="text-slate-400 text-sm font-medium">
        {{ selected.earnedAt ? `Ganado el ${selected.earnedAt.slice(0, 10)}` : 'Todavía no lo ganas' }}
      </p>
    </AppModal>
  </ExpandableCard>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Medal } from '@lucide/vue';
import ExpandableCard from './ExpandableCard.vue';
import AppModal from './AppModal.vue';
import { BADGES } from '../constants';

const props = defineProps({
  // Array {badge_id, earned_at} tal cual devuelve el backend.
  earnedBadges: { type: Array, default: () => [] }
});

const selected = ref(null);

const badges = computed(() => {
  const earnedMap = new Map(props.earnedBadges.map((b) => [b.badge_id, b.earned_at]));
  return BADGES.map((badge) => ({
    ...badge,
    earnedAt: earnedMap.get(badge.id) || null
  }));
});
</script>
