<template>
  <div class="space-y-6">
    <div class="space-y-4">
      <!-- Header Perfil con Resplandor Azul -->
      <div class="relative card-duo bg-slate-900 bg-[radial-gradient(ellipse_at_top_right,_rgba(28,176,246,0.22),_transparent_65%)] border-sky-500/30 text-center py-6 space-y-3 overflow-hidden">
        <div class="w-20 h-20 bg-gradient-to-tr from-brand-blue to-sky-400 rounded-full flex items-center justify-center shadow-xl mx-auto border-4 border-slate-800">
          <UserRound class="w-10 h-10 text-white stroke-[2.5]" />
        </div>
        <div>
          <h2 class="text-2xl font-extrabold text-white">{{ user.display_name }}</h2>
          <p class="text-slate-300 text-base font-medium">Código: <span class="font-mono text-amber-400 font-bold">{{ user.invite_code }}</span></p>
          <p v-if="memberSinceLabel" class="text-slate-400 text-sm font-medium">Leyendo desde {{ memberSinceLabel }}</p>
        </div>
        <div class="flex items-center justify-center gap-6 text-slate-300 text-base font-medium">
          <button type="button" @click="openFollowList('followers')" class="cursor-pointer hover:text-white transition-colors">
            <strong class="text-white font-extrabold">{{ user.followers_count || 0 }}</strong> {{ user.followers_count === 1 ? 'seguidor' : 'seguidores' }}
          </button>
          <button type="button" @click="openFollowList('following')" class="cursor-pointer hover:text-white transition-colors">
            <strong class="text-white font-extrabold">{{ user.following_count || 0 }}</strong> seguidos
          </button>
        </div>
      </div>

      <!-- Constancia total (distinto de la racha: dias leidos aunque no sean consecutivos) -->
      <div class="card-duo bg-slate-900/90 border-slate-800 p-4 flex items-center justify-center gap-3">
        <BookOpenCheck class="w-6 h-6 text-brand-green stroke-[2.5]" />
        <span class="text-slate-200 text-base font-semibold">
          <span class="text-brand-green font-extrabold text-xl">{{ user.total_days_read || 0 }}</span>
          días leídos en total
        </span>
      </div>

      <!-- Lecturas favoritas: desglose de reacciones registradas dia a dia -->
      <div v-if="reactionBreakdown.length > 0" class="card-duo bg-slate-900/90 border-slate-800 p-4 space-y-3">
        <h4 class="font-extrabold text-white text-base">Tus lecturas favoritas</h4>
        <div class="flex flex-wrap gap-2">
          <div
            v-for="r in reactionBreakdown"
            :key="r.id"
            class="flex items-center gap-1.5 bg-slate-950/70 border border-slate-800 rounded-xl px-3 py-1.5"
          >
            <span class="text-lg leading-none">{{ r.emoji }}</span>
            <span class="text-slate-200 text-sm font-bold">{{ r.count }}</span>
            <span class="text-slate-400 text-sm font-medium">{{ r.label }}</span>
          </div>
        </div>
      </div>

      <!-- Estadísticas Globales -->
      <div class="grid grid-cols-2 gap-3">
        <div class="card-duo bg-slate-900/90 border-slate-800 p-4 text-center space-y-2">
          <div class="flex items-center justify-center gap-3">
            <span v-if="user.is_streak_lost" class="text-2xl leading-none">🥶</span>
            <Flame v-else class="w-7 h-7 text-amber-400 stroke-[2.5]" />
            <div class="text-3xl font-extrabold text-amber-400">{{ user.is_streak_lost ? 0 : user.streak_count }}</div>
          </div>
          <div class="text-slate-300 text-base font-semibold uppercase tracking-wider">Racha<br/>actual</div>
        </div>
        <div class="card-duo bg-slate-900/90 border-slate-800 p-4 text-center space-y-2">
          <div class="flex items-center justify-center gap-3">
            <Zap class="w-7 h-7 text-purple-400 stroke-[2.5]" />
            <div class="text-3xl font-extrabold text-purple-400">{{ user.max_streak_count }}</div>
          </div>
          <div class="text-slate-300 text-base font-semibold uppercase tracking-wider">Racha<br/>máxima</div>
        </div>
      </div>

      <!-- Protectores de racha -->
      <div class="grid grid-cols-2 gap-3">
        <div class="card-duo bg-slate-900/90 border-slate-800 p-4 text-center space-y-2">
          <div class="flex items-center justify-center gap-3">
            <span class="text-2xl leading-none">🧊</span>
            <div class="text-3xl font-extrabold text-sky-400">{{ user.streak_freezes || 0 }}</div>
          </div>
          <div class="text-slate-300 text-base font-semibold uppercase tracking-wider">Protectores<br/>disponibles</div>
        </div>
        <div class="card-duo bg-slate-900/90 border-slate-800 p-4 text-center space-y-2">
          <div class="flex items-center justify-center gap-3">
            <span class="text-2xl leading-none">📖</span>
            <div class="text-3xl font-extrabold text-slate-400">{{ user.streak_freezes_used || 0 }}</div>
          </div>
          <div class="text-slate-300 text-base font-semibold uppercase tracking-wider">Protectores<br/>usados</div>
        </div>
      </div>

      <!-- Tracker semanal de 7 días (Lun - Dom) -->
      <WeeklyTracker :target-id="user.id" />
    </div>

    <div class="space-y-4">
      <!-- Título de Sección: Configuración -->
      <h3 class="font-extrabold text-white text-lg flex items-center gap-3">
        <Settings class="w-6 h-6 text-sky-400 stroke-[2.5]" />
        <span>Configuración</span>
      </h3>

      <!-- Ajustes de Notificación Diaria (Desplegable / Accordion) -->
      <div class="card-duo transition-all duration-200">
        <div 
          @click="isReminderExpanded = !isReminderExpanded" 
          class="flex items-center justify-between cursor-pointer select-none gap-3"
        >
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center shrink-0">
              <Bell class="w-5 h-5 text-amber-400 stroke-[2.5]" />
            </div>
            <div>
              <h3 class="font-extrabold text-white text-lg">Recordatorio diario</h3>
              <p class="text-slate-300 text-base font-medium">
                {{ isReminderExpanded ? 'Ocultar ajustes de hora' : `Programado a las ${reminderTime || '20:00'} hrs` }}
              </p>
            </div>
          </div>
          <component 
            :is="isReminderExpanded ? ChevronUp : ChevronDown" 
            class="w-6 h-6 text-slate-400 stroke-[2.5] transition-transform duration-200" 
          />
        </div>

        <!-- Contenido desplegable -->
        <div v-if="isReminderExpanded" class="space-y-4 pt-4 mt-4 border-t border-slate-800">
          <p class="text-slate-300 text-base font-medium">
            Te notificaremos cada día para ayudarte a mantener y proteger tu racha de lectura.
          </p>

          <div class="flex items-center justify-between bg-slate-900 border border-slate-800 p-3 rounded-2xl">
            <span class="text-base font-bold text-slate-200">Hora de lectura:</span>
            <input 
              v-model="reminderTime" 
              type="time" 
              step="600"
              class="bg-slate-800 border border-slate-700 text-amber-400 font-extrabold rounded-xl px-3 py-1.5 text-base focus:outline-none focus:border-brand-green"
            />
          </div>

          <div class="flex flex-col gap-2.5 pt-1">
            <AppButton 
              color="green"
              block
              @click="saveReminder" 
            >
              Guardar recordatorio
            </AppButton>

            <button 
              type="button"
              @click="triggerTestNotification" 
              :disabled="testingNotification"
              class="w-full bg-slate-900/90 hover:bg-slate-800 text-amber-300 font-bold py-3 px-4 rounded-2xl border-2 border-slate-700 hover:border-amber-400/50 transition-colors text-sm flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
            >
              <Bell class="w-4 h-4 text-amber-400 stroke-[2.5]" />
              <span>{{ testingNotification ? 'Programando...' : 'Probar notificación (en 3 segundos)' }}</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Datos de Perfil (Desplegable / Accordion) -->
      <div class="card-duo transition-all duration-200">
        <div 
          @click="isProfileExpanded = !isProfileExpanded" 
          class="flex items-center justify-between cursor-pointer select-none gap-3"
        >
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-brand-green/10 border border-brand-green/30 flex items-center justify-center shrink-0">
              <UserCheck class="w-5 h-5 text-brand-green stroke-[2.5]" />
            </div>
            <div>
              <h3 class="font-extrabold text-white text-lg">Datos de perfil</h3>
              <p class="text-slate-300 text-base font-medium">
                {{ isProfileExpanded ? 'Ocultar información personal' : 'Toca para ver o editar tu información' }}
              </p>
            </div>
          </div>
          <component 
            :is="isProfileExpanded ? ChevronUp : ChevronDown" 
            class="w-6 h-6 text-slate-400 stroke-[2.5] transition-transform duration-200" 
          />
        </div>

        <!-- Contenido del formulario al desplegar -->
        <div v-if="isProfileExpanded" class="space-y-4 pt-4 mt-4 border-t border-slate-800">
          <div class="space-y-3">
            <!-- Nombre de Usuario (Editable) -->
            <div class="space-y-1.5">
              <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Nombre</label>
              <div class="relative flex items-center">
                <UserRound class="w-5 h-5 text-slate-400 absolute left-3.5 pointer-events-none stroke-[2.5]" />
                <input 
                  v-model="editDisplayName" 
                  type="text" 
                  placeholder="Tu nombre de usuario"
                  class="w-full bg-slate-900 border border-slate-800 focus:border-brand-green text-white font-bold rounded-2xl pl-11 pr-4 py-3 text-base focus:outline-none transition-colors"
                  @keyup.enter="saveProfile"
                />
              </div>
            </div>

            <!-- Correo Electrónico (Solo Lectura con Badge) -->
            <div class="space-y-1.5">
              <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Correo electrónico</label>
              <div class="relative flex items-center">
                <Mail class="w-5 h-5 text-slate-500 absolute left-3.5 pointer-events-none stroke-[2.5]" />
                <input 
                  :value="user.email || 'Autenticación Social'" 
                  type="email" 
                  disabled 
                  class="w-full bg-slate-900/60 border border-slate-800/80 text-slate-400 font-medium rounded-2xl pl-11 pr-28 py-3 text-base select-none cursor-not-allowed"
                />
                <span class="absolute right-3 bg-slate-800 text-emerald-400 border border-emerald-500/30 text-xs font-bold px-2.5 py-1 rounded-xl flex items-center gap-1">
                  <CheckCircle2 class="w-3.5 h-3.5 stroke-[2.5]" /> Verificado
                </span>
              </div>
            </div>

            <!-- Zona Horaria (Auto-detectada) -->
            <div class="space-y-1.5">
              <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Zona horaria</label>
              <div class="relative flex items-center">
                <Globe class="w-5 h-5 text-slate-400 absolute left-3.5 pointer-events-none stroke-[2.5]" />
                <input 
                  :value="currentTimezone" 
                  type="text" 
                  disabled
                  class="w-full bg-slate-900/60 border border-slate-800/80 text-slate-300 font-medium rounded-2xl pl-11 pr-4 py-3 text-base select-none cursor-not-allowed"
                />
              </div>
            </div>
          </div>

          <AppButton 
            color="green"
            block
            :disabled="savingProfile || !isNameChanged"
            @click="saveProfile" 
          >
            <span v-if="savingProfile">Guardando...</span>
            <span v-else>Guardar datos</span>
          </AppButton>
        </div>
      </div>

      <!-- Guía y Tutorial / Tour de Bienvenida -->
      <div class="card-duo">
        <div class="flex items-center justify-between gap-3">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-indigo-500/10 border border-indigo-500/30 flex items-center justify-center shrink-0">
              <Compass class="w-5 h-5 text-indigo-400 stroke-[2.5]" />
            </div>
            <div>
              <h3 class="font-extrabold text-white text-lg">Guía de inicio</h3>
              <p class="text-slate-300 text-base font-medium">
                Aprende cómo funciona la racha y los amigos
              </p>
            </div>
          </div>
          <AppButton 
            color="blue"
            @click="openTour"
          >
            Ver tour
          </AppButton>
        </div>
      </div>
    </div>

    <!-- Botones de Acción -->
    <div class="space-y-3">
      <button 
        @click="rateApp" 
        class="w-full bg-slate-800/90 hover:bg-slate-700/80 text-slate-300 hover:text-white font-bold py-3.5 px-4 rounded-2xl border-2 border-slate-700 hover:border-amber-400/50 transition-colors text-base flex items-center justify-center gap-3 cursor-pointer"
      >
        <Star class="w-5 h-5 text-amber-400 fill-amber-400 stroke-[2.5]" />
        <span>Calificar la aplicación</span>
      </button>

      <button
        @click="isLogoutModalOpen = true"
        class="w-full bg-slate-800 hover:bg-rose-950/40 text-slate-400 hover:text-rose-300 font-bold py-3.5 px-4 rounded-2xl border-2 border-slate-700 hover:border-rose-800 transition-colors text-base flex items-center justify-center gap-3 cursor-pointer"
      >
        <LogOut class="w-5 h-5 text-rose-400 stroke-[2.5]" />
        <span>Cerrar sesión</span>
      </button>

      <div>
        <a
          href="https://www.biblingo.me/privacidad"
          target="_blank"
          rel="noopener noreferrer"
          class="block text-center text-sm font-semibold text-slate-500 hover:text-slate-300 py-2"
        >
          Política de privacidad
        </a>

        <p class="text-center text-sm font-medium text-slate-600">
          Versión {{ appVersion }}
        </p>
      </div>
    </div>

    <!-- Modal Confirmación de Cerrar Sesión -->
    <AppModal
      :is-open="isLogoutModalOpen"
      title="¿Cerrar sesión?"
      description="Tu racha y tus progresos de lectura están guardados en tu cuenta."
      @close="isLogoutModalOpen = false"
      :show-close="false"
    >
      <template #icon>
        <div class="w-11 h-11 rounded-2xl bg-rose-500/10 border border-rose-500/30 flex items-center justify-center shrink-0">
          <LogOut class="w-5 h-5 text-rose-400 stroke-[2.5]" />
        </div>
      </template>

      <template #footer>
        <div class="flex items-center gap-3 w-full">
          <div class="flex-1">
            <AppButton
              color="dark"
              block
              @click="isLogoutModalOpen = false"
            >
              Cancelar
            </AppButton>
          </div>
          <div class="flex-1">
            <AppButton
              color="rose"
              block
              @click="confirmLogout"
            >
              Cerrar sesión
            </AppButton>
          </div>
        </div>
      </template>
    </AppModal>

    <!-- Lista de Seguidores / Seguidos -->
    <FollowListModal
      :is-open="isFollowListOpen"
      :user-id="user.id"
      :initial-tab="followListInitialTab"
      :display-name="user.display_name"
      is-own-profile
      @close="closeFollowList"
      @change-tab="switchFollowListTab"
      @select-user="goToFriendProfile"
    />
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AppButton from '../components/AppButton.vue';
import AppModal from '../components/AppModal.vue';
import FollowListModal from '../components/FollowListModal.vue';
import { UserRound, Flame, Zap, Bell, LogOut, UserCheck, Mail, Globe, CheckCircle2, ChevronDown, ChevronUp, Compass, Settings, Star, BookOpenCheck } from '@lucide/vue';
import WeeklyTracker from '../components/WeeklyTracker.vue';
import { NotificationService } from '../services/notifications';
import { ApiService } from '../services/api';
import { ToastService } from '../services/toast';
import { StorageService } from '../services/storage';
import { ReviewService } from '../services/review';
import { formatMemberSince } from '../utils/dateFormatter';
import { READING_REACTIONS } from '../constants';
import versionInfo from '../version.json';

