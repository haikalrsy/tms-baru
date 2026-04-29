<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold tracking-tight">My Transfers</h1>
        <p class="text-sm text-muted-foreground mt-0.5">All transfers assigned to you</p>
      </div>
      <button @click="fetchTransfers" :disabled="loading"
        class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs hover:bg-muted transition-colors disabled:opacity-50">
        <RefreshCw :size="13" :class="loading && 'animate-spin'" /> Refresh
      </button>
    </div>

    <!-- Filter tabs -->
    <div class="flex gap-2 overflow-x-auto pb-1">
      <button v-for="tab in tabs" :key="tab.value" @click="activeTab = tab.value"
        :class="['shrink-0 rounded-full px-4 py-1.5 text-xs font-semibold transition-all',
          activeTab === tab.value
            ? 'bg-foreground text-background'
            : 'bg-muted text-muted-foreground hover:text-foreground']">
        {{ tab.label }}
        <span v-if="tab.count > 0" class="ml-1.5 rounded-full bg-destructive text-white px-1.5 py-0.5 text-[9px]">{{ tab.count }}</span>
      </button>
    </div>

    <div v-if="loading" class="flex items-center justify-center py-16">
      <Loader2 class="h-8 w-8 animate-spin text-muted-foreground" />
    </div>

    <div v-else-if="filtered.length === 0" class="rounded-2xl border bg-card p-16 text-center">
      <PackageSearch class="h-12 w-12 text-muted-foreground/30 mx-auto mb-4" />
      <p class="font-medium text-muted-foreground">No transfers here</p>
      <p class="text-xs text-muted-foreground/60 mt-1">Check back once admin assigns a transfer</p>
    </div>

    <div v-else class="space-y-3">
      <div v-for="transfer in filtered" :key="transfer.id"
        class="rounded-2xl border bg-card overflow-hidden hover:shadow-sm transition-shadow">

        <!-- Header -->
        <div class="flex items-center justify-between px-5 py-3.5 border-b bg-muted/30">
          <div class="flex items-center gap-2">
            <span class="font-mono text-sm font-bold">{{ transfer.transfer_number }}</span>
            <span :class="['rounded-full px-2 py-0.5 text-[10px] font-semibold capitalize', statusBadge(transfer.status)]">
              {{ transfer.status.replace(/_/g, ' ') }}
            </span>
          </div>
          <span class="text-xs text-muted-foreground">{{ formatDate(transfer.created_at) }}</span>
        </div>

        <!-- Body -->
        <div class="p-5 space-y-4">
          <!-- Route -->
          <div class="flex items-center gap-3">
            <div class="flex flex-col items-center gap-1">
              <div class="h-2 w-2 rounded-full bg-primary" />
              <div class="h-8 w-px bg-border" />
              <div class="h-2 w-2 rounded-full border-2 border-primary" />
            </div>
            <div class="flex-1 space-y-3">
              <div>
                <p class="text-[10px] uppercase tracking-wide text-muted-foreground font-semibold">From</p>
                <p class="text-sm font-semibold">{{ transfer.originWarehouse?.name ?? transfer.origin_warehouse?.name ?? '—' }}</p>
              </div>
              <div>
                <p class="text-[10px] uppercase tracking-wide text-muted-foreground font-semibold">To</p>
                <p class="text-sm font-semibold">{{ transfer.destinationWarehouse?.name ?? transfer.destination_warehouse?.name ?? '—' }}</p>
              </div>
            </div>
            <div class="text-right">
              <p class="text-2xl font-bold">{{ transfer.items?.length ?? 0 }}</p>
              <p class="text-[11px] text-muted-foreground">items</p>
            </div>
          </div>

          <!-- Items preview -->
          <div v-if="transfer.items?.length" class="rounded-lg bg-muted/50 p-3 space-y-1.5">
            <p class="text-[10px] uppercase tracking-wide text-muted-foreground font-semibold mb-2">Items</p>
            <div v-for="item in transfer.items.slice(0, 3)" :key="item.id" class="flex justify-between text-xs">
              <span class="font-medium">{{ item.item_name ?? item.item?.name ?? '—' }}</span>
              <span class="text-muted-foreground">{{ item.quantity ?? item.qty ?? 0 }} {{ item.unit ?? item.uom ?? 'pcs' }}</span>
            </div>
            <p v-if="transfer.items.length > 3" class="text-[11px] text-muted-foreground">
              +{{ transfer.items.length - 3 }} more items
            </p>
          </div>

          <p v-if="transfer.notes" class="text-xs text-muted-foreground italic border-l-2 border-border pl-3">{{ transfer.notes }}</p>

          <!-- Actions — sesuai flow: picking → packing → on_the_way → put_away -->
          <div class="flex gap-2 pt-1">

            <!-- Picking: selesai picking → packing -->
            <template v-if="transfer.status === 'picking'">
              <button @click="advanceStatus(transfer)"
                :disabled="actionLoading === transfer.id"
                class="flex-1 flex items-center justify-center gap-2 rounded-xl bg-yellow-500 text-white py-2.5 text-xs font-semibold hover:bg-yellow-600 transition-colors disabled:opacity-50">
                <Package class="h-3.5 w-3.5" />
                {{ actionLoading === transfer.id ? 'Updating...' : 'Done Picking → Start Packing' }}
              </button>
            </template>

            <!-- Packing: selesai packing → on_the_way -->
            <template v-else-if="transfer.status === 'packing'">
              <button @click="advanceStatus(transfer)"
                :disabled="actionLoading === transfer.id"
                class="flex-1 flex items-center justify-center gap-2 rounded-xl bg-orange-500 text-white py-2.5 text-xs font-semibold hover:bg-orange-600 transition-colors disabled:opacity-50">
                <Truck class="h-3.5 w-3.5" />
                {{ actionLoading === transfer.id ? 'Updating...' : 'Done Packing → On The Way' }}
              </button>
            </template>

            <!-- On The Way: sampai tujuan → put_away -->
            <template v-else-if="transfer.status === 'on_the_way'">
              <button @click="advanceStatus(transfer)"
                :disabled="actionLoading === transfer.id"
                class="flex-1 flex items-center justify-center gap-2 rounded-xl bg-blue-500 text-white py-2.5 text-xs font-semibold hover:bg-blue-600 transition-colors disabled:opacity-50">
                <CheckCircle class="h-3.5 w-3.5" />
                {{ actionLoading === transfer.id ? 'Updating...' : 'Arrived → Put Away' }}
              </button>
            </template>

            <!-- Put Away: menunggu admin -->
            <template v-else-if="transfer.status === 'put_away'">
              <div class="flex-1 flex items-center justify-center gap-2 rounded-xl bg-purple-500/10 text-purple-700 py-2.5 text-xs font-semibold border border-purple-500/20">
                <Clock class="h-3.5 w-3.5" />
                Waiting for admin approval
              </div>
            </template>

            <!-- Completed / Cancelled -->
            <template v-else>
              <div :class="['flex-1 flex items-center justify-center gap-1.5 rounded-xl py-2.5 text-xs',
                transfer.status === 'completed' ? 'bg-green-500/10 text-green-700' : 'bg-muted text-muted-foreground']">
                <component :is="transfer.status === 'completed' ? CheckCircle : XCircle" class="h-3.5 w-3.5" />
                {{ transfer.status === 'completed' ? 'Completed' : 'Cancelled' }}
              </div>
            </template>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, inject, computed, onMounted } from 'vue'
