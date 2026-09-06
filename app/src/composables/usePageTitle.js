import { ref } from 'vue';

// Singleton: el SubHeader global (montado en App.vue) lee este titulo, y cada vista
// con header propio (Ajustes, perfil de amigo) lo actualiza al montar/cambiar de dato.
const title = ref('');

export function usePageTitle() {
  const setTitle = (value) => {
    title.value = value;
  };

  return { title, setTitle };
}
