import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

function readStoredString(key) {
  const value = localStorage.getItem(key)
  if (!value || value === 'undefined' || value === 'null') {
    localStorage.removeItem(key)
    return ''
  }
  return value
}

function readStoredJson(key, fallback = null) {
  const value = localStorage.getItem(key)
  if (!value || value === 'undefined' || value === 'null') {
    localStorage.removeItem(key)
    return fallback
  }
  try {
    return JSON.parse(value)
  } catch {
    localStorage.removeItem(key)
    return fallback
  }
}

export const useAuthStore = defineStore('auth', () => {
  const token = ref(readStoredString('auth_token'))
  const user = ref(readStoredJson('user'))

  const isAuthenticated = computed(() => !!token.value && isTokenValid())

  function isTokenValid() {
    if (!token.value) return false
    try {
      const payload = JSON.parse(atob(token.value.split('.')[1]))
      return payload.exp * 1000 > Date.now()
    } catch {
      return false
    }
  }

  function setAuth(data) {
    token.value = data.token
    user.value = data.user
    localStorage.setItem('auth_token', data.token)
    localStorage.setItem('user', JSON.stringify(data.user))
  }

  async function login(credentials) {
    const { useApi } = await import('@/composables/useApi.js')
    const api = useApi()
    const data = await api.post('/auth/login', credentials)
    setAuth(data)
    return data
  }

  async function demoLogin() {
    await login({ email: 'admin', password: 'admin123' })
  }

  function logout() {
    token.value = ''
    user.value = null
    localStorage.removeItem('auth_token')
    localStorage.removeItem('user')
  }

  return { token, user, isAuthenticated, isTokenValid, setAuth, login, demoLogin, logout }
})
