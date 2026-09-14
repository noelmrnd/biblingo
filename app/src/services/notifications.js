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
  /**
   * Registra a donde navegar al tocar cada tipo de notificacion (push y local).
   * Separado de registerPushNotifications para que llamarlo desde otro lado sin
   * router (ej. Ajustes) no pise estos callbacks con undefined.
   */
  setNotificationNavigationHandlers(onNewFollowerTapped, onNudgeTapped, onReadingReminderTappedHandler) {
    pushState.onNewFollowerTapped = onNewFollowerTapped;
    pushState.onNudgeTapped = onNudgeTapped;
    onReadingReminderTapped = onReadingReminderTappedHandler;
  },

  /**
   * Muestra el prompt de permiso de notificaciones LOCALES. Usar solo desde una
   * accion explicita del usuario (activar recordatorio en Ajustes/Onboarding,
   * boton de prueba) — nunca desde una reprogramacion silenciosa en background,
   * el prompt del SO no debe aparecer sin que el usuario lo haya pedido.
   */
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

  /**
   * Consulta el permiso de notificaciones LOCALES sin mostrar el prompt del SO.
   * Usar en reprogramaciones silenciosas (login, tras registrar una lectura):
   * si el permiso no esta otorgado, simplemente no se programa nada.
   */
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

  /**
   * Solicita permiso push y registra el dispositivo (token) para el usuario dado.
   * Asume que attachListeners ya corrio (los listeners deben estar puestos
   * antes de que llegue el evento 'registration' con el token).
   */
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

  /**
   * Programar ráfaga de 7 días de notificaciones locales.
   * Si ya se leyó hoy o la hora de hoy ya pasó, comienza a notificar a partir de mañana.
   * El recordatorio de HOY (si corresponde) usa un mensaje de urgencia real segun si
   * queda o no un protector de racha, en vez del mismo mensaje generico de siempre —
   * es el unico dia en que perder la racha es una amenaza inminente, no hipotetica.
   * Solo consulta el permiso (nunca lo pide) — si el usuario no lo otorgo, no
   * programa nada en silencio. El caller que activa el recordatorio por primera
   * vez es responsable de pedirlo antes con requestLocalPermissions().
   */
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
    if (!granted) return false;

    try {
      // Cancelar todas las notificaciones pendientes previas antes de reprogramar
      await this.cancelLocalReminders();

      const [hoursStr, minutesStr] = reminderTimeStr.split(':');
      const hours = parseInt(hoursStr, 10) || 20;
      const minutes = parseInt(minutesStr, 10) || 0;

      // Segundo recordatorio ("última llamada") a hora fija cerca del fin del día,
      // independiente de la hora elegida por el usuario para el primero.
      const LAST_CHANCE_HOUR = 22;
      const LAST_CHANCE_MINUTE = 30;

      const now = new Date();
      const todayReminderTime = new Date(now.getFullYear(), now.getMonth(), now.getDate(), hours, minutes, 0);
      const todayLastChanceTime = new Date(now.getFullYear(), now.getMonth(), now.getDate(), LAST_CHANCE_HOUR, LAST_CHANCE_MINUTE, 0);

      // Incluir el día de hoy únicamente si el usuario NO ha leído hoy Y la hora del recordatorio es en el futuro
      const includeToday = !hasReadToday && todayReminderTime.getTime() > now.getTime();
      const includeTodayLastChance = !hasReadToday && todayLastChanceTime.getTime() > now.getTime();
      const startOffset = includeToday ? 0 : 1;
      const endOffset = startOffset + 6;

      const streakText = currentStreak + ' ' + (currentStreak === 0 ? 'día' : 'días');
      // Con libro activo, cada mensaje lo menciona por nombre (mas personalizado que
      // el generico "tu libro"/"tu lectura"); sin libro, cae al texto original.
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
          const msgIndex = Math.abs(dayOffset) % messages.length;
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

      // Segundo recordatorio del día ("última llamada"), a hora fija, mismo rango de 7 días.
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

  /**
   * Guarda la hora de recordatorio diario en storage + perfil (sin reprogramar la
   * ráfaga: cada llamador decide cuándo y con qué datos reprogramar). Usado tanto
   * en el Onboarding como en Ajustes.
   */
  async persistReminderTime(reminderTimeStr) {
    await StorageService.set('reminder_time', reminderTimeStr);
    await ApiService.updateProfile({ reminder_time: reminderTimeStr });
  },

  /**
   * Elimina el token push de la API y limpia el almacenamiento local del token.
   */
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

  /**
   * Programa una notificación local de prueba tras N segundos (por defecto 3s).
   */
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

  /**
   * Desregistra el push y cancela recordatorios locales pendientes. Se usa al
   * cerrar sesion o eliminar la cuenta: en ambos casos no tiene sentido seguir
   * notificando a un usuario que ya no esta logueado en este dispositivo.
   */
  async cleanupOnLogout() {
    await this.unregisterPushToken();
    await this.cancelLocalReminders();
  },

  /**
   * Cancela cualquier recordatorio diario ya programado (pendiente de dispararse),
   * sin tocar el token push. Se usa al apagar la categoria "Recordatorio de lectura"
   * en Ajustes.
   */
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

  /**
   * Limpia las notificaciones Push remotas entregadas (ej. toques de amigos).
   * Se invoca al abrir la app o regresar a ella desde segundo plano.
   */
  async clearDeliveredPushNotifications() {
    if (!Capacitor.isNativePlatform()) return;
    try {
      await PushNotifications.removeAllDeliveredNotifications();
      console.log('[NotificationService] Notificaciones push entregadas limpiadas.');
    } catch (e) {
      console.warn('Error al limpiar notificaciones push:', e.message || e);
    }
  },

  /**
   * Limpia las notificaciones locales entregadas (borra tambien el badge del
   * icono como efecto colateral). Se invoca tras completar la lectura del dia.
   */
  async clearDeliveredLocalNotifications() {
    if (!Capacitor.isNativePlatform()) return;
    try {
      await LocalNotifications.removeAllDeliveredNotifications();
      console.log('[NotificationService] Notificaciones locales y badge de lectura limpiados tras leer.');
    } catch (e) {
      console.warn('Error al limpiar notificaciones locales:', e.message || e);
    }
  },

  /**
   * Engancha listeners de eventos push y locales (token recibido, notificacion
   * tocada, etc). No depende de userId ni de permiso, se puede llamar apenas
   * arranca la app (ver useAppLifecycle). Idempotente.
   */
  async attachListeners() {
    if (!Capacitor.isNativePlatform() || listenersRegistered) return;
    listenersRegistered = true;

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

    // Escuchar posibles errores de registro
    await PushNotifications.addListener('registrationError', (error) => {
      console.warn('Error en registro de Push Notifications:', error);
    });

    // Escuchar cuando llega una notificación Push estando la app en primer plano.
    // No se muestra Toast aca: el payload de FCMService incluye un bloque
    // 'notification' (no es data-only), asi que Android ya la muestra solo en
    // la barra de estado aunque la app este abierta — un Toast manual aca
    // duplicaba el aviso.
    await PushNotifications.addListener('pushNotificationReceived', (notification) => {
      console.log('[PushReceived]', notification);
    });

    // Escuchar al tocar una notificación Push desde la barra de estado. El destino
    // depende del tipo: un nuevo seguidor lleva a SU perfil (el ranking solo muestra
    // a quienes yo sigo, no a quienes me siguen a mi, asi que ahi no aparece), un
    // toque lleva a inicio (la accion pedida es leer hoy, no mirar el ranking).
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
