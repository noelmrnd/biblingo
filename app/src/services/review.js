import { Capacitor, registerPlugin } from '@capacitor/core';
import { StorageService } from './storage';

const InAppReview = registerPlugin('InAppReview');

const STORAGE_KEY_LAST_REVIEW = 'last_review_requested_at';

export const ReviewService = {
  /**
   * Determina si el entorno actual soporta In-App Review (dispositivo nativo iOS/Android).
   */
  isAvailable() {
    return Capacitor.isNativePlatform();
  },

  /**
   * Solicita la reseña dentro de la app (In-App Review).
   * 
   * @param {Object} options
   * @param {number} [options.cooldownDays=30] - Días mínimos que deben transcurrir antes de volver a solicitarlo automáticamente. Pasa 0 para ignorar el cooldown.
   * @param {boolean} [options.force=false] - Si es true, ignora el cooldown de almacenamiento local.
   * @returns {Promise<boolean>} true si se ejecutó la llamada a la API nativa, false si se omitió por entorno o cooldown.
   */
  async requestReview({ cooldownDays = 30, force = false } = {}) {
    if (!this.isAvailable()) {
      console.info('[ReviewService] In-App Review no está disponible en entorno web/navegador.');
      return false;
    }

    try {
      if (!force && cooldownDays > 0) {
        const lastRequested = await StorageService.get(STORAGE_KEY_LAST_REVIEW);
        if (lastRequested) {
          const daysElapsed = (Date.now() - Number(lastRequested)) / (1000 * 60 * 60 * 24);
          if (daysElapsed < cooldownDays) {
            console.info(`[ReviewService] Solicitud omitida por cooldown (${Math.round(daysElapsed)} / ${cooldownDays} días transcurridos).`);
            return false;
          }
        }
      }

      // Llamada nativa a Google Play In-App Review o Apple SKStoreReviewController
      await InAppReview.requestReview();

      // Guardar la marca de tiempo de la última solicitud
      await StorageService.set(STORAGE_KEY_LAST_REVIEW, Date.now());
      console.info('[ReviewService] requestReview invocado exitosamente.');
      return true;
    } catch (error) {
      console.warn('[ReviewService] Error al solicitar In-App Review:', error);
      return false;
    }
  }
};
