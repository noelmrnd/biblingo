import { Haptics, ImpactStyle, NotificationType } from '@capacitor/haptics';

// Wrapper con try/catch: en web (sin plugin nativo) algunas implementaciones
// tiran error en vez de resolver silenciosamente, y no queremos que un fallo
// de haptics rompa el flujo de la app.
const safe = async (fn) => {
  try {
    await fn();
  } catch {
    // no-op: dispositivo sin soporte de haptics
  }
};

export const HapticsService = {
  light() {
    return safe(() => Haptics.impact({ style: ImpactStyle.Light }));
  },

  medium() {
    return safe(() => Haptics.impact({ style: ImpactStyle.Medium }));
  },

  heavy() {
    return safe(() => Haptics.impact({ style: ImpactStyle.Heavy }));
  },

  success() {
    return safe(() => Haptics.notification({ type: NotificationType.Success }));
  },

  warning() {
    return safe(() => Haptics.notification({ type: NotificationType.Warning }));
  },

  error() {
    return safe(() => Haptics.notification({ type: NotificationType.Error }));
  }
};
