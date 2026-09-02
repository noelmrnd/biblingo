<template>
  <div class="space-y-6 pb-24 max-w-md mx-auto">
    <!-- Card de Invitación con Código QR visible desde el principio -->
    <div class="card-duo bg-gradient-to-br from-brand-card via-slate-900 to-slate-900 border-indigo-500/30 text-center space-y-4 py-6 px-5 relative overflow-hidden">
      <div class="absolute -top-10 -right-10 w-32 h-32 bg-brand-green/10 rounded-full blur-2xl pointer-events-none"></div>

      <div class="flex items-center justify-between text-left">
        <div>
          <h3 class="font-extrabold text-white text-base">Tu Código de Invitación</h3>
          <p class="text-slate-400 text-xs">Muestra tu QR a un amigo para conectarte</p>
        </div>
        <span class="text-3xl">📱</span>
      </div>

      <!-- Contenedor del QR Code visible desde el inicio -->
      <div class="py-2">
        <div class="bg-white p-3.5 rounded-3xl inline-block shadow-2xl border-4 border-brand-green transform hover:scale-105 transition-transform duration-200">
          <img v-if="qrDataUrl" :src="qrDataUrl" alt="Código QR de Invitación" class="w-44 h-44 mx-auto block" />
          <div v-else class="w-44 h-44 flex items-center justify-center text-slate-400 text-xs font-semibold">
            Generando QR...
          </div>
        </div>
      </div>

      <!-- Código de texto y Botón de Compartir -->
      <div class="bg-slate-900/90 border border-slate-700 p-3 rounded-2xl flex items-center justify-between">
        <div class="text-left pl-2">
          <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Código:</span>
          <span class="text-xl font-black tracking-widest text-emerald-400 font-mono">
            {{ user.invite_code || 'BIBLINGO1' }}
          </span>
        </div>
        <button 
          @click="shareInvite" 
          class="btn-3d-green text-xs py-2.5 px-4"
        >
          <span>Compartir Enlace</span>
        </button>
      </div>
    </div>

    <!-- Agregar Amigo por Código o QR -->
    <div class="card-duo space-y-3">
      <div class="flex items-center justify-between">
        <h4 class="font-bold text-white text-sm">Añadir a un Amigo</h4>
        <label class="text-xs font-bold text-brand-blue hover:underline cursor-pointer flex items-center gap-1">
          <span>📷 Cargar QR</span>
          <input type="file" accept="image/*" class="hidden" @change="handleQrFileUpload" />
        </label>
      </div>

      <div class="flex gap-2">
        <input 
          v-model="inputCode" 
          type="text" 
          placeholder="Código (ej. 8K2M9P)"
          class="flex-1 bg-slate-900 border border-slate-700 rounded-xl px-4 py-2.5 text-sm uppercase text-white font-mono placeholder:text-slate-500 focus:outline-none focus:border-brand-green"
          maxlength="12"
          @keyup.enter="addFriend"
        />
        <button 
          @click="addFriend"
          :disabled="loading || !inputCode"
          class="btn-3d-blue text-xs py-2.5 px-4 disabled:opacity-50"
        >
          Añadir
        </button>
      </div>
      <p v-if="statusMsg" :class="statusError ? 'text-rose-400' : 'text-emerald-400'" class="text-xs font-semibold">
        {{ statusMsg }}
      </p>
    </div>

    <!-- Tabla de Clasificación de Amigos -->
    <div class="space-y-3">
      <h3 class="font-extrabold text-white text-base flex items-center gap-2">
        <span>🏆</span> Ranking de Rachas
      </h3>

      <div v-if="friends.length === 0" class="card-duo text-center py-8 text-slate-400 space-y-2">
        <span class="text-4xl block">🦉</span>
        <p class="text-sm font-semibold">Aún no tienes amigos agregados.</p>
        <p class="text-xs text-slate-500">Muestra tu código QR o comparte tu enlace para empezar a competir.</p>
      </div>

      <div v-else class="space-y-2">
        <div 
          v-for="(friend, index) in sortedFriends" 
          :key="friend.id"
          class="card-duo py-3.5 px-4 flex items-center justify-between bg-slate-900/80 border-slate-800 hover:border-slate-700 transition-colors"
        >
          <div class="flex items-center gap-3">
            <!-- Medallas de ranking -->
            <div class="w-7 text-center font-black text-sm">
              <span v-if="index === 0">🥇</span>
              <span v-else-if="index === 1">🥈</span>
              <span v-else-if="index === 2">🥉</span>
              <span v-else class="text-slate-500">#{{ index + 1 }}</span>
            </div>

            <div>
              <h4 class="font-bold text-white text-sm flex items-center gap-1.5">
                {{ friend.display_name }}
                <span v-if="friend.id === user.id" class="text-[10px] bg-brand-green/20 text-brand-green px-1.5 py-0.5 rounded font-extrabold">TÚ</span>
              </h4>
              <p class="text-slate-400 text-xs">
                Última lectura: {{ friend.last_read_date || 'Sin registro' }}
              </p>
            </div>
          </div>

          <!-- Racha del amigo -->
          <div class="flex items-center gap-1.5 font-extrabold text-amber-400 text-sm bg-amber-500/10 px-3 py-1.5 rounded-xl border border-amber-500/20">
            <span>🔥</span>
            <span>{{ friend.streak_count }}d</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import QRCode from 'qrcode';
import { ApiService } from '../services/api';
import { ShareService } from '../services/shareService';

const props = defineProps({
  user: { type: Object, required: true }
});

const friends = ref([]);
const inputCode = ref('');
const loading = ref(false);
const statusMsg = ref('');
const statusError = ref(false);
const qrDataUrl = ref('');

const sortedFriends = computed(() => {
  const all = [...friends.value];
  if (!all.some(f => f.id === props.user.id)) {
    all.push({
      id: props.user.id,
      display_name: `${props.user.display_name} (Tú)`,
      streak_count: props.user.streak_count,
      last_read_date: props.user.last_read_date,
      invite_code: props.user.invite_code
    });
  }
  return all.sort((a, b) => b.streak_count - a.streak_count);
});

const generateQrCode = async () => {
  if (!props.user.invite_code) return;
  const inviteUrl = `https://biblingo.me/invite/${props.user.invite_code}`;
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
    }
  } catch (e) {
    console.warn('Error al cargar amigos:', e.message);
  }
};

const addFriend = async () => {
  if (!inputCode.value || loading.value) return;
  loading.value = true;
  statusMsg.value = '';
  statusError.value = false;

  try {
    const res = await ApiService.addFriend(props.user.id, inputCode.value);
    if (res.success) {
      statusMsg.value = `¡${res.friend.display_name} agregado a tus amigos! 🎉`;
      inputCode.value = '';
      loadFriends();
    }
  } catch (e) {
    statusError.value = true;
    statusMsg.value = e.message || 'Error al agregar amigo.';
  } finally {
    loading.value = false;
  }
};

const handleQrFileUpload = (event) => {
  const file = event.target.files[0];
  if (!file) return;

  alert('Se procesará la imagen cargada para obtener el código de invitación.');
};

const shareInvite = async () => {
  const res = await ShareService.shareInviteCode(props.user.invite_code, props.user.display_name);
  if (res.method === 'clipboard') {
    alert('¡Enlace y código de invitación copiado al portapapeles! 📋');
  }
};

onMounted(() => {
  loadFriends();
  generateQrCode();
});
</script>
