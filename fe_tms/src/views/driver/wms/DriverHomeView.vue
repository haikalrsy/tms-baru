<template>
  <div class="space-y-6">
    <!-- Online status banner -->
    <div :class="['rounded-2xl p-5 flex items-center justify-between transition-all duration-500',
      isOnline ? 'bg-green-500/10 border border-green-500/30' : 'bg-muted border border-border']">
      <div class="flex items-center gap-4">
        <div :class="['relative flex h-12 w-12 items-center justify-center rounded-full',
          isOnline ? 'bg-green-500/20' : 'bg-muted-foreground/10']">
          <Truck :class="['h-6 w-6 transition-colors', isOnline ? 'text-green-600' : 'text-muted-foreground']" />
          <span v-if="isOnline" class="absolute -right-0.5 -top-0.5 flex h-3.5 w-3.5">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75" />
            <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-green-500" />
          </span>
        </div>
        <div>
          <p class="font-semibold text-sm">{{ isOnline ? 'You are Online' : 'You are Offline' }}</p>
          <p class="text-xs text-muted-foreground">
            {{ isOnline ? 'Ready to receive transfer assignments' : 'Toggle online to receive assignments' }}
          </p>
        </div>
      </div>
      <button @click="toggleOnline" :disabled="statusLoading"
        :class="['rounded-full px-4 py-2 text-xs font-semibold transition-all disabled:opacity-60',
          isOnline ? 'bg-green-500 text-white hover:bg-green-600' : 'bg-foreground text-background hover:bg-foreground/80']">
        {{ statusLoading ? '...' : isOnline ? 'Go Offline' : 'Go Online' }}
      </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
      <div v-for="stat in stats" :key="stat.label" class="rounded-xl border bg-card p-4 space-y-2">
        <div class="flex items-center justify-between">
          <span class="text-xs text-muted-foreground font-medium">{{ stat.label }}</span>
          <component :is="stat.icon" class="h-4 w-4 text-muted-foreground" />
        </div>
        <p class="text-2xl font-bold tracking-tight">{{ stat.value }}</p>
        <p class="text-[11px] text-muted-foreground">{{ stat.sub }}</p>
      </div>
    </div>

    <!-- Active transfers -->
    <div class="rounded-2xl border bg-card overflow-hidden">
      <div class="flex items-center justify-between px-5 py-4 border-b">
        <div>
          <h2 class="font-semibold text-sm">Active Transfers</h2>
          <p class="text-xs text-muted-foreground mt-0.5">Transfers currently assigned to you</p>
        </div>
        <RouterLink to="/driver/transfers"
          class="text-xs font-medium text-primary hover:underline flex items-center gap-1">
          View all <ArrowRight class="h-3 w-3" />
        </RouterLink>
      </div>

      <div v-if="loading" class="p-8 flex items-center justify-center">
        <Loader2 class="h-6 w-6 animate-spin text-muted-foreground" />
      </div>

      <div v-else-if="activeTransfers.length === 0" class="p-10 text-center">
        <PackageCheck class="h-10 w-10 text-muted-foreground/40 mx-auto mb-3" />
        <p class="text-sm font-medium text-muted-foreground">No active transfers</p>
        <p class="text-xs text-muted-foreground/60 mt-1">
          {{ isOnline ? 'Waiting for admin to assign a transfer' : 'Go online to receive transfers' }}
        </p>
      </div>

      <ul v-else class="divide-y">
        <li v-for="transfer in activeTransfers" :key="transfer.id"
          class="flex items-center gap-4 px-5 py-4 hover:bg-muted/30 transition-colors">
          <div :class="['flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-sm', statusColor(transfer.status)]">
            {{ statusEmoji(transfer.status) }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold truncate">{{ transfer.transfer_number }}</p>
            <p class="text-xs text-muted-foreground truncate">
              {{ transfer.originWarehouse?.name ?? transfer.origin_warehouse?.name ?? '—' }}
              → {{ transfer.destinationWarehouse?.name ?? transfer.destination_warehouse?.name ?? '—' }}
            </p>
          </div>
          <div class="text-right shrink-0">
            <span :class="['inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold capitalize', statusBadge(transfer.status)]">
              {{ transfer.status.replace(/_/g, ' ') }}
            </span>
            <p class="text-[11px] text-muted-foreground mt-1">{{ transfer.items?.length ?? 0 }} items</p>
          </div>
          <RouterLink :to="`/driver/transfers`" class="p-2 rounded-lg hover:bg-muted">
            <ChevronRight class="h-4 w-4 text-muted-foreground" />
          </RouterLink>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, inject, computed, onMounted } from 'vue'
