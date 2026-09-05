<template>
  <div class="space-y-6">
    <!-- Card Desplegable: Invitar amigos (Estilo similar a Datos de perfil con resplandor) -->
    <div class="card-duo bg-slate-900 bg-[radial-gradient(ellipse_at_top_right,_rgba(88,204,2,0.18),_transparent_65%)] border-indigo-500/30 relative overflow-hidden transition-all duration-200">
      <div 
        @click="isInviteExpanded = !isInviteExpanded" 
        class="flex items-center justify-between cursor-pointer select-none gap-3"
      >
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-brand-green/10 border border-brand-green/30 flex items-center justify-center shrink-0">
            <UserRoundPlus class="w-5 h-5 text-brand-green stroke-[2.5]" />
          </div>
          <div>
            <h3 class="font-extrabold text-white text-lg">Invitar amigos</h3>
            <p class="text-slate-300 text-base font-medium">
              {{ isInviteExpanded ? 'Ocultar opciones de invitación' : 'Comparte tu código QR o agrega a tus amigos' }}
            </p>
          </div>
        </div>
        <component 
          :is="isInviteExpanded ? ChevronUp : ChevronDown" 
          class="w-6 h-6 text-slate-400 stroke-[2.5] transition-transform duration-200" 
        />
      </div>

      <!-- Contenido desplegable -->
      <div v-if="isInviteExpanded" class="space-y-4 mt-4 text-center">
        <!-- Contenedor del QR Code -->
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

        <!-- Agregar amigo por código -->
        <div class="space-y-2.5 pt-3 border-t border-slate-800/80 text-left">
          <label class="text-xs text-slate-300 uppercase tracking-wider block font-bold">¿Tienes el código de un amigo?</label>
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
          <p v-if="statusMsg" :class="statusError ? 'text-rose-400' : 'text-emerald-400'" class="text-sm font-bold">
            {{ statusMsg }}
          </p>
        </div>
      </div>
    </div>

    <!-- Solicitudes de Amistad (Recibidas y Enviadas) -->
    <div 
      v-if="receivedRequests.length > 0 || sentRequests.length > 0" 
      class="card-duo bg-slate-900 bg-[radial-gradient(ellipse_at_top_right,_rgba(88,204,2,0.15),_transparent_65%)] border-brand-green/30 space-y-4 transition-all duration-200"
    >
      <!-- Header de la Card consistente con el resto de la app -->
      <div class="flex items-center justify-between gap-3">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-brand-green/10 border border-brand-green/30 flex items-center justify-center shrink-0">
            <UserCheck class="w-5 h-5 text-brand-green stroke-[2.5]" />
          </div>
          <div>
            <h3 class="font-extrabold text-white text-lg">Solicitudes de amistad</h3>
            <p class="text-slate-300 text-base font-medium">
              {{ receivedRequests.length > 0 
                  ? `Tienes ${receivedRequests.length} solicitud${receivedRequests.length > 1 ? 'es' : ''} pendiente${receivedRequests.length > 1 ? 's' : ''}` 
                  : 'Gestiona tus solicitudes enviadas' }}
            </p>
          </div>
        </div>
        <span class="bg-brand-green/20 text-brand-green text-sm font-black px-3 py-1 rounded-full border border-brand-green/30 shrink-0">
          {{ receivedRequests.length + sentRequests.length }}
        </span>
      </div>

      <!-- Segmented Control con Estilo Duolingo -->
      <div class="grid grid-cols-2 gap-2 bg-slate-950/80 p-1.5 rounded-2xl border-2 border-slate-800">
        <button
          type="button"
          @click="activeRequestTab = 'received'"
          :class="activeRequestTab === 'received' 
            ? 'bg-brand-card text-brand-green border-brand-green/50 shadow-md font-black' 
            : 'text-slate-400 hover:text-slate-200 border-transparent font-extrabold'"
          class="py-2.5 px-3 rounded-xl text-base flex items-center justify-center gap-2 transition-all border cursor-pointer select-none active:scale-95"
        >
          <span>Recibidas</span>
          <span 
            v-if="receivedRequests.length > 0" 
            class="bg-brand-green text-slate-950 text-xs font-black px-2 py-0.5 rounded-full"
          >
            {{ receivedRequests.length }}
          </span>
        </button>
        <button
          type="button"
          @click="activeRequestTab = 'sent'"
          :class="activeRequestTab === 'sent' 
            ? 'bg-brand-card text-brand-blue border-sky-500/50 shadow-md font-black' 
            : 'text-slate-400 hover:text-slate-200 border-transparent font-extrabold'"
          class="py-2.5 px-3 rounded-xl text-base flex items-center justify-center gap-2 transition-all border cursor-pointer select-none active:scale-95"
        >
          <span>Enviadas</span>
          <span 
            v-if="sentRequests.length > 0" 
            class="bg-brand-blue text-white text-xs font-black px-2 py-0.5 rounded-full"
          >
            {{ sentRequests.length }}
          </span>
        </button>
      </div>

      <!-- Tab: Recibidas -->
      <div v-if="activeRequestTab === 'received'" class="space-y-3">
        <div v-if="receivedRequests.length === 0" class="py-8 text-center text-slate-400 space-y-2">
          <UserCheck class="w-10 h-10 text-slate-600 mx-auto stroke-[2]" />
          <p class="text-base font-extrabold text-white">No tienes solicitudes recibidas.</p>
          <p class="text-sm text-slate-400">Cuando alguien te agregue, aparecerá aquí para que lo aceptes.</p>
        </div>
        <div 
          v-for="req in receivedRequests" 
          :key="req.request_id"
          class="bg-slate-950/70 border-2 border-slate-800/90 rounded-2xl p-3.5 flex items-center justify-between gap-3 hover:border-slate-700 transition-colors"
        >
          <div class="flex items-center gap-3 min-w-0 flex-1">
            <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-brand-green/30 to-emerald-500/20 border border-brand-green/40 flex items-center justify-center shrink-0 text-emerald-300 font-black text-base shadow-sm">
              {{ (req.display_name || '?').charAt(0).toUpperCase() }}
            </div>
            <div class="min-w-0 flex-1">
              <h4 class="font-extrabold text-white text-base truncate">
                {{ req.display_name }}
              </h4>
              <p class="text-slate-300 text-sm font-medium truncate flex items-center gap-1.5 mt-0.5">
                <Flame class="w-4 h-4 text-amber-400 stroke-[2.5]" />
                <span>Racha: <strong class="text-amber-400">{{ req.streak_count }}</strong> días</span>
              </p>
            </div>
          </div>

          <!-- Botones Aceptar / Rechazar con 3D AppButton -->
          <div class="flex items-center gap-2 shrink-0">
            <AppButton
              color="green"
              size="sm"
              :disabled="requestActionLoading[req.request_id]"
              @click="acceptRequest(req)"
            >
              <Check class="w-4 h-4 stroke-[3]" />
              <span>Aceptar</span>
            </AppButton>
            <AppButton
              color="dark"
              size="sm"
              :disabled="requestActionLoading[req.request_id]"
              @click="rejectRequest(req)"
              aria-label="Rechazar solicitud"
            >
              <X class="w-4 h-4 stroke-[2.5] text-rose-400" />
            </AppButton>
          </div>
        </div>
      </div>

      <!-- Tab: Enviadas -->
      <div v-if="activeRequestTab === 'sent'" class="space-y-3">
        <div v-if="sentRequests.length === 0" class="py-8 text-center text-slate-400 space-y-2">
          <Clock class="w-10 h-10 text-slate-600 mx-auto stroke-[2]" />
          <p class="text-base font-extrabold text-white">No tienes solicitudes enviadas pendientes.</p>
          <p class="text-sm text-slate-400">Las solicitudes que envíes por código o enlace aparecerán aquí.</p>
        </div>
        <div 
          v-for="req in sentRequests" 
          :key="req.request_id"
          class="bg-slate-950/70 border-2 border-slate-800/90 rounded-2xl p-3.5 flex items-center justify-between gap-3 hover:border-slate-700 transition-colors"
        >
          <div class="flex items-center gap-3 min-w-0 flex-1">
            <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-brand-blue/30 to-sky-500/20 border border-sky-500/40 flex items-center justify-center shrink-0 text-sky-300 font-black text-base shadow-sm">
              {{ (req.display_name || '?').charAt(0).toUpperCase() }}
            </div>
            <div class="min-w-0 flex-1">
              <h4 class="font-extrabold text-white text-base truncate">
                {{ req.display_name }}
              </h4>
              <p class="text-slate-400 text-sm font-medium truncate flex items-center gap-1.5 mt-0.5">
                <Clock class="w-4 h-4 text-sky-400 stroke-[2.5]" />
                <span class="text-sky-300/90">Esperando respuesta...</span>
              </p>
            </div>
          </div>

          <!-- Botón Cancelar con 3D AppButton -->
          <div class="shrink-0">
            <AppButton
              color="dark"
              size="sm"
              :disabled="requestActionLoading[req.request_id]"
              @click="cancelRequest(req)"
            >
              <X class="w-4 h-4 stroke-[2.5] text-slate-400" />
              <span class="text-slate-300">Cancelar</span>
            </AppButton>
          </div>
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
        <SwipeItem 
          v-for="(friend, index) in sortedFriends" 
          :key="friend.id"
          :disabled="friend.id === user.id"
          :is-open="activeSwipeFriendId === friend.id"
          :action-width="88"
          @open="activeSwipeFriendId = friend.id"
          @close="handleSwipeClose(friend.id)"
          @action="promptRemoveFriend(friend)"
        >
          <div 
            class="card-duo py-3.5 px-4 flex items-center justify-between bg-slate-900 border-slate-800 hover:border-slate-700 transition-colors gap-4"
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
                  friend.is_streak_lost
                    ? 'bg-sky-500/10 border-sky-500/20 text-sky-300'
                    : 'bg-amber-500/10 border-amber-500/20 text-amber-400'
                ]"
                class="flex items-center gap-1.5 font-extrabold text-base px-2.5 py-1.5 rounded-xl border"
              >
                <span v-if="friend.is_streak_lost" class="text-base leading-none">🥶</span>
                <Flame v-else class="w-4 h-4 text-amber-400 stroke-[2.5]" />
                <span>{{ friend.streak_count }}</span>
              </div>

              <!-- Botón Dar un Toque (Solo visible para amigos que no han leído hoy) -->
              <button
                v-if="friend.id !== user.id && !friend.has_read_today"
                @click.stop="sendNudge(friend)"
                :disabled="nudgedFriends[friend.id] || nudgeLoading[friend.id]"
                class="bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 disabled:from-slate-800 disabled:to-slate-800 disabled:text-slate-500 text-slate-950 font-extrabold px-3 py-1.5 rounded-xl text-base flex items-center gap-1.5 shadow-md active:scale-95 transition-all cursor-pointer border border-amber-400/40 disabled:border-slate-700"
              >
                <BellRing class="w-4 h-4 stroke-[2.5]" />
                <span>{{ nudgedFriends[friend.id] ? 'Enviado' : 'Toque' }}</span>
              </button>
            </div>
          </div>
        </SwipeItem>
      </div>
    </div>

    <!-- Modal Confirmación de Eliminar Amigo -->
    <AppModal
      :is-open="isRemoveModalOpen"
      :loading="removeLoading"
      :title="friendToRemove ? `¿Eliminar a ${friendToRemove.display_name}?` : '¿Eliminar amigo?'"
      description="Ya no verás su progreso en el ranking ni podrán enviarse toques mutuamente."
      @close="closeRemoveModal"
      :show-close="false"
    >
      <template #icon>
        <div class="w-11 h-11 rounded-2xl bg-rose-500/10 border border-rose-500/30 flex items-center justify-center shrink-0">
          <UserMinus class="w-5 h-5 text-rose-400 stroke-[2.5]" />
        </div>
      </template>

      <template #footer>
        <div class="flex items-center gap-3 w-full">
          <div class="flex-1">
            <AppButton
              color="dark"
              block
              :disabled="removeLoading"
              @click="closeRemoveModal"
            >
              Cancelar
            </AppButton>
          </div>
          <div class="flex-1">
            <AppButton
              color="rose"
              block
              :disabled="removeLoading"
              @click="confirmRemoveFriend"
            >
              {{ removeLoading ? 'Eliminando...' : 'Eliminar' }}
            </AppButton>
          </div>
        </div>
      </template>
    </AppModal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import AppButton from '../components/AppButton.vue';
