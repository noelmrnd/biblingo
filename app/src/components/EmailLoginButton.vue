<template>
  <button
    type="button"
    @click="showModal = true"
    :disabled="loading"
    class="w-full bg-slate-800 hover:bg-slate-700 text-white font-extrabold py-4 px-6 rounded-2xl transition-all duration-150 shadow-md active:shadow-sm active:scale-[0.97] active:brightness-95 flex items-center justify-center gap-3 text-base cursor-pointer disabled:opacity-50"
  >
    <Mail class="w-5 h-5" />
    <span>Continuar con correo</span>
  </button>

  <AppModal
    :is-open="showModal"
    title="Continuar con correo"
    @close="showModal = false"
  >
    <div class="w-full space-y-3">
      <AppTextInput
        v-model="emailValue"
        type="email"
        :icon="Mail"
        autocomplete="username"
        placeholder="Correo"
        @keyup.enter="login"
      />
      <AppTextInput
        v-model="passwordValue"
        type="password"
        :icon="Lock"
        autocomplete="current-password"
        placeholder="Contraseña"
        @keyup.enter="login"
      />
      <p v-if="errorMsg" class="text-rose-400 text-sm font-bold bg-rose-950/50 border border-rose-800 p-3 rounded-xl w-full">
        {{ errorMsg }}
      </p>
    </div>
    <template #footer>
      <AppButton
        color="green"
        size="sm"
        class="w-full"
        :disabled="loading"
        text="Entrar con correo"
        @click="login"
      />
    </template>
  </AppModal>
</template>

<script setup>
import { ref } from 'vue';
import { Mail, Lock } from '@lucide/vue';
import AppButton from './AppButton.vue';
import AppModal from './AppModal.vue';
import AppTextInput from './AppTextInput.vue';
import { AuthService } from '@/services/authService';

const emit = defineEmits(['login-success']);

const showModal = ref(false);
const loading = ref(false);
const errorMsg = ref('');
const emailValue = ref('');
const passwordValue = ref('');

const login = async () => {
  loading.value = true;
  errorMsg.value = '';
  try {
    const res = await AuthService.loginWithEmail(emailValue.value, passwordValue.value);
    if (res && res.user) {
      emit('login-success', res.user, res.token);
    }
  } catch (err) {
    errorMsg.value = 'Error al iniciar sesión. Inténtalo de nuevo.';
  } finally {
    loading.value = false;
  }
};
</script>
