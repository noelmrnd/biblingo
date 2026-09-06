<template>
  <Teleport to="body">
    <Transition name="modal-fade">
      <div
        v-if="isOpen"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 backdrop-blur-sm sm:p-6"
        @click.self="onClose"
      >
        <div class="modal-card bg-slate-900 sm:border border-slate-700/80 sm:rounded-3xl shadow-2xl w-full h-full sm:h-auto sm:max-w-lg sm:max-h-[85vh] pb-safe-cond flex flex-col overflow-hidden">
          <div class="p-5">
            <div class="flex-shrink-0 pb-2 space-y-3">
              <div class="flex items-start justify-between gap-3">
                <h3 class="text-xl text-white font-black">{{ displayName }}</h3>
                <button
                  @click="onClose"
                  class="p-2 -mr-1 -mt-1 text-slate-400 hover:text-white rounded-full transition-colors cursor-pointer"
                  aria-label="Cerrar"
                >
                  <X class="w-5 h-5 stroke-[2.5]" />
                </button>
              </div>

              <!-- Segmented Control Seguidores / Seguidos -->
              <div class="grid grid-cols-2 gap-2 bg-slate-950/80 p-1.5 rounded-2xl border-2 border-slate-800">
                <button
                  type="button"
                  @click="emit('change-tab', 'followers')"
                  :class="activeTab === 'followers'
                    ? 'bg-brand-card text-brand-green border-brand-green/50 shadow-md font-black'
                    : 'text-slate-400 hover:text-slate-200 border-transparent font-extrabold'"
                  class="py-2.5 px-3 rounded-xl text-base transition-all border cursor-pointer select-none active:scale-95"
                >
                  Seguidores
                </button>
                <button
                  type="button"
                  @click="emit('change-tab', 'following')"
                  :class="activeTab === 'following'
                    ? 'bg-brand-card text-brand-blue border-sky-500/50 shadow-md font-black'
                    : 'text-slate-400 hover:text-slate-200 border-transparent font-extrabold'"
                  class="py-2.5 px-3 rounded-xl text-base transition-all border cursor-pointer select-none active:scale-95"
                >
                  Seguidos
                </button>
              </div>
            </div>

            <div class="flex-1 overflow-y-auto min-h-0 space-y-2 pt-2 no-scrollbar overscroll-contain">
            <div v-if="isLoadingActiveTab" class="py-10 text-center text-slate-400 font-medium">Cargando...</div>

            <div v-else-if="activeUsers.length === 0" class="py-10 text-center text-slate-400 space-y-2">
              <UsersRound class="w-10 h-10 text-slate-600 mx-auto stroke-[2]" />
              <p class="font-bold text-white">{{ emptyMessage }}</p>
            </div>

            <button
              v-for="u in activeUsers"
              :key="u.id"
              type="button"
              :disabled="u.is_self"
              @click="selectUser(u)"
              class="w-full text-left p-3 rounded-2xl border-2 border-slate-800 bg-slate-950/60 hover:bg-slate-800/80 hover:border-slate-700 transition-all flex items-center gap-3 cursor-pointer disabled:cursor-default disabled:hover:bg-slate-950/60 disabled:hover:border-slate-800"
            >
              <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-brand-blue to-sky-400 flex items-center justify-center shrink-0 text-white font-black text-base">
                {{ (u.display_name || '?').charAt(0).toUpperCase() }}
              </div>
              <div class="min-w-0 flex-1">
                <h4 class="font-extrabold text-white text-base truncate flex items-center gap-2">
                  <span class="truncate">{{ u.display_name }}</span>
                  <span v-if="u.is_self" class="text-xs bg-brand-green/20 text-brand-green px-2 py-0.5 rounded-md font-black flex-none">TÚ</span>
                </h4>
                <p class="text-slate-400 text-sm font-medium flex items-center gap-1.5">
                  <Flame class="w-3.5 h-3.5 text-amber-400 stroke-[2.5]" />
                  <span>{{ u.streak_count }} días de racha</span>
                </p>
              </div>
              <span v-if="!u.is_self && u.is_following" class="text-xs text-slate-500 font-bold flex-none">Sigues</span>
            </button>
          </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { X, UsersRound, Flame } from '@lucide/vue';
import { ApiService } from '../services/api';

const props = defineProps({
  isOpen: { type: Boolean, default: false },
  userId: { type: String, default: null },
  initialTab: { type: String, default: 'followers' }, // 'followers' | 'following'
  displayName: { type: String, default: '' },
  isOwnProfile: { type: Boolean, default: false }
});

const emit = defineEmits(['close', 'select-user', 'change-tab']);

// Controlado por el padre via initialTab (sincronizado con la URL), para que cambiar
// de tab reemplace la ruta en vez de apilar entradas en el historial.
const activeTab = computed(() => props.initialTab === 'following' ? 'following' : 'followers');
const listsByTab = ref({ followers: null, following: null });
const loadingTab = ref({ followers: false, following: false });

const activeUsers = computed(() => listsByTab.value[activeTab.value] || []);
const isLoadingActiveTab = computed(() => loadingTab.value[activeTab.value]);

const emptyMessage = computed(() => {
  if (activeTab.value === 'followers') {
    return props.isOwnProfile ? 'Aún no tienes seguidores.' : 'Aún no tiene seguidores.';
  }
  return props.isOwnProfile ? 'Aún no sigues a nadie.' : 'Aún no sigue a nadie.';
});

const loadTab = async (tab) => {
  if (!props.userId || listsByTab.value[tab] !== null) return;
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

const selectUser = (u) => {
  if (u.is_self) return;
  emit('select-user', u.id);
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
