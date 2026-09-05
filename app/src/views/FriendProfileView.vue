<template>
  <div class="space-y-6">
    <button
      type="button"
      @click="router.push({ name: 'friends' })"
      class="flex items-center gap-2 text-slate-300 hover:text-white font-bold text-base cursor-pointer"
    >
      <ArrowLeft class="w-5 h-5 stroke-[2.5]" />
      <span>Amigos</span>
    </button>

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
        <div>
          <h2 class="text-2xl font-extrabold text-white">{{ friend.display_name }}</h2>
          <p class="text-slate-300 text-base font-medium">{{ friend.last_read_label }}</p>
          <p v-if="memberSinceLabel" class="text-slate-400 text-sm font-medium">Leyendo desde {{ memberSinceLabel }}</p>
        </div>
        <div class="flex items-center justify-center gap-6 text-slate-300 text-base font-medium">
          <span><strong class="text-white font-extrabold">{{ friend.followers_count || 0 }}</strong> {{ friend.followers_count === 1 ? 'seguidor' : 'seguidores' }}</span>
          <span><strong class="text-white font-extrabold">{{ friend.following_count || 0 }}</strong> seguidos</span>
        </div>
      </div>

      <div class="card-duo bg-slate-900/90 border-slate-800 p-4 flex items-center justify-center gap-3">
        <BookOpenCheck class="w-6 h-6 text-brand-green stroke-[2.5]" />
        <span class="text-slate-200 text-base font-semibold">
          <span class="text-brand-green font-extrabold text-xl">{{ friend.total_days_read || 0 }}</span>
          días leídos en total
        </span>
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div class="card-duo bg-slate-900/90 border-slate-800 p-4 text-center space-y-2">
          <div class="flex items-center justify-center gap-3">
            <span v-if="friend.is_streak_lost" class="text-3xl leading-none">🥶</span>
            <Flame v-else class="w-7 h-7 text-amber-400 stroke-[2.5]" />
            <div class="text-3xl font-extrabold text-amber-400">{{ friend.streak_count }}</div>
          </div>
          <div class="text-slate-300 text-base font-semibold uppercase tracking-wider">Racha<br/>actual</div>
        </div>
        <div class="card-duo bg-slate-900/90 border-slate-800 p-4 text-center space-y-2">
          <div class="flex items-center justify-center gap-3">
            <Zap class="w-7 h-7 text-purple-400 stroke-[2.5]" />
            <div class="text-3xl font-extrabold text-purple-400">{{ friend.max_streak_count }}</div>
          </div>
          <div class="text-slate-300 text-base font-semibold uppercase tracking-wider">Racha<br/>máxima</div>
        </div>
      </div>

      <div v-if="friend.mutual_friends_count > 0" class="flex items-center justify-center gap-2 text-slate-300 text-base font-medium">
        <UsersRound class="w-5 h-5 text-slate-400 stroke-[2.5]" />
        <span>{{ friend.mutual_friends_count }} amigo{{ friend.mutual_friends_count > 1 ? 's' : '' }} en común</span>
      </div>

      <WeeklyTracker :preloaded-history="friend.history" :preloaded-has-read-today="friend.has_read_today" />

      <button
        v-if="friend.is_mutual && !friend.has_read_today"
        @click="sendNudge"
        :disabled="nudged || nudgeLoading"
        class="w-full bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 disabled:from-slate-800 disabled:to-slate-800 disabled:text-slate-500 text-slate-950 font-bold py-3.5 px-4 rounded-2xl flex items-center justify-center gap-2 shadow-md active:scale-95 transition-all cursor-pointer border border-amber-400/40 disabled:border-slate-700"
      >
        <BellRing class="w-5 h-5 stroke-[2.5]" />
        <span>{{ nudged ? 'Toque enviado' : 'Dar un toque' }}</span>
      </button>

      <p v-else-if="!friend.is_mutual" class="text-center text-slate-500 text-sm font-medium">
        {{ friend.is_following ? 'Aún no te sigue de vuelta' : 'No te sigue' }} — solo pueden darse toques quienes se siguen mutuamente.
      </p>

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
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { ArrowLeft, Flame, Zap, BellRing, UserMinus, UserX, UsersRound, BookOpenCheck } from '@lucide/vue';
import AppButton from '../components/AppButton.vue';
import AppModal from '../components/AppModal.vue';
import WeeklyTracker from '../components/WeeklyTracker.vue';
import { ApiService } from '../services/api';
import { ToastService } from '../services/toast';
import { formatMemberSince } from '../utils/dateFormatter';

const props = defineProps({
  id: { type: String, required: true },
  user: { type: Object, required: true }
});

const router = useRouter();

const loading = ref(true);
const friend = ref(null);
const nudged = ref(false);
const nudgeLoading = ref(false);
const isRemoveModalOpen = ref(false);
const removeLoading = ref(false);

const memberSinceLabel = computed(() => formatMemberSince(friend.value?.member_since));

onMounted(async () => {
  try {
    const res = await ApiService.getFriendProfile(props.id);
    if (res.success) {
      friend.value = {
        ...res.user,
        history: res.history,
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
});

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
