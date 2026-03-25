<template>
  <div class="p-6 space-y-6 animate-fade-in">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">ช้างเผือก-มงกุฎไทย</h1>
        <p class="text-gray-500 mt-1">รายการคำขอพระราชทานเครื่องราชอิสริยาภรณ์</p>
      </div>
      <RouterLink to="/decorations/new"
                  class="btn-primary px-4 py-2 gap-2">
        <Plus class="w-4 h-4" />
        เสนอขอใหม่
      </RouterLink>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap items-center gap-3">
      <select v-model="filterYear" @change="resetAndFetch"
              class="input w-auto min-w-[120px]">
        <option value="">ทุกปี</option>
        <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
      </select>
      <select v-model="filterStatus" @change="resetAndFetch"
              class="input w-auto min-w-[140px]">
        <option value="">ทุกสถานะ</option>
        <option value="draft">ฉบับร่าง</option>
        <option value="submitted">ส่งแล้ว</option>
        <option value="screening">กลั่นกรอง</option>
        <option value="approved">อนุมัติ</option>
        <option value="granted">ได้รับพระราชทาน</option>
        <option value="rejected">ไม่ผ่าน</option>
      </select>
      <select v-model="filterType" @change="resetAndFetch"
              class="input w-auto min-w-[160px]">
        <option value="">ทุกประเภท</option>
        <option value="ช้างเผือก">ช้างเผือก</option>
        <option value="มงกุฎไทย">มงกุฎไทย</option>
      </select>
      <div class="relative flex-1 min-w-[200px] max-w-sm">
        <Search class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" />
        <input v-model="searchQuery" @input="debouncedFetch" type="text"
               placeholder="ค้นหาชื่อ / เลขประจำตัว..."
               class="input pl-10" />
      </div>
    </div>

    <!-- Loading -->
    <SkeletonLoader v-if="loading" type="table" :rows="6" />

    <!-- Table -->
    <div v-else-if="items.length" class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-12">#</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ชื่อ-นามสกุล</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ระดับ/ตำแหน่ง</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ชั้นที่ขอ</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">สถานะ</th>
              <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase w-12"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <template v-for="(item, idx) in items" :key="item.request_id">
              <ExpandableRow
                :expanded="expandedId === item.request_id"
                :columns="6"
                @toggle="toggleExpand(item.request_id)"
              >
                <template #summary>
                  <td class="px-4 py-3 text-sm text-gray-500 tabular-nums">{{ offset + idx + 1 }}</td>
                  <td class="px-4 py-3">
                    <p class="text-sm font-medium text-gray-900">
                      {{ item.rank_name ? item.rank_name + ' ' : '' }}{{ item.prefix || '' }}{{ item.first_name }} {{ item.last_name }}
                    </p>
                    <p class="text-xs text-gray-500 mt-0.5">{{ item.department || '' }}</p>
                  </td>
                  <td class="px-4 py-3">
                    <p class="text-sm text-gray-900">{{ item.position_level_name || getLevelName(item.current_level_code) }}</p>
                    <p class="text-xs text-gray-500">{{ item.current_level_code }}</p>
                  </td>
                  <td class="px-4 py-3">
                    <span class="text-sm font-medium text-primary-700">{{ item.requested_level_abbr }}</span>
                    <span v-if="item.requested_level_name" class="text-xs text-gray-500 ml-1">{{ item.requested_level_name }}</span>
                  </td>
                  <td class="px-4 py-3"><StatusBadge :status="item.status" /></td>
                </template>

                <template #detail>
                  <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- ข้อมูลส่วนตัว -->
                    <div>
                      <h4 class="text-xs font-semibold text-gray-500 uppercase mb-3">ข้อมูลส่วนตัว</h4>
                      <dl class="space-y-2 text-sm">
                        <div class="flex justify-between">
                          <dt class="text-gray-500">วันเกิด</dt>
                          <dd class="text-gray-900">{{ item.birth_date_thai || '-' }}</dd>
                        </div>
                        <div class="flex justify-between">
                          <dt class="text-gray-500">เริ่มรับราชการ</dt>
                          <dd class="text-gray-900">{{ item.hire_date_thai || '-' }}</dd>
                        </div>
                        <div class="flex justify-between">
                          <dt class="text-gray-500">เริ่มระดับปัจจุบัน</dt>
                          <dd class="text-gray-900">{{ item.position_level_start_date_thai || '-' }}</dd>
                        </div>
                      </dl>
                    </div>

                    <!-- ข้อมูลตำแหน่ง/เงินเดือน -->
                    <div>
                      <h4 class="text-xs font-semibold text-gray-500 uppercase mb-3">ตำแหน่ง / เงินเดือน</h4>
                      <dl class="space-y-2 text-sm">
                        <div class="flex justify-between">
                          <dt class="text-gray-500">ประเภท</dt>
                          <dd class="text-gray-900">{{ item.position_type || '-' }}</dd>
                        </div>
                        <div class="flex justify-between">
                          <dt class="text-gray-500">เงินเดือน</dt>
                          <dd class="text-gray-900 tabular-nums">{{ formatNumber(item.salary) }}</dd>
                        </div>
                        <div class="flex justify-between">
                          <dt class="text-gray-500">เงินเดือน 5 ปี</dt>
                          <dd class="text-gray-900 tabular-nums">{{ formatNumber(item.salary_5y) }}</dd>
                        </div>
                        <div class="flex justify-between">
                          <dt class="text-gray-500">เงินประจำตำแหน่ง</dt>
                          <dd class="text-gray-900 tabular-nums">{{ formatNumber(item.position_allowance) }}</dd>
                        </div>
                      </dl>
                    </div>

                    <!-- เครื่องราชฯ -->
                    <div>
                      <h4 class="text-xs font-semibold text-gray-500 uppercase mb-3">เครื่องราชอิสริยาภรณ์</h4>
                      <dl class="space-y-2 text-sm">
                        <div class="flex justify-between">
                          <dt class="text-gray-500">ชั้นปัจจุบัน</dt>
                          <dd class="font-medium text-gray-900">{{ item.current_level_abbr || 'ยังไม่เคยได้รับ' }}</dd>
                        </div>
                        <div class="flex justify-between">
                          <dt class="text-gray-500">ชั้นที่ขอ</dt>
                          <dd class="font-medium text-primary-700">{{ item.requested_level_abbr }}</dd>
                        </div>
                        <div class="flex justify-between">
                          <dt class="text-gray-500">ผลตรวจคุณสมบัติ</dt>
                          <dd>
                            <span v-if="item.eligibility_passed === 1" class="text-green-600">✓ ผ่าน</span>
                            <span v-else-if="item.eligibility_passed === 0" class="text-red-600">✗ ไม่ผ่าน</span>
                            <span v-else class="text-gray-400">- รอตรวจ</span>
                          </dd>
                        </div>
                        <div class="flex justify-between">
                          <dt class="text-gray-500">ทะเบียนฐานันดร</dt>
                          <dd>
                            <span v-if="item.thabanandorn_status === 'ผ่าน'" class="text-green-600">✓ ผ่าน</span>
                            <span v-else-if="item.thabanandorn_status === 'ไม่ผ่าน'" class="text-red-600">✗ ไม่ผ่าน</span>
                            <span v-else class="text-gray-400">- {{ item.thabanandorn_status || 'รอตรวจ' }}</span>
                          </dd>
                        </div>
                        <div class="flex justify-between">
                          <dt class="text-gray-500">สิทธิ์</dt>
                          <dd>
                            <StatusBadge v-if="item.eligibility_right === 'มีสิทธิ'" status="eligible" />
                            <StatusBadge v-else-if="item.eligibility_right === 'ไม่มีสิทธิ'" status="not_eligible" />
                            <span v-else class="text-gray-400 text-xs">รอพิจารณา</span>
                          </dd>
                        </div>
                      </dl>
                    </div>
                  </div>
                </template>
              </ExpandableRow>
            </template>
          </tbody>
        </table>
      </div>
      <div class="px-4 py-3 border-t border-gray-200">
        <PaginationBar :total="total" :limit="perPage" :offset="offset" @update:offset="changeOffset" />
      </div>
    </div>

    <!-- Empty -->
    <EmptyState v-else title="ไม่พบรายการคำขอ" description="ยังไม่มีคำขอเครื่องราชอิสริยาภรณ์ตามเงื่อนไขที่เลือก" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { useApi } from '@/composables/useApi.js'
