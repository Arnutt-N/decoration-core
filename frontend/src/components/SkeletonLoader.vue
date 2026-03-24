<template>
  <div class="animate-pulse space-y-4">
    <div v-for="i in rows" :key="i" class="h-4 bg-gray-200 rounded w-full" :style="{ width: `${60 + Math.random() * 40}%` }"></div>
  </div>
</template>

<script setup>
defineProps({ rows: { type: Number, default: 3 } })
</script>
