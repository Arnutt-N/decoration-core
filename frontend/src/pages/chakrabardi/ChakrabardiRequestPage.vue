<template>
  <div class="space-y-6 animate-fade-in">
    <div>
      <h1 class="text-2xl font-bold text-gray-900">เสนอขอเหรียญจักรพรรดิมาลา</h1>
      <p class="text-gray-500 mt-1">เลือกบุคลากรจากรายชื่อผู้มีสิทธิ์เพื่อเสนอขอพระราชทานเหรียญจักรพรรดิมาลา</p>
    </div>

    <!-- Loading -->
    <SkeletonLoader v-if="loading" :rows="5" />

    <template v-else>
      <!-- รายชื่อผู้มีสิทธิ์ -->
      <div v-if="eligibleList.length" class="card overflow-hidden">
        <h2 class="text-lg font-semibold text-gray-800 mb-3">เลือกบุคลากรที่ต้องการเสนอขอ</h2>
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-10">เลือก</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ชื่อ-นามสกุล</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ตำแหน่ง</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">อายุราชการ</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">หน่วยงาน</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="item in eligibleList" :key="item.id"
                class="hover:bg-gray-50 cursor-pointer"
                :class="{ 'bg-primary-50': selectedIds.has(item.id) }"
                @click="toggleSelect(item.id)">
              <td class="px-4 py-3">
                <input type="checkbox" :checked="selectedIds.has(item.id)"
                       @click.stop="toggleSelect(item.id)"
                       class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
              </td>
              <td class="px-4 py-3 text-sm text-gray-900 font-medium">{{ item.full_name }}</td>
              <td class="px-4 py-3 text-sm text-gray-700">{{ item.position }}</td>
              <td class="px-4 py-3 text-sm text-gray-700">{{ item.service_years }} ปี</td>
              <td class="px-4 py-3 text-sm text-gray-700">{{ item.department }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <EmptyState v-else title="ไม่พบผู้มีสิทธิ์" description="ไม่มีบุคลากรที่มีคุณสมบัติครบถ้วนสำหรับเสนอขอ" />

      <!-- Submit -->
      <div v-if="eligibleList.length" class="flex items-center gap-4">
        <button @click="submitRequest" :disabled="submitting || !selectedIds.size"
                class="inline-flex items-center gap-2 px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 transition-colors">
          <Send class="w-4 h-4" />
          {{ submitting ? 'กำลังบันทึก...' : 'เสนอขอเหรียญจักรพรรดิมาลา' }}
        </button>
        <span class="text-sm text-gray-500">เลือกแล้ว {{ selectedIds.size }} คน</span>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useApi } from '@/composables/useApi.js'
import { useUiStore } from '@/stores/ui.js'
import EmptyState from '@/components/EmptyState.vue'
import SkeletonLoader from '@/components/SkeletonLoader.vue'
import { Send } from 'lucide-vue-next'

const api = useApi()
const ui = useUiStore()
const router = useRouter()

const eligibleList = ref([])
const loading = ref(true)
const submitting = ref(false)
const selectedIds = reactive(new Set())

function toggleSelect(id) {
  if (selectedIds.has(id)) {
    selectedIds.delete(id)
  } else {
    selectedIds.add(id)
  }
}

async function submitRequest() {
  if (!selectedIds.size) return
  submitting.value = true
  const currentYear = new Date().getFullYear() + 543
  try {
    const selectedPersonnel = eligibleList.value.filter((p) => selectedIds.has(p.id))
    for (const person of selectedPersonnel) {
      await api.post('/chakrabardi', {
        personnel_id: person.personnel_id,
        service_start_date: person.hire_date,
        request_year: currentYear,
      })
    }
    ui.showToast(`เสนอขอเหรียญจักรพรรดิมาลาสำเร็จ ${selectedIds.size} คน`, 'success')
    router.push('/chakrabardi')
  } catch (err) {
    ui.showToast(`เกิดข้อผิดพลาด: ${err.message}`, 'error')
  } finally {
    submitting.value = false
  }
}

onMounted(async () => {
  try {
    const data = await api.get('/chakrabardi/eligible')
    eligibleList.value = data.data || []
  } catch (err) {
    console.error('โหลดข้อมูลล้มเหลว:', err)
  } finally {
    loading.value = false
  }
})
</script>
