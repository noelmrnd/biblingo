<template>
  <div class="relative text-center p-5 -mx-5 -mt-5 space-y-3 overflow-hidden bg-[radial-gradient(ellipse_at_top,_rgba(28,176,246,0.18),_transparent_62%)]">
    <slot name="corner" />

    <div class="w-20 h-20 bg-gradient-to-tr from-brand-blue to-sky-400 rounded-full flex items-center justify-center shadow-xl mx-auto border-4 border-slate-800">
      <span v-if="avatarInitial" class="text-white font-black text-3xl">{{ avatarInitial }}</span>
      <UserRound v-else class="w-10 h-10 text-white stroke-[2.5]" />
    </div>

    <div class="space-y-1">
      <h2 class="text-2xl font-extrabold text-white">{{ displayName }}</h2>
      <p class="text-slate-300 text-base font-medium font-mono flex flex-wrap items-center justify-center gap-x-2 gap-y-1">
        <span>@{{ username }}</span>
        <slot name="badge" />
      </p>
      <p v-if="memberSinceLabel" class="text-slate-400 text-base font-medium">Leyendo desde {{ memberSinceLabel }}</p>
    </div>

    <div class="flex items-center justify-center gap-6 text-slate-300 text-base font-medium">
      <button type="button" @click="$emit('open-followers')" class="cursor-pointer hover:text-white transition-colors">
        <strong class="text-white font-extrabold">{{ followersCount || 0 }}</strong> {{ followersCount === 1 ? 'seguidor' : 'seguidores' }}
      </button>
      <button type="button" @click="$emit('open-following')" class="cursor-pointer hover:text-white transition-colors">
        <strong class="text-white font-extrabold">{{ followingCount || 0 }}</strong> seguidos
      </button>
    </div>
  </div>
</template>

<script setup>
import { UserRound } from '@lucide/vue';

defineProps({
  displayName: { type: String, default: '' },
  username: { type: String, default: '' },
  memberSinceLabel: { type: String, default: '' },
  followersCount: { type: Number, default: 0 },
  followingCount: { type: Number, default: 0 },
  avatarInitial: { type: String, default: '' }
});

defineEmits(['open-followers', 'open-following']);
</script>
