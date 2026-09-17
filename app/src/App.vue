<template>
  <div
    class="h-full w-full bg-brand-dark flex flex-col overflow-hidden selection:bg-brand-green selection:text-white relative"
    :class="{ 'max-w-lg mx-auto shadow-xl': !isBrowserOnAppDomain }"
  >
    <!-- Componente Toast Flotante Global -->
    <ToastNotification />

    <!-- Modal de Celebracion para Logros -->
    <BadgeCelebrationModal />

    <router-view v-if="!Capacitor.isNativePlatform() && router.currentRoute.value.name === 'invite'" />

    <GetAppView v-else-if="isBrowserOnAppDomain" />

    <!-- Splash mientras se resuelve la sesión guardada, evita el parpadeo hacia Login -->
    <div v-else-if="isInitializing" class="flex-1 flex items-center justify-center">
      <AppSpinner size="lg" />
    </div>

    <!-- Si no está autenticado, mostrar Login -->
    <LoginView v-else-if="!currentUser" @login-success="onLoginSuccess" />

    <!-- Aplicación Principal -->
    <template v-else>
      <!-- Tour de Bienvenida Inicial Autónomo (Onboarding) -->
      <OnboardingTour ref="tourRef" />

      <!-- Cada vista trae su propio AppPage, que ya incluye el header que le corresponde.
           KeepAlive solo en los 3 tabs principales: evita destruir/recrear el DOM (con el
           parpadeo del logo del header incluido) al ir y volver entre Racha/Amigos/Perfil.
           Perfil de amigo y Ajustes quedan afuera a proposito, son pantallas de detalle que
           no queremos acumular en memoria indefinidamente. -->
      <router-view v-slot="{ Component }">
        <keep-alive :include="keepAliveNames">
          <component
            :is="Component"
            :user="currentUser"
            @user-updated="onUserUpdated"
            @logout="onLogout"
            @delete-account="onDeleteAccount"
            @open-tour="tourRef?.open"
          />
        </keep-alive>
      </router-view>

      <!-- Bottom Navigation Bar Gamificada (Flex Fixed Bottom) -->
      <BottomNav />
    </template>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { Capacitor } from '@capacitor/core';
import BottomNav from './components/BottomNav.vue';
import OnboardingTour from './components/OnboardingTour.vue';
import LoginView from './views/LoginView.vue';
import GetAppView from './views/GetAppView.vue';
import AppSpinner from './components/AppSpinner.vue';
import ToastNotification from './components/ToastNotification.vue';
import BadgeCelebrationModal from './components/BadgeCelebrationModal.vue';
import { ToastService } from './services/toast';
import { UserService } from './services/userService';
import { NotificationService } from './services/notifications';
import { StorageService } from './services/storage';
import { ApiService, setUnauthorizedHandler } from './services/api';
import { AnalyticsService } from './services/analytics';
import { useInviteFlow, friendAddedRedirect } from './composables/useInviteFlow';
import { useAppLifecycle } from './composables/useAppLifecycle';
import { useCurrentUser } from './composables/useCurrentUser';
import {APP_CONFIG, TOUR_SEEN_KEY} from "@/constants.js";

const router = useRouter();
const { user: currentUser, clearUser, markFreshLoad } = useCurrentUser();
const tourRef = ref(null);
const isInitializing = ref(true);

// Deriva el include de <keep-alive> del nombre real de cada componente marcado
// con meta.keepAlive en el router, en vez de tipearlo a mano — asi un rename
// de archivo no desincroniza la lista y rompe el cache en silencio.
const keepAliveNames = router.getRoutes()
  .filter((r) => r.meta?.keepAlive)
  .map((r) => r.components?.default?.name || r.components?.default?.__name)
  .filter(Boolean);
const isBrowserOnAppDomain = !Capacitor.isNativePlatform() && window.location.hostname === APP_CONFIG.appDomain;

const { processInvite, resolvePendingInvite } = useInviteFlow({
  getCurrentUser: () => currentUser.value,
  onFriendAdded: friendAddedRedirect(router),
});

const { init: initAppLifecycle, cleanup: cleanupAppLifecycle } = useAppLifecycle({
  router,
  onDeepLinkInvite: (code) => processInvite(code, currentUser.value)
});

