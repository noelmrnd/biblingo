import { createApp } from 'vue';
import { Capacitor } from '@capacitor/core'
import { SplashScreen } from '@capacitor/splash-screen'
import { StatusBar } from '@capacitor/status-bar'
import App from './App.vue';
import './style.css';

createApp(App).mount('#app');

const initialLoader = document.getElementById('initial-loader')
if (initialLoader) {
    initialLoader.classList.add('hide')
    initialLoader.addEventListener('transitionend', () => initialLoader.remove())
}

if (Capacitor.isNativePlatform()) {
  // Lets the WebView draw under the status bar so it matches the page background.
  StatusBar.setOverlaysWebView({overlay: true}).catch(() => {});

  // Wait for the first real frame to paint before hiding the splash,
  // so it never dismisses onto a blank white screen.
  requestAnimationFrame(() => {
    requestAnimationFrame(() => {
      SplashScreen.hide();
    });
  });
}
