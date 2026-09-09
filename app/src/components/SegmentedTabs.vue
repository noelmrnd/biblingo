<template>
  <div
    class="grid gap-2 bg-slate-950/80 p-1.5 rounded-2xl border border-slate-800"
    :style="{ gridTemplateColumns: `repeat(${tabs.length}, minmax(0, 1fr))` }"
  >
    <button
      v-for="tab in tabs"
      :key="tab.id"
      type="button"
      @click="select(tab.id)"
      :class="modelValue === tab.id
        ? activeClass(tab.color)
        : 'text-slate-400 hover:text-slate-200 border-transparent font-extrabold'"
      class="py-2.5 px-3 rounded-lg text-base transition-all cursor-pointer select-none active:scale-95"
    >
      {{ tab.label }}
    </button>
  </div>
</template>

<script setup>
const props = defineProps({
  // [{ id, label, color? }] — color: 'green' | 'blue' | 'orange' | 'rose' (default 'green')
  tabs: { type: Array, required: true },
  modelValue: { type: String, default: null }
});

const emit = defineEmits(['update:modelValue']);

const activeClass = (color) => {
  switch (color) {
    case 'blue':
      return 'bg-brand-card text-brand-blue shadow-md font-black';
    case 'orange':
      return 'bg-brand-card text-brand-flame shadow-md font-black';
    case 'rose':
      return 'bg-brand-card text-rose-400 shadow-md font-black';
    case 'green':
    default:
      return 'bg-brand-card text-brand-green shadow-md font-black';
  }
};

const select = (id) => {
  if (id === props.modelValue) return;
  emit('update:modelValue', id);
};
</script>
