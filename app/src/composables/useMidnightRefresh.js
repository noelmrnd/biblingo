import { ref } from 'vue';
import { toLocalDateString } from '../utils/dateFormatter';

const CHECK_INTERVAL_MS = 60 * 1000;

// Singleton: un solo timer para toda la app, no uno por componente que lo use.
// "today" es la unica fuente de verdad de "que dia es hoy" en tiempo real.
const today = ref(new Date());

const checkNow = () => {
  const now = new Date();
  if (toLocalDateString(now) !== toLocalDateString(today.value)) {
    today.value = now;
  }
};

setInterval(checkNow, CHECK_INTERVAL_MS);

/**
 * Suscribirse a "hoy" es reactivo por defecto: cualquier computed/watch que
 * lea today.value se actualiza solo al cruzar la medianoche (revisado cada
 * minuto). Si un componente necesita reaccionar con un efecto (ej. recargar
 * datos del mes), usa watch(today, (now, prev) => ...) directamente — no hay
 * API custom, es el mismo patron de siempre en Vue.
 *
 * checkNow() tambien se expone para forzar la revision al instante (ej. en
 * onActivated, al volver de otro tab, por si el timer se pauso con la app en
 * background) sin esperar hasta el proximo tick del intervalo.
 */
export function useMidnightRefresh() {
  return { today, checkNow };
}
