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
        </div>
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

      <WeeklyTracker :week-days="weekDays" />

      <button
        v-if="!friend.has_read_today"
        @click="sendNudge"
        :disabled="nudged || nudgeLoading"
        class="w-full bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 disabled:from-slate-800 disabled:to-slate-800 disabled:text-slate-500 text-slate-950 font-bold py-3.5 px-4 rounded-2xl flex items-center justify-center gap-2 shadow-md active:scale-95 transition-all cursor-pointer border border-amber-400/40 disabled:border-slate-700"
      >
        <BellRing class="w-5 h-5 stroke-[2.5]" />
        <span>{{ nudged ? 'Toque enviado' : 'Dar un toque' }}</span>
      </button>

      <button
        type="button"
        @click="isRemoveModalOpen = true"
        class="w-full bg-slate-800 hover:bg-rose-950/40 text-slate-400 hover:text-rose-300 font-bold py-3.5 px-4 rounded-2xl border-2 border-slate-700 hover:border-rose-800 transition-colors text-base flex items-center justify-center gap-3 cursor-pointer"
      >
        <UserMinus class="w-5 h-5 text-rose-400 stroke-[2.5]" />
        <span>Eliminar amigo</span>
      </button>
    </template>

    <AppModal
      :is-open="isRemoveModalOpen"
      :loading="removeLoading"
      :title="friend ? `¿Eliminar a ${friend.display_name}?` : '¿Eliminar amigo?'"
      description="Ya no verás su progreso en el ranking ni podrán enviarse toques mutuamente."
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
              {{ removeLoading ? 'Eliminando...' : 'Eliminar' }}
            </AppButton>
          </div>
        </div>
      </template>
    </AppModal>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { ArrowLeft, Flame, Zap, BellRing, UserMinus, UserX } from '@lucide/vue';
import AppButton from '../components/AppButton.vue';
import AppModal from '../components/AppModal.vue';
import WeeklyTracker from '../components/WeeklyTracker.vue';
import { ApiService } from '../services/api';
import { ToastService } from '../services/toast';
import { useWeeklyTracker } from '../composables/useWeeklyTracker';

const props = defineProps({
  id: { type: String, required: true },
  user: { type: Object, required: true }
});

const router = useRouter();

const { weekDays, load: loadWeeklyTracker } = useWeeklyTracker(props.user.id, props.id);

const loading = ref(true);
const friend = ref(null);
const nudged = ref(false);
const nudgeLoading = ref(false);
const isRemoveModalOpen = ref(false);
const removeLoading = ref(false);

onMounted(async () => {
  loadWeeklyTracker();
  try {
    const res = await ApiService.getFriends(props.user.id);
    if (res.success) {
      friend.value = (res.friends || []).find(f => String(f.id) === String(props.id)) || null;
      if (friend.value?.nudged_today) {
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
    const res = await ApiService.removeFriend(props.user.id, friend.value.id);
    if (res.success) {
      ToastService.success(`Eliminaste a ${friend.value.display_name} de tus amigos.`);
      router.push({ name: 'friends' });
    }
  } catch (e) {
    ToastService.error(e.message || 'Error al eliminar amigo.');
  } finally {
    removeLoading.value = false;
    isRemoveModalOpen.value = false;
  }
};
</script>
