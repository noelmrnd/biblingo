import { App } from '@capacitor/app';
import { Capacitor } from '@capacitor/core';

export const DeepLinkService = {
  /**
   * Registra el listener de deep links en Nativo. Devuelve el handle de Capacitor
   * (con .remove()) o null en Web — ahi la ruta /invite/:username (ver router/index.js
   * y App.vue) ya cubre el caso por su cuenta via vue-router, sin necesitar parsear
   * la URL cruda ni pisarla con history.replaceState.
   */
  async initListener(onInviteReceived) {
    if (!Capacitor.isNativePlatform()) return null;

    const handle = await App.addListener('appUrlOpen', (event) => {
      const url = event.url;
      const username = this.extractUsername(url);
      if (username) {
        onInviteReceived(username);
      }
    });

    // Cold start: comprobar si la app fue lanzada mediante una URL profunda
    try {
      const launchUrl = await App.getLaunchUrl();
      if (launchUrl && launchUrl.url) {
        const username = this.extractUsername(launchUrl.url);
        if (username) {
          onInviteReceived(username);
        }
      }
    } catch (e) {
      console.warn('Error al verificar launchUrl:', e);
    }

    return handle;
  },

  extractUsername(urlStr) {
    if (!urlStr) return null;
    try {
      if (urlStr.includes('/invite/')) {
        const parts = urlStr.split('/invite/');
        if (parts[1]) {
          return parts[1].split('/')[0].split('?')[0].toLowerCase().trim();
        }
      }
      const urlObj = new URL(urlStr, window.location.origin);
      return urlObj.searchParams.get('invite') ? urlObj.searchParams.get('invite').toLowerCase().trim() : null;
    } catch (e) {
      return null;
    }
  }
};
