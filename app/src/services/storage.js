import { Preferences } from '@capacitor/preferences';

export const StorageService = {
  async set(key, value) {
    try {
      const stringValue = typeof value === 'object' ? JSON.stringify(value) : String(value);
      await Preferences.set({ key, value: stringValue });
    } catch (e) {
      console.warn(`Error al guardar en Storage (${key}):`, e);
      try {
        const stringValue = typeof value === 'object' ? JSON.stringify(value) : String(value);
        localStorage.setItem(key, stringValue);
      } catch (err) {}
    }
  },

  async get(key, defaultValue = null) {
    try {
      const { value } = await Preferences.get({ key });
      if (value === null || value === undefined) {
        // Fallback a localStorage por migración
        const localVal = localStorage.getItem(key);
        if (localVal !== null) {
          try { return JSON.parse(localVal); } catch { return localVal; }
        }
        return defaultValue;
      }
      try {
        return JSON.parse(value);
      } catch {
        return value;
      }
    } catch (e) {
      // Fallback si falla el plugin
      const localVal = localStorage.getItem(key);
      if (localVal !== null) {
        try { return JSON.parse(localVal); } catch { return localVal; }
      }
      return defaultValue;
    }
  },

  async remove(key) {
    try {
      await Preferences.remove({ key });
    } catch (e) {}
    try {
      localStorage.removeItem(key);
    } catch (e) {}
  },

  async clear() {
    try {
      await Preferences.clear();
    } catch (e) {}
    try {
      localStorage.clear();
    } catch (e) {}
  }
};
