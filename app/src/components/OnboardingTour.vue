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
        <!-- Header: Barra de Progreso y Botón Saltar -->
        <div class="space-y-3 flex-none">
          <div class="flex items-center justify-between">
            <span class="text-base font-bold tracking-wider uppercase text-slate-400">
              Paso {{ currentStep + 1 }} de {{ steps.length }}
            </span>
            <button 
              @click="skipTour"
              class="text-base font-medium text-slate-400 hover:text-slate-200 transition-colors py-1 px-2.5 rounded-lg bg-slate-800/80 hover:bg-slate-700 active:scale-95 cursor-pointer"
            >
              Saltar
            </button>
          </div>

          <!-- Indicador de Progreso Segmentado -->
          <div class="grid grid-cols-5 gap-1.5 h-1.5 w-full">
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
        <div v-if="currentStepData.type !== 'book-config'" class="py-6 flex flex-col items-center text-center space-y-4 flex-1">
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

            <div v-if="!isBookConfigBible">
              <label class="text-sm font-semibold text-slate-400">Número de páginas</label>
              <input
                v-model="bookTotalPages"
                type="number"
                min="1"
                placeholder="Ej. 320"
                class="mt-1 w-full rounded-xl bg-slate-950/60 border border-slate-800 px-4 py-3 text-white placeholder:text-slate-500 focus:outline-none focus:border-brand-green"
              />
            </div>
            <p v-else class="text-sm text-slate-400">
              La Biblia se registra por capítulos, no por páginas. ¡Podrás marcarlos en cualquier orden!
            </p>
          </div>

          <button
            type="button"
            @click="skipBookConfig"
            class="text-sm font-medium text-slate-400 hover:text-slate-200 transition-colors cursor-pointer"
          >
            Prefiero decidirlo después
          </button>
        </div>

        <!-- Footer: Botones de Acción -->
        <div class="pt-2 flex items-center gap-3 flex-none">
          <AppButton
            v-if="currentStep > 0"
            @click="prevStep"
            color="card"
            :icon="ChevronLeft"
          />

          <AppButton
            v-if="currentStep < steps.length - 1"
            @click="nextStep"
            color="green"
            block
            text="Siguiente"
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
            :disabled="savingBook || !isBookConfigValid"
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
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import AppButton from './AppButton.vue';
import {
  ChevronRight,
  ChevronLeft,
  Rocket
} from '@lucide/vue';
import confetti from 'canvas-confetti';
import { StorageService } from '@/services/storage';
import { ToastService } from '@/services/toast';
import { HapticsService } from '@/services/haptics';
import { ApiService } from '@/services/api';
import { isBibleTitle, detectTrackingMode } from '@/utils/bookTracking';

import tourStep1 from '@/assets/tour/tour-step-1.png';
import tourStep2 from '@/assets/tour/tour-step-2.png';
import tourStep3 from '@/assets/tour/tour-step-3.png';
import tourStep4 from '@/assets/tour/tour-step-4.png';

const router = useRouter();
const TOUR_SEEN_KEY = 'has_seen_onboarding_tour';
const isOpen = ref(false);
const currentStep = ref(0);

const steps = [
  {
    title: 'Tu racha diaria',
    description: 'Cada día que lees tus libros favoritos aumentas tu racha. Si dejas pasar un día sin leer, la racha se congelará. ¡Mantén encedida tu llama!',
    ambientColor: 'bg-brand-flame',
    image: tourStep1
  },
  {
    title: 'Lectura entre amigos',
    description: 'Invita a tus amigos con tu código o QR para leer juntos. Podrás competir en el ranking amistoso y darles un toque si se les hace tarde.',
    ambientColor: 'bg-brand-blue',
    image: tourStep2
  },
  {
    title: 'Protege tu hábito',
    description: 'Configura la hora ideal para tu recordatorio diario. Te enviaremos una notificación para proteger tu racha y no olvidar tu lectura diaria.',
    ambientColor: 'bg-brand-purple',
    image: tourStep3
  },
  {
    title: 'Un momento para ti',
    description: 'Dedica unos minutos al día a avanzar en tus libros. Al terminar, comparte cómo te hizo sentir lo que leíste.',
    ambientColor: 'bg-brand-green',
    image: tourStep4
  },
  {
    type: 'book-config',
    title: '¿Qué estás leyendo?',
    description: 'Regístralo y te iremos preguntando en qué página vas cada vez que leas. Puedes cambiarlo cuando quieras.',
    ambientColor: 'bg-brand-blue',
  },
];

const currentStepData = computed(() => steps[currentStep.value]);

// Paso 5 (book-config): registro opcional del libro activo.
const bookTitle = ref('');
const bookTotalPages = ref('');
const savingBook = ref(false);
const skippedBookConfig = ref(false);

const isBookConfigBible = computed(() => isBibleTitle(bookTitle.value));

const isBookConfigValid = computed(() => {
  if (skippedBookConfig.value || bookTitle.value.trim() === '') return true;
  if (isBookConfigBible.value) return true;
  return Number(bookTotalPages.value) > 0;
});

const skipBookConfig = () => {
  HapticsService.light();
  skippedBookConfig.value = true;
  bookTitle.value = '';
  bookTotalPages.value = '';
};

const nextStep = () => {
  if (currentStep.value < steps.length - 1) {
    currentStep.value++;
  }
};

const prevStep = () => {
  if (currentStep.value > 0) {
    currentStep.value--;
  }
};

const skipTour = async () => {
  HapticsService.light();
  isOpen.value = false;
  currentStep.value = 0;
  await StorageService.set(TOUR_SEEN_KEY, true);
};

const finishTour = async () => {
  if (!isBookConfigValid.value) return;

  const title = bookTitle.value.trim();
  if (title !== '' && !skippedBookConfig.value) {
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

const checkTourStatus = async () => {
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
