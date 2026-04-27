<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-white rounded-xl shadow-sm p-8 text-center">

      <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-6">
        <Mail class="w-8 h-8 text-primary" />
      </div>

      <h1 class="text-2xl font-bold mb-2">Verifikasi Email</h1>
      <p class="text-muted-foreground text-sm mb-1">Kode 6 digit dikirim ke</p>
      <p class="text-primary font-medium text-sm mb-8">{{ email }}</p>

      <div v-if="error" class="mb-4 p-3 bg-destructive/10 border border-destructive/30 rounded-lg text-destructive text-sm">
        {{ error }}
      </div>
      <div v-if="successMsg" class="mb-4 p-3 bg-success/10 border border-success/30 rounded-lg text-success text-sm">
        {{ successMsg }}
      </div>

      <!-- OTP boxes -->
      <div class="flex justify-center gap-2 mb-8">
        <input
          v-for="(_, i) in 6" :key="i"
          :ref="el => { if (el) inputs[i] = el as HTMLInputElement }"
          v-model="digits[i]"
          type="text" inputmode="numeric" maxlength="1"
          class="w-11 h-13 text-center text-xl font-bold border-2 rounded-xl focus:outline-none focus:border-primary transition"
          :class="error ? 'border-destructive/50 bg-destructive/5' : 'border-border bg-surface-2'"
          @input="onInput(i, $event as InputEvent)"
          @keydown="onKeydown(i, $event)"
          @paste="onPaste($event)"
        />
      </div>

      <button @click="handleVerify" :disabled="loading || code.length < 6"
        class="w-full inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50 mb-4 transition-colors">
        <Loader2 v-if="loading" class="mr-2 h-4 w-4 animate-spin" />
        {{ loading ? 'Memverifikasi...' : 'Verifikasi' }}
      </button>

      <p class="text-sm text-muted-foreground">
        Tidak menerima kode?
        <button @click="handleResend" :disabled="resendCooldown > 0 || resendLoading"
          class="text-primary hover:underline font-medium disabled:text-muted-foreground disabled:no-underline">
          {{ resendCooldown > 0 ? `Kirim ulang (${resendCooldown}s)` : 'Kirim ulang' }}
        </button>
      </p>

    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Mail, Loader2 } from 'lucide-vue-next'
import api from '@/lib/axios'

const route  = useRoute()
const router = useRouter()
const email  = (route.query.email as string) ?? ''

const digits        = ref<string[]>(Array(6).fill(''))
const inputs        = ref<HTMLInputElement[]>([])
const loading       = ref(false)
const resendLoading = ref(false)
const resendCooldown= ref(0)
const error         = ref('')
const successMsg    = ref('')

const code = computed(() => digits.value.join(''))

let timer: ReturnType<typeof setInterval> | null = null

function startCooldown(seconds = 60) {
  resendCooldown.value = seconds
  timer = setInterval(() => {
    resendCooldown.value--
    if (resendCooldown.value <= 0 && timer) clearInterval(timer)
  }, 1000)
}

onMounted(() => {
  if (!email) router.push('/register')
  inputs.value[0]?.focus()
  startCooldown(60)
})

onUnmounted(() => { if (timer) clearInterval(timer) })

function onInput(i: number, e: InputEvent) {
  const val = (e.target as HTMLInputElement).value.replace(/\D/g, '')
  digits.value[i] = val
  error.value = ''
  if (val && i < 5) inputs.value[i + 1]?.focus()
}

function onKeydown(i: number, e: KeyboardEvent) {
  if (e.key === 'Backspace' && !digits.value[i] && i > 0) {
    inputs.value[i - 1]?.focus()
  }
}

function onPaste(e: ClipboardEvent) {
  const pasted = e.clipboardData?.getData('text').replace(/\D/g, '').slice(0, 6) ?? ''
  if (pasted.length === 6) {
    pasted.split('').forEach((c, i) => { digits.value[i] = c })
    inputs.value[5]?.focus()
  }
  e.preventDefault()
}

async function handleVerify() {
  if (code.value.length < 6) return
  error.value   = ''
  loading.value = true
  try {
    await api.post('/auth/verify-email', { email, code: code.value })
    router.push('/pending-approval')
  } catch (err: any) {
    error.value   = err.response?.data?.message ?? 'Kode tidak valid.'
    digits.value  = Array(6).fill('')
    inputs.value[0]?.focus()
  } finally {
    loading.value = false
  }
}

async function handleResend() {
  resendLoading.value = true
  error.value         = ''
  try {
    const { data } = await api.post('/auth/resend-verification', { email })
    successMsg.value = 'Kode baru dikirim!'
    startCooldown(data.wait_seconds ?? 60)
    setTimeout(() => { successMsg.value = '' }, 3000)
  } catch (err: any) {
    const wait = err.response?.data?.wait_seconds
    if (wait) startCooldown(wait)
    error.value = err.response?.data?.message ?? 'Gagal kirim ulang.'
  } finally {
    resendLoading.value = false
  }
}
</script>