const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || (
  window.location.origin.includes('localhost') || window.location.origin.includes('127.0.0.1')
    ? 'http://localhost:8000/api'
    : 'https://biblingo.me/api'
);

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
    return request('/auth/social', {
      method: 'POST',
      body: JSON.stringify(payload)
    });
  },

  async getReadingStatus(userId) {
    return request(`/reading/status?user_id=${userId}`);
  },

  async logReading(userId) {
    return request('/reading/log', {
      method: 'POST',
      body: JSON.stringify({ user_id: userId })
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
  }
};
