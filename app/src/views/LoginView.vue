<template>
  <div class="min-h-screen bg-brand-dark flex flex-col items-center justify-center p-6 text-center relative overflow-hidden">
    <!-- Emojis y destellos flotantes de fondo -->
    <div class="absolute -top-12 -left-12 w-64 h-64 bg-brand-green/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-12 -right-12 w-64 h-64 bg-brand-flame/10 rounded-full blur-3xl pointer-events-none"></div>

    <div class="max-w-md w-full flex flex-col items-center z-10 space-y-8">
      <!-- Logo y Mascota -->
      <div class="relative group cursor-pointer">
        <div class="w-32 h-32 bg-gradient-to-tr from-brand-green to-emerald-400 rounded-3xl flex items-center justify-center shadow-2xl shadow-emerald-500/20 border-4 border-slate-800 transform group-hover:scale-105 transition-transform duration-300">
          <span class="text-6xl select-none animate-bounce-short">🦉📖</span>
        </div>
        <div class="absolute -bottom-2 -right-2 bg-brand-flame px-3 py-1 rounded-full text-xs font-black text-white shadow-lg flex items-center gap-1 border-2 border-slate-900">
          <span>🔥</span> Racha
        </div>
      </div>

      <!-- Título y Eslogan -->
      <div class="space-y-2">
        <h1 class="text-4xl font-extrabold tracking-tight bg-gradient-to-r from-white via-slate-100 to-slate-400 bg-clip-text text-transparent">
          Biblingo
        </h1>
        <p class="text-slate-400 text-lg font-medium">
          Construye un hábito diario de lectura que dure para siempre.
        </p>
      </div>

      <!-- Botones de Autenticación -->
      <div class="w-full space-y-3.5 pt-4">
        <!-- Apple Sign In (iOS / Recomendado en Apple) -->
        <button 
          @click="loginApple"
          :disabled="loading"
          class="w-full bg-white text-black font-extrabold py-4 px-6 rounded-2xl border-b-4 border-slate-300 active:border-b-0 active:translate-y-1 transition-all shadow-lg flex items-center justify-center gap-3 text-base cursor-pointer disabled:opacity-50"
        >
          <svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" width="24" height="28" viewBox="0 0 814 1000">
            <path d="M788.1 340.9c-5.8 4.5-108.2 62.2-108.2 190.5 0 148.4 130.3 200.9 134.2 202.2-.6 3.2-20.7 71.9-68.7 141.9-42.8 61.6-87.5 123.1-155.5 123.1s-85.5-39.5-164-39.5c-76.5 0-103.7 40.8-165.9 40.8s-105.6-57-155.5-127C46.7 790.7 0 663 0 541.8c0-194.4 126.4-297.5 250.8-297.5 66.1 0 121.2 43.4 162.7 43.4 39.5 0 101.1-46 176.3-46 28.5 0 130.9 2.6 198.3 99.2zm-234-181.5c31.1-36.9 53.1-88.1 53.1-139.3 0-7.1-.6-14.3-1.9-20.1-50.6 1.9-110.8 33.7-147.1 75.8-28.5 32.4-55.1 83.6-55.1 135.5 0 7.8 1.3 15.6 1.9 18.1 3.2.6 8.4 1.3 13.6 1.3 45.4 0 102.5-30.4 135.5-71.3z"/>
          </svg>
          <span>Continuar con Apple</span>
        </button>

        <!-- Google Sign In (Android / Web) -->
        <button 
          @click="loginGoogle"
          :disabled="loading"
          class="w-full bg-slate-800 hover:bg-slate-700 text-white font-extrabold py-4 px-6 rounded-2xl border-b-4 border-slate-900 active:border-b-0 active:translate-y-1 transition-all shadow-lg flex items-center justify-center gap-3 text-base cursor-pointer disabled:opacity-50"
        >
          <svg class="w-5 h-5" viewBox="0 0 24 24">
            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
          </svg>
          <span>Continuar con Google</span>
        </button>

        <!-- Panel de Desarrollo Local (Vite Dev / Localhost) -->
        <div v-if="IS_DEV" class="pt-4 border-t border-slate-800 space-y-3">
          <div class="text-xs font-black text-amber-400 uppercase tracking-wider flex items-center justify-center gap-1">
            <span>🛠️</span> Entorno de desarrollo
          </div>
          <div class="flex gap-3">
            <input 
              v-model="devName"
              type="text" 
              placeholder="Nombre de usuario dev"
              class="flex-1 bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-base text-white focus:outline-none focus:border-brand-green"
              @keyup.enter="loginDev"
            />
            <button 
              @click="loginDev"
              :disabled="loading"
              class="btn-3d-green text-sm py-2.5 px-4 whitespace-nowrap"
            >
              Entrar dev
            </button>
          </div>
        </div>
      </div>

      <p v-if="errorMsg" class="text-rose-400 text-sm font-bold bg-rose-950/50 border border-rose-800 p-3 rounded-xl w-full">
        {{ errorMsg }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { AuthService } from '../services/authService';
import { IS_DEV } from '../constants';

const emit = defineEmits(['login-success']);
const loading = ref(false);
const errorMsg = ref('');
const devName = ref('Lector Dev');

const handleAuth = async (authPromise) => {
  loading.value = true;
  errorMsg.value = '';
  try {
    const res = await authPromise;
    if (res && res.user) {
      emit('login-success', res.user);
    }
  } catch (err) {
    errorMsg.value = err.message || 'Error al iniciar sesión. Inténtalo de nuevo.';
  } finally {
    loading.value = false;
  }
};

const loginApple = () => handleAuth(AuthService.loginWithApple());
const loginGoogle = () => handleAuth(AuthService.loginWithGoogle());
const loginDev = () => handleAuth(AuthService.devLogin(devName.value));
</script>
