<template>
  <div class="p-6 space-y-6 animate-fade-in">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">เหรียญจักรพรรดิมาลา</h1>
        <p class="text-gray-500 mt-1">บุคลากรที่มีอายุราชการครบ 25 ปี</p>
      </div>
      <RouterLink to="/chakrabardi/new" class="btn-primary px-4 py-2 gap-2">
        <Plus class="w-4 h-4" />
        เสนอขอใหม่
      </RouterLink>
    </div>

    <!-- Tabs -->
    <TabFilter v-model="activeTab" :tabs="tabItems" />

    <!-- Search -->
    <div class="flex flex-wrap items-center gap-3">
      <div class="relative flex-1 min-w-[200px] max-w-sm">
        <Search class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" />
        <input v-model="searchQuery" @input="debouncedFetch" type="text"
               placeholder="ค้นหาชื่อ / เลขประจำตัว..."
               class="input pl-10" />
      </div>
    </div>

    <!-- Loading -->
    <SkeletonLoader v-if="loading" type="table" :rows="8" />

    <!-- Table -->
    <div v-else-if="items.length" class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-12">#</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ชื่อ-นามสกุล</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ตำแหน่ง / ระดับ</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">หน่วยงาน</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">อายุราชการ</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                {{ columnLabel }}
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">สถานะ</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="(item, idx) in items" :key="item.personnel_id"
                class="hover:bg-gray-50 cursor-pointer transition-colors"
                @click="goToDetail(item.personnel_id)">
              <td class="px-4 py-3 text-sm text-gray-500 tabular-nums">{{ (page - 1) * perPage + idx + 1 }}</td>
              <td class="px-4 py-3">
                <p class="text-sm font-medium text-gray-900">
                  {{ item.rank_name ? item.rank_name + ' ' : '' }}{{ item.prefix || '' }}{{ item.first_name }} {{ item.last_name }}
                </p>
              </td>
              <td class="px-4 py-3">
                <p class="text-sm text-gray-900">{{ item.current_position || item.position_level_name || '-' }}</p>
                <p class="text-xs text-gray-500">{{ item.current_level_code }}</p>
              </td>
              <td class="px-4 py-3 text-sm text-gray-700 max-w-[200px] truncate">{{ item.department || '-' }}</td>
              <td class="px-4 py-3 text-sm text-gray-900 tabular-nums whitespace-nowrap">
                {{ item.service_years_approx || '-' }} ปี
              </td>
              <td class="px-4 py-3 text-sm whitespace-nowrap">
                <template v-if="activeTab === 'awarded'">
                  <span class="text-gray-900">{{ item.medal_date_thai || '-' }}</span>
                  <span v-if="item.medal_year" class="text-xs text-gray-500 ml-1">({{ item.medal_year }})</span>
                </template>
                <template v-else-if="activeTab === 'deferred'">
                  <span class="text-orange-700 text-xs">{{ truncateText(item.deferral_reason, 40) }}</span>
                </template>
                <template v-else>
                  <span class="text-gray-700">{{ item.completion_25y_date_thai || '-' }}</span>
                </template>
              </td>
              <td class="px-4 py-3">
                <StatusBadge :status="getRowStatus(item)" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="px-4 py-3 border-t border-gray-200">
        <PaginationBar :total="totalItems" :limit="perPage" :offset="offset" @update:offset="changeOffset" />
      </div>
    </div>

    <!-- Empty -->
    <EmptyState v-else :title="emptyTitle" :description="emptyDescription" />
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useApi } from '@/composables/useApi.js'
import TabFilter from '@/components/TabFilter.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import EmptyState from '@/components/EmptyState.vue'
import SkeletonLoader from '@/components/SkeletonLoader.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import { useUiStore } from '@/stores/ui.js'
import { Plus, Search } from 'lucide-vue-next'

const router = useRouter()
const ui = useUiStore()
const { get } = useApi()

const items = ref([])
const loading = ref(true)
const totalItems = ref(0)
const offset = ref(0)
const perPage = 20
const activeTab = ref('all')
const searchQuery = ref('')
const tabCounts = ref({})

const page = computed(() => Math.floor(offset.value / perPage) + 1)

const tabItems = computed(() => [
  { key: 'all', label: 'ทั้งหมด', count: tabCounts.value.all },
  { key: 'eligible', label: 'ครบสิทธิ์', count: tabCounts.value.eligible },
  { key: 'pending', label: 'รอครบสิทธิ์', count: tabCounts.value.pending },
  { key: 'awarded', label: 'ได้รับแล้ว', count: tabCounts.value.awarded },
  { key: 'deferred', label: 'ชะลอ', count: tabCounts.value.deferred },
])

const columnLabel = computed(() => {
  if (activeTab.value === 'awarded') return 'วันที่ได้รับ'
  if (activeTab.value === 'deferred') return 'เหตุผล'
  return 'วันครบ 25 ปี'
})

const emptyTitle = computed(() => {
  const labels = { eligible: 'ไม่มีผู้ครบสิทธิ์', pending: 'ไม่มีผู้รอครบสิทธิ์', awarded: 'ไม่มีผู้ได้รับ', deferred: 'ไม่มีรายการชะลอ' }
  return labels[activeTab.value] || 'ไม่พบข้อมูล'
})

const emptyDescription = computed(() => 'ไม่พบข้อมูลตามเงื่อนไขที่เลือก')

function getRowStatus(item) {
  if (item.has_medal) return 'awarded'
  if (item.deferral_reason) return 'deferred'
  if (item.discipline_status) return 'discipline_issue'
  if (item.service_years_approx >= 25) return 'eligible'
  return 'awaiting_25y'
}

function truncateText(text, max) {
  if (!text) return '-'
  return text.length > max ? text.slice(0, max) + '...' : text
}

function goToDetail(personnelId) {
  router.push(`/chakrabardi/detail/${personnelId}`)
}

async function fetchData() {
  loading.value = true
  try {
    const params = new URLSearchParams({ tab: activeTab.value, page: page.value })
    if (searchQuery.value) params.set('search', searchQuery.value)

    const res = await get(`/chakrabardi/personnel?${params}`)
    items.value = res.data || []
    totalItems.value = res.pagination?.total || 0
    if (res.tab_counts) tabCounts.value = res.tab_counts
  } catch (err) {
    ui.showToast('โหลดข้อมูลล้มเหลว กรุณาลองใหม่', 'error')
  } finally {
    loading.value = false
  }
}

function changeOffset(newOffset) {
  offset.value = newOffset
  fetchData()
}

let searchTimer = null
function debouncedFetch() {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    offset.value = 0
    fetchData()
  }, 300)
}

watch(activeTab, () => {
  offset.value = 0
  fetchData()
})

onMounted(fetchData)
</script>
