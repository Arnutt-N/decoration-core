<template>
  <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-6 sticky top-0 z-20">
    <div class="flex items-center gap-4">
      <button @click="$emit('toggle-sidebar')" class="p-2 rounded-lg hover:bg-gray-100">
        <Menu class="w-5 h-5 text-gray-600" />
      </button>
      <h2 class="text-lg font-semibold text-gray-800">{{ pageTitle }}</h2>
    </div>

    <div class="flex items-center gap-4">
      <span class="text-sm text-gray-500">{{ user?.name || 'ผู้ใช้' }}</span>
      <button @click="handleLogout" class="flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50 rounded-lg transition-colors">
        <LogOut class="w-4 h-4" />
        <span>ออกจากระบบ</span>
      </button>
    </div>
  </header>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth.js'
import { Menu, LogOut } from 'lucide-vue-next'

defineEmits(['toggle-sidebar'])

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const user = computed(() => auth.user)

const pageTitles = {
  'dashboard': 'Dashboard',
  'decorations': 'เครื่องราชฯ ช้างเผือก-มงกุฎไทย',
  'decoration-new': 'เสนอขอเครื่องราชฯ',
  'decoration-check': 'ตรวจสิทธิ์',
  'decoration-history': 'ประวัติการได้รับ',
  'direk-persons': 'ข้อมูลอาสาสมัคร',
  'direk-requests': 'คำขอดิเรกคุณาภรณ์',
  'direk-new': 'เสนอขอดิเรกคุณาภรณ์',
  'direk-screening': 'ตรวจสอบคุณสมบัติ',
  'direk-history': 'ประวัติได้รับดิเรกฯ',
  'direk-regulations': 'กฎระเบียบ',
  'chakrabardi': 'เหรียญจักรพรรดิมาลา',
  'chakrabardi-new': 'เสนอขอเหรียญจักรพรรดิมาลา',
  'chakrabardi-history': 'ประวัติได้รับเหรียญฯ',
  'files': 'จัดการไฟล์',
  'users': 'จัดการผู้ใช้',
  'settings': 'ตั้งค่าระบบ',
}

const pageTitle = computed(() => pageTitles[route.name] || 'ระบบเครื่องราชอิสริยาภรณ์')

function handleLogout() {
  auth.logout()
  router.push('/login')
}
</script>
