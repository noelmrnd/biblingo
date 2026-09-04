<template>
  <div class="space-y-6">
    <!-- Card de Conexión: Código de Invitación + Agregar Amigo desplegable -->
    <div class="card-duo bg-slate-900 bg-[radial-gradient(ellipse_at_top_right,_rgba(88,204,2,0.18),_transparent_65%)] border-indigo-500/30 text-center space-y-4 py-6 px-5 relative overflow-hidden">
      <div>
        <h3 class="font-extrabold text-white text-lg">Código de invitación</h3>
        <p class="text-slate-300 text-base font-medium">Muestra este QR a un amigo para conectarte</p>
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
        <AppButton 
          color="green"
          @click="shareInvite" 
        >
          <Share2 class="w-5 h-5 stroke-[2.5]" />
          <span>Compartir</span>
        </AppButton>
      </div>

      <p v-if="copyMsg" class="text-emerald-400 text-base font-extrabold text-center pt-1">
        {{ copyMsg }}
      </p>

      <!-- Sección Desplegable: Agregar amigo por código (Secundario) -->
      <div>
        <button
          type="button"
          @click="isAddFriendExpanded = !isAddFriendExpanded"
          class="w-full flex items-center justify-between text-left py-1 text-slate-400 hover:text-white transition-colors cursor-pointer select-none group"
        >
          <div class="flex items-center gap-2">
            <UserRoundPlus class="w-4 h-4 text-sky-400 stroke-[2.5]" />
            <span class="text-base font-semibold text-slate-300 group-hover:text-white">
              ¿Tienes el código de un amigo?
            </span>
          </div>
          <component 
            :is="isAddFriendExpanded ? ChevronUp : ChevronDown" 
            class="w-4 h-4 text-slate-400 stroke-[2.5] transition-transform duration-200" 
          />
        </button>

        <div v-if="isAddFriendExpanded" class="pt-3 space-y-3">
          <div class="flex gap-2.5">
            <input 
              v-model="inputCode" 
              type="text" 
              placeholder="Código"
              class="flex-1 bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-base uppercase text-white font-mono placeholder:text-slate-500 focus:outline-none focus:border-brand-green min-w-0"
              maxlength="12"
              @keyup.enter="addFriend"
            />
            <AppButton 
              color="blue"
              :disabled="loading || !inputCode"
              @click="addFriend"
            >
              Agregar
            </AppButton>
          </div>
          <p v-if="statusMsg" :class="statusError ? 'text-rose-400' : 'text-emerald-400'" class="text-sm font-bold text-left">
            {{ statusMsg }}
          </p>
        </div>
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
          <div class="flex items-center gap-2 flex-none">
            <!-- Badge de Racha Unificado -->
            <div 
              :class="[
                friend.has_read_today
                  ? 'bg-emerald-500/15 border-emerald-500/30 text-emerald-400'
                  : friend.is_streak_lost
                  ? 'bg-sky-500/10 border-sky-500/20 text-sky-300'
                  : 'bg-amber-500/10 border-amber-500/20 text-amber-400'
              ]"
              class="flex items-center gap-1.5 font-extrabold text-base px-2.5 py-1.5 rounded-xl border"
            >
              <CheckCircle2 v-if="friend.has_read_today" class="w-4 h-4 text-emerald-400 stroke-[2.5]" />
              <span v-else-if="friend.is_streak_lost" class="text-base leading-none">🥶</span>
              <Flame v-else class="w-4 h-4 text-amber-400 stroke-[2.5]" />
              <span>{{ friend.streak_count }}</span>
            </div>

            <!-- Botón Dar un Toque (Solo visible para amigos que no han leído hoy) -->
            <button
              v-if="friend.id !== user.id && !friend.has_read_today"
              @click="sendNudge(friend)"
              :disabled="nudgedFriends[friend.id] || nudgeLoading[friend.id]"
              class="bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 disabled:from-slate-800 disabled:to-slate-800 disabled:text-slate-500 text-slate-950 font-extrabold px-3 py-1.5 rounded-xl text-base flex items-center gap-1.5 shadow-md active:scale-95 transition-all cursor-pointer border border-amber-400/40 disabled:border-slate-700"
            >
              <BellRing class="w-4 h-4 stroke-[2.5]" />
              <span>{{ nudgedFriends[friend.id] ? 'Enviado' : 'Toque' }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import AppButton from '../components/AppButton.vue';
import { Share2, UserRoundPlus, Trophy, UsersRound, Flame, BellRing, CheckCircle2, ChevronDown, ChevronUp } from '@lucide/vue';
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
const isAddFriendExpanded = ref(false);
const statusMsg = ref('');
const statusError = ref(false);
const qrDataUrl = ref('');
const copyMsg = ref('');
const nudgedFriends = ref({});
const nudgeLoading = ref({});

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
      invite_code: props.user.invite_code,
      has_read_today: props.user.has_read_today,
      is_streak_lost: props.user.is_streak_lost
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
  document.activeElement?.blur();
  loading.value = true;

  try {
    const res = await ApiService.addFriend(props.user.id, inputCode.value);
    if (res.success) {
      ToastService.success(`¡${res.friend.display_name} agregado a tus amigos! 🎉`);
      inputCode.value = '';
      isAddFriendExpanded.value = false;
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
