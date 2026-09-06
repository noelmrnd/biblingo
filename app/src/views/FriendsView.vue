<template>
  <AppPage app-header>
    <InviteFriends :user="user" />

    <StreakRanking :user="user" />
  </AppPage>
</template>

<script setup>
import AppPage from '../components/AppPage.vue';
import InviteFriends from '../components/InviteFriends.vue';
import StreakRanking from '../components/StreakRanking.vue';

defineProps({
  user: { type: Object, required: true }
});
</script>
