<template>
  <AppPage title="Ajustes" :back-to="{ name: 'profile' }">
    <div class="space-y-4">
      <!-- Título de Sección: Configuración -->
      <h3 class="font-extrabold text-white text-lg flex items-center gap-3">
        <Settings class="w-6 h-6 text-sky-400 stroke-[2.5]" />
        <span>Configuración</span>
      </h3>

      <!-- Ajustes de Notificación Diaria -->
      <ExpandableCard
        v-model="isReminderExpanded"
        title="Recordatorio diario"
        :description="`Programado a las ${reminderTime || '20:00'} hrs`"
        icon-bg-class="bg-amber-500/10 border-amber-500/30"
      >
        <template #icon>
          <Bell class="w-5 h-5 text-amber-400 stroke-[2.5]" />
        </template>

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
            <span>{{ testingNotification ? 'Programando...' : 'Probar notificación' }}</span>
          </button>
        </div>
      </ExpandableCard>

      <!-- Datos de Perfil -->
      <ExpandableCard
        v-model="isProfileExpanded"
        title="Datos de perfil"
        description="Ve o edita los datos de cuenta"
        icon-bg-class="bg-brand-green/10 border-brand-green/30"
      >
        <template #icon>
          <UserCheck class="w-5 h-5 text-brand-green stroke-[2.5]" />
        </template>

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

          <!-- Usuario (Editable) -->
          <div class="space-y-1.5">
            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Usuario</label>
            <div class="relative flex items-center">
              <span class="absolute left-3.5 text-slate-400 font-mono pointer-events-none">@</span>
              <input
                v-model="editUsername"
                type="text"
                placeholder="usuario"
                maxlength="20"
                class="w-full bg-slate-900 border border-slate-800 focus:border-brand-green text-white font-mono font-bold rounded-2xl pl-8 pr-4 py-3 text-base focus:outline-none transition-colors lowercase"
                @keyup.enter="saveProfile"
              />
            </div>
            <p v-if="editUsername && !isUsernameValid" class="text-rose-400 text-xs font-semibold">
              3-20 caracteres: minúsculas, números o guion bajo.
            </p>
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
          :disabled="savingProfile || !hasProfileChanges || !isUsernameValid"
          @click="saveProfile"
        >
          <span v-if="savingProfile">Guardando...</span>
          <span v-else>Guardar datos</span>
        </AppButton>
      </ExpandableCard>

      <!-- Guía y Tutorial / Tour de Bienvenida -->
      <ExpandableCard
        :collapsible="false"
        title="Guía de inicio"
        description="Aprende cómo funciona la racha y los amigos"
        icon-bg-class="bg-indigo-500/10 border-indigo-500/30"
      >
        <template #icon>
          <Compass class="w-5 h-5 text-indigo-400 stroke-[2.5]" />
        </template>
        <template #action>
          <AppButton color="blue" @click="openTour">
            Ver tour
          </AppButton>
        </template>
      </ExpandableCard>
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
        @click="isFeedbackModalOpen = true"
        class="w-full bg-slate-800/90 hover:bg-slate-700/80 text-slate-300 hover:text-white font-bold py-3.5 px-4 rounded-2xl border-2 border-slate-700 hover:border-sky-400/50 transition-colors text-base flex items-center justify-center gap-3 cursor-pointer"
      >
        <MessageSquarePlus class="w-5 h-5 text-sky-400 stroke-[2.5]" />
        <span>Enviar sugerencia</span>
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

    <FeedbackModal
      :is-open="isFeedbackModalOpen"
      @close="isFeedbackModalOpen = false"
    />
  </AppPage>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { UserRound, Bell, LogOut, UserCheck, Mail, Globe, CheckCircle2, Compass, Settings, Star, MessageSquarePlus } from '@lucide/vue';
import AppPage from '../components/AppPage.vue';
import AppButton from '../components/AppButton.vue';
import AppModal from '../components/AppModal.vue';
import ExpandableCard from '../components/ExpandableCard.vue';
import FeedbackModal from '../components/FeedbackModal.vue';
import { NotificationService } from '../services/notifications';
import { ApiService } from '../services/api';
import { ToastService } from '../services/toast';
import { StorageService } from '../services/storage';
import { ReviewService } from '../services/review';
import versionInfo from '../version.json';

const appVersion = versionInfo.version;

const props = defineProps({
  user: { type: Object, required: true }
});

const emit = defineEmits(['logout', 'user-updated', 'open-tour']);

const isLogoutModalOpen = ref(false);
const isFeedbackModalOpen = ref(false);

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
const editUsername = ref('');
const savingProfile = ref(false);
const currentTimezone = ref('UTC');
const isProfileExpanded = ref(false);

const isNameChanged = computed(() => {
  return editDisplayName.value.trim() !== '' && editDisplayName.value.trim() !== (props.user.display_name || '');
});

const isUsernameChanged = computed(() => {
  return editUsername.value.trim() !== '' && editUsername.value.trim().toLowerCase() !== (props.user.username || '');
});

const isUsernameValid = computed(() => /^[a-z0-9_]{3,20}$/.test(editUsername.value.trim().toLowerCase()));

const hasProfileChanges = computed(() => isNameChanged.value || isUsernameChanged.value);

watch(() => props.user, (newUser) => {
  if (newUser) {
    editDisplayName.value = newUser.display_name || '';
    editUsername.value = newUser.username || '';
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
  if (!isUsernameValid.value) {
    ToastService.error('El usuario debe tener 3-20 caracteres: minúsculas, números o guion bajo.');
    return;
  }

  savingProfile.value = true;
  try {
    const res = await ApiService.updateProfile(props.user.id, {
      display_name: newName,
      username: editUsername.value.trim().toLowerCase(),
      timezone: currentTimezone.value
    });

    if (res && res.user) {
      emit('user-updated', res.user);
    } else {
      emit('user-updated', { display_name: newName, username: editUsername.value.trim().toLowerCase() });
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

onMounted(async () => {
  editDisplayName.value = props.user.display_name || '';
  currentTimezone.value = props.user.timezone || Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC';

  const saved = await StorageService.get('reminder_time');
  if (saved) {
    reminderTime.value = saved;
  } else if (props.user.reminder_time) {
    reminderTime.value = props.user.reminder_time;
  }

  // Trae los datos propios del servidor en vez de confiar en el objeto user cacheado
  // localmente (pudo quedar desactualizado, ej. username agregado despues del login).
  // Usa un endpoint propio y liviano (solo lo que esta pantalla necesita), no el de
  // perfil completo con racha/seguidores/historial.
  try {
    const res = await ApiService.getSettings();
    if (res.success) {
      emit('user-updated', res.user);
      editDisplayName.value = res.user.display_name || '';
      editUsername.value = res.user.username || '';
      if (res.user.timezone) {
        currentTimezone.value = res.user.timezone;
      }
    }
  } catch (e) {
    console.warn('No se pudo refrescar los datos de perfil:', e.message);
  }
});
</script>
