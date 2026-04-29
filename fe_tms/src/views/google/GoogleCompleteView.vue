<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-white rounded-xl shadow-sm p-8">

      <div class="flex items-center gap-3 mb-8">
        <div class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center">
          <span class="text-primary-foreground font-bold text-sm">LX</span>
        </div>
        <span class="text-xl font-semibold">LogiX</span>
      </div>

      <h1 class="text-2xl font-bold mb-1">Satu Langkah Lagi</h1>
      <p class="text-muted-foreground text-sm mb-8">Masukkan nomor HP kamu untuk melengkapi pendaftaran.</p>

      <div v-if="error" class="mb-4 p-3 bg-destructive/10 border border-destructive/30 rounded-lg text-destructive text-sm">
        {{ error }}
      </div>

      <!-- Info akun Google -->
      <div v-if="googleUser" class="flex items-center gap-3 p-3 bg-surface-2 rounded-lg border mb-6">
        <img v-if="googleUser.avatar" :src="googleUser.avatar" class="w-10 h-10 rounded-full" />
        <div v-else class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center">
          <span class="text-primary font-semibold text-sm">{{ googleUser.name?.[0] }}</span>
        </div>
        <div>
          <p class="text-sm font-medium">{{ googleUser.name }}</p>
          <p class="text-xs text-muted-foreground">{{ googleUser.email }}</p>
        </div>
      </div>

      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium mb-1">No. HP</label>
          <input v-model="phone" type="tel" placeholder="08xxxxxxxxxx"
            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
            @keyup.enter="handleComplete" />
        </div>

        <button @click="handleComplete" :disabled="loading || !phone"
          class="w-full inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50 transition-colors">
          <Loader2 v-if="loading" class="mr-2 h-4 w-4 animate-spin" />
          {{ loading ? 'Menyimpan...' : 'Selesaikan Pendaftaran' }}
        </button>
      </div>

    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Loader2 } from 'lucide-vue-next'
import api from '@/lib/axios'

const route     = useRoute()
const router    = useRouter()
const phone     = ref('')
const loading   = ref(false)
const error     = ref('')
const googleUser= ref<any>(null)

onMounted(async () => {
  const token = route.query.token as string
  if (!token) return router.push('/login')

  localStorage.setItem('temp_token', token)

  try {
    const { data } = await api.get('/auth/me', {
      headers: { Authorization: `Bearer ${token}` },
    })
    googleUser.value = data.data
  } catch {}
})

async function handleComplete() {
  error.value   = ''
  loading.value = true
  const token   = localStorage.getItem('temp_token')
  try {
    await api.post('/auth/google/complete', { phone: phone.value }, {
      headers: { Authorization: `Bearer ${token}` },
    })
    localStorage.removeItem('temp_token')
    router.push('/pending-approval')
  } catch (err: any) {
    error.value = err.response?.data?.message ?? 'Gagal menyimpan data.'
  } finally {
    loading.value = false
  }
}
</script>