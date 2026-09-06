<template>
  <AppPage app-header>
    <InviteFriends :user="user" :force-expand="hasNoFriends" />

    <StreakRanking :user="user" @friends-loaded="onFriendsLoaded" />
  </AppPage>
</template>

<script setup>
import { ref } from 'vue';
import AppPage from '../components/AppPage.vue';
import InviteFriends from '../components/InviteFriends.vue';
import StreakRanking from '../components/StreakRanking.vue';

defineProps({
  user: { type: Object, required: true }
});

// Con 0 amigos, la tarjeta de invitar se auto-expande: no tiene sentido que el
// usuario tenga que tocarla para descubrir como empezar su red social.
const hasNoFriends = ref(false);
const onFriendsLoaded = (count) => {
  hasNoFriends.value = count === 0;
};
</script>
