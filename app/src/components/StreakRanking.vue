<template>
  <div class="space-y-3">
    <h3 class="font-extrabold text-white text-lg flex items-center gap-3">
      <Trophy class="w-6 h-6 text-amber-400 stroke-[2.5]" />
      <span>Ranking de rachas</span>
    </h3>

    <div v-if="friends.filter(f => !f.is_self).length === 0" class="card-duo text-center py-8 text-slate-400 space-y-2">
      <UsersRound class="w-12 h-12 text-slate-500 mx-auto stroke-[2]" />
      <p class="text-lg font-extrabold text-white">Aún no sigues a nadie.</p>
      <p class="text-base text-slate-300 font-medium">Comparte tu perfil o agrega a un amigo por su usuario para empezar.</p>
    </div>

    <div v-else class="space-y-3">
      <SwipeItem
        v-for="(friend, index) in friends"
        :key="friend.id"
        :disabled="friend.is_self"
        :is-open="activeSwipeFriendId === friend.id"
        :action-width="88"
        @open="activeSwipeFriendId = friend.id"
        @close="handleSwipeClose(friend.id)"
        @action="promptRemoveFriend(friend)"
      >
        <div
          @click="!friend.is_self && router.push({ name: 'friend-profile', params: { id: friend.id } })"
          :class="!friend.is_self && 'cursor-pointer'"
          class="card-duo flex items-center justify-between transition-colors gap-3"
        >
          <!-- Medallas de ranking -->
          <div class="min-w-8 text-center">
            <span class="text-4xl" v-if="index === 0">🥇</span>
            <span class="text-4xl" v-else-if="index === 1">🥈</span>
            <span class="text-4xl" v-else-if="index === 2">🥉</span>
            <span v-else class="text-xl text-slate-400 font-semibold">{{ index + 1 }}</span>
          </div>

          <div class="min-w-0 flex-1">
            <h4 class="font-bold text-white text-base flex items-center gap-3">
              <span class="truncate">{{ friend.display_name }}</span>
              <span v-if="friend.is_self" class="text-sm bg-brand-green/20 text-brand-green px-2 py-0.5 rounded-md font-black flex-none">TÚ</span>
            </h4>
            <p class="text-slate-300 text-base font-medium truncate">
              {{ friend.last_read_label }}
            </p>
          </div>

          <!-- Acciones de Racha & Recordatorio -->
          <div class="flex items-center gap-2 flex-none">
            <!-- Botón Dar un Toque (Solo visible para amigos que no han leído hoy) -->
            <button
              v-if="!friend.is_self && friend.is_mutual && !friend.has_read_today"
              @click.stop="nudge.sendNudge(friend.id, friend.display_name)"
              :disabled="nudge.nudged[friend.id] || nudge.loading[friend.id]"
              class="bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 disabled:from-slate-800 disabled:to-slate-800 disabled:text-slate-500 text-slate-950 font-semibold px-3 py-1.5 rounded-xl text-base flex items-center gap-1.5 shadow-md active:scale-95 transition-all cursor-pointer border border-amber-400/40 disabled:border-slate-700"
            >
              <BellRing class="w-4 h-4 stroke-[2.5]" />
              <span>{{ nudge.nudged[friend.id] ? 'Enviado' : 'Toque' }}</span>
            </button>

            <!-- Badge de Racha Unificado -->
            <div
              :class="[
                friend.is_streak_lost
                  ? 'bg-sky-500/10 border-sky-500/20 text-sky-300'
                  : 'bg-amber-500/10 border-amber-500/20 text-amber-400'
              ]"
              class="flex items-center gap-1.5 font-extrabold text-base px-2.5 py-1.5 rounded-xl border"
            >
              <span v-if="friend.is_streak_lost" class="text-base leading-none">🥶</span>
              <Flame v-else class="w-4 h-4 text-amber-400 stroke-[2.5]" />
              <span>{{ friend.streak_count }}</span>
            </div>
          </div>
        </div>
      </SwipeItem>
    </div>

    <!-- Modal Confirmación de Dejar de Seguir -->
    <UnfollowConfirmModal
      :is-open="isRemoveModalOpen"
      :loading="removeLoading"
      :display-name="friendToRemove?.display_name"
      @close="closeRemoveModal"
      @confirm="confirmRemoveFriend"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import SwipeItem from './SwipeItem.vue';
import UnfollowConfirmModal from './UnfollowConfirmModal.vue';
import { Trophy, UsersRound, Flame, BellRing } from '@lucide/vue';
import { ApiService } from '../services/api';
import { ToastService } from '../services/toast';
import { useNudge } from '../composables/useNudge';

const props = defineProps({
  user: { type: Object, required: true }
});

const router = useRouter();

const friends = ref([]);
const nudge = useNudge();
const isRemoveModalOpen = ref(false);
const friendToRemove = ref(null);
const removeLoading = ref(false);
const activeSwipeFriendId = ref(null);

const loadFriends = async () => {
  try {
    const res = await ApiService.getFriends();
    if (res.success) {
      friends.value = res.friends || [];
      // Sincronizar estado de toques enviados hoy desde la API
      friends.value.forEach(f => {
        if (f.nudged_today) {
          nudge.markNudged(f.id);
        }
      });
    }
  } catch (e) {
    console.warn('Error al cargar amigos:', e.message);
  }
};

const handleSwipeClose = (friendId) => {
  if (activeSwipeFriendId.value === friendId) {
    activeSwipeFriendId.value = null;
  }
};

const promptRemoveFriend = (friend) => {
  friendToRemove.value = friend;
  isRemoveModalOpen.value = true;
};

const closeRemoveModal = () => {
  isRemoveModalOpen.value = false;
  friendToRemove.value = null;
  activeSwipeFriendId.value = null;
};

const confirmRemoveFriend = async () => {
  if (!friendToRemove.value || removeLoading.value) return;
  const friend = friendToRemove.value;
  removeLoading.value = true;
  try {
    const res = await ApiService.unfollowUser(friend.id);
    if (res.success) {
      ToastService.success(`Dejaste de seguir a ${friend.display_name}.`);
      friends.value = friends.value.filter(f => f.id !== friend.id);
      delete nudge.nudged[friend.id];
      closeRemoveModal();
    }
  } catch (e) {
    ToastService.error(e.message || 'Error al dejar de seguir.');
  } finally {
    removeLoading.value = false;
  }
};

onMounted(() => {
  loadFriends();
});

defineExpose({ loadFriends });
</script>
