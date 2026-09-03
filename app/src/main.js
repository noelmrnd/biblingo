import { createApp } from 'vue';
import { Capacitor } from '@capacitor/core'
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
}
