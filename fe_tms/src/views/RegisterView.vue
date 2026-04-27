<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-white rounded-xl shadow-sm p-8">

      <div class="flex items-center gap-3 mb-8">
        <div class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center">
          <span class="text-primary-foreground font-bold text-sm">LX</span>
        </div>
        <span class="text-xl font-semibold">LogiX</span>
      </div>

      <h1 class="text-2xl font-bold mb-1">Buat Akun</h1>
      <p class="text-muted-foreground text-sm mb-8">Isi data diri kamu untuk mendaftar.</p>

      <div v-if="error" class="mb-4 p-3 bg-destructive/10 border border-destructive/30 rounded-lg text-destructive text-sm">
        {{ error }}
      </div>

      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium mb-1">Nama Lengkap</label>
          <input v-model="form.name" type="text" placeholder="John Doe"
            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Email</label>
          <input v-model="form.email" type="email" placeholder="you@example.com"
            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">No. HP</label>
          <input v-model="form.phone" type="tel" placeholder="08xxxxxxxxxx"
            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Password</label>
          <input v-model="form.password" type="password" placeholder="Min. 8 karakter"
            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Konfirmasi Password</label>
          <input v-model="form.password_confirmation" type="password" placeholder="Ulangi password"
            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" />
        </div>

        <button @click="handleRegister" :disabled="loading"
          class="w-full inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 disabled:opacity-50">
          <Loader2 v-if="loading" class="mr-2 h-4 w-4 animate-spin" />
          {{ loading ? 'Mendaftar...' : 'Daftar' }}
        </button>
      </div>

      <div class="flex items-center gap-3 my-6">
        <div class="flex-1 border-t border-border" />
        <span class="text-muted-foreground text-xs">atau</span>
        <div class="flex-1 border-t border-border" />
      </div>

      <button @click="handleGoogle" :disabled="googleLoading"
        class="w-full inline-flex items-center justify-center gap-2.5 rounded-md border border-input bg-background px-4 py-2 text-sm font-medium transition-colors hover:bg-muted disabled:opacity-50">
        <GoogleIcon />
        {{ googleLoading ? 'Redirecting...' : 'Daftar dengan Google' }}
      </button>

      <p class="text-center text-sm text-muted-foreground mt-6">
        Sudah punya akun?
        <RouterLink to="/login" class="text-primary hover:underline font-medium">Login</RouterLink>
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { Loader2 } from 'lucide-vue-next'
import api from '@/lib/axios'
import GoogleIcon from '@/components/icon/GoogleIcon.vue'

const router        = useRouter()
const loading       = ref(false)
const googleLoading = ref(false)
const error         = ref('')
const form          = reactive({
  name: '', email: '', phone: '',
  password: '', password_confirmation: '',
})

async function handleRegister() {
  error.value   = ''
  loading.value = true
  try {
    await api.post('/auth/register', form)
    router.push({ path: '/verify-email', query: { email: form.email } })
  } catch (err: any) {
    const errors = err.response?.data?.errors
    error.value  = errors
      ? Object.values(errors).flat().join(', ')
      : err.response?.data?.message ?? 'Registrasi gagal.'
  } finally {
    loading.value = false
  }
}

async function handleGoogle() {
  googleLoading.value = true
  try {
    const { data } = await api.get('/auth/google')
    window.location.href = data.url
  } catch {
    error.value         = 'Gagal menghubungkan ke Google.'
    googleLoading.value = false
  }
}
</script>