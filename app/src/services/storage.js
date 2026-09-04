import { Preferences } from '@capacitor/preferences';

const configurePromise = Preferences.configure({ group: 'app' }).catch((err) => {
  console.warn('Error configuring Preferences group:', err);
});

async function ensureConfigured() {
  await configurePromise;
}

export const StorageService = {
  async set(key, value) {
    await ensureConfigured();
    try {
      const stringValue = typeof value === 'object' ? JSON.stringify(value) : String(value);
      await Preferences.set({ key, value: stringValue });
    } catch (e) {
      console.warn(`Error al guardar en Storage (${key}):`, e);
    }
  },

  async get(key, defaultValue = null) {
    await ensureConfigured();
    try {
      const { value } = await Preferences.get({ key });
      if (value === null || value === undefined) {
        return defaultValue;
      }
      try {
        return JSON.parse(value);
      } catch {
        return value;
      }
    } catch (e) {
      return defaultValue;
    }
  },

  async remove(key) {
    await ensureConfigured();
    try {
      await Preferences.remove({ key });
    } catch (e) {}
  },

  async clear() {
    await ensureConfigured();
    try {
      await Preferences.clear();
    } catch (e) {}
  }
};


