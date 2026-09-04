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
            <span class="text-xs font-black tracking-wider uppercase text-slate-400">
              Paso {{ currentStep + 1 }} de {{ steps.length }}
            </span>
            <button 
              @click="skipTour"
              class="text-xs font-extrabold text-slate-400 hover:text-slate-200 transition-colors py-1 px-2.5 rounded-lg bg-slate-800/80 hover:bg-slate-700 active:scale-95"
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
          <!-- Ilustración / Elemento Visual Principal -->
          <div class="relative py-2 flex items-center justify-center min-h-[140px] w-full">
            
            <!-- Paso 1: Racha Hero -->
            <div v-if="currentStep === 0" class="flex flex-col items-center">
              <div class="relative">
                <div class="text-7xl animate-flame-pulse inline-block filter drop-shadow-[0_0_25px_rgba(255,150,0,0.85)]">
                  🔥
                </div>
                <div class="absolute -bottom-1 -right-2 bg-amber-400 text-slate-950 font-black text-sm px-2 py-0.5 rounded-full shadow-lg border-2 border-slate-900">
                  +1 día
                </div>
              </div>
              <div class="mt-3 inline-flex items-center gap-1.5 bg-amber-500/15 border border-amber-500/30 text-amber-300 px-3 py-1 rounded-full text-xs font-extrabold">
                <Sparkles class="w-3.5 h-3.5" />
                <span>Construye tu hábito</span>
              </div>
            </div>

            <!-- Paso 2: Botón de lectura y emociones -->
            <div v-else-if="currentStep === 1" class="w-full space-y-3">
              <div class="bg-brand-green/20 border-2 border-brand-green/60 p-3 rounded-2xl flex items-center justify-center gap-2.5 shadow-lg">
                <BookOpen class="w-5 h-5 text-brand-green stroke-[2.5]" />
                <span class="text-white font-extrabold text-sm">Marcar lectura de hoy</span>
              </div>
              <div class="flex items-center justify-center gap-2 bg-slate-900/90 p-2.5 rounded-2xl border border-slate-800">
                <span class="text-xl hover:scale-125 transition-transform">💡</span>
                <span class="text-xl hover:scale-125 transition-transform">❤️</span>
                <span class="text-xl hover:scale-125 transition-transform">🔥</span>
                <span class="text-xl hover:scale-125 transition-transform">🙏</span>
                <span class="text-xl hover:scale-125 transition-transform">🧠</span>
              </div>
            </div>

            <!-- Paso 3: Amigos, Ranking y Toques -->
            <div v-else-if="currentStep === 2" class="w-full space-y-2.5">
              <div class="bg-slate-900/90 border border-slate-800 p-2.5 rounded-2xl flex items-center justify-between text-left">
                <div class="flex items-center gap-2.5">
                  <span class="text-2xl">🥇</span>
                  <div>
                    <p class="text-xs font-extrabold text-white">Ranking de rachas</p>
                    <p class="text-[10px] text-slate-400">Compite con amigos</p>
                  </div>
                </div>
                <div class="bg-amber-500/20 text-amber-300 text-xs font-black px-2 py-1 rounded-lg border border-amber-500/30">
                  🔥 14
                </div>
              </div>
              <div class="bg-slate-900/90 border border-slate-800 p-2.5 rounded-2xl flex items-center justify-between text-left">
                <div class="flex items-center gap-2.5">
                  <span class="text-2xl">👥</span>
                  <div>
                    <p class="text-xs font-extrabold text-white">¿Tu amigo no ha leído?</p>
                    <p class="text-[10px] text-slate-400">Envíale un aviso amistoso</p>
                  </div>
                </div>
                <div class="bg-gradient-to-r from-amber-500 to-amber-600 text-slate-950 font-black text-xs px-2.5 py-1 rounded-lg flex items-center gap-1">
                  <BellRing class="w-3 h-3 stroke-[3]" />
                  <span>Toque</span>
                </div>
              </div>
            </div>

            <!-- Paso 4: Recordatorio Diario -->
            <div v-else-if="currentStep === 3" class="w-full space-y-3">
              <div class="w-16 h-16 mx-auto bg-gradient-to-tr from-amber-400 to-amber-600 rounded-3xl flex items-center justify-center shadow-xl shadow-amber-500/20 border-2 border-amber-300">
                <BellRing class="w-8 h-8 text-slate-950 stroke-[2.5] animate-bounce-short" />
              </div>
              <div class="bg-slate-900/90 border border-slate-800 px-4 py-2.5 rounded-2xl inline-flex items-center gap-2">
                <span class="text-xs text-slate-300 font-bold">Recordatorio:</span>
                <span class="text-amber-400 font-black font-mono text-sm">20:00 hrs</span>
                <span class="text-xs bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 px-2 py-0.5 rounded-md font-extrabold">Activo</span>
              </div>
            </div>

          </div>

          <!-- Textos: Título y Descripción -->
          <div class="space-y-2">
            <h3 class="text-2xl font-extrabold text-white leading-tight">
              {{ currentStepData.title }}
            </h3>
            <p class="text-slate-300 text-sm font-medium leading-relaxed">
              {{ currentStepData.description }}
            </p>
          </div>

          <!-- Tip / Consejo Destacado -->
          <div class="bg-slate-900/80 border border-slate-800 p-3 rounded-2xl w-full text-left flex items-start gap-2.5">
            <CheckCircle2 class="w-4 h-4 text-brand-green flex-none mt-0.5 stroke-[2.5]" />
            <p class="text-xs text-slate-300 font-semibold">
              {{ currentStepData.tip }}
            </p>
          </div>
        </div>

        <!-- Footer: Botones de Acción -->
        <div class="pt-2 flex items-center gap-3 flex-none">
          <button 
            v-if="currentStep > 0"
            @click="prevStep"
            class="btn-3d-dark py-3.5 px-4 text-sm font-bold flex items-center justify-center gap-1"
          >
            <ChevronLeft class="w-4 h-4 stroke-[3]" />
            <span>Atrás</span>
          </button>

          <button 
            v-if="currentStep < steps.length - 1"
            @click="nextStep"
            class="btn-3d-green flex-1 py-3.5 text-base font-black flex items-center justify-center gap-2"
          >
            <span>Siguiente</span>
            <ChevronRight class="w-4 h-4 stroke-[3]" />
          </button>

          <button 
            v-else
            @click="finishTour"
            class="btn-3d-green flex-1 py-3.5 text-base font-black flex items-center justify-center gap-2 shadow-emerald-500/30"
          >
            <span>¡Empezar a leer!</span>
            <span>🚀</span>
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, computed } from 'vue';
import { 
  Sparkles, 
  BookOpen, 
  BellRing, 
  CheckCircle2, 
  ChevronRight, 
  ChevronLeft 
} from '@lucide/vue';
import confetti from 'canvas-confetti';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['close', 'complete']);

