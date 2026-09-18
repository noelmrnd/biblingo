<template>
  <div class="w-full text-left">
    <div class="flex flex-wrap gap-1.5">
      <button
        v-for="preset in BOOK_PRESETS"
        :key="preset.title"
        type="button"
        @click="selectPreset(preset)"
        :class="selectedTitle === preset.title ? 'bg-brand-blue text-white border-brand-blue' : 'bg-slate-950/60 text-slate-300 border-slate-800 hover:border-slate-700'"
        class="px-3 py-1.5 rounded-full border text-sm font-medium cursor-pointer transition-colors"
      >
        {{ preset.title }}
      </button>
      <button
        type="button"
        @click="selectPreset(null)"
        :class="selectedTitle === null ? 'bg-brand-blue text-white border-brand-blue' : 'bg-slate-950/60 text-slate-300 border-slate-800 hover:border-slate-700'"
        class="px-3 py-1.5 rounded-full border text-sm font-medium cursor-pointer transition-colors"
      >
        Otro
      </button>
    </div>

    <div v-if="selectedTitle === null" class="flex flex-row gap-2 w-full mt-3">
      <div class="grow">
        <AppTextInput
          :model-value="title"
          @update:model-value="$emit('update:title', $event)"
          autocapitalize="sentences"
          placeholder="Nombre del libro"
          class="grow"
        />
      </div>
      <div v-if="title.trim() !== '' && !isBible" style="width: 100px">
        <AppTextInput
          style="width: 100px"
          :model-value="pages"
          @update:model-value="$emit('update:pages', $event)"
          type="number"
          :min="MIN_BOOK_TOTAL_PAGES"
          :max="MAX_BOOK_TOTAL_PAGES"
          placeholder="Páginas"
          :input-class="['no-spinner', pagesError ? '!border-rose-500/60' : '']"
        />
      </div>
    </div>

    <p v-if="pagesError" class="mt-1 text-sm text-rose-400 font-medium">{{ pagesError }}</p>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import AppTextInput from './AppTextInput.vue';
import { isBibleTitle, MAX_BOOK_TOTAL_PAGES, MIN_BOOK_TOTAL_PAGES, DEFAULT_BOOK_TOTAL_PAGES } from '@/utils/bookTracking';

// Presets de libros populares (titulo + paginas), para no tener que tipear.
// "Otro" (selectedPreset === null) muestra los inputs libres de titulo/paginas.
const BOOK_PRESETS = [
  { title: 'Hábitos atómicos', pages: 320 },
  { title: 'La Biblia', pages: 1189 },
  { title: 'Cien años de soledad', pages: 417 },
  { title: 'Orgullo y prejuicio', pages: 432 },
  { title: 'Romper el círculo', pages: 400 },
];

const props = defineProps({
  title: { type: String, required: true },
  pages: { type: [String, Number], required: true },
  pagesError: { type: String, default: null },
});
const emit = defineEmits(['update:title', 'update:pages']);

const isBible = computed(() => isBibleTitle(props.title));

// Titulo del preset activo: undefined = nada elegido todavia (default, sin
// chip marcado ni inputs visibles), null = "Otro" elegido explicitamente,
// string = preset elegido. Se guarda el titulo, no el objeto: ref() envuelve
// objetos asignados en un proxy reactivo, por lo que comparar por referencia
// contra el objeto crudo del v-for (BOOK_PRESETS no es reactivo) nunca da
// igual — comparar por titulo evita ese problema. Vive solo aca — si el padre
// necesita resetear el formulario (p.ej. tras guardar), debe remontar el
// componente (:key) en vez de mutar title/pages por fuera.
const selectedTitle = ref(BOOK_PRESETS.some((p) => p.title === props.title) ? props.title : undefined);

const selectPreset = (preset) => {
  const newTitle = preset ? preset.title : null;
  const deselecting = selectedTitle.value === newTitle;

  selectedTitle.value = deselecting ? undefined : newTitle;
  emit('update:title', preset && !deselecting ? preset.title : '');
  emit('update:pages', preset && !deselecting ? String(preset.pages) : '');
};

// Para que el padre pueda volver a "nada elegido" tras guardar (ver
// saveNewBook en BookSettingsCard), sin remontar el componente entero.
const reset = () => {
  selectedTitle.value = undefined;
};

defineExpose({ reset });
</script>
