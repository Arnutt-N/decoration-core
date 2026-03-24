<template>
  <div class="space-y-6 animate-fade-in">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">รายการคำขอดิเรกคุณาภรณ์</h1>
        <p class="text-gray-500 mt-1">รายการคำขอพระราชทานเครื่องราชอิสริยาภรณ์อันเป็นที่สรรเสริญยิ่งดิเรกคุณาภรณ์</p>
      </div>
      <RouterLink to="/direk/requests/new"
                  class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
        <Plus class="w-4 h-4" />
        เสนอขอใหม่
      </RouterLink>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap gap-3">
      <select v-model="filterYear" @change="fetchData"
              class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
        <option value="">ทุกปี</option>
        <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
      </select>
      <select v-model="filterStatus" @change="fetchData"
              class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
        <option value="">ทุกสถานะ</option>
        <option value="draft">ร่าง</option>
        <option value="submitted">ส่งแล้ว</option>
        <option value="screening">กลั่นกรอง</option>
        <option value="approved">อนุมัติ</option>
        <option value="granted">ได้รับพระราชทาน</option>
        <option value="rejected">ไม่ผ่าน</option>
      </select>
      <select v-model="filterRequestType" @change="fetchData"
              class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
        <option value="">ทุกประเภทคำขอ</option>
        <option value="ผลงาน">ผลงาน</option>
        <option value="บริจาค">บริจาค</option>
      </select>
    </div>

    <!-- Loading -->
    <SkeletonLoader v-if="loading" :rows="5" />

    <!-- Table -->
    <div v-else-if="items.length" class="card overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ลำดับ</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ชื่อ-นามสกุล</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ชั้นที่ขอ</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ประเภทคำขอ</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">สถานะ</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">วันที่</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="(item, idx) in items" :key="item.id" class="hover:bg-gray-50">
            <td class="px-4 py-3 text-sm text-gray-700">{{ idx + 1 }}</td>
            <td class="px-4 py-3 text-sm text-gray-900 font-medium">{{ item.full_name }}</td>
            <td class="px-4 py-3 text-sm text-gray-700">{{ item.level_name }}</td>
            <td class="px-4 py-3 text-sm text-gray-700">{{ item.request_type }}</td>
            <td class="px-4 py-3"><StatusBadge :status="item.status" /></td>
            <td class="px-4 py-3 text-sm text-gray-500">{{ item.created_at }}</td>
          </tr>
        </tbody>
      </table>
      <PaginationBar :page="page" :total="total" :per-page="perPage" @change="changePage" />
    </div>

    <EmptyState v-else title="ไม่พบรายการคำขอ" description="ยังไม่มีคำขอดิเรกคุณาภรณ์ในระบบ" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { useApi } from '@/composables/useApi.js'
import StatusBadge from '@/components/StatusBadge.vue'
import EmptyState from '@/components/EmptyState.vue'
import SkeletonLoader from '@/components/SkeletonLoader.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import { Plus } from 'lucide-vue-next'

const api = useApi()
const items = ref([])
const loading = ref(true)
const page = ref(1)
const perPage = 20
const total = ref(0)
const filterYear = ref('')
const filterStatus = ref('')
const filterRequestType = ref('')

const currentBuddhistYear = new Date().getFullYear() + 543
const yearOptions = Array.from({ length: 5 }, (_, i) => currentBuddhistYear - i)

async function fetchData() {
  loading.value = true
  try {
    const params = new URLSearchParams()
    params.set('page', page.value)
    params.set('per_page', perPage)
    if (filterYear.value) params.set('year', filterYear.value)
    if (filterStatus.value) params.set('status', filterStatus.value)
    if (filterRequestType.value) params.set('request_type', filterRequestType.value)
    const data = await api.get(`/direk/requests?${params}`)
    items.value = data.data || []
    total.value = data.pagination?.total || data.total || items.value.length
  } catch (err) {
    console.error('โหลดข้อมูลล้มเหลว:', err)
  } finally {
    loading.value = false
  }
}

function changePage(p) {
  page.value = p
  fetchData()
}

onMounted(fetchData)
</script>
