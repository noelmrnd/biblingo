import { App as CapApp } from '@capacitor/app';
import { DeepLinkService } from '@/services/deepLinks';
import { NotificationService } from '@/services/notifications';

/**
 * Registra los listeners globales de ciclo de vida de la app: notificaciones push,
 * retorno a primer plano y deep links de invitación.
 */
export function useAppLifecycle({ router, onDeepLinkInvite }) {
  const listenerHandles = [];

  const init = async () => {
    // No depende del usuario logueado (solo del router), asi que se setea una
    // sola vez aca en vez de repetirse en cada cambio de sesion.
    NotificationService.setNotificationNavigationHandlers(
      (followerId) => followerId
        ? router.push({ name: 'friend-profile', params: { id: followerId } })
        : router.push({ name: 'friends' }),
      () => router.push({ name: 'dashboard' }),
      () => router.push({ name: 'dashboard' })
    );

    NotificationService.clearDeliveredPushNotifications();
    // Debe esperarse: init() (y por lo tanto App.vue) necesita garantizar que
    // el listener 'registration' ya esta puesto antes de que currentUser
    // dispare registerPushNotifications, o el token nativo llega sin nadie
    // escuchando y se pierde.
    await NotificationService.attachListeners();

    // Escuchar cuando la app regresa a primer plano desde segundo plano
    const appStateHandle = await CapApp.addListener('appStateChange', ({ isActive }) => {
      if (isActive) {
        NotificationService.clearDeliveredPushNotifications();
      }
    });
    listenerHandles.push(appStateHandle);

    // Inicializar receptor de enlaces de invitación (Deep Links & Cold Start)
    const deepLinkHandle = await DeepLinkService.initListener(async (username) => {
      await onDeepLinkInvite(username);
    });
    if (deepLinkHandle) {
      listenerHandles.push(deepLinkHandle);
    }
  };

  const cleanup = () => {
    listenerHandles.forEach((handle) => handle.remove());
    listenerHandles.length = 0;
  };

  return { init, cleanup };
}
