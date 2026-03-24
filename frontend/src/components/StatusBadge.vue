<template>
  <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium', badgeClass]">
    {{ label }}
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  status: String,
})

const statusMap = {
  draft: { label: 'ร่าง', class: 'bg-gray-100 text-gray-700' },
  submitted: { label: 'ส่งแล้ว', class: 'bg-blue-100 text-blue-700' },
  screening: { label: 'กลั่นกรอง', class: 'bg-yellow-100 text-yellow-700' },
  committee_review: { label: 'คณะกรรมการ', class: 'bg-purple-100 text-purple-700' },
  approved: { label: 'อนุมัติ', class: 'bg-green-100 text-green-700' },
  sent_to_cabinet: { label: 'ส่ง สลค.', class: 'bg-indigo-100 text-indigo-700' },
  granted: { label: 'ได้รับพระราชทาน', class: 'bg-amber-100 text-amber-700' },
  rejected: { label: 'ไม่ผ่าน', class: 'bg-red-100 text-red-700' },
}

const label = computed(() => statusMap[props.status]?.label || props.status)
const badgeClass = computed(() => statusMap[props.status]?.class || 'bg-gray-100 text-gray-700')
</script>
