<template>
  <div class="h-full w-full bg-brand-dark flex flex-col overflow-hidden selection:bg-brand-green selection:text-white relative">
    <!-- Componente Toast Flotante Global -->
    <ToastNotification />

    <!-- Si no está autenticado, mostrar Login -->
    <LoginView v-if="!currentUser" @login-success="onLoginSuccess" />

    <!-- Aplicación Principal -->
    <template v-else>
      <!-- Top Navbar (Flex Fixed Top con Safe Area iOS) -->
      <header class="flex-none z-30 bg-brand-dark/90 backdrop-blur-md border-b border-brand-border px-4 py-3 pt-safe flex items-center justify-between shadow-md">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 bg-brand-green rounded-xl flex items-center justify-center text-lg shadow-sm">
            🦉
          </div>
          <span class="font-extrabold text-xl tracking-tight text-white">Biblingo</span>
        </div>

        <!-- Racha activa en la barra superior -->
        <div class="flex items-center gap-1.5 bg-slate-900 border border-amber-500/30 px-3 py-1 rounded-full shadow-inner">
          <span class="text-lg animate-flame-pulse">🔥</span>
          <span class="font-black text-amber-400 text-base">{{ currentUser.streak_count || 0 }}</span>
        </div>
      </header>

      <!-- Contenido de la vista activa (Área central a pantalla completa sin barras de scroll antiestéticas) -->
      <main class="flex-1 overflow-y-auto w-full no-scrollbar">
        <div class="max-w-md mx-auto p-4 space-y-4">
          <DashboardView 
            v-if="currentTab === 'dashboard'" 
            :user="currentUser" 
            @user-updated="onUserUpdated" 
          />
          <FriendsView 
            v-else-if="currentTab === 'friends'" 
            :user="currentUser" 
          />
          <ProfileView 
            v-else-if="currentTab === 'profile'" 
            :user="currentUser" 
            @logout="onLogout" 
          />
        </div>
      </main>

      <!-- Bottom Navigation Bar Gamificada (Flex Fixed Bottom) -->
      <nav class="flex-none z-30 bg-slate-950/95 backdrop-blur-lg border-t border-brand-border pt-1.5 px-4 pb-safe">
        <div class="max-w-md mx-auto flex justify-between items-center gap-2">
          <button 
            @click="currentTab = 'dashboard'"
            :class="currentTab === 'dashboard' 
              ? 'text-brand-green bg-[radial-gradient(ellipse_at_center,_rgba(88,204,2,0.25)_0%,_transparent_70%)]' 
              : 'text-slate-400 hover:text-slate-200'"
            class="flex-1 py-1 px-1 flex flex-col items-center justify-center gap-0.5 rounded-2xl transition-all duration-200 cursor-pointer select-none relative overflow-hidden"
          >
            <span class="text-2xl pointer-events-none transition-transform" :class="currentTab === 'dashboard' ? 'scale-110 drop-shadow-[0_0_10px_rgba(88,204,2,0.7)]' : ''">🔥</span>
            <span class="text-base font-extrabold tracking-wide pointer-events-none">Racha</span>
          </button>

          <button 
            @click="currentTab = 'friends'"
            :class="currentTab === 'friends' 
              ? 'text-brand-blue bg-[radial-gradient(ellipse_at_center,_rgba(28,176,246,0.25)_0%,_transparent_70%)]' 
              : 'text-slate-400 hover:text-slate-200'"
            class="flex-1 py-1 px-1 flex flex-col items-center justify-center gap-0.5 rounded-2xl transition-all duration-200 cursor-pointer select-none relative overflow-hidden"
          >
            <span class="text-2xl pointer-events-none transition-transform" :class="currentTab === 'friends' ? 'scale-110 drop-shadow-[0_0_10px_rgba(28,176,246,0.7)]' : ''">👥</span>
            <span class="text-base font-extrabold tracking-wide pointer-events-none">Amigos</span>
          </button>

          <button 
            @click="currentTab = 'profile'"
            :class="currentTab === 'profile' 
              ? 'text-brand-purple bg-[radial-gradient(ellipse_at_center,_rgba(168,85,247,0.25)_0%,_transparent_70%)]' 
              : 'text-slate-400 hover:text-slate-200'"
            class="flex-1 py-1 px-1 flex flex-col items-center justify-center gap-0.5 rounded-2xl transition-all duration-200 cursor-pointer select-none relative overflow-hidden"
          >
            <span class="text-2xl pointer-events-none transition-transform" :class="currentTab === 'profile' ? 'scale-110 drop-shadow-[0_0_10px_rgba(168,85,247,0.7)]' : ''">🦉</span>
            <span class="text-base font-extrabold tracking-wide pointer-events-none">Perfil</span>
          </button>
        </div>
      </nav>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import LoginView from './views/LoginView.vue';
import DashboardView from './views/DashboardView.vue';
import FriendsView from './views/FriendsView.vue';
import ProfileView from './views/ProfileView.vue';
import ToastNotification from './components/ToastNotification.vue';
import { DeepLinkService } from './services/deepLinks';
import { ApiService } from './services/api';
import { ToastService } from './services/toast';

const currentUser = ref(null);
const currentTab = ref('dashboard');

const onLoginSuccess = (user) => {
  currentUser.value = user;
  localStorage.setItem('biblingo_user', JSON.stringify(user));
  ToastService.success(`¡Bienvenido de nuevo, ${user.display_name}! 👋`);
};

const onUserUpdated = (updatedUser) => {
  currentUser.value = { ...currentUser.value, ...updatedUser };
  localStorage.setItem('biblingo_user', JSON.stringify(currentUser.value));
};

const onLogout = () => {
  currentUser.value = null;
  localStorage.removeItem('biblingo_user');
  ToastService.info('Sesión cerrada correctamente.');
};

onMounted(() => {
  // Cargar usuario almacenado si existe
  const saved = localStorage.getItem('biblingo_user');
  if (saved) {
    try {
      currentUser.value = JSON.parse(saved);
    } catch (e) {
      localStorage.removeItem('biblingo_user');
    }
  }

  // Inicializar receptor de enlaces de invitación (Deep Links)
  DeepLinkService.initListener(async (inviteCode) => {
    if (currentUser.value && inviteCode) {
      try {
        const res = await ApiService.addFriend(currentUser.value.id, inviteCode);
        if (res.success) {
          ToastService.success(`¡Has aceptado la invitación de ${res.friend.display_name}! 👥🎉`);
          currentTab.value = 'friends';
        }
      } catch (e) {
        ToastService.error(e.message || 'Error al procesar la invitación.');
      }
    }
  });
});
</script>
