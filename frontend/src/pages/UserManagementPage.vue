<template>
  <div class="space-y-6 animate-fade-in">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">จัดการผู้ใช้</h1>
        <p class="text-gray-500 mt-1">จัดการบัญชีผู้ใช้งานและสิทธิ์การเข้าถึงระบบ</p>
      </div>
      <button @click="showAddForm = true"
              class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
        <UserPlus class="w-4 h-4" />
        เพิ่มผู้ใช้
      </button>
    </div>

    <!-- Loading -->
    <SkeletonLoader v-if="loading" :rows="5" />

    <!-- Table -->
    <div v-else-if="users.length" class="card overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ชื่อผู้ใช้</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ชื่อ-นามสกุล</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">อีเมล</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">บทบาท</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">สถานะ</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">จัดการ</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50">
            <td class="px-4 py-3 text-sm text-gray-900 font-medium">{{ user.username }}</td>
            <td class="px-4 py-3 text-sm text-gray-700">{{ user.full_name }}</td>
            <td class="px-4 py-3 text-sm text-gray-500">{{ user.email }}</td>
            <td class="px-4 py-3">
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                    :class="roleClass(user.role)">
                {{ roleLabel(user.role) }}
              </span>
            </td>
            <td class="px-4 py-3"><StatusBadge :status="user.status || 'approved'" /></td>
            <td class="px-4 py-3">
              <button @click="deleteUser(user.id)" class="text-red-600 hover:text-red-800 text-sm">ลบ</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <EmptyState v-else title="ไม่มีผู้ใช้ในระบบ" description="ยังไม่มีบัญชีผู้ใช้งาน" />

    <!-- Modal เพิ่มผู้ใช้ -->
    <div v-if="showAddForm" class="fixed inset-0 z-40 flex items-center justify-center bg-black/50" @click.self="showAddForm = false">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-lg mx-4 p-6 space-y-4">
        <h2 class="text-lg font-semibold text-gray-800">เพิ่มผู้ใช้ใหม่</h2>
        <form @submit.prevent="addUser" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อผู้ใช้</label>
            <input v-model="newUser.username" type="text" required
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500" />
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อ-นามสกุล</label>
              <input v-model="newUser.full_name" type="text" required
                     class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">อีเมล</label>
              <input v-model="newUser.email" type="email" required
                     class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500" />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">รหัสผ่าน</label>
            <input v-model="newUser.password" type="password" required minlength="6"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">บทบาท</label>
            <select v-model="newUser.role"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500">
              <option value="user">ผู้ใช้ทั่วไป</option>
              <option value="operator">เจ้าหน้าที่</option>
              <option value="admin">ผู้ดูแลระบบ</option>
            </select>
          </div>
          <div class="flex gap-3 pt-2">
            <button type="submit" :disabled="submitting"
                    class="px-5 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 text-sm">
              {{ submitting ? 'กำลังบันทึก...' : 'บันทึก' }}
            </button>
            <button type="button" @click="showAddForm = false"
                    class="px-5 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm">
              ยกเลิก
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useApi } from '@/composables/useApi.js'
import { useUiStore } from '@/stores/ui.js'
import StatusBadge from '@/components/StatusBadge.vue'
import EmptyState from '@/components/EmptyState.vue'
import SkeletonLoader from '@/components/SkeletonLoader.vue'
import { UserPlus } from 'lucide-vue-next'

const api = useApi()
const ui = useUiStore()

const users = ref([])
const loading = ref(true)
const showAddForm = ref(false)
const submitting = ref(false)

const newUser = ref({
  username: '',
  full_name: '',
  email: '',
  password: '',
  role: 'user',
})

const roleMap = {
  admin: { label: 'ผู้ดูแลระบบ', class: 'bg-red-100 text-red-700' },
  operator: { label: 'เจ้าหน้าที่', class: 'bg-blue-100 text-blue-700' },
  user: { label: 'ผู้ใช้ทั่วไป', class: 'bg-gray-100 text-gray-700' },
}

function roleLabel(role) {
  return roleMap[role]?.label || role
}

function roleClass(role) {
  return roleMap[role]?.class || 'bg-gray-100 text-gray-700'
}

async function fetchUsers() {
  loading.value = true
  try {
    const data = await api.get('/users')
    users.value = data.data || []
  } catch (err) {
    console.error('โหลดข้อมูลผู้ใช้ล้มเหลว:', err)
  } finally {
    loading.value = false
  }
}

async function addUser() {
  submitting.value = true
  try {
    await api.post('/users', newUser.value)
    ui.showToast('เพิ่มผู้ใช้สำเร็จ', 'success')
    showAddForm.value = false
    newUser.value = { username: '', full_name: '', email: '', password: '', role: 'user' }
    fetchUsers()
  } catch (err) {
    ui.showToast(`เกิดข้อผิดพลาด: ${err.message}`, 'error')
  } finally {
    submitting.value = false
  }
}

async function deleteUser(id) {
  if (!confirm('ต้องการลบผู้ใช้นี้หรือไม่?')) return
  try {
    await api.del(`/users/${id}`)
    ui.showToast('ลบผู้ใช้สำเร็จ', 'success')
    users.value = users.value.filter((u) => u.id !== id)
  } catch (err) {
    ui.showToast(`ลบผู้ใช้ล้มเหลว: ${err.message}`, 'error')
  }
}

onMounted(fetchUsers)
</script>
