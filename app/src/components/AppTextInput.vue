<template>
  <div class="relative flex items-center">
    <component
      :is="icon"
      v-if="icon"
      class="w-5 h-5 absolute left-3.5 pointer-events-none stroke-[2.5]"
      :class="disabled ? 'text-slate-500' : 'text-slate-400'"
    />
    <span v-else-if="prefix" class="absolute left-3.5 pointer-events-none" :class="disabled ? 'text-slate-500' : 'text-slate-400'">{{ prefix }}</span>

    <input
      v-bind="$attrs"
      :type="type"
      :value="modelValue"
      @input="$emit('update:modelValue', $event.target.value)"
      :disabled="disabled"
      :placeholder="placeholder"
      :maxlength="maxlength"
      :min="min"
      class="w-full rounded-2xl py-3 text-base focus:outline-none transition-colors"
      :class="[
        (icon || prefix) ? 'pl-11' : 'px-4',
        $slots.suffix ? 'pr-28' : (icon || prefix) ? 'pr-4' : '',
        disabled
          ? 'bg-slate-900/60 border border-slate-800/80 text-slate-300 select-none cursor-not-allowed'
          : 'bg-slate-900 border border-slate-800 focus:border-brand-green text-white',
        inputClass
      ]"
    />

    <slot name="suffix" />
  </div>
</template>

<script setup>
defineOptions({ inheritAttrs: false });

defineProps({
  modelValue: { type: [String, Number], default: '' },
  type: { type: String, default: 'text' },
  placeholder: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
  maxlength: { type: [String, Number], default: undefined },
  min: { type: [String, Number], default: undefined },
  icon: { type: [Object, Function], default: null },
  prefix: { type: String, default: '' },
  inputClass: { type: String, default: '' }
});

defineEmits(['update:modelValue']);
</script>
