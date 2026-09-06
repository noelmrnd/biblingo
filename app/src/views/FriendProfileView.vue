<template>
  <AppPage :title="friend?.display_name || 'Perfil'" :back-to="{ name: 'friends' }">
    <div v-if="loading" class="py-16 text-center text-slate-400">Cargando perfil...</div>

    <div v-else-if="!friend" class="card-duo text-center py-8 text-slate-400 space-y-2">
      <UserX class="w-12 h-12 text-slate-500 mx-auto stroke-[2]" />
      <p class="text-lg font-extrabold text-white">No se encontró a este amigo.</p>
    </div>

    <template v-else>
      <div class="relative card-duo bg-slate-900 bg-[radial-gradient(ellipse_at_top_right,_rgba(28,176,246,0.22),_transparent_65%)] border-sky-500/30 text-center py-6 space-y-3 overflow-hidden">
        <div class="w-20 h-20 bg-gradient-to-tr from-brand-blue to-sky-400 rounded-full flex items-center justify-center shadow-xl mx-auto border-4 border-slate-800 text-white font-black text-3xl">
          {{ (friend.display_name || '?').charAt(0).toUpperCase() }}
        </div>
        <div class="space-y-1">
          <h2 class="text-2xl font-extrabold text-white">{{ friend.display_name }}</h2>
          <p class="text-slate-300 text-base font-medium font-mono flex flex-wrap items-center justify-center gap-x-2 gap-y-1">
            <span>@{{ friend.username }}</span>
            <span v-if="friend.is_mutual" class="text-xs bg-brand-green/20 text-brand-green px-2 py-0.5 rounded-md font-black font-sans">AMIGOS</span>
            <span v-else-if="friend.is_following" class="text-xs bg-slate-800 text-slate-400 px-2 py-0.5 rounded-md font-bold font-sans">SIGUIENDO</span>
            <span v-else-if="friend.is_followed_by" class="text-xs bg-sky-500/20 text-sky-300 px-2 py-0.5 rounded-md font-bold font-sans">TE SIGUE</span>
          </p>
          <p class="text-slate-300 text-base font-medium">Leyó: {{ friend.last_read_label }}</p>
          <p v-if="memberSinceLabel" class="text-slate-400 text-sm font-medium">Leyendo desde {{ memberSinceLabel }}</p>
        </div>
        <div class="flex items-center justify-center gap-6 text-slate-300 text-base font-medium">
          <button type="button" @click="openFollowList('followers')" class="cursor-pointer hover:text-white transition-colors">
            <strong class="text-white font-extrabold">{{ friend.followers_count || 0 }}</strong> {{ friend.followers_count === 1 ? 'seguidor' : 'seguidores' }}
          </button>
          <button type="button" @click="openFollowList('following')" class="cursor-pointer hover:text-white transition-colors">
            <strong class="text-white font-extrabold">{{ friend.following_count || 0 }}</strong> seguidos
          </button>
        </div>
      </div>

      <ExpandableCard
        :collapsible="false"
        title="Reacciones"
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
        <p v-else class="text-slate-400 text-base font-medium">Aún no ha registrado reacciones.</p>
      </ExpandableCard>

      <div class="grid grid-cols-2 gap-3">
        <StatCard
          :value="friend.is_streak_lost ? 0 : friend.streak_count"
          label="Racha actual"
          color-class="text-amber-400"
        >
          <template #icon>
            <span v-if="friend.is_streak_lost" class="text-xl leading-none">🥶</span>
            <Flame v-else class="w-5 h-5 text-amber-400 stroke-[2.5]" />
          </template>
        </StatCard>
        <StatCard :value="friend.total_days_read || 0" label="Días leídos" color-class="text-brand-green">
          <template #icon>
            <BookOpenCheck class="w-5 h-5 text-brand-green stroke-[2.5]" />
          </template>
        </StatCard>
      </div>

      <div v-if="friend.mutual_friends_count > 0" class="flex items-center justify-center gap-2 text-slate-300 text-base font-medium">
        <UsersRound class="w-5 h-5 text-slate-400 stroke-[2.5]" />
        <span>{{ friend.mutual_friends_count }} amigo{{ friend.mutual_friends_count > 1 ? 's' : '' }} en común</span>
      </div>

      <button
        v-if="friend.is_mutual && !friend.has_read_today"
        @click="sendNudge"
        :disabled="nudged || nudgeLoading"
        class="w-full bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 disabled:from-slate-800 disabled:to-slate-800 disabled:text-slate-500 text-slate-950 font-bold py-3.5 px-4 rounded-2xl flex items-center justify-center gap-2 shadow-md active:scale-95 transition-all cursor-pointer border border-amber-400/40 disabled:border-slate-700"
      >
        <BellRing class="w-5 h-5 stroke-[2.5]" />
        <span>{{ nudged ? 'Toque enviado' : 'Dar un toque' }}</span>
      </button>

      <button
        v-if="friend.is_following"
        type="button"
        @click="isRemoveModalOpen = true"
        class="w-full bg-slate-800 hover:bg-rose-950/40 text-slate-400 hover:text-rose-300 font-bold py-3.5 px-4 rounded-2xl border-2 border-slate-700 hover:border-rose-800 transition-colors text-base flex items-center justify-center gap-3 cursor-pointer"
      >
        <UserMinus class="w-5 h-5 text-rose-400 stroke-[2.5]" />
        <span>Dejar de seguir</span>
      </button>
    </template>

    <AppModal
      :is-open="isRemoveModalOpen"
      :loading="removeLoading"
      :title="friend ? `¿Dejar de seguir a ${friend.display_name}?` : '¿Dejar de seguir?'"
      description="Ya no verás su progreso en tu ranking."
      @close="isRemoveModalOpen = false"
      :show-close="false"
    >
      <template #icon>
        <div class="w-11 h-11 rounded-2xl bg-rose-500/10 border border-rose-500/30 flex items-center justify-center shrink-0">
          <UserMinus class="w-5 h-5 text-rose-400 stroke-[2.5]" />
        </div>
      </template>

      <template #footer>
        <div class="flex items-center gap-3 w-full">
          <div class="flex-1">
            <AppButton color="dark" block :disabled="removeLoading" @click="isRemoveModalOpen = false">
              Cancelar
            </AppButton>
          </div>
          <div class="flex-1">
            <AppButton color="rose" block :disabled="removeLoading" @click="confirmRemove">
              {{ removeLoading ? 'Procesando...' : 'Dejar de seguir' }}
            </AppButton>
          </div>
        </div>
      </template>
    </AppModal>

    <FollowListModal
      :is-open="isFollowListOpen"
      :user-id="props.id"
      :initial-tab="followListInitialTab"
      :display-name="friend?.display_name"
      @close="closeFollowList"
      @change-tab="switchFollowListTab"
      @select-user="goToFriendProfile"
    />
  </AppPage>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { Flame, BellRing, UserMinus, UserX, UsersRound, BookOpenCheck, Heart } from '@lucide/vue';
