<template>
  <div class="space-y-6 animate-fade-in">
    <div>
      <h1 class="text-2xl font-bold text-gray-900">รายการผู้มีสิทธิ์ได้รับเหรียญจักรพรรดิมาลา</h1>
      <p class="text-gray-500 mt-1">รายชื่อบุคลากรที่มีคุณสมบัติครบถ้วนสำหรับขอพระราชทานเหรียญจักรพรรดิมาลา</p>
    </div>

    <!-- Loading -->
    <SkeletonLoader v-if="loading" :rows="5" />

    <!-- Table -->
    <div v-else-if="items.length" class="card overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ลำดับ</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ชื่อ</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ตำแหน่ง</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">วันบรรจุ</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">อายุราชการ</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">หน่วยงาน</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="(item, idx) in items" :key="item.id" class="hover:bg-gray-50">
            <td class="px-4 py-3 text-sm text-gray-700">{{ idx + 1 }}</td>
            <td class="px-4 py-3 text-sm text-gray-900 font-medium">{{ item.full_name }}</td>
            <td class="px-4 py-3 text-sm text-gray-700">{{ item.position }}</td>
            <td class="px-4 py-3 text-sm text-gray-700">{{ item.start_date }}</td>
            <td class="px-4 py-3 text-sm text-gray-700">{{ item.service_years }} ปี</td>
            <td class="px-4 py-3 text-sm text-gray-700">{{ item.department }}</td>
          </tr>
        </tbody>
      </table>
      <PaginationBar :page="page" :total="total" :per-page="perPage" @change="changePage" />
    </div>

    <EmptyState v-else title="ไม่พบผู้มีสิทธิ์" description="ยังไม่มีบุคลากรที่มีคุณสมบัติครบถ้วนในปีนี้" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useApi } from '@/composables/useApi.js'
import EmptyState from '@/components/EmptyState.vue'
import SkeletonLoader from '@/components/SkeletonLoader.vue'
import PaginationBar from '@/components/PaginationBar.vue'

const api = useApi()
const items = ref([])
const loading = ref(true)
const page = ref(1)
const perPage = 20
const total = ref(0)

async function fetchData() {
  loading.value = true
  try {
    const params = new URLSearchParams()
    params.set('page', page.value)
    params.set('per_page', perPage)
    const data = await api.get(`/chakrabardi/eligible?${params}`)
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
