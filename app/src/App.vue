<template>
  <div class="h-full w-full bg-brand-dark flex flex-col overflow-hidden selection:bg-brand-green selection:text-white relative">
    <!-- Componente Toast Flotante Global -->
    <ToastNotification />

    <!-- Tour de Bienvenida Inicial (Onboarding) -->
    <OnboardingTour 
      :is-open="showTour" 
      @close="onTourClose" 
      @complete="onTourComplete" 
    />

    <!-- Si no está autenticado, mostrar Login -->
    <LoginView v-if="!currentUser" @login-success="onLoginSuccess" />

    <!-- Aplicación Principal -->
    <template v-else>
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
            @open-tour="openTour"
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
import { DeepLinkService } from './services/deepLinks';
import { ApiService } from './services/api';
import { ToastService } from './services/toast';
import { UserService } from './services/userService';
import { NotificationService } from './services/notifications';
import { StorageService } from './services/storage';

const currentUser = ref(null);
const currentTab = ref('dashboard');
const showTour = ref(false);
const PENDING_INVITE_KEY = 'pending_invite_code';
const TOUR_SEEN_KEY = 'has_seen_onboarding_tour';
let lastProcessedCode = null;
let lastProcessedTime = 0;

const checkTourStatus = async () => {
  const hasSeenTour = await StorageService.get(TOUR_SEEN_KEY);
  if (!hasSeenTour) {
    setTimeout(() => {
      showTour.value = true;
    }, 500);
  }
};

const onTourComplete = async () => {
  showTour.value = false;
  await StorageService.set(TOUR_SEEN_KEY, true);
  ToastService.success('¡Tour completado! Que disfrutes tu lectura diaria. 📖✨');
};

const onTourClose = async () => {
  showTour.value = false;
  await StorageService.set(TOUR_SEEN_KEY, true);
};

const openTour = () => {
  showTour.value = true;
};

const processInvite = async (inviteCode, user = currentUser.value) => {
  if (!inviteCode) return;

  // Prevenir reprocesamiento duplicado inmediato (ej: cold-start + listener)
  const now = Date.now();
  if (inviteCode === lastProcessedCode && now - lastProcessedTime < 3000) {
    return;
  }
  lastProcessedCode = inviteCode;
  lastProcessedTime = now;

  // Si no hay sesión iniciada, almacenar para procesar después del login/registro
  if (!user || !user.id) {
    await StorageService.set(PENDING_INVITE_KEY, inviteCode);
    ToastService.info(`Invitación (${inviteCode}) guardada. Inicia sesión para conectar con tu amigo.`);
    return;
  }

  try {
    const res = await ApiService.addFriend(user.id, inviteCode);
    if (res.success) {
      ToastService.success(`¡Has aceptado la invitación de ${res.friend.display_name}! 👥🎉`);
      currentTab.value = 'friends';
    }
  } catch (e) {
    ToastService.error(e.message || 'Error al procesar la invitación.');
  } finally {
    await StorageService.remove(PENDING_INVITE_KEY);
  }
};

const onLoginSuccess = async (user) => {
  currentUser.value = user;
  await UserService.saveSession(user);
  ToastService.success(`¡Hola, ${user.display_name}! 👋`);

  // Inicializar Notificaciones Push para el usuario autenticado
  if (user && user.id) {
    NotificationService.initPushNotifications(user.id);
  }

  // Procesar invitación pendiente si existía
  const pendingInvite = await StorageService.get(PENDING_INVITE_KEY);
  if (pendingInvite) {
    await processInvite(pendingInvite, user);
  }

  // Comprobar si corresponde mostrar el tour inicial
  await checkTourStatus();
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
  currentUser.value = await UserService.initSession();

  if (currentUser.value && currentUser.value.id) {
    NotificationService.initPushNotifications(currentUser.value.id);

    // Procesar invitación pendiente guardada si existe sesión activa
    const pendingInvite = await StorageService.get(PENDING_INVITE_KEY);
    if (pendingInvite) {
      await processInvite(pendingInvite, currentUser.value);
    }

    // Comprobar tour para usuario activo
    await checkTourStatus();
  }

  // Inicializar receptor de enlaces de invitación (Deep Links & Cold Start)
  DeepLinkService.initListener(async (inviteCode) => {
    await processInvite(inviteCode, currentUser.value);
  });
});
</script>
