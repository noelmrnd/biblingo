import { ref } from 'vue';

export const toastState = ref({
  visible: false,
  message: '',
  type: 'success', // 'success' | 'error' | 'info'
  timeoutId: null
});

// Cola interna: si se pide mostrar un toast mientras otro esta visible, se
// encola en vez de pisarlo (antes cada llamada a show() reemplazaba el toast
// actual sin importar si alcanzo a leerse, lo que hacia perder avisos cuando
// dos disparaban casi juntos, ej. "se uso un protector" + medalla nueva).
const queue = [];

const showNext = () => {
  const next = queue.shift();
  if (!next) {
    toastState.value = { ...toastState.value, visible: false, timeoutId: null };
    return;
  }

  toastState.value = {
    visible: true,
    message: next.message,
    type: next.type,
    timeoutId: setTimeout(showNext, next.duration)
  };
};

export const ToastService = {
  show(message, type = 'success', duration = 3500) {
    queue.push({ message, type, duration });
    if (!toastState.value.visible) {
      showNext();
    }
  },

  success(message, duration = 3500) {
    this.show(message, 'success', duration);
  },

  error(message, duration = 4000) {
    this.show(message, 'error', duration);
  },

  info(message, duration = 3500) {
    this.show(message, 'info', duration);
  },

  hide() {
    if (toastState.value.timeoutId) {
      clearTimeout(toastState.value.timeoutId);
    }
    showNext();
  }
};
