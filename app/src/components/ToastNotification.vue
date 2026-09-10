<template>
  <Teleport to="body">
  <Transition
    enter-active-class="transition transform duration-300 ease-out"
    enter-from-class="-translate-y-12 opacity-0 scale-95"
    enter-to-class="translate-y-0 opacity-100 scale-100"
    leave-active-class="transition transform duration-200 ease-in"
    leave-from-class="translate-y-0 opacity-100 scale-100"
    leave-to-class="-translate-y-12 opacity-0 scale-95"
  >
    <div 
      v-if="toastState.visible"
      class="fixed top-safe left-0 right-0 z-[60] px-4 pointer-events-none flex justify-center"
    >
      <div 
        :class="[
          toastState.type === 'error' 
            ? 'bg-rose-950/95 border-rose-500/50 text-rose-200 shadow-rose-950/50' 
            : toastState.type === 'info'
            ? 'bg-slate-900/95 border-sky-500/50 text-sky-200 shadow-sky-950/50'
            : 'bg-emerald-950/95 border-emerald-500/50 text-emerald-200 shadow-emerald-950/50'
        ]"
        class="pointer-events-auto max-w-sm w-full border-1 rounded-2xl p-4 shadow-2xl backdrop-blur-xl flex items-center justify-between gap-3"
      >
        <div class="flex items-center gap-3">
          <AlertTriangle v-if="toastState.type === 'error'" class="w-6 h-6 stroke-[2.5] flex-shrink-0" />
          <Info v-else-if="toastState.type === 'info'" class="w-6 h-6 stroke-[2.5] flex-shrink-0" />
          <CheckCircle v-else class="w-6 h-6 stroke-[2.5] flex-shrink-0" />
          <p class="text-base font-extrabold leading-snug">
            {{ toastState.message }}
          </p>
        </div>

        <button
          @click="ToastService.hide()"
          class="text-slate-400 hover:text-white p-1 transition-colors cursor-pointer"
        >
          <X class="w-5 h-5 stroke-[2.5]" />
        </button>
      </div>
    </div>
  </Transition>
  </Teleport>
</template>

<script setup>
import { AlertTriangle, Info, CheckCircle, X } from '@lucide/vue';
import { toastState, ToastService } from '../services/toast';
</script>
