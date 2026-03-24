<template>
  <div class="space-y-6 animate-fade-in">
    <div>
      <h1 class="text-2xl font-bold text-gray-900">ตั้งค่าระบบ</h1>
      <p class="text-gray-500 mt-1">จัดการการตั้งค่าระบบเครื่องราชอิสริยาภรณ์</p>
    </div>

    <!-- Loading -->
    <SkeletonLoader v-if="loading" :rows="5" />

    <!-- Settings -->
    <div v-else-if="settings.length" class="space-y-4 max-w-2xl">
      <div v-for="setting in settings" :key="setting.key" class="card">
        <div class="flex items-start justify-between gap-4">
          <div class="flex-1">
            <label :for="`setting-${setting.key}`" class="block text-sm font-medium text-gray-800">
              {{ setting.label || setting.key }}
            </label>
            <p v-if="setting.description" class="text-xs text-gray-500 mt-0.5">{{ setting.description }}</p>
            <div class="mt-2">
              <!-- Boolean setting -->
              <div v-if="setting.type === 'boolean'" class="flex items-center">
                <button @click="setting.value = !setting.value"
                        :class="['relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
                                  setting.value ? 'bg-primary-600' : 'bg-gray-200']">
                  <span :class="['inline-block h-4 w-4 rounded-full bg-white transition-transform',
                                  setting.value ? 'translate-x-6' : 'translate-x-1']" />
                </button>
                <span class="ml-2 text-sm text-gray-600">{{ setting.value ? 'เปิด' : 'ปิด' }}</span>
              </div>
              <!-- Select setting -->
              <select v-else-if="setting.type === 'select'" :id="`setting-${setting.key}`"
                      v-model="setting.value"
                      class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                <option v-for="opt in setting.options" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
              </select>
              <!-- Number setting -->
              <input v-else-if="setting.type === 'number'" :id="`setting-${setting.key}`"
                     v-model.number="setting.value" type="number"
                     class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500" />
              <!-- Text/default setting -->
              <input v-else :id="`setting-${setting.key}`"
                     v-model="setting.value" type="text"
                     class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500" />
            </div>
          </div>
          <button @click="saveSetting(setting)" :disabled="savingKey === setting.key"
                  class="mt-6 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 text-sm shrink-0 transition-colors">
            {{ savingKey === setting.key ? 'กำลังบันทึก...' : 'บันทึก' }}
          </button>
        </div>
      </div>
    </div>

    <EmptyState v-else title="ไม่มีการตั้งค่า" description="ยังไม่มีรายการตั้งค่าในระบบ" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useApi } from '@/composables/useApi.js'
import { useUiStore } from '@/stores/ui.js'
import EmptyState from '@/components/EmptyState.vue'
import SkeletonLoader from '@/components/SkeletonLoader.vue'

const api = useApi()
const ui = useUiStore()

const settings = ref([])
const loading = ref(true)
const savingKey = ref(null)

async function fetchSettings() {
  loading.value = true
  try {
    const data = await api.get('/settings')
    settings.value = (data.data || []).map((s) => ({
      ...s,
      type: s.type || 'text',
    }))
  } catch (err) {
    console.error('โหลดการตั้งค่าล้มเหลว:', err)
  } finally {
    loading.value = false
  }
}

async function saveSetting(setting) {
  savingKey.value = setting.key
  try {
    await api.put(`/settings/${setting.key}`, { value: setting.value })
    ui.showToast(`บันทึกการตั้งค่า "${setting.label || setting.key}" สำเร็จ`, 'success')
  } catch (err) {
    ui.showToast(`บันทึกล้มเหลว: ${err.message}`, 'error')
  } finally {
    savingKey.value = null
  }
}

onMounted(fetchSettings)
</script>
