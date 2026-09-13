import { reactive } from 'vue';
import { ApiService } from '@/services/api';
import { ToastService } from '@/services/toast';

/**
 * Envio de "toque" a un amigo, indexado por id para soportar tanto una lista
 * (varios toques en paralelo) como un solo amigo (perfil individual).
 */
export function useNudge() {
  const nudged = reactive({});
  const loading = reactive({});

  const markNudged = (id) => {
    nudged[id] = true;
  };

  const sendNudge = async (id) => {
    if (nudged[id] || loading[id]) return;
    loading[id] = true;
    try {
      await ApiService.nudgeFriend(id);
      nudged[id] = true;
    } catch (e) {
      ToastService.error(e.message || 'No se pudo enviar el recordatorio.');
    } finally {
      loading[id] = false;
    }
  };

  return { nudged, loading, markNudged, sendNudge };
}
