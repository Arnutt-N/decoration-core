<template>
  <aside :class="['sidebar overflow-y-auto sidebar-scroll', !open && '-translate-x-full']">
    <!-- Logo -->
    <div class="p-4 border-b border-gray-200">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-lg bg-primary-600 flex items-center justify-center">
          <Award class="w-6 h-6 text-white" />
        </div>
        <div>
          <h1 class="font-bold text-gray-900 text-sm">เครื่องราชอิสริยาภรณ์</h1>
          <p class="text-xs text-gray-500">Decoration Core</p>
        </div>
      </div>
    </div>

    <!-- เมนู -->
    <nav class="p-3 space-y-1">
      <!-- Dashboard -->
      <RouterLink to="/dashboard" class="menu-item" active-class="menu-active">
        <LayoutDashboard class="w-5 h-5" />
        <span>Dashboard</span>
      </RouterLink>

      <!-- ช้างเผือก-มงกุฎไทย -->
      <div>
        <button @click="toggleMenu('changpuak')" class="menu-item w-full justify-between">
          <span class="flex items-center gap-3">
            <Medal class="w-5 h-5" />
            <span>ช้างเผือก-มงกุฎไทย</span>
          </span>
          <ChevronDown :class="['w-4 h-4 transition-transform', openMenus.changpuak && 'rotate-180']" />
        </button>
        <div v-show="openMenus.changpuak" class="ml-8 space-y-1 mt-1">
          <RouterLink to="/decorations" class="submenu-item" active-class="menu-active">รายการคำขอ</RouterLink>
          <RouterLink to="/decorations/new" class="submenu-item" active-class="menu-active">เสนอขอใหม่</RouterLink>
          <RouterLink to="/decorations/check" class="submenu-item" active-class="menu-active">ตรวจสิทธิ์</RouterLink>
          <RouterLink to="/decorations/history" class="submenu-item" active-class="menu-active">ประวัติการได้รับ</RouterLink>
        </div>
      </div>

      <!-- ดิเรกคุณาภรณ์ -->
      <div>
        <button @click="toggleMenu('direk')" class="menu-item w-full justify-between">
          <span class="flex items-center gap-3">
            <Star class="w-5 h-5" />
            <span>ดิเรกคุณาภรณ์</span>
          </span>
          <ChevronDown :class="['w-4 h-4 transition-transform', openMenus.direk && 'rotate-180']" />
        </button>
        <div v-show="openMenus.direk" class="ml-8 space-y-1 mt-1">
          <RouterLink to="/direk/persons" class="submenu-item" active-class="menu-active">ข้อมูลอาสาสมัคร</RouterLink>
          <RouterLink to="/direk/requests" class="submenu-item" active-class="menu-active">รายการคำขอ</RouterLink>
          <RouterLink to="/direk/requests/new" class="submenu-item" active-class="menu-active">เสนอขอใหม่</RouterLink>
          <RouterLink to="/direk/screening" class="submenu-item" active-class="menu-active">ตรวจสอบคุณสมบัติ</RouterLink>
          <RouterLink to="/direk/history" class="submenu-item" active-class="menu-active">ประวัติได้รับ</RouterLink>
          <RouterLink to="/direk/regulations" class="submenu-item" active-class="menu-active">กฎระเบียบ</RouterLink>
        </div>
      </div>

      <!-- เหรียญจักรพรรดิมาลา -->
      <div>
        <button @click="toggleMenu('chakra')" class="menu-item w-full justify-between">
          <span class="flex items-center gap-3">
            <CircleDot class="w-5 h-5" />
            <span>เหรียญจักรพรรดิมาลา</span>
          </span>
          <ChevronDown :class="['w-4 h-4 transition-transform', openMenus.chakra && 'rotate-180']" />
        </button>
        <div v-show="openMenus.chakra" class="ml-8 space-y-1 mt-1">
          <RouterLink to="/chakrabardi" class="submenu-item" active-class="menu-active">รายการผู้มีสิทธิ์</RouterLink>
          <RouterLink to="/chakrabardi/new" class="submenu-item" active-class="menu-active">เสนอขอใหม่</RouterLink>
          <RouterLink to="/chakrabardi/history" class="submenu-item" active-class="menu-active">ประวัติได้รับ</RouterLink>
        </div>
      </div>

      <div class="border-t border-gray-200 my-3"></div>

      <!-- ระบบสนับสนุน -->
      <RouterLink to="/files" class="menu-item" active-class="menu-active">
        <FolderOpen class="w-5 h-5" />
        <span>จัดการไฟล์</span>
      </RouterLink>

      <RouterLink to="/users" class="menu-item" active-class="menu-active">
        <Users class="w-5 h-5" />
        <span>จัดการผู้ใช้</span>
      </RouterLink>

      <RouterLink to="/settings" class="menu-item" active-class="menu-active">
        <Settings class="w-5 h-5" />
        <span>ตั้งค่า</span>
      </RouterLink>
    </nav>
  </aside>
</template>

<script setup>
import { reactive } from 'vue'
import { RouterLink } from 'vue-router'
import {
  LayoutDashboard, Medal, Star, CircleDot,
  FolderOpen, Users, Settings as SettingsIcon,
  ChevronDown, Award
} from 'lucide-vue-next'

// ใช้ Settings icon แยกชื่อเพื่อไม่ชนกับ component name
const Settings = SettingsIcon

defineProps({ open: Boolean })

const openMenus = reactive({
  changpuak: false,
  direk: false,
  chakra: false,
})

function toggleMenu(key) {
  openMenus[key] = !openMenus[key]
}
</script>

<style scoped>
@reference "@/style.css";
.menu-item {
  @apply flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors cursor-pointer;
}
.submenu-item {
  @apply block px-3 py-2 rounded-md text-sm text-gray-500 hover:bg-gray-100 hover:text-gray-800 transition-colors;
}
.menu-active {
  @apply bg-primary-50 text-primary-700 font-medium;
}
</style>
