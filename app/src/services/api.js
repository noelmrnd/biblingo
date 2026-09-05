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

  // El backend deriva el usuario del token Bearer, no de un user_id en la URL.
  async getReadingStatus() {
    return request('/reading/status');
  },

  async logReading(userId, reaction = null) {
    return request('/reading/log', {
      method: 'POST',
      body: JSON.stringify({ user_id: userId, reaction })
    });
  },

  async getFriends(userId) {
    return request(`/friends?user_id=${userId}`);
  },

  // Perfil completo de un amigo (o el propio) en una sola llamada: stats + historial
  // de 30 dias + contadores de seguidores/seguidos + amigos en comun.
  async getFriendProfile(friendId) {
    return request(`/friends/profile?friend_id=${friendId}`);
  },

  // Seguir es instantaneo (sin aprobacion), como en Duolingo.
  async followUser(inviteCode) {
    return request('/friends/follow', {
      method: 'POST',
      body: JSON.stringify({ invite_code: inviteCode })
    });
  },

  async unfollowUser(friendId) {
    return request('/friends/unfollow', {
      method: 'POST',
      body: JSON.stringify({ friend_id: friendId })
    });
  },

  async updateProfile(userId, data) {
    return request('/user/update', {
      method: 'POST',
      body: JSON.stringify({ user_id: userId, ...data })
    });
  },

  async registerPushToken(userId, pushToken, platform = 'ios') {
    return request('/user/push-token', {
      method: 'POST',
      body: JSON.stringify({ user_id: userId, push_token: pushToken, platform })
    });
  },

  async unregisterPushToken(userId, pushToken) {
    return request(`/user/push-token?user_id=${userId}`, {
      method: 'DELETE',
      body: JSON.stringify({ push_token: pushToken })
    });
  },

  async nudgeFriend(userId, friendId) {
    return request('/friends/nudge', {
      method: 'POST',
      body: JSON.stringify({ user_id: userId, friend_id: friendId })
    });
  },

};
