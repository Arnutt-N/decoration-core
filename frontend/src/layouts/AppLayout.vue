<template>
  <div class="min-h-screen bg-gray-50">
    <AppSidebar :open="ui.sidebarOpen" @toggle="ui.toggleSidebar()" />
    <div :class="['transition-all duration-300', ui.sidebarOpen ? 'ml-64' : 'ml-0']">
      <AppTopbar @toggle-sidebar="ui.toggleSidebar()" />
      <main class="p-6">
        <RouterView />
      </main>
    </div>
  </div>
</template>

<script setup>
import { RouterView } from 'vue-router'
import AppSidebar from '@/components/AppSidebar.vue'
import AppTopbar from '@/components/AppTopbar.vue'
import { useUiStore } from '@/stores/ui.js'

const ui = useUiStore()
</script>
