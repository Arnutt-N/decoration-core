<template>
  <div class="space-y-6 animate-fade-in">
    <div>
      <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
      <p class="text-gray-500 mt-1">ภาพรวมระบบเครื่องราชอิสริยาภรณ์ ประจำปี {{ currentYear }}</p>
    </div>

    <!-- Loading -->
    <SkeletonLoader v-if="loading" :rows="4" />

    <!-- Stats -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <StatCard label="บุคลากรทั้งหมด" :value="stats.total_personnel || 0"
                :icon="UsersIcon" iconBg="bg-blue-100" iconColor="text-blue-600" />
      <StatCard label="อาสาสมัคร (ดิเรกฯ)" :value="stats.total_volunteers || 0"
                :icon="Heart" iconBg="bg-pink-100" iconColor="text-pink-600" />
      <StatCard label="คำขอรอดำเนินการ" :value="stats.summary?.pending_requests || 0"
                :icon="Clock" iconBg="bg-amber-100" iconColor="text-amber-600" />
      <StatCard label="ได้รับพระราชทานปีนี้" :value="stats.summary?.granted_this_year || 0"
                :icon="Award" iconBg="bg-green-100" iconColor="text-green-600" />
    </div>

    <!-- สรุปแยกตามประเภท -->
    <div v-if="!loading" class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="card">
        <h3 class="font-semibold text-gray-800 mb-3 flex items-center gap-2">
          <Medal class="w-5 h-5 text-primary-600" /> ช้างเผือก-มงกุฎไทย
        </h3>
        <div class="space-y-2 text-sm">
          <div class="flex justify-between"><span class="text-gray-500">คำขอทั้งหมด</span><span class="font-medium">{{ stats.changpuak?.total || 0 }}</span></div>
          <div class="flex justify-between"><span class="text-gray-500">รอดำเนินการ</span><span class="font-medium text-amber-600">{{ stats.changpuak?.pending || 0 }}</span></div>
          <div class="flex justify-between"><span class="text-gray-500">ได้รับแล้ว</span><span class="font-medium text-green-600">{{ stats.changpuak?.granted || 0 }}</span></div>
        </div>
      </div>

      <div class="card">
        <h3 class="font-semibold text-gray-800 mb-3 flex items-center gap-2">
          <Star class="w-5 h-5 text-accent-500" /> ดิเรกคุณาภรณ์
        </h3>
        <div class="space-y-2 text-sm">
          <div class="flex justify-between"><span class="text-gray-500">คำขอทั้งหมด</span><span class="font-medium">{{ stats.direk?.total || 0 }}</span></div>
          <div class="flex justify-between"><span class="text-gray-500">รอดำเนินการ</span><span class="font-medium text-amber-600">{{ stats.direk?.pending || 0 }}</span></div>
          <div class="flex justify-between"><span class="text-gray-500">ได้รับแล้ว</span><span class="font-medium text-green-600">{{ stats.direk?.granted || 0 }}</span></div>
        </div>
      </div>

      <div class="card">
        <h3 class="font-semibold text-gray-800 mb-3 flex items-center gap-2">
          <CircleDot class="w-5 h-5 text-blue-500" /> เหรียญจักรพรรดิมาลา
        </h3>
        <div class="space-y-2 text-sm">
          <div class="flex justify-between"><span class="text-gray-500">คำขอทั้งหมด</span><span class="font-medium">{{ stats.chakrabardi?.total || 0 }}</span></div>
          <div class="flex justify-between"><span class="text-gray-500">รอดำเนินการ</span><span class="font-medium text-amber-600">{{ stats.chakrabardi?.pending || 0 }}</span></div>
          <div class="flex justify-between"><span class="text-gray-500">ได้รับแล้ว</span><span class="font-medium text-green-600">{{ stats.chakrabardi?.granted || 0 }}</span></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useApi } from '@/composables/useApi.js'
import { useUiStore } from '@/stores/ui.js'
import StatCard from '@/components/StatCard.vue'
import SkeletonLoader from '@/components/SkeletonLoader.vue'
import { Users as UsersIcon, Heart, Clock, Award, Medal, Star, CircleDot } from 'lucide-vue-next'

const api = useApi()
const ui = useUiStore()
const stats = ref({})
const loading = ref(true)
const currentYear = new Date().getFullYear() + 543

onMounted(async () => {
  try {
    const data = await api.get('/dashboard')
    stats.value = data
  } catch (err) {
    console.error('Dashboard load failed:', err)
    ui.showToast('โหลดข้อมูล Dashboard ล้มเหลว', 'error')
  } finally {
    loading.value = false
  }
})
</script>
