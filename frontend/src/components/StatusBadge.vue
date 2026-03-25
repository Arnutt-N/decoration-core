<template>
  <span
    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
    :class="badgeClass"
  >
    {{ statusLabel }}
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  status: { type: String, required: true },
})

const statusMap = {
  // Decoration request statuses
  draft: { label: 'ฉบับร่าง', class: 'bg-gray-100 text-gray-600' },
  submitted: { label: 'ส่งแล้ว', class: 'bg-blue-50 text-blue-700' },
  screening: { label: 'กลั่นกรอง', class: 'bg-amber-50 text-amber-700' },
  committee_review: { label: 'คณะกรรมการพิจารณา', class: 'bg-purple-50 text-purple-700' },
  approved: { label: 'อนุมัติ', class: 'bg-green-50 text-green-700' },
  sent_to_cabinet: { label: 'ส่ง ครม.', class: 'bg-indigo-50 text-indigo-700' },
  granted: { label: 'ได้รับพระราชทาน', class: 'bg-green-100 text-green-800' },
  rejected: { label: 'ไม่อนุมัติ', class: 'bg-red-50 text-red-700' },
  // General statuses
  pending: { label: 'รอดำเนินการ', class: 'bg-amber-50 text-amber-700' },
  completed: { label: 'เสร็จสิ้น', class: 'bg-gray-100 text-gray-600' },
  active: { label: 'ใช้งานอยู่', class: 'bg-blue-50 text-blue-700' },
  eligible: { label: 'มีสิทธิ์', class: 'bg-green-50 text-green-700' },
  not_eligible: { label: 'ไม่มีสิทธิ์', class: 'bg-red-50 text-red-700' },
  // Round statuses
  open: { label: 'เปิดรับ', class: 'bg-green-50 text-green-700' },
  closed: { label: 'ปิดรับ', class: 'bg-gray-100 text-gray-600' },
  // Chakrabardi statuses
  deferred: { label: 'ชะลอ', class: 'bg-orange-50 text-orange-700' },
  awaiting_25y: { label: 'รอครบ 25 ปี', class: 'bg-sky-50 text-sky-700' },
  awarded: { label: 'ได้รับแล้ว', class: 'bg-emerald-50 text-emerald-700' },
  discipline_issue: { label: 'มีปัญหาวินัย', class: 'bg-red-50 text-red-700' },
}

const badgeClass = computed(() => statusMap[props.status]?.class || 'bg-gray-100 text-gray-600')
const statusLabel = computed(() => statusMap[props.status]?.label || props.status)
</script>
