<template>
  <AppPage
    title="Ajustes"
    :back-route="{ name: 'profile' }"
  >
    <SectionTitle title="Configuración" :icon="Settings" icon-color-class="text-sky-400">
      <div class="space-y-3">
        <!-- Datos de Perfil -->
        <ExpandableCard
          v-model="isProfileExpanded"
          title="Datos de perfil"
          description="Ve o edita los datos de cuenta"
          icon-bg-class="bg-brand-green/10 border-brand-green/30"
          icon-color-class="text-brand-green"
          :icon="UserCheck"
        >
          <div class="space-y-3">
            <!-- Nombre de Usuario (Editable) -->
            <AppFormField label="Nombre">
              <AppTextInput
                v-model="editDisplayName"
                :icon="UserRound"
                autocapitalize="words"
                placeholder="Tu nombre de usuario"
                @keyup.enter="saveProfile"
              />
            </AppFormField>

            <!-- Usuario (Editable) -->
            <AppFormField label="Usuario" :error="editUsername && !isUsernameValid ? '3-20 caracteres: minúsculas, números o guion bajo.' : ''">
              <AppTextInput
                v-model="editUsername"
                :icon="AtSign"
                placeholder="usuario"
                maxlength="20"
                input-class="lowercase"
                @keyup.enter="saveProfile"
              />
            </AppFormField>

            <!-- Correo Electrónico (Solo Lectura con Badge) -->
            <AppFormField label="Correo electrónico">
              <AppTextInput :model-value="user.email || 'Autenticación Social'" :icon="Mail" type="email" disabled>
                <template #suffix>
                  <span class="absolute right-3 bg-slate-800 text-emerald-400 border border-emerald-500/30 text-xs px-2.5 py-1 rounded-xl flex items-center gap-1">
                    <CheckCircle2 class="w-3.5 h-3.5 stroke-[2.5]" /> Verificado
                  </span>
                </template>
              </AppTextInput>
            </AppFormField>

            <!-- Zona Horaria (Auto-detectada) -->
            <AppFormField label="Zona horaria">
              <AppTextInput :model-value="currentTimezone" :icon="Globe" disabled />
            </AppFormField>
          </div>

          <AppButton
            color="green"
            block
            :disabled="saveProfileAction.loading.value || !hasProfileChanges || !isUsernameValid"
            :text="saveProfileAction.loading.value ? 'Guardando...' : 'Guardar datos'"
            @click="saveProfile"
          />
        </ExpandableCard>

        <!-- Categorias de Notificacion -->
        <ExpandableCard
          v-model="isNotificationsExpanded"
          title="Notificaciones"
          description="Elige qué avisos quieres recibir"
          icon-bg-class="bg-sky-500/10 border-sky-500/30"
          icon-color-class="text-sky-400"
          :icon="BellRing"
        >
          <div class="space-y-1">
            <div v-for="cat in NOTIFICATION_CATEGORIES" :key="cat.key" class="border-b border-slate-800/70 last:border-0">
              <div class="flex items-center justify-between gap-3 py-2.5">
                <div class="min-w-0">
                  <p class="text-base font-semibold text-white">{{ cat.label }}</p>
                  <p class="text-sm text-slate-400 font-medium">{{ cat.description }}</p>
                </div>
                <AppToggle v-model="notificationPrefs[cat.key]" />
              </div>

              <!-- Hora del recordatorio: solo tiene sentido con la categoria activa -->
              <div v-if="cat.key === 'daily_reminder' && notificationPrefs.daily_reminder" class="pb-3 space-y-2.5">
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
                  type="button"
                  @click="triggerTestNotification"
                  :disabled="testingNotification"
                  class="text-amber-400/80 hover:text-amber-300 font-semibold text-sm underline underline-offset-2 decoration-amber-400/40 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {{ testingNotification ? 'Programando...' : 'Probar notificación' }}
                </button>
              </div>
            </div>
          </div>

          <AppButton
            color="green"
            block
            :disabled="savePrefsAction.loading.value || !prefsDirty"
            :text="savePrefsAction.loading.value ? 'Guardando...' : 'Guardar notificaciones'"
            @click="saveNotificationPrefs"
          />
        </ExpandableCard>

        <BookSettingsCard />
      </div>
    </SectionTitle>

    <SectionTitle title="Más" :icon="MoreHorizontal" icon-color-class="text-slate-400">
      <!-- Botones de Acción -->
      <div class="space-y-3">
        <SettingsActionButton
          :icon="Star"
          class="hover:border-amber-400/50 [&_svg]:text-amber-400 [&_svg]:fill-amber-400"
          @click="rateApp"
        >
          Calificar la aplicación
        </SettingsActionButton>

        <SettingsActionButton
          :icon="MessageSquarePlus"
          class="hover:border-sky-400/50 [&_svg]:text-sky-400"
          @click="isFeedbackModalOpen = true"
        >
          Enviar sugerencia
        </SettingsActionButton>

        <SettingsActionButton
          :icon="LogOut"
          variant="danger"
          class="[&_svg]:text-rose-400"
          @click="isLogoutModalOpen = true"
        >
          Cerrar sesión
        </SettingsActionButton>

      <div class="flex flex-col pt-8">
        <a
          href="https://www.libringo.com/privacidad"
          target="_blank"
          rel="noopener noreferrer"
          class="text-center text-sm font-semibold text-slate-500 hover:text-slate-300 p-2 mx-auto"
        >
          Política de privacidad
        </a>

        <button
          @click="isDeleteAccountModalOpen = true"
          class="text-center text-sm font-semibold text-slate-500 hover:text-slate-300 p-2 mx-auto cursor-pointer"
        >
          Eliminar cuenta
        </button>

        <button
          v-if="APP_CONFIG.isDev"
          class="text-center text-sm font-semibold text-slate-500 hover:text-slate-300 p-2 mx-auto cursor-pointer"
          @click="openTour"
        >
          Guía de inicio [dev]
        </button>

        <p class="text-center text-sm font-medium text-slate-600 py-2">
          Versión {{ appVersion }}
        </p>
      </div>
    </div>
    </SectionTitle>

    <!-- Modal Confirmación de Cerrar Sesión -->
    <ConfirmActionModal
      :is-open="isLogoutModalOpen"
      :icon="LogOut"
      title="¿Cerrar sesión?"
      description="Tu racha y tus progresos de lectura están guardados en tu cuenta."
      confirm-label="Cerrar sesión"
      @close="isLogoutModalOpen = false"
      @confirm="confirmLogout"
    />

    <!-- Modal Confirmación de Eliminar Cuenta -->
    <ConfirmActionModal
      :is-open="isDeleteAccountModalOpen"
      :icon="Trash2"
      title="¿Eliminar tu cuenta?"
      description="Se borrará tu racha, amigos e historial de lectura. Esta acción no se puede deshacer."
      confirm-label="Eliminar"
      @close="isDeleteAccountModalOpen = false"
      @confirm="confirmDeleteAccount"
    />

    <FeedbackModal
      :is-open="isFeedbackModalOpen"
      @close="isFeedbackModalOpen = false"
    />
  </AppPage>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue';