import StatusBadge from '@/components/StatusBadge.vue'
import EmptyState from '@/components/EmptyState.vue'
import SkeletonLoader from '@/components/SkeletonLoader.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import ExpandableRow from '@/components/ExpandableRow.vue'
import { useUiStore } from '@/stores/ui.js'
import { Plus, Search } from 'lucide-vue-next'

const ui = useUiStore()
const { get } = useApi()

const items = ref([])
const loading = ref(true)
const total = ref(0)
const offset = ref(0)
const perPage = 20
const expandedId = ref(null)

const filterYear = ref('')
const filterStatus = ref('')
const filterType = ref('')
const searchQuery = ref('')

const currentBuddhistYear = new Date().getFullYear() + 543
const yearOptions = Array.from({ length: 5 }, (_, i) => currentBuddhistYear - i)

const page = computed(() => Math.floor(offset.value / perPage) + 1)

function getLevelName(code) {
  const map = { K1: 'ปฏิบัติการ', K2: 'ชำนาญการ', K3: 'ชำนาญการพิเศษ', K4: 'เชี่ยวชาญ', K5: 'ทรงคุณวุฒิ', O1: 'ปฏิบัติงาน', O2: 'ชำนาญงาน', O3: 'อาวุโส', D1: 'อำนวยการ ต้น', D2: 'อำนวยการ สูง', M1: 'บริหาร ต้น', M2: 'บริหาร สูง' }
  return map[code] || code || '-'
}

function formatNumber(val) {
  if (val == null || val === '') return '-'
  return Number(val).toLocaleString('th-TH')
}

async function fetchData() {
  loading.value = true
  try {
    const params = new URLSearchParams({ page: page.value })
    if (filterYear.value) params.set('year', filterYear.value)
    if (filterStatus.value) params.set('status', filterStatus.value)
    if (filterType.value) params.set('type', filterType.value)
    if (searchQuery.value) params.set('search', searchQuery.value)

    const res = await get(`/decorations?${params}`)
    items.value = res.data || []
    total.value = res.pagination?.total || 0
  } catch (err) {
    ui.showToast('โหลดข้อมูลล้มเหลว กรุณาลองใหม่', 'error')
  } finally {
    loading.value = false
  }
}

function resetAndFetch() {
  offset.value = 0
  expandedId.value = null
  fetchData()
}

function changeOffset(newOffset) {
  offset.value = newOffset
  expandedId.value = null
  fetchData()
}

let searchTimer = null
function debouncedFetch() {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(resetAndFetch, 300)
}

function toggleExpand(id) {
  expandedId.value = expandedId.value === id ? null : id
}

onMounted(fetchData)
</script>
