<template>
  <div class="min-h-screen bg-brand-dark flex flex-col justify-between selection:bg-brand-green selection:text-white">
    <!-- Si no está autenticado, mostrar Login -->
    <LoginView v-if="!currentUser" @login-success="onLoginSuccess" />

    <!-- Aplicación Principal -->
    <template v-else>
      <!-- Top Navbar -->
      <header class="sticky top-0 z-30 bg-brand-dark/90 backdrop-blur-md border-b border-brand-border px-4 py-3 flex items-center justify-between shadow-md">
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

      <!-- Contenido de la vista activa -->
      <main class="flex-1 p-4 max-w-md mx-auto w-full">
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
      </main>

      <!-- Bottom Navigation Bar Gamificada -->
      <nav class="fixed bottom-0 left-0 right-0 z-30 bg-slate-950/95 backdrop-blur-lg border-t border-brand-border py-2 px-6">
        <div class="max-w-md mx-auto flex justify-around items-center">
          <button 
            @click="currentTab = 'dashboard'"
            :class="currentTab === 'dashboard' ? 'text-brand-green scale-105' : 'text-slate-400 hover:text-slate-200'"
            class="flex flex-col items-center gap-1 transition-all duration-200 cursor-pointer"
          >
            <span class="text-2xl">🔥</span>
            <span class="text-[11px] font-extrabold tracking-wide">Racha</span>
          </button>

          <button 
            @click="currentTab = 'friends'"
            :class="currentTab === 'friends' ? 'text-brand-blue scale-105' : 'text-slate-400 hover:text-slate-200'"
            class="flex flex-col items-center gap-1 transition-all duration-200 cursor-pointer"
          >
            <span class="text-2xl">👥</span>
            <span class="text-[11px] font-extrabold tracking-wide">Amigos</span>
          </button>

          <button 
            @click="currentTab = 'profile'"
            :class="currentTab === 'profile' ? 'text-brand-purple scale-105' : 'text-slate-400 hover:text-slate-200'"
            class="flex flex-col items-center gap-1 transition-all duration-200 cursor-pointer"
          >
            <span class="text-2xl">🦉</span>
            <span class="text-[11px] font-extrabold tracking-wide">Perfil</span>
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