import {
  UserRound,
  AtSign,
  BellRing,
  LogOut,
  Trash2,
  UserCheck,
  Mail,
  Globe,
  CheckCircle2,
  Settings,
  Star,
  MessageSquarePlus,
  MoreHorizontal
} from '@lucide/vue';
import AppPage from '@/components/AppPage.vue';
import SectionTitle from '@/components/SectionTitle.vue';
import AppButton from '@/components/AppButton.vue';
import AppFormField from '@/components/AppFormField.vue';
import AppTextInput from '@/components/AppTextInput.vue';
import AppToggle from '@/components/AppToggle.vue';
import ConfirmActionModal from '@/components/ConfirmActionModal.vue';
import ExpandableCard from '@/components/ExpandableCard.vue';
import SettingsActionButton from '@/components/SettingsActionButton.vue';
import FeedbackModal from '@/components/FeedbackModal.vue';
import BookSettingsCard from '@/components/BookSettingsCard.vue';
import { NotificationService } from '@/services/notifications';
import { ApiService } from '@/services/api';
import { ToastService } from '@/services/toast';
import { StorageService } from '@/services/storage';
import { ReviewService } from '@/services/review';
import { useAsyncAction } from '@/composables/useAsyncAction';
import versionInfo from '@/version.json';
import {APP_CONFIG} from "@/constants.js";

const appVersion = versionInfo.version;

// daily_reminder y freeze_used tienen efecto real en el cliente (cancelar/reprogramar
// recordatorios locales, mostrar u ocultar el toast de "protector te salvo"). El resto
// (streak_at_risk, new_follower, friend_activity, nudge, news) son push del servidor:
// new_follower y nudge ya se respetan en el backend (DomainEventProcessor), los demas
// todavia no tienen ningun disparador — quedan guardados listos para cuando se agregue.
const NOTIFICATION_CATEGORIES = [
  { key: 'daily_reminder', label: 'Recordatorio de lectura', description: 'Aviso diario a la hora que elijas.' },
  { key: 'streak_at_risk', label: 'Racha en peligro', description: 'Si el día está por acabar sin haber leído.' },
  { key: 'freeze_used', label: 'Uso del protector de racha', description: 'Cuando un protector salva tu racha.' },
  { key: 'new_follower', label: 'Nuevo seguidor', description: 'Cuando alguien empieza a seguirte.' },
  // { key: 'friend_activity', label: 'Actividad de amigos', description: 'Cuando un amigo lee o sube de racha.' },
  { key: 'nudge', label: 'Toques', description: 'Cuando un amigo te manda un recordatorio.' },
  { key: 'news', label: 'Novedades de Libringo', description: 'Anuncios y nuevas funciones de la app.' },
];

const props = defineProps({
  user: { type: Object, required: true }
});

const emit = defineEmits(['logout', 'delete-account', 'user-updated', 'open-tour']);