import AppModal from '../components/AppModal.vue';
import SwipeItem from '../components/SwipeItem.vue';
import { Share2, UserRoundPlus, Trophy, UsersRound, Flame, BellRing, ChevronDown, ChevronUp, UserMinus, UserCheck, Check, X, Clock } from '@lucide/vue';
import QRCode from 'qrcode';
import { ApiService } from '../services/api';
import { ShareService } from '../services/shareService';
import { ToastService } from '../services/toast';
import { formatFriendlyDate } from '../utils/dateFormatter';

const props = defineProps({
  user: { type: Object, required: true }
});

const friends = ref([]);
const receivedRequests = ref([]);
const sentRequests = ref([]);
const activeRequestTab = ref('received');
const requestActionLoading = ref({});
const inputCode = ref('');
const loading = ref(false);
const isInviteExpanded = ref(false);
const statusMsg = ref('');
const statusError = ref(false);
const qrDataUrl = ref('');
const copyMsg = ref('');
const nudgedFriends = ref({});
const nudgeLoading = ref({});
const isRemoveModalOpen = ref(false);
const friendToRemove = ref(null);
const removeLoading = ref(false);
const activeSwipeFriendId = ref(null);

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

const loadFriendRequests = async () => {
  try {
    const res = await ApiService.getFriendRequests(props.user.id);
    if (res.success) {
      receivedRequests.value = res.received || res.requests || [];
      sentRequests.value = res.sent || [];
      if (receivedRequests.value.length === 0 && sentRequests.value.length > 0) {
        activeRequestTab.value = 'sent';
      } else if (receivedRequests.value.length > 0 && sentRequests.value.length === 0) {
        activeRequestTab.value = 'received';
      }
    }
  } catch (e) {
    console.warn('Error al cargar solicitudes de amistad:', e.message);
  }
};

