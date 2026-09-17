import { LocalNotifications } from '@capacitor/local-notifications';
import { PushNotifications } from '@capacitor/push-notifications';
import { Capacitor } from '@capacitor/core';
import { ApiService } from './api';
import { StorageService } from './storage';
import { ToastService } from './toast';

const pushState = { userId: null, onNewFollowerTapped: null, onNudgeTapped: null };
let onReadingReminderTapped = null;
let listenersRegistered = false;

export const NotificationService = {
  setNotificationNavigationHandlers(onNewFollowerTapped, onNudgeTapped, onReadingReminderTappedHandler) {
    pushState.onNewFollowerTapped = onNewFollowerTapped;
    pushState.onNudgeTapped = onNudgeTapped;
    onReadingReminderTapped = onReadingReminderTappedHandler;
  },

  async requestLocalPermissions() {
    if (!Capacitor.isNativePlatform()) return true;

    try {
      const status = await LocalNotifications.requestPermissions();
      return status.display === 'granted';
    } catch (e) {
      console.warn('Error al solicitar permisos de notificación local:', e);
      return false;
    }
  },

  async checkLocalPermissions() {
    if (!Capacitor.isNativePlatform()) return true;

    try {
      const status = await LocalNotifications.checkPermissions();
      return status.display === 'granted';
    } catch (e) {
      console.warn('Error al consultar permisos de notificación local:', e);
      return false;
    }
  },

  async registerPushNotifications(userId) {
    if (!Capacitor.isNativePlatform() || !userId) return;

    pushState.userId = userId;

    try {
      const permResult = await PushNotifications.requestPermissions();
      if (permResult.receive !== 'granted') {
        console.warn('Permiso de notificaciones push no otorgado.');
        return;
      }

      await PushNotifications.register();
    } catch (e) {
      console.warn('Error al inicializar Push Notifications:', e);
    }
  },

  async activateNotifications(userId) {
    const localGranted = await this.requestLocalPermissions();
    await this.registerPushNotifications(userId);
    return localGranted;
  },

  async reactivateIfPermitted(userId) {
    if (Capacitor.isNativePlatform() && userId) {
      pushState.userId = userId;
      try {
        const pushStatus = await PushNotifications.checkPermissions();
        if (pushStatus.receive === 'granted') {
          await PushNotifications.register();
        } else {
          // Solo desregistrar si habia un token guardado de una sesion anterior con
          // permiso otorgado: evita pegarle a la API en cada apertura de la app
          // cuando el permiso ya estaba denegado (no hay nada que limpiar).
          const savedToken = await StorageService.get('push_token');
          if (savedToken) {
            await this.unregisterPushToken();
          }
        }
      } catch (e) {
        console.warn('Error al verificar permisos push:', e);
      }
    }
    return this.checkLocalPermissions();
  },

  async schedule7DayBurst(
    reminderTimeStr = '20:00',
    currentStreak = 1,
    hasReadToday = false,
    freezesAvailable = 0,
    bookTitle = null,
  ) {
    if (!Capacitor.isNativePlatform()) {
      console.log(`[Web Demo] Recordatorio de 7 días programado a las ${reminderTimeStr} (Ya leyó hoy: ${hasReadToday})`);
      return true;
    }

    const granted = await this.checkLocalPermissions();
    if (!granted) {
      await this.cancelLocalReminders();
      return false;
    }

    try {
      await this.cancelLocalReminders();

      const [hoursStr, minutesStr] = reminderTimeStr.split(':');
      const hours = parseInt(hoursStr, 10) || 20;
      const minutes = parseInt(minutesStr, 10) || 0;

      const LAST_CHANCE_HOUR = 22;
      const LAST_CHANCE_MINUTE = 30;

      const now = new Date();
      const todayReminderTime = new Date(now.getFullYear(), now.getMonth(), now.getDate(), hours, minutes, 0);
      const todayLastChanceTime = new Date(now.getFullYear(), now.getMonth(), now.getDate(), LAST_CHANCE_HOUR, LAST_CHANCE_MINUTE, 0);

      const includeToday = !hasReadToday && todayReminderTime.getTime() > now.getTime();
      const includeTodayLastChance = !hasReadToday && todayLastChanceTime.getTime() > now.getTime();
      const startOffset = includeToday ? 0 : 1;
      const endOffset = startOffset + 6;

      const streakText = currentStreak + ' ' + (currentStreak === 0 ? 'día' : 'días');
      const book = (bookTitle || '').trim();

      const notifications = [];
      const messages = book
        ? [
            `¡No rompas tu racha de ${streakText}! 📖🔥 "${book}" te espera.`,
            `Dedica 5 minutos a "${book}" hoy y sigue haciendo crecer tu hábito. 📚`,
            `¡Un capítulo de "${book}" al día marca la diferencia! ✨`,
            `Racha protegida: ${currentStreak + 1} días a tu alcance. ¡Sigue con "${book}"! 🔥`,
            `"${book}" te espera. Lee 5 minutos hoy. 📕`,
            '¡Completa tu semana perfecta de lectura! 🎯',
            `¡Mantén viva tu racha de lectura! Avanza en "${book}" hoy. 🌟`
          ]
        : [
            `¡No rompas tu racha de ${streakText}! 📖🔥 Tu libro te espera.`,
            'Dedica 5 minutos a leer hoy y sigue haciendo crecer tu hábito. 📚',
            '¡Un capítulo al día marca la diferencia! Entra a Libringo. ✨',
            `Racha protegida: ${currentStreak + 1} días a tu alcance. ¡A leer! 🔥`,
            'El conocimiento te espera. Lee 5 minutos hoy. 📕',
            '¡Completa tu semana perfecta de lectura! 🎯',
            '¡Mantén viva tu racha de lectura! Registra tu progreso hoy. 🌟'
          ];

      for (let dayOffset = startOffset; dayOffset <= endOffset; dayOffset++) {
        const scheduleDate = new Date(now.getFullYear(), now.getMonth(), now.getDate() + dayOffset, hours, minutes, 0);
        const isTodayReminder = includeToday && dayOffset === 0;

        let title = '📖 Libringo: Recordatorio diario';
        let body;
        if (isTodayReminder) {
          title = freezesAvailable === 0 ? '⚠️ Tu racha está en riesgo' : '🧊 No arriesgues tu racha';
          const urgentBook = book ? ` "${book}"` : '';
          body = freezesAvailable === 0
            ? `No tienes protectores de racha. ¡Lee${urgentBook} ahora para no perder tu racha de ${streakText}!`
            : `¡Lee${urgentBook} ahora para no perder tu racha de ${streakText}!`;
        } else {
          const msgIndex = (dayOffset - startOffset) % messages.length;
          body = messages[msgIndex];
        }

        notifications.push({
          id: 1000 + dayOffset,
          title,
          body,
          schedule: { at: scheduleDate },
          sound: 'beep.wav',
          badge: 1,
          actionTypeId: 'OPEN_READING',
          extra: { dayOffset }
        });
      }

      const startOffsetLC = includeTodayLastChance ? 0 : 1;
      const endOffsetLC = startOffsetLC + 6;
      for (let dayOffset = startOffsetLC; dayOffset <= endOffsetLC; dayOffset++) {
        const scheduleDate = new Date(now.getFullYear(), now.getMonth(), now.getDate() + dayOffset, LAST_CHANCE_HOUR, LAST_CHANCE_MINUTE, 0);
        notifications.push({
          id: 2000 + dayOffset,
          title: '⏰ Última llamada de hoy',
          body: book
            ? `El día se acaba y aún puedes salvar tu racha de ${streakText}. ¡Sigue leyendo "${book}"! 📖`
            : `El día se acaba y aún puedes salvar tu racha de ${streakText}. ¡Registra tu lectura! 📖`,
          schedule: { at: scheduleDate },
          sound: 'beep.wav',
          badge: 1,
          actionTypeId: 'OPEN_READING',
          extra: { dayOffset, lastChance: true }
        });
      }

      await LocalNotifications.schedule({ notifications });
      console.log(`Ráfaga de notificaciones programada (hoy incluido: ${includeToday}, última llamada hoy: ${includeTodayLastChance}).`);
      return true;
    } catch (e) {
      console.error('Error al programar ráfaga de notificaciones:', e);
      return false;
    }
  },

  async scheduleReminderForUser(user) {
    if (!user || user.notification_prefs?.daily_reminder === false) {
      await this.cancelLocalReminders();
      return false;
    }
    const savedTime = (await StorageService.get('reminder_time')) || user.reminder_time || '20:00';
    return this.schedule7DayBurst(
      savedTime, user.streak_count,
      user.has_read_today || false,
      user.streak_freezes || 0,
      user.current_book_title || null,
    );
  },

  async persistReminderTime(reminderTimeStr) {
    await StorageService.set('reminder_time', reminderTimeStr);
    await ApiService.updateProfile({ reminder_time: reminderTimeStr });
  },

  async unregisterPushToken() {
    try {
      const savedToken = await StorageService.get('push_token');
      const savedUserId = await StorageService.get('push_user_id');

      if (savedUserId) {
        await ApiService.unregisterPushToken(savedToken || '');
      }

      await StorageService.remove('push_token');
      await StorageService.remove('push_user_id');
      console.log('[PushNotifications] Token desregistrado exitosamente.');
    } catch (e) {
      console.warn('Error al desregistrar push token:', e.message || e);
    }
  },

  async sendLocalTestNotification(delaySeconds = 3) {
    if (!Capacitor.isNativePlatform()) {
      ToastService.info(`[Simulación Web] 🔔 Notificación en ${delaySeconds} segundos: "¡Las notificaciones locales funcionan! 🎉"`);
      return true;
    }

    try {
      const scheduleDate = new Date(Date.now() + delaySeconds * 1000);
      const notifId = Math.floor(10000 + Math.random() * 90000);

      await LocalNotifications.schedule({
        notifications: [
          {
            id: notifId,
            title: '📖 Libringo: Notificación de prueba',
            body: '¡Las notificaciones funcionan perfectamente! 🎉 Tu libro te espera hoy.',
            schedule: { at: scheduleDate },
            sound: 'beep.wav',
            actionTypeId: 'OPEN_READING',
            extra: { isTest: true }
          }
        ]
      });

      ToastService.success(`Notificación en ${delaySeconds} segundos. ¡Bloquea o sal de la app para ver el aviso! 📲`);
      return true;
    } catch (e) {
      console.error('Error al programar notificación de prueba:', e);
      ToastService.error(`Error: ${e.message || 'No se pudo programar'}`);
      return false;
    }
  },

  async cleanupOnLogout() {
    await this.unregisterPushToken();
    await this.cancelLocalReminders();
    pushState.userId = null;
  },

  async cancelLocalReminders() {
    if (!Capacitor.isNativePlatform()) return;
    try {
      const pending = await LocalNotifications.getPending();
      if (pending.notifications && pending.notifications.length > 0) {
        await LocalNotifications.cancel(pending);
      }
    } catch (e) {
      console.warn('Error al cancelar recordatorios programados:', e.message || e);
    }
  },

  async clearDeliveredPushNotifications() {
    if (!Capacitor.isNativePlatform()) return;
    try {
      await PushNotifications.removeAllDeliveredNotifications();
      console.log('[NotificationService] Notificaciones push entregadas limpiadas.');
    } catch (e) {
      console.warn('Error al limpiar notificaciones push:', e.message || e);
    }
  },

  async clearDeliveredLocalNotifications() {
    if (!Capacitor.isNativePlatform()) return;
    try {
      await LocalNotifications.removeAllDeliveredNotifications();
      console.log('[NotificationService] Notificaciones locales y badge de lectura limpiados tras leer.');
    } catch (e) {
      console.warn('Error al limpiar notificaciones locales:', e.message || e);
    }
  },

  async attachListeners() {
    if (!Capacitor.isNativePlatform() || listenersRegistered) return;
    listenersRegistered = true;

    await PushNotifications.addListener('registration', async (token) => {
      if (token && token.value) {
        if (!pushState.userId) {
          console.log('[PushNotifications] Token recibido sin usuario activo (logout en curso), se descarta.');
          return;
        }

        const pushToken = token.value;
        const platform = Capacitor.getPlatform() || 'ios';

        console.log(`[PushNotifications] Token recibido (${platform}):`, pushToken);

        try {
          const savedToken = await StorageService.get('push_token');
          const savedUserId = await StorageService.get('push_user_id');

          if (savedToken === pushToken && String(savedUserId) === String(pushState.userId)) {
            console.log('[PushNotifications] El token ya está sincronizado para este usuario.');
            return;
          }

          await ApiService.registerPushToken(pushToken, platform);
          await StorageService.set('push_token', pushToken);
          await StorageService.set('push_user_id', pushState.userId);
          console.log('[PushNotifications] Token sincronizado exitosamente con la API.');
        } catch (err) {
          console.warn('Error al enviar el push token a la API:', err.message);
        }
      }
    });

    await PushNotifications.addListener('registrationError', (error) => {
      console.warn('Error en registro de Push Notifications:', error);
    });

    await PushNotifications.addListener('pushNotificationReceived', (notification) => {
      console.log('[PushReceived]', notification);
    });

    await PushNotifications.addListener('pushNotificationActionPerformed', (notificationAction) => {
      console.log('[PushActionPerformed]', notificationAction);
      const data = notificationAction.notification?.data;
      if (data?.type === 'new_follower') {
        pushState.onNewFollowerTapped?.(data.user_id);
      } else if (data?.type === 'nudge') {
        pushState.onNudgeTapped?.();
      }
    });

    await LocalNotifications.addListener('localNotificationReceived', (notification) => {
      console.log('[LocalNotificationReceived]', notification);
    });

    await LocalNotifications.addListener('localNotificationActionPerformed', (notificationAction) => {
      console.log('[LocalNotificationActionPerformed]', notificationAction);
      onReadingReminderTapped?.();
    });
  },
};
