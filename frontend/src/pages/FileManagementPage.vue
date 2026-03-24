<template>
  <div class="space-y-6 animate-fade-in">
    <div>
      <h1 class="text-2xl font-bold text-gray-900">จัดการไฟล์เอกสาร</h1>
      <p class="text-gray-500 mt-1">อัปโหลดและจัดการเอกสารในระบบ</p>
    </div>

    <!-- Upload form -->
    <div class="card max-w-xl">
      <h2 class="text-lg font-semibold text-gray-800 mb-3 flex items-center gap-2">
        <Upload class="w-5 h-5 text-primary-600" />
        อัปโหลดไฟล์ใหม่
      </h2>
      <form @submit.prevent="uploadFile" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">หมวดหมู่</label>
          <select v-model="uploadForm.category"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
            <option value="">-- เลือกหมวดหมู่ --</option>
            <option value="ประกาศ">ประกาศ</option>
            <option value="คำสั่ง">คำสั่ง</option>
            <option value="แบบฟอร์ม">แบบฟอร์ม</option>
            <option value="อื่นๆ">อื่นๆ</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">เลือกไฟล์</label>
          <input ref="fileInput" type="file" @change="onFileChange"
                 class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">คำอธิบาย</label>
          <input v-model="uploadForm.description" type="text" placeholder="ระบุคำอธิบายไฟล์ (ถ้ามี)..."
                 class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary-500 focus:border-primary-500" />
        </div>
        <button type="submit" :disabled="uploading || !selectedFile || !uploadForm.category"
                class="inline-flex items-center gap-2 px-5 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 transition-colors">
          <Upload class="w-4 h-4" />
          {{ uploading ? 'กำลังอัปโหลด...' : 'อัปโหลด' }}
        </button>
      </form>
    </div>

    <!-- Loading -->
    <SkeletonLoader v-if="loading" :rows="5" />

    <!-- File list -->
    <div v-else-if="files.length" class="card overflow-hidden">
      <h2 class="text-lg font-semibold text-gray-800 mb-3">รายการไฟล์ทั้งหมด</h2>
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ชื่อไฟล์</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">หมวดหมู่</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">คำอธิบาย</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">วันที่อัปโหลด</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">จัดการ</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="file in files" :key="file.id" class="hover:bg-gray-50">
            <td class="px-4 py-3 text-sm text-gray-900 font-medium flex items-center gap-2">
              <FileIcon class="w-4 h-4 text-gray-400" />
              {{ file.filename }}
            </td>
            <td class="px-4 py-3 text-sm text-gray-700">{{ file.category }}</td>
            <td class="px-4 py-3 text-sm text-gray-500">{{ file.description || '-' }}</td>
            <td class="px-4 py-3 text-sm text-gray-500">{{ file.created_at }}</td>
            <td class="px-4 py-3">
              <div class="flex gap-2">
                <a :href="`/api/files/${file.file_id}/download`" target="_blank"
                   class="inline-flex items-center gap-1 px-3 py-1 text-xs bg-blue-50 text-blue-700 rounded-md hover:bg-blue-100 transition-colors">
                  <Download class="w-3 h-3" /> ดาวน์โหลด
                </a>
                <button @click="deleteFile(file.id)"
                        class="inline-flex items-center gap-1 px-3 py-1 text-xs bg-red-50 text-red-700 rounded-md hover:bg-red-100 transition-colors">
                  <Trash2 class="w-3 h-3" /> ลบ
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <EmptyState v-else title="ไม่มีไฟล์ในระบบ" description="ยังไม่มีไฟล์เอกสารที่อัปโหลด" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useApi } from '@/composables/useApi.js'
import { useUiStore } from '@/stores/ui.js'
import EmptyState from '@/components/EmptyState.vue'
import SkeletonLoader from '@/components/SkeletonLoader.vue'
import { Upload, Download, Trash2, File as FileIcon } from 'lucide-vue-next'

const api = useApi()
const ui = useUiStore()

const files = ref([])
const loading = ref(true)
const uploading = ref(false)
const selectedFile = ref(null)
const fileInput = ref(null)

const uploadForm = ref({
  category: '',
  description: '',
})

function onFileChange(e) {
  selectedFile.value = e.target.files[0] || null
}

async function fetchFiles() {
  loading.value = true
  try {
    const data = await api.get('/files')
    files.value = data.data || []
  } catch (err) {
    console.error('โหลดไฟล์ล้มเหลว:', err)
  } finally {
    loading.value = false
  }
}

async function uploadFile() {
  if (!selectedFile.value || !uploadForm.value.category) return
  uploading.value = true
  try {
    const formData = new FormData()
    formData.append('file', selectedFile.value)
    formData.append('category', uploadForm.value.category)
    formData.append('description', uploadForm.value.description)
    await api.upload('/files', formData)
    ui.showToast('อัปโหลดไฟล์สำเร็จ', 'success')
    uploadForm.value = { category: '', description: '' }
    selectedFile.value = null
    if (fileInput.value) fileInput.value.value = ''
    fetchFiles()
  } catch (err) {
    ui.showToast(`อัปโหลดล้มเหลว: ${err.message}`, 'error')
  } finally {
    uploading.value = false
  }
}

async function deleteFile(id) {
  if (!confirm('ต้องการลบไฟล์นี้หรือไม่?')) return
  try {
    await api.del(`/files/${id}`)
    ui.showToast('ลบไฟล์สำเร็จ', 'success')
    files.value = files.value.filter((f) => f.id !== id)
  } catch (err) {
    ui.showToast(`ลบไฟล์ล้มเหลว: ${err.message}`, 'error')
  }
}

onMounted(fetchFiles)
</script>