const appVersion = versionInfo.version;

const props = defineProps({
  user: { type: Object, required: true }
});

const emit = defineEmits(['logout', 'user-updated', 'open-tour']);

const route = useRoute();
const router = useRouter();

const isLogoutModalOpen = ref(false);

const reactionBreakdown = computed(() => {
  const counts = props.user.reaction_counts || {};
  return READING_REACTIONS
    .map((r) => ({ ...r, count: counts[r.id] || 0 }))
    .filter((r) => r.count > 0)
    .sort((a, b) => b.count - a.count);
});

// El modal se sincroniza con ?panel= en la URL: abrirlo empuja una entrada al
// historial, asi el boton atras del navegador lo cierra solo, y al volver desde el
// perfil de un amigo (navegado desde la lista) se reabre automaticamente.
const isFollowListOpen = computed(() => !!route.query.panel);
const followListInitialTab = computed(() => route.query.panel === 'following' ? 'following' : 'followers');

const openFollowList = (tab) => {
  router.push({ query: { ...route.query, panel: tab } });
};

// Cambiar entre Seguidores/Seguidos reemplaza la entrada actual en vez de apilar una
// nueva, para que "atras" cierre el modal en un solo paso sin importar cuantas veces
// se cambio de tab.
const switchFollowListTab = (tab) => {
  router.replace({ query: { ...route.query, panel: tab } });
};

