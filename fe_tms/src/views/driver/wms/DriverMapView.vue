<template>
  <div class="space-y-4">
    <div>
      <h1 class="text-2xl font-bold tracking-tight">Live Map</h1>
      <p class="text-sm text-muted-foreground mt-0.5">Your current location and active route</p>
    </div>

    <!-- Offline warning -->
    <div v-if="!isOnline"
      class="rounded-xl border border-yellow-500/30 bg-yellow-500/5 px-4 py-3 flex items-center gap-3">
      <AlertTriangle class="h-4 w-4 text-yellow-600 shrink-0" />
      <p class="text-xs font-medium text-yellow-700 dark:text-yellow-400">
        You are offline. Go online from the sidebar to share your location.
      </p>
    </div>

    <!-- Geolocation not supported -->
    <div v-if="geoError"
      class="rounded-xl border border-red-500/30 bg-red-500/5 px-4 py-3 flex items-center gap-3">
      <AlertTriangle class="h-4 w-4 text-red-500 shrink-0" />
      <p class="text-xs font-medium text-red-600">{{ geoError }}</p>
    </div>

    <!-- Map -->
    <div class="relative rounded-2xl border overflow-hidden bg-muted" style="height: 480px;">
      <div ref="mapEl" class="h-full w-full" />

      <!-- Loading overlay -->
      <div v-if="!mapReady"
        class="absolute inset-0 flex items-center justify-center bg-background/60 z-[500] backdrop-blur-sm">
        <div class="flex flex-col items-center gap-3">
          <div class="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent" />
          <p class="text-xs text-muted-foreground">Loading map...</p>
        </div>
      </div>

      <!-- Controls -->
      <button @click="centerOnMe"
        class="absolute bottom-4 right-4 z-[1000] rounded-xl bg-card border shadow-md px-3 py-2 text-xs font-medium flex items-center gap-2 hover:bg-muted transition-colors">
        <Crosshair class="h-4 w-4" /> My Location
      </button>

      <!-- Status badge -->
      <div class="absolute top-4 left-4 z-[1000] rounded-xl bg-card border shadow-md px-3 py-2 flex items-center gap-2">
        <span :class="['h-2 w-2 rounded-full transition-colors',
          isOnline && coords ? 'bg-green-500 animate-pulse' : isOnline ? 'bg-yellow-500 animate-pulse' : 'bg-muted-foreground']" />
        <span class="text-xs font-medium">
          {{ isOnline && coords ? 'Sharing location' : isOnline ? 'Getting location...' : 'Location off' }}
        </span>
      </div>

      <!-- Accuracy badge -->
      <div v-if="coords" class="absolute top-4 right-4 z-[1000] rounded-xl bg-card border shadow-md px-3 py-2">
        <p class="text-[10px] text-muted-foreground">Accuracy</p>
        <p class="text-xs font-bold">±{{ coords.accuracy }}m</p>
      </div>
    </div>

    <!-- Coordinates card -->
    <div v-if="coords" class="rounded-xl border bg-card px-4 py-3 flex items-center gap-3">
      <MapPin class="h-4 w-4 text-primary shrink-0" />
      <div class="flex-1">
        <p class="text-xs font-semibold">Current Position</p>
        <p class="text-[11px] text-muted-foreground font-mono">
          {{ coords.lat.toFixed(6) }}, {{ coords.lng.toFixed(6) }}
        </p>
      </div>
      <div class="text-right text-[11px] text-muted-foreground">
        Last update<br>
        <span class="font-medium text-foreground">{{ lastSentTime || '—' }}</span>
      </div>
    </div>

    <!-- Active transfer route info -->
    <div v-if="activeTransfer" class="rounded-xl border bg-card p-4 space-y-3">
      <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Active Route</p>
      <div class="flex items-center gap-2 flex-wrap text-sm">
        <span class="font-mono text-primary font-bold">{{ activeTransfer.transfer_number }}</span>
        <span class="text-muted-foreground">·</span>
        <span class="font-medium">{{ activeTransfer.originWarehouse?.name ?? '—' }}</span>
        <span class="text-muted-foreground">→</span>
        <span class="font-medium">{{ activeTransfer.destinationWarehouse?.name ?? '—' }}</span>
      </div>
      <span :class="['inline-flex rounded-full px-2.5 py-1 text-[10px] font-bold capitalize', statusBadge(activeTransfer.status)]">
        {{ activeTransfer.status.replace(/_/g, ' ') }}
      </span>
    </div>

    <!-- No active transfer -->
    <div v-else-if="!loadingTransfer" class="rounded-xl border bg-muted/30 px-4 py-6 text-center">
      <p class="text-sm text-muted-foreground">No active transfer assigned</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, inject, watch, onMounted, onUnmounted } from 'vue'
