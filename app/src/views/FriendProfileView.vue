<template>
  <AppPage :title="friend?.display_name || 'Perfil'" :back-to="{ name: 'friends' }">
    <div v-if="loading" class="py-16 text-center text-slate-400">Cargando perfil...</div>

    <div v-else-if="!friend" class="card-duo text-center py-8 text-slate-400 space-y-2">
      <UserX class="w-12 h-12 text-slate-500 mx-auto stroke-[2]" />
      <p class="text-lg font-extrabold text-white">No se encontró a este amigo.</p>
    </div>

    <template v-else>
      <ProfileHeader
        :display-name="friend.display_name"
        :username="friend.username"
        :avatar-initial="(friend.display_name || '?').charAt(0).toUpperCase()"
        :member-since-label="memberSinceLabel"
        :followers-count="friend.followers_count"
        :following-count="friend.following_count"
        @open-followers="followList.open('followers')"
        @open-following="followList.open('following')"
      >
        <template #badge>
          <span v-if="friend.is_mutual" class="text-xs bg-brand-green/20 text-brand-green px-2 py-0.5 rounded-md font-black font-sans">AMIGOS</span>
          <span v-else-if="friend.is_following" class="text-xs bg-slate-800 text-slate-400 px-2 py-0.5 rounded-md font-bold font-sans">SIGUIENDO</span>
          <span v-else-if="friend.is_followed_by" class="text-xs bg-sky-500/20 text-sky-300 px-2 py-0.5 rounded-md font-bold font-sans">TE SIGUE</span>
        </template>
      </ProfileHeader>

      <div class="flex gap-3">
        <AppButton
          v-if="!friend.is_following"
          color="green"
          block
          :disabled="follow.loading.value"
          @click="followFriend"
        >
          <UserRoundPlus class="w-5 h-5 stroke-[2.5]" />
          <span>{{ follow.loading.value ? 'Siguiendo...' : 'Seguir' }}</span>
        </AppButton>

        <AppButton
          v-else
          color="green"
          block
          @click="isRemoveModalOpen = true"
        >
          <UserCheck class="w-5 h-5 stroke-[2.5]" />
          <span>Siguiendo</span>
        </AppButton>

        <AppButton
          v-if="friend.is_mutual && !friend.has_read_today"
          color="orange"
          block
          :disabled="nudge.nudged[friend.id] || nudge.loading[friend.id]"
          @click="nudge.sendNudge(friend.id, friend.display_name)"
        >
          <BellRing class="w-5 h-5 stroke-[2.5]" />
          <span>{{ nudge.nudged[friend.id] ? 'Toque enviado' : 'Dar un toque' }}</span>
        </AppButton>
      </div>

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

      <ReactionBreakdown :reaction-counts="friend.reaction_counts" />

      <BadgesCircles :earned-badges="friend.badges || []" />

      <div v-if="friend.mutual_friends_count > 0" class="flex items-center justify-center gap-2 text-slate-300 text-base font-medium">
        <UsersRound class="w-5 h-5 text-slate-400 stroke-[2.5]" />
        <span>{{ friend.mutual_friends_count }} amigo{{ friend.mutual_friends_count > 1 ? 's' : '' }} en común</span>
      </div>
    </template>

    <UnfollowConfirmModal
      :is-open="isRemoveModalOpen"
      :loading="remove.loading.value"
      :display-name="friend?.display_name"
      @close="isRemoveModalOpen = false"
      @confirm="confirmRemove"
    />

    <FollowListModal
      :is-open="followList.isOpen.value"
      :user-id="props.id"
      :initial-tab="followList.initialTab.value"
      :display-name="friend?.display_name"
      @close="followList.close"
      @change-tab="followList.switchTab"
      @select-user="followList.goToFriendProfile"
    />
  </AppPage>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { Flame, BellRing, UserCheck, UserRoundPlus, UserX, UsersRound, BookOpenCheck } from '@lucide/vue';
import AppPage from '../components/AppPage.vue';
import BadgesCircles from '../components/BadgesCircles.vue';
import ProfileHeader from '../components/ProfileHeader.vue';
import ReactionBreakdown from '../components/ReactionBreakdown.vue';
import StatCard from '../components/StatCard.vue';
import AppButton from '../components/AppButton.vue';
import UnfollowConfirmModal from '../components/UnfollowConfirmModal.vue';
import FollowListModal from '../components/FollowListModal.vue';
import { ApiService } from '../services/api';
import { ToastService } from '../services/toast';
import { formatMemberSince } from '../utils/dateFormatter';
import { useFollowListPanel } from '../composables/useFollowListPanel';
import { useNudge } from '../composables/useNudge';
import { useAsyncAction } from '../composables/useAsyncAction';

const props = defineProps({
  id: { type: String, required: true },
  user: { type: Object, required: true }
});

const route = useRoute();
const router = useRouter();

const loading = ref(true);
const friend = ref(null);
const nudge = useNudge();
const isRemoveModalOpen = ref(false);
const remove = useAsyncAction();
const follow = useAsyncAction();

const memberSinceLabel = computed(() => formatMemberSince(friend.value?.member_since));

const followList = useFollowListPanel(route, router);

const loadFriendProfile = async (friendId) => {
  loading.value = true;
  friend.value = null;
  try {
    const res = await ApiService.getFriendProfile(friendId);
    if (res.success) {
      friend.value = {
        ...res.user,
        mutual_friends_count: res.mutual_friends_count
      };
      if (res.nudged_today) {
        nudge.markNudged(res.user.id);
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

const followFriend = async () => {
  const res = await follow.run(() => ApiService.followUser(friend.value.username), {
    errorMsg: 'No se pudo seguir a este usuario.'
  });
  if (res?.success) {
    friend.value.is_following = true;
    friend.value.is_mutual = friend.value.is_followed_by;
    ToastService.success(`¡Ahora sigues a ${friend.value.display_name}! 🎉`);
  }
};

const confirmRemove = async () => {
  const res = await remove.run(() => ApiService.unfollowUser(friend.value.id), {
    errorMsg: 'Error al dejar de seguir.'
  });
  if (res?.success) {
    ToastService.success(`Dejaste de seguir a ${friend.value.display_name}.`);
    friend.value.is_following = false;
    friend.value.is_mutual = false;
  }
  isRemoveModalOpen.value = false;
};
</script>
