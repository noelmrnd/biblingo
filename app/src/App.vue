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
      <AppHeader :streak-count="currentUser.is_streak_lost ? 0 : (currentUser.streak_count || 0)" />

      <!-- Contenido de la vista activa (Área central a pantalla completa sin barras de scroll antiestéticas) -->
      <main
        class="flex-1 overflow-y-auto w-full no-scrollbar transition-[padding] duration-200"
        :style="keyboardHeight > 0 ? { paddingBottom: `${keyboardHeight}px` } : undefined"
      >
        <div class="max-w-md mx-auto p-4 space-y-4">
          <router-view
            :user="currentUser"
            @user-updated="onUserUpdated"
            @logout="onLogout"
            @open-tour="tourRef?.open"
          />
        </div>
      </main>

      <!-- Bottom Navigation Bar Gamificada (Flex Fixed Bottom) -->
      <BottomNav />
    </template>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import AppHeader from './components/AppHeader.vue';
import BottomNav from './components/BottomNav.vue';
import OnboardingTour from './components/OnboardingTour.vue';
import { keyboardHeight } from './utils/keyboard';
import LoginView from './views/LoginView.vue';
import ToastNotification from './components/ToastNotification.vue';
import { ToastService } from './services/toast';
import { UserService } from './services/userService';
import { NotificationService } from './services/notifications';
import { setUnauthorizedHandler } from './services/api';
import { useInviteFlow } from './composables/useInviteFlow';
import { useAppLifecycle } from './composables/useAppLifecycle';
import { useCurrentUser } from './composables/useCurrentUser';

const router = useRouter();
const { user: currentUser, clearUser } = useCurrentUser();
const tourRef = ref(null);
const isInitializing = ref(true);

const { processInvite, resolvePendingInvite } = useInviteFlow({
  getCurrentUser: () => currentUser.value,
  onFriendAdded: () => { router.push({ name: 'friends' }); }
});

const { init: initAppLifecycle, cleanup: cleanupAppLifecycle } = useAppLifecycle({
  onDeepLinkInvite: (code) => processInvite(code, currentUser.value)
});

const onLoginSuccess = async (user, token) => {
  // Guardar el token antes de exponer currentUser: al asignarlo se monta
  // DashboardView de inmediato y dispara llamadas a la API que ya necesitan
  // el token guardado, o fallan con 401 por la condición de carrera.
  await UserService.saveSession(user, token);
  currentUser.value = user;
  ToastService.success(`¡Hola, ${user.display_name}! 👋`);

  // Procesar invitación pendiente si existía
  await resolvePendingInvite(user);
};

// Punto único de inicialización de push: se dispara solo cuando cambia el id de sesión
// (login, restauración de sesión) — no en cada actualización de perfil/recordatorio.
const currentUserId = computed(() => currentUser.value?.id);
watch(currentUserId, (id) => {
  if (id) {
    NotificationService.initPushNotifications(id, () => {
      router.push({ name: 'friends' });
    }).catch((e) => {
      console.warn('No se pudo inicializar notificaciones push:', e.message);
    });
  }
});

const onUserUpdated = async (updatedUser) => {
  currentUser.value = { ...currentUser.value, ...updatedUser };
  await UserService.saveSession(currentUser.value);
};

const onLogout = async () => {
  await NotificationService.unregisterPushToken();
  clearUser();
  await UserService.clearSession();
  router.push({ name: 'dashboard' });
  // ToastService.info('Sesión cerrada correctamente.');
};

// Token invalido/expirado/revocado: no tiene caso llamar endpoints autenticados
// (unregisterPushToken volveria a fallar con 401), solo limpiar sesion local.
let forcingLogout = false;
const forceLogout = async () => {
  if (forcingLogout || !currentUser.value) return;
  forcingLogout = true;
  try {
    clearUser();
    await UserService.clearSession();
    router.push({ name: 'dashboard' });
    ToastService.error('Tu sesión expiró. Inicia sesión de nuevo.');
  } finally {
    forcingLogout = false;
  }
};

onMounted(async () => {
  setUnauthorizedHandler(forceLogout);

  // Inicializar sesión de usuario y sincronizar timezone en segundo plano si cambió
  try {
    currentUser.value = await UserService.initSession();
  } catch (e) {
    console.warn('No se pudo restaurar la sesión guardada:', e.message);
    clearUser();
  } finally {
    isInitializing.value = false;
  }

  // Registrar listeners globales: push, retorno a primer plano, deep links
  await initAppLifecycle();

  if (currentUser.value && currentUser.value.id) {
    // Procesar invitación pendiente guardada si existe sesión activa
    await resolvePendingInvite(currentUser.value);
  }
});

onUnmounted(() => {
  cleanupAppLifecycle();
});
</script>