import { Truck, ArrowRight, ChevronRight, Loader2, PackageCheck, Package, CheckCircle, Clock } from 'lucide-vue-next'
import api from '@/lib/axios'

const isOnline     = inject<any>('driverOnline', ref(false))
const statusLoading = ref(false)

const loading         = ref(false)
const activeTransfers = ref<any[]>([])

const stats = computed(() => [
  {
    label: 'Picking',
    value: activeTransfers.value.filter(t => t.status === 'picking').length.toString(),
    sub: 'Sedang picking', icon: Package,
  },
  {
    label: 'Packing',
    value: activeTransfers.value.filter(t => t.status === 'packing').length.toString(),
    sub: 'Sedang packing', icon: Package,
  },
  {
    label: 'On The Way',
    value: activeTransfers.value.filter(t => t.status === 'on_the_way').length.toString(),
    sub: 'Dalam perjalanan', icon: Truck,
  },
  {
    label: 'Put Away',
    value: activeTransfers.value.filter(t => t.status === 'put_away').length.toString(),
    sub: 'Menunggu approval', icon: CheckCircle,
  },
])

// ── Toggle online (sync dengan DriverLayoutView via inject) ───────────────────
async function toggleOnline() {
  statusLoading.value = true
  const newStatus = isOnline.value ? 'off_duty' : 'available'
  try {
    await api.put('/driver/status', { status: newStatus })
    isOnline.value = !isOnline.value
  } catch (e) {
    console.error('Failed to update status', e)
  } finally {
    statusLoading.value = false
  }
}

// ── Fetch active transfers ─────────────────────────────────────────────────────
async function fetchTransfers() {
  loading.value = true
  try {
    // Ambil transfer yang masih aktif (belum completed/cancelled)
    const res = await api.get('/driver/transfers', {
      params: { status: 'picking,packing,on_the_way,put_away' }
    })
    const raw = res.data?.data ?? res.data
    activeTransfers.value = Array.isArray(raw) ? raw : (raw?.data ?? [])
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

function statusColor(status: string) {
  const map: Record<string, string> = {
    picking:    'bg-yellow-500/10 text-yellow-600',
    packing:    'bg-orange-500/10 text-orange-600',
    on_the_way: 'bg-blue-500/10 text-blue-600',
    put_away:   'bg-purple-500/10 text-purple-600',
    completed:  'bg-green-500/10 text-green-600',
    cancelled:  'bg-red-500/10 text-red-600',
  }
  return map[status] || 'bg-muted text-muted-foreground'
}

function statusEmoji(status: string) {
  const map: Record<string, string> = {
    picking: '📦', packing: '🗃️', on_the_way: '🚚', put_away: '✅', completed: '✓', cancelled: '✗',
  }
  return map[status] || '•'
}

function statusBadge(status: string) {
  const map: Record<string, string> = {
    picking:    'bg-yellow-500/10 text-yellow-700',
    packing:    'bg-orange-500/10 text-orange-700',
    on_the_way: 'bg-blue-500/10 text-blue-700',
    put_away:   'bg-purple-500/10 text-purple-700',
    completed:  'bg-green-500/10 text-green-700',
    cancelled:  'bg-red-500/10 text-red-700',
  }
  return map[status] || 'bg-muted text-muted-foreground'
}

onMounted(fetchTransfers)
</script> 