<template>
  <Teleport to="body">
    <Transition name="modal-fade">
      <div
        v-if="isOpen"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 backdrop-blur-sm sm:p-6"
        @click.self="onClose"
      >
        <div class="modal-card bg-slate-900 sm:border border-slate-700/80 sm:rounded-3xl shadow-2xl w-full h-full sm:h-auto sm:max-w-lg sm:max-h-[85vh] pt-safe-cond pb-safe-cond flex flex-col overflow-hidden">
          <div class="p-5 flex flex-col flex-1 min-h-0">
            <div class="flex-shrink-0 pb-2 space-y-3">
              <div class="flex items-start justify-between gap-3">
                <h3 class="text-xl text-white font-bold">{{ displayName }}</h3>
                <IconButton
                  @click="onClose"
                  :haptic="false"
                  class="-mr-1 -mt-1"
                  aria-label="Cerrar"
                >
                  <X class="w-5 h-5 stroke-[2.5]" />
                </IconButton>
              </div>

              <SegmentedTabs :tabs="tabOptions" :model-value="activeTab" @update:model-value="changeTab" />
            </div>

            <div class="flex-1 overflow-y-auto min-h-0 space-y-2 pt-2 no-scrollbar overscroll-contain">
            <div v-if="isLoadingActiveTab" class="py-10 flex justify-center">
              <AppSpinner />
            </div>

            <div v-else-if="activeUsers.length === 0" class="py-10 text-center text-slate-400 space-y-2">
              <UsersRound class="w-10 h-10 text-slate-600 mx-auto stroke-[2]" />
              <p class="font-bold text-white">{{ emptyMessage }}</p>
            </div>

            <UserFollowRow
              v-for="u in activeUsers"
              :key="u.id"
              :user="u"
              :is-follow-loading="followingId === u.id"
              :follow-disabled="followingId !== null"
              @open="selectUser(u)"
              @follow="follow(u)"
            />
          </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { X, UsersRound } from '@lucide/vue';
import { ApiService } from '../services/api';
import IconButton from './IconButton.vue';
import SegmentedTabs from './SegmentedTabs.vue';
import AppSpinner from './AppSpinner.vue';
import UserFollowRow from './UserFollowRow.vue';
import { HapticsService } from '../services/haptics';
import { ToastService } from '../services/toast';

const tabOptions = [
  { id: 'followers', label: 'Seguidores', color: 'green' },
  { id: 'following', label: 'Seguidos', color: 'blue' }
];

const props = defineProps({
  isOpen: { type: Boolean, default: false },
  userId: { type: String, default: null },
  initialTab: { type: String, default: 'followers' }, // 'followers' | 'following'
  displayName: { type: String, default: '' },
  isOwnProfile: { type: Boolean, default: false }
});

const emit = defineEmits(['close', 'select-user', 'change-tab', 'followed']);

// Controlado por el padre via initialTab (sincronizado con la URL), para que cambiar
// de tab reemplace la ruta en vez de apilar entradas en el historial.
const activeTab = computed(() => props.initialTab === 'following' ? 'following' : 'followers');
const listsByTab = ref({ followers: null, following: null });
const loadingTab = ref({ followers: false, following: false });

const activeUsers = computed(() => listsByTab.value[activeTab.value] || []);
const isLoadingActiveTab = computed(() => loadingTab.value[activeTab.value]);
const followingId = ref(null);

const emptyMessage = computed(() => {
  if (activeTab.value === 'followers') {
    return props.isOwnProfile ? 'Aún no tienes seguidores.' : 'Aún no tiene seguidores.';
  }
  return props.isOwnProfile ? 'Aún no sigues a nadie.' : 'Aún no sigue a nadie.';
});

const loadTab = async (tab) => {
  // loadingTab.value[tab] tambien: sin esto, si activeTab e isOpen cambian en
  // el mismo tick (ej. abrir el modal directo en "following"), ambos watchers
  // llaman loadTab(tab) antes de que el primer await resuelva y listsByTab
  // siga en null para los dos, disparando la misma peticion dos veces.
  if (!props.userId || listsByTab.value[tab] !== null || loadingTab.value[tab]) return;
  loadingTab.value[tab] = true;
  try {
    const res = await ApiService.getFollowList(props.userId, tab);
    if (res.success) {
      listsByTab.value[tab] = res.users || [];
    }
  } catch (e) {
    console.warn(`No se pudo cargar la lista de ${tab}:`, e.message);
  } finally {
    loadingTab.value[tab] = false;
  }
};

watch(activeTab, (tab) => loadTab(tab));

watch(() => props.isOpen, (open) => {
  if (open) {
    listsByTab.value = { followers: null, following: null };
    loadTab(activeTab.value);
  }
}, { immediate: true });

const onClose = () => emit('close');

const changeTab = (tab) => {
  emit('change-tab', tab);
};

const selectUser = (u) => {
  if (u.is_self) return;
  HapticsService.light();
  emit('select-user', u.id);
};

const follow = async (u) => {
  followingId.value = u.id;
  try {
    const res = await ApiService.followUser(u.username);
    if (res.success) {
      u.is_following = true;
      ToastService.success(res.message || `¡Ahora sigues a ${u.display_name}! 👥`);
      emit('followed');
    }
  } catch (e) {
    ToastService.error(e.message || 'Error al seguir usuario.');
  } finally {
    followingId.value = null;
  }
};
</script>

<style scoped>
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.25s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}

.modal-fade-enter-active .modal-card,
.modal-fade-leave-active .modal-card {
  transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.25s ease;
}

.modal-fade-enter-from .modal-card,
.modal-fade-leave-to .modal-card {
  opacity: 0;
  transform: scale(0.96);
}
</style>
