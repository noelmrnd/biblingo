import { App } from '@capacitor/app';
import { Capacitor } from '@capacitor/core';

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
        const code = this.extractInviteCode(url);
        if (code) {
          onInviteReceived(code);
        }
      });

      // Cold start: comprobar si la app fue lanzada mediante una URL profunda
      try {
        const launchUrl = await App.getLaunchUrl();
        if (launchUrl && launchUrl.url) {
          const code = this.extractInviteCode(launchUrl.url);
          if (code) {
            onInviteReceived(code);
          }
        }
      } catch (e) {
        console.warn('Error al verificar launchUrl:', e);
      }

      return handle;
    } else {
      // Comprobar URL inicial en Web
      const currentUrl = window.location.href;
      const code = this.extractInviteCode(currentUrl);
      if (code) {
        onInviteReceived(code);
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

  extractInviteCode(urlStr) {
    if (!urlStr) return null;
    try {
      if (urlStr.includes('/invite/')) {
        const parts = urlStr.split('/invite/');
        if (parts[1]) {
          return parts[1].split('/')[0].split('?')[0].toUpperCase().trim();
        }
      }
      const urlObj = new URL(urlStr, window.location.origin);
      return urlObj.searchParams.get('invite') ? urlObj.searchParams.get('invite').toUpperCase().trim() : null;
    } catch (e) {
      return null;
    }
  }
};

