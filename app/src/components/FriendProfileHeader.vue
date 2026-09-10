<template>
  <div class="relative flex items-center gap-4 pt-5 px-5 -mx-5 -mt-5 overflow-hidden bg-[radial-gradient(ellipse_at_top,_rgba(28,176,246,0.18),_transparent_62%)]">
    <div class="w-16 h-16 shrink-0 bg-gradient-to-tr from-brand-blue to-sky-400 rounded-full flex items-center justify-center shadow-xl border-4 border-slate-800">
      <span v-if="avatarInitial" class="text-white font-black text-2xl">{{ avatarInitial }}</span>
      <UserRound v-else class="w-8 h-8 text-white stroke-[2.5]" />
    </div>

    <div class="min-w-0 flex-1 space-y-0.5">
      <div class="flex items-center gap-2 min-w-0">
        <h2 class="text-xl font-bold text-white truncate">{{ displayName }}</h2>
        <slot name="badge" />
      </div>
      <p class="text-slate-400 text-sm font-medium font-mono truncate">@{{ username }}</p>

      <div class="flex items-center gap-4 text-slate-300 text-base font-medium pt-1">
        <button type="button" @click="$emit('open-followers')" class="cursor-pointer hover:text-white transition-colors">
          <strong class="text-white font-extrabold">{{ followersCount || 0 }}</strong> {{ followersCount === 1 ? 'seguidor' : 'seguidores' }}
        </button>
        <button type="button" @click="$emit('open-following')" class="cursor-pointer hover:text-white transition-colors">
          <strong class="text-white font-extrabold">{{ followingCount || 0 }}</strong> siguiendo
        </button>
      </div>

      <div v-if="mutualFriendsCount > 0" class="flex items-center gap-1.5 text-slate-400 text-sm font-medium pt-1">
        <UsersRound class="w-4 h-4 stroke-[2.5]" />
        <span>{{ mutualFriendsCount }} amigo{{ mutualFriendsCount > 1 ? 's' : '' }} en común</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { UserRound, UsersRound } from '@lucide/vue';

defineProps({
  displayName: { type: String, default: '' },
  username: { type: String, default: '' },
  avatarInitial: { type: String, default: '' },
  followersCount: { type: Number, default: 0 },
  followingCount: { type: Number, default: 0 },
  mutualFriendsCount: { type: Number, default: 0 },
});

defineEmits(['open-followers', 'open-following']);
</script>
