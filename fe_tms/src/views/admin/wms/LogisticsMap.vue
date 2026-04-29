<template>
  <div class="flex flex-col h-[calc(100vh-9rem)] gap-4">
    <!-- Header -->
    <div class="flex items-center justify-between shrink-0">
      <div>
        <h1 class="text-2xl font-bold tracking-tight">Logistics Map</h1>
        <p class="text-sm text-muted-foreground mt-0.5">Live view of warehouses, drivers, and transfer routes</p>
      </div>
      <div class="flex items-center gap-2">
        <button v-for="layer in layers" :key="layer.key"
          @click="layer.visible = !layer.visible; updateLayerVisibility(layer)"
          :class="[
            'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs font-medium transition-all',
            layer.visible
              ? 'bg-primary/10 border-primary/30 text-primary'
              : 'border-border text-muted-foreground hover:bg-muted',
          ]">
          <span :class="['h-2 w-2 rounded-full', layer.dot]" />
          {{ layer.label }}
        </button>
        <button @click="loadMapData"
          class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border text-sm hover:bg-muted transition-colors">
          <RefreshCw :size="14" :class="loading && 'animate-spin'" /> Refresh
        </button>
      </div>
    </div>

    <!-- Map + Sidebar -->
    <div class="flex gap-4 flex-1 min-h-0">
      <!-- Map -->
      <div class="flex-1 rounded-2xl border overflow-hidden relative bg-muted/20">
        <div v-if="loading && !mapReady"
          class="absolute inset-0 flex items-center justify-center bg-background/60 z-10 backdrop-blur-sm">
          <div class="flex flex-col items-center gap-3">
            <RefreshCw :size="28" class="animate-spin text-primary" />
            <p class="text-sm text-muted-foreground">Loading map...</p>
          </div>
        </div>
        <div ref="mapContainer" class="w-full h-full" />

        <!-- Live indicator -->
        <div class="absolute top-3 left-3 z-[400] flex items-center gap-2 rounded-full bg-card/90 backdrop-blur-sm px-3 py-1.5 border shadow-sm text-xs">
          <span class="relative flex h-2 w-2">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75" />
            <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500" />
          </span>
          Live
        </div>

        <!-- Stats overlay -->
        <div class="absolute bottom-3 left-3 z-[400] flex gap-2">
          <div class="rounded-xl bg-card/90 backdrop-blur-sm border shadow-sm px-3 py-2 text-center min-w-[60px]">
            <p class="text-lg font-bold text-blue-500">{{ warehouses.length }}</p>
            <p class="text-[10px] text-muted-foreground">Warehouse</p>
          </div>
          <div class="rounded-xl bg-card/90 backdrop-blur-sm border shadow-sm px-3 py-2 text-center min-w-[60px]">
            <p class="text-lg font-bold text-green-500">{{ drivers.filter(d => d.availability_status === 'available').length }}</p>
            <p class="text-[10px] text-muted-foreground">Available</p>
          </div>
          <div class="rounded-xl bg-card/90 backdrop-blur-sm border shadow-sm px-3 py-2 text-center min-w-[60px]">
            <p class="text-lg font-bold text-amber-500">{{ drivers.filter(d => d.availability_status === 'on_trip').length }}</p>
            <p class="text-[10px] text-muted-foreground">On Trip</p>
          </div>
          <div class="rounded-xl bg-card/90 backdrop-blur-sm border shadow-sm px-3 py-2 text-center min-w-[60px]">
            <p class="text-lg font-bold text-purple-500">{{ transfers.length }}</p>
            <p class="text-[10px] text-muted-foreground">Routes</p>
          </div>
        </div>
      </div>

      <!-- Side panel -->
      <div class="w-72 flex flex-col gap-3 overflow-y-auto shrink-0 pr-0.5">

        <!-- Active Transfers -->
        <div class="rounded-xl border bg-card p-4">
          <h3 class="text-sm font-semibold mb-3 flex items-center gap-2">
            <ArrowRightLeft :size="14" class="text-purple-500" />
            Active Routes
            <span v-if="transfers.length" class="ml-auto rounded-full bg-purple-500/10 text-purple-600 px-2 py-0.5 text-[10px] font-bold">
              {{ transfers.length }}
            </span>
          </h3>
          <div class="space-y-2">
            <div v-for="t in transfers" :key="t.id" @click="focusTransfer(t)"
              class="p-3 rounded-xl border hover:border-primary/50 hover:bg-muted/40 cursor-pointer transition-all group">
              <div class="flex items-start justify-between gap-1 mb-1.5">
                <p class="font-mono text-xs text-primary font-semibold">
                  {{ t.salesOrder?.so_number ?? t.transfer_number }}
                </p>
                <span :class="statusBadge(t.status)">{{ t.status }}</span>
              </div>
              <div class="flex items-center gap-1 text-xs text-muted-foreground">
                <span class="h-1.5 w-1.5 rounded-full bg-blue-400" />
                <span class="truncate">{{ t.originWarehouse?.name ?? '—' }}</span>
              </div>
              <div class="flex items-center gap-1 text-xs text-muted-foreground mt-0.5">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-400" />
                <span class="truncate">{{ t.destinationWarehouse?.name ?? '—' }}</span>
              </div>
              <p v-if="t.driver" class="mt-1.5 text-[11px] text-muted-foreground font-medium">
                🚗 {{ t.driver.name }}
              </p>
            </div>
            <p v-if="transfers.length === 0" class="text-xs text-muted-foreground text-center py-4 opacity-60">
              No active routes
            </p>
          </div>
        </div>

        <!-- Warehouses -->
        <div class="rounded-xl border bg-card p-4">
          <h3 class="text-sm font-semibold mb-3 flex items-center gap-2">
            <span class="text-base">🏭</span> Warehouses
            <span v-if="warehouses.length" class="ml-auto rounded-full bg-blue-500/10 text-blue-600 px-2 py-0.5 text-[10px] font-bold">
              {{ warehouses.length }}
            </span>
          </h3>
          <div class="space-y-1.5">
            <div v-for="wh in warehouses" :key="wh.id" @click="flyToWarehouse(wh)"
              class="flex items-center gap-3 p-2.5 rounded-lg hover:bg-muted/50 cursor-pointer transition-colors group">
              <div class="h-8 w-8 rounded-lg bg-blue-500/10 flex items-center justify-center shrink-0 group-hover:bg-blue-500/20 transition-colors">
                <span class="text-sm">🏭</span>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium truncate">{{ wh.name }}</p>
                <p class="text-xs text-muted-foreground font-mono">{{ wh.code }}</p>
              </div>
              <span :class="['h-2 w-2 rounded-full shrink-0',
                wh.latitude && wh.longitude
                  ? (wh.status === 'active' ? 'bg-emerald-500' : 'bg-muted-foreground/40')
                  : 'bg-orange-400']"
                :title="!wh.latitude || !wh.longitude ? 'No coordinates' : wh.status" />
            </div>
            <p v-if="warehouses.length === 0" class="text-xs text-muted-foreground text-center py-4 opacity-60">
              No warehouses found
            </p>
            <p v-if="warehousesWithoutCoords > 0" class="text-[10px] text-orange-500 text-center pt-1">
              {{ warehousesWithoutCoords }} warehouse{{ warehousesWithoutCoords > 1 ? 's' : '' }} without coordinates
            </p>
          </div>
        </div>

        <!-- Drivers -->
        <div class="rounded-xl border bg-card p-4">
          <h3 class="text-sm font-semibold mb-3 flex items-center gap-2">
            <Truck :size="14" class="text-green-500" /> Drivers
            <span v-if="drivers.length" class="ml-auto rounded-full bg-green-500/10 text-green-600 px-2 py-0.5 text-[10px] font-bold">
              {{ drivers.length }}
            </span>
          </h3>
          <div class="space-y-1.5">
            <div v-for="driver in drivers" :key="driver.id" @click="flyToDriver(driver)"
              class="flex items-center gap-3 p-2.5 rounded-lg hover:bg-muted/50 cursor-pointer transition-colors">
              <div :class="['h-8 w-8 rounded-full flex items-center justify-center shrink-0 text-xs font-bold ring-2',
                driver.availability_status === 'available'
                  ? 'bg-green-500 text-white ring-green-200 dark:ring-green-900'
                  : driver.availability_status === 'on_trip'
                    ? 'bg-amber-500 text-white ring-amber-200 dark:ring-amber-900'
                    : 'bg-muted text-muted-foreground ring-border']">
                {{ initials(driver.name) }}
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium truncate">{{ driver.name }}</p>
                <p class="text-[11px] text-muted-foreground capitalize">{{ driver.availability_status.replace('_', ' ') }}</p>
              </div>
              <span :class="['h-2 w-2 rounded-full shrink-0 ring-2',
                driver.availability_status === 'available'
                  ? 'bg-green-500 ring-green-200 dark:ring-green-900'
                  : driver.availability_status === 'on_trip'
                    ? 'bg-amber-500 ring-amber-200 dark:ring-amber-900'
                    : 'bg-muted-foreground/40 ring-transparent']" />
            </div>
            <p v-if="drivers.length === 0" class="text-xs text-muted-foreground text-center py-4 opacity-60">
              No active drivers
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import { RefreshCw, Truck, ArrowRightLeft } from 'lucide-vue-next'
import api from '@/lib/axios'

