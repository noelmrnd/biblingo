import { ref } from 'vue';

// Singleton a nivel de modulo (mismo patron que useCurrentUser): BookSettingsCard
// escribe el libro activo tras cargarlo/crearlo/quitarlo, PrivacySettingsCard solo
// lo lee para deshabilitar sus toggles — sin pasar por props/emit entre ambos.
const activeBook = ref(null);

export function useActiveBook() {
  const setActiveBook = (book) => {
    activeBook.value = book;
  };

  return { activeBook, setActiveBook };
}
