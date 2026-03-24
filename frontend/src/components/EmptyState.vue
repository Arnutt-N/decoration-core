<template>
  <div class="text-center py-12">
    <component :is="icon" class="w-12 h-12 text-gray-300 mx-auto mb-4" />
    <h3 class="text-lg font-medium text-gray-500">{{ title }}</h3>
    <p v-if="description" class="text-sm text-gray-400 mt-1">{{ description }}</p>
    <slot />
  </div>
</template>

<script setup>
import { Inbox } from 'lucide-vue-next'
defineProps({
  title: { type: String, default: 'ไม่พบข้อมูล' },
  description: String,
  icon: { type: [Object, Function], default: () => Inbox },
})
</script>
