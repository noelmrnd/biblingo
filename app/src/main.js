import { createApp } from 'vue';
import { Capacitor } from '@capacitor/core';
import { SplashScreen } from '@capacitor/splash-screen';
import App from './App.vue';
import './assets/style.css';
import { setupKeyboardDismiss } from './utils/keyboard';

setupKeyboardDismiss();

createApp(App).mount('#app');

const initialLoader = document.getElementById('initial-loader')
if (initialLoader) {
    initialLoader.classList.add('hide')
    initialLoader.addEventListener('transitionend', () => initialLoader.remove())
}

if (Capacitor.isNativePlatform()) {
  // Wait for the first real frame to paint before hiding the splash,
  // so it never dismisses onto a blank white screen.
  requestAnimationFrame(() => {
    requestAnimationFrame(() => {
      SplashScreen.hide();
    });
  });
}