const currentStep = ref(0);

const steps = [
  {
    title: 'Tu racha diaria',
    description: 'La constancia es la clave. Cada día que lees tus libros favoritos, tu racha aumenta. ¡Mantén encendida tu llama!',
    tip: 'Si dejas pasar un día sin leer, la racha se congelará.',
    ambientColor: 'bg-brand-flame'
  },
  {
    title: 'Marca tu lectura',
    description: 'Dedica unos minutos al día a avanzar en tus libros. Al terminar, presiona el botón verde para registrar tu progreso y elegir tu reacción.',
    tip: 'Puedes registrar cómo te hizo sentir lo que leíste hoy (inspirador, reflexivo, apasionante).',
    ambientColor: 'bg-brand-green'
  },
  {
    title: 'Compite y motívate',
    description: 'Invita a tus amigos con tu código o QR. Podrás ver el ranking de lectura y darles un "Toque" si se les hace tarde para leer.',
    tip: 'Compartir el hábito de la lectura en comunidad lo hace mucho más fácil y divertido.',
    ambientColor: 'bg-brand-blue'
  },
  {
    title: 'Protege tu hábito',
    description: 'En tu perfil puedes configurar la hora exacta de tu recordatorio diario para que no se te pase tu momento de lectura.',
    tip: 'Te enviaremos una notificación discreta para proteger tu racha.',
    ambientColor: 'bg-brand-purple'
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

const skipTour = () => {
  currentStep.value = 0;
  emit('close');
};

const finishTour = () => {
  // Disparar confeti de celebración
  try {
    confetti({
      particleCount: 90,
      spread: 60,
      origin: { y: 0.6 },
      colors: ['#58CC02', '#FF9600', '#1CB0F6', '#CE82FF']
    });
  } catch (e) {
    // Ignorar si canvas-confetti falla
  }

  currentStep.value = 0;
  emit('complete');
};
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
