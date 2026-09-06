import { API_BASE_URL } from '../constants';
import { StorageService } from './storage';

export { API_BASE_URL };

const AUTH_TOKEN_KEY = 'auth_token';

export async function saveAuthToken(token) {
  await StorageService.set(AUTH_TOKEN_KEY, token);
}

export async function clearAuthToken() {
  await StorageService.remove(AUTH_TOKEN_KEY);
}

export async function getAuthToken() {
  return StorageService.get(AUTH_TOKEN_KEY);
}

let unauthorizedHandler = null;

/**
 * Registra el callback a invocar cuando el servidor responde 401 (token invalido,
 * expirado o revocado) — App.vue lo usa para forzar el logout automáticamente.
 */
export function setUnauthorizedHandler(handler) {
  unauthorizedHandler = handler;
}

export async function request(endpoint, options = {}) {
  const url = `${API_BASE_URL}${endpoint}`;
  const headers = {
    'Content-Type': 'application/json',
    ...(options.headers || {})
  };

  // Autentica la peticion ante el backend; el login (/auth/social) todavia no tiene
  // token que mandar, todo lo demas lo necesita desde que el servidor exige sesion.
  const token = await StorageService.get(AUTH_TOKEN_KEY);
  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  try {
    const response = await fetch(url, {
      ...options,
      headers
    });
    const data = await response.json();
    if (!response.ok) {
      if (response.status === 401 && endpoint !== '/auth/social') {
        unauthorizedHandler?.();
      }
      throw new Error(data.error || 'Error de conexión con la API');
    }
    return data;
  } catch (error) {
    console.warn(`[API Error] ${endpoint}:`, error.message);
    throw error;
  }
}

export const ApiService = {
  async socialLogin(payload) {
    const deviceTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC';
    return request('/auth/social', {
      method: 'POST',
      body: JSON.stringify({ timezone: deviceTimezone, ...payload })
    });
  },

  // Perfil completo del usuario autenticado (misma forma que devuelve el login).
  // Se usa al abrir la app para reconstruir el usuario en memoria a partir de
  // solo el token guardado — no hay copia del user cacheada en disco.
  async getCurrentUser() {
    return request('/auth/me');
  },

  // El backend deriva el usuario del token Bearer, no de un user_id en la URL.
  async getReadingStatus() {
    return request('/reading/status');
  },

  // Dias leidos de un mes especifico, para el calendario del tracker mensual.
  async getReadingCalendar(year, month) {
    return request(`/reading/calendar?year=${year}&month=${month}`);
  },

  async logReading(reaction = null) {
    return request('/reading/log', {
      method: 'POST',
      body: JSON.stringify({ reaction })
    });
  },

  async getFriends() {
    return request('/friends');
  },

  // Perfil completo de un amigo (o el propio) en una sola llamada: stats + historial
  // de 30 dias + contadores de seguidores/seguidos + amigos en comun.
  async getFriendProfile(friendId) {
    return request(`/friends/profile?friend_id=${friendId}`);
  },

  // Seguir es instantaneo (sin aprobacion), como en Duolingo.
  async followUser(username) {
    return request('/friends/follow', {
      method: 'POST',
      body: JSON.stringify({ username })
    });
  },

  async unfollowUser(friendId) {
    return request('/friends/unfollow', {
      method: 'POST',
      body: JSON.stringify({ friend_id: friendId })
    });
  },

  // type: 'followers' | 'following'. Publica: puede consultarse la de cualquier usuario.
  async getFollowList(userId, type) {
    return request(`/friends/list?user_id=${userId}&type=${type}`);
  },

  async updateProfile(data) {
    return request('/user/update', {
      method: 'POST',
      body: JSON.stringify(data)
    });
  },

  // Datos minimos para la pantalla de Ajustes: nombre, usuario, correo, timezone,
  // recordatorio. Nada de racha/seguidores/historial (eso es getFriendProfile).
  async getSettings() {
    return request('/user/settings');
  },

  // prefs: objeto parcial, solo las claves que cambiaron (ver UserEntity::DEFAULT_NOTIFICATION_PREFS).
  async updateNotificationPrefs(prefs) {
    return request('/user/notification-prefs', {
      method: 'POST',
      body: JSON.stringify(prefs)
    });
  },

  async registerPushToken(pushToken, platform = 'ios') {
    return request('/user/push-token', {
      method: 'POST',
      body: JSON.stringify({ push_token: pushToken, platform })
    });
  },

  async unregisterPushToken(pushToken) {
    return request('/user/push-token', {
      method: 'DELETE',
      body: JSON.stringify({ push_token: pushToken })
    });
  },

  // type: 'idea' | 'bug' | 'other'
  async submitFeedback(type, message) {
    return request('/user/feedback', {
      method: 'POST',
      body: JSON.stringify({ type, message })
    });
  },

  async nudgeFriend(friendId) {
    return request('/friends/nudge', {
      method: 'POST',
      body: JSON.stringify({ friend_id: friendId })
    });
  },

  // Revoca el token de esta sesion (o todos con everywhere=true) en el servidor.
  async logout(everywhere = false) {
    return request('/auth/logout', {
      method: 'POST',
      body: JSON.stringify({ all: everywhere })
    });
  },

  // Soft delete: el servidor marca la cuenta como 'deleted' y su token deja de
  // servir de inmediato. No es reversible desde la app.
  async deleteAccount() {
    return request('/user/account', { method: 'DELETE' });
  },

};
