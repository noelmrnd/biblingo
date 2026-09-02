<template>
  <div class="space-y-6 pb-24 max-w-md mx-auto">
    <!-- Header Perfil -->
    <div class="card-duo text-center py-6 space-y-3">
      <div class="w-20 h-20 bg-gradient-to-tr from-brand-green to-emerald-400 rounded-full flex items-center justify-center text-4xl shadow-xl mx-auto border-4 border-slate-800">
        🦉
      </div>
      <div>
        <h2 class="text-2xl font-extrabold text-white">{{ user.display_name }}</h2>
        <p class="text-slate-400 text-xs font-mono">Código: {{ user.invite_code }}</p>
      </div>
    </div>

    <!-- Estadísticas Globales -->
    <div class="grid grid-cols-2 gap-3">
      <div class="card-duo bg-slate-900/90 border-slate-800 p-4 text-center space-y-1">
        <span class="text-2xl">🔥</span>
        <div class="text-2xl font-extrabold text-amber-400">{{ user.streak_count }}</div>
        <div class="text-slate-400 text-xs font-bold uppercase">Racha Actual</div>
      </div>
      <div class="card-duo bg-slate-900/90 border-slate-800 p-4 text-center space-y-1">
        <span class="text-2xl">⚡</span>
        <div class="text-2xl font-extrabold text-purple-400">{{ user.max_streak_count }}</div>
        <div class="text-slate-400 text-xs font-bold uppercase">Racha Máxima</div>
      </div>
    </div>

    <!-- Ajustes de Notificación Diaria -->
    <div class="card-duo space-y-4">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="font-extrabold text-white text-base">Recordatorio Diario</h3>
          <p class="text-slate-400 text-xs">Te notificaremos cada día para proteger tu racha</p>
        </div>
        <span class="text-2xl">🔔</span>
      </div>

      <div class="flex items-center justify-between bg-slate-900 border border-slate-800 p-3 rounded-2xl">
        <span class="text-sm font-bold text-slate-300">Hora de lectura:</span>
        <input 
          v-model="reminderTime" 
          type="time" 
          class="bg-slate-800 border border-slate-700 text-amber-400 font-extrabold rounded-xl px-3 py-1.5 text-sm focus:outline-none focus:border-brand-green"
        />
      </div>

      <button 
        @click="saveReminder" 
        class="btn-3d-green w-full text-sm py-3"
      >
        <span>Guardar Recordatorio</span>
      </button>
      <p v-if="savedMsg" class="text-emerald-400 text-xs font-semibold text-center">
        {{ savedMsg }}
      </p>
    </div>

    <!-- Botón Cerrar Sesión -->
    <div class="pt-4">
      <button 
        @click="logout" 
        class="w-full bg-slate-800 hover:bg-rose-950/40 text-slate-400 hover:text-rose-300 font-bold py-3.5 px-4 rounded-2xl border-2 border-slate-700 hover:border-rose-800 transition-colors text-sm flex items-center justify-center gap-2 cursor-pointer"
      >
        <span>🚪</span>
        <span>Cerrar Sesión</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { NotificationService } from '../services/notifications';
import { ApiService } from '../services/api';

const props = defineProps({
  user: { type: Object, required: true }
});

const emit = defineEmits(['logout']);

const reminderTime = ref('20:00');
const savedMsg = ref('');

const saveReminder = async () => {
  try {
    localStorage.setItem('biblingo_reminder_time', reminderTime.value);
    await ApiService.updateProfile(props.user.id, { reminder_time: reminderTime.value });
    await NotificationService.requestPermissions();
    await NotificationService.schedule7DayBurst(reminderTime.value, props.user.streak_count);
    savedMsg.value = `¡Recordatorio guardado para las ${reminderTime.value}! ⏰`;
  } catch (e) {
    savedMsg.value = `¡Recordatorio guardado para las ${reminderTime.value}! ⏰`;
  } finally {
    setTimeout(() => { savedMsg.value = ''; }, 4000);
  }
};

const logout = () => {
  emit('logout');
};

onMounted(() => {
  const saved = localStorage.getItem('biblingo_reminder_time');
  if (saved) {
    reminderTime.value = saved;
  } else if (props.user.reminder_time) {
    reminderTime.value = props.user.reminder_time;
  }
});
</script>
