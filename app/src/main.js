import { createApp } from 'vue';
import { Capacitor } from '@capacitor/core';
import { SplashScreen } from '@capacitor/splash-screen';
import { StatusBar, Style } from '@capacitor/status-bar';
import App from './App.vue';
import { router } from './router';
import './assets/style.css';
import { setupKeyboardDismiss } from './utils/keyboard';

setupKeyboardDismiss();

createApp(App).use(router).mount('#app');

const initialLoader = document.getElementById('initial-loader')
if (initialLoader) {
    initialLoader.classList.add('hide')
    initialLoader.addEventListener('transitionend', () => initialLoader.remove())
}

if (Capacitor.isNativePlatform()) {
  // Se fuerza por JS porque en Android el estilo por config puede aplicarse
  // antes de que el decor view este listo y quedar con iconos oscuros.
  StatusBar.setStyle({ style: Style.Dark }).catch(() => {});

  // Wait for the first real frame to paint before hiding the splash,
  // so it never dismisses onto a blank white screen.
  requestAnimationFrame(() => {
    requestAnimationFrame(() => {
      SplashScreen.hide();
    });
  });
}
