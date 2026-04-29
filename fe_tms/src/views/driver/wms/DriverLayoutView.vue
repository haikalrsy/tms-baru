<template>
  <div class="flex min-h-screen w-full bg-background">

    <!-- Sidebar desktop -->
    <aside :class="['hidden lg:flex flex-col border-r bg-sidebar transition-[width] duration-300', collapsed ? 'w-[72px]' : 'w-[248px]']">
      <div class="flex h-16 items-center gap-2 border-b border-sidebar-border px-4">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary text-primary-foreground font-bold">LX</div>
        <div v-if="!collapsed" class="flex flex-col leading-tight">
          <span class="font-display text-sm font-semibold tracking-tight">LogiX</span>
          <span class="text-[10px] uppercase tracking-wider text-muted-foreground">Driver Panel</span>
        </div>
      </div>

      <nav class="flex-1 overflow-y-auto px-3 py-4">
        <ul class="flex flex-col gap-1">
          <li v-for="item in navItems" :key="item.to">
            <RouterLink :to="item.to"
              :class="['flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all',
                isActive(item.to) ? 'bg-sidebar-accent text-sidebar-accent-foreground shadow-sm' : 'text-sidebar-foreground hover:bg-sidebar-accent/60',
                collapsed && 'justify-center px-2']">
              <component :is="item.icon" :size="18" class="shrink-0" />
              <span v-if="!collapsed" class="truncate">{{ item.label }}</span>
              <span v-if="!collapsed && item.badge > 0"
                class="ml-auto flex h-5 w-5 items-center justify-center rounded-full bg-destructive text-[10px] font-bold text-white">
                {{ item.badge }}
              </span>
            </RouterLink>
          </li>
        </ul>
      </nav>

      <div class="border-t border-sidebar-border p-3 space-y-2">
        <button @click="toggleOnline" :disabled="statusLoading"
          :class="['flex w-full items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium transition-all disabled:opacity-60',
            isOnline ? 'bg-green-500/15 text-green-600' : 'bg-muted text-muted-foreground hover:bg-muted/80']">
          <span :class="['h-2 w-2 rounded-full shrink-0 transition-colors', isOnline ? 'bg-green-500 animate-pulse' : 'bg-muted-foreground']" />
          <span v-if="!collapsed">{{ statusLoading ? 'Updating...' : isOnline ? 'Online · Available' : 'Offline' }}</span>
        </button>
        <button @click="collapsed = !collapsed"
          class="flex w-full items-center justify-center gap-2 rounded-lg p-2 text-xs text-muted-foreground hover:bg-sidebar-accent/60">
          <ChevronLeft :class="['h-4 w-4 transition-transform', collapsed && 'rotate-180']" />
          <span v-if="!collapsed">Collapse</span>
        </button>
      </div>
    </aside>

    <!-- Mobile drawer -->
    <Transition name="fade">
      <div v-if="mobileOpen" class="fixed inset-0 z-50 lg:hidden">
        <div class="absolute inset-0 bg-foreground/30" @click="mobileOpen = false" />
        <aside class="absolute left-0 top-0 h-full w-[260px] bg-sidebar shadow-xl">
          <div class="flex h-16 items-center gap-2 border-b border-sidebar-border px-4">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary text-primary-foreground font-bold">LX</div>
            <div class="flex flex-col leading-tight">
              <span class="font-display font-semibold">LogiX</span>
              <span class="text-[10px] uppercase tracking-wider text-muted-foreground">Driver Panel</span>
            </div>
          </div>
          <nav class="px-3 py-4">
            <ul class="flex flex-col gap-1">
              <li v-for="item in navItems" :key="item.to">
                <RouterLink :to="item.to" @click="mobileOpen = false"
                  :class="['flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all',
                    isActive(item.to) ? 'bg-sidebar-accent text-sidebar-accent-foreground' : 'text-sidebar-foreground hover:bg-sidebar-accent/60']">
                  <component :is="item.icon" :size="18" />
                  <span>{{ item.label }}</span>
                  <span v-if="item.badge > 0"
                    class="ml-auto flex h-5 w-5 items-center justify-center rounded-full bg-destructive text-[10px] font-bold text-white">
                    {{ item.badge }}
                  </span>
                </RouterLink>
              </li>
            </ul>
          </nav>
          <div class="absolute bottom-0 left-0 right-0 border-t border-sidebar-border p-3">
            <button @click="toggleOnline"
              :class="['flex w-full items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium',
                isOnline ? 'bg-green-500/15 text-green-600' : 'bg-muted text-muted-foreground']">
              <span :class="['h-2 w-2 rounded-full', isOnline ? 'bg-green-500 animate-pulse' : 'bg-muted-foreground']" />
              {{ isOnline ? 'Online · Available' : 'Offline' }}
            </button>
          </div>
        </aside>
      </div>
    </Transition>

    <!-- Main -->
    <div class="flex min-w-0 flex-1 flex-col">
      <header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b bg-surface/80 px-4 backdrop-blur-md sm:px-6">
        <button class="rounded-md p-2 hover:bg-muted lg:hidden" @click="mobileOpen = true">
          <Menu class="h-5 w-5" />
        </button>
        <div class="flex-1" />

        <!-- Online pill -->
        <button @click="toggleOnline" :disabled="statusLoading"
          :class="['hidden sm:inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-medium transition-all border disabled:opacity-60',
            isOnline ? 'bg-green-500/10 text-green-600 border-green-500/30' : 'bg-muted text-muted-foreground border-border']">
          <span :class="['h-1.5 w-1.5 rounded-full', isOnline ? 'bg-green-500 animate-pulse' : 'bg-muted-foreground']" />
          {{ statusLoading ? '...' : isOnline ? 'Online' : 'Go Online' }}
        </button>

        <button @click="themeStore.toggle()" class="rounded-md p-2 text-muted-foreground hover:bg-muted">
          <Sun v-if="themeStore.isDark" class="h-4 w-4" />
          <Moon v-else class="h-4 w-4" />
        </button>

        <button class="relative rounded-md p-2 text-muted-foreground hover:bg-muted" @click="$router.push('/driver/transfers')">
          <Bell class="h-4 w-4" />
          <span v-if="pendingCount > 0"
            class="absolute right-1.5 top-1.5 flex h-4 w-4 items-center justify-center rounded-full bg-destructive text-[9px] font-bold text-white">
            {{ pendingCount }}
          </span>
        </button>

        <div class="flex items-center gap-3 rounded-lg border bg-surface-2 px-2.5 py-1.5">
          <div class="flex h-7 w-7 items-center justify-center rounded-md bg-primary text-primary-foreground text-xs font-semibold">{{ initials }}</div>
          <div class="hidden flex-col text-xs leading-tight sm:flex">
            <span class="font-semibold text-foreground">{{ authStore.user?.name || authStore.user?.email }}</span>
            <span class="text-muted-foreground">Driver</span>
          </div>
          <button @click="handleLogout" class="inline-flex items-center justify-center rounded-md p-1.5 hover:bg-muted">
            <LogOut class="h-4 w-4" />
          </button>
        </div>
      </header>

      <main class="flex-1 overflow-x-hidden p-4 sm:p-6 lg:p-8">
        <RouterView />
      </main>
    </div>

    <!-- Incoming transfer popup -->
    <Transition name="notif">
      <div v-if="incomingTransfer"
        class="fixed bottom-6 right-6 z-[100] w-80 rounded-2xl border bg-card shadow-2xl overflow-hidden">
        <div class="bg-primary px-4 py-2 flex items-center gap-2">
          <Bell class="h-4 w-4 text-primary-foreground" />
          <span class="text-xs font-semibold text-primary-foreground">New Transfer Assignment!</span>
        </div>
        <div class="p-4 space-y-3">
          <div class="space-y-1">
            <p class="font-semibold text-sm">{{ incomingTransfer.transfer_number }}</p>
            <p class="text-xs text-muted-foreground">
              From <span class="font-medium text-foreground">{{ incomingTransfer.origin }}</span>
              → <span class="font-medium text-foreground">{{ incomingTransfer.destination }}</span>
            </p>
            <p class="text-xs text-muted-foreground">{{ incomingTransfer.items_count }} items · Status: picking</p>
          </div>
          <div class="flex gap-2">
            <button @click="goToTransfer"
              class="flex-1 py-2 rounded-lg bg-primary text-primary-foreground text-xs font-medium hover:bg-primary/90 transition-colors">
              View Transfer
            </button>
            <button @click="incomingTransfer = null"
              class="flex-1 py-2 rounded-lg border text-xs font-medium hover:bg-muted transition-colors">
              Dismiss
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, provide } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useThemeStore } from '@/stores/theme'
import { Package, Map, LogOut, Moon, Sun, Bell, ChevronLeft, Menu, ArrowRightLeft } from 'lucide-vue-next'
import api from '@/lib/axios'

const authStore  = useAuthStore()
const themeStore = useThemeStore()
const route      = useRoute()
const router     = useRouter()

const collapsed    = ref(false)
const mobileOpen   = ref(false)
const isOnline     = ref(false)
const statusLoading = ref(false)
const pendingCount = ref(0)
const incomingTransfer = ref<null | {
  id: number; transfer_number: string; origin: string; destination: string; items_count: number
}>(null)

// Provide ke child views
provide('driverOnline', isOnline)
provide('pendingCount', pendingCount)
provide('refreshPending', pollPending)

const navItems = computed(() => [
  { to: '/driver',           label: 'Dashboard',    icon: Package,        badge: 0 },
  { to: '/driver/transfers', label: 'My Transfers',  icon: ArrowRightLeft, badge: pendingCount.value },
  { to: '/driver/map',       label: 'Live Map',      icon: Map,            badge: 0 },
])

const isActive = (to: string) => {
  if (to === '/driver') return route.path === '/driver'
  return route.path.startsWith(to)
}

const initials = computed(() => {
  const name = authStore.user?.name || authStore.user?.email || 'D'
  return name.split(' ').map((s: string) => s[0]).slice(0, 2).join('').toUpperCase()
})

// ── Online toggle ─────────────────────────────────────────────────────────────
async function toggleOnline() {
  statusLoading.value = true
  const newStatus = isOnline.value ? 'off_duty' : 'available'
  try {
    await api.put('/driver/status', { status: newStatus })
    isOnline.value = !isOnline.value
    if (isOnline.value) {
      startPolling()
    } else {
      stopPolling()
      incomingTransfer.value = null
    }
  } catch (e) {
    console.error('Failed to update status', e)
  } finally {
    statusLoading.value = false
  }
}