import { AlertTriangle, Crosshair, MapPin } from 'lucide-vue-next'
import api from '@/lib/axios'

// Injected dari DriverLayoutView
const isOnline = inject<any>('driverOnline', ref(false))

// ── State ─────────────────────────────────────────────────────────────────────
const mapEl    = ref<HTMLDivElement>()
const mapReady = ref(false)
const geoError = ref('')
const coords   = ref<{ lat: number; lng: number; accuracy: number } | null>(null)
const lastSentTime    = ref('')
const activeTransfer  = ref<any>(null)
const loadingTransfer = ref(false)

let map: any = null
let L: any   = null
let myMarker: any     = null
let myCircle: any     = null   // accuracy circle
let routeLayer: any   = null
let watchId: number | null       = null
let sendInterval: ReturnType<typeof setInterval> | null = null

// ── Map init ──────────────────────────────────────────────────────────────────
onMounted(async () => {
  L = await import('leaflet')
  await import('leaflet/dist/leaflet.css')

  // Default center: Jakarta
  map = L.map(mapEl.value, { center: [-6.2088, 106.8456], zoom: 13, zoomControl: false })
  L.control.zoom({ position: 'bottomright' }).addTo(map)

  L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
    attribution: '© OpenStreetMap, © CARTO',
    subdomains: 'abcd', maxZoom: 19,
  }).addTo(map)

  mapReady.value = true

  // Mulai tracking kalau sudah online
  if (isOnline.value) startTracking()
  await fetchActiveTransfer()
})

onUnmounted(() => {
  stopTracking()
  if (map) map.remove()
})

// ── Geolocation tracking ──────────────────────────────────────────────────────
function startTracking() {
  geoError.value = ''
  if (!navigator.geolocation) {
    geoError.value = 'Geolocation is not supported by your browser.'
    return
  }

  // High accuracy watch
  watchId = navigator.geolocation.watchPosition(
    (pos) => {
      const { latitude: lat, longitude: lng, accuracy } = pos.coords
      coords.value = { lat, lng, accuracy: Math.round(accuracy) }
      updateMyMarker(lat, lng, accuracy)
    },
    (err) => {
      console.error('Geolocation error:', err)
      if (err.code === err.PERMISSION_DENIED) {
        geoError.value = 'Location permission denied. Please allow location access in your browser.'
      } else if (err.code === err.TIMEOUT) {
        geoError.value = 'Location request timed out. Please check your GPS.'
      }
    },
    { enableHighAccuracy: true, maximumAge: 5000, timeout: 15000 }
  )

  // Kirim ke backend tiap 10 detik
  sendInterval = setInterval(async () => {
    if (coords.value) {
      await sendLocation(coords.value.lat, coords.value.lng)
    }
  }, 10_000)

  // Langsung send pertama kali begitu ada koordinat
  const firstSend = setInterval(() => {
    if (coords.value) {
      sendLocation(coords.value.lat, coords.value.lng)
      clearInterval(firstSend)
    }
  }, 500)
}

function stopTracking() {
  if (watchId !== null) {
    navigator.geolocation.clearWatch(watchId)
    watchId = null
  }
  if (sendInterval) {
    clearInterval(sendInterval)
    sendInterval = null
  }
}

