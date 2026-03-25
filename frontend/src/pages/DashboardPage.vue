<template>
  <div class="p-6 space-y-6 animate-fade-in">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">ภาพรวมระบบเครื่องราชอิสริยาภรณ์</h1>
        <p class="text-gray-600 mt-1">สรุปข้อมูลสำคัญและสถานะคำขอ</p>
      </div>
      <button
        @click="fetchDashboard"
        class="btn-outline px-4 py-2 flex items-center gap-2"
      >
        <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': loading }" />
        รีเฟรช
      </button>
    </div>

    <!-- Loading -->
    <SkeletonLoader v-if="loading" type="stat-cards" />

    <!-- Stat Cards -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <StatCard
        label="จำนวนบุคลากร"
        :value="data.total_personnel || 0"
        :icon="Users"
        icon-bg-class="bg-blue-50"
        icon-class="text-blue-600"
        :sparkline="true"
        sparkline-color="bg-blue-200"
      />
      <StatCard
        label="อาสาสมัคร (ดิเรกฯ)"
        :value="data.total_volunteers || 0"
        :icon="Heart"
        icon-bg-class="bg-pink-50"
        icon-class="text-pink-600"
      />
      <StatCard
        label="คำขอรอดำเนินการ"
        :value="data.summary?.pending_requests || 0"
        :icon="Clock"
        icon-bg-class="bg-amber-50"
        icon-class="text-amber-600"
      />
      <StatCard
        label="ได้รับพระราชทานปีนี้"
        :value="data.summary?.granted_this_year || 0"
        :icon="Award"
        icon-bg-class="bg-green-50"
        icon-class="text-green-600"
      />
    </div>

    <!-- Decoration Type Summaries -->
    <div v-if="!loading" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- ช้างเผือก-มงกุฎไทย -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center gap-3 mb-4">
          <div class="p-2 bg-indigo-50 rounded-lg">
            <Crown class="w-5 h-5 text-indigo-600" />
          </div>
          <h3 class="font-semibold text-gray-900">ช้างเผือก-มงกุฎไทย</h3>
        </div>
        <div class="space-y-3">
          <div class="flex justify-between text-sm">
            <span class="text-gray-600">คำขอทั้งหมด</span>
            <span class="font-medium text-gray-900">{{ data.changpuak?.total || 0 }}</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-gray-600">รอดำเนินการ</span>
            <span class="font-medium text-amber-600">{{ data.changpuak?.pending || 0 }}</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-gray-600">ได้รับพระราชทาน</span>
            <span class="font-medium text-green-600">{{ data.changpuak?.granted || 0 }}</span>
          </div>
        </div>
        <RouterLink to="/decorations" class="mt-4 block text-center text-sm text-blue-600 hover:text-blue-700 font-medium">
          ดูทั้งหมด →
        </RouterLink>
      </div>

      <!-- ดิเรกคุณาภรณ์ -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center gap-3 mb-4">
          <div class="p-2 bg-pink-50 rounded-lg">
            <Heart class="w-5 h-5 text-pink-600" />
          </div>
          <h3 class="font-semibold text-gray-900">ดิเรกคุณาภรณ์</h3>
        </div>
        <div class="space-y-3">
          <div class="flex justify-between text-sm">
            <span class="text-gray-600">คำขอทั้งหมด</span>
            <span class="font-medium text-gray-900">{{ data.direk?.total || 0 }}</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-gray-600">รอดำเนินการ</span>
            <span class="font-medium text-amber-600">{{ data.direk?.pending || 0 }}</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-gray-600">ได้รับพระราชทาน</span>
            <span class="font-medium text-green-600">{{ data.direk?.granted || 0 }}</span>
          </div>
        </div>
        <RouterLink to="/direk/requests" class="mt-4 block text-center text-sm text-blue-600 hover:text-blue-700 font-medium">
          ดูทั้งหมด →
        </RouterLink>
      </div>

      <!-- เหรียญจักรพรรดิมาลา -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center gap-3 mb-4">
          <div class="p-2 bg-amber-50 rounded-lg">
            <Medal class="w-5 h-5 text-amber-600" />
          </div>
          <h3 class="font-semibold text-gray-900">เหรียญจักรพรรดิมาลา</h3>
        </div>
        <div class="space-y-3">
          <div class="flex justify-between text-sm">
            <span class="text-gray-600">คำขอทั้งหมด</span>
            <span class="font-medium text-gray-900">{{ data.chakrabardi?.total || 0 }}</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-gray-600">รอดำเนินการ</span>
            <span class="font-medium text-amber-600">{{ data.chakrabardi?.pending || 0 }}</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-gray-600">ได้รับพระราชทาน</span>
            <span class="font-medium text-green-600">{{ data.chakrabardi?.granted || 0 }}</span>
          </div>
        </div>
        <RouterLink to="/chakrabardi" class="mt-4 block text-center text-sm text-blue-600 hover:text-blue-700 font-medium">
          ดูทั้งหมด →
        </RouterLink>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { useApi } from '@/composables/useApi.js'
import StatCard from '@/components/StatCard.vue'
import SkeletonLoader from '@/components/SkeletonLoader.vue'
import { Users, Heart, Clock, Award, Crown, Medal, RefreshCw } from 'lucide-vue-next'

const { get } = useApi()
const loading = ref(true)
const data = ref({})

async function fetchDashboard() {
  loading.value = true
  try {
    const res = await get('/dashboard')
    data.value = res
  } catch (e) {
    console.error('Failed to load dashboard:', e)
  } finally {
    loading.value = false
  }
}

onMounted(fetchDashboard)
</script>
