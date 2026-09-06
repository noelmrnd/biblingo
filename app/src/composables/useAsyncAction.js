import { ref } from 'vue';
import { ToastService } from '../services/toast';

/**
 * Envuelve una accion async con el patron repetido en toda la app:
 * loading + toast de exito/error. successMsg/errorMsg aceptan string o
 * funcion (result) => string para mensajes dinamicos.
 */
export function useAsyncAction() {
  const loading = ref(false);

  const run = async (fn, { successMsg, errorMsg } = {}) => {
    if (loading.value) return;
    loading.value = true;
    try {
      const res = await fn();
      if (successMsg) {
        ToastService.success(typeof successMsg === 'function' ? successMsg(res) : successMsg);
      }
      return res;
    } catch (e) {
      ToastService.error(e.message || errorMsg || 'Ocurrió un error.');
      return undefined;
    } finally {
      loading.value = false;
    }
  };

  return { loading, run };
}
