import { App } from '@capacitor/app';
import { Capacitor } from '@capacitor/core';

export const DeepLinkService = {
  initListener(onInviteReceived) {
    // Escuchar cambios de URL en Native App
    if (Capacitor.isNativePlatform()) {
      App.addListener('appUrlOpen', (event) => {
        const url = event.url;
        const code = this.extractInviteCode(url);
        if (code) {
          onInviteReceived(code);
        }
      });
    }

    // Comprobar URL inicial en Web
    const currentUrl = window.location.href;
    const code = this.extractInviteCode(currentUrl);
    if (code) {
      onInviteReceived(code);
    }
  },

  extractInviteCode(urlStr) {
    try {
      if (urlStr.includes('/invite/')) {
        const parts = urlStr.split('/invite/');
        if (parts[1]) {
          return parts[1].split('/')[0].split('?')[0].toUpperCase().trim();
        }
      }
      const urlObj = new URL(urlStr);
      return urlObj.searchParams.get('invite') ? urlObj.searchParams.get('invite').toUpperCase().trim() : null;
    } catch (e) {
      return null;
    }
  }
};