const closeFollowList = () => {
  router.back();
};

const goToFriendProfile = (id) => {
  router.push({ name: 'friend-profile', params: { id } });
};

const openTour = () => {
  emit('open-tour');
};

const confirmLogout = () => {
  isLogoutModalOpen.value = false;
  emit('logout');
};

const rateApp = async () => {
  const requested = await ReviewService.requestReview({ force: true });
  if (!requested && !ReviewService.isAvailable()) {
    ToastService.info('La calificación en tienda está disponible en la app instalada.');
  }
};

const reminderTime = ref('20:00');
const isReminderExpanded = ref(false);
const editDisplayName = ref('');
const savingProfile = ref(false);
const currentTimezone = ref('UTC');
const isProfileExpanded = ref(false);

const isNameChanged = computed(() => {
  return editDisplayName.value.trim() !== '' && editDisplayName.value.trim() !== (props.user.display_name || '');
});

const memberSinceLabel = computed(() => formatMemberSince(props.user.member_since));

watch(() => props.user, (newUser) => {
  if (newUser) {
    editDisplayName.value = newUser.display_name || '';
    if (newUser.timezone) {
      currentTimezone.value = newUser.timezone;
    }
  }
}, { immediate: true });

const saveProfile = async () => {
  document.activeElement?.blur();
  const newName = editDisplayName.value.trim();
  if (!newName) {
    ToastService.error('El nombre no puede estar vacío.');
    return;
  }
  if (newName.length < 2 || newName.length > 50) {
    ToastService.error('El nombre debe tener entre 2 y 50 caracteres.');
    return;
  }

  savingProfile.value = true;
  try {
    const res = await ApiService.updateProfile(props.user.id, { 
      display_name: newName,
      timezone: currentTimezone.value
    });
    
    if (res && res.user) {
      emit('user-updated', res.user);
    } else {
      emit('user-updated', { display_name: newName });
    }
    ToastService.success('¡Perfil actualizado con éxito! ✨');
    isProfileExpanded.value = false;
  } catch (e) {
    ToastService.error(e.message || 'No se pudo actualizar el perfil.');
  } finally {
    savingProfile.value = false;
  }
};

