import { ref } from 'vue';

export const toastState = ref({
  visible: false,
  message: '',
  type: 'success', // 'success' | 'error' | 'info'
  timeoutId: null
});

export const ToastService = {
  show(message, type = 'success', duration = 3500) {
    if (toastState.value.timeoutId) {
      clearTimeout(toastState.value.timeoutId);
    }

    toastState.value = {
      visible: true,
      message,
      type,
      timeoutId: setTimeout(() => {
        toastState.value.visible = false;
      }, duration)
    };
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
    toastState.value.visible = false;
  }
};