// ── Import leaflet CSS di sini (cara yang benar untuk Vite) ──────────────────
import 'leaflet/dist/leaflet.css'

interface WarehouseData {
  id: number; name: string; code: string
  latitude: number | null; longitude: number | null; status: string
}
interface DriverData {
  id: number; name: string
  availability_status: string; lat: number; lng: number
}
interface TransferData {
  id: number; transfer_number: string; status: string
  driver?: { id: number; name: string }
  salesOrder?: { so_number: string }
  originWarehouse?: { id: number; name: string; latitude: number; longitude: number }
  destinationWarehouse?: { id: number; name: string; latitude: number; longitude: number }
}

const mapContainer = ref<HTMLDivElement>()
const loading      = ref(false)
const mapReady     = ref(false)
let map: any = null
let L: any   = null

const markerRefs: Record<string, any> = {}
const layerGroups: Record<string, any> = {}

const warehouses = ref<WarehouseData[]>([])
const drivers    = ref<DriverData[]>([])
const transfers  = ref<TransferData[]>([])

// Hitung warehouse yang tidak punya koordinat
const warehousesWithoutCoords = computed(() =>
  warehouses.value.filter(w => !w.latitude || !w.longitude).length
)

const layers = reactive([
  { key: 'warehouses', label: 'Warehouses', visible: true, dot: 'bg-blue-500' },
  { key: 'drivers',    label: 'Drivers',    visible: true, dot: 'bg-green-500' },
  { key: 'routes',     label: 'Routes',     visible: true, dot: 'bg-purple-500' },
])

