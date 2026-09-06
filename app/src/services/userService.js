import { StorageService } from './storage';
import { ApiService, saveAuthToken, clearAuthToken, getAuthToken } from './api';

/**
 * El usuario completo ya no se cachea en disco — solo el token de auth. Al
 * abrir la app, initSession() usa ese token para pedir /auth/me y reconstruir
 * el usuario en memoria. Evita mantener una copia potencialmente desactualizada
 * en Preferences (racha, seguidores, prefs de notificacion cambian seguido).
 */
export const UserService = {
  async initSession() {
    const token = await getAuthToken();
    if (!token) return null;

    const res = await ApiService.getCurrentUser();
    if (!res.success) return null;

    // Sincronizar zona horaria si cambió con respecto a la guardada en el perfil del usuario
    const deviceTz = Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC';
    if (res.user.timezone !== deviceTz) {
      try {
        await ApiService.updateProfile({ timezone: deviceTz });
        res.user.timezone = deviceTz;
      } catch (e) {
        console.warn('No se pudo sincronizar la zona horaria en segundo plano:', e.message);
      }
    }

    return res.user;
  },

  /** Guarda el token de auth. Se llama solo en el login inicial. */
  async saveToken(token) {
    await saveAuthToken(token);
  },

  async clearSession() {
    await StorageService.remove('push_token');
    await StorageService.remove('push_user_id');
    await clearAuthToken();
  }
};
