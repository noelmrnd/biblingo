<script setup>
</script>
<template>
  <div class="min-h-screen flex items-center justify-center px-6 py-10 text-center">
    <div class="max-w-sm flex flex-col items-center gap-8">
      <img src="/assets/logo-256.png" alt="Libringo" class="w-18 h-18 rounded-2xl" />
      <div class="space-y-3">
        <h1 class="text-2xl font-bold">Libringo se usa desde la app</h1>
        <p class="text-slate-400 text-base leading-relaxed">
          Descarga Libringo en tu móvil para construir tu racha de lectura y competir con tus amigos.
        </p>
      </div>
      <div class="flex flex-col gap-3 w-full">
        <a
          href="https://apps.apple.com/app/id6808078003"
          target="_blank"
          rel="noopener noreferrer"
          class="flex items-center gap-3 px-4 py-3 rounded-2xl bg-brand-card border border-brand-border text-slate-100"
          aria-label="Descargar en App Store"
        >
          <svg class="w-7 h-7 flex-shrink-0" viewBox="0 0 24 24" fill="currentColor">
            <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M15.97 6.37c.61-.75 1.04-1.8 0.92-2.87-.9.04-2.02.6-2.66 1.34-.57.65-1.07 1.71-.93 2.74 1.01.08 2.06-.5 2.67-1.21z"/>
          </svg>
          <div class="flex flex-col items-start">
            <span class="text-xs text-slate-400">Descárgalo en el</span>
            <span class="text-base font-bold">App Store</span>
          </div>
        </a>
        <a
          href="https://play.google.com/store/apps/details?id=com.libringo.app"
          target="_blank"
          rel="noopener noreferrer"
          class="flex items-center gap-3 px-4 py-3 rounded-2xl bg-brand-card border border-brand-border text-slate-100"
          aria-label="Disponible en Google Play"
        >
          <svg class="w-7 h-7 flex-shrink-0" viewBox="0 0 24 24">
            <path fill="#00C3FF" d="M3.6 1.9C3.2 2.3 3 2.9 3 3.7v16.6c0 .8.2 1.4.6 1.8l.1.1 9.4-9.4v-.2L3.7 1.8l-.1.1z"/>
            <path fill="#FFC800" d="M16.2 15.7l-3.1-3.1v-.2l3.1-3.1.1.1 3.7 2.1c1 .6 1 1.5 0 2.1l-3.7 2.1-.1.1z"/>
            <path fill="#FF3A44" d="M13.1 12.6l-9.5 9.5c.5.5 1.3.6 2.2.1l10.4-6-3.1-3.6z"/>
            <path fill="#00E676" d="M13.1 12.4l3.1-3.5-10.4-6c-.9-.5-1.7-.4-2.2.1l9.5 9.4z"/>
          </svg>
          <div class="flex flex-col items-start">
            <span class="text-xs text-slate-400">Disponible en</span>
            <span class="text-base font-bold">Google Play</span>
          </div>
        </a>
      </div>
    </div>
  </div>
</template>
