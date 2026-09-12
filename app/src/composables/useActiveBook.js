import { ref } from 'vue';

// Singleton a nivel de modulo (mismo patron que useCurrentUser): BookSettingsCard
// escribe el libro activo tras cargarlo/crearlo/quitarlo y lo lee en su propia
// seccion de privacidad para deshabilitar los toggles sin libro activo.
const activeBook = ref(null);

export function useActiveBook() {
  const setActiveBook = (book) => {
    activeBook.value = book;
  };

  return { activeBook, setActiveBook };
}
