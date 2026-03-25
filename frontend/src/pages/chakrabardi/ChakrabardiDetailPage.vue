<template>
  <div class="p-6 space-y-6 animate-fade-in">
    <!-- Back button -->
    <button @click="router.push('/chakrabardi')" class="btn-ghost px-3 py-1.5 gap-1 text-sm">
      <ArrowLeft class="w-4 h-4" />
      กลับรายการ
    </button>

    <!-- Loading -->
    <SkeletonLoader v-if="loading" :rows="6" />

    <template v-else-if="data">
      <!-- Header card -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-start justify-between">
          <div>
            <h1 class="text-xl font-bold text-gray-900">
              {{ person.rank_name ? person.rank_name + ' ' : '' }}{{ person.prefix || '' }}{{ person.first_name }} {{ person.last_name }}
            </h1>
            <p class="text-gray-500 mt-1">
              {{ person.current_position || person.position_level_name || '-' }}
              <span v-if="person.current_level_code" class="text-gray-400">| {{ person.current_level_code }}</span>
            </p>
            <p class="text-sm text-gray-400 mt-0.5">{{ data.personnel?.department || person.org_name || '-' }}</p>
          </div>
          <StatusBadge :status="overallStatus" />
        </div>
      </div>

      <!-- Info grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
          <p class="text-xs font-medium text-gray-500 uppercase">วันบรรจุ</p>
          <p class="text-lg font-semibold text-gray-900 mt-1">{{ person.hire_date_thai || '-' }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
          <p class="text-xs font-medium text-gray-500 uppercase">อายุราชการ</p>
          <p class="text-lg font-semibold text-gray-900 mt-1">{{ data.service?.text || '-' }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
          <p class="text-xs font-medium text-gray-500 uppercase">วันครบ 25 ปี</p>
          <p class="text-lg font-semibold mt-1" :class="isPast25y ? 'text-green-600' : 'text-amber-600'">
            {{ data.completion_25y_date_thai || '-' }}
          </p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
          <p class="text-xs font-medium text-gray-500 uppercase">วันเกษียณ</p>
          <p class="text-lg font-semibold text-gray-900 mt-1">{{ data.retirement_date_thai || person.retirement_date_thai || '-' }}</p>
        </div>
      </div>

      <!-- Discipline warning -->
      <div v-if="person.discipline_status" class="bg-red-50 border border-red-200 rounded-lg p-4">
        <div class="flex items-start gap-3">
          <AlertTriangle class="w-5 h-5 text-red-600 mt-0.5 shrink-0" />
          <div>
            <h3 class="font-medium text-red-800">สถานะวินัย</h3>
            <p class="text-sm text-red-700 mt-1">{{ person.discipline_status }}</p>
            <p v-if="person.discipline_detail" class="text-sm text-red-600 mt-1">{{ person.discipline_detail }}</p>
          </div>
        </div>
      </div>

      <!-- Medal status -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="font-semibold text-gray-900 mb-4">สถานะเหรียญจักรพรรดิมาลา</h2>
        <div v-if="data.has_medal" class="flex items-center gap-3 p-4 bg-emerald-50 rounded-lg">
          <CheckCircle class="w-6 h-6 text-emerald-600" />
          <div>
            <p class="font-medium text-emerald-800">ได้รับเหรียญจักรพรรดิมาลาแล้ว</p>
            <p v-if="data.history?.[0]" class="text-sm text-emerald-700 mt-0.5">
              วันที่ได้รับ: {{ data.history[0].award_date_thai || '-' }}
              <span v-if="data.history[0].award_year">(พ.ศ. {{ data.history[0].award_year }})</span>
            </p>
          </div>
        </div>
        <div v-else-if="isPast25y" class="flex items-center gap-3 p-4 bg-amber-50 rounded-lg">
          <Clock class="w-6 h-6 text-amber-600" />
          <div>
            <p class="font-medium text-amber-800">ครบ 25 ปี — ยังไม่ได้รับเหรียญ</p>
            <p class="text-sm text-amber-700 mt-0.5">มีสิทธิ์เสนอขอพระราชทานเหรียญจักรพรรดิมาลา</p>
          </div>
        </div>
        <div v-else class="flex items-center gap-3 p-4 bg-sky-50 rounded-lg">
          <Clock class="w-6 h-6 text-sky-600" />
          <div>
            <p class="font-medium text-sky-800">ยังไม่ครบ 25 ปี</p>
            <p class="text-sm text-sky-700 mt-0.5">จะครบสิทธิ์วันที่ {{ data.completion_25y_date_thai || '-' }}</p>
          </div>
        </div>
      </div>

      <!-- Award history -->
      <div v-if="data.history?.length" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="font-semibold text-gray-900 mb-4">ประวัติได้รับเหรียญ</h2>
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">ปี พ.ศ.</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">วันที่ได้รับ</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">วันที่เสนอขอ</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">ราชกิจจาฯ</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">หมายเหตุ</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="h in data.history" :key="h.award_id" class="hover:bg-gray-50">
              <td class="px-4 py-2 text-sm text-gray-900">{{ h.award_year || '-' }}</td>
              <td class="px-4 py-2 text-sm text-gray-700">{{ h.award_date_thai || '-' }}</td>
              <td class="px-4 py-2 text-sm text-gray-700">{{ h.submit_date_thai || '-' }}</td>
              <td class="px-4 py-2 text-sm text-gray-500">{{ h.gazette_ref || '-' }}</td>
              <td class="px-4 py-2 text-sm text-gray-500">{{ h.remarks || '-' }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Request history / deferral -->
      <div v-if="data.requests?.length" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="font-semibold text-gray-900 mb-4">ประวัติคำขอ</h2>
        <div class="space-y-3">
          <div v-for="req in data.requests" :key="req.request_id"
               class="p-4 rounded-lg border"
               :class="req.deferral_reason ? 'border-orange-200 bg-orange-50' : 'border-gray-200 bg-gray-50'">
            <div class="flex items-center justify-between">
              <div>
                <StatusBadge :status="req.status" />
                <span class="text-sm text-gray-600 ml-2">ปี {{ req.request_year }}</span>
              </div>
              <span class="text-xs text-gray-400">{{ req.created_at_thai || req.created_at }}</span>
            </div>
            <div v-if="req.deferral_reason" class="mt-2">
              <p class="text-sm font-medium text-orange-800">เหตุผลชะลอ:</p>
              <p class="text-sm text-orange-700 mt-0.5">{{ req.deferral_reason }}</p>
            </div>
            <p v-if="req.remarks" class="text-sm text-gray-600 mt-1">หมายเหตุ: {{ req.remarks }}</p>
          </div>
        </div>
      </div>
    </template>

    <EmptyState v-else title="ไม่พบข้อมูล" description="ไม่พบข้อมูลบุคลากรรายนี้" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApi } from '@/composables/useApi.js'
import StatusBadge from '@/components/StatusBadge.vue'
import EmptyState from '@/components/EmptyState.vue'
import SkeletonLoader from '@/components/SkeletonLoader.vue'
import { useUiStore } from '@/stores/ui.js'
import { ArrowLeft, AlertTriangle, CheckCircle, Clock } from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()
const ui = useUiStore()
const { get } = useApi()

const loading = ref(true)
const data = ref(null)

const person = computed(() => data.value?.personnel || {})

const isPast25y = computed(() => {
  if (!data.value?.completion_25y_date) return false
  return new Date(data.value.completion_25y_date) <= new Date()
})

const overallStatus = computed(() => {
  if (data.value?.has_medal) return 'awarded'
  if (person.value.discipline_status) return 'discipline_issue'
  const latestReq = data.value?.requests?.[0]
  if (latestReq?.deferral_reason) return 'deferred'
  if (isPast25y.value) return 'eligible'
  return 'awaiting_25y'
})

async function fetchDetail() {
  loading.value = true
  try {
    const res = await get(`/chakrabardi/personnel/${route.params.personnelId}`)
    data.value = res.data || null
  } catch (err) {
    ui.showToast('โหลดข้อมูลล้มเหลว กรุณาลองใหม่', 'error')
  } finally {
    loading.value = false
  }
}

onMounted(fetchDetail)
</script>
