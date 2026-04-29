<template>
  <div v-if="authStore.loading || !authStore.user || !authStore.profile"
    class="flex min-h-screen items-center justify-center bg-background">
    <div class="h-10 w-10 animate-spin rounded-full border-2 border-primary border-t-transparent" />
  </div>

  <template v-else>
     <DashboardLayout role="driver">
    <div class="flex flex-col gap-6">
      <div>
        <h1 class="font-display text-2xl font-semibold tracking-tight">My Transfers</h1>
        <p class="text-sm text-muted-foreground">Your active and recent transfer assignments.</p>
      </div>

      <div v-if="loading" class="flex items-center justify-center py-16">
        <div class="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" />
      </div>

      <!-- Active transfer with map -->
      <div v-else-if="activeTransfer" class="rounded-xl border bg-card shadow-sm overflow-hidden">
        <div class="flex flex-col gap-3 border-b p-5 sm:flex-row sm:items-start sm:justify-between">
          <div class="flex flex-col gap-1">
            <span :class="['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize w-fit', statusBadge(activeTransfer.status)]">
              {{ activeTransfer.status.replace(/_/g, ' ') }}
            </span>
            <h2 class="font-display text-lg font-semibold mt-1">{{ activeTransfer.transfer_number }}</h2>
            <span class="text-xs text-muted-foreground">{{ activeTransfer.salesOrder?.so_number ?? '—' }}</span>
          </div>
          <div class="text-right text-sm space-y-1">
            <div class="flex items-center justify-end gap-1.5 text-muted-foreground text-xs">
              <MapPin :size="13" />
              {{ activeTransfer.originWarehouse?.name ?? '—' }} → {{ activeTransfer.destinationWarehouse?.name ?? '—' }}
            </div>
            <div class="text-xs text-muted-foreground">{{ activeTransfer.items?.length ?? 0 }} items</div>
          </div>
        </div>

        <!-- Mini map -->
        <div class="h-[280px] relative">
          <div ref="miniMapEl" class="h-full w-full" />
          <div v-if="!mapReady" class="absolute inset-0 flex items-center justify-center bg-muted/50 z-10">
            <div class="h-6 w-6 animate-spin rounded-full border-2 border-primary border-t-transparent" />
          </div>
        </div>

        <!-- Actions -->
        <div class="flex flex-wrap gap-2 border-t p-4">
          <template v-if="activeTransfer.status === 'picking'">
            <button @click="advanceStatus" :disabled="actionLoading"
              class="flex items-center gap-2 rounded-lg bg-yellow-500 px-4 py-2 text-sm font-medium text-white hover:bg-yellow-600 transition-colors disabled:opacity-50">
              <Package :size="15" />{{ actionLoading ? 'Updating...' : 'Done Picking → Packing' }}
            </button>
          </template>
          <template v-else-if="activeTransfer.status === 'packing'">
            <button @click="advanceStatus" :disabled="actionLoading"
              class="flex items-center gap-2 rounded-lg bg-orange-500 px-4 py-2 text-sm font-medium text-white hover:bg-orange-600 transition-colors disabled:opacity-50">
              <Truck :size="15" />{{ actionLoading ? 'Updating...' : 'Done Packing → On The Way' }}
            </button>
          </template>
          <template v-else-if="activeTransfer.status === 'on_the_way'">
            <button @click="advanceStatus" :disabled="actionLoading"
              class="flex items-center gap-2 rounded-lg bg-blue-500 px-4 py-2 text-sm font-medium text-white hover:bg-blue-600 transition-colors disabled:opacity-50">
              <CheckCircle2 :size="15" />{{ actionLoading ? 'Updating...' : 'Arrived → Put Away' }}
            </button>
          </template>
          <template v-else-if="activeTransfer.status === 'put_away'">
            <div class="flex items-center gap-2 rounded-lg bg-purple-500/10 border border-purple-500/20 px-4 py-2 text-sm font-medium text-purple-700">
              <Clock :size="15" />Waiting for admin approval
            </div>
          </template>
        </div>
      </div>

      <!-- No active transfer -->
      <div v-else-if="!loading" class="rounded-xl border bg-card p-10 text-center">
        <PackageCheck class="h-10 w-10 text-muted-foreground/30 mx-auto mb-3" />
        <p class="font-medium text-muted-foreground">No active transfer</p>
        <p class="text-xs text-muted-foreground/60 mt-1">Admin will assign a transfer to you</p>
      </div>

      <!-- All transfers list -->
      <div v-if="transfers.length > 0" class="flex flex-col gap-3">
        <h3 class="text-sm font-semibold uppercase tracking-wider text-muted-foreground">All Transfers</h3>
        <div v-for="t in transfers" :key="t.id"
          class="flex items-center gap-3 rounded-xl border bg-card p-4 hover:shadow-sm transition-all">
          <div :class="['flex h-10 w-10 items-center justify-center rounded-lg text-sm shrink-0', statusColor(t.status)]">
            {{ statusEmoji(t.status) }}
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="font-mono font-semibold text-sm">{{ t.transfer_number }}</span>
              <span :class="['inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold capitalize', statusBadge(t.status)]">
                {{ t.status.replace(/_/g, ' ') }}
              </span>
            </div>
            <div class="text-xs text-muted-foreground truncate mt-0.5">
              {{ t.originWarehouse?.name ?? '—' }} → {{ t.destinationWarehouse?.name ?? '—' }}
            </div>
          </div>
          <div class="text-right shrink-0">
            <p class="text-xs text-muted-foreground">{{ t.items?.length ?? 0 }} items</p>
            <p class="text-[11px] text-muted-foreground mt-0.5">{{ formatDate(t.created_at) }}</p>
          </div>
        </div>
      </div>
    </div>
     </DashboardLayout>
  </template>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { storeToRefs } from 'pinia'
import { MapPin, Package, Truck, CheckCircle2, Clock, PackageCheck } from 'lucide-vue-next'
import api from '@/lib/axios'
import DashboardLayout from '@/components/DashboardLayout.vue'

const router    = useRouter()
const authStore = useAuthStore()
const { user, loading: authLoading, profile, roles } = storeToRefs(authStore)

const loading       = ref(false)
const actionLoading = ref(false)
const transfers     = ref<any[]>([])
const miniMapEl     = ref<HTMLDivElement>()
const mapReady      = ref(false)

let map: any        = null
let L: any          = null
let routeLayer: any = null

watch([user, authLoading, profile, roles], () => {
  if (authLoading.value) return
  if (!user.value) { router.push('/login'); return }
  if (profile.value && profile.value.status !== 'approved') { router.push('/pending-approval'); return }
  if (!roles.value.includes('driver')) {
    router.push(roles.value.includes('admin') ? '/admin' : '/login')
  }
})

const activeTransfer = computed(() =>
  transfers.value.find(t => ['picking', 'packing', 'on_the_way', 'put_away'].includes(t.status)) ?? null
)

async function fetchTransfers() {
  loading.value = true
  try {
    const res = await api.get('/driver/transfers')
    const raw = res.data?.data ?? res.data
    transfers.value = Array.isArray(raw) ? raw : (raw?.data ?? [])
  } catch (e) {
    console.error('fetchTransfers error:', e)
  } finally {
    loading.value = false
  }
}

async function advanceStatus() {
  if (!activeTransfer.value) return
  actionLoading.value = true
  try {
    await api.post(`/driver/transfers/${activeTransfer.value.id}/status`)
    await fetchTransfers()
    if (activeTransfer.value && map) renderRoute(activeTransfer.value)
  } catch (e: any) {
    alert(e.response?.data?.message ?? 'Failed to update status')
  } finally {
    actionLoading.value = false
  }
}

async function initMap() {
  if (!miniMapEl.value) return
  L = await import('leaflet')
  await import('leaflet/dist/leaflet.css')

  map = L.map(miniMapEl.value, {
    center: [-6.2088, 106.8456], zoom: 11,
    zoomControl: false, attributionControl: false,
  })
  L.control.zoom({ position: 'bottomright' }).addTo(map)
  L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
    subdomains: 'abcd', maxZoom: 19,
  }).addTo(map)

  mapReady.value = true
  if (activeTransfer.value) renderRoute(activeTransfer.value)
}

function renderRoute(transfer: any) {
  if (!L || !map) return
  if (routeLayer) { routeLayer.remove(); routeLayer = null }

  const origin = transfer.originWarehouse
  const dest   = transfer.destinationWarehouse
  if (!origin?.latitude || !dest?.latitude) return

  routeLayer = L.layerGroup().addTo(map)

  const makeIcon = (color: string, label: string) => L.divIcon({
    className: '', iconSize: [34, 42], iconAnchor: [17, 42],
    html: `<div style="display:flex;flex-direction:column;align-items:center;">
      <div style="width:34px;height:34px;background:${color};border-radius:9px;
        display:flex;align-items:center;justify-content:center;border:2.5px solid white;
        box-shadow:0 3px 8px ${color}55;">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.2">
          <path d="M22 8.35V20a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V8.35A2 2 0 0 1 3.26 6.5l8-3.2a2 2 0 0 1 1.48 0l8 3.2A2 2 0 0 1 22 8.35Z"/>
          <path d="M6 18h12"/><path d="M6 14h12"/><rect x="8" y="10" width="8" height="14"/>
        </svg>
      </div>
      <div style="width:0;height:0;border-left:6px solid transparent;border-right:6px solid transparent;border-top:8px solid ${color};"></div>
      <div style="background:${color};color:white;font-size:8px;font-weight:800;padding:1px 5px;border-radius:3px;margin-top:1px;white-space:nowrap;">${label}</div>
    </div>`,
  })

  L.marker([origin.latitude, origin.longitude], { icon: makeIcon('#3b82f6', 'FROM') })
    .addTo(routeLayer).bindPopup(`<b>${origin.name}</b><br><small>Origin</small>`)

  L.marker([dest.latitude, dest.longitude], { icon: makeIcon('#10b981', 'TO') })
    .addTo(routeLayer).bindPopup(`<b>${dest.name}</b><br><small>Destination</small>`)

  L.polyline(
    [[origin.latitude, origin.longitude], [dest.latitude, dest.longitude]],
    { color: '#3b82f6', weight: 3, dashArray: '10,6', opacity: 0.8 }
  ).addTo(routeLayer)

  map.fitBounds(
    L.latLngBounds([origin.latitude, origin.longitude], [dest.latitude, dest.longitude]),
    { padding: [40, 40] }
  )
}

watch(activeTransfer, (val) => { if (val && map) renderRoute(val) })

function statusBadge(s: string) {
  const m: Record<string, string> = {
    picking: 'bg-yellow-500/10 text-yellow-700', packing: 'bg-orange-500/10 text-orange-700',
    on_the_way: 'bg-blue-500/10 text-blue-700', put_away: 'bg-purple-500/10 text-purple-700',
    completed: 'bg-green-500/10 text-green-700', cancelled: 'bg-red-500/10 text-red-700',
  }
  return m[s] || 'bg-muted text-muted-foreground'
}
function statusColor(s: string) {
  const m: Record<string, string> = {
    picking: 'bg-yellow-500/10 text-yellow-600', packing: 'bg-orange-500/10 text-orange-600',
    on_the_way: 'bg-blue-500/10 text-blue-600', put_away: 'bg-purple-500/10 text-purple-600',
    completed: 'bg-green-500/10 text-green-600', cancelled: 'bg-red-500/10 text-red-600',
  }
  return m[s] || 'bg-muted text-muted-foreground'
}
function statusEmoji(s: string) {
  const m: Record<string, string> = {
    picking: '📦', packing: '🗃️', on_the_way: '🚚', put_away: '✅', completed: '✓', cancelled: '✗',
  }
  return m[s] || '•'
}
function formatDate(d: string) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })
}

onMounted(async () => {
  await fetchTransfers()
  await initMap()
})
onUnmounted(() => { if (map) { map.remove(); map = null } })
</script>