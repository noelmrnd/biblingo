<template>
  <div class="h-full w-full bg-brand-dark flex flex-col overflow-hidden selection:bg-brand-green selection:text-white relative">
    <!-- Componente Toast Flotante Global -->
    <ToastNotification />

    <!-- Splash mientras se resuelve la sesión guardada, evita el parpadeo hacia Login -->
    <div v-if="isInitializing" class="flex-1 flex items-center justify-center">
      <div class="w-10 h-10 border-4 border-slate-700 border-t-brand-green rounded-full animate-spin"></div>
    </div>

    <!-- Si no está autenticado, mostrar Login -->
    <LoginView v-else-if="!currentUser" @login-success="onLoginSuccess" />

    <!-- Aplicación Principal -->
    <template v-else>
      <!-- Tour de Bienvenida Inicial Autónomo (Onboarding) -->
      <OnboardingTour ref="tourRef" />

      <!-- Top Navbar (Flex Fixed Top con Safe Area iOS) -->
      <AppHeader :streak-count="currentUser.streak_count || 0" />

      <!-- Contenido de la vista activa (Área central a pantalla completa sin barras de scroll antiestéticas) -->
      <main 
        class="flex-1 overflow-y-auto w-full no-scrollbar transition-[padding] duration-200"
        :style="keyboardHeight > 0 ? { paddingBottom: `${keyboardHeight}px` } : undefined"
      >
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
            @user-updated="onUserUpdated"
            @open-tour="tourRef?.open"
          />
        </div>
      </main>

      <!-- Bottom Navigation Bar Gamificada (Flex Fixed Bottom) -->
      <BottomNav v-model="currentTab" />
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import AppHeader from './components/AppHeader.vue';
import BottomNav from './components/BottomNav.vue';
import OnboardingTour from './components/OnboardingTour.vue';
import { keyboardHeight } from './utils/keyboard';
import LoginView from './views/LoginView.vue';
import DashboardView from './views/DashboardView.vue';
import FriendsView from './views/FriendsView.vue';
import ProfileView from './views/ProfileView.vue';
import ToastNotification from './components/ToastNotification.vue';
import { ToastService } from './services/toast';
import { UserService } from './services/userService';
import { NotificationService } from './services/notifications';
import { useInviteFlow } from './composables/useInviteFlow';
import { useAppLifecycle } from './composables/useAppLifecycle';

const currentUser = ref(null);
const currentTab = ref('dashboard');
const tourRef = ref(null);
const isInitializing = ref(true);

const { processInvite, resolvePendingInvite } = useInviteFlow({
  getCurrentUser: () => currentUser.value,
  onFriendAdded: () => { currentTab.value = 'friends'; }
});

const { init: initAppLifecycle } = useAppLifecycle({
  getCurrentUser: () => currentUser.value,
  onDeepLinkInvite: (code) => processInvite(code, currentUser.value)
});

const onLoginSuccess = async (user) => {
  currentUser.value = user;
  await UserService.saveSession(user);
  ToastService.success(`¡Hola, ${user.display_name}! 👋`);

  // Inicializar Notificaciones Push para el usuario autenticado
  if (user && user.id) {
    NotificationService.initPushNotifications(user.id);
  }

  // Procesar invitación pendiente si existía
  await resolvePendingInvite(user);
};

const onUserUpdated = async (updatedUser) => {
  currentUser.value = { ...currentUser.value, ...updatedUser };
  await UserService.saveSession(currentUser.value);
};

const onLogout = async () => {
  await NotificationService.unregisterPushToken();
  currentUser.value = null;
  await UserService.clearSession();
  // ToastService.info('Sesión cerrada correctamente.');
};

onMounted(async () => {
  // Inicializar sesión de usuario y sincronizar timezone en segundo plano si cambió
  try {
    currentUser.value = await UserService.initSession();
  } catch (e) {
    console.warn('No se pudo restaurar la sesión guardada:', e.message);
    currentUser.value = null;
  } finally {
    isInitializing.value = false;
  }

  // Registrar listeners globales: push, retorno a primer plano, deep links
  initAppLifecycle();

  if (currentUser.value && currentUser.value.id) {
    // Procesar invitación pendiente guardada si existe sesión activa
    await resolvePendingInvite(currentUser.value);
  }
});
</script>