// ── Marker: titik biru yang represent posisi driver ───────────────────────────
function updateMyMarker(lat: number, lng: number, accuracy: number) {
  if (!L || !map) return

  // Icon: lingkaran biru dengan pulse
  const icon = L.divIcon({
    className: '',
    iconSize:  [24, 24],
    iconAnchor:[12, 12],
    html: `
      <div style="position:relative;width:24px;height:24px;">
        <div style="
          position:absolute;inset:-6px;border-radius:50%;
          background:#3b82f6;opacity:0.2;
          animation:driverPing 2s ease-in-out infinite;
        "></div>
        <div style="
          position:relative;width:24px;height:24px;border-radius:50%;
          background:#3b82f6;border:3px solid white;
          box-shadow:0 2px 10px rgba(59,130,246,0.5);
        "></div>
      </div>
    `,
  })

  if (!myMarker) {
    myMarker = L.marker([lat, lng], { icon, zIndexOffset: 1000 })
      .addTo(map)
      .bindPopup('<b>📍 Your Location</b>')
  } else {
    myMarker.setLatLng([lat, lng])
  }

  // Accuracy circle
  if (!myCircle) {
    myCircle = L.circle([lat, lng], {
      radius: accuracy, color: '#3b82f6', fillColor: '#3b82f6',
      fillOpacity: 0.08, weight: 1.5, dashArray: '4,4',
    }).addTo(map)
  } else {
    myCircle.setLatLng([lat, lng]).setRadius(accuracy)
  }

  // Center map ke posisi driver pertama kali
  if (!coordsHadFirstFix) {
    coordsHadFirstFix = true
    map.setView([lat, lng], 15)
  }
}

let coordsHadFirstFix = false

function centerOnMe() {
  if (coords.value && map) {
    map.flyTo([coords.value.lat, coords.value.lng], 16, { duration: 1 })
  } else {
    navigator.geolocation?.getCurrentPosition((pos) => {
      map?.flyTo([pos.coords.latitude, pos.coords.longitude], 16, { duration: 1 })
    })
  }
}

// ── Send location ke Laravel ──────────────────────────────────────────────────
async function sendLocation(lat: number, lng: number) {
  try {
    await api.put('/driver/location', { lat, lng })
    lastSentTime.value = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
  } catch (e) {
    // Silent — jangan ganggu UX
  }
}

// ── Fetch active transfer + gambar route ──────────────────────────────────────
async function fetchActiveTransfer() {
  loadingTransfer.value = true
  try {
    const res  = await api.get('/driver/transfers', {
      params: { status: 'picking,packing,on_the_way,put_away' },
    })
    const raw  = res.data?.data ?? res.data
    const list = Array.isArray(raw) ? raw : (raw?.data ?? [])

    activeTransfer.value = list.find((t: any) =>
      ['picking', 'packing', 'on_the_way', 'put_away'].includes(t.status)
    ) ?? null

    if (activeTransfer.value && map && L) {
      drawRoute(activeTransfer.value)
    }
  } catch { /* silent */ } finally {
    loadingTransfer.value = false
  }
}

