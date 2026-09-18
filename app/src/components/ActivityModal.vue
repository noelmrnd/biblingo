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
            <div class="flex-shrink-0 pb-2 flex items-start justify-between gap-3">
              <h3 class="text-xl text-white font-bold">Actividad reciente</h3>
              <IconButton @click="onClose" :haptic="false" class="-mr-1 -mt-1" aria-label="Cerrar">
                <X class="w-5 h-5 stroke-[2.5]" />
              </IconButton>
            </div>

            <div class="flex-1 overflow-y-auto min-h-0 space-y-2 pt-2 no-scrollbar overscroll-contain">
              <div v-if="isLoading" class="py-10 flex justify-center">
                <AppSpinner />
              </div>

              <div v-else-if="activity.length === 0" class="py-10 text-center text-slate-400 space-y-2">
                <Bell class="w-10 h-10 text-slate-600 mx-auto stroke-[2]" />
                <p class="font-bold text-white">Sin actividad reciente.</p>
              </div>

              <button
                v-for="a in activity"
                :key="`${a.type}-${a.id}`"
                type="button"
                @click="openProfile(a.id)"
                class="w-full flex items-center gap-3 p-3 rounded-2xl bg-slate-800/60 hover:bg-slate-800 transition-colors cursor-pointer text-left"
              >
                <div
                  :class="activityIconBgClass(a.type)"
                  class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0"
                >
                  <component
                    :is="isNudge(a.type) ? BellRing : UserRoundPlus"
                    :class="activityIconTextClass(a.type)"
                    class="w-5 h-5 stroke-[2.5]"
                  />
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-white font-semibold truncate">
                    {{ activityLabel(a) }}
                  </p>
                  <p class="text-slate-400 text-sm">{{ relativeLabel(a.created_at) }}</p>
                </div>
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { watch } from 'vue';
import { useRouter } from 'vue-router';
import { X, Bell, UserRoundPlus, BellRing } from '@lucide/vue';
import IconButton from './IconButton.vue';
import AppSpinner from './AppSpinner.vue';
import { useActivity } from '@/composables/useActivity';
import { HapticsService } from '@/services/haptics';

const props = defineProps({
  isOpen: { type: Boolean, default: false }
});

const emit = defineEmits(['close']);

const { activity, isLoading, refresh, markChecked } = useActivity();
const router = useRouter();

watch(() => props.isOpen, (open) => {
  if (open) {
    refresh({ force: true });
    markChecked();
  }
});

const onClose = () => emit('close');

const isNudge = (type) => type === 'nudge_received' || type === 'nudge_sent';
const isReceived = (type) => type === 'follow_received' || type === 'nudge_received';

const activityIconBgClass = (type) => {
  if (isNudge(type)) return isReceived(type) ? 'bg-brand-nudge/15' : 'bg-brand-nudge-dark/15';
  return isReceived(type) ? 'bg-brand-green/15' : 'bg-brand-blue/15';
};

const activityIconTextClass = (type) => {
  if (isNudge(type)) return isReceived(type) ? 'text-brand-nudge' : 'text-brand-nudge-dark';
  return isReceived(type) ? 'text-brand-green' : 'text-brand-blue';
};

const activityLabel = (a) => {
  switch (a.type) {
    case 'follow_received': return `${a.display_name} empezó a seguirte`;
    case 'follow_sent': return `Empezaste a seguir a ${a.display_name}`;
    case 'nudge_received': return `${a.display_name} te dio un toque`;
    case 'nudge_sent': return `Le diste un toque a ${a.display_name}`;
    default: return '';
  }
};

const openProfile = (id) => {
  HapticsService.light();
  // No cierra el modal (isOpen se mantiene): al volver del perfil el modal
  // reaparece tal como quedo, igual que FollowListModal/AddFriendModal.
  router.push({ name: 'friend-profile', params: { id } });
};

// created_at viene como 'YYYY-MM-DD HH:MM:SS' (UTC, formato MySQL) del backend.
const relativeLabel = (createdAt) => {
  const date = new Date(createdAt.replace(' ', 'T') + 'Z');
  const diffMin = Math.round((Date.now() - date.getTime()) / 60000);
  if (diffMin < 1) return 'Ahora mismo';
  if (diffMin < 60) return `Hace ${diffMin} min`;
  const diffHour = Math.round(diffMin / 60);
  if (diffHour < 24) return `Hace ${diffHour} h`;
  const diffDay = Math.round(diffHour / 24);
  if (diffDay === 1) return 'Ayer';
  return `Hace ${diffDay} días`;
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
