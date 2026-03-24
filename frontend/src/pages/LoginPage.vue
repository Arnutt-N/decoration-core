<template>
  <div class="min-h-screen bg-gradient-to-br from-primary-900 via-primary-800 to-primary-950 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
      <!-- Logo -->
      <div class="text-center mb-8">
        <div class="w-20 h-20 mx-auto bg-white/10 backdrop-blur rounded-2xl flex items-center justify-center mb-4">
          <Award class="w-10 h-10 text-accent-400" />
        </div>
        <h1 class="text-2xl font-bold text-white">ระบบเครื่องราชอิสริยาภรณ์</h1>
        <p class="text-primary-200 mt-2">กระทรวงยุติธรรม — Decoration Core</p>
      </div>

      <!-- Login Form -->
      <form @submit.prevent="handleLogin" class="bg-white rounded-2xl shadow-xl p-8">
        <h2 class="text-lg font-semibold text-gray-800 mb-6">เข้าสู่ระบบ</h2>

        <div v-if="errorMsg" class="mb-4 p-3 bg-red-50 text-red-700 text-sm rounded-lg">
          {{ errorMsg }}
        </div>

        <div class="space-y-4">
          <div>
            <label class="label">ชื่อผู้ใช้</label>
            <input v-model="email" type="text" class="input" placeholder="admin" required autofocus />
          </div>
          <div>
            <label class="label">รหัสผ่าน</label>
            <input v-model="password" type="password" class="input" placeholder="admin123" required />
          </div>
        </div>

        <button type="submit" :disabled="loading"
                class="w-full mt-6 btn-primary py-3 text-base">
          {{ loading ? 'กำลังเข้าสู่ระบบ...' : 'เข้าสู่ระบบ' }}
        </button>

        <button type="button" @click="handleDemoLogin" :disabled="loading"
                class="w-full mt-3 btn-outline py-2.5">
          เข้าด้วย Demo Account
        </button>
      </form>

      <p class="text-center text-primary-300 text-xs mt-6">
        สำนักงานปลัดกระทรวงยุติธรรม &copy; 2569
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth.js'
import { Award } from 'lucide-vue-next'

const router = useRouter()
const auth = useAuthStore()

const email = ref('')
const password = ref('')
const loading = ref(false)
const errorMsg = ref('')

async function handleLogin() {
  loading.value = true
  errorMsg.value = ''
  try {
    await auth.login({ email: email.value, password: password.value })
    router.push('/dashboard')
  } catch (err) {
    errorMsg.value = err.message || 'เข้าสู่ระบบล้มเหลว'
  } finally {
    loading.value = false
  }
}

async function handleDemoLogin() {
  loading.value = true
  errorMsg.value = ''
  try {
    await auth.demoLogin()
    router.push('/dashboard')
  } catch (err) {
    errorMsg.value = err.message
  } finally {
    loading.value = false
  }
}
</script>