import { Loader2, Truck, CheckCircle, XCircle, PackageSearch, Package, Clock, RefreshCw } from 'lucide-vue-next'
import api from '@/lib/axios'

const isOnline     = inject<any>('driverOnline', ref(false))
const pendingCount = inject<any>('pendingCount', ref(0))

const loading     = ref(false)
const actionLoading = ref<number | null>(null)
const transfers   = ref<any[]>([])
const activeTab   = ref('active')

const tabs = computed(() => [
  { label: 'Active',    value: 'active',    count: transfers.value.filter(t => ['picking','packing','on_the_way','put_away'].includes(t.status)).length },
  { label: 'Picking',   value: 'picking',   count: transfers.value.filter(t => t.status === 'picking').length },
  { label: 'Packing',   value: 'packing',   count: transfers.value.filter(t => t.status === 'packing').length },
  { label: 'On The Way',value: 'on_the_way',count: transfers.value.filter(t => t.status === 'on_the_way').length },
  { label: 'Put Away',  value: 'put_away',  count: transfers.value.filter(t => t.status === 'put_away').length },
  { label: 'Completed', value: 'completed', count: 0 },
])

const filtered = computed(() => {
  if (activeTab.value === 'active') {
    return transfers.value.filter(t => ['picking','packing','on_the_way','put_away'].includes(t.status))
  }
  return transfers.value.filter(t => t.status === activeTab.value)
})

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

function formatDate(date: string) {
  if (!date) return '—'
  return new Date(date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

// ── Advance status: picking→packing→on_the_way→put_away ──────────────────────
async function advanceStatus(transfer: any) {
  actionLoading.value = transfer.id
  try {
    // POST /api/driver/transfers/{id}/status
    await api.post(`/driver/transfers/${transfer.id}/status`)
    // Refresh list
    await fetchTransfers()
  } catch (e: any) {
    alert(e.response?.data?.message ?? 'Failed to update status')
  } finally {
    actionLoading.value = null
  }
}

async function fetchTransfers() {
  loading.value = true
  try {
    const res = await api.get('/driver/transfers')
    const raw = res.data?.data ?? res.data
    transfers.value = Array.isArray(raw) ? raw : (raw?.data ?? [])
    pendingCount.value = transfers.value.filter((t: any) =>
      ['picking','packing','on_the_way','put_away'].includes(t.status)
    ).length
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

onMounted(fetchTransfers)
</script>

<style>
.modal-enter-active { transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
.modal-leave-active { transition: all 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; transform: translateY(20px); }
</style>