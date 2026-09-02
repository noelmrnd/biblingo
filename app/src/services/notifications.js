import { LocalNotifications } from '@capacitor/local-notifications';
import { PushNotifications } from '@capacitor/push-notifications';
import { Capacitor } from '@capacitor/core';

export const NotificationService = {
  /**
   * Solicita permisos de notificación al usuario.
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
   * Programa la ráfaga de 7 días escalonados de recordatorios locales de lectura.
   * Se ejecuta al guardar la hora deseada o tras completar la lectura del día.
   */
  async schedule7DayBurst(reminderTimeStr = '20:00', currentStreak = 1) {
    if (!Capacitor.isNativePlatform()) {
      console.log(`[Web Demo] Recordatorio de 7 días programado a las ${reminderTimeStr}`);
      return;
    }

    try {
      // Cancelar notificaciones previas
      const pending = await LocalNotifications.getPending();
      if (pending.notifications.length > 0) {
        await LocalNotifications.cancel(pending);
      }

      const [hoursStr, minutesStr] = reminderTimeStr.split(':');
      const hours = parseInt(hoursStr, 10) || 20;
      const minutes = parseInt(minutesStr, 10) || 0;

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

      const now = new Date();

      for (let dayOffset = 1; dayOffset <= 7; dayOffset++) {
        const scheduleDate = new Date(now.getFullYear(), now.getMonth(), now.getDate() + dayOffset, hours, minutes, 0);

        notifications.push({
          id: 1000 + dayOffset,
          title: '📖 Biblingo: Recordatorio Diario',
          body: messages[dayOffset - 1] || messages[0],
          schedule: { at: scheduleDate },
          sound: 'beep.wav',
          actionTypeId: 'OPEN_READING',
          extra: { dayOffset }
        });
      }

      await LocalNotifications.schedule({ notifications });
      console.log('Ráfaga de 7 días de notificaciones locales programada con éxito.');
    } catch (e) {
      console.error('Error al programar ráfaga de notificaciones:', e);
    }
  },

  /**
   * Registrar token para Push Notifications (FCM).
   */
  async registerPushToken(onTokenReceived) {
    if (!Capacitor.isNativePlatform()) return;

    try {
      await PushNotifications.register();
      await PushNotifications.addListener('registration', token => {
        if (token && token.value) {
          onTokenReceived(token.value);
        }
      });
    } catch (e) {
      console.warn('Error al registrar Push Notifications:', e);
    }
  }
};
