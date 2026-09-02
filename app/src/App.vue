<template>
  <div class="h-full w-full bg-brand-dark flex flex-col overflow-hidden selection:bg-brand-green selection:text-white">
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
          <span class="text-base animate-flame-pulse">🔥</span>
          <span class="font-black text-amber-400 text-sm">{{ currentUser.streak_count || 0 }}</span>
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
      <nav class="flex-none z-30 bg-slate-950/95 backdrop-blur-lg border-t border-brand-border pt-2.5 px-4 pb-safe">
        <div class="max-w-md mx-auto flex justify-between items-center gap-2">
          <button 
            @click="currentTab = 'dashboard'"
            :class="currentTab === 'dashboard' ? 'text-brand-green bg-brand-green/15 border-brand-green/30' : 'text-slate-400 border-transparent hover:text-slate-200 hover:bg-slate-900/60'"
            class="flex-1 py-2.5 px-2 flex flex-col items-center justify-center gap-1 rounded-2xl border transition-colors duration-150 cursor-pointer select-none"
          >
            <span class="text-2xl pointer-events-none">🔥</span>
            <span class="text-xs font-extrabold tracking-wide pointer-events-none">Racha</span>
          </button>

          <button 
            @click="currentTab = 'friends'"
            :class="currentTab === 'friends' ? 'text-brand-blue bg-brand-blue/15 border-brand-blue/30' : 'text-slate-400 border-transparent hover:text-slate-200 hover:bg-slate-900/60'"
            class="flex-1 py-2.5 px-2 flex flex-col items-center justify-center gap-1 rounded-2xl border transition-colors duration-150 cursor-pointer select-none"
          >
            <span class="text-2xl pointer-events-none">👥</span>
            <span class="text-xs font-extrabold tracking-wide pointer-events-none">Amigos</span>
          </button>

          <button 
            @click="currentTab = 'profile'"
            :class="currentTab === 'profile' ? 'text-brand-purple bg-brand-purple/15 border-brand-purple/30' : 'text-slate-400 border-transparent hover:text-slate-200 hover:bg-slate-900/60'"
            class="flex-1 py-2.5 px-2 flex flex-col items-center justify-center gap-1 rounded-2xl border transition-colors duration-150 cursor-pointer select-none"
          >
            <span class="text-2xl pointer-events-none">🦉</span>
            <span class="text-xs font-extrabold tracking-wide pointer-events-none">Perfil</span>
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
import { DeepLinkService } from './services/deepLinks';
import { ApiService } from './services/api';

const currentUser = ref(null);
const currentTab = ref('dashboard');

const onLoginSuccess = (user) => {
  currentUser.value = user;
  localStorage.setItem('biblingo_user', JSON.stringify(user));
};

const onUserUpdated = (updatedUser) => {
  currentUser.value = { ...currentUser.value, ...updatedUser };
  localStorage.setItem('biblingo_user', JSON.stringify(currentUser.value));
};

const onLogout = () => {
  currentUser.value = null;
  localStorage.removeItem('biblingo_user');
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
          alert(`¡Has aceptado la invitación de ${res.friend.display_name}! 👥🎉`);
          currentTab.value = 'friends';
        }
      } catch (e) {
        console.warn('Error al procesar deep link:', e.message);
      }
    }
  });
});
</script>
