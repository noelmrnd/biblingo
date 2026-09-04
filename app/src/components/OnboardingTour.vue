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
        class="relative w-full max-w-sm bg-brand-card border-2 border-brand-border rounded-3xl p-6 shadow-2xl flex flex-col justify-between max-h-[90vh] overflow-y-auto no-scrollbar"
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
          <div class="grid grid-cols-4 gap-1.5 h-1.5 w-full">
            <div 
              v-for="(_, index) in steps" 
              :key="index"
              :class="[
                index <= currentStep ? 'bg-brand-green shadow-sm shadow-emerald-500/50' : 'bg-slate-800'
              ]"
              class="h-full rounded-full transition-all duration-300"
            ></div>
          </div>
        </div>

        <!-- Contenido Central Dinámico del Paso -->
        <div class="py-6 flex flex-col items-center text-center space-y-4 flex-1">
          <!-- Ilustración del paso desde /tour -->
          <div class="relative py-2 flex items-center justify-center min-h-[160px] w-full">
            <img 
              :src="currentStepData.image" 
              :alt="currentStepData.title"
              class="w-40 h-40 object-contain drop-shadow-xl select-none pointer-events-none transition-all duration-300"
            />
          </div>

          <!-- Textos: Título y Descripción -->
          <div class="space-y-2.5">
            <h3 class="text-2xl font-extrabold text-white leading-tight">
              {{ currentStepData.title }}
            </h3>
            <p class="text-slate-300 text-base font-medium leading-relaxed">
              {{ currentStepData.description }}
            </p>
          </div>
        </div>

        <!-- Footer: Botones de Acción -->
        <div class="pt-2 flex items-center gap-3 flex-none">
          <AppButton 
            v-if="currentStep > 0"
            @click="prevStep"
            color="dark"
          >
            <ChevronLeft class="w-4 h-4 stroke-[3]" />
          </AppButton>

          <AppButton 
            v-if="currentStep < steps.length - 1"
            @click="nextStep"
            color="green"
            block
          >
            <span>Siguiente</span>
            <ChevronRight class="w-4 h-4 stroke-[3]" />
          </AppButton>

          <AppButton 
            v-else
            @click="finishTour"
            color="green"
            block
          >
            <span>¡Empezar a leer!</span>
            <span>🚀</span>
          </AppButton>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import AppButton from './AppButton.vue';
import { 
  ChevronRight, 
  ChevronLeft 
} from '@lucide/vue';
import confetti from 'canvas-confetti';
import { StorageService } from '../services/storage';
import { ToastService } from '../services/toast';

const TOUR_SEEN_KEY = 'has_seen_onboarding_tour';
const isOpen = ref(false);
const currentStep = ref(0);

const steps = [
  {
    title: 'Tu racha diaria',
    description: 'Cada día que lees tus libros favoritos aumentas tu racha. Si dejas pasar un día sin leer, la racha se congelará. ¡Mantén encendida tu llama!',
    ambientColor: 'bg-brand-flame',
    image: '/tour/tour-step-1.png'
  },
  {
    title: 'Marca tu lectura',
    description: 'Dedica unos minutos al día a avanzar en tus libros. Al terminar, presiona el botón verde para registrar tu progreso y compartir cómo te hizo sentir lo que leíste.',
    ambientColor: 'bg-brand-green',
    image: '/tour/tour-step-2.png'
  },
  {
    title: 'Compite y motívate',
    description: 'Invita a tus amigos con tu código o QR para compartir el hábito de la lectura. Podrás competir en el ranking y darles un toque si se les hace tarde.',
    ambientColor: 'bg-brand-blue',
    image: '/tour/tour-step-3.png'
  },
  {
    title: 'Protege tu hábito',
    description: 'Configura en tu perfil la hora ideal para tu recordatorio diario. Te enviaremos una notificación para proteger tu racha y no olvidar tu momento de lectura.',
    ambientColor: 'bg-brand-purple',
    image: '/tour/tour-step-4.png'
  }
];

const currentStepData = computed(() => steps[currentStep.value]);

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
  isOpen.value = false;
  currentStep.value = 0;
  await StorageService.set(TOUR_SEEN_KEY, true);
};

const finishTour = async () => {
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
};

const open = () => {
  currentStep.value = 0;
  isOpen.value = true;
};

const checkTourStatus = async () => {
  const hasSeenTour = await StorageService.get(TOUR_SEEN_KEY);
  if (!hasSeenTour) {
    setTimeout(() => {
      isOpen.value = true;
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
