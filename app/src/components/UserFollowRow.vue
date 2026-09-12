<template>
  <div
    @click="$emit('open')"
    class="w-full text-left p-3 rounded-2xl border border-slate-800 bg-slate-950/60 transition-all flex items-center gap-3 cursor-pointer hover:bg-slate-800/80 hover:border-slate-700"
  >
    <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-brand-blue to-sky-400 flex items-center justify-center shrink-0 text-white font-black text-base">
      {{ (user.display_name || '?').charAt(0).toUpperCase() }}
    </div>
    <div class="min-w-0 flex-1">
      <h4 class="font-extrabold text-white text-base truncate flex items-center gap-2">
        <span class="truncate">{{ user.display_name }}</span>
        <span v-if="user.is_self" class="text-xs bg-brand-green/20 text-brand-green px-2 py-0.5 rounded-md font-black flex-none">TÚ</span>
      </h4>
      <p class="text-slate-400 text-sm font-medium truncate">@{{ user.username }}</p>
    </div>
    <span v-if="!user.is_self && user.is_following" class="text-sm text-slate-500 font-medium flex-none">Siguiendo</span>
    <AppButton
      v-else-if="!user.is_self"
      color="blue"
      size="sm"
      text="Seguir"
      :loading="isFollowLoading"
      :disabled="followDisabled"
      @click.stop="$emit('follow')"
    />
  </div>
</template>

<script setup>
import AppButton from './AppButton.vue';

defineProps({
  user: { type: Object, required: true },
  isFollowLoading: { type: Boolean, default: false },
  // Deshabilita el boton "Seguir" de esta fila sin estar cargando (ej. ya hay
  // otra fila con un follow en curso).
  followDisabled: { type: Boolean, default: false }
});

defineEmits(['open', 'follow']);
</script>
