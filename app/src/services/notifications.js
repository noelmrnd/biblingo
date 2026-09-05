import { LocalNotifications } from '@capacitor/local-notifications';
import { PushNotifications } from '@capacitor/push-notifications';
import { Capacitor } from '@capacitor/core';
import { ApiService } from './api';
import { StorageService } from './storage';
import { ToastService } from './toast';

export const NotificationService = {
  /**
   * Solicita permisos de notificación al usuario (locales y push).
   */
  async requestPermissions() {
    if (!Capacitor.isNativePlatform()) return true;

    try {
      const localStatus = await LocalNotifications.requestPermissions();
      const pushStatus = await PushNotifications.requestPermissions();
      return localStatus.display === 'granted' && pushStatus.receive === 'granted';
    } catch (e) {
      console.warn('Error al solicitar permisos de notificación:', e);
      return false;
    }
  },

  /**
   * Inicializa el registro de notificaciones Push, solicita permisos,
   * escucha eventos de registro y envía el token a la API del servidor.
   */
  async initPushNotifications(userId, onFriendNotificationTapped) {
    if (!Capacitor.isNativePlatform() || !userId) return;

    try {
      const permResult = await PushNotifications.requestPermissions();
      if (permResult.receive !== 'granted') {
        console.warn('Permiso de notificaciones push no otorgado.');
        return;
      }

      await PushNotifications.register();

      // Escuchar registro exitoso de token FCM / APNs
      await PushNotifications.addListener('registration', async (token) => {
        if (token && token.value) {
          const pushToken = token.value;
          const platform = Capacitor.getPlatform() || 'ios';

          console.log(`[PushNotifications] Token recibido (${platform}):`, pushToken);

          try {
            const savedToken = await StorageService.get('push_token');
            const savedUserId = await StorageService.get('push_user_id');

            // Enviar a la API únicamente si el token o el usuario activo cambiaron
            if (savedToken === pushToken && String(savedUserId) === String(userId)) {
              console.log('[PushNotifications] El token ya está sincronizado para este usuario.');
              return;
            }

            await ApiService.registerPushToken(userId, pushToken, platform);
            await StorageService.set('push_token', pushToken);
            await StorageService.set('push_user_id', userId);
            console.log('[PushNotifications] Token sincronizado exitosamente con la API.');
          } catch (err) {
            console.warn('Error al enviar el push token a la API:', err.message);
          }
        }
      });

      // Escuchar posibles errores de registro
      await PushNotifications.addListener('registrationError', (error) => {
        console.warn('Error en registro de Push Notifications:', error);
      });

      // Escuchar cuando llega una notificación Push estando la app en primer plano
      await PushNotifications.addListener('pushNotificationReceived', (notification) => {
        console.log('[PushReceived]', notification);
        const title = notification.title || '📖 Biblingo';
        const body = notification.body || '¡Tienes una nueva notificación!';
        ToastService.info(`${title}: ${body}`);
      });

      // Escuchar al tocar una notificación Push desde la barra de estado
      await PushNotifications.addListener('pushNotificationActionPerformed', (notificationAction) => {
        console.log('[PushActionPerformed]', notificationAction);
        const type = notificationAction.notification?.data?.type;
        const friendTypes = ['friend_request', 'friend_added', 'nudge'];
        if (friendTypes.includes(type)) {
          onFriendNotificationTapped?.();
        }
      });

    } catch (e) {
      console.warn('Error al inicializar Push Notifications:', e);
    }
  },

  /**
   * Programar ráfaga de 7 días de notificaciones locales.
   * Si ya se leyó hoy o la hora de hoy ya pasó, comienza a notificar a partir de mañana.
   */
  async schedule7DayBurst(reminderTimeStr = '20:00', currentStreak = 1, hasReadToday = false) {
    if (!Capacitor.isNativePlatform()) {
      console.log(`[Web Demo] Recordatorio de 7 días programado a las ${reminderTimeStr} (Ya leyó hoy: ${hasReadToday})`);
      return;
    }

    try {
      // Cancelar todas las notificaciones pendientes previas
      const pending = await LocalNotifications.getPending();
      if (pending.notifications && pending.notifications.length > 0) {
        await LocalNotifications.cancel(pending);
      }

      const [hoursStr, minutesStr] = reminderTimeStr.split(':');
      const hours = parseInt(hoursStr, 10) || 20;
      const minutes = parseInt(minutesStr, 10) || 0;

      const now = new Date();
      const todayReminderTime = new Date(now.getFullYear(), now.getMonth(), now.getDate(), hours, minutes, 0);

      // Incluir el día de hoy únicamente si el usuario NO ha leído hoy Y la hora del recordatorio es en el futuro
      const includeToday = !hasReadToday && todayReminderTime.getTime() > now.getTime();
      const startOffset = includeToday ? 0 : 1;
      const endOffset = startOffset + 6;

      const notifications = [];
      const messages = [
        `¡No rompas tu racha de ${currentStreak} día(s)! 📖🔥 Tu libro te espera.`,
        'Dedica 10 minutos a leer hoy y sigue haciendo crecer tu hábito. 📚',
        '¡Un capítulo al día marca la diferencia! Entra a Biblingo. ✨',
        `Racha protegida: ${currentStreak + 1} días a tu alcance. ¡A leer! 🔥`,
        'El conocimiento te espera. Lee 5 páginas hoy. 📕',
        '¡Casi completas tu semana perfecta de lectura! 🎯',
        '¡Mantén viva tu llama de lectura! Registra tu progreso hoy. 🌟'
      ];

      for (let dayOffset = startOffset; dayOffset <= endOffset; dayOffset++) {
        const scheduleDate = new Date(now.getFullYear(), now.getMonth(), now.getDate() + dayOffset, hours, minutes, 0);
        const msgIndex = Math.abs(dayOffset) % messages.length;

        notifications.push({
          id: 1000 + dayOffset,
          title: '📖 Biblingo: Recordatorio diario',
          body: messages[msgIndex],
          schedule: { at: scheduleDate },
          sound: 'beep.wav',
          badge: 1,
          actionTypeId: 'OPEN_READING',
          extra: { dayOffset }
        });
      }

      await LocalNotifications.schedule({ notifications });
      console.log(`Ráfaga de notificaciones programada (hoy incluido: ${includeToday}).`);
    } catch (e) {
      console.error('Error al programar ráfaga de notificaciones:', e);
    }
  },

  /**
   * Elimina el token push de la API y limpia el almacenamiento de notificaciones al cerrar sesión.
   */
  async unregisterPushToken() {
    try {
      const savedToken = await StorageService.get('push_token');
      const savedUserId = await StorageService.get('push_user_id');

      if (savedUserId) {
        await ApiService.unregisterPushToken(savedUserId, savedToken || '');
      }

      await StorageService.remove('push_token');
      await StorageService.remove('push_user_id');

      if (Capacitor.isNativePlatform()) {
        const pending = await LocalNotifications.getPending();
        if (pending.notifications && pending.notifications.length > 0) {
          await LocalNotifications.cancel(pending);
        }
      }
      console.log('[PushNotifications] Token desregistrado exitosamente.');
    } catch (e) {
      console.warn('Error al desregistrar push token:', e.message || e);
    }
  },

  /**
   * Configura listeners para notificaciones locales (cuando la app está abierta o se interactúa).
   */
  async attachLocalListeners() {
    if (!Capacitor.isNativePlatform()) return;
    try {
      await LocalNotifications.addListener('localNotificationReceived', (notification) => {
        console.log('[LocalNotificationReceived]', notification);
        const title = notification.title || '📖 Biblingo';
        const body = notification.body || '¡Recordatorio de lectura!';
        ToastService.info(`${title}: ${body}`);
      });

      await LocalNotifications.addListener('localNotificationActionPerformed', (notificationAction) => {
        console.log('[LocalNotificationActionPerformed]', notificationAction);
      });
    } catch (e) {
      console.warn('Error al configurar listeners de notificaciones locales:', e);
    }
  },

  /**
   * Programa una notificación local de prueba tras N segundos (por defecto 3s).
   */
  async sendTestNotification(delaySeconds = 3) {
    if (!Capacitor.isNativePlatform()) {
      ToastService.info(`[Simulación Web] 🔔 Notificación en ${delaySeconds}s: "¡Las notificaciones locales funcionan! 🎉"`);
      return true;
    }

    try {
      await this.attachLocalListeners();
      const localStatus = await LocalNotifications.requestPermissions();
      if (localStatus.display !== 'granted') {
        ToastService.error('Permiso de notificaciones denegado en los ajustes del dispositivo.');
        return false;
      }

      const scheduleDate = new Date(Date.now() + delaySeconds * 1000);
      const notifId = Math.floor(10000 + Math.random() * 90000);

      await LocalNotifications.schedule({
        notifications: [
          {
            id: notifId,
            title: '📖 Biblingo: Notificación de prueba',
            body: '¡Las notificaciones locales funcionan perfectamente! 🎉 Tu libro te espera hoy.',
            schedule: { at: scheduleDate },
            sound: 'beep.wav',
            actionTypeId: 'OPEN_READING',
            extra: { isTest: true }
          }
        ]
      });

      ToastService.success(`Notificación en ${delaySeconds}s. ¡Bloquea o sal de la app para ver el aviso! 📲`);
      return true;
    } catch (e) {
      console.error('Error al programar notificación de prueba:', e);
      ToastService.error(`Error: ${e.message || 'No se pudo programar'}`);
      return false;
    }
  },

  /**
   * Caso 1: Limpia las notificaciones Push remotas entregadas (ej. toques de amigos).
   * Se invoca al abrir la app o regresar a ella desde segundo plano.
   */
  async clearPushNotifications() {
    if (!Capacitor.isNativePlatform()) return;
    try {
      await PushNotifications.removeAllDeliveredNotifications();
      console.log('[NotificationService] Notificaciones push entregadas limpiadas.');
    } catch (e) {
      console.warn('Error al limpiar notificaciones push:', e.message || e);
    }
  },

  /**
   * Caso 2: Limpia las notificaciones locales entregadas y resetea el badge del icono a 0.
   * Se invoca únicamente tras haber completado la lectura del día.
   */
  async clearLocalNotifications() {
    if (!Capacitor.isNativePlatform()) return;
    try {
      await LocalNotifications.removeAllDeliveredNotifications();
      console.log('[NotificationService] Notificaciones locales y badge de lectura limpiados tras leer.');
    } catch (e) {
      console.warn('Error al limpiar notificaciones locales:', e.message || e);
    }
  }
};
