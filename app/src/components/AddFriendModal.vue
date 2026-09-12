<template>
  <Teleport to="body">
    <Transition name="modal-fade">
      <div
        v-if="isOpen"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 backdrop-blur-sm sm:p-6"
        @click.self="close"
      >
        <div class="modal-card bg-slate-900 sm:border border-slate-700/80 sm:rounded-3xl shadow-2xl w-full h-full sm:h-auto sm:max-w-lg sm:max-h-[85vh] pt-safe-cond pb-safe-cond flex flex-col overflow-hidden">
          <div class="p-5 flex flex-col flex-1 min-h-0">
            <div class="flex-shrink-0 pb-3 space-y-3">
              <div class="flex items-start justify-between gap-3">
                <h3 class="text-xl text-white font-bold">Agregar amigos</h3>
                <IconButton @click="close" :haptic="false" class="-mr-1 -mt-1" aria-label="Cerrar">
                  <X class="w-5 h-5 stroke-[2.5]" />
                </IconButton>
              </div>

              <div class="relative">
                <Search class="w-5 h-5 text-slate-500 absolute left-4 top-1/2 -translate-y-1/2" />
                <input
                  v-model="query"
                  type="text"
                  placeholder="Buscar por nombre o usuario"
                  class="w-full bg-slate-950 border border-slate-700 rounded-xl pl-11 pr-4 py-3 text-base text-white placeholder:text-slate-500 focus:outline-none focus:border-brand-green"
                  autofocus
                />
              </div>
            </div>

            <div class="flex-1 overflow-y-auto min-h-0 space-y-2 pt-2 no-scrollbar overscroll-contain">
              <div v-if="query.trim().length < MIN_QUERY_LENGTH" class="py-10 text-center text-slate-400 space-y-2">
                <UsersRound class="w-10 h-10 text-slate-600 mx-auto stroke-[2]" />
                <p class="font-bold text-white">Busca a tus amigos</p>
                <p class="text-sm">Escribe al menos {{ MIN_QUERY_LENGTH }} letras de su nombre o usuario.</p>
              </div>

              <div v-else-if="isLoading" class="py-10 flex justify-center">
                <AppSpinner />
              </div>

              <div v-else-if="results.length === 0" class="py-10 text-center text-slate-400 space-y-2">
                <UsersRound class="w-10 h-10 text-slate-600 mx-auto stroke-[2]" />
                <p class="font-bold text-white">Sin resultados</p>
                <p class="text-sm">No encontramos a nadie con "{{ query.trim() }}".</p>
              </div>

              <UserFollowRow
                v-for="u in results"
                :key="u.id"
                :user="u"
                :is-follow-loading="followingId === u.id"
                :follow-disabled="followingId !== null"
                @open="openProfile(u)"
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
import { ref, watch } from 'vue';
import { X, Search, UsersRound } from '@lucide/vue';
import IconButton from './IconButton.vue';
import AppSpinner from './AppSpinner.vue';
import UserFollowRow from './UserFollowRow.vue';
import { ApiService } from '@/services/api';
import { ToastService } from '@/services/toast';

const MIN_QUERY_LENGTH = 2;
const SEARCH_DEBOUNCE_MS = 350;

const emit = defineEmits(['close', 'open-profile']);

defineProps({
  isOpen: { type: Boolean, default: false }
});

const query = ref('');
const results = ref([]);
const isLoading = ref(false);
const followingId = ref(null);
const hadChanges = ref(false);

let debounceTimer = null;
// Evita que una respuesta vieja (de un query ya abandonado) pise los
// resultados del query que se esta viendo ahora.
let requestSeq = 0;

const search = async () => {
  const q = query.value.trim();
  if (q.length < MIN_QUERY_LENGTH) {
    results.value = [];
    return;
  }
  const seq = ++requestSeq;
  isLoading.value = true;
  try {
    const res = await ApiService.searchUsers(q);
    if (seq !== requestSeq) return;
    results.value = res.success ? (res.users || []) : [];
  } catch (e) {
    if (seq !== requestSeq) return;
    console.warn('Error al buscar usuarios:', e.message);
  } finally {
    if (seq === requestSeq) isLoading.value = false;
  }
};

watch(query, () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(search, SEARCH_DEBOUNCE_MS);
});

const follow = async (u) => {
  followingId.value = u.id;
  try {
    const res = await ApiService.followUser(u.username);
    if (res.success) {
      u.is_following = true;
      hadChanges.value = true;
      ToastService.success(res.message || `¡Ahora sigues a ${u.display_name}! 👥`);
    }
  } catch (e) {
    ToastService.error(e.message || 'Error al seguir usuario.');
  } finally {
    followingId.value = null;
  }
};

// No cierra ni resetea el estado de busqueda: el padre solo oculta el modal
// (isOpen=false) antes de navegar y lo reabre al volver, para que la busqueda
// siga ahi en vez de tener que repetirla.
const openProfile = (u) => {
  emit('open-profile', u.id);
};

const close = () => {
  if (followingId.value !== null) return;
  query.value = '';
  results.value = [];
  emit('close', hadChanges.value);
  hadChanges.value = false;
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
