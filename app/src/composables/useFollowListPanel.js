import { computed } from 'vue';

/**
 * El modal de seguidores/seguidos se sincroniza con ?panel= en la URL: abrirlo
 * empuja una entrada al historial, asi el boton atras del navegador lo cierra
 * solo, y al volver desde el perfil de un amigo (navegado desde la lista) se
 * reabre automaticamente.
 */
export function useFollowListPanel(route, router) {
  const isOpen = computed(() => !!route.query.panel);
  const initialTab = computed(() => route.query.panel === 'following' ? 'following' : 'followers');

  const open = (tab) => {
    router.push({ query: { ...route.query, panel: tab } });
  };

  // Cambiar entre Seguidores/Seguidos reemplaza la entrada actual en vez de
  // apilar una nueva, para que "atras" cierre el modal en un solo paso sin
  // importar cuantas veces se cambio de tab.
  const switchTab = (tab) => {
    router.replace({ query: { ...route.query, panel: tab } });
  };

  const close = () => {
    router.back();
  };

  const goToFriendProfile = (id) => {
    router.push({ name: 'friend-profile', params: { id } });
  };

  return { isOpen, initialTab, open, switchTab, close, goToFriendProfile };
}
