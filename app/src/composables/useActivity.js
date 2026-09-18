import { ref } from 'vue';
import { ApiService } from '@/services/api';

// Singleton a nivel de modulo (mismo patron que useCurrentUser): el punto rojo
// en el header vive en AppPage, montado por cada tab, asi que todas comparten
// el mismo hasNew en vez de refetchear cada vez que se cambia de tab.
const hasNew = ref(false);
const activity = ref([]);
const isLoading = ref(false);
let loaded = false;

export function useActivity() {
  const refresh = async ({ force = false } = {}) => {
    if (loaded && !force) return;
    loaded = true;
    isLoading.value = true;
    try {
      const res = await ApiService.getActivity();
      if (res.success) {
        hasNew.value = res.has_new;
        activity.value = res.activity || [];
      }
    } catch (e) {
      console.warn('No se pudo cargar la actividad:', e.message);
    } finally {
      isLoading.value = false;
    }
  };

  const markChecked = async () => {
    hasNew.value = false;
    try {
      await ApiService.checkActivity();
    } catch (e) {
      console.warn('No se pudo marcar la actividad como leida:', e.message);
    }
  };

  const reset = () => {
    hasNew.value = false;
    activity.value = [];
    loaded = false;
  };

  return { hasNew, activity, isLoading, refresh, markChecked, reset };
}
