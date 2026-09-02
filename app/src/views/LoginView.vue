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
          <svg class="w-5 h-5 fill-current" viewBox="0 0 170 170">
            <path d="M150.37 130.25c-2.45 5.66-5.35 10.87-8.71 15.66-4.58 6.53-8.33 11.05-11.22 13.56-4.48 4.12-9.28 6.23-14.42 6.35-3.69 0-8.14-1.05-13.32-3.18-5.19-2.12-9.97-3.17-14.34-3.17-4.58 0-9.49 1.05-14.75 3.17-5.26 2.13-9.5 3.24-12.74 3.35-4.33.13-9.13-1.9-14.38-6.08-3.32-2.73-7.27-7.46-11.85-14.19-6.3-9.28-11.3-19.82-15.01-31.62-3.71-11.8-5.56-23.03-5.56-33.68 0-14.34 3.59-26.17 10.77-35.49 7.18-9.32 16.35-14.07 27.5-14.25 4.8 0 10.08 1.25 15.86 3.75 5.78 2.5 9.77 3.75 11.97 3.75 1.83 0 5.86-1.22 12.09-3.67 6.23-2.45 11.47-3.6 15.72-3.46 9.87.5 18.25 4.38 25.14 11.64-8.8 5.34-13.11 12.82-12.93 22.44.18 7.57 3.01 14.12 8.48 19.65 5.47 5.53 12.08 8.76 19.83 9.69-2.22 6.64-5.15 13.06-8.8 19.26zM119.22 31.86c0-6.73 2.45-13.2 7.35-19.41 4.9-6.21 11.13-10.02 18.69-11.44.27 1.48.41 2.89.41 4.22 0 6.64-2.51 13.15-7.53 19.53-5.02 6.38-11.18 10.22-18.48 11.52-.18-1.42-.44-2.89-.44-4.42z"/>
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

        <!-- Acceso Rápido / Demo -->
        <button 
          @click="loginDemo"
          :disabled="loading"
          class="btn-3d-green w-full text-base"
        >
          <span>🚀 Entrar como Invitado</span>
        </button>
      </div>

      <p v-if="errorMsg" class="text-rose-400 text-sm font-semibold bg-rose-950/50 border border-rose-800 p-3 rounded-xl w-full">
        {{ errorMsg }}
      </p>

      <p class="text-slate-500 text-xs max-w-xs">
        Al continuar, aceptas formar parte del club de hábito diario de lectura Biblingo. Sin contraseñas molestos.
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { AuthService } from '../services/authService';

const emit = defineEmits(['login-success']);
const loading = ref(false);
const errorMsg = ref('');

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
const loginDemo = () => handleAuth(AuthService.demoLogin('Lector Invitado'));
</script>