const acceptRequest = async (req) => {
  if (requestActionLoading.value[req.request_id]) return;
  requestActionLoading.value[req.request_id] = true;
  try {
    const res = await ApiService.acceptFriendRequest(props.user.id, req.sender_id, req.request_id);
    if (res.success) {
      ToastService.success(`¡Ahora tú y ${req.display_name} son amigos! 🎉`);
      receivedRequests.value = receivedRequests.value.filter(r => r.request_id !== req.request_id);
      loadFriends();
    }
  } catch (e) {
    ToastService.error(e.message || 'Error al aceptar solicitud.');
  } finally {
    requestActionLoading.value[req.request_id] = false;
  }
};

const rejectRequest = async (req) => {
  if (requestActionLoading.value[req.request_id]) return;
  requestActionLoading.value[req.request_id] = true;
  try {
    const res = await ApiService.rejectFriendRequest(props.user.id, req.sender_id, req.request_id);
    if (res.success) {
      ToastService.info('Solicitud de amistad rechazada.');
      receivedRequests.value = receivedRequests.value.filter(r => r.request_id !== req.request_id);
    }
  } catch (e) {
    ToastService.error(e.message || 'Error al rechazar solicitud.');
  } finally {
    requestActionLoading.value[req.request_id] = false;
  }
};