const saveReminder = async () => {
  try {
    await StorageService.set('reminder_time', reminderTime.value);
    await ApiService.updateProfile(props.user.id, { reminder_time: reminderTime.value });
    await NotificationService.requestPermissions();
    await NotificationService.initPushNotifications(props.user.id);
    await NotificationService.schedule7DayBurst(reminderTime.value, props.user.streak_count, props.user.has_read_today || false);

    emit('user-updated', { ...props.user, reminder_time: reminderTime.value });

    ToastService.success('¡Recordatorio guardado! ⏰');
    isReminderExpanded.value = false;
  } catch (e) {
    ToastService.error('No se pudo guardar el recordatorio.');
  }
};

const testingNotification = ref(false);

const triggerTestNotification = async () => {
  testingNotification.value = true;
  try {
    await NotificationService.sendTestNotification(3);
  } finally {
    setTimeout(() => {
      testingNotification.value = false;
    }, 3500);
  }
};

const logout = () => {
  isLogoutModalOpen.value = true;
};

onMounted(async () => {
  editDisplayName.value = props.user.display_name || '';
  currentTimezone.value = props.user.timezone || Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC';

  const saved = await StorageService.get('reminder_time');
  if (saved) {
    reminderTime.value = saved;
  } else if (props.user.reminder_time) {
    reminderTime.value = props.user.reminder_time;
  }
});
</script>