const isLogoutModalOpen = ref(false);
const isFeedbackModalOpen = ref(false);
const isDeleteAccountModalOpen = ref(false);

const openTour = () => {
  emit('open-tour');
};

const confirmLogout = () => {
  isLogoutModalOpen.value = false;
  emit('logout');
};

const confirmDeleteAccount = () => {
  isDeleteAccountModalOpen.value = false;
  emit('delete-account');
};

const rateApp = async () => {
  const requested = await ReviewService.requestReview({ force: true });
  if (!requested && !ReviewService.isAvailable()) {
    ToastService.info('La calificación en tienda está disponible en la app instalada.');
  }
};

const reminderTime = ref('20:00');
const editDisplayName = ref('');
const editUsername = ref('');
const saveProfileAction = useAsyncAction();
const currentTimezone = ref('UTC');
const isProfileExpanded = ref(false);
const isNotificationsExpanded = ref(false);

const DEFAULT_PREFS = Object.fromEntries(NOTIFICATION_CATEGORIES.map((c) => [c.key, true]));
const notificationPrefs = reactive({ ...DEFAULT_PREFS });
const savedPrefs = ref({ ...DEFAULT_PREFS });
const savePrefsAction = useAsyncAction();

const savedReminderTime = ref('20:00');
const prefsDirty = computed(() => {
  const prefsChanged = NOTIFICATION_CATEGORIES.some((c) => notificationPrefs[c.key] !== savedPrefs.value[c.key]);
  const timeChanged = notificationPrefs.daily_reminder && reminderTime.value !== savedReminderTime.value;
  return prefsChanged || timeChanged;
});

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
    if (newUser.notification_prefs) {
      Object.assign(notificationPrefs, newUser.notification_prefs);
      savedPrefs.value = { ...newUser.notification_prefs };
    }
  }
}, { immediate: true });

const saveNotificationPrefs = async () => {
  // Solo manda las categorias que realmente cambiaron desde el ultimo guardado.
  const changedPrefs = Object.fromEntries(
    NOTIFICATION_CATEGORIES
      .map((c) => c.key)
      .filter((key) => notificationPrefs[key] !== savedPrefs.value[key])
      .map((key) => [key, notificationPrefs[key]])
  );
  const timeChanged = notificationPrefs.daily_reminder && reminderTime.value !== savedReminderTime.value;

  if (Object.keys(changedPrefs).length === 0 && !timeChanged) return;

  const res = await savePrefsAction.run(async () => {
    if (Object.keys(changedPrefs).length > 0) {
      await ApiService.updateNotificationPrefs(changedPrefs);
    }
    if (timeChanged) {
      await NotificationService.persistReminderTime(reminderTime.value);
    }
    return true;
  }, {
    successMsg: 'Preferencias de notificación guardadas.',
    errorMsg: 'No se pudo guardar las notificaciones.'
  });

  if (res === undefined) return;

  Object.assign(savedPrefs.value, notificationPrefs);
  savedReminderTime.value = reminderTime.value;
  emit('user-updated', { ...props.user, notification_prefs: { ...notificationPrefs }, reminder_time: reminderTime.value });

  if (notificationPrefs.daily_reminder) {
    await NotificationService.requestPermissions();
    await NotificationService.initPushNotifications(props.user.id);
    await NotificationService.schedule7DayBurst(reminderTime.value, props.user.streak_count, props.user.has_read_today || false, props.user.streak_freezes || 0);
  } else {
    await NotificationService.cancelReminders();
  }
};

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

  const res = await saveProfileAction.run(() => ApiService.updateProfile({
    display_name: newName,
    username: editUsername.value.trim().toLowerCase(),
    timezone: currentTimezone.value
  }), {
    successMsg: '¡Perfil actualizado con éxito! ✨',
    errorMsg: 'No se pudo actualizar el perfil.'
  });

  if (res === undefined) return;

  if (res && res.user) {
    emit('user-updated', res.user);
  } else {
    emit('user-updated', { display_name: newName, username: editUsername.value.trim().toLowerCase() });
  }
  isProfileExpanded.value = false;
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
  savedReminderTime.value = reminderTime.value;

  // Trae los datos propios del servidor en vez de confiar en el objeto user cacheado
  // localmente (pudo quedar desactualizado, ej. username agregado despues del login).
  // Usa un endpoint propio y liviano (solo lo que esta pantalla necesita), no el de
  // perfil completo con racha/seguidores/historial.
  try {
    const res = await ApiService.getSettings();
    if (res.success) {
      emit('user-updated', { ...res.user, notification_prefs: res.notification_prefs });
      editDisplayName.value = res.user.display_name || '';
      editUsername.value = res.user.username || '';
      if (res.user.timezone) {
        currentTimezone.value = res.user.timezone;
      }
      if (res.notification_prefs) {
        Object.assign(notificationPrefs, res.notification_prefs);
        savedPrefs.value = { ...res.notification_prefs };
      }
    }
  } catch (e) {
    console.warn('No se pudo refrescar los datos de perfil:', e.message);
  }
});
</script>