const cancelRequest = async (req) => {
  if (requestActionLoading.value[req.request_id]) return;
  requestActionLoading.value[req.request_id] = true;
  try {
    const res = await ApiService.cancelFriendRequest(props.user.id, req.receiver_id, req.request_id);
    if (res.success) {
      ToastService.info('Solicitud cancelada.');
      sentRequests.value = sentRequests.value.filter(r => r.request_id !== req.request_id);
    }
  } catch (e) {
    ToastService.error(e.message || 'Error al cancelar solicitud.');
  } finally {
    requestActionLoading.value[req.request_id] = false;
  }
};

const addFriend = async () => {
  if (!inputCode.value || loading.value) return;
  document.activeElement?.blur();
  loading.value = true;

  try {
    const res = await ApiService.sendFriendRequest(props.user.id, inputCode.value);
    if (res.success) {
      ToastService.success(res.message || `¡Solicitud de amistad enviada! 👥`);
      inputCode.value = '';
      isInviteExpanded.value = false;
      activeRequestTab.value = 'sent';
      loadFriends();
      loadFriendRequests();
    }
  } catch (e) {
    ToastService.error(e.message || 'Error al enviar solicitud.');
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

const handleSwipeClose = (friendId) => {
  if (activeSwipeFriendId.value === friendId) {
    activeSwipeFriendId.value = null;
  }
};

const promptRemoveFriend = (friend) => {
  friendToRemove.value = friend;
  isRemoveModalOpen.value = true;
};

const closeRemoveModal = () => {
  isRemoveModalOpen.value = false;
  friendToRemove.value = null;
  activeSwipeFriendId.value = null;
};

const confirmRemoveFriend = async () => {
  if (!friendToRemove.value || removeLoading.value) return;
  const friend = friendToRemove.value;
  removeLoading.value = true;
  try {
    const res = await ApiService.removeFriend(props.user.id, friend.id);
    if (res.success) {
      ToastService.success(`Eliminaste a ${friend.display_name} de tus amigos.`);
      friends.value = friends.value.filter(f => f.id !== friend.id);
      delete nudgedFriends.value[friend.id];
      closeRemoveModal();
    }
  } catch (e) {
    ToastService.error(e.message || 'Error al eliminar amigo.');
  } finally {
    removeLoading.value = false;
  }
};

onMounted(() => {
  loadFriends();
  loadFriendRequests();
  generateQrCode();
});
</script>
