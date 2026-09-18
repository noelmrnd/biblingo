<template>
  <Transition name="tour-fade">
    <div
      v-if="isOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/85 backdrop-blur-md overflow-hidden select-none"
      :style="keyboardHeight > 0 ? { paddingBottom: `${keyboardHeight}px` } : undefined"
    >
      <!-- Fondo con gradientes de luz ambiental -->
      <div 
        :class="currentStepData.ambientColor" 
        class="absolute w-80 h-80 rounded-full blur-3xl pointer-events-none opacity-25 transition-all duration-500"
      ></div>

      <!-- Tarjeta Principal del Tour -->
      <div 
        class="relative w-full max-w-sm bg-brand-card border border-brand-border rounded-3xl p-6 shadow-2xl flex flex-col justify-between max-h-[90vh] overflow-y-auto no-scrollbar"
      >
        <!-- Header: Volver + Barra de Progreso -->
        <div class="space-y-3 flex-none">
          <div class="grid grid-cols-[2.5rem_1fr_2.5rem] items-center">
            <IconButton
              v-if="currentStep > 0"
              @click="prevStep"
              :haptic="false"
              aria-label="Paso anterior"
            >
              <ChevronLeft class="w-5 h-5 stroke-[2.5]" />
            </IconButton>
            <div v-else></div>

            <span class="text-base font-bold tracking-wider uppercase text-slate-400 text-center">
              Paso {{ currentStep + 1 }} de {{ steps.length }}
            </span>

            <div></div>
          </div>

          <!-- Indicador de Progreso Segmentado -->
          <div class="grid gap-1.5 h-1.5 w-full" :style="{ gridTemplateColumns: `repeat(${steps.length}, minmax(0, 1fr))` }">
            <div
              v-for="(_, index) in steps"
              :key="index"
              :class="[
                index <= currentStep ? 'bg-brand-green shadow-sm shadow-emerald-500/50' : 'bg-slate-700'
              ]"
              class="h-full rounded-full transition-all duration-300"
            ></div>
          </div>
        </div>

        <!-- Contenido Central Dinámico del Paso -->
        <div v-if="currentStepData.type === 'reminder-time'" class="py-6 flex flex-col items-center text-center space-y-4 flex-1 w-full">
          <div class="relative flex items-center justify-center w-full mt-6 mb-4">
            <img
              :src="currentStepData.image"
              :alt="currentStepData.title"
              class="h-40 object-contain drop-shadow-xl select-none pointer-events-none transition-all duration-300"
            />
          </div>

          <div class="space-y-3">
            <h3 class="text-2xl font-extrabold text-white leading-tight">
              {{ currentStepData.title }}
            </h3>
            <p class="text-slate-300 text-base font-medium leading-relaxed">
              {{ currentStepData.description }}
            </p>
          </div>

          <div class="w-full min-w-0 text-left">
            <label class="text-sm font-semibold text-slate-400">Hora del recordatorio</label>
            <div class="mt-1 w-full min-w-0 overflow-hidden rounded-xl bg-slate-950/60 border border-slate-800 focus-within:border-brand-green">
              <input
                v-model="reminderTime"
                type="time"
                class="w-full min-w-0 box-border bg-transparent px-4 py-3 text-white focus:outline-none"
              />
            </div>
          </div>
        </div>

        <div v-else-if="currentStepData.type !== 'book-config'" class="py-6 flex flex-col items-center text-center space-y-4 flex-1">
          <!-- Ilustración del paso desde /tour -->
          <div class="relative flex items-center justify-center w-full mt-6 mb-4">
            <img
              :src="currentStepData.image"
              :alt="currentStepData.title"
              class="h-40 object-contain drop-shadow-xl select-none pointer-events-none transition-all duration-300"
            />
          </div>

          <!-- Textos: Título y Descripción -->
          <div class="space-y-3">
            <h3 class="text-2xl font-extrabold text-white leading-tight">
              {{ currentStepData.title }}
            </h3>
            <p class="text-slate-300 text-base font-medium leading-relaxed">
              {{ currentStepData.description }}
            </p>
          </div>
        </div>

        <!-- Paso de configuracion: registrar el libro que esta leyendo -->
        <div v-else class="py-6 flex flex-col items-center text-center space-y-4 flex-1 w-full">
          <div class="relative flex items-center justify-center w-full mb-1">
            <img
              :src="currentStepData.image"
              :alt="currentStepData.title"
              class="h-28 object-contain drop-shadow-xl select-none pointer-events-none transition-all duration-300"
            />
          </div>

          <div class="space-y-3">
            <h3 class="text-2xl font-extrabold text-white leading-tight">
              {{ currentStepData.title }}
            </h3>
            <p class="text-slate-300 text-base font-medium leading-relaxed">
              {{ currentStepData.description }}
            </p>
          </div>

          <BookTitlePagesInput
            :title="bookTitle"
            :pages="bookTotalPages"
            :pages-error="pagesError"
            @update:title="bookTitle = $event"
            @update:pages="bookTotalPages = $event"
          />
        </div>

        <!-- Footer: Botones de Acción -->
        <div class="pt-2 flex items-center gap-3 flex-none">
          <AppButton
            v-if="currentStep < steps.length - 1"
            @click="nextStep"
            :color="currentStepData.type === 'book-config' && bookTitle.trim() === '' ? 'blue' : 'green'"
            block
            :text="currentStepData.type === 'book-config' && bookTitle.trim() === '' ? 'Registrar más tarde' : 'Siguiente'"
            :icon="ChevronRight"
            icon-position="end"
          />

          <AppButton
            v-else
            @click="finishTour"
            color="green"
            haptic="heavy"
            block
            :loading="savingBook"
            loading-text="Guardando..."
            :disabled="savingBook"
            text="¡Empezar a leer!"
            :icon="Rocket"
            icon-position="end"
          />
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import AppButton from './AppButton.vue';
import IconButton from './IconButton.vue';
import BookTitlePagesInput from './BookTitlePagesInput.vue';
import {
  ChevronRight,
  ChevronLeft,
  Rocket
} from '@lucide/vue';
import confetti from 'canvas-confetti';
import { StorageService } from '@/services/storage';
import { ToastService } from '@/services/toast';
import { ApiService } from '@/services/api';
import { NotificationService } from '@/services/notifications';
import { keyboardHeight } from '@/utils/keyboard';
import { useCurrentUser } from '@/composables/useCurrentUser';
import { isBibleTitle,  detectTrackingMode,  MAX_BOOK_TOTAL_PAGES,  MIN_BOOK_TOTAL_PAGES,  DEFAULT_BOOK_TOTAL_PAGES } from '@/utils/bookTracking';
import { TOUR_SEEN_KEY } from '@/constants';

