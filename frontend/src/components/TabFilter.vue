<template>
  <div class="flex items-center gap-1 p-1 bg-gray-100 rounded-lg overflow-x-auto">
    <button
      v-for="tab in tabs"
      :key="tab.key"
      @click="$emit('update:modelValue', tab.key)"
      class="px-4 py-2 text-sm font-medium rounded-md whitespace-nowrap transition-all duration-200 cursor-pointer"
      :class="modelValue === tab.key
        ? 'bg-white text-primary-700 shadow-sm'
        : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'"
    >
      {{ tab.label }}
      <span
        v-if="tab.count != null"
        class="ml-1.5 px-1.5 py-0.5 text-xs rounded-full"
        :class="modelValue === tab.key
          ? 'bg-primary-100 text-primary-700'
          : 'bg-gray-200 text-gray-600'"
      >
        {{ tab.count }}
      </span>
    </button>
  </div>
</template>

<script setup>
defineProps({
  tabs: { type: Array, required: true },
  modelValue: { type: String, required: true },
})
defineEmits(['update:modelValue'])
</script>
