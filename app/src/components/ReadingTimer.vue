<template>
  <!-- Temporizador de lectura de 10 minutos -->
  <!--
  <div class="card-duo bg-slate-900/90 border-slate-700 space-y-4 text-center">
    <div class="flex items-center justify-between text-left">
      <div>
        <h4 class="font-extrabold text-white text-sm">Cronómetro de Lectura</h4>
        <p class="text-slate-400 text-xs">Meta recomendada: 10 minutos</p>
      </div>
      <span class="text-2xl">⏱️</span>
    </div>

    <div class="text-4xl font-black font-mono tracking-wider text-amber-400 py-2">
      {{ formatTimer(timerSeconds) }}
    </div>

    <div class="flex gap-3">
      <button
        @click="toggleTimer"
        :class="isTimerRunning ? 'btn-3d-orange' : 'btn-3d-blue'"
        class="flex-1 py-2.5 text-sm"
      >
        {{ isTimerRunning ? 'Pausar' : 'Iniciar 10 Min' }}
      </button>
      <button
        @click="resetTimer"
        class="btn-3d-dark text-xs py-2.5 px-4"
      >
        Reiniciar
      </button>
    </div>
  </div>
  -->
</template>

<script setup>
// import { ref, onUnmounted } from 'vue';

// const timerSeconds = ref(600); // 10 mins
// const isTimerRunning = ref(false);
// let timerInterval = null;

// const formatTimer = (sec) => {
//   const m = Math.floor(sec / 60);
//   const s = sec % 60;
//   return `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
// };

// const toggleTimer = () => {
//   if (isTimerRunning.value) {
//     clearInterval(timerInterval);
//     isTimerRunning.value = false;
//   } else {
//     isTimerRunning.value = true;
//     timerInterval = setInterval(() => {
//       if (timerSeconds.value > 0) {
//         timerSeconds.value--;
//       } else {
//         clearInterval(timerInterval);
//         isTimerRunning.value = false;
//         alert('¡Felicidades! Completaste tus 10 minutos de lectura diaria. 🔥');
//         logReadingToday();
//       }
//     }, 1000);
//   }
// };

// const resetTimer = () => {
//   clearInterval(timerInterval);
//   isTimerRunning.value = false;
//   timerSeconds.value = 600;
// };

// onUnmounted(() => {
//   if (timerInterval) clearInterval(timerInterval);
// });
</script>
