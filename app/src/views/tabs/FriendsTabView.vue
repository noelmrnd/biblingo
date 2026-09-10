<template>
  <AppPage app-header>
    <div>
      <ExpandableCard
        v-model="isInviteExpanded"
        title="Invitar amigos"
        description="Comparte tu perfil o agrega a tus amigos"
        icon-bg-class="bg-brand-green/10 border-brand-green/30"
        icon-color-class="text-brand-green"
        :icon="UserRoundPlus"
        card-class="bg-slate-900 bg-[radial-gradient(ellipse_at_top_right,_rgba(88,204,2,0.18),_transparent_65%)] border-indigo-500/30"
      >
        <div class="space-y-3">
          <AppButton color="green" block :icon="Share2" text="Compartir mi perfil" @click="isShareModalOpen = true" />

          <AppButton color="blue" block :icon="Search" text="Buscar amigos" @click="isAddFriendModalOpen = true" />
        </div>
      </ExpandableCard>

      <ShareProfileModal
        :is-open="isShareModalOpen"
        :username="user.username"
        @close="isShareModalOpen = false"
      />

      <AddFriendModal
        :is-open="isAddFriendModalOpen"
        @close="onAddFriendModalClose"
        @open-profile="onOpenProfileFromSearch"
      />
    </div>

    <StreakRanking ref="rankingRef" :user="user" @friends-loaded="onFriendsLoaded" />
  </AppPage>
</template>

<script setup>
import { ref, watch, onActivated, onDeactivated } from 'vue';
import { useRouter } from 'vue-router';
import { Search, Share2, UserRoundPlus } from '@lucide/vue';
import AppPage from '@/components/AppPage.vue';
import AppButton from '@/components/AppButton.vue';
import ExpandableCard from '@/components/ExpandableCard.vue';
import ShareProfileModal from '@/components/ShareProfileModal.vue';
import AddFriendModal from '@/components/AddFriendModal.vue';
import StreakRanking from '@/components/StreakRanking.vue';

defineProps({
  user: { type: Object, required: true }
});

// Con 0 amigos, la tarjeta de invitar se auto-expande: no tiene sentido que el
// usuario tenga que tocarla para descubrir como empezar su red social.
const hasNoFriends = ref(false);
const rankingRef = ref(null);
const onFriendsLoaded = (count) => {
  hasNoFriends.value = count === 0;
};

const isInviteExpanded = ref(false);
watch(hasNoFriends, (v) => {
  if (v) isInviteExpanded.value = true;
}, { immediate: true });
const isShareModalOpen = ref(false);
const isAddFriendModalOpen = ref(false);

const onAddFriendModalClose = (hadChanges) => {
  isAddFriendModalOpen.value = false;
  if (hadChanges) rankingRef.value?.loadFriends();
};

const router = useRouter();
// El modal no se destruye al ocultarlo (isOpen=false), solo se oculta su
// v-if interno: la busqueda/resultados quedan intactos. Se usa
// onActivated/onDeactivated (atados a la visibilidad real de esta vista,
// no a un nombre de ruta) porque el modal esta Teleport-eado a <body>: el
// display:none que keep-alive aplica al desactivar esta vista no lo alcanza,
// asi que sin esto quedaria visible encima al navegar hacia adelante.
let reopenAddFriendModal = false;
const onOpenProfileFromSearch = (id) => {
  router.push({ name: 'friend-profile', params: { id } });
};

onDeactivated(() => {
  if (isAddFriendModalOpen.value) {
    isAddFriendModalOpen.value = false;
    reopenAddFriendModal = true;
  }
});

onActivated(() => {
  if (reopenAddFriendModal) {
    reopenAddFriendModal = false;
    isAddFriendModalOpen.value = true;
  }
});
</script>
