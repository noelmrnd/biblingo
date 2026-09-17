import { StorageService } from '@/services/storage';
import { ApiService } from '@/services/api';
import { ToastService } from '@/services/toast';

const PENDING_INVITE_KEY = 'pending_invite_username';

/**
 * onFriendAdded compartido por App.vue e InviteView.vue: tras seguir al que invito,
 * navega a su perfil (o al listado de amigos si por algun motivo no vino el id).
 */
export function friendAddedRedirect(router) {
  return (friend) => {
    if (friend?.id) {
      router.push({ name: 'friend-profile', params: { id: friend.id } });
    } else {
      router.push({ name: 'friends' });
    }
  };
}

/**
 * Maneja el flujo de invitaciones por username: procesa inmediatamente si hay sesión,
 * o guarda el username pendiente para procesarlo después del login/registro.
 */
export function useInviteFlow({ getCurrentUser, onFriendAdded }) {
  let lastProcessedKey = null;
  let lastProcessedTime = 0;

  const processInvite = async (username, user = getCurrentUser()) => {
    if (!username) return;

    // Prevenir reprocesamiento duplicado inmediato (ej: cold-start + listener).
    // La key incluye si hay sesion o no: sin esto, el intento anonimo (cold-start,
    // antes de que la sesion se restaure) marcaba el username como "procesado" y
    // el intento real que viene despues via resolvePendingInvite (ya con sesion,
    // milisegundos/segundos mas tarde) quedaba descartado en silencio por este
    // mismo guard, sin llegar nunca a intentar el follow ni mostrar su error.
    const now = Date.now();
    const key = `${username}:${user?.id ? 'auth' : 'anon'}`;
    if (key === lastProcessedKey && now - lastProcessedTime < 3000) {
      return;
    }
    lastProcessedKey = key;
    lastProcessedTime = now;

    // Si no hay sesión iniciada, almacenar para procesar después del login/registro
    if (!user || !user.id) {
      await StorageService.set(PENDING_INVITE_KEY, username);
      // ToastService.info(`Invitación (@${username}) guardada. Inicia sesión para conectar con tu amigo.`);
      return;
    }

    try {
      const res = await ApiService.followUser(username);
      if (res.success) {
        ToastService.success(res.message || `¡Ahora sigues a ${res.friend?.display_name}! 👥🎉`);
        onFriendAdded?.(res.friend);
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
