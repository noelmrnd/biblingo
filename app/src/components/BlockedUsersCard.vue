<template>
  <ExpandableCard
    v-model="isExpanded"
    title="Usuarios bloqueados"
    description="Administra a quién bloqueaste"
    icon-bg-class="bg-rose-500/10 border-rose-500/30"
    icon-color-class="text-rose-400"
    :icon="ShieldOff"
    @update:model-value="onExpand"
  >
    <div v-if="loading" class="py-4 flex justify-center">
      <AppSpinner size="sm" />
    </div>

    <p v-else-if="blockedUsers.length === 0" class="text-base text-slate-400 font-medium py-1">
      No has bloqueado a nadie.
    </p>

    <div v-else class="space-y-1">
      <div v-for="u in blockedUsers" :key="u.id" class="flex items-center justify-between gap-3 py-2.5 border-b border-slate-800/70 last:border-0">
        <div class="min-w-0">
          <p class="text-base font-semibold text-white truncate">{{ u.display_name }}</p>
          <p class="text-sm text-slate-400 font-medium">@{{ u.username }}</p>
        </div>
        <button
          type="button"
          :disabled="unblockAction.loading.value"
          @click="unblockUser(u)"
          class="text-sm font-semibold text-sky-400 hover:text-sky-300 py-1 px-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed shrink-0"
        >
          Desbloquear
        </button>
      </div>
    </div>
  </ExpandableCard>
</template>

<script setup>
import { ref } from 'vue';
import { ShieldOff } from '@lucide/vue';
import ExpandableCard from './ExpandableCard.vue';
import AppSpinner from './AppSpinner.vue';
import { ApiService } from '@/services/api';
import { useAsyncAction } from '@/composables/useAsyncAction';
import { ToastService } from '@/services/toast';

const isExpanded = ref(false);
const loading = ref(false);
const blockedUsers = ref([]);
const loaded = ref(false);
const unblockAction = useAsyncAction();

// Carga perezosa: solo al expandir la tarjeta por primera vez, no en cada
// visita a Ajustes (lista rara vez cambia y no es informacion critica).
const onExpand = async (expanded) => {
  if (!expanded || loaded.value) return;
  loading.value = true;
  try {
    const res = await ApiService.getBlockedUsers();
    if (res.success) {
      blockedUsers.value = res.blocked_users;
      loaded.value = true;
    }
  } catch (e) {
    console.warn('No se pudo cargar la lista de bloqueados:', e.message);
  } finally {
    loading.value = false;
  }
};

const unblockUser = async (u) => {
  const res = await unblockAction.run(() => ApiService.unblockUser(u.id), {
    successMsg: `Desbloqueaste a ${u.display_name}.`,
    errorMsg: 'No se pudo desbloquear a este usuario.'
  });
  if (res === undefined) return;
  blockedUsers.value = blockedUsers.value.filter((b) => b.id !== u.id);
};
</script>
