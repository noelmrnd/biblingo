<template>
  <div class="space-y-4">
    <!-- Header Perfil -->
    <div class="card-duo text-center py-6 space-y-3">
      <div class="w-20 h-20 bg-gradient-to-tr from-brand-green to-emerald-400 rounded-full flex items-center justify-center shadow-xl mx-auto border-4 border-slate-800">
        <UserRound class="w-10 h-10 text-white stroke-[2.5]" />
      </div>
      <div>
        <h2 class="text-2xl font-extrabold text-white">{{ user.display_name }}</h2>
        <p class="text-slate-300 text-base font-medium">Código: <span class="font-mono text-amber-400 font-bold">{{ user.invite_code }}</span></p>
      </div>
    </div>

    <!-- Estadísticas Globales -->
    <div class="grid grid-cols-2 gap-3">
      <div class="card-duo bg-slate-900/90 border-slate-800 p-4 text-center space-y-1">
        <Flame class="w-7 h-7 text-amber-400 mx-auto stroke-[2.5]" />
        <div class="text-2xl font-extrabold text-amber-400">{{ user.streak_count }}</div>
        <div class="text-slate-300 text-base font-black uppercase tracking-wider">Racha actual</div>
      </div>
      <div class="card-duo bg-slate-900/90 border-slate-800 p-4 text-center space-y-1">
        <Zap class="w-7 h-7 text-purple-400 mx-auto stroke-[2.5]" />
        <div class="text-2xl font-extrabold text-purple-400">{{ user.max_streak_count }}</div>
        <div class="text-slate-300 text-base font-black uppercase tracking-wider">Racha máxima</div>
      </div>
    </div>

    <!-- Ajustes de Notificación Diaria -->
    <div class="card-duo space-y-4">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="font-extrabold text-white text-lg">Recordatorio diario</h3>
          <p class="text-slate-300 text-base mt-0.5 font-medium">Te notificaremos cada día para proteger tu racha</p>
        </div>
        <Bell class="w-6 h-6 text-amber-400 stroke-[2.5]" />
      </div>

      <div class="flex items-center justify-between bg-slate-900 border border-slate-800 p-3 rounded-2xl">
        <span class="text-base font-bold text-slate-200">Hora de lectura:</span>
        <input 
          v-model="reminderTime" 
          type="time" 
          step="600"
          class="bg-slate-800 border border-slate-700 text-amber-400 font-extrabold rounded-xl px-3 py-1.5 text-base focus:outline-none focus:border-brand-green"
        />
      </div>

      <button 
        @click="saveReminder" 
        class="btn-3d-green w-full text-base py-3.5"
      >
        <span>Guardar recordatorio</span>
      </button>
    </div>

    <!-- Botón Cerrar Sesión -->
    <div class="pt-4">
      <button 
        @click="logout" 
        class="w-full bg-slate-800 hover:bg-rose-950/40 text-slate-400 hover:text-rose-300 font-bold py-3.5 px-4 rounded-2xl border-2 border-slate-700 hover:border-rose-800 transition-colors text-base flex items-center justify-center gap-2 cursor-pointer"
      >
        <LogOut class="w-5 h-5 text-rose-400 stroke-[2.5]" />
        <span>Cerrar sesión</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { UserRound, Flame, Zap, Bell, LogOut } from '@lucide/vue';
import { NotificationService } from '../services/notifications';
import { ApiService } from '../services/api';
import { ToastService } from '../services/toast';
import { StorageService } from '../services/storage';

const props = defineProps({
  user: { type: Object, required: true }
});

const emit = defineEmits(['logout']);

const reminderTime = ref('20:00');

const saveReminder = async () => {
  try {
    await StorageService.set('biblingo_reminder_time', reminderTime.value);
    await ApiService.updateProfile(props.user.id, { reminder_time: reminderTime.value });
    await NotificationService.requestPermissions();
    await NotificationService.schedule7DayBurst(reminderTime.value, props.user.streak_count, props.user.has_read_today || false);
    
    ToastService.success('¡Recordatorio guardado! ⏰');
  } catch (e) {
    ToastService.error('No se pudo guardar el recordatorio.');
  }
};

const logout = () => {
  emit('logout');
};

onMounted(async () => {
  const saved = await StorageService.get('biblingo_reminder_time');
  if (saved) {
    reminderTime.value = saved;
  } else if (props.user.reminder_time) {
    reminderTime.value = props.user.reminder_time;
  }
});
</script>
