<template>
  <div class="card hover:shadow-md transition-shadow">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm text-gray-500 mb-1">{{ label }}</p>
        <p class="text-2xl font-bold text-gray-900">{{ value }}</p>
        <p v-if="subtitle" class="text-xs text-gray-400 mt-1">{{ subtitle }}</p>
      </div>
      <div :class="['w-12 h-12 rounded-xl flex items-center justify-center', iconBg]">
        <component :is="icon" class="w-6 h-6" :class="iconColor" />
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  label: String,
  value: [String, Number],
  subtitle: String,
  icon: [Object, Function],
  iconBg: { type: String, default: 'bg-primary-100' },
  iconColor: { type: String, default: 'text-primary-600' },
})
</script>
