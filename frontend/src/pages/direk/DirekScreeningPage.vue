<template>
  <div class="space-y-6 animate-fade-in">
    <div>
      <h1 class="text-2xl font-bold text-gray-900">ตรวจสอบคุณสมบัติผู้เสนอขอ</h1>
      <p class="text-gray-500 mt-1">กลั่นกรองคุณสมบัติผู้เสนอขอดิเรกคุณาภรณ์ที่รอดำเนินการ</p>
    </div>

    <!-- Loading -->
    <SkeletonLoader v-if="loading" :rows="5" />

    <!-- List -->
    <div v-else-if="items.length" class="space-y-3">
      <div v-for="item in items" :key="item.id" class="card">
        <!-- Header row -->
        <div class="flex items-center justify-between cursor-pointer" @click="toggleExpand(item.id)">
          <div class="flex items-center gap-3">
            <ChevronDown class="w-5 h-5 text-gray-400 transition-transform"
                         :class="{ 'rotate-180': expandedId === item.id }" />
            <div>
              <p class="font-medium text-gray-900">{{ item.full_name }}</p>
              <p class="text-sm text-gray-500">{{ item.request_type }} | ชั้น {{ item.level_name }}</p>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <StatusBadge :status="item.status" />
            <span class="text-sm text-gray-400">{{ item.created_at_thai || item.created_at }}</span>
          </div>
        </div>

        <!-- Expanded screening checklist -->
        <div v-if="expandedId === item.id" class="mt-4 pt-4 border-t border-gray-100 space-y-4">
          <h3 class="text-sm font-semibold text-gray-700">รายการตรวจสอบคุณสมบัติ</h3>
          <div class="space-y-2">
            <div v-for="(check, idx) in screeningChecklist" :key="idx"
                 class="flex items-center gap-3 p-2 rounded-lg bg-gray-50">
              <input :id="`check-${item.id}-${idx}`" type="checkbox"
                     v-model="checkStates[item.id][idx]"
                     class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
              <label :for="`check-${item.id}-${idx}`" class="text-sm text-gray-700">{{ check }}</label>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">หมายเหตุ</label>
            <textarea v-model="remarks[item.id]" rows="2" placeholder="ระบุหมายเหตุ (ถ้ามี)..."
                      class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500" />
          </div>

          <div class="flex gap-3">
            <button @click="submitScreening(item.id, 'approved')"
                    :disabled="submittingId === item.id"
                    class="inline-flex items-center gap-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 text-sm transition-colors">
              <CheckCircle class="w-4 h-4" />
              ผ่านการกลั่นกรอง
            </button>
            <button @click="submitScreening(item.id, 'rejected')"
                    :disabled="submittingId === item.id"
                    class="inline-flex items-center gap-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50 text-sm transition-colors">
              <XCircle class="w-4 h-4" />
              ไม่ผ่าน
            </button>
          </div>
        </div>
      </div>
    </div>

    <EmptyState v-else title="ไม่มีรายการรอกลั่นกรอง" description="ไม่มีคำขอดิเรกคุณาภรณ์ที่รอตรวจสอบคุณสมบัติ" />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useApi } from '@/composables/useApi.js'
import { useUiStore } from '@/stores/ui.js'
import StatusBadge from '@/components/StatusBadge.vue'
import EmptyState from '@/components/EmptyState.vue'
import SkeletonLoader from '@/components/SkeletonLoader.vue'
import { ChevronDown, CheckCircle, XCircle } from 'lucide-vue-next'

const api = useApi()
const ui = useUiStore()

const items = ref([])
const loading = ref(true)
const expandedId = ref(null)
const submittingId = ref(null)
const checkStates = reactive({})
const remarks = reactive({})

const screeningChecklist = [
  'ตรวจสอบคุณสมบัติเบื้องต้นตามระเบียบ',
  'ตรวจสอบระยะเวลาการปฏิบัติงาน/บริจาค',
  'ตรวจสอบประวัติความประพฤติ',
  'ตรวจสอบไม่เคยถูกลงโทษทางวินัย',
  'ตรวจสอบเอกสารประกอบครบถ้วน',
  'ตรวจสอบชั้นตราที่เสนอขอถูกต้อง',
]

function toggleExpand(id) {
  if (expandedId.value === id) {
    expandedId.value = null
  } else {
    expandedId.value = id
    if (!checkStates[id]) {
      checkStates[id] = screeningChecklist.map(() => false)
      remarks[id] = ''
    }
  }
}

async function fetchData() {
  loading.value = true
  try {
    const data = await api.get('/direk/requests?status=submitted')
    items.value = data.data || []
  } catch (err) {
    console.error('โหลดข้อมูลล้มเหลว:', err)
  } finally {
    loading.value = false
  }
}

async function submitScreening(requestId, result) {
  submittingId.value = requestId
  try {
    await api.put(`/direk/requests/${requestId}`, {
      status: result === 'approved' ? 'approved' : 'rejected',
      eligibility_passed: result === 'approved' ? 1 : 0,
      eligibility_notes: remarks[requestId] || '',
    })
    ui.showToast(
      result === 'approved' ? 'ผ่านการกลั่นกรองแล้ว' : 'ปฏิเสธคำขอแล้ว',
      result === 'approved' ? 'success' : 'warning'
    )
    items.value = items.value.filter((i) => i.id !== requestId)
    expandedId.value = null
  } catch (err) {
    ui.showToast(`เกิดข้อผิดพลาด: ${err.message}`, 'error')
  } finally {
    submittingId.value = null
  }
}

onMounted(fetchData)
</script>
