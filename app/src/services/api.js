import { API_BASE_URL } from '../constants';

export { API_BASE_URL };

export async function request(endpoint, options = {}) {
  const url = `${API_BASE_URL}${endpoint}`;
  const headers = {
    'Content-Type': 'application/json',
    ...(options.headers || {})
  };

  try {
    const response = await fetch(url, {
      ...options,
      headers
    });
    const data = await response.json();
    if (!response.ok) {
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

  async getReadingStatus(userId) {
    return request(`/reading/status?user_id=${userId}`);
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

  async addFriend(userId, inviteCode) {
    return request('/friends/add', {
      method: 'POST',
      body: JSON.stringify({ user_id: userId, invite_code: inviteCode })
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
  }
};