function updateLayerVisibility(layer: any) {
  if (!map || !layerGroups[layer.key]) return
  layer.visible ? layerGroups[layer.key].addTo(map) : layerGroups[layer.key].removeFrom(map)
}

// ── Icons ──────────────────────────────────────────────────────────────────────
function warehouseIcon(status: string) {
  const active = status === 'active'
  return L.divIcon({
    className: '',
    iconSize:  [44, 54],
    iconAnchor:[22, 54],
    popupAnchor: [0, -54],
    html: `
      <div style="display:flex;flex-direction:column;align-items:center;filter:drop-shadow(0 4px 8px rgba(0,0,0,0.3));">
        <div style="
          width:44px;height:44px;
          background:${active ? '#3b82f6' : '#9ca3af'};
          border-radius:12px;
          display:flex;align-items:center;justify-content:center;
          border:3px solid white;
        ">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 8.35V20a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V8.35A2 2 0 0 1 3.26 6.5l8-3.2a2 2 0 0 1 1.48 0l8 3.2A2 2 0 0 1 22 8.35Z"/>
            <path d="M6 18h12"/><path d="M6 14h12"/><rect x="8" y="10" width="8" height="14"/>
          </svg>
        </div>
        <div style="width:0;height:0;border-left:7px solid transparent;border-right:7px solid transparent;border-top:10px solid ${active ? '#3b82f6' : '#9ca3af'};margin-top:-1px;"></div>
      </div>`,
  })
}

