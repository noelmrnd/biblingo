<template>
  <div class="space-y-6">
    <div class="space-y-4">
      <!-- Header Perfil con Resplandor Azul -->
      <div class="relative card-duo bg-slate-900 bg-[radial-gradient(ellipse_at_top_right,_rgba(28,176,246,0.22),_transparent_65%)] border-sky-500/30 text-center py-6 space-y-3 overflow-hidden">
        <button
          type="button"
          @click="router.push({ name: 'profile-settings' })"
          class="absolute top-4 right-4 p-2 text-slate-400 hover:text-white rounded-full transition-colors cursor-pointer"
          aria-label="Configuración"
        >
          <Settings class="w-6 h-6 stroke-[2.5]" />
        </button>

        <div class="w-20 h-20 bg-gradient-to-tr from-brand-blue to-sky-400 rounded-full flex items-center justify-center shadow-xl mx-auto border-4 border-slate-800">
          <UserRound class="w-10 h-10 text-white stroke-[2.5]" />
        </div>
        <div>
          <h2 class="text-2xl font-extrabold text-white">{{ user.display_name }}</h2>
          <p class="text-slate-300 text-base font-medium font-mono">@{{ user.username }}</p>
          <p v-if="memberSinceLabel" class="text-slate-400 text-sm font-medium">Leyendo desde {{ memberSinceLabel }}</p>
        </div>
        <div class="flex items-center justify-center gap-6 text-slate-300 text-base font-medium">
          <button type="button" @click="openFollowList('followers')" class="cursor-pointer hover:text-white transition-colors">
            <strong class="text-white font-extrabold">{{ user.followers_count || 0 }}</strong> {{ user.followers_count === 1 ? 'seguidor' : 'seguidores' }}
          </button>
          <button type="button" @click="openFollowList('following')" class="cursor-pointer hover:text-white transition-colors">
            <strong class="text-white font-extrabold">{{ user.following_count || 0 }}</strong> seguidos
          </button>
        </div>
      </div>

      <!-- Constancia total (distinto de la racha: dias leidos aunque no sean consecutivos) -->
      <div class="card-duo bg-slate-900/90 border-slate-800 p-4 flex items-center justify-center gap-3">
        <BookOpenCheck class="w-6 h-6 text-brand-green stroke-[2.5]" />
        <span class="text-slate-200 text-base font-semibold">
          <span class="text-brand-green font-extrabold text-xl">{{ user.total_days_read || 0 }}</span>
          {{ (user.total_days_read || 0) === 1 ? 'día leído' : 'días leídos' }} en total
        </span>
      </div>

      <!-- Lecturas favoritas: desglose de reacciones registradas dia a dia -->
      <ExpandableCard
        :collapsible="false"
        title="Tus reacciones"
        icon-bg-class="bg-rose-500/10 border-rose-500/30"
      >
        <template #icon>
          <Heart class="w-5 h-5 text-rose-400 stroke-[2.5]" />
        </template>

        <div v-if="reactionBreakdown.length > 0" class="flex flex-wrap gap-2">
          <div
            v-for="r in reactionBreakdown"
            :key="r.id"
            class="flex items-center gap-1.5 bg-slate-950/70 border border-slate-800 rounded-xl px-3 py-1.5"
          >
            <span class="text-lg leading-none">{{ r.emoji }}</span>
            <span class="text-slate-200 text-sm font-bold">{{ r.count }}</span>
            <span class="text-slate-400 text-sm font-medium">{{ r.label }}</span>
          </div>
        </div>
        <p v-else class="text-slate-400 text-base font-medium">
          Elige una reacción al registrar tu lectura y aquí verás cuáles se repiten más.
        </p>
      </ExpandableCard>

      <!-- Estadísticas Globales -->
      <div class="grid grid-cols-2 gap-3">
        <div class="card-duo bg-slate-900/90 border-slate-800 p-4 text-center space-y-2">
          <div class="flex items-center justify-center gap-3">
            <span v-if="user.is_streak_lost" class="text-2xl leading-none">🥶</span>
            <Flame v-else class="w-7 h-7 text-amber-400 stroke-[2.5]" />
            <div class="text-3xl font-extrabold text-amber-400">{{ user.is_streak_lost ? 0 : user.streak_count }}</div>
          </div>
          <div class="text-slate-300 text-base font-semibold uppercase tracking-wider">Racha<br/>actual</div>
        </div>
        <div class="card-duo bg-slate-900/90 border-slate-800 p-4 text-center space-y-2">
          <div class="flex items-center justify-center gap-3">
            <Zap class="w-7 h-7 text-purple-400 stroke-[2.5]" />
            <div class="text-3xl font-extrabold text-purple-400">{{ user.max_streak_count }}</div>
          </div>
          <div class="text-slate-300 text-base font-semibold uppercase tracking-wider">Racha<br/>máxima</div>
        </div>
      </div>

      <!-- Protectores de racha -->
      <div class="grid grid-cols-2 gap-3">
        <div class="card-duo bg-slate-900/90 border-slate-800 p-4 text-center space-y-2">
          <div class="flex items-center justify-center gap-3">
            <span class="text-2xl leading-none">🧊</span>
            <div class="text-3xl font-extrabold text-sky-400">{{ user.streak_freezes || 0 }}</div>
          </div>
          <div class="text-slate-300 text-base font-semibold uppercase tracking-wider">Protectores<br/>disponibles</div>
        </div>
        <div class="card-duo bg-slate-900/90 border-slate-800 p-4 text-center space-y-2">
          <div class="flex items-center justify-center gap-3">
            <span class="text-2xl leading-none">📖</span>
            <div class="text-3xl font-extrabold text-slate-400">{{ user.streak_freezes_used || 0 }}</div>
          </div>
          <div class="text-slate-300 text-base font-semibold uppercase tracking-wider">Protectores<br/>usados</div>
        </div>
      </div>

    </div>

    <!-- Lista de Seguidores / Seguidos -->
    <FollowListModal
      :is-open="isFollowListOpen"
      :user-id="user.id"
      :initial-tab="followListInitialTab"
      :display-name="user.display_name"
      is-own-profile
      @close="closeFollowList"
      @change-tab="switchFollowListTab"
      @select-user="goToFriendProfile"
    />
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import FollowListModal from '../components/FollowListModal.vue';
import { UserRound, Flame, Zap, Settings, BookOpenCheck, Heart } from '@lucide/vue';
import ExpandableCard from '../components/ExpandableCard.vue';
import { formatMemberSince } from '../utils/dateFormatter';
import { READING_REACTIONS } from '../constants';
import { useCurrentUser } from '../composables/useCurrentUser';

const props = defineProps({
  user: { type: Object, required: true }
});

const route = useRoute();
const router = useRouter();

// Refresca el usuario propio al entrar a esta vista, en vez de depender de que otro
// componente (ej. Dashboard) lo haya hecho antes. Comparte el mismo singleton y el
// mismo guard anti-duplicados que Dashboard, asi que si Dashboard ya pidio el estado
// hace poco, esto no vuelve a golpear la API.
const { refreshProfile } = useCurrentUser();
onMounted(() => refreshProfile());

const reactionBreakdown = computed(() => {
  const counts = props.user.reaction_counts || {};
  return READING_REACTIONS
    .map((r) => ({ ...r, count: counts[r.id] || 0 }))
    .filter((r) => r.count > 0)
    .sort((a, b) => b.count - a.count);
});

// El modal se sincroniza con ?panel= en la URL: abrirlo empuja una entrada al
// historial, asi el boton atras del navegador lo cierra solo, y al volver desde el
// perfil de un amigo (navegado desde la lista) se reabre automaticamente.
const isFollowListOpen = computed(() => !!route.query.panel);
const followListInitialTab = computed(() => route.query.panel === 'following' ? 'following' : 'followers');

const openFollowList = (tab) => {
  router.push({ query: { ...route.query, panel: tab } });
};

// Cambiar entre Seguidores/Seguidos reemplaza la entrada actual en vez de apilar una
// nueva, para que "atras" cierre el modal en un solo paso sin importar cuantas veces
// se cambio de tab.
const switchFollowListTab = (tab) => {
  router.replace({ query: { ...route.query, panel: tab } });
};

const closeFollowList = () => {
  router.back();
};

const goToFriendProfile = (id) => {
  router.push({ name: 'friend-profile', params: { id } });
};

const memberSinceLabel = computed(() => formatMemberSince(props.user.member_since));
</script>
