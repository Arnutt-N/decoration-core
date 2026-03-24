<template>
  <div class="space-y-6 animate-fade-in">
    <div>
      <h1 class="text-2xl font-bold text-gray-900">ประวัติได้รับพระราชทานดิเรกคุณาภรณ์</h1>
      <p class="text-gray-500 mt-1">ค้นหาและดูประวัติการได้รับพระราชทานเครื่องราชอิสริยาภรณ์อันเป็นที่สรรเสริญยิ่งดิเรกคุณาภรณ์</p>
    </div>

    <!-- ค้นหา -->
    <div class="card max-w-xl">
      <label class="block text-sm font-medium text-gray-700 mb-1">ค้นหาบุคคล</label>
      <div class="relative">
        <Search class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" />
        <input v-model="searchQuery" @input="searchPersons" type="text"
               placeholder="พิมพ์ชื่อ หรือ เลขบัตร..."
               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500" />
      </div>
      <ul v-if="searchResults.length" class="mt-1 border border-gray-200 rounded-lg divide-y max-h-48 overflow-y-auto">
        <li v-for="person in searchResults" :key="person.id"
            @click="fetchHistory(person)"
            class="px-4 py-2 text-sm hover:bg-primary-50 cursor-pointer">
          {{ person.full_name }} — {{ person.volunteer_type || 'ไม่ระบุ' }}
        </li>
      </ul>
    </div>

    <!-- Loading -->
    <SkeletonLoader v-if="loading" :rows="4" />

    <!-- ข้อมูลบุคคล -->
    <div v-if="selectedPerson && !loading" class="card max-w-xl">
      <h2 class="font-semibold text-gray-800 mb-2">{{ selectedPerson.full_name }}</h2>
      <p class="text-sm text-gray-500">{{ selectedPerson.volunteer_type }} | {{ selectedPerson.affiliation }}</p>
    </div>

    <!-- ตารางประวัติ -->
    <div v-if="history.length && !loading" class="card overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ลำดับ</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ชั้นตรา</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ชื่อย่อ</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ปีที่ได้รับ</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ประเภท</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="(item, idx) in history" :key="item.id" class="hover:bg-gray-50">
            <td class="px-4 py-3 text-sm text-gray-700">{{ idx + 1 }}</td>
            <td class="px-4 py-3 text-sm text-gray-900 font-medium">{{ item.level_name }}</td>
            <td class="px-4 py-3 text-sm text-gray-700">{{ item.abbreviation }}</td>
            <td class="px-4 py-3 text-sm text-gray-700">{{ item.award_year }}</td>
            <td class="px-4 py-3 text-sm text-gray-700">{{ item.request_type }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <EmptyState v-else-if="!loading && searched && !history.length" title="ไม่พบประวัติ" description="ไม่พบข้อมูลประวัติดิเรกคุณาภรณ์ของบุคคลท่านนี้" />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useApi } from '@/composables/useApi.js'
import EmptyState from '@/components/EmptyState.vue'
import SkeletonLoader from '@/components/SkeletonLoader.vue'
import { Search } from 'lucide-vue-next'

const api = useApi()
const searchQuery = ref('')
const searchResults = ref([])
const selectedPerson = ref(null)
const history = ref([])
const loading = ref(false)
const searched = ref(false)

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

async function fetchHistory(person) {
  selectedPerson.value = person
  searchQuery.value = person.full_name
  searchResults.value = []
  loading.value = true
  searched.value = true
  try {
    const data = await api.get(`/direk/awards?person_id=${person.person_id}`)
    history.value = data.data || []
  } catch (err) {
    console.error('โหลดประวัติล้มเหลว:', err)
    history.value = []
  } finally {
    loading.value = false
  }
}
</script>