function driverIcon(status: string) {
  const color = status === 'available' ? '#22c55e' : status === 'on_trip' ? '#f59e0b' : '#9ca3af'
  const pulse = status === 'on_trip'
    ? `<div style="position:absolute;inset:-4px;border-radius:50%;background:${color};opacity:0.3;animation:ping 1.5s cubic-bezier(0,0,0.2,1) infinite;"></div>`
    : ''
  return L.divIcon({
    className: '',
    iconSize:  [36, 36],
    iconAnchor:[18, 18],
    popupAnchor: [0, -18],
    html: `
      <div style="position:relative;width:36px;height:36px;">
        ${pulse}
        <div style="
          position:relative;width:36px;height:36px;
          background:${color};border-radius:50%;
          display:flex;align-items:center;justify-content:center;
          border:3px solid white;box-shadow:0 2px 12px ${color}66;
        ">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="8" r="4"/><path d="M20 21a8 8 0 1 0-16 0"/>
          </svg>
        </div>
      </div>`,
  })
}

// ── Data fetch — ambil SEMUA warehouse, bukan hanya yang punya koordinat ──────
async function loadMapData() {
  loading.value = true
  try {
    // Ambil map data (drivers + transfers) dan semua warehouses secara paralel
    const [mapRes, whRes] = await Promise.all([
      api.get('/wms/map/data'),
      api.get('/wms/warehouses'),
    ])

    // Warehouses dari endpoint warehouses (semua, termasuk yang belum ada koordinat)
    const allWarehouses = whRes.data?.data ?? whRes.data ?? []
    warehouses.value = allWarehouses

    drivers.value   = mapRes.data.drivers   ?? []
    transfers.value = mapRes.data.transfers  ?? []

    if (map && L) {
      // Clear semua layer
      Object.values(layerGroups).forEach((lg: any) => lg.clearLayers())
      // Clear marker refs
      Object.keys(markerRefs).forEach(k => delete markerRefs[k])

      renderWarehouses()
      renderDrivers()
      renderRoutes()

      // Auto-fit ke semua marker yang punya koordinat
      if (!mapReady.value) {
        const allPoints: [number, number][] = [
          ...warehouses.value
            .filter(w => w.latitude && w.longitude)
            .map(w => [Number(w.latitude), Number(w.longitude)] as [number, number]),
          ...drivers.value
            .filter(d => d.lat && d.lng)
            .map(d => [d.lat, d.lng] as [number, number]),
        ]
        if (allPoints.length > 0) {
          map.fitBounds(L.latLngBounds(allPoints), { padding: [60, 60] })
        }
      }
    }
  } catch (e) {
    console.error('Failed to load map data', e)
  } finally {
    loading.value  = false
    mapReady.value = true
  }
}

// ── Map init ───────────────────────────────────────────────────────────────────
onMounted(async () => {
  // Import leaflet secara dynamic (CSS sudah di-import di atas)
  L = (await import('leaflet')).default

  map = L.map(mapContainer.value!, {
    center:      [-2.5, 118],
    zoom:        5,
    zoomControl: false,
  })

  L.control.zoom({ position: 'bottomright' }).addTo(map)

  L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
    attribution: '© OpenStreetMap, © CARTO',
    subdomains:  'abcd',
    maxZoom:     19,
  }).addTo(map)

  // Inject ping animation CSS
  const style = document.createElement('style')
  style.textContent = `
    @keyframes ping {
      75%, 100% { transform: scale(2); opacity: 0; }
    }
    .leaflet-popup-content-wrapper {
      border-radius: 14px !important;
      box-shadow: 0 8px 32px rgba(0,0,0,0.18) !important;
      border: 1px solid rgba(0,0,0,0.06) !important;
      padding: 0 !important;
      overflow: hidden;
    }
    .leaflet-popup-content { margin: 0 !important; }
    .leaflet-popup-tip { display: none; }
    .leaflet-container { font-family: system-ui, sans-serif; }
  `
  document.head.appendChild(style)

  layerGroups.warehouses = L.layerGroup().addTo(map)
  layerGroups.drivers    = L.layerGroup().addTo(map)
  layerGroups.routes     = L.layerGroup().addTo(map)

  await loadMapData()

  // Auto-refresh tiap 30 detik
  const interval = setInterval(loadMapData, 30_000)
  onUnmounted(() => clearInterval(interval))
})