function drawRoute(transfer: any) {
  // Hapus route lama
  if (routeLayer) { routeLayer.remove(); routeLayer = null }

  const origin = transfer.originWarehouse ?? transfer.origin_warehouse
  const dest   = transfer.destinationWarehouse ?? transfer.destination_warehouse
  if (!origin?.latitude || !dest?.latitude) return

  const warehouseIcon = (label: string) => L.divIcon({
    className: '',
    iconSize:  [36, 44],
    iconAnchor:[18, 44],
    html: `
      <div style="display:flex;flex-direction:column;align-items:center;">
        <div style="
          width:36px;height:36px;background:#3b82f6;border-radius:10px;
          display:flex;align-items:center;justify-content:center;
          border:2.5px solid white;box-shadow:0 3px 10px rgba(59,130,246,0.4);
        ">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.2">
            <path d="M22 8.35V20a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V8.35A2 2 0 0 1 3.26 6.5l8-3.2a2 2 0 0 1 1.48 0l8 3.2A2 2 0 0 1 22 8.35Z"/>
            <path d="M6 18h12"/><path d="M6 14h12"/><rect x="8" y="10" width="8" height="14"/>
          </svg>
        </div>
        <div style="width:0;height:0;border-left:6px solid transparent;border-right:6px solid transparent;border-top:8px solid #3b82f6;margin-top:-1px;"></div>
        <div style="
          margin-top:2px;background:#1d4ed8;color:white;border-radius:4px;
          font-size:9px;font-weight:700;padding:1px 5px;white-space:nowrap;
        ">${label}</div>
      </div>`,
  })

  routeLayer = L.layerGroup().addTo(map)

  // Marker origin
  L.marker([origin.latitude, origin.longitude], { icon: warehouseIcon('ORIGIN') })
    .addTo(routeLayer)
    .bindPopup(`<div style="font-family:system-ui;padding:8px;"><b>${origin.name}</b><br><small style="color:#6b7280">Origin Warehouse</small></div>`)

  // Marker destination  
  const destColor = '#10b981'
  const destMarker = L.divIcon({
    className: '',
    iconSize:  [36, 44],
    iconAnchor:[18, 44],
    html: `
      <div style="display:flex;flex-direction:column;align-items:center;">
        <div style="
          width:36px;height:36px;background:${destColor};border-radius:10px;
          display:flex;align-items:center;justify-content:center;
          border:2.5px solid white;box-shadow:0 3px 10px rgba(16,185,129,0.4);
        ">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.2">
            <path d="M22 8.35V20a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V8.35A2 2 0 0 1 3.26 6.5l8-3.2a2 2 0 0 1 1.48 0l8 3.2A2 2 0 0 1 22 8.35Z"/>
            <path d="M6 18h12"/><path d="M6 14h12"/><rect x="8" y="10" width="8" height="14"/>
          </svg>
        </div>
        <div style="width:0;height:0;border-left:6px solid transparent;border-right:6px solid transparent;border-top:8px solid ${destColor};margin-top:-1px;"></div>
        <div style="
          margin-top:2px;background:#065f46;color:white;border-radius:4px;
          font-size:9px;font-weight:700;padding:1px 5px;white-space:nowrap;
        ">DEST</div>
      </div>`,
  })

  L.marker([dest.latitude, dest.longitude], { icon: destMarker })
    .addTo(routeLayer)
    .bindPopup(`<div style="font-family:system-ui;padding:8px;"><b>${dest.name}</b><br><small style="color:#6b7280">Destination Warehouse</small></div>`)

  // Polyline rute (dashed)
  L.polyline(
    [[origin.latitude, origin.longitude], [dest.latitude, dest.longitude]],
    { color: '#3b82f6', weight: 3.5, dashArray: '10, 7', opacity: 0.8, lineCap: 'round' }
  ).addTo(routeLayer)

  // Fit bounds supaya keliatan semua
  const bounds = L.latLngBounds(
    [origin.latitude, origin.longitude],
    [dest.latitude, dest.longitude]
  )
  map.fitBounds(bounds, { padding: [80, 80] })
}

// ── Watch isOnline ────────────────────────────────────────────────────────────
watch(isOnline, (val) => {
  if (val) {
    startTracking()
  } else {
    stopTracking()
    geoError.value = ''
  }
})

// ── Helpers ───────────────────────────────────────────────────────────────────
function statusBadge(status: string) {
  const m: Record<string, string> = {
    picking:    'bg-yellow-500/10 text-yellow-700 dark:text-yellow-400',
    packing:    'bg-orange-500/10 text-orange-700 dark:text-orange-400',
    on_the_way: 'bg-blue-500/10 text-blue-700 dark:text-blue-400',
    put_away:   'bg-purple-500/10 text-purple-700 dark:text-purple-400',
    completed:  'bg-green-500/10 text-green-700 dark:text-green-400',
  }
  return m[status] || 'bg-muted text-muted-foreground'
}
</script>

<style>
@keyframes driverPing {
  0%, 100% { transform: scale(1); opacity: 0.2; }
  50%       { transform: scale(1.8); opacity: 0; }
}
.leaflet-popup-content-wrapper {
  border-radius: 12px !important;
  box-shadow: 0 4px 20px rgba(0,0,0,0.12) !important;
}
</style>