<template>
  <AppModal
    :is-open="isOpen"
    :loading="loading"
    title="Agregar amigos"
    description="Busca a tu amigo por su nombre de usuario."
    @close="close"
  >
    <div class="space-y-3">
      <div class="flex gap-2.5">
        <div class="flex-1 relative min-w-0">
          <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 font-mono">@</span>
          <input
            v-model="username"
            type="text"
            placeholder="usuario"
            class="w-full bg-slate-950 border border-slate-700 rounded-xl pl-8 pr-4 py-3 text-base text-white font-mono placeholder:text-slate-500 focus:outline-none focus:border-brand-green"
            maxlength="30"
            @keyup.enter="addFriend"
          />
        </div>
        <AppButton
          color="blue"
          :disabled="loading || !username"
          @click="addFriend"
        >
          Agregar
        </AppButton>
      </div>
      <p v-if="statusMsg" :class="statusError ? 'text-rose-400' : 'text-emerald-400'" class="text-sm font-bold">
        {{ statusMsg }}
      </p>
    </div>
  </AppModal>
</template>

<script setup>
import { ref } from 'vue';
import AppModal from './AppModal.vue';
import AppButton from './AppButton.vue';
import { ApiService } from '../services/api';

const emit = defineEmits(['close', 'added']);

defineProps({
  isOpen: { type: Boolean, default: false }
});

const username = ref('');
const loading = ref(false);
const statusMsg = ref('');
const statusError = ref(false);

const close = () => {
  if (loading.value) return;
  username.value = '';
  statusMsg.value = '';
  emit('close');
};

const addFriend = async () => {
  if (!username.value || loading.value) return;
  document.activeElement?.blur();
  loading.value = true;
  statusMsg.value = '';

  try {
    const cleanUsername = username.value.trim().replace(/^@/, '');
    const res = await ApiService.followUser(cleanUsername);
    if (res.success) {
      statusMsg.value = res.message || '¡Ahora sigues a este usuario! 👥';
      statusError.value = false;
      emit('added');
      setTimeout(close, 900);
    }
  } catch (e) {
    statusMsg.value = e.message || 'Error al seguir usuario.';
    statusError.value = true;
  } finally {
    loading.value = false;
  }
};
</script>
