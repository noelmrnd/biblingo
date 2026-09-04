import { ref } from 'vue';
import { registerPlugin, Capacitor } from '@capacitor/core';

export const Keyboard = registerPlugin('Keyboard');

export const keyboardHeight = ref(0);

/**
 * Configura el ajuste de teclado en la aplicación:
 * - Monitorea la altura del teclado para permitir scroll dinámico sobre los controles.
 * - Desplaza suavemente el campo enfocado a la vista.
 * - Cierra el teclado al tocar fuera de los campos de texto.
 */
export function setupKeyboardDismiss() {
  if (Capacitor.isNativePlatform()) {
    try {
      Keyboard.addListener('keyboardWillShow', (info) => {
        keyboardHeight.value = info?.keyboardHeight || 0;
      });

      Keyboard.addListener('keyboardWillHide', () => {
        keyboardHeight.value = 0;
      });
    } catch (err) {
      console.warn('Keyboard listeners not available:', err);
    }
  }

  // Auto-desplazar suavemente el input al centro de la vista visible al enfocarlo
  window.addEventListener('focusin', (e) => {
    if (e.target && (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA')) {
      setTimeout(() => {
        e.target.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }, 300);
    }
  });

  const dismissKeyboard = (e) => {
    const activeEl = document.activeElement;
    if (!activeEl) return;

    const isInputField =
      activeEl.tagName === 'INPUT' ||
      activeEl.tagName === 'TEXTAREA' ||
      activeEl.isContentEditable;

    if (!isInputField) return;

    // Si el toque/clic fue sobre el mismo input o sobre otro campo editable, permitir la interacción
    if (e && e.target && (e.target === activeEl || e.target.closest('input, textarea, [contenteditable="true"]'))) {
      return;
    }

    // Desenfocar elemento activo y ocultar teclado nativo
    activeEl.blur();
    Keyboard.hide().catch(() => {});
  };

  // Rastrear si el usuario está realizando un gesto de desplazamiento (scroll) o un toque fijo (tap)
  let touchStartX = 0;
  let touchStartY = 0;
  let isScrolling = false;

  window.addEventListener('touchstart', (e) => {
    if (e.touches && e.touches.length > 0) {
      touchStartX = e.touches[0].clientX;
      touchStartY = e.touches[0].clientY;
    }
    isScrolling = false;
  }, { passive: true });

  window.addEventListener('touchmove', (e) => {
    if (e.touches && e.touches.length > 0) {
      const deltaX = Math.abs(e.touches[0].clientX - touchStartX);
      const deltaY = Math.abs(e.touches[0].clientY - touchStartY);
      // Si el dedo se mueve más de 8px, es un scroll y no un tap
      if (deltaX > 8 || deltaY > 8) {
        isScrolling = true;
      }
    }
  }, { passive: true });

  window.addEventListener('touchend', (e) => {
    // Si el usuario estaba scrolleando para ver los controles, NO cerrar el teclado
    if (isScrolling) {
      return;
    }
    // Solo si fue un tap fijo fuera del input, cerrar el teclado
    dismissKeyboard(e);
  }, { passive: true });

  // Soporte para clics con mouse en escritorio / navegador web
  window.addEventListener('click', (e) => {
    dismissKeyboard(e);
  });
}
