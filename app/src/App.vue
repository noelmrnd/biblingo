<template>
  <div
    class="h-full w-full bg-brand-dark flex flex-col overflow-hidden selection:bg-brand-green selection:text-white relative"
    :class="{ 'max-w-lg mx-auto shadow-xl': !isBrowserOnAppDomain }"
  >
    <!-- Componente Toast Flotante Global -->
    <ToastNotification />

    <!-- Modal de Celebracion para Logros -->
    <BadgeCelebrationModal />

    <!-- app.libringo.com abierto desde un navegador normal (no la WebView nativa):
         mostrar la invitación a descargar la app en vez de la app web/login. -->
    <GetAppView v-if="isBrowserOnAppDomain" />

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
import { useInviteFlow } from './composables/useInviteFlow';
import { useAppLifecycle } from './composables/useAppLifecycle';
import { useCurrentUser } from './composables/useCurrentUser';
import {APP_CONFIG} from "@/constants.js";

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
  onFriendAdded: (friend) => {
    if (friend?.id) {
      router.push({ name: 'friend-profile', params: { id: friend.id } });
    } else {
      router.push({ name: 'friends' });
    }
  }
});

const { init: initAppLifecycle, cleanup: cleanupAppLifecycle } = useAppLifecycle({
  onDeepLinkInvite: (code) => processInvite(code, currentUser.value)
});

// Reprograma la rafaga de 7 dias con los datos ya frescos del login/inicio de
// sesion (streak_count, has_read_today, reminder_time), en vez de esperar a
// que DashboardView monte — asi corre sin importar en que tab entre primero.
const scheduleReminderForUser = async (user) => {
  if (user.notification_prefs?.daily_reminder === false) return;
  const savedTime = (await StorageService.get('reminder_time')) || user.reminder_time || '20:00';
  NotificationService.schedule7DayBurst(savedTime, user.streak_count, user.has_read_today || false, user.streak_freezes || 0);
};

const onLoginSuccess = async (user, token) => {
  // Guardar el token antes de exponer currentUser: al asignarlo se monta
  // DashboardView de inmediato y dispara llamadas a la API que ya necesitan
  // el token guardado, o fallan con 401 por la condición de carrera.
  await UserService.saveToken(token);
  currentUser.value = user;
  markFreshLoad();
  ToastService.success(`¡Hola, ${user.display_name}! 👋`);
  scheduleReminderForUser(user);
  AnalyticsService.logEvent('login');

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
    AnalyticsService.setUser(id).catch(() => {});
  }
});

const onUserUpdated = (updatedUser) => {
  currentUser.value = { ...currentUser.value, ...updatedUser };
};

const onLogout = async () => {
  await NotificationService.unregisterPushToken();
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
  // Antes de borrar la cuenta: el token todavia es valido aca. Una vez que el
  // servidor marca status='deleted' el token se invalida de inmediato, asi que
  // desregistrar el push despues dispararia un 401 y el logout forzado por error.
  await NotificationService.unregisterPushToken();

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

  // Si hay token guardado, reconstruye el usuario completo pidiendolo al servidor
  // (no se cachea el objeto user en disco) y sincroniza timezone si cambió.
  try {
    currentUser.value = await UserService.initSession();
    if (currentUser.value) {
      markFreshLoad();
      scheduleReminderForUser(currentUser.value);
    }
  } catch (e) {
    console.warn('No se pudo restaurar la sesión:', e.message);
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