onUnmounted(() => { if (map) map.remove() })

// ── Render markers ─────────────────────────────────────────────────────────────
function renderWarehouses() {
  warehouses.value.forEach(wh => {
    // Skip warehouse yang tidak punya koordinat
    if (!wh.latitude || !wh.longitude) return

    const lat = Number(wh.latitude)
    const lng = Number(wh.longitude)
    if (isNaN(lat) || isNaN(lng)) return

    const popup = `
      <div style="padding:14px 16px;min-width:180px;">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
          <span style="font-size:18px;">🏭</span>
          <div>
            <p style="font-weight:700;font-size:13px;margin:0;">${wh.name}</p>
            <p style="color:#6b7280;font-size:11px;margin:0;font-family:monospace;">${wh.code}</p>
          </div>
        </div>
        <span style="
          display:inline-block;padding:3px 10px;border-radius:999px;font-size:10px;font-weight:700;
          background:${wh.status === 'active' ? '#dcfce7' : '#f3f4f6'};
          color:${wh.status === 'active' ? '#16a34a' : '#6b7280'};
        ">${wh.status.toUpperCase()}</span>
        <p style="color:#9ca3af;font-size:10px;margin-top:6px;margin-bottom:0;">
          ${lat.toFixed(5)}, ${lng.toFixed(5)}
        </p>
      </div>`

    const marker = L.marker([lat, lng], { icon: warehouseIcon(wh.status) })
      .bindPopup(popup, { maxWidth: 240 })
    layerGroups.warehouses.addLayer(marker)
    markerRefs[`wh-${wh.id}`] = marker
  })
}

function renderDrivers() {
  drivers.value.forEach(driver => {
    if (!driver.lat || !driver.lng) return

    const statusColor = driver.availability_status === 'available' ? '#16a34a' : '#d97706'
    const statusBg    = driver.availability_status === 'available' ? '#dcfce7' : '#fef3c7'
    const statusLabel = driver.availability_status === 'available' ? 'Available' : 'On Trip'

    const popup = `
      <div style="padding:14px 16px;min-width:160px;">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
          <div style="
            width:32px;height:32px;border-radius:50%;
            background:${driver.availability_status === 'available' ? '#22c55e' : '#f59e0b'};
            display:flex;align-items:center;justify-content:center;
            color:white;font-weight:700;font-size:11px;
          ">${initials(driver.name)}</div>
          <p style="font-weight:700;font-size:13px;margin:0;">${driver.name}</p>
        </div>
        <span style="
          display:inline-block;padding:3px 10px;border-radius:999px;font-size:10px;font-weight:700;
          background:${statusBg};color:${statusColor};
        ">${statusLabel}</span>
      </div>`

    const marker = L.marker([driver.lat, driver.lng], { icon: driverIcon(driver.availability_status) })
      .bindPopup(popup, { maxWidth: 220 })
    layerGroups.drivers.addLayer(marker)
    markerRefs[`driver-${driver.id}`] = marker
  })
}

