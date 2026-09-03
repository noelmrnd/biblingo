<template>
  <div class="space-y-10">
    <div class="space-y-4">
      <!-- Card de Invitación con Código QR visible desde el principio -->
      <div class="card-duo bg-slate-900 bg-[radial-gradient(ellipse_at_top_right,_rgba(88,204,2,0.18),_transparent_65%)] border-indigo-500/30 text-center space-y-4 py-6 px-5 relative overflow-hidden">

        <div class="flex items-center justify-between text-left gap-3">
          <div>
            <h3 class="font-extrabold text-white text-lg">Código de invitación</h3>
            <p class="text-slate-300 text-base font-medium">Muestra este QR a un amigo para conectarte</p>
          </div>
          <QrCode class="w-8 h-8 text-emerald-400 stroke-[2.5]" />
        </div>

        <!-- Contenedor del QR Code visible desde el inicio -->
        <div class="py-2">
          <div class="bg-white p-3.5 rounded-3xl inline-block shadow-2xl border-4 border-brand-green">
            <img v-if="qrDataUrl" :src="qrDataUrl" alt="Código QR de invitación" class="w-44 h-44 mx-auto block" />
            <div v-else class="w-44 h-44 flex items-center justify-center text-slate-400 text-base font-semibold">
              Generando QR...
            </div>
          </div>
        </div>

        <!-- Código de texto y Botón de Compartir -->
        <div class="bg-slate-900/90 border border-slate-700 p-3 rounded-2xl flex items-center justify-between">
          <div class="text-left pl-2">
            <span class="text-xs text-slate-300 uppercase tracking-wider block">Código:</span>
            <span class="text-xl font-black tracking-widest text-emerald-400 font-mono">
              {{ user.invite_code || 'BIBLINGO1' }}
            </span>
          </div>
          <button 
            @click="shareInvite" 
            class="btn-3d-green text-base py-3 px-5 font-extrabold flex items-center gap-3"
          >
            <Share2 class="w-5 h-5 stroke-[2.5]" />
            <span>Compartir</span>
          </button>
        </div>

        <p v-if="copyMsg" class="text-emerald-400 text-base font-extrabold text-center pt-1">
          {{ copyMsg }}
        </p>
      </div>

      <!-- Agregar Amigo por Código -->
      <div class="card-duo space-y-3">
        <h4 class="font-extrabold text-white text-lg flex items-center gap-3">
          <UserRoundPlus class="w-5 h-5 text-sky-400 stroke-[2.5]" />
          <span>Agregar amigo</span>
        </h4>

        <div class="flex gap-3">
          <input 
            v-model="inputCode" 
            type="text" 
            placeholder="Código"
            class="flex-1 bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-base uppercase text-white font-mono placeholder:text-slate-500 focus:outline-none focus:border-brand-green"
            maxlength="12"
            @keyup.enter="addFriend"
          />
          <button 
            @click="addFriend"
            :disabled="loading || !inputCode"
            class="btn-3d-blue text-base py-3 px-5 font-extrabold disabled:opacity-50"
          >
            Agregar
          </button>
        </div>
        <p v-if="statusMsg" :class="statusError ? 'text-rose-400' : 'text-emerald-400'" class="text-base font-bold">
          {{ statusMsg }}
        </p>
      </div>
    </div>

    <!-- Tabla de Clasificación de Amigos -->
    <div class="space-y-3">
      <h3 class="font-extrabold text-white text-lg flex items-center gap-3">
        <Trophy class="w-6 h-6 text-amber-400 stroke-[2.5]" />
        <span>Ranking de rachas</span>
      </h3>

      <div v-if="friends.length === 0" class="card-duo text-center py-8 text-slate-400 space-y-2">
        <UsersRound class="w-12 h-12 text-slate-500 mx-auto stroke-[2]" />
        <p class="text-lg font-extrabold text-white">Aún no tienes amigos agregados.</p>
        <p class="text-base text-slate-300 font-medium">Muestra tu código QR o comparte tu enlace para empezar.</p>
      </div>

      <div v-else class="space-y-3">
        <div 
          v-for="(friend, index) in sortedFriends" 
          :key="friend.id"
          class="card-duo py-3.5 px-4 flex items-center justify-between bg-slate-900/80 border-slate-800 hover:border-slate-700 transition-colors gap-4"
        >
          <div class="flex items-center gap-5 min-w-0 flex-1">
            <!-- Medallas de ranking -->
            <div class="w-7 text-center font-black text-4xl flex-none">
              <span v-if="index === 0">🥇</span>
              <span v-else-if="index === 1">🥈</span>
              <span v-else-if="index === 2">🥉</span>
              <span v-else class="text-slate-400 font-bold">#{{ index + 1 }}</span>
            </div>

            <div class="min-w-0 flex-1">
              <h4 class="font-bold text-white text-base flex items-center gap-3 truncate">
                {{ friend.display_name }}
                <span v-if="friend.id === user.id" class="text-sm bg-brand-green/20 text-brand-green px-2 py-0.5 rounded-md font-black flex-none">TÚ</span>
              </h4>
              <p class="text-slate-300 text-base font-medium truncate">
                {{ formatFriendlyDate(friend.last_read_date) }}
              </p>
            </div>
          </div>

          <!-- Acciones de Racha & Recordatorio -->
          <div class="flex items-center gap-3 flex-none">
            <!-- Si es el usuario actual -->
            <div v-if="friend.id === user.id" class="flex items-center gap-1.5 font-extrabold text-amber-400 text-base bg-amber-500/10 px-3 py-1.5 rounded-xl border border-amber-500/20">
              <Flame class="w-4 h-4 text-amber-400 stroke-[2.5]" />
              <span>{{ friend.streak_count }}d</span>
            </div>

            <!-- Si es un amigo -->
            <template v-else>
              <!-- Si el amigo YA leyó hoy -->
              <div v-if="hasFriendReadToday(friend.last_read_date)" class="flex items-center gap-1.5 bg-emerald-500/15 text-emerald-400 px-3 py-1.5 rounded-xl border border-emerald-500/30 text-base font-extrabold">
                <CheckCircle2 class="w-4 h-4 text-emerald-400 stroke-[2.5]" />
                <span>{{ friend.streak_count }}d</span>
              </div>

              <!-- Si el amigo AÚN NO ha leído hoy -->
              <div v-else class="flex items-center gap-1.5">
                <button
                  @click="sendNudge(friend)"
                  :disabled="nudgedFriends[friend.id] || nudgeLoading[friend.id]"
                  class="bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 disabled:from-slate-800 disabled:to-slate-800 disabled:text-slate-500 text-slate-950 font-extrabold px-3.5 py-1.5 rounded-xl text-base flex items-center gap-1.5 shadow-md active:scale-95 transition-all cursor-pointer border border-amber-400/40 disabled:border-slate-700"
                >
                  <BellRing class="w-4 h-4 stroke-[2.5]" :class="nudgedFriends[friend.id] ? '' : 'animate-bounce'" />
                  <span>{{ nudgedFriends[friend.id] ? 'Toque enviado' : 'Dar un toque' }}</span>
                </button>
              </div>
            </template>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { QrCode, Share2, UserRoundPlus, Trophy, UsersRound, Flame, BellRing, CheckCircle2 } from '@lucide/vue';
