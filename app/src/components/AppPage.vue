<template>
  <slot name="header" />

  <main
    class="flex-1 overflow-y-auto w-full no-scrollbar transition-[padding] duration-200"
    :style="keyboardHeight > 0 ? { paddingBottom: `${keyboardHeight}px` } : undefined"
  >
    <div class="max-w-md mx-auto p-4 space-y-4">
      <slot />
    </div>
  </main>
</template>

<script setup>
import { keyboardHeight } from '../utils/keyboard';
</script>
