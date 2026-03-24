<template>
  <div class="fixed top-4 right-4 z-50 space-y-2">
    <TransitionGroup name="toast">
      <div v-for="toast in toasts" :key="toast.id"
           :class="['flex items-center gap-3 px-4 py-3 rounded-lg shadow-lg text-sm max-w-sm', toastClass(toast.type)]">
        <span>{{ toast.message }}</span>
        <button @click="ui.removeToast(toast.id)" class="ml-auto opacity-70 hover:opacity-100">&times;</button>
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useUiStore } from '@/stores/ui.js'

const ui = useUiStore()
const toasts = computed(() => ui.toasts)

function toastClass(type) {
  const classes = {
    success: 'bg-green-50 text-green-800 border border-green-200',
    error: 'bg-red-50 text-red-800 border border-red-200',
    warning: 'bg-amber-50 text-amber-800 border border-amber-200',
    info: 'bg-blue-50 text-blue-800 border border-blue-200',
  }
  return classes[type] || classes.info
}
</script>

<style scoped>
.toast-enter-active { transition: all 0.3s ease-out; }
.toast-leave-active { transition: all 0.2s ease-in; }
.toast-enter-from { opacity: 0; transform: translateX(20px); }
.toast-leave-to { opacity: 0; transform: translateX(20px); }
</style>