import QRCode from 'qrcode';
import { ApiService } from '../services/api';
import { ShareService } from '../services/shareService';
import { ToastService } from '../services/toast';
import { formatFriendlyDate } from '../utils/dateFormatter';

const props = defineProps({
  user: { type: Object, required: true }
});

const friends = ref([]);
const inputCode = ref('');
const loading = ref(false);
const statusMsg = ref('');
const statusError = ref(false);
const qrDataUrl = ref('');
const copyMsg = ref('');
const nudgedFriends = ref({});
const nudgeLoading = ref({});

const hasFriendReadToday = (lastReadDateStr) => {
  if (!lastReadDateStr) return false;
  const today = new Date().toISOString().split('T')[0];
  return lastReadDateStr.startsWith(today);
};

const sendNudge = async (friend) => {
  if (nudgedFriends.value[friend.id] || nudgeLoading.value[friend.id]) return;

  nudgeLoading.value[friend.id] = true;
  try {
    const res = await ApiService.nudgeFriend(props.user.id, friend.id);
    nudgedFriends.value[friend.id] = true;
    ToastService.success(res.message || `¡Le enviaste un recordatorio a ${friend.display_name}! 🔔`);
  } catch (e) {
    ToastService.error(e.message || `No se pudo enviar el recordatorio.`);
  } finally {
    nudgeLoading.value[friend.id] = false;
  }
};

const sortedFriends = computed(() => {
  const all = [...friends.value];
  if (!all.some(f => f.id === props.user.id)) {
    all.push({
      id: props.user.id,
      display_name: props.user.display_name,
      streak_count: props.user.streak_count,
      last_read_date: props.user.last_read_date,
      invite_code: props.user.invite_code
    });
  }
  return all.sort((a, b) => b.streak_count - a.streak_count);
});

const generateQrCode = async () => {
  if (!props.user.invite_code) return;
  const inviteUrl = `https://app.biblingo.me/invite/${props.user.invite_code}`;
  try {
    qrDataUrl.value = await QRCode.toDataURL(inviteUrl, {
      width: 300,
      margin: 2,
      color: {
        dark: '#0F172A',
        light: '#FFFFFF'
      }
    });
  } catch (err) {
    console.warn('Error al generar el código QR:', err);
  }
};

const loadFriends = async () => {
  try {
    const res = await ApiService.getFriends(props.user.id);
    if (res.success) {
      friends.value = res.friends || [];
      // Sincronizar estado de toques enviados hoy desde la API
      friends.value.forEach(f => {
        if (f.nudged_today) {
          nudgedFriends.value[f.id] = true;
        }
      });
    }
  } catch (e) {
    console.warn('Error al cargar amigos:', e.message);
  }
};

const addFriend = async () => {
  if (!inputCode.value || loading.value) return;
  loading.value = true;

  try {
    const res = await ApiService.addFriend(props.user.id, inputCode.value);
    if (res.success) {
      ToastService.success(`¡${res.friend.display_name} agregado a tus amigos! 🎉`);
      inputCode.value = '';
      loadFriends();
    }
  } catch (e) {
    ToastService.error(e.message || 'Error al agregar amigo.');
  } finally {
    loading.value = false;
  }
};

const shareInvite = async () => {
  const res = await ShareService.shareInviteCode(props.user.invite_code, props.user.display_name);
  if (res.success && res.method === 'clipboard') {
    ToastService.success('¡Enlace y código copiado! 📋');
  }
};

onMounted(() => {
  loadFriends();
  generateQrCode();
});
</script>
