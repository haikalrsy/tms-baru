<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center">
    <div class="text-center">
      <Loader2 class="w-8 h-8 text-primary animate-spin mx-auto mb-4" />
      <p class="text-muted-foreground text-sm">Menyelesaikan login...</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Loader2 } from 'lucide-vue-next'
import { toast } from 'vue-sonner'
import api from '@/lib/axios'
import { useAuthStore } from '@/stores/auth'

const route  = useRoute()
const router = useRouter()
const auth   = useAuthStore()

onMounted(async () => {
  const token = route.query.token as string
  const error = route.query.error as string

  if (error || !token) {
    toast.error('Login Google gagal.')
    return router.push('/login?error=google_failed')
  }

  try {
    const { data } = await api.get('/auth/me', {
      headers: { Authorization: `Bearer ${token}` },
    })
    auth.setAuth({ token, user: data.data })
    toast.success('Login berhasil!')

    const role = data.data.role
    router.push(role === 'admin' ? '/admin' : '/driver')
  } catch {
    toast.error('Gagal mengambil data user.')
    router.push('/login?error=google_failed')
  }
})
</script>