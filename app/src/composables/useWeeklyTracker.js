import { ref, computed } from 'vue';
import { ApiService } from '../services/api';
import { toLocalDateString } from '../utils/dateFormatter';

/**
 * Carga el historial de lectura de un usuario (propio o de un amigo, validado por el
 * backend vía friendship) y arma el tracker semanal (L-D).
 */
export function useWeeklyTracker(userId, friendId = null) {
  const loading = ref(true);
  const hasReadToday = ref(null);
  const historyDates = ref([]);

  const weekDays = computed(() => {
    const labels = ['L', 'M', 'X', 'J', 'V', 'S', 'D'];
    const today = new Date();
    const currentDayOfWeek = (today.getDay() + 6) % 7; // 0 = Lunes, 6 = Domingo

    const monday = new Date(today);
    monday.setDate(today.getDate() - currentDayOfWeek);

    return labels.map((label, i) => {
      const d = new Date(monday);
      d.setDate(monday.getDate() + i);
      const dateStr = toLocalDateString(d);
      const isToday = (i === currentDayOfWeek);
      const isRead = historyDates.value.includes(dateStr) || (isToday && hasReadToday.value);

      return { label, dateNum: d.getDate(), dateStr, isToday, isRead };
    });
  });

  const load = async () => {
    loading.value = true;
    try {
      const res = friendId
        ? await ApiService.getFriendHistory(userId, friendId)
        : await ApiService.getReadingStatus(userId);
      if (res.success) {
        hasReadToday.value = res.has_read_today;
        historyDates.value = res.history || [];
      }
    } catch (e) {
      console.warn('No se pudo cargar el historial semanal:', e.message);
    } finally {
      loading.value = false;
    }
  };

  return { loading, weekDays, load };
}
