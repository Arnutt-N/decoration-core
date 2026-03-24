<template>
  <div class="space-y-6 animate-fade-in">
    <div>
      <h1 class="text-2xl font-bold text-gray-900">ตรวจสิทธิ์เครื่องราชอิสริยาภรณ์</h1>
      <p class="text-gray-500 mt-1">ตรวจสอบสิทธิ์การขอพระราชทานเครื่องราชอิสริยาภรณ์ของบุคลากร</p>
    </div>

    <!-- ค้นหาบุคลากร -->
    <div class="card max-w-xl">
      <label class="block text-sm font-medium text-gray-700 mb-1">ค้นหาบุคลากร</label>
      <div class="flex gap-2">
        <div class="relative flex-1">
          <Search class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" />
          <input v-model="searchQuery" @input="searchPersonnel" type="text"
                 placeholder="พิมพ์ชื่อ หรือ รหัสบุคลากร..."
                 class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500" />
        </div>
      </div>
      <ul v-if="searchResults.length" class="mt-1 border border-gray-200 rounded-lg divide-y max-h-48 overflow-y-auto">
        <li v-for="person in searchResults" :key="person.id"
            @click="checkEligibility(person)"
            class="px-4 py-2 text-sm hover:bg-primary-50 cursor-pointer">
          {{ person.full_name }} — {{ person.position || 'ไม่ระบุตำแหน่ง' }}
        </li>
      </ul>
    </div>

    <!-- Loading -->
    <SkeletonLoader v-if="loading" :rows="3" />

    <!-- ผลการตรวจสอบ -->
    <div v-else-if="result" class="space-y-4 max-w-2xl">
      <div class="card">
        <h2 class="text-lg font-semibold text-gray-800 mb-3 flex items-center gap-2">
          <UserCheck class="w-5 h-5 text-primary-600" />
          ข้อมูลบุคลากร
        </h2>
        <div class="grid grid-cols-2 gap-3 text-sm">
          <div><span class="text-gray-500">ชื่อ-นามสกุล:</span> <span class="font-medium">{{ result.full_name }}</span></div>
          <div><span class="text-gray-500">ตำแหน่ง:</span> <span class="font-medium">{{ result.position }}</span></div>
          <div><span class="text-gray-500">สังกัด:</span> <span class="font-medium">{{ result.department }}</span></div>
          <div><span class="text-gray-500">อายุราชการ:</span> <span class="font-medium">{{ result.service_years }} ปี</span></div>
        </div>
      </div>

      <div class="card">
        <h2 class="text-lg font-semibold text-gray-800 mb-3 flex items-center gap-2">
          <ClipboardCheck class="w-5 h-5 text-green-600" />
          ผลการตรวจสิทธิ์
        </h2>
        <div class="space-y-3">
          <div v-for="(item, idx) in result.eligibility_items" :key="idx"
               class="flex items-start gap-3 p-3 rounded-lg"
               :class="item.eligible ? 'bg-green-50' : 'bg-red-50'">
            <CheckCircle v-if="item.eligible" class="w-5 h-5 text-green-500 shrink-0 mt-0.5" />
            <XCircle v-else class="w-5 h-5 text-red-500 shrink-0 mt-0.5" />
            <div>
              <p class="text-sm font-medium" :class="item.eligible ? 'text-green-800' : 'text-red-800'">{{ item.label }}</p>
              <p class="text-xs text-gray-500 mt-0.5">{{ item.detail }}</p>
            </div>
          </div>
        </div>
      </div>

      <div v-if="result.next_eligible_level" class="card bg-primary-50 border-primary-200">
        <h3 class="font-semibold text-primary-800 mb-1">ชั้นที่มีสิทธิ์ขอถัดไป</h3>
        <p class="text-sm text-primary-700">{{ result.next_eligible_level }}</p>
      </div>
    </div>

    <EmptyState v-else-if="!loading && searched" title="ไม่พบข้อมูล" description="กรุณาค้นหาบุคลากรเพื่อตรวจสอบสิทธิ์" />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useApi } from '@/composables/useApi.js'
import EmptyState from '@/components/EmptyState.vue'
import SkeletonLoader from '@/components/SkeletonLoader.vue'
import { Search, UserCheck, ClipboardCheck, CheckCircle, XCircle } from 'lucide-vue-next'

const api = useApi()
const searchQuery = ref('')
const searchResults = ref([])
const result = ref(null)
const loading = ref(false)
const searched = ref(false)

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

async function checkEligibility(person) {
  searchQuery.value = person.full_name
  searchResults.value = []
  loading.value = true
  searched.value = true
  try {
    result.value = await api.get(`/decorations/check/${person.id}`)
  } catch (err) {
    console.error('ตรวจสอบสิทธิ์ล้มเหลว:', err)
    result.value = null
  } finally {
    loading.value = false
  }
}
</script>