const onLoginSuccess = async (user, token) => {
  // Guardar el token antes de exponer currentUser: al asignarlo se monta
  // DashboardView de inmediato y dispara llamadas a la API que ya necesitan
  // el token guardado, o fallan con 401 por la condición de carrera.
  await UserService.saveToken(token);
  currentUser.value = user;
  markFreshLoad();
  // ToastService.success(`¡Hola, ${user.display_name}! 👋`);
  AnalyticsService.logEvent('login');

  // Procesar invitación pendiente si existía
  await resolvePendingInvite(user);
};

// Punto único de inicialización de notificaciones: se dispara solo cuando cambia
// el id de sesión (login, restauración de sesión) — no en cada actualización de
// perfil/recordatorio.
const currentUserId = computed(() => currentUser.value?.id);
watch(currentUserId, async (id) => {
  if (!id) return;

  // Los permisos (local + push, ver activateNotifications) se piden con contexto
  // durante el onboarding (ver OnboardingTour.finishTour/checkTourStatus), no aca
  // sin explicacion. Si el tour ya se vio (usuario existente) no hay onboarding
  // que los pida, asi que se activan normalmente en cada login.
  const tourSeen = await StorageService.get(TOUR_SEEN_KEY);
  if (tourSeen) {
    try {
      await NotificationService.reactivateIfPermitted(id);
    } catch (e) {
      console.warn('No se pudo inicializar notificaciones:', e.message);
    }
  }

  // Este watch corre en los mismos 2 momentos en que currentUserId pasa a tener
  // valor (login nuevo, restauracion de sesion) — programar aca en vez de en
  // onLoginSuccess/onMounted evita duplicar la llamada, y corre DESPUES de
  // activar los permisos arriba, sin la carrera de chequear antes de otorgar.
  NotificationService.scheduleReminderForUser(currentUser.value);

  AnalyticsService.setUser(id).catch(() => {});
});

const onUserUpdated = (updatedUser) => {
  currentUser.value = { ...currentUser.value, ...updatedUser };
};

const onLogout = async () => {
  await NotificationService.cleanupOnLogout();

  // Revoca el token en el servidor (best-effort): si falla igual se limpia la
  // sesion local, no tiene sentido dejar al usuario atrapado sin poder salir.
  try {
    await ApiService.logout();
  } catch (e) {
    console.warn('No se pudo revocar la sesión en el servidor:', e.message);
  }
  clearUser();
  await UserService.clearSession();
  AnalyticsService.setUser(null).catch(() => {});
  router.push({ name: 'dashboard' });
  // ToastService.info('Sesión cerrada correctamente.');
};

const onDeleteAccount = async () => {
  await NotificationService.cleanupOnLogout();

  try {
    await ApiService.deleteAccount();
  } catch (e) {
    ToastService.error(e.message || 'No se pudo eliminar la cuenta.');
    return;
  }

  clearUser();
  await UserService.clearSession();
  router.push({ name: 'dashboard' });
  ToastService.info('Tu cuenta fue eliminada.');
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
  AnalyticsService.init().catch(() => {});

  // Registrar listeners globales (push, retorno a primer plano, deep links) ANTES
  // de restaurar la sesion: asignar currentUser.value dispara el watch de abajo,
  // que puede llamar a registerPushNotifications ya en esta misma func. Si
  // attachListeners (adentro de initAppLifecycle) no corrio todavia, el listener
  // 'registration' no esta puesto y el token que devuelve el SDK nativo se pierde.
  await initAppLifecycle();

  // Si hay token guardado, reconstruye el usuario completo pidiendolo al servidor
  // (no se cachea el objeto user en disco) y sincroniza timezone si cambió.
  try {
    currentUser.value = await UserService.initSession();
    if (currentUser.value) {
      markFreshLoad();
    }
  } catch (e) {
    console.warn('No se pudo restaurar la sesión:', e.message);
    clearUser();
  } finally {
    isInitializing.value = false;
  }

  if (currentUser.value && currentUser.value.id) {
    // Procesar invitación pendiente guardada si existe sesión activa
    await resolvePendingInvite(currentUser.value);
  }
});

onUnmounted(() => {
  cleanupAppLifecycle();
});
</script>