import tourStep1 from '@/assets/tour/tour-step-1.png';
import tourStep2 from '@/assets/tour/tour-step-2.png';
import tourStep3 from '@/assets/tour/tour-step-3.png';
import tourStep4 from '@/assets/tour/tour-step-4.png';

const router = useRouter();
const { user } = useCurrentUser();
const isOpen = ref(false);
const currentStep = ref(0);

const steps = [
  {
    title: 'Crea tu racha',
    description: 'Lee todos los días para hacer crecer tu racha. Si un día no lees, tu racha se congelará.',
    ambientColor: 'bg-brand-flame',
    image: tourStep1
  },
  {
    type: 'reminder-time',
    title: 'No olvides leer',
    description: 'Te enviaremos un recordatorio diario para ayudarte a mantener tu hábito y tu racha.',
    ambientColor: 'bg-brand-purple',
    image: tourStep3
  },
  {
    type: 'book-config',
    title: '¿Qué libro estás leyendo?',
    description: 'Elige un libro para registrar tu avance diario. Estos son los más populares entre los usuarios.',
    ambientColor: 'bg-brand-green',
    image: tourStep4
  },
  {
    title: 'Lee con tus amigos',
    description: 'Invita a tus amigos, compartan su progreso y comparen sus rachas en el ranking.',
    ambientColor: 'bg-brand-blue',
    image: tourStep2
  },
];
const currentStepData = computed(() => steps[currentStep.value]);

