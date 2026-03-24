<template>
  <div class="space-y-6 animate-fade-in">
    <div>
      <h1 class="text-2xl font-bold text-gray-900">เสนอขอดิเรกคุณาภรณ์</h1>
      <p class="text-gray-500 mt-1">ขั้นตอนการเสนอขอพระราชทานเครื่องราชอิสริยาภรณ์อันเป็นที่สรรเสริญยิ่งดิเรกคุณาภรณ์</p>
    </div>

    <!-- Stepper -->
    <div class="flex items-center gap-2 max-w-2xl">
      <div v-for="s in 3" :key="s" class="flex items-center gap-2" :class="s < 3 ? 'flex-1' : ''">
        <div :class="['w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium shrink-0',
                       step >= s ? 'bg-primary-600 text-white' : 'bg-gray-200 text-gray-500']">
          {{ s }}
        </div>
        <span class="text-sm" :class="step >= s ? 'text-primary-700 font-medium' : 'text-gray-400'">
          {{ stepLabels[s - 1] }}
        </span>
        <div v-if="s < 3" class="flex-1 h-0.5" :class="step > s ? 'bg-primary-600' : 'bg-gray-200'" />
      </div>
    </div>

    <!-- Step 1: ค้นหาบุคคล -->
    <div v-if="step === 1" class="card max-w-2xl space-y-4">
      <h2 class="text-lg font-semibold text-gray-800">ขั้นตอนที่ 1: ค้นหาบุคคล</h2>
      <div class="relative">
        <Search class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" />
        <input v-model="searchQuery" @input="searchPersons" type="text"
               placeholder="ค้นหาจากฐานข้อมูลอาสาสมัคร/บุคคลภายนอก..."
               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500" />
      </div>
      <ul v-if="searchResults.length" class="border border-gray-200 rounded-lg divide-y max-h-48 overflow-y-auto">
        <li v-for="person in searchResults" :key="person.id"
            @click="selectPerson(person)"
            class="px-4 py-2 text-sm hover:bg-primary-50 cursor-pointer">
          {{ person.full_name }} — {{ person.volunteer_type || 'ไม่ระบุ' }}
        </li>
      </ul>
      <div v-if="selectedPerson" class="p-3 bg-primary-50 rounded-lg text-sm">
        <span class="font-medium text-primary-700">เลือกแล้ว:</span> {{ selectedPerson.full_name }}
        <span class="text-primary-500 ml-2">({{ selectedPerson.volunteer_type }})</span>
      </div>
      <div class="flex justify-end">
        <button @click="goToStep2" :disabled="!selectedPerson"
                class="px-5 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 transition-colors">
          ถัดไป
        </button>
      </div>
    </div>

    <!-- Step 2: ตรวจสิทธิ์อัตโนมัติ -->
    <div v-if="step === 2" class="card max-w-2xl space-y-4">
      <h2 class="text-lg font-semibold text-gray-800">ขั้นตอนที่ 2: ตรวจสิทธิ์อัตโนมัติ</h2>

      <SkeletonLoader v-if="checkingEligibility" :rows="3" />

      <div v-else-if="eligibility" class="space-y-3">
        <div v-for="(item, idx) in eligibility.checklist" :key="idx"
             class="flex items-start gap-3 p-3 rounded-lg"
             :class="item.passed ? 'bg-green-50' : 'bg-red-50'">
          <CheckCircle v-if="item.passed" class="w-5 h-5 text-green-500 shrink-0 mt-0.5" />
          <XCircle v-else class="w-5 h-5 text-red-500 shrink-0 mt-0.5" />
          <div>
            <p class="text-sm font-medium" :class="item.passed ? 'text-green-800' : 'text-red-800'">{{ item.label }}</p>
            <p class="text-xs text-gray-500 mt-0.5">{{ item.detail }}</p>
          </div>
        </div>

        <div v-if="eligibility.eligible" class="p-3 bg-green-50 border border-green-200 rounded-lg">
          <p class="text-sm font-medium text-green-800">ผ่านเกณฑ์ — สามารถเสนอขอได้</p>
          <p v-if="eligibility.suggested_level" class="text-xs text-green-600 mt-1">ชั้นที่แนะนำ: {{ eligibility.suggested_level }}</p>
        </div>
        <div v-else class="p-3 bg-red-50 border border-red-200 rounded-lg">
          <p class="text-sm font-medium text-red-800">ไม่ผ่านเกณฑ์ — ไม่สามารถเสนอขอได้ในขณะนี้</p>
        </div>
      </div>

      <div class="flex justify-between">
        <button @click="step = 1"
                class="px-5 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
          ย้อนกลับ
        </button>
        <button @click="step = 3" :disabled="!eligibility?.eligible"
                class="px-5 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 transition-colors">
          ถัดไป
        </button>
      </div>
    </div>

    <!-- Step 3: กรอกรายละเอียด -->
    <div v-if="step === 3" class="card max-w-2xl space-y-4">
      <h2 class="text-lg font-semibold text-gray-800">ขั้นตอนที่ 3: กรอกรายละเอียดคำขอ</h2>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">ประเภทคำขอ</label>
        <select v-model="form.request_type"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
          <option value="">-- เลือกประเภท --</option>
          <option value="ผลงาน">ผลงาน</option>
          <option value="บริจาค">บริจาค</option>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">ชั้นที่ขอ</label>
        <select v-model="form.requested_level"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
          <option value="">-- เลือกชั้น --</option>
          <option v-for="level in levels" :key="level.id" :value="level.id">{{ level.name }}</option>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">รายละเอียดเพิ่มเติม</label>
        <textarea v-model="form.description" rows="4" placeholder="ระบุรายละเอียดผลงาน หรือ จำนวนเงินบริจาค..."
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary-500 focus:border-primary-500" />
      </div>

      <div v-if="form.request_type === 'บริจาค'">
        <label class="block text-sm font-medium text-gray-700 mb-1">จำนวนเงินบริจาค (บาท)</label>
        <input v-model.number="form.donation_amount" type="number" min="0" step="1000"
               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary-500 focus:border-primary-500" />
      </div>

      <div class="flex justify-between pt-2">
        <button @click="step = 2"
                class="px-5 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
          ย้อนกลับ
        </button>
        <button @click="submitRequest" :disabled="submitting || !isFormValid"
                class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 transition-colors">
          {{ submitting ? 'กำลังบันทึก...' : 'บันทึกคำขอ' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useApi } from '@/composables/useApi.js'
import { useUiStore } from '@/stores/ui.js'
import SkeletonLoader from '@/components/SkeletonLoader.vue'
import { Search, CheckCircle, XCircle } from 'lucide-vue-next'

const api = useApi()
const ui = useUiStore()
const router = useRouter()

const step = ref(1)
const stepLabels = ['ค้นหาบุคคล', 'ตรวจสิทธิ์อัตโนมัติ', 'กรอกรายละเอียด']

const searchQuery = ref('')
const searchResults = ref([])
const selectedPerson = ref(null)
const eligibility = ref(null)
const checkingEligibility = ref(false)
const levels = ref([])
const submitting = ref(false)

const form = ref({
  request_type: '',
  requested_level: '',
  description: '',
  donation_amount: null,
})

const isFormValid = computed(() =>
  form.value.request_type && form.value.requested_level
)

onMounted(async () => {
  try {
    const data = await api.get('/direk/levels')
    levels.value = data.data || []
  } catch (err) {
    console.error('โหลดชั้นตราล้มเหลว:', err)
  }
})

let searchTimer = null
function searchPersons() {
  clearTimeout(searchTimer)
  if (searchQuery.value.length < 2) {
    searchResults.value = []
    return
  }
  searchTimer = setTimeout(async () => {
    try {
      const data = await api.get(`/direk/persons?search=${encodeURIComponent(searchQuery.value)}`)
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

async function goToStep2() {
  step.value = 2
  checkingEligibility.value = true
  try {
    eligibility.value = await api.get(`/direk/requests/check/${selectedPerson.value.id}`)
  } catch (err) {
    console.error('ตรวจสิทธิ์ล้มเหลว:', err)
    eligibility.value = null
  } finally {
    checkingEligibility.value = false
  }
}

async function submitRequest() {
  if (!isFormValid.value) return
  submitting.value = true
  try {
    await api.post('/direk/requests', {
      person_id: selectedPerson.value.id,
      request_type: form.value.request_type,
      requested_level: form.value.requested_level,
      description: form.value.description,
      donation_amount: form.value.donation_amount,
    })
    ui.showToast('บันทึกคำขอดิเรกคุณาภรณ์สำเร็จ', 'success')
    router.push('/direk/requests')
  } catch (err) {
    ui.showToast(`เกิดข้อผิดพลาด: ${err.message}`, 'error')
  } finally {
    submitting.value = false
  }
}
</script>
