import { ref } from 'vue';
import { ApiService } from '../services/api';
import { monthCache } from '../utils/monthlyCalendarCache';

// Singleton a nivel de modulo: todas las vistas que llamen a useCurrentUser()
// comparten la misma referencia, en vez de que cada una guarde su propia copia
// del usuario (que es como se llegaba a pedir /reading/status por duplicado
// cuando Dashboard y Profile montaban casi al mismo tiempo).
const user = ref(null);
let lastFullRefresh = 0;
const FULL_REFRESH_TTL_MS = 10000;

// Se incrementa en cada login/logout. refreshProfile() lo captura antes de su
// await: si cambio mientras la request estaba en vuelo (logout, o login con
// otro usuario, sin recargar el proceso) descarta la respuesta en vez de
// pisar el usuario actual con datos de la sesion anterior.
let sessionEpoch = 0;

export function useCurrentUser() {
  const setUser = (newUser) => {
    sessionEpoch++;
    user.value = newUser;
  };

  const mergeUser = (partial) => {
    if (user.value) {
      user.value = { ...user.value, ...partial };
    }
  };

  // Cierra sesion o falla la restauracion: limpiar tambien monthCache
  // (module-scope, sobrevive a unmounts a proposito — por eso no se limpia
  // solo con desmontar MonthlyTracker) para que el siguiente login en el
  // mismo dispositivo no arrastre el calendario del usuario anterior.
  const clearUser = () => {
    sessionEpoch++;
    user.value = null;
    lastFullRefresh = 0;
    monthCache.clear();
  };

  /**
   * Marca que el usuario ya trae datos frescos de racha/reacciones/seguidores
   * (login y /auth/me devuelven el mismo superset que /reading/status), para
   * que refreshProfile() no repita la llamada si algo monta justo despues
   * dentro del TTL.
   */
  const markFreshLoad = () => {
    lastFullRefresh = Date.now();
  };

  /**
   * Refresca racha/reacciones/seguidores/etc desde /reading/status. Si otra vista ya
   * lo pidio hace menos de FULL_REFRESH_TTL_MS, no repite la peticion — solo devuelve
   * el usuario ya actualizado.
   */
  const refreshProfile = async ({ force = false } = {}) => {
    if (!user.value?.id) return null;

    const now = Date.now();
    if (!force && now - lastFullRefresh < FULL_REFRESH_TTL_MS) {
      return user.value;
    }
    lastFullRefresh = now;
    const epochAtStart = sessionEpoch;

    try {
      const res = await ApiService.getReadingStatus();
      if (res.success && sessionEpoch === epochAtStart) {
        mergeUser(res);
      }
      return user.value;
    } catch (e) {
      console.warn('No se pudo refrescar el usuario:', e.message);
      return user.value;
    }
  };

  return { user, setUser, mergeUser, clearUser, refreshProfile, markFreshLoad };
}