function renderRoutes() {
  transfers.value.forEach(t => {
    const origin = t.originWarehouse
    const dest   = t.destinationWarehouse
    if (!origin?.latitude || !dest?.latitude) return

    const statusColors: Record<string, string> = {
      picking:    '#6366f1',
      packing:    '#f59e0b',
      on_the_way: '#3b82f6',
      put_away:   '#10b981',
    }
    const color = statusColors[t.status] ?? '#6366f1'

    L.polyline(
      [[origin.latitude, origin.longitude], [dest.latitude, dest.longitude]],
      { color, weight: 4, opacity: 0.85, dashArray: '10, 8', lineCap: 'round' }
    ).addTo(layerGroups.routes)

    L.circleMarker([origin.latitude, origin.longitude], {
      radius: 5, fillColor: color, color: 'white', weight: 2, fillOpacity: 1,
    }).bindTooltip(`Origin: ${origin.name}`, { direction: 'top' }).addTo(layerGroups.routes)

    L.circleMarker([dest.latitude, dest.longitude], {
      radius: 7, fillColor: color, color: 'white', weight: 2.5, fillOpacity: 1,
    }).bindTooltip(`Destination: ${dest.name}`, { direction: 'top' }).addTo(layerGroups.routes)

    const midLat = (origin.latitude + dest.latitude) / 2
    const midLng = (origin.longitude + dest.longitude) / 2
    const statusLabel = t.status.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())

    L.marker([midLat, midLng], {
      icon: L.divIcon({
        className: '',
        iconSize: [80, 24],
        iconAnchor: [40, 12],
        html: `<div style="
          background:${color};color:white;border-radius:999px;
          font-size:10px;font-weight:700;padding:3px 10px;
          white-space:nowrap;box-shadow:0 2px 8px ${color}66;
          border:2px solid white;
        ">${statusLabel}</div>`,
      }),
    }).bindPopup(`
      <div style="padding:14px 16px;min-width:180px;">
        <p style="font-weight:700;font-size:13px;margin:0 0 6px;">${t.salesOrder?.so_number ?? t.transfer_number}</p>
        <div style="font-size:11px;color:#6b7280;margin-bottom:4px;"><span style="color:#4b5563;">From:</span> ${origin.name}</div>
        <div style="font-size:11px;color:#6b7280;margin-bottom:8px;"><span style="color:#4b5563;">To:</span> ${dest.name}</div>
        ${t.driver ? `<div style="font-size:11px;color:#6b7280;">🚗 ${t.driver.name}</div>` : ''}
        <span style="display:inline-block;margin-top:6px;padding:3px 10px;border-radius:999px;font-size:10px;font-weight:700;background:${color}22;color:${color};">${statusLabel}</span>
      </div>
    `, { maxWidth: 240 }).addTo(layerGroups.routes)
  })
}

// ── Interactions ───────────────────────────────────────────────────────────────
function flyToWarehouse(wh: WarehouseData) {
  if (!wh.latitude || !wh.longitude) return
  map?.flyTo([Number(wh.latitude), Number(wh.longitude)], 14, { duration: 1.2 })
  setTimeout(() => markerRefs[`wh-${wh.id}`]?.openPopup(), 1300)
}

function flyToDriver(driver: DriverData) {
  if (!driver.lat || !driver.lng) return
  map?.flyTo([driver.lat, driver.lng], 15, { duration: 1.2 })
  setTimeout(() => markerRefs[`driver-${driver.id}`]?.openPopup(), 1300)
}

function focusTransfer(t: TransferData) {
  const o = t.originWarehouse
  const d = t.destinationWarehouse
  if (!o?.latitude || !d?.latitude) return
  map?.flyToBounds(
    L.latLngBounds([o.latitude, o.longitude], [d.latitude, d.longitude]),
    { padding: [80, 80], duration: 1.3, maxZoom: 13 }
  )
}

function initials(name: string) {
  return name.split(' ').map(s => s[0]).slice(0, 2).join('').toUpperCase()
}

function statusBadge(status: string) {
  const map: Record<string, string> = {
    picking:    'text-[10px] px-1.5 py-0.5 rounded-full bg-indigo-500/10 text-indigo-600 font-semibold',
    packing:    'text-[10px] px-1.5 py-0.5 rounded-full bg-amber-500/10 text-amber-600 font-semibold',
    on_the_way: 'text-[10px] px-1.5 py-0.5 rounded-full bg-blue-500/10 text-blue-600 font-semibold',
    put_away:   'text-[10px] px-1.5 py-0.5 rounded-full bg-emerald-500/10 text-emerald-600 font-semibold',
  }
  return map[status] ?? 'text-[10px] px-1.5 py-0.5 rounded-full bg-muted text-muted-foreground font-semibold'
}
</script>

<style>
.leaflet-popup-content-wrapper {
  border-radius: 14px !important;
  box-shadow: 0 8px 32px rgba(0,0,0,0.15) !important;
  overflow: hidden;
  padding: 0 !important;
}
.leaflet-popup-content { margin: 0 !important; }
.leaflet-popup-tip-container { display: none; }
</style>