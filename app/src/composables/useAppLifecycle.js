import { App as CapApp } from '@capacitor/app';
import { DeepLinkService } from '../services/deepLinks';
import { NotificationService } from '../services/notifications';

/**
 * Registra los listeners globales de ciclo de vida de la app: notificaciones push,
 * retorno a primer plano y deep links de invitación.
 */
export function useAppLifecycle({ onDeepLinkInvite }) {
  const listenerHandles = [];

  const init = async () => {
    // Caso 1: Quitar notificaciones Push entregadas al abrir la app
    NotificationService.clearPushNotifications();

    // Escuchar cuando la app regresa a primer plano desde segundo plano
    const appStateHandle = await CapApp.addListener('appStateChange', ({ isActive }) => {
      if (isActive) {
        NotificationService.clearPushNotifications();
      }
    });
    listenerHandles.push(appStateHandle);

    // Inicializar listeners de notificaciones locales
    NotificationService.attachLocalListeners();

    // Inicializar receptor de enlaces de invitación (Deep Links & Cold Start)
    const deepLinkHandle = await DeepLinkService.initListener(async (inviteCode) => {
      await onDeepLinkInvite(inviteCode);
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
