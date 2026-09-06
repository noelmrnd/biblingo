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
          green: '#4EC313',
          'green-dark': '#337F0C',
          blue: '#1D6CED',
          flame: '#FF640A',
          'flame-dark': '#B34607',
          purple: '#B086F9',
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
