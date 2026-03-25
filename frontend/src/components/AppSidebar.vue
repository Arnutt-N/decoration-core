<template>
  <aside
    class="w-64 bg-gray-800 h-screen fixed left-0 top-0 z-30 transform transition-transform duration-300 ease-in-out flex flex-col sidebar-scroll"
    style="overflow-y: overlay;"
    :class="open ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
  >
    <!-- Header -->
    <div class="relative flex h-16 min-h-16 items-center bg-gray-900 px-4">
      <div class="flex justify-start">
        <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center shrink-0">
          <Award class="w-4 h-4 text-white" />
        </div>
      </div>
      <span class="pointer-events-none absolute left-1/2 top-1/2 max-w-[calc(100%-6rem)] -translate-x-1/2 -translate-y-1/2 whitespace-nowrap text-center text-sm font-bold tracking-tight text-white">
        เครื่องราชอิสริยาภรณ์
      </span>
      <div class="ml-auto flex justify-end">
        <button
          @click="$emit('close')"
          class="lg:hidden text-gray-400 hover:text-white transition-colors cursor-pointer"
          aria-label="ปิดเมนู"
        >
          <X class="w-5 h-5" />
        </button>
      </div>
    </div>

    <!-- Navigation -->
    <nav class="mt-8 px-4 space-y-2 flex-1">
      <template v-for="item in menuItems" :key="item.id">
        <div v-if="item.children">
          <button
            @click="toggleSubmenu(item.id)"
            class="w-full flex items-center px-4 py-3 text-left rounded-lg transition-all duration-200 cursor-pointer"
            :class="isParentActive(item)
              ? 'bg-blue-600/10 text-blue-400 border-l-3 border-blue-400'
              : 'text-gray-300 hover:bg-gray-700/50 hover:text-white hover:translate-x-0.5 transition-all duration-150'"
          >
            <component :is="item.icon" class="w-5 h-5 mr-3" />
            <span class="text-sm font-medium flex-1">{{ item.label }}</span>
            <ChevronRight
              class="w-4 h-4 transition-transform duration-200"
              :class="{ 'rotate-90': openSubmenus.has(item.id) }"
            />
          </button>
          <div v-show="openSubmenus.has(item.id)" class="ml-8 mt-2 space-y-1">
            <RouterLink
              v-for="child in item.children"
              :key="child.id"
              :to="child.to"
              class="w-full flex items-center px-3 py-2 text-left rounded-lg transition-all duration-200"
              :class="route.path === child.to
                ? 'bg-blue-500/10 text-blue-400 font-medium'
                : 'text-gray-400 hover:bg-gray-700/50 hover:text-white'"
            >
              <span class="w-2 h-2 bg-current rounded-full mr-3 opacity-60"></span>
              <span class="text-xs font-medium">{{ child.label }}</span>
            </RouterLink>
          </div>
        </div>

        <div v-else-if="item.divider" class="border-t border-gray-700 my-3"></div>

        <RouterLink
          v-else
          :to="item.to"
          class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 cursor-pointer"
          :class="route.path === item.to
            ? 'bg-blue-600/10 text-blue-400 border-l-3 border-blue-400'
            : 'text-gray-300 hover:bg-gray-700/50 hover:text-white hover:translate-x-0.5 transition-all duration-150'"
        >
          <component :is="item.icon" class="w-5 h-5 mr-3" />
          <span class="text-sm font-medium">{{ item.label }}</span>
        </RouterLink>
      </template>
    </nav>

    <!-- User Card -->
    <div class="p-4">
      <div class="bg-gray-700 rounded-lg p-4">
        <div class="flex items-center space-x-3">
          <div class="relative">
            <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center">
              <span class="text-white font-medium">{{ auth.user?.name?.charAt(0) || 'A' }}</span>
            </div>
            <div class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-green-500 rounded-full border-2 border-gray-700"></div>
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-1.5">
              <p class="text-white text-sm font-medium truncate">{{ auth.user?.name || 'ผู้ดูแลระบบ' }}</p>
              <span class="text-[10px] px-1.5 py-0.5 bg-blue-500/20 text-blue-300 rounded font-medium shrink-0">Admin</span>
            </div>
            <p class="text-gray-400 text-xs truncate mt-0.5">{{ auth.user?.email || 'admin@decoration.moj.go.th' }}</p>
          </div>
        </div>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { reactive } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth.js'
import {
  X,
  Award,
  LayoutDashboard,
  Crown,
  Heart,
  Medal,
  ChevronRight,
  FolderOpen,
  Users,
  Settings,
} from 'lucide-vue-next'

defineProps({ open: Boolean })
defineEmits(['close'])

const route = useRoute()
const auth = useAuthStore()
const openSubmenus = reactive(new Set())

const menuItems = [
  { id: 'dashboard', label: 'Dashboard', icon: LayoutDashboard, to: '/dashboard' },
  {
    id: 'decorations',
    label: 'ช้างเผือก-มงกุฎไทย',
    icon: Crown,
    children: [
      { id: 'dec-list', label: 'รายการคำขอ', to: '/decorations' },
      { id: 'dec-new', label: 'เสนอขอใหม่', to: '/decorations/new' },
      { id: 'dec-check', label: 'ตรวจสอบสิทธิ์', to: '/decorations/check' },
      { id: 'dec-history', label: 'ประวัติการได้รับ', to: '/decorations/history' },
    ],
  },
  {
    id: 'direk',
    label: 'ดิเรกคุณาภรณ์',
    icon: Heart,
    children: [
      { id: 'direk-persons', label: 'ข้อมูลบุคคล', to: '/direk/persons' },
      { id: 'direk-requests', label: 'รายการคำขอ', to: '/direk/requests' },
      { id: 'direk-new', label: 'เสนอขอใหม่', to: '/direk/requests/new' },
      { id: 'direk-screening', label: 'กลั่นกรอง', to: '/direk/screening' },
      { id: 'direk-history', label: 'ประวัติการได้รับ', to: '/direk/history' },
      { id: 'direk-regulations', label: 'กฎระเบียบ', to: '/direk/regulations' },
    ],
  },
  {
    id: 'chakrabardi',
    label: 'เหรียญจักรพรรดิมาลา',
    icon: Medal,
    children: [
      { id: 'chakra-list', label: 'รายการคำขอ', to: '/chakrabardi' },
      { id: 'chakra-new', label: 'เสนอขอใหม่', to: '/chakrabardi/new' },
      { id: 'chakra-history', label: 'ประวัติการได้รับ', to: '/chakrabardi/history' },
    ],
  },
  { id: 'divider', divider: true },
  { id: 'files', label: 'จัดการไฟล์', icon: FolderOpen, to: '/files' },
  { id: 'users', label: 'จัดการผู้ใช้', icon: Users, to: '/users' },
  { id: 'settings', label: 'ตั้งค่าระบบ', icon: Settings, to: '/settings' },
]

function toggleSubmenu(id) {
  if (openSubmenus.has(id)) {
    openSubmenus.delete(id)
  } else {
    openSubmenus.add(id)
  }
}

function isParentActive(item) {
  return item.children?.some((c) => route.path === c.to || route.path.startsWith(c.to + '/'))
}
</script>
