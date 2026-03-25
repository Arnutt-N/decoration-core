<template>
  <tr
    class="cursor-pointer transition-colors"
    :class="expanded ? 'bg-primary-50/50' : 'hover:bg-gray-50'"
    @click="$emit('toggle')"
  >
    <slot name="summary" />
    <td class="px-4 py-3 text-center">
      <ChevronDown
        class="w-4 h-4 text-gray-400 transition-transform duration-200 inline-block"
        :class="{ 'rotate-180': expanded }"
      />
    </td>
  </tr>
  <tr v-if="expanded">
    <td :colspan="columns" class="p-0">
      <div class="px-6 py-4 bg-gray-50 border-t border-b border-gray-100 animate-slide-up">
        <slot name="detail" />
      </div>
    </td>
  </tr>
</template>

<script setup>
import { ChevronDown } from 'lucide-vue-next'

defineProps({
  expanded: { type: Boolean, default: false },
  columns: { type: Number, default: 7 },
})
defineEmits(['toggle'])
</script>
