import { App as CapApp } from '@capacitor/app';
import { DeepLinkService } from '../services/deepLinks';
import { NotificationService } from '../services/notifications';

/**
 * Registra los listeners globales de ciclo de vida de la app: notificaciones push,
 * retorno a primer plano y deep links de invitación.
 */
export function useAppLifecycle({ onDeepLinkInvite }) {
  const init = () => {
    // Caso 1: Quitar notificaciones Push entregadas al abrir la app
    NotificationService.clearPushNotifications();

    // Escuchar cuando la app regresa a primer plano desde segundo plano
    CapApp.addListener('appStateChange', ({ isActive }) => {
      if (isActive) {
        NotificationService.clearPushNotifications();
      }
    });

    // Inicializar listeners de notificaciones locales
    NotificationService.attachLocalListeners();

    // Inicializar receptor de enlaces de invitación (Deep Links & Cold Start)
    DeepLinkService.initListener(async (inviteCode) => {
      await onDeepLinkInvite(inviteCode);
    });
  };

  return { init };
}
