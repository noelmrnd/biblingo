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
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 bg-brand-green rounded-xl flex items-center justify-center shadow-sm">
            <BookOpen class="w-5 h-5 text-white stroke-[2.5]" />
          </div>
          <span class="font-extrabold text-xl tracking-tight text-white">Biblingo</span>
        </div>

        <!-- Racha activa en la barra superior (Emoji de fuego permitido) -->
        <div class="flex items-center gap-2 bg-slate-900 border border-amber-500/30 px-4 py-1 rounded-full shadow-inner">
          <span class="text-lg animate-flame-pulse">🔥</span>
          <span class="font-bold text-amber-400 text-xl">{{ currentUser.streak_count || 0 }}</span>
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
      <nav class="flex-none z-30 bg-slate-950/95 backdrop-blur-lg border-t border-brand-border pt-2 pb-safe-sm px-4">
        <div class="max-w-md mx-auto flex justify-between items-center">
          <button 
            @click="currentTab = 'dashboard'"
            :class="currentTab === 'dashboard' ? 'text-brand-green' : 'text-slate-400 hover:text-slate-200'"
            class="flex-1 py-1 px-1 flex flex-col items-center justify-center gap-0.5 rounded-2xl transition-colors duration-200 cursor-pointer select-none relative"
          >
            <div 
              v-if="currentTab === 'dashboard'" 
              class="absolute -inset-y-1 -inset-x-2 bg-[radial-gradient(ellipse_at_center,_rgba(88,204,2,0.35)_0%,_transparent_70%)] blur-md pointer-events-none rounded-3xl"
            ></div>
            <Flame class="w-6 h-6 stroke-[2.5] pointer-events-none relative z-10" />
            <span class="text-base font-extrabold tracking-wide pointer-events-none relative z-10">Racha</span>
          </button>

          <button 
            @click="currentTab = 'friends'"
            :class="currentTab === 'friends' ? 'text-brand-blue' : 'text-slate-400 hover:text-slate-200'"
            class="flex-1 py-1 px-1 flex flex-col items-center justify-center gap-0.5 rounded-2xl transition-colors duration-200 cursor-pointer select-none relative"
          >
            <div 
              v-if="currentTab === 'friends'" 
              class="absolute -inset-y-1 -inset-x-2 bg-[radial-gradient(ellipse_at_center,_rgba(28,176,246,0.35)_0%,_transparent_70%)] blur-md pointer-events-none rounded-3xl"
            ></div>
            <UsersRound class="w-6 h-6 stroke-[2.5] pointer-events-none relative z-10" />
            <span class="text-base font-extrabold tracking-wide pointer-events-none relative z-10">Amigos</span>
          </button>

          <button 
            @click="currentTab = 'profile'"
            :class="currentTab === 'profile' ? 'text-brand-purple' : 'text-slate-400 hover:text-slate-200'"
            class="flex-1 py-1 px-1 flex flex-col items-center justify-center gap-0.5 rounded-2xl transition-colors duration-200 cursor-pointer select-none relative"
          >
            <div 
              v-if="currentTab === 'profile'" 
              class="absolute -inset-y-1 -inset-x-2 bg-[radial-gradient(ellipse_at_center,_rgba(168,85,247,0.35)_0%,_transparent_70%)] blur-md pointer-events-none rounded-3xl"
            ></div>
            <UserRound class="w-6 h-6 stroke-[2.5] pointer-events-none relative z-10" />
            <span class="text-base font-extrabold tracking-wide pointer-events-none relative z-10">Perfil</span>
          </button>
        </div>
      </nav>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Flame, UsersRound, UserRound, BookOpen } from '@lucide/vue';
import LoginView from './views/LoginView.vue';
import DashboardView from './views/DashboardView.vue';
import FriendsView from './views/FriendsView.vue';
import ProfileView from './views/ProfileView.vue';
import ToastNotification from './components/ToastNotification.vue';
import { DeepLinkService } from './services/deepLinks';
import { ApiService } from './services/api';
import { ToastService } from './services/toast';
import { UserService } from './services/userService';

const currentUser = ref(null);
const currentTab = ref('dashboard');

const onLoginSuccess = async (user) => {
  currentUser.value = user;
  await UserService.saveSession(user);
  ToastService.success(`¡Bienvenido de nuevo, ${user.display_name}! 👋`);
};

const onUserUpdated = async (updatedUser) => {
  currentUser.value = { ...currentUser.value, ...updatedUser };
  await UserService.saveSession(currentUser.value);
};

const onLogout = async () => {
  currentUser.value = null;
  await UserService.clearSession();
  ToastService.info('Sesión cerrada correctamente.');
};

onMounted(async () => {
  // Inicializar sesión de usuario y sincronizar timezone en segundo plano si cambió
  currentUser.value = await UserService.initSession();

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
