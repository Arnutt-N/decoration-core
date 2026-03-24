import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  {
    path: '/login',
    name: 'login',
    component: () => import('@/pages/LoginPage.vue'),
    meta: { requiresAuth: false },
  },
  {
    path: '/',
    component: () => import('@/layouts/AppLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      { path: '', redirect: '/dashboard' },
      {
        path: 'dashboard',
        name: 'dashboard',
        component: () => import('@/pages/DashboardPage.vue'),
      },
      // === ช้างเผือก-มงกุฎไทย ===
      {
        path: 'decorations',
        name: 'decorations',
        component: () => import('@/pages/decoration/DecorationListPage.vue'),
      },
      {
        path: 'decorations/new',
        name: 'decoration-new',
        component: () => import('@/pages/decoration/DecorationRequestPage.vue'),
      },
      {
        path: 'decorations/check',
        name: 'decoration-check',
        component: () => import('@/pages/decoration/EligibilityCheckPage.vue'),
      },
      {
        path: 'decorations/history',
        name: 'decoration-history',
        component: () => import('@/pages/decoration/DecorationHistoryPage.vue'),
      },
      // === ดิเรกคุณาภรณ์ ===
      {
        path: 'direk/persons',
        name: 'direk-persons',
        component: () => import('@/pages/direk/DirekPersonsPage.vue'),
      },
      {
        path: 'direk/requests',
        name: 'direk-requests',
        component: () => import('@/pages/direk/DirekRequestsPage.vue'),
      },
      {
        path: 'direk/requests/new',
        name: 'direk-new',
        component: () => import('@/pages/direk/DirekNewRequestPage.vue'),
      },
      {
        path: 'direk/screening',
        name: 'direk-screening',
        component: () => import('@/pages/direk/DirekScreeningPage.vue'),
      },
      {
        path: 'direk/history',
        name: 'direk-history',
        component: () => import('@/pages/direk/DirekHistoryPage.vue'),
      },
      {
        path: 'direk/regulations',
        name: 'direk-regulations',
        component: () => import('@/pages/direk/DirekRegulationsPage.vue'),
      },
      // === เหรียญจักรพรรดิมาลา ===
      {
        path: 'chakrabardi',
        name: 'chakrabardi',
        component: () => import('@/pages/chakrabardi/ChakrabardiListPage.vue'),
      },
      {
        path: 'chakrabardi/new',
        name: 'chakrabardi-new',
        component: () => import('@/pages/chakrabardi/ChakrabardiRequestPage.vue'),
      },
      {
        path: 'chakrabardi/history',
        name: 'chakrabardi-history',
        component: () => import('@/pages/chakrabardi/ChakrabardiHistoryPage.vue'),
      },
      // === ระบบสนับสนุน ===
      {
        path: 'files',
        name: 'files',
        component: () => import('@/pages/FileManagementPage.vue'),
      },
      {
        path: 'users',
        name: 'users',
        component: () => import('@/pages/UserManagementPage.vue'),
      },
      {
        path: 'settings',
        name: 'settings',
        component: () => import('@/pages/SettingsPage.vue'),
      },
    ],
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/dashboard',
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach(async (to) => {
  const { useAuthStore } = await import('@/stores/auth.js')
  const auth = useAuthStore()

  if (to.meta.requiresAuth !== false && !auth.isAuthenticated) {
    return '/login'
  }

  if (to.path === '/login' && auth.isAuthenticated) {
    return '/dashboard'
  }
})

export default router
