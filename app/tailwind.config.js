/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        brand: {
          // Accion principal / lectura confirmada (boton de leer, tab Amigos, dia leido en calendario)
          green: '#4EC313',
          'green-dark': '#337F0C',

          // Identidad social / perfil (tab Perfil, seguidores-seguidos, estado "te sigue")
          blue: '#1D6CED',

          // Racha activa (icono de fuego, contador de racha, badge de racha en ranking)
          // flame: '#FF640A',
          flame: '#FF740A',
          'flame-dark': '#B34607',

          // Protector de racha / racha congelada. Tres tonos porque el mismo concepto
          // aparece en dos contextos con requisitos de contraste distintos:
          freeze: '#38bdf8', // text-sky-400

          // freeze: '#0EA5E9',        // fondo solido (celda "congelada" del calendario) — necesita ser oscuro para que el texto blanco encima contraste
          'freeze-light': '#7DD3FC', // texto/icono sobre fondo oscuro (badges, StatCell, StreakHero) — tono claro para legibilidad
          'freeze-dark': '#0284C7',  // borde de la celda solida — un tono mas oscuro que el fondo para dar profundidad

          // Cuarto acento decorativo, usado solo en el fondo ambiental de un paso
          // del onboarding (no tiene significado fijo en el resto de la app;
          // medallas/racha maxima usan purple-400 de Tailwind, no este token)
          purple: '#B086F9',

          // Reacciones de lectura (icono Corazon en reglas + resumen "Reacciones" del perfil)
          // reaction: '#FB7185',
          reaction: '#F43F5E',

          // Toques / recordatorios a amigos (boton "Dar un toque", icono BellRing en reglas)
          // nudge: '#FF640A',
          // 'nudge-dark': '#B34607',
          nudge: '#F59E0B',
          'nudge-dark': '#FF640A',

          // Dias leidos / constancia total (StatCell "Días leídos" en perfil propio y de amigos).
          // Mismo hex que brand-green hoy, pero token separado para poder cambiarlo
          // sin afectar el color de accion principal (boton de leer, etc).
          days: '#4EC313',

          dark: '#131927',
          card: '#1F293D',
          border: '#2B384E'
        }
      },

      fontFamily: {
        sans: ['Outfit', 'Inter', 'sans-serif']
      },
      animation: {
        'flame-pulse': 'flamePulse 1.5s infinite ease-in-out',
        'bounce-short': 'bounceShort 0.5s ease-in-out',
        'glow': 'glow 2s infinite alternate'
      },
      keyframes: {
        flamePulse: {
          '0%, 100%': { transform: 'scale(1)', filter: 'drop-shadow(0 0 10px rgba(255, 150, 0, 0.6))' },
          '50%': { transform: 'scale(1.12)', filter: 'drop-shadow(0 0 22px rgba(255, 150, 0, 0.9))' }
        },
        bounceShort: {
          '0%, 100%': { transform: 'translateY(0)' },
          '50%': { transform: 'translateY(-8px)' }
        },
        glow: {
          '0%': { boxShadow: '0 0 15px rgba(88, 204, 2, 0.3)' },
          '100%': { boxShadow: '0 0 30px rgba(88, 204, 2, 0.7)' }
        }
      }
    },
  },
  plugins: [],
}
