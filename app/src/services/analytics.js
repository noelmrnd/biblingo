import { FirebaseAnalytics } from '@capacitor-firebase/analytics';

export const AnalyticsService = {
  async init() {
    await FirebaseAnalytics.setEnabled({ enabled: true });
  },

  async setUser(userId) {
    await FirebaseAnalytics.setUserId({ userId });
  },

  async logEvent(name, params = {}) {
    await FirebaseAnalytics.logEvent({ name, params });
  },
};
