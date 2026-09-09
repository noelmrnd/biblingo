import { ref } from 'vue';

export const badgeCelebrationState = ref({
  visible: false,
  badge: null
});

// Cola: si se ganan varias medallas a la vez (ej. backfill de un usuario que ya
// estaba por encima de varios umbrales), se muestran una por una en vez de
// pisarse — mismo patron que ToastService.
const queue = [];

// Duracion del fade de salida del modal (ver .celebration-fade-leave-active
// en BadgeCelebrationModal.vue) — hay que esperarla antes de mostrar la
// siguiente medalla, si no el v-if nunca pasa por false y Vue no dispara ni
// la transicion de salida ni la de entrada (la medalla cambia de golpe).
const LEAVE_DURATION = 250;

const showNext = () => {
  const next = queue.shift();
  if (!next) {
    badgeCelebrationState.value = { visible: false, badge: null };
    return;
  }

  badgeCelebrationState.value = { visible: true, badge: next };
};

export const BadgeCelebrationService = {
  celebrate(badge) {
    queue.push(badge);
    if (!badgeCelebrationState.value.visible) {
      showNext();
    }
  },

  dismiss() {
    const hasNext = queue.length > 0;
    badgeCelebrationState.value = { ...badgeCelebrationState.value, visible: false };

    if (hasNext) {
      setTimeout(showNext, LEAVE_DURATION);
    } else {
      badgeCelebrationState.value = { visible: false, badge: null };
    }
  }
};
