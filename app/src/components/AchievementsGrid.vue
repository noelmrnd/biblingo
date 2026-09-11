<template>
  <div class="space-y-3">
    <SectionTitle title="Logros" :icon="Medal" icon-color-class="text-amber-400" />

    <AppCard>
      <div class="grid grid-cols-3 gap-4">
        <button
          v-for="tile in tiles"
          :key="tile.groupKey"
          type="button"
          @click="selectTile(tile)"
          class="flex flex-col items-center gap-1.5 cursor-pointer justify-center aspect-square"
        >
          <div class="relative">
            <div
              :class="tile.isAnyEarned ? 'bg-amber-500/10 border-amber-500/30' : 'bg-slate-950/70 border-slate-800 opacity-30 grayscale'"
              class="w-16 h-16 rounded-full border flex items-center justify-center text-3xl leading-none"
            >
              {{ tile.displayTier.emoji }}
            </div>
            <span
              v-if="tile.progressLabel"
              class="absolute -bottom-1 -right-1 bg-slate-800 border border-slate-700 text-slate-200 text-[10px] font-bold px-1.5 py-0.5 rounded-full leading-none"
            >
              {{ tile.progressLabel }}
            </span>
          </div>
          <span class="text-slate-300 text-xs font-semibold text-center leading-tight line-clamp-2">{{ tile.displayTier.label }}</span>
        </button>
      </div>

      <AppModal
        :is-open="!!selectedTile"
        :title="selectedTile?.displayTier.label"
        :description="selectedTile ? `${selectedTile.earnedCount} de ${selectedTile.tiers.length} ${pluralize(selectedTile.tiers.length, 'nivel obtenido', 'niveles obtenidos')}` : ''"
        @close="selectedTile = null"
      >
        <template v-if="selectedTile" #icon>
          <div
            :class="selectedTile.isAnyEarned ? 'bg-amber-500/10 border-amber-500/30' : 'bg-slate-950/70 border-slate-800 opacity-30 grayscale'"
            class="w-14 h-14 rounded-2xl border flex items-center justify-center text-3xl leading-none shrink-0"
          >
            {{ selectedTile.displayTier.emoji }}
          </div>
        </template>

        <div v-if="selectedTile" class="space-y-2">
          <div
            v-for="tier in selectedTile.tiers"
            :key="tier.id"
            :class="tier.earnedAt ? 'border-brand-green/30 bg-brand-green/5' : 'border-slate-800 bg-slate-950/40'"
            class="flex items-center gap-3 px-4 py-3 rounded-2xl border"
          >
            <span class="text-2xl leading-none shrink-0">{{ tier.emoji }}</span>
            <div class="min-w-0 flex-1">
              <p class="text-white font-bold text-base">{{ tier.label }}</p>
              <p class="text-slate-400 text-sm font-medium">{{ tier.description }}</p>
              <p v-if="tier.earnedAt" class="text-brand-green text-xs font-semibold mt-0.5">Obtenido el {{ formatDateDMY(tier.earnedAt) }}</p>
              <p v-else class="text-slate-500 text-xs font-semibold mt-0.5">Todavía no lo ganas</p>
            </div>
            <component :is="tier.earnedAt ? Check : Lock" class="w-5 h-5 shrink-0" :class="tier.earnedAt ? 'text-brand-green' : 'text-slate-600'" />
          </div>
        </div>
      </AppModal>
    </AppCard>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Medal, Check, Lock } from '@lucide/vue';
import SectionTitle from './SectionTitle.vue';
import AppCard from './AppCard.vue';
import AppModal from './AppModal.vue';
import { formatDateDMY } from '@/utils/dateFormatter';
import { pluralize } from '@/utils/pluralize';
import { BADGES, getBadgeGroup } from '@/constants';
import { HapticsService } from '@/services/haptics';

const props = defineProps({
  // Array {badge_id, earned_at} tal cual devuelve el backend.
  earnedBadges: { type: Array, default: () => [] }
});

// Orden curado de secciones (no alfabetico): racha primero (lo mas visible del
// dashboard), lectura/amigos/reacciones despues, toques y fundador al final
// (mas de nicho / dependen de otros usuarios).
const GROUP_ORDER = ['streak', 'reading', 'friends', 'reaction', 'nudge', 'founder'];

const selectedTile = ref(null);

const selectTile = (tile) => {
  HapticsService.light();
  selectedTile.value = tile;
};

// Cada "tile" es 1 categoria real de medalla (streak, following, reaction:loved,
// pages, etc — mismo key que usa BadgeEntity::checkAndAward internamente), NO 1
// por medalla individual: agrupa todos los tiers de esa categoria en un solo
// circulo que muestra el nivel mas alto ya alcanzado + cuantos quedan por ganar,
// en vez de un circulo por cada umbral (que es como se mostraba antes).
const tiles = computed(() => {
  const earnedMap = new Map(props.earnedBadges.map((b) => [b.badge_id, b.earned_at]));

  const byGroupKey = new Map();
  for (const badge of BADGES) {
    const groupKey = badge.category + (badge.reaction ? `:${badge.reaction}` : '');
    if (!byGroupKey.has(groupKey)) byGroupKey.set(groupKey, []);
    byGroupKey.get(groupKey).push({ ...badge, earnedAt: earnedMap.get(badge.id) || null });
  }

  const built = [...byGroupKey.entries()].map(([groupKey, tiers]) => {
    const earnedTiers = tiers.filter((t) => t.earnedAt);
    const highestEarned = earnedTiers[earnedTiers.length - 1] || null;

    return {
      groupKey,
      category: tiers[0].category,
      tiers,
      earnedCount: earnedTiers.length,
      isAnyEarned: earnedTiers.length > 0,
      displayTier: highestEarned || tiers[0],
      progressLabel: tiers.length > 1 ? `${earnedTiers.length}/${tiers.length}` : null,
    };
  });

  // Sin encabezados de sección: un solo grid continuo, pero se mantiene el
  // mismo orden curado (GROUP_ORDER) para que las categorias relacionadas
  // queden juntas visualmente aunque no haya titulo que las separe.
  const order = (t) => {
    const idx = GROUP_ORDER.indexOf(getBadgeGroup(t.category));
    return idx === -1 ? GROUP_ORDER.length : idx;
  };
  return built.sort((a, b) => order(a) - order(b));
});
</script>
