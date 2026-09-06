<template>
  <AppModal
    :is-open="isOpen"
    :loading="sendAction.loading.value"
    title="Enviar sugerencia"
    description="Cuéntanos qué se te ocurre o qué no funcionó como esperabas."
    @close="close"
  >
    <template #icon>
      <div class="w-11 h-11 rounded-2xl bg-sky-500/10 border border-sky-500/30 flex items-center justify-center shrink-0">
        <MessageSquarePlus class="w-5 h-5 text-sky-400 stroke-[2.5]" />
      </div>
    </template>

    <div class="space-y-4">
      <div class="grid grid-cols-3 gap-2">
        <button
          v-for="option in FEEDBACK_TYPES"
          :key="option.id"
          type="button"
          @click="type = option.id"
          :class="type === option.id
            ? 'bg-brand-card text-brand-green border-brand-green/50 shadow-md font-black'
            : 'text-slate-400 hover:text-slate-200 border-slate-800 font-extrabold'"
          class="py-2.5 px-2 rounded-xl text-sm transition-all border cursor-pointer select-none active:scale-95"
        >
          {{ option.label }}
        </button>
      </div>

      <textarea
        v-model="message"
        rows="5"
        maxlength="2000"
        placeholder="Escribe aquí tu idea, sugerencia o el problema que encontraste..."
        class="w-full bg-slate-900 border border-slate-800 focus:border-brand-green text-white font-medium rounded-2xl p-4 text-base focus:outline-none transition-colors resize-none"
      ></textarea>
    </div>

    <template #footer>
      <AppButton
        color="green"
        block
        :disabled="sendAction.loading.value || message.trim().length < 5"
        @click="send"
      >
        {{ sendAction.loading.value ? 'Enviando...' : 'Enviar' }}
      </AppButton>
    </template>
  </AppModal>
</template>

<script setup>
import { ref } from 'vue';
import { MessageSquarePlus } from '@lucide/vue';
import AppModal from './AppModal.vue';
import AppButton from './AppButton.vue';
import { ApiService } from '../services/api';
import { useAsyncAction } from '../composables/useAsyncAction';

const FEEDBACK_TYPES = [
  { id: 'idea', label: 'Idea' },
  { id: 'bug', label: 'Problema' },
  { id: 'other', label: 'Otro' }
];

defineProps({
  isOpen: { type: Boolean, default: false }
});

const emit = defineEmits(['close']);

const type = ref('idea');
const message = ref('');
const sendAction = useAsyncAction();

const close = () => {
  if (sendAction.loading.value) return;
  type.value = 'idea';
  message.value = '';
  emit('close');
};

const send = async () => {
  const trimmed = message.value.trim();
  if (trimmed.length < 5 || sendAction.loading.value) return;

  const res = await sendAction.run(() => ApiService.submitFeedback(type.value, trimmed), {
    successMsg: (r) => r.message || '¡Gracias por tu comentario! 🙌',
    errorMsg: 'No se pudo enviar tu comentario.'
  });

  if (res) close();
};
</script>
