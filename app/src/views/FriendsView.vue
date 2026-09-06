<template>
  <AppPage>
    <template #header>
      <AppHeader />
    </template>

    <div class="space-y-6">
      <InviteFriends :user="user" />

      <StreakRanking :user="user" />
    </div>
  </AppPage>
</template>

<script setup>
import AppPage from '../components/AppPage.vue';
import AppHeader from '../components/AppHeader.vue';
import InviteFriends from '../components/InviteFriends.vue';
import StreakRanking from '../components/StreakRanking.vue';

defineProps({
  user: { type: Object, required: true }
});
</script>
