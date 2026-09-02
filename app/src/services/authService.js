import { SignInWithApple } from '@capacitor-community/apple-sign-in';
import { GoogleAuth } from '@codetrix-studio/capacitor-google-auth';
import { Capacitor } from '@capacitor/core';
import { ApiService } from './api';

export const AuthService = {
  /**
   * Login con Sign in with Apple (iOS / Web).
   */
  async loginWithApple() {
    try {
      if (Capacitor.getPlatform() === 'ios') {
        const result = await SignInWithApple.authorize({
          clientId: 'me.biblingo.app',
          redirectURI: 'https://biblingo.me/api/auth/apple/callback',
          scopes: 'email name',
        });

        const response = result.response;
        const displayName = response.givenName ? `${response.givenName} ${response.familyName || ''}`.trim() : 'Lector Apple';

        return await ApiService.socialLogin({
          provider: 'apple',
          id_token: response.user || response.identityToken,
          email: response.email,
          display_name: displayName,
          platform: 'ios'
        });
      } else {
        // Fallback Web o Android para Apple Sign-In
        return this.demoLogin('Apple User', 'apple');
      }
    } catch (e) {
      console.warn('Falló Sign in with Apple:', e);
      throw e;
    }
  },

  /**
   * Login con Google Sign-In (Android / Web).
   */
  async loginWithGoogle() {
    try {
      if (Capacitor.isNativePlatform()) {
        const googleUser = await GoogleAuth.signIn();
        return await ApiService.socialLogin({
          provider: 'google',
          id_token: googleUser.id || googleUser.authentication.idToken,
          email: googleUser.email,
          display_name: googleUser.name || 'Lector Google',
          platform: Capacitor.getPlatform()
        });
      } else {
        return this.demoLogin('Lector Google', 'google');
      }
    } catch (e) {
      console.warn('Falló Google Sign-In:', e);
      return this.demoLogin('Lector Google', 'google');
    }
  },

  /**
   * Login de Demostración para desarrollo y pruebas rápidas.
   */
  async demoLogin(name = 'Lector Apasionado', provider = 'demo') {
    const demoId = 'demo_' + Math.random().toString(36).substring(2, 9);
    return await ApiService.socialLogin({
      provider: provider,
      id_token: demoId,
      email: `${demoId}@biblingo.me`,
      display_name: name,
      platform: Capacitor.getPlatform() || 'web'
    });
  }
};
