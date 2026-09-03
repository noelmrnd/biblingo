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
          clientId: 'com.libringo.app',
          redirectURI: 'https://libringo.com/api/auth/apple/callback',
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
        throw new Error('Sign in with Apple solo está disponible en dispositivos iOS.');
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
      const clientId = import.meta.env.VITE_GOOGLE_CLIENT_ID || '';
      
      // Inicializar GoogleAuth en entorno Web / Nativo
      try {
        await GoogleAuth.initialize({
          clientId: clientId,
          scopes: ['profile', 'email'],
          grantOfflineAccess: false,
        });
      } catch (initErr) {
        // Ignorar si ya está inicializado previamente
      }

      const googleUser = await GoogleAuth.signIn();
      return await ApiService.socialLogin({
        provider: 'google',
        id_token: googleUser.id || googleUser.authentication?.idToken || googleUser.serverAuthCode,
        email: googleUser.email,
        display_name: googleUser.name || 'Lector Google',
        platform: Capacitor.getPlatform() || 'web'
      });
    } catch (e) {
      console.warn('Falló Google Sign-In:', e);
      if (!import.meta.env.VITE_GOOGLE_CLIENT_ID && (e.message?.includes('grantOfflineAccess') || e.message?.includes('undefined') || !e.message)) {
        throw new Error('Debes configurar tu Client ID de Google (VITE_GOOGLE_CLIENT_ID) en el archivo .env para iniciar sesión en la Web.');
      }
      throw new Error(e.message || 'No se pudo iniciar sesión con Google.');
    }
  },

  /**
   * Login de desarrollo local (permite probar sin configurar OAuth en Google/Apple).
   */
  async devLogin(name = 'Lector Dev') {
    const cleanName = name.trim() || 'Lector Dev';
    const devId = 'dev_' + cleanName.toLowerCase().replace(/[^a-z0-9]/g, '_');
    return await ApiService.socialLogin({
      provider: 'dev',
      id_token: devId,
      email: `${devId}@dev.libringo.com`,
      display_name: cleanName,
      platform: Capacitor.getPlatform() || 'web'
    });
  }
};
