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
  async initPushNotifications(userId) {
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
            await ApiService.updateProfile(userId, {
              push_token: pushToken,
              platform: platform
            });
            await StorageService.set('push_token', pushToken);
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
          actionTypeId: 'OPEN_READING',
          extra: { dayOffset }
        });
      }

      await LocalNotifications.schedule({ notifications });
      console.log(`Ráfaga de notificaciones programada (hoy incluido: ${includeToday}).`);
    } catch (e) {
      console.error('Error al programar ráfaga de notificaciones:', e);
    }
  }
};
