<template>
  <div class="space-y-6 animate-fade-in">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">ข้อมูลอาสาสมัคร/บุคคลภายนอก</h1>
        <p class="text-gray-500 mt-1">จัดการข้อมูลบุคคลภายนอกสำหรับเสนอขอดิเรกคุณาภรณ์</p>
      </div>
      <button @click="showAddForm = true"
              class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
        <Plus class="w-4 h-4" />
        เพิ่มบุคคล
      </button>
    </div>

    <!-- ค้นหา -->
    <div class="relative max-w-md">
      <Search class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" />
      <input v-model="searchQuery" @input="fetchData" type="text" placeholder="ค้นหาชื่อ หรือ เลขบัตร..."
             class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500" />
    </div>

    <!-- Loading -->
    <SkeletonLoader v-if="loading" :rows="5" />

    <!-- Table -->
    <div v-else-if="items.length" class="card overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ชื่อ-นามสกุล</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">เลขบัตร</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ประเภทอาสาสมัคร</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">สังกัด</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">สถานะ</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="item in items" :key="item.id" class="hover:bg-gray-50">
            <td class="px-4 py-3 text-sm text-gray-900 font-medium">{{ item.full_name }}</td>
            <td class="px-4 py-3 text-sm text-gray-700">{{ item.id_card }}</td>
            <td class="px-4 py-3 text-sm text-gray-700">{{ item.volunteer_type }}</td>
            <td class="px-4 py-3 text-sm text-gray-700">{{ item.affiliation }}</td>
            <td class="px-4 py-3"><StatusBadge :status="item.status" /></td>
          </tr>
        </tbody>
      </table>
      <PaginationBar :page="page" :total="total" :per-page="perPage" @change="changePage" />
    </div>

    <EmptyState v-else title="ไม่พบข้อมูลบุคคล" description="ยังไม่มีข้อมูลอาสาสมัคร/บุคคลภายนอกในระบบ" />

    <!-- Modal เพิ่มบุคคล -->
    <div v-if="showAddForm" class="fixed inset-0 z-40 flex items-center justify-center bg-black/50" @click.self="showAddForm = false">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-lg mx-4 p-6 space-y-4">
        <h2 class="text-lg font-semibold text-gray-800">เพิ่มบุคคลใหม่</h2>
        <form @submit.prevent="addPerson" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">คำนำหน้า</label>
              <input v-model="newPerson.prefix" type="text" placeholder="นาย/นาง/นางสาว"
                     class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อ</label>
              <input v-model="newPerson.first_name" type="text" required
                     class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500" />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">นามสกุล</label>
            <input v-model="newPerson.last_name" type="text" required
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">เลขบัตรประชาชน</label>
            <input v-model="newPerson.id_card" type="text" maxlength="13" required
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">ประเภทอาสาสมัคร</label>
            <select v-model="newPerson.volunteer_type"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500">
              <option value="">-- เลือกประเภท --</option>
              <option value="อสม.">อสม.</option>
              <option value="อพปร.">อพปร.</option>
              <option value="ผู้บริจาค">ผู้บริจาค</option>
              <option value="อื่นๆ">อื่นๆ</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">สังกัด</label>
            <input v-model="newPerson.affiliation" type="text"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500" />
          </div>
          <div class="flex gap-3 pt-2">
            <button type="submit" :disabled="submitting"
                    class="px-5 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 text-sm">
              {{ submitting ? 'กำลังบันทึก...' : 'บันทึก' }}
            </button>
            <button type="button" @click="showAddForm = false"
                    class="px-5 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm">
              ยกเลิก
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useApi } from '@/composables/useApi.js'
import { useUiStore } from '@/stores/ui.js'
import StatusBadge from '@/components/StatusBadge.vue'
import EmptyState from '@/components/EmptyState.vue'
import SkeletonLoader from '@/components/SkeletonLoader.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import { Plus, Search } from 'lucide-vue-next'

const api = useApi()
const ui = useUiStore()

const items = ref([])
const loading = ref(true)
const page = ref(1)
const perPage = 20
const total = ref(0)
const searchQuery = ref('')
const showAddForm = ref(false)
const submitting = ref(false)

const newPerson = ref({
  prefix: '',
  first_name: '',
  last_name: '',
  id_card: '',
  volunteer_type: '',
  affiliation: '',
})

let searchTimer = null
async function fetchData() {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(async () => {
    loading.value = true
    try {
      const params = new URLSearchParams()
      params.set('page', page.value)
      params.set('per_page', perPage)
      if (searchQuery.value) params.set('search', searchQuery.value)
      const data = await api.get(`/direk/persons?${params}`)
      items.value = data.data || []
      total.value = data.pagination?.total || data.total || items.value.length
    } catch (err) {
      console.error('โหลดข้อมูลล้มเหลว:', err)
    } finally {
      loading.value = false
    }
  }, 300)
}

function changePage(p) {
  page.value = p
  fetchData()
}

async function addPerson() {
  submitting.value = true
  try {
    await api.post('/direk/persons', newPerson.value)
    ui.showToast('เพิ่มข้อมูลบุคคลสำเร็จ', 'success')
    showAddForm.value = false
    newPerson.value = { prefix: '', first_name: '', last_name: '', id_card: '', volunteer_type: '', affiliation: '' }
    fetchData()
  } catch (err) {
    ui.showToast(`เกิดข้อผิดพลาด: ${err.message}`, 'error')
  } finally {
    submitting.value = false
  }
}

onMounted(fetchData)
</script>
