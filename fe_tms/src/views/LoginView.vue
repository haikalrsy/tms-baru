<template>
  <div class="grid min-h-screen lg:grid-cols-2">

    <!-- Left — form -->
    <div class="flex items-center justify-center px-6 py-12">
      <div class="w-full max-w-sm">

        <div class="mb-8 flex items-center gap-2.5">
          <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary text-primary-foreground font-bold">LX</div>
          <span class="font-display text-lg font-semibold tracking-tight">TMS & WMS</span>
        </div>

        <h1 class="font-display text-2xl font-semibold tracking-tight">Sign in to your account</h1>
        <p class="mt-1.5 text-sm text-muted-foreground">Welcome back. Enter your credentials below.</p>

        <!-- Error -->
        <div v-if="error" class="mt-4 rounded-lg border border-destructive/30 bg-destructive/10 px-4 py-3 text-sm text-destructive">
          {{ error }}
        </div>

        <!-- Form -->
        <form @submit.prevent="onSubmit" class="mt-8 flex flex-col gap-4">
          <div class="flex flex-col gap-1.5">
            <label for="email" class="text-sm font-medium">Email</label>
            <input
              id="email" v-model="email" type="email"
              placeholder="you@company.com" autocomplete="email" required
              class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
            />
          </div>

          <div class="flex flex-col gap-1.5">
            <label for="password" class="text-sm font-medium">Password</label>
            <input
              id="password" v-model="password" type="password"
              placeholder="••••••••" autocomplete="current-password" required
              class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
            />
          </div>

          <button
            type="submit" :disabled="busy"
            class="mt-2 inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 disabled:opacity-50 disabled:pointer-events-none"
          >
            <Loader2 v-if="busy" class="mr-2 h-4 w-4 animate-spin" />
            Sign in
          </button>
        </form>

        <!-- Divider -->
        <div class="flex items-center gap-3 my-5">
          <div class="flex-1 border-t border-border" />
          <span class="text-xs text-muted-foreground">atau</span>
          <div class="flex-1 border-t border-border" />
        </div>

        <!-- Google -->
        <button
          type="button" @click="onGoogle" :disabled="googleBusy"
          class="w-full inline-flex items-center justify-center gap-2.5 rounded-md border border-input bg-background px-4 py-2 text-sm font-medium transition-colors hover:bg-muted disabled:opacity-50"
        >
          <GoogleIcon />
          {{ googleBusy ? 'Redirecting...' : 'Continue with Google' }}
        </button>

        <p class="mt-6 text-sm text-muted-foreground">
          Need an account?
          <RouterLink to="/register" class="font-medium text-primary hover:underline">Create one</RouterLink>
        </p>

      </div>
    </div>

    <!-- Right — hero -->
    <div class="relative hidden overflow-hidden bg-gradient-to-br from-primary/15 via-accent to-info/10 lg:block">
      <div class="absolute inset-0 flex flex-col justify-end p-12">
        <div class="rounded-2xl border bg-card/80 p-6 shadow-elevated backdrop-blur">
          <div class="mb-3 inline-flex items-center gap-2 rounded-full bg-success/10 px-3 py-1 text-xs font-medium text-success">
            <span class="h-1.5 w-1.5 rounded-full bg-success animate-pulse-soft" />
            Live operations
          </div>
          <h2 class="font-display text-2xl font-semibold tracking-tight">Unified TMS & WMS</h2>
          <p class="mt-2 text-sm text-muted-foreground">
            Track every order from ERP intake through warehouse picking, packing, and last-mile delivery — all in one professional dashboard.
          </p>
          <div class="mt-5 grid grid-cols-3 gap-3 text-center">
            <div v-for="s in stats" :key="s.label" class="rounded-lg border bg-surface-2/60 p-3">
              <div class="font-display text-lg font-semibold">{{ s.value }}</div>
              <div class="text-[10px] uppercase tracking-wider text-muted-foreground">{{ s.label }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { Loader2 } from 'lucide-vue-next'
import { toast } from 'vue-sonner'
import api from '@/lib/axios'
import { useAuthStore } from '@/stores/auth'
import GoogleIcon from '@/components/icon/GoogleIcon.vue'

const router     = useRouter()
const auth       = useAuthStore()
const email      = ref('')
const password   = ref('')
const busy       = ref(false)
const googleBusy = ref(false)
const error      = ref('')

const stats = [
  { label: 'Warehouses',    value: '12'  },
  { label: 'Active routes', value: '48'  },
  { label: 'On-time rate',  value: '97%' },
]

async function onSubmit() {
  error.value = ''
  busy.value  = true
  try {
    await auth.signIn(email.value, password.value)
    toast.success('Login berhasil!')
    router.push('/admin')
  } catch (err: any) {
    const code    = err.response?.data?.code
    const status  = err.response?.data?.account_status
    const message = err.response?.data?.message

    if (code === 'EMAIL_NOT_VERIFIED') {
      router.push(`/verify-email?email=${email.value}`)
      return
    }
    if (status === 'pending') {
      router.push('/pending-approval')
      return
    }

    error.value = message ?? 'Email atau password salah.'
  } finally {
    busy.value = false
  }
}

async function onGoogle() {
  googleBusy.value = true
  try {
    const { data } = await api.get('/auth/google')
    window.location.href = data.url
  } catch {
    error.value      = 'Gagal menghubungkan ke Google.'
    googleBusy.value = false
  }
}
</script>