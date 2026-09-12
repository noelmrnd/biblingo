<template>
  <Transition name="tour-fade">
    <div 
      v-if="isOpen" 
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/85 backdrop-blur-md overflow-hidden select-none"
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

          <div class="w-full text-left">
            <label class="text-sm font-semibold text-slate-400">Hora del recordatorio</label>
            <input
              v-model="reminderTime"
              type="time"
              class="mt-1 w-full rounded-xl bg-slate-950/60 border border-slate-800 px-4 py-3 text-white focus:outline-none focus:border-brand-green"
            />
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

          <div class="w-full space-y-3 text-left">
            <div>
              <label class="text-sm font-semibold text-slate-400">Nombre del libro</label>
              <input
                v-model="bookTitle"
                type="text"
                autocapitalize="words"
                placeholder="Ej. El Principito"
                class="mt-1 w-full rounded-xl bg-slate-950/60 border border-slate-800 px-4 py-3 text-white placeholder:text-slate-500 focus:outline-none focus:border-brand-green"
              />
            </div>

            <div v-if="bookTitle.trim() !== '' && !isBookConfigBible">
              <label class="text-sm font-semibold text-slate-400">Número de páginas</label>
              <input
                v-model="bookTotalPages"
                type="number"
                min="1"
                :max="MAX_BOOK_TOTAL_PAGES"
                placeholder="Ej. 120"
                class="mt-1 w-full rounded-xl bg-slate-950/60 border px-4 py-3 text-white placeholder:text-slate-500 focus:outline-none"
                :class="pagesError ? 'border-rose-500/60 focus:border-rose-500' : 'border-slate-800 focus:border-brand-green'"
              />
              <p v-if="pagesError" class="mt-1 text-sm text-rose-400 font-medium">{{ pagesError }}</p>
            </div>
            <p v-else-if="bookTitle.trim() !== ''" class="text-sm text-slate-400">
              Podrás llevar el registro de tu lectura por capítulos.
            </p>
          </div>
        </div>

        <!-- Footer: Botones de Acción -->
        <div class="pt-2 flex items-center gap-3 flex-none">
          <AppButton
            v-if="currentStep < steps.length - 1"
            @click="nextStep"
            color="green"
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
import { useCurrentUser } from '@/composables/useCurrentUser';
import { isBibleTitle, detectTrackingMode, MAX_BOOK_TOTAL_PAGES } from '@/utils/bookTracking';

import tourStep1 from '@/assets/tour/tour-step-1.png';
import tourStep2 from '@/assets/tour/tour-step-2.png';
import tourStep3 from '@/assets/tour/tour-step-3.png';
import tourStep4 from '@/assets/tour/tour-step-4.png';

const router = useRouter();
const { user } = useCurrentUser();
const TOUR_SEEN_KEY = 'has_seen_onboarding_tour';
const isOpen = ref(false);
const currentStep = ref(0);

const steps = [
  {
    title: 'Tu racha diaria',
    description: 'Cada día que lees aumentas tu racha. Si dejas pasar un día sin leer, tu racha se congelará.',
    ambientColor: 'bg-brand-flame',
    image: tourStep1
  },
  {
    type: 'reminder-time',
    title: 'Protege tu hábito',
    description: 'Elige la hora de tu recordatorio diario. Te avisaremos para que no olvides leer y mantengas tu racha.',
    ambientColor: 'bg-brand-purple',
    image: tourStep3
  },
  {
    type: 'book-config',
    title: '¿Qué estás leyendo?',
    description: 'Registra tu libro para llevar tu avance mientras lees. Puedes cambiarlo cuando quieras.',
    ambientColor: 'bg-brand-green',
    image: tourStep4
  },
  {
    title: 'Lee con tus amigos',
    description: 'Invita a tus amigos a leer. Compartan su progreso y compitan en el ranking de rachas.',
    ambientColor: 'bg-brand-blue',
    image: tourStep2
  },
];
const currentStepData = computed(() => steps[currentStep.value]);

// Paso "reminder-time": hora elegida para el recordatorio diario de lectura.
const reminderTime = ref('20:00');

// Paso 5 (book-config): registro opcional del libro activo.
const bookTitle = ref('');
const bookTotalPages = ref('');
const savingBook = ref(false);

const isBookConfigBible = computed(() => isBibleTitle(bookTitle.value));

// El paso de libro es siempre opcional: nunca bloquea el avance del tour, sin
// importar que tan a medias haya quedado el titulo/paginas. finishTour decide
// con isBookConfigComplete si hay suficiente para registrar el libro o no. Solo
// se evalua al finalizar (no es reactivo/computed porque nada mas lo necesita).
const isBookConfigComplete = () => {
  if (bookTitle.value.trim() === '') return false;
  if (isBookConfigBible.value) return true;
  const pages = Number(bookTotalPages.value);
  return pages > 0 && pages <= MAX_BOOK_TOTAL_PAGES;
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
    pagesError.value = `Ingresa un número de páginas entre 1 y ${MAX_BOOK_TOTAL_PAGES}.`;
    return true;
  }
  return false;
};

const nextStep = () => {
  if (currentStepData.value.type === 'book-config' && blockOnInvalidBookConfig()) return;
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
    savingBook.value = true;
    try {
      await ApiService.createBook(title, detectTrackingMode(title), isBookConfigBible.value ? null : Number(bookTotalPages.value));
    } catch (e) {
      // No bloquea el onboarding: el usuario puede registrar su libro despues desde su perfil.
      ToastService.error(e.message || 'No se pudo guardar tu libro, podrás agregarlo después.');
    } finally {
      savingBook.value = false;
    }
  }

  try {
    await NotificationService.requestPermissions();
    await NotificationService.persistReminderTime(reminderTime.value);
    // El libro recien registrado personaliza el recordatorio desde la primera vez,
    // no hay que esperar a la proxima vez que se guarden preferencias en Ajustes.
    await NotificationService.schedule7DayBurst(reminderTime.value, 0, false, 0, registeredBook ? title : null);
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
  ToastService.success('¡Tour completado! Que disfrutes tu lectura diaria. 📖✨');
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
