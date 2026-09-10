<template>
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

        <AppButton color="blue" block :icon="UserRoundPlus" text="Agregar amigos" @click="isAddFriendModalOpen = true" />
      </div>
    </ExpandableCard>

    <ShareProfileModal
      :is-open="isShareModalOpen"
      :username="user.username"
      @close="isShareModalOpen = false"
    />

    <AddFriendModal
      :is-open="isAddFriendModalOpen"
      @close="isAddFriendModalOpen = false"
    />
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import AppButton from './AppButton.vue';
import { Share2, UserRoundPlus } from '@lucide/vue';
import ExpandableCard from './ExpandableCard.vue';
import ShareProfileModal from './ShareProfileModal.vue';
import AddFriendModal from './AddFriendModal.vue';

const props = defineProps({
  user: { type: Object, required: true },
  // true cuando el usuario todavia no sigue a nadie: fuerza la expansion para
  // que el CTA de invitar no quede escondido justo cuando mas hace falta.
  forceExpand: { type: Boolean, default: false }
});

const isInviteExpanded = ref(false);
watch(() => props.forceExpand, (v) => {
  if (v) isInviteExpanded.value = true;
}, { immediate: true });
const isShareModalOpen = ref(false);
const isAddFriendModalOpen = ref(false);
</script>
