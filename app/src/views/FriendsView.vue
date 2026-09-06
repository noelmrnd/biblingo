<template>
  <div class="space-y-6">
    <!-- Invitar amigos -->
    <ExpandableCard
      v-model="isInviteExpanded"
      title="Invitar amigos"
      description="Comparte tu usuario o agrega a tus amigos"
      icon-bg-class="bg-brand-green/10 border-brand-green/30"
      card-class="bg-slate-900 bg-[radial-gradient(ellipse_at_top_right,_rgba(88,204,2,0.18),_transparent_65%)] border-indigo-500/30"
    >
      <template #icon>
        <UserRoundPlus class="w-5 h-5 text-brand-green stroke-[2.5]" />
      </template>

      <div class="space-y-3">
        <AppButton color="green" block @click="isShareModalOpen = true">
          <Share2 class="w-5 h-5 stroke-[2.5]" />
          <span>Compartir mi perfil</span>
        </AppButton>

        <AppButton color="blue" block @click="isAddFriendModalOpen = true">
          <UserRoundPlus class="w-5 h-5 stroke-[2.5]" />
          <span>Agregar amigos</span>
        </AppButton>
      </div>
    </ExpandableCard>

    <ShareProfileModal
      :is-open="isShareModalOpen"
      :username="user.username"
      @close="isShareModalOpen = false"
    />

    <AddFriendModal
      :is-open="isAddFriendModalOpen"
      @close="isAddFriendModalOpen = false"
      @added="loadFriends"
    />

    <!-- Tabla de Clasificación de Personas que sigues -->
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
                Leyó: {{ friend.last_read_label }}
              </p>
            </div>

            <!-- Acciones de Racha & Recordatorio -->
            <div class="flex items-center gap-2 flex-none">
              <!-- Botón Dar un Toque (Solo visible para amigos que no han leído hoy) -->
              <button
                v-if="!friend.is_self && friend.is_mutual && !friend.has_read_today"
                @click.stop="sendNudge(friend)"
                :disabled="nudgedFriends[friend.id] || nudgeLoading[friend.id]"
                class="bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 disabled:from-slate-800 disabled:to-slate-800 disabled:text-slate-500 text-slate-950 font-semibold px-3 py-1.5 rounded-xl text-base flex items-center gap-1.5 shadow-md active:scale-95 transition-all cursor-pointer border border-amber-400/40 disabled:border-slate-700"
              >
                <BellRing class="w-4 h-4 stroke-[2.5]" />
                <span>{{ nudgedFriends[friend.id] ? 'Enviado' : 'Toque' }}</span>
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
    </div>

    <!-- Modal Confirmación de Dejar de Seguir -->
    <AppModal
      :is-open="isRemoveModalOpen"
      :loading="removeLoading"
      :title="friendToRemove ? `¿Dejar de seguir a ${friendToRemove.display_name}?` : '¿Dejar de seguir?'"
      description="Ya no verás su progreso en tu ranking."
      @close="closeRemoveModal"
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
            <AppButton
              color="dark"
              block
              :disabled="removeLoading"
              @click="closeRemoveModal"
            >
              Cancelar
            </AppButton>
          </div>
          <div class="flex-1">
            <AppButton
              color="rose"
              block
              :disabled="removeLoading"
              @click="confirmRemoveFriend"
            >
              {{ removeLoading ? 'Procesando...' : 'Dejar de seguir' }}
            </AppButton>
          </div>
        </div>
      </template>
    </AppModal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import AppButton from '../components/AppButton.vue';
import AppModal from '../components/AppModal.vue';
import SwipeItem from '../components/SwipeItem.vue';
import { Share2, UserRoundPlus, Trophy, UsersRound, Flame, BellRing, UserMinus } from '@lucide/vue';
import ExpandableCard from '../components/ExpandableCard.vue';
import ShareProfileModal from '../components/ShareProfileModal.vue';
import AddFriendModal from '../components/AddFriendModal.vue';
import { ApiService } from '../services/api';
import { ToastService } from '../services/toast';

const props = defineProps({
  user: { type: Object, required: true }
});

const router = useRouter();

const friends = ref([]);
const isInviteExpanded = ref(false);
const isShareModalOpen = ref(false);
const isAddFriendModalOpen = ref(false);
const nudgedFriends = ref({});
const nudgeLoading = ref({});
const isRemoveModalOpen = ref(false);
const friendToRemove = ref(null);
const removeLoading = ref(false);
const activeSwipeFriendId = ref(null);

const sendNudge = async (friend) => {
  if (nudgedFriends.value[friend.id] || nudgeLoading.value[friend.id]) return;

  nudgeLoading.value[friend.id] = true;
  try {
    const res = await ApiService.nudgeFriend(props.user.id, friend.id);
    nudgedFriends.value[friend.id] = true;
    ToastService.success(res.message || `¡Le enviaste un recordatorio a ${friend.display_name}! 🔔`);
  } catch (e) {
    ToastService.error(e.message || `No se pudo enviar el recordatorio.`);
  } finally {
    nudgeLoading.value[friend.id] = false;
  }
};

const loadFriends = async () => {
  try {
    const res = await ApiService.getFriends(props.user.id);
    if (res.success) {
      friends.value = res.friends || [];
      // Sincronizar estado de toques enviados hoy desde la API
      friends.value.forEach(f => {
        if (f.nudged_today) {
          nudgedFriends.value[f.id] = true;
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
      delete nudgedFriends.value[friend.id];
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
</script>
