<template>
  <div v-if="totalPages > 1" class="flex items-center justify-between mt-4">
    <p class="text-sm text-gray-500">แสดง {{ from }}-{{ to }} จาก {{ total }} รายการ</p>
    <div class="flex gap-1">
      <button @click="$emit('change', page - 1)" :disabled="page <= 1"
              class="px-3 py-1.5 text-sm rounded border border-gray-300 hover:bg-gray-100 disabled:opacity-40">
        ก่อนหน้า
      </button>
      <button v-for="p in visiblePages" :key="p" @click="$emit('change', p)"
              :class="['px-3 py-1.5 text-sm rounded border', p === page ? 'bg-primary-600 text-white border-primary-600' : 'border-gray-300 hover:bg-gray-100']">
        {{ p }}
      </button>
      <button @click="$emit('change', page + 1)" :disabled="page >= totalPages"
              class="px-3 py-1.5 text-sm rounded border border-gray-300 hover:bg-gray-100 disabled:opacity-40">
        ถัดไป
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  page: Number,
  perPage: { type: Number, default: 20 },
  total: Number,
})

defineEmits(['change'])

const totalPages = computed(() => Math.ceil(props.total / props.perPage))
const from = computed(() => (props.page - 1) * props.perPage + 1)
const to = computed(() => Math.min(props.page * props.perPage, props.total))

const visiblePages = computed(() => {
  const pages = []
  const start = Math.max(1, props.page - 2)
  const end = Math.min(totalPages.value, props.page + 2)
  for (let i = start; i <= end; i++) pages.push(i)
  return pages
})
</script>
