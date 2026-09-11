<template>
  <div v-if="currentBookTitle || totalPagesRead" class="card-duo space-y-2">
    <p v-if="currentBookTitle" class="text-base text-slate-300 font-medium">
      Leyendo: <span class="font-bold text-white">{{ currentBookTitle }}</span>
      <span v-if="readingProgressPercent !== null && readingProgressPercent !== undefined"> ({{ readingProgressPercent }}%)</span>
    </p>
    <p class="text-base text-slate-400 font-medium">
      <span class="font-bold text-white">{{ formatNumber(totalPagesRead) }}</span>
      {{ pluralize(totalPagesRead, 'página/capítulo leído', 'páginas/capítulos leídos') }} en total
    </p>
  </div>
</template>

<script setup>
import { formatNumber } from '@/utils/numberFormatter';
import { pluralize } from '@/utils/pluralize';

defineProps({
  currentBookTitle: { type: String, default: null },
  readingProgressPercent: { type: Number, default: null },
  totalPagesRead: { type: Number, default: 0 }
});
</script>