// Paso "reminder-time": hora elegida para el recordatorio diario de lectura.
const reminderTime = ref('20:00');

// Paso 5 (book-config): registro opcional del libro activo. Los presets de
// titulo/paginas viven en BookTitlePagesInput.
const bookTitle = ref('');
const bookTotalPages = ref('');
const savingBook = ref(false);

const isBookConfigBible = computed(() => isBibleTitle(bookTitle.value));

// Paginas vacias no bloquean: se usa DEFAULT_BOOK_TOTAL_PAGES (ver
// resolvedBookTotalPages), igual que el placeholder ya sugiere. Solo si el
// usuario escribio un numero fuera de rango se considera invalido.
const isBookConfigComplete = () => {
  if (bookTitle.value.trim() === '') return false;
  if (isBookConfigBible.value) return true;
  if (bookTotalPages.value === '') return true;
  const pages = Number(bookTotalPages.value);
  return pages >= MIN_BOOK_TOTAL_PAGES && pages <= MAX_BOOK_TOTAL_PAGES;
};

// Mensaje de error (o null) para el numero de paginas. Se establece solo al
// intentar finalizar el tour (ver finishTour), no mientras se escribe: si no,
// el error aparece apenas se toca el titulo, antes de que el usuario haya
// tenido chance de llenar las paginas. Se limpia al volver a editar cualquiera
// de los dos campos, para no dejar un error viejo pegado.
const pagesError = ref(null);
watch([bookTitle, bookTotalPages], () => {
  pagesError.value = null;
});

// Titulo puesto pero sin paginas validas: a diferencia de dejar todo vacio
// (avance silencioso, sin libro), aca si hay un error visible que bloquea —
// el usuario ya empezo a llenar el libro, no tiene sentido perderlo en silencio.
// true si bloqueo (dejo pagesError seteado), false si puede seguir.
const blockOnInvalidBookConfig = () => {
  const title = bookTitle.value.trim();
  if (title !== '' && !isBookConfigBible.value && !isBookConfigComplete()) {
    pagesError.value = `Ingresa un número entre ${MIN_BOOK_TOTAL_PAGES} y ${MAX_BOOK_TOTAL_PAGES}.`;
    return true;
  }
  return false;
};

const nextStep = async () => {
  if (currentStepData.value.type === 'book-config' && blockOnInvalidBookConfig()) return;

  if (currentStepData.value.type === 'reminder-time') {
    // Pide local y push juntos (ver activateNotifications) justo al elegir la
    // hora: es el momento con mas contexto para el prompt, en vez de al final
    // del tour despues de 2 pasos mas sin relacion. Repetir la llamada al ir y
    // volver a este paso no reabre el prompt del OS (ya respondido una vez),
    // asi que no hace falta guardar un flag para evitarlo.
    try {
      const localGranted = await NotificationService.activateNotifications(user.value?.id);
      if (!localGranted) {
        ToastService.info('No activaste las notificaciones. Podrás activarlas luego desde Ajustes.');
      }
    } catch (e) {
      console.warn('No se pudo activar notificaciones:', e.message || e);
    }
    try {
      await NotificationService.persistReminderTime(reminderTime.value);
    } catch (e) {
      console.warn('No se pudo guardar el horario de recordatorio:', e.message || e);
    }
  }

  if (currentStep.value < steps.length - 1) {
    currentStep.value++;
  }
};

const prevStep = () => {
  if (currentStep.value > 0) {
    currentStep.value--;
  }
};

