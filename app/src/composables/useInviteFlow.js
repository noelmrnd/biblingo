import { StorageService } from '../services/storage';
import { ApiService } from '../services/api';
import { ToastService } from '../services/toast';

const PENDING_INVITE_KEY = 'pending_invite_username';

/**
 * Maneja el flujo de invitaciones por username: procesa inmediatamente si hay sesión,
 * o guarda el username pendiente para procesarlo después del login/registro.
 */
export function useInviteFlow({ getCurrentUser, onFriendAdded }) {
  let lastProcessedUsername = null;
  let lastProcessedTime = 0;

  const processInvite = async (username, user = getCurrentUser()) => {
    if (!username) return;

    // Prevenir reprocesamiento duplicado inmediato (ej: cold-start + listener)
    const now = Date.now();
    if (username === lastProcessedUsername && now - lastProcessedTime < 3000) {
      return;
    }
    lastProcessedUsername = username;
    lastProcessedTime = now;

    // Si no hay sesión iniciada, almacenar para procesar después del login/registro
    if (!user || !user.id) {
      await StorageService.set(PENDING_INVITE_KEY, username);
      ToastService.info(`Invitación (@${username}) guardada. Inicia sesión para conectar con tu amigo.`);
      return;
    }

    try {
      const res = await ApiService.followUser(username);
      if (res.success) {
        ToastService.success(res.message || `¡Ahora sigues a ${res.friend?.display_name}! 👥🎉`);
        onFriendAdded?.();
      }
    } catch (e) {
      ToastService.error(e.message || 'Error al procesar la invitación.');
    } finally {
      await StorageService.remove(PENDING_INVITE_KEY);
    }
  };

  const resolvePendingInvite = async (user = getCurrentUser()) => {
    const pendingInvite = await StorageService.get(PENDING_INVITE_KEY);
    if (pendingInvite) {
      await processInvite(pendingInvite, user);
    }
  };

  return { processInvite, resolvePendingInvite };
}
