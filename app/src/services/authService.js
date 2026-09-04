import { SocialLogin } from '@capgo/capacitor-social-login';
import { Capacitor } from '@capacitor/core';
import { ApiService } from './api';
import { getAppleConfig, GOOGLE_CLIENT_ID, GOOGLE_IOS_CLIENT_ID } from '../constants';

let socialLoginInitialized = false;

async function ensureInitialized() {
  if (socialLoginInitialized) return;

  const platform = Capacitor.getPlatform();

  try {
    const config = {
      apple: getAppleConfig(platform),
    };

    if (GOOGLE_CLIENT_ID || GOOGLE_IOS_CLIENT_ID) {
      config.google = {
        webClientId: GOOGLE_CLIENT_ID,
        iOSClientId: GOOGLE_IOS_CLIENT_ID,
      };
    }
    await SocialLogin.initialize(config);
    socialLoginInitialized = true;
  } catch (err) {
    console.warn('Error al inicializar SocialLogin:', err);
  }
}

export const AuthService = {
  /**
   * Login con Sign in with Apple (Exclusivo iOS nativo).
   */
  async loginWithApple() {
    try {
      if (Capacitor.getPlatform() === 'ios') {
        await ensureInitialized();

        const res = await SocialLogin.login({
          provider: 'apple',
          options: {
            scopes: ['email', 'name'],
          },
        });

        const response = res.result;
        const givenName = response.profile?.givenName;
        const familyName = response.profile?.familyName;
        const displayName = givenName ? `${givenName} ${familyName || ''}`.trim() : 'Lector Apple';

        return await ApiService.socialLogin({
          provider: 'apple',
          id_token: response.profile?.user || response.idToken,
          email: response.profile?.email,
          display_name: displayName,
          platform: 'ios'
        });
      } else {
        throw new Error('Sign in with Apple solo está disponible en dispositivos iOS.');
      }
    } catch (e) {
      console.warn('Falló Sign in with Apple:', e);
      throw e;
    }
  },

  /**
   * Login con Google Sign-In (Android / iOS / Web).
   */
  async loginWithGoogle() {
    try {
      const clientId = (import.meta.env.VITE_GOOGLE_CLIENT_ID || '').trim();
      if (!clientId) {
        throw new Error('Debes configurar tu Client ID de Google (VITE_GOOGLE_CLIENT_ID) en el archivo .env para poder usar Google Sign-In.');
      }

      await ensureInitialized();

      const res = await SocialLogin.login({
        provider: 'google',
        options: {
          scopes: ['email', 'profile'],
        },
      });

      const response = res.result;
      const idToken = response.idToken || (response.accessToken ? response.accessToken.token : null) || response.profile?.id;

      return await ApiService.socialLogin({
        provider: 'google',
        id_token: idToken,
        email: response.profile?.email,
        display_name: response.profile?.name || 'Lector Google',
        platform: Capacitor.getPlatform() || 'web'
      });
    } catch (e) {
      console.warn('Falló Google Sign-In:', e);
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
      email: `${devId}@app.biblingo.me`,
      display_name: cleanName,
      platform: Capacitor.getPlatform() || 'web'
    });
  }
};
