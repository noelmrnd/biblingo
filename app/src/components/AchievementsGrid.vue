<template>
  <SectionTitle title="Logros" :icon="Medal" icon-color-class="text-amber-400">
    <AppCard>
      <div class="grid grid-cols-3 gap-8 p-2">
        <button
          v-for="tile in tiles"
          :key="tile.category"
          type="button"
          @click="selectTile(tile)"
          class="flex flex-col items-center gap-3 cursor-pointer"
        >
          <div class="relative">
            <div class="aspect-square">
              <img
                :src="tile.image"
                :alt="tile.displayTier.label"
                :class="tile.isAnyEarned ? '' : 'opacity-30 grayscale'"
                class="w-full h-full object-contain aspect-square"
              />
            </div>
            <div
              v-if="tile.progressLabel"
              class="absolute bottom-0 right-0 bg-slate-800 border border-slate-700 text-slate-200 text-xs font-semibold px-2 py-1.5 rounded-full leading-none"
            >
              {{ tile.progressLabel }}
            </div>
          </div>
          <div class="text-slate-300 text-xs font-semibold text-center leading-tight line-clamp-2">
            {{ tile.displayTier.label }}
          </div>
        </button>
      </div>

      <AppModal
        :is-open="!!selectedTile"
        @close="selectedTile = null"
      >
        <template v-if="selectedTile" #header>
          <div class="relative flex flex-col items-center text-center gap-2 pb-2">
            <IconButton
              @click="selectedTile = null"
              :haptic="false"
              class="absolute -top-1 -right-1"
              aria-label="Cerrar"
            >
              <X class="w-5 h-5 stroke-[2.5]" />
            </IconButton>
            <img
              :src="selectedTile.image"
              :alt="selectedTile.displayTier.label"
              :class="selectedTile.isAnyEarned ? '' : 'opacity-30 grayscale'"
              class="w-32 h-32 object-contain"
            />
            <h3 class="text-xl text-white font-bold leading-tight">{{ selectedTile.displayTier.label }}</h3>
            <p class="text-base text-slate-300 font-medium">{{ selectedTile.description }}</p>
            <p v-if="selectedTile.items.length > 1" class="text-slate-500 text-sm font-medium">
              {{ selectedTile.earnedCount }} de {{ selectedTile.items.length }}
              {{ pluralize(selectedTile.items.length, 'nivel obtenido', 'niveles obtenidos') }}
            </p>
          </div>
        </template>

        <div v-if="selectedTile" class="space-y-2">
          <div
            v-for="tier in selectedTile.items"
            :key="tier.id"
            :class="tier.earnedAt ? 'border-brand-green/30 bg-brand-green/5' : 'border-slate-800 bg-slate-950/40'"
            class="flex items-center gap-3 px-4 py-3 rounded-2xl border"
          >
            <div class="min-w-0 flex-1">
              <p class="text-white font-bold text-base">{{ tier.label }}</p>
              <p v-if="tier.earnedAt" class="text-brand-green text-xs font-semibold mt-0.5">Obtenido el {{ formatDateDMY(tier.earnedAt) }}</p>
              <p v-else class="text-slate-500 text-xs font-semibold mt-0.5">Todavía no lo ganas</p>
            </div>
            <component :is="tier.earnedAt ? Check : Lock" class="w-5 h-5 shrink-0" :class="tier.earnedAt ? 'text-brand-green' : 'text-slate-600'" />
          </div>
        </div>
      </AppModal>
    </AppCard>
  </SectionTitle>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Medal, Check, Lock, X } from '@lucide/vue';
import SectionTitle from './SectionTitle.vue';
import AppCard from './AppCard.vue';
import AppModal from './AppModal.vue';
import IconButton from './IconButton.vue';
import { formatDateDMY } from '@/utils/dateFormatter';
import { pluralize } from '@/utils/pluralize';
import { BADGE_GROUPS } from '@/constants';
import { HapticsService } from '@/services/haptics';

const props = defineProps({
  // Array {badge_id, earned_at} tal cual devuelve el backend.
  earnedBadges: { type: Array, default: () => [] }
});

const selectedTile = ref(null);

const selectTile = (tile) => {
  HapticsService.light();
  selectedTile.value = tile;
};

// Cada "tile" es 1 categoria de BADGE_GROUPS (streak, following, reaction_loved,
// pages, etc), NO 1 por medalla individual: agrupa todos los tiers (items) de
// esa categoria en un solo circulo (sticker fijo) que muestra el nivel mas
// alto ya alcanzado + cuantos quedan por ganar, en vez de un circulo por cada
// umbral (que es como se mostraba antes). Sin encabezados de sección — un solo
// grid continuo, pero BADGE_GROUPS ya viene en el orden curado (racha primero,
// lectura/amigos/reacciones despues, toques y fundador al final) asi que las
// categorias relacionadas quedan juntas visualmente aunque no haya titulo.
const tiles = computed(() => {
  const earnedMap = new Map(props.earnedBadges.map((b) => [b.badge_id, b.earned_at]));

  return BADGE_GROUPS.map((badgeGroup) => {
    const items = badgeGroup.items.map((item) => ({ ...item, earnedAt: earnedMap.get(item.id) || null }));
    const earnedItems = items.filter((t) => t.earnedAt);
    const highestEarned = earnedItems[earnedItems.length - 1] || null;

    return {
      category: badgeGroup.category,
      image: badgeGroup.image,
      description: badgeGroup.description,
      items,
      earnedCount: earnedItems.length,
      isAnyEarned: earnedItems.length > 0,
      displayTier: highestEarned || items[0],
      progressLabel: items.length > 1 ? `${earnedItems.length}/${items.length}` : null,
    };
  });
});
</script>