import AppPage from '../components/AppPage.vue';
import ExpandableCard from '../components/ExpandableCard.vue';
import StatCard from '../components/StatCard.vue';
import AppButton from '../components/AppButton.vue';
import AppModal from '../components/AppModal.vue';
import FollowListModal from '../components/FollowListModal.vue';
import { READING_REACTIONS } from '../constants';
import { ApiService } from '../services/api';
import { ToastService } from '../services/toast';
import { formatMemberSince } from '../utils/dateFormatter';

const props = defineProps({
  id: { type: String, required: true },
  user: { type: Object, required: true }
});

const route = useRoute();
const router = useRouter();

const loading = ref(true);
const friend = ref(null);
const nudged = ref(false);
const nudgeLoading = ref(false);
const isRemoveModalOpen = ref(false);
const removeLoading = ref(false);

const memberSinceLabel = computed(() => formatMemberSince(friend.value?.member_since));

const reactionBreakdown = computed(() => {
  const counts = friend.value?.reaction_counts || {};
  return READING_REACTIONS
    .map((r) => ({ ...r, count: counts[r.id] || 0 }))
    .filter((r) => r.count > 0)
    .sort((a, b) => b.count - a.count);
});

// El modal se sincroniza con ?panel= en la URL: abrirlo empuja una entrada al
// historial, asi el boton atras del navegador lo cierra solo, y al volver desde el
// perfil de otro amigo (navegado desde la lista) se reabre automaticamente.
const isFollowListOpen = computed(() => !!route.query.panel);
const followListInitialTab = computed(() => route.query.panel === 'following' ? 'following' : 'followers');

const openFollowList = (tab) => {
  router.push({ query: { ...route.query, panel: tab } });
};

const switchFollowListTab = (tab) => {
  router.replace({ query: { ...route.query, panel: tab } });
};

const closeFollowList = () => {
  router.back();
};

const goToFriendProfile = (id) => {
  router.push({ name: 'friend-profile', params: { id } });
};

const loadFriendProfile = async (friendId) => {
  loading.value = true;
  friend.value = null;
  nudged.value = false;
  try {
    const res = await ApiService.getFriendProfile(friendId);
    if (res.success) {
      friend.value = {
        ...res.user,
        mutual_friends_count: res.mutual_friends_count
      };
      if (res.nudged_today) {
        nudged.value = true;
      }
    }
  } catch (e) {
    console.warn('Error al cargar el perfil del amigo:', e.message);
  } finally {
    loading.value = false;
  }
};

onMounted(() => loadFriendProfile(props.id));

// La ruta reutiliza el componente al navegar entre perfiles de amigos (mismo nombre
// de ruta, distinto id), asi que onMounted no vuelve a dispararse por si solo.
watch(() => props.id, (newId) => loadFriendProfile(newId));

const sendNudge = async () => {
  if (nudged.value || nudgeLoading.value) return;
  nudgeLoading.value = true;
  try {
    const res = await ApiService.nudgeFriend(props.user.id, friend.value.id);
    nudged.value = true;
    ToastService.success(res.message || `¡Le enviaste un recordatorio a ${friend.value.display_name}! 🔔`);
  } catch (e) {
    ToastService.error(e.message || 'No se pudo enviar el recordatorio.');
  } finally {
    nudgeLoading.value = false;
  }
};

const confirmRemove = async () => {
  removeLoading.value = true;
  try {
    const res = await ApiService.unfollowUser(friend.value.id);
    if (res.success) {
      ToastService.success(`Dejaste de seguir a ${friend.value.display_name}.`);
      router.push({ name: 'friends' });
    }
  } catch (e) {
    ToastService.error(e.message || 'Error al dejar de seguir.');
  } finally {
    removeLoading.value = false;
    isRemoveModalOpen.value = false;
  }
};
</script>