const finishTour = async () => {
  const title = bookTitle.value.trim();
  const registeredBook = isBookConfigComplete();
  if (registeredBook) {
    const resolvedBookTotalPages = bookTotalPages.value === '' ? DEFAULT_BOOK_TOTAL_PAGES : Number(bookTotalPages.value);

    savingBook.value = true;
    try {
      await ApiService.createBook(title, detectTrackingMode(title), isBookConfigBible.value ? null : resolvedBookTotalPages);
    } catch (e) {
      // No bloquea el onboarding: el usuario puede registrar su libro despues desde su perfil.
      ToastService.error(e.message || 'No se pudo guardar tu libro, podrás agregarlo después.');
    } finally {
      savingBook.value = false;
    }
  }

  try {
    // Permiso y hora ya se pidieron/guardaron al salir del paso reminder-time
    // (ver nextStep); aca solo falta programar la rafaga con el libro ya conocido.
    await NotificationService.schedule7DayBurst(
      reminderTime.value,
      0,
      false,
      0,
      registeredBook ? title : null,
    );
  } catch (e) {
    // No bloquea el onboarding: el usuario puede ajustar el horario despues desde Ajustes.
    console.warn('No se pudo guardar el horario de recordatorio:', e.message || e);
  }

  try {
    confetti({
      particleCount: 90,
      spread: 60,
      origin: { y: 0.6 },
      colors: ['#58CC02', '#FF9600', '#1CB0F6', '#CE82FF']
    });
  } catch (e) {}

  isOpen.value = false;
  currentStep.value = 0;
  await StorageService.set(TOUR_SEEN_KEY, true);
  router.push({ name: 'dashboard' });
};

// Precarga las imagenes de todos los steps al abrir, no solo la del primero:
// sin esto, cada @click en "Siguiente" dispara la descarga de la imagen del
// step nuevo recien en ese momento, y se nota el salto/carga en el <img>.
const preloadStepImages = () => {
  steps.forEach((step) => {
    const img = new Image();
    img.src = step.image;
  });
};

const open = () => {
  currentStep.value = 0;
  isOpen.value = true;
  preloadStepImages();
};

// Sin flag propio de "onboarding completado" en el servidor: una cuenta que ya
// tiene actividad real (leyo algun dia, tiene racha o paginas registradas) no
// es nueva, sin importar que el flag local se haya perdido por desinstalar y
// reinstalar la app — evita reabrir el tour a usuarios ya establecidos.
const hasRealActivity = (u) => u?.days_read || u?.streak_count || u?.pages_read;

const checkTourStatus = async () => {
  if (hasRealActivity(user.value)) {
    await StorageService.set(TOUR_SEEN_KEY, true);
    // App.vue ya corrio su watch y no activo nada (el flag todavia no existia en
    // ese momento) — se activa aca para no dejar a este usuario sin notificaciones
    // por la carrera entre ambos checks. Si el permiso local recien se otorga aca,
    // hay que reprogramar la rafaga: el intento original en App.vue ya la habia
    // encontrado sin otorgar y no queda ningun otro disparador que la reintente.
    try {
      const localGranted = await NotificationService.activateNotifications(user.value?.id);
      if (localGranted) {
        NotificationService.scheduleReminderForUser(user.value);
      }
    } catch (e) {
      console.warn('No se pudo inicializar notificaciones:', e.message);
    }
    return;
  }

  const hasSeenTour = await StorageService.get(TOUR_SEEN_KEY);
  if (!hasSeenTour) {
    setTimeout(() => {
      isOpen.value = true;
      preloadStepImages();
    }, 500);
  }
};

onMounted(() => {
  checkTourStatus();
});

defineExpose({
  open,
  checkTourStatus
});
</script>

<style scoped>
.tour-fade-enter-active,
.tour-fade-leave-active {
  transition: opacity 0.25s ease;
}

.tour-fade-enter-from,
.tour-fade-leave-to {
  opacity: 0;
}
</style>
