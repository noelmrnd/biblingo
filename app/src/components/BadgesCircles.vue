<template>
  <div class="space-y-3">
    <SectionTitle title="Logros" :icon="Medal" icon-color-class="text-amber-400" />

    <AppCard>
      <div class="grid grid-cols-4 gap-4">
        <button
          v-for="badge in badges"
          :key="badge.id"
          type="button"
          @click="selectBadge(badge)"
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
          {{ selected.earnedAt ? `Obtenido el ${formatDateDMY(selected.earnedAt)}` : 'Todavía no lo ganas' }}
        </p>
      </AppModal>
    </AppCard>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Medal } from '@lucide/vue';
import SectionTitle from './SectionTitle.vue';
import AppCard from './AppCard.vue';
import AppModal from './AppModal.vue';
import { formatDateDMY } from '../utils/dateFormatter';
import { BADGES } from '../constants';
import { HapticsService } from '../services/haptics';

const props = defineProps({
  // Array {badge_id, earned_at} tal cual devuelve el backend.
  earnedBadges: { type: Array, default: () => [] }
});

const selected = ref(null);

const selectBadge = (badge) => {
  HapticsService.light();
  selected.value = badge;
};

const badges = computed(() => {
  const earnedMap = new Map(props.earnedBadges.map((b) => [b.badge_id, b.earned_at]));
  return BADGES.map((badge) => ({
    ...badge,
    earnedAt: earnedMap.get(badge.id) || null
  }));
});
</script>
