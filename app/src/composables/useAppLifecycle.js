import { App as CapApp } from '@capacitor/app';
import { DeepLinkService } from '@/services/deepLinks';
import { NotificationService } from '@/services/notifications';

/**
 * Registra los listeners globales de ciclo de vida de la app: notificaciones push,
 * retorno a primer plano y deep links de invitación.
 */
export function useAppLifecycle({ onDeepLinkInvite }) {
  const listenerHandles = [];

  const init = async () => {
    NotificationService.clearDeliveredPushNotifications();
    NotificationService.attachListeners();

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
