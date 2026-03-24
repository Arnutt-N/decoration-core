<template>
  <div class="space-y-6 animate-fade-in">
    <div>
      <h1 class="text-2xl font-bold text-gray-900">กฎระเบียบที่เกี่ยวข้อง</h1>
      <p class="text-gray-500 mt-1">ระเบียบและหลักเกณฑ์เกี่ยวกับเครื่องราชอิสริยาภรณ์อันเป็นที่สรรเสริญยิ่งดิเรกคุณาภรณ์</p>
    </div>

    <!-- ชั้นตราดิเรกคุณาภรณ์ 7 ชั้น -->
    <div class="card">
      <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
        <Award class="w-5 h-5 text-primary-600" />
        ชั้นตราดิเรกคุณาภรณ์ 7 ชั้น
      </h2>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
        <div v-for="level in direkLevels" :key="level.order"
             class="p-3 rounded-lg border border-gray-200 hover:border-primary-200 hover:bg-primary-50/30 transition-colors">
          <div class="flex items-center gap-2 mb-1">
            <span class="w-6 h-6 rounded-full bg-primary-100 text-primary-700 text-xs flex items-center justify-center font-medium">{{ level.order }}</span>
            <span class="font-medium text-gray-800 text-sm">{{ level.name }}</span>
          </div>
          <p class="text-xs text-gray-500 ml-8">{{ level.abbreviation }}</p>
        </div>
      </div>
    </div>

    <!-- เกณฑ์การบริจาค -->
    <div class="card">
      <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
        <Banknote class="w-5 h-5 text-green-600" />
        เกณฑ์จำนวนเงินบริจาค
      </h2>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">ชั้นตรา</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">ชื่อย่อ</th>
              <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">จำนวนเงินขั้นต่ำ (บาท)</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="level in donationThresholds" :key="level.abbreviation" class="hover:bg-gray-50">
              <td class="px-4 py-2 text-gray-900 font-medium">{{ level.name }}</td>
              <td class="px-4 py-2 text-gray-600">{{ level.abbreviation }}</td>
              <td class="px-4 py-2 text-right text-gray-700">{{ formatNumber(level.amount) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- รายการระเบียบ -->
    <SkeletonLoader v-if="loading" :rows="3" />
    <div v-else-if="regulations.length" class="card">
      <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
        <FileText class="w-5 h-5 text-blue-600" />
        ระเบียบและหลักเกณฑ์
      </h2>
      <div class="divide-y divide-gray-100">
        <div v-for="reg in regulations" :key="reg.id" class="py-3 flex items-start gap-3">
          <BookOpen class="w-5 h-5 text-gray-400 shrink-0 mt-0.5" />
          <div>
            <p class="text-sm font-medium text-gray-800">{{ reg.title }}</p>
            <div class="flex gap-4 mt-1 text-xs text-gray-500">
              <span v-if="reg.reference_no">เลขที่: {{ reg.reference_no }}</span>
              <span v-if="reg.effective_date">วันที่มีผล: {{ reg.effective_date }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <EmptyState v-else-if="!loading" title="ไม่มีข้อมูลระเบียบ" description="ยังไม่มีระเบียบที่บันทึกในระบบ" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useApi } from '@/composables/useApi.js'
import EmptyState from '@/components/EmptyState.vue'
import SkeletonLoader from '@/components/SkeletonLoader.vue'
import { Award, Banknote, FileText, BookOpen } from 'lucide-vue-next'

const api = useApi()
const regulations = ref([])
const loading = ref(true)

const direkLevels = [
  { order: 1, name: 'เหรียญทองดิเรกคุณาภรณ์', abbreviation: 'ร.ท.ด.' },
  { order: 2, name: 'เหรียญเงินดิเรกคุณาภรณ์', abbreviation: 'ร.ง.ด.' },
  { order: 3, name: 'เบญจมดิเรกคุณาภรณ์', abbreviation: 'บ.ด.' },
  { order: 4, name: 'จตุตถดิเรกคุณาภรณ์', abbreviation: 'จ.ด.' },
  { order: 5, name: 'ตติยดิเรกคุณาภรณ์', abbreviation: 'ต.ด.' },
  { order: 6, name: 'ทุติยดิเรกคุณาภรณ์', abbreviation: 'ท.ด.' },
  { order: 7, name: 'ปฐมดิเรกคุณาภรณ์', abbreviation: 'ป.ด.' },
]

const donationThresholds = [
  { name: 'เหรียญทองดิเรกคุณาภรณ์', abbreviation: 'ร.ท.ด.', amount: 100000 },
  { name: 'เหรียญเงินดิเรกคุณาภรณ์', abbreviation: 'ร.ง.ด.', amount: 200000 },
  { name: 'เบญจมดิเรกคุณาภรณ์', abbreviation: 'บ.ด.', amount: 500000 },
  { name: 'จตุตถดิเรกคุณาภรณ์', abbreviation: 'จ.ด.', amount: 1500000 },
  { name: 'ตติยดิเรกคุณาภรณ์', abbreviation: 'ต.ด.', amount: 3000000 },
  { name: 'ทุติยดิเรกคุณาภรณ์', abbreviation: 'ท.ด.', amount: 6000000 },
  { name: 'ปฐมดิเรกคุณาภรณ์', abbreviation: 'ป.ด.', amount: 10000000 },
]

function formatNumber(num) {
  return num.toLocaleString('th-TH')
}

onMounted(async () => {
  try {
    const data = await api.get('/direk/regulations')
    regulations.value = data.data || []
  } catch (err) {
    console.error('โหลดระเบียบล้มเหลว:', err)
  } finally {
    loading.value = false
  }
})
</script>
