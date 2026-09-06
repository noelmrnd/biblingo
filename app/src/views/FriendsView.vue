<template>
  <div class="space-y-6">
    <InviteFriends :user="user" />

    <StreakRanking :user="user" />
  </div>
</template>

<script setup>
import InviteFriends from '../components/InviteFriends.vue';
import StreakRanking from '../components/StreakRanking.vue';

defineProps({
  user: { type: Object, required: true }
});
</script>
