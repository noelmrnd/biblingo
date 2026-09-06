<template>
  <AppModal
    :is-open="isOpen"
    title="Comparte tu perfil"
    description="Que te encuentren con tu QR o tu usuario."
    @close="$emit('close')"
  >
    <div class="text-center mt-4">
      <div class="bg-white p-3 rounded-3xl inline-block shadow-2xl border-4 border-brand-green">
        <img v-if="qrDataUrl" :src="qrDataUrl" alt="Código QR de perfil" class="w-44 h-44 mx-auto block" />
        <div v-else class="w-44 h-44 flex items-center justify-center text-slate-400 text-base font-semibold">
          Generando QR...
        </div>
      </div>

      <p class="text-xl font-black tracking-wide text-emerald-400 font-mono mt-2">@{{ username }}</p>

      <div class="flex gap-2.5 mt-8">
        <AppButton color="green" block @click="share">
          <Share2 class="w-5 h-5 stroke-[2.5]" />
          <span>Compartir</span>
        </AppButton>
        <AppButton color="dark" block @click="copyLink">
          <Link class="w-5 h-5 stroke-[2.5]" />
          <span>Copiar enlace</span>
        </AppButton>
      </div>
    </div>
  </AppModal>
</template>

<script setup>
import { ref, watch } from 'vue';
import { Share2, Link } from '@lucide/vue';
import QRCode from 'qrcode';
import AppModal from './AppModal.vue';
import AppButton from './AppButton.vue';
import { ShareService } from '../services/shareService';
import { ToastService } from '../services/toast';

const props = defineProps({
  isOpen: { type: Boolean, default: false },
  username: { type: String, required: true }
});

defineEmits(['close']);

const qrDataUrl = ref('');

const generateQrCode = async () => {
  if (!props.username) return;
  const inviteUrl = `https://app.biblingo.me/invite/${props.username}`;
  try {
    qrDataUrl.value = await QRCode.toDataURL(inviteUrl, {
      width: 300,
      margin: 2,
      color: { dark: '#0F172A', light: '#FFFFFF' }
    });
  } catch (err) {
    console.warn('Error al generar el código QR:', err);
  }
};

watch(() => props.isOpen, (open) => {
  if (open && !qrDataUrl.value) generateQrCode();
});

const share = async () => {
  const res = await ShareService.shareUsername(props.username);
  if (res.success && res.method === 'clipboard') {
    ToastService.success('¡Enlace copiado! 📋');
  }
};

const copyLink = async () => {
  const res = await ShareService.copyProfileLink(props.username);
  if (res.success) {
    ToastService.success('¡Enlace copiado! 📋');
  } else {
    ToastService.error('No se pudo copiar el enlace.');
  }
};
</script>
