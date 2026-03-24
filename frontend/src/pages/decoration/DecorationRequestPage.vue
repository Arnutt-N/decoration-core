<template>
  <div class="space-y-6 animate-fade-in">
    <div>
      <h1 class="text-2xl font-bold text-gray-900">เสนอขอเครื่องราชอิสริยาภรณ์</h1>
      <p class="text-gray-500 mt-1">กรอกข้อมูลเพื่อเสนอขอพระราชทานเครื่องราชอิสริยาภรณ์</p>
    </div>

    <div class="card max-w-2xl">
      <form @submit.prevent="submitForm" class="space-y-5">
        <!-- ค้นหาบุคลากร -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">ค้นหาบุคลากร</label>
          <div class="relative">
            <Search class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" />
            <input v-model="searchQuery" @input="searchPersonnel" type="text" placeholder="พิมพ์ชื่อ หรือ รหัสบุคลากร..."
                   class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500" />
          </div>
          <!-- ผลลัพธ์การค้นหา -->
          <ul v-if="searchResults.length" class="mt-1 border border-gray-200 rounded-lg divide-y max-h-48 overflow-y-auto">
            <li v-for="person in searchResults" :key="person.id"
                @click="selectPerson(person)"
                class="px-4 py-2 text-sm hover:bg-primary-50 cursor-pointer">
              {{ person.full_name }} — {{ person.position || 'ไม่ระบุตำแหน่ง' }}
            </li>
          </ul>
          <div v-if="selectedPerson" class="mt-2 p-3 bg-primary-50 rounded-lg text-sm">
            <span class="font-medium text-primary-700">เลือกแล้ว:</span> {{ selectedPerson.full_name }}
          </div>
        </div>

        <!-- เลือกประเภท -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">เลือกประเภท</label>
          <select v-model="form.decoration_type"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
            <option value="">-- เลือกประเภท --</option>
            <option value="ช้างเผือก">ช้างเผือก</option>
            <option value="มงกุฎไทย">มงกุฎไทย</option>
          </select>
        </div>

        <!-- เลือกชั้นที่ขอ -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">เลือกชั้นที่ขอ</label>
          <select v-model="form.requested_level_abbr"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
            <option value="">-- เลือกชั้น --</option>
            <option v-for="level in filteredLevels" :key="level.level_id" :value="level.abbreviation">{{ level.abbreviation }} — {{ level.level_name }}</option>
          </select>
        </div>

        <!-- Buttons -->
        <div class="flex gap-3 pt-2">
          <button type="submit" :disabled="submitting || !isValid"
                  class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 transition-colors">
            <span v-if="submitting">กำลังบันทึก...</span>
            <span v-else>บันทึกคำขอ</span>
          </button>
          <RouterLink to="/decorations"
                      class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
            ยกเลิก
          </RouterLink>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useApi } from '@/composables/useApi.js'
import { useUiStore } from '@/stores/ui.js'
import { Search } from 'lucide-vue-next'

const api = useApi()
const ui = useUiStore()
const router = useRouter()

const searchQuery = ref('')
const searchResults = ref([])
const selectedPerson = ref(null)
const submitting = ref(false)
const levels = ref([])

const form = ref({
  decoration_type: '',
  requested_level_abbr: '',
})

const filteredLevels = computed(() =>
  form.value.decoration_type
    ? levels.value.filter(l => l.decoration_type === form.value.decoration_type)
    : levels.value
)

onMounted(async () => {
  try {
    const data = await api.get('/decorations/levels')
    levels.value = data.data || []
  } catch (err) {
    ui.showToast('โหลดชั้นตราล้มเหลว', 'error')
  }
})

const isValid = computed(() =>
  selectedPerson.value && form.value.decoration_type && form.value.requested_level_abbr
)

let searchTimer = null
function searchPersonnel() {
  clearTimeout(searchTimer)
  if (searchQuery.value.length < 2) {
    searchResults.value = []
    return
  }
  searchTimer = setTimeout(async () => {
    try {
      const data = await api.get(`/personnel?search=${encodeURIComponent(searchQuery.value)}`)
      searchResults.value = data.data || []
    } catch (err) {
      console.error('ค้นหาล้มเหลว:', err)
    }
  }, 300)
}

function selectPerson(person) {
  selectedPerson.value = person
  searchQuery.value = person.full_name
  searchResults.value = []
}

async function submitForm() {
  if (!isValid.value) return
  submitting.value = true
  try {
    await api.post('/decorations', {
      personnel_id: selectedPerson.value.personnel_id,
      decoration_type: form.value.decoration_type,
      requested_level_abbr: form.value.requested_level_abbr,
    })
    ui.showToast('บันทึกคำขอสำเร็จ', 'success')
    router.push('/decorations')
  } catch (err) {
    ui.showToast(`เกิดข้อผิดพลาด: ${err.message}`, 'error')
  } finally {
    submitting.value = false
  }
}
</script>
