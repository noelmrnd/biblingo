import { App } from '@capacitor/app';
import { Capacitor } from '@capacitor/core';

// Capturada al cargar el módulo (antes de que vue-router resuelva/redirija la ruta inicial),
// para no perder un deep link tipo /invite/USERNAME si el router lo saca de la URL primero.
const INITIAL_URL = typeof window !== 'undefined' ? window.location.href : '';

export const DeepLinkService = {
  /**
   * Registra el listener de deep links. Devuelve el handle de Capacitor (con .remove())
   * cuando aplica, o null en Web, para permitir limpieza en onUnmounted.
   */
  async initListener(onInviteReceived) {
    // Escuchar cambios de URL en Native App
    if (Capacitor.isNativePlatform()) {
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
    } else {
      // Comprobar URL inicial en Web (capturada antes de que el router redirija)
      const username = this.extractUsername(INITIAL_URL);
      if (username) {
        onInviteReceived(username);
        // Limpiar URL en el navegador para evitar reprocesar si el usuario recarga
        try {
          if (window.history && window.history.replaceState) {
            window.history.replaceState({}, document.title, window.location.origin + '/');
          }
        } catch (e) {}
      }
      return null;
    }
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
