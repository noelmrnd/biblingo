import { StorageService } from './storage';
import { ApiService, saveAuthToken, clearAuthToken } from './api';

const USER_STORAGE_KEY = 'user_session';

export const UserService = {
  /**
   * Carga la sesión guardada y verifica en segundo plano si la zona horaria del dispositivo cambió.
   */
  async initSession() {
    const user = await StorageService.get(USER_STORAGE_KEY);
    if (!user) return null;

    // Sincronizar zona horaria únicamente si cambió con respecto a la guardada en el perfil del usuario
    const deviceTz = Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC';
    if (user.timezone !== deviceTz) {
      try {
        await ApiService.updateProfile({ timezone: deviceTz });
        user.timezone = deviceTz;
        await StorageService.set(USER_STORAGE_KEY, user);
      } catch (e) {
        console.warn('No se pudo sincronizar la zona horaria en segundo plano:', e.message);
      }
    }

    return user;
  },

  /**
   * Guarda o actualiza la sesión del usuario en almacenamiento nativo.
   * El token solo se pasa (y se persiste) en el login inicial; las actualizaciones
   * posteriores del objeto user no lo tocan.
   */
  async saveSession(user, token = null) {
    if (!user) return;
    await StorageService.set(USER_STORAGE_KEY, user);
    if (token) {
      await saveAuthToken(token);
    }
  },

  /**
   * Elimina los datos de sesión almacenados.
   */
  async clearSession() {
    await StorageService.remove(USER_STORAGE_KEY);
    await StorageService.remove('push_token');
    await StorageService.remove('push_user_id');
    await clearAuthToken();
  }
};