// ── Polling: cek transfer baru setiap 10 detik ────────────────────────────────
let pollInterval: ReturnType<typeof setInterval> | null = null
const seenTransferIds = new Set<number>()

async function pollPending() {
  try {
    const res = await api.get('/driver/transfers/pending')
    const data = res.data?.data ?? []
    pendingCount.value = data.length

    // Tampilkan notif kalau ada transfer baru yang belum pernah dilihat
    if (data.length > 0) {
      const newest = data[0]
      if (!seenTransferIds.has(newest.id)) {
        seenTransferIds.add(newest.id)
        incomingTransfer.value = {
          id:              newest.id,
          transfer_number: newest.transfer_number,
          origin:          newest.origin,
          destination:     newest.destination,
          items_count:     newest.items_count,
        }
      }
    }
  } catch (e) {
    // silent — jangan ganggu UX kalau polling fail
  }
}

function startPolling() {
  pollPending() // langsung cek pertama kali
  pollInterval = setInterval(pollPending, 10_000)
}

function stopPolling() {
  if (pollInterval) { clearInterval(pollInterval); pollInterval = null }
}

function goToTransfer() {
  incomingTransfer.value = null
  router.push('/driver/transfers')
}

async function handleLogout() {
  try { await api.post('/auth/logout') } catch { /* ignore */ }
  authStore.signOut?.()
  router.push('/login')
}

// ── Init: ambil status driver dari API ────────────────────────────────────────
async function initDriverStatus() {
  try {
    const res = await api.get('/driver/status')
    const status = res.data?.availability_status ?? 'off_duty'
    isOnline.value = status === 'available' || status === 'on_trip'
    if (isOnline.value) startPolling()
  } catch { /* silent */ }
}

onMounted(initDriverStatus)
onUnmounted(stopPolling)
</script>

<style>
.notif-enter-active { transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); }
.notif-leave-active { transition: all 0.2s ease; }
.notif-enter-from   { opacity: 0; transform: translateY(20px) scale(0.95); }
.notif-leave-to     { opacity: 0; transform: translateY(10px); }
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>