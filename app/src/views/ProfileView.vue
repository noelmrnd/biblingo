<template>
  <AppPage app-header>
    <!-- Header Perfil con Resplandor Azul -->
    <ProfileHeader
      :display-name="user.display_name"
      :username="user.username"
      :member-since-label="memberSinceLabel"
      :followers-count="user.followers_count"
      :following-count="user.following_count"
      @open-followers="followList.open('followers')"
      @open-following="followList.open('following')"
    >
      <template #corner>
        <button
          type="button"
          @click="router.push({ name: 'profile-settings' })"
          class="absolute top-4 right-4 p-2 text-slate-400 hover:text-white rounded-full transition-colors cursor-pointer"
          aria-label="Configuración"
        >
          <Settings class="w-6 h-6 stroke-[2.5]" />
        </button>
      </template>
    </ProfileHeader>

    <!-- Resumen: racha actual, maxima, constancia total y protectores usados (mismo peso visual) -->
    <div class="grid grid-cols-2 gap-3">
      <StatCard
        :value="user.is_streak_lost ? 0 : user.streak_count"
        label="Racha actual"
        color-class="text-amber-400"
      >
        <template #icon>
          <span v-if="user.is_streak_lost" class="text-xl leading-none">🥶</span>
          <Flame v-else class="w-5 h-5 text-amber-400 stroke-[2.5]" />
        </template>
      </StatCard>
      <StatCard :value="user.total_days_read || 0" label="Días leídos" color-class="text-brand-green">
        <template #icon>
          <BookOpenCheck class="w-5 h-5 text-brand-green stroke-[2.5]" />
        </template>
      </StatCard>
      <StatCard :value="user.max_streak_count" label="Racha máxima" color-class="text-purple-400">
        <template #icon>
          <Zap class="w-5 h-5 text-purple-400 stroke-[2.5]" />
        </template>
      </StatCard>
      <StatCard :value="user.streak_freezes_used || 0" label="Protectores usados" color-class="text-slate-300">
        <template #icon>
          <Shield class="w-5 h-5 text-slate-300 stroke-[2.5]" />
        </template>
      </StatCard>
    </div>

    <!-- Lecturas favoritas: desglose de reacciones registradas dia a dia -->
    <ReactionBreakdown
      :reaction-counts="user.reaction_counts"
      title="Tus reacciones"
      empty-label="Elige una reacción al registrar tu lectura y aquí verás cuáles se repiten más."
    />

    <!-- Lista de Seguidores / Seguidos -->
    <FollowListModal
      :is-open="followList.isOpen.value"
      :user-id="user.id"
      :initial-tab="followList.initialTab.value"
      :display-name="user.display_name"
      is-own-profile
      @close="followList.close"
      @change-tab="followList.switchTab"
      @select-user="followList.goToFriendProfile"
    />
  </AppPage>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AppPage from '../components/AppPage.vue';
import FollowListModal from '../components/FollowListModal.vue';
import ProfileHeader from '../components/ProfileHeader.vue';
import ReactionBreakdown from '../components/ReactionBreakdown.vue';
import { Flame, Zap, Settings, BookOpenCheck, Shield } from '@lucide/vue';
import StatCard from '../components/StatCard.vue';
import { formatMemberSince } from '../utils/dateFormatter';
import { useCurrentUser } from '../composables/useCurrentUser';
import { useFollowListPanel } from '../composables/useFollowListPanel';

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

const followList = useFollowListPanel(route, router);

const memberSinceLabel = computed(() => formatMemberSince(props.user.member_since));
</script>
