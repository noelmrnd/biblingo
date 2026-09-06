<template>
  <header class="flex-none z-30 bg-brand-dark/90 backdrop-blur-md border-b border-brand-border px-4 py-3 pt-safe flex items-center gap-3 shadow-md">
    <button
      type="button"
      @click="router.push(backTo)"
      class="w-8 h-8 -ml-1 flex items-center justify-center text-slate-300 hover:text-white rounded-full transition-colors cursor-pointer"
      aria-label="Volver"
    >
      <ArrowLeft class="w-6 h-6 stroke-[2.5]" />
    </button>
    <span class="font-extrabold text-xl tracking-tight text-white truncate">{{ title }}</span>
  </header>
</template>

<script setup>
import { useRouter } from 'vue-router';
import { ArrowLeft } from '@lucide/vue';

defineProps({
  title: { type: String, required: true },
  backTo: { type: [String, Object], required: true }
});

const router = useRouter();
</script>
