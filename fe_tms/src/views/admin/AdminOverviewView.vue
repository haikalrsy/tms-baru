<template>
  <div class="p-6 space-y-6 bg-gray-50 dark:bg-gray-950 min-h-screen">

    <!-- Header -->
    <div>
      <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-50 tracking-tight">Operations Overview</h1>
      <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Real-time view of transport, deliveries, and revenue performance.</p>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
      <div v-for="stat in stats" :key="stat.label"
        class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-3">
          <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-widest">{{ stat.label }}</p>
          <div :class="`w-8 h-8 rounded-xl flex items-center justify-center ${stat.iconBg}`">
            <component :is="stat.icon" :class="`w-4 h-4 ${stat.iconColor}`" />
          </div>
        </div>
        <p class="text-3xl font-bold text-gray-900 dark:text-gray-50 tabular-nums">{{ stat.value }}</p>
        <div v-if="stat.delta" class="mt-2 flex items-center gap-1">
          <span :class="['text-xs font-medium', stat.delta.positive ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500']">
            {{ stat.delta.positive ? '▲' : '▼' }} {{ stat.delta.value }}
          </span>
        </div>
      </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">
      <!-- Area Chart -->
      <div class="lg:col-span-3 bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 shadow-sm">
        <div class="mb-4">
          <h3 class="font-semibold text-gray-800 dark:text-gray-100">Revenue · last 7 days</h3>
          <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Daily completed delivery revenue</p>
        </div>
        <div class="h-64 relative">
          <canvas ref="areaChartRef" class="w-full h-full" />
        </div>
      </div>

      <!-- Bar Chart -->
      <div class="lg:col-span-2 bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 shadow-sm">
        <div class="mb-4">
          <h3 class="font-semibold text-gray-800 dark:text-gray-100">Revenue · today by hour</h3>
          <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">08:00 — 19:00</p>
        </div>
        <div class="h-64 relative">
          <canvas ref="barChartRef" class="w-full h-full" />
        </div>
      </div>
    </div>

    <!-- Bottom Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

      <!-- Recent Job Orders -->
      <div class="lg:col-span-2 bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
          <div>
            <h3 class="font-semibold text-gray-800 dark:text-gray-100">Recent Job Orders</h3>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Latest activity across all orders</p>
          </div>
          <div v-if="loading" class="w-4 h-4 border-2 border-blue-500 border-t-transparent rounded-full animate-spin" />
        </div>
        <div v-if="loading" class="flex items-center justify-center py-12">
          <Loader2 class="w-6 h-6 animate-spin text-blue-500" />
        </div>
        <table v-else class="w-full">
          <thead class="bg-gray-50 dark:bg-gray-800/60">
            <tr>
              <th class="text-left text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wide px-5 py-3">Job</th>
              <th class="text-left text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wide px-4 py-3">Route</th>
              <th class="text-left text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wide px-4 py-3">Status</th>
              <th class="text-right text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wide px-5 py-3">Cost</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
            <tr v-for="job in recentJobs" :key="job.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition">
              <td class="px-5 py-3.5">
                <p class="text-sm font-semibold text-blue-600 dark:text-blue-400">{{ job.job_number }}</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 truncate max-w-32">{{ job.customer_name }}</p>
              </td>
              <td class="px-4 py-3.5">
                <p class="text-xs text-gray-600 dark:text-gray-400 whitespace-nowrap">
                  {{ job.origin_city }} → {{ job.destination_city }}
                </p>
              </td>
              <td class="px-4 py-3.5">
                <span :class="`text-xs px-2 py-1 rounded-full font-medium ${statusClass(job.status)}`">
                  {{ statusLabel(job.status) }}
                </span>
              </td>
              <td class="px-5 py-3.5 text-right">
                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                  {{ job.estimated_cost ? 'Rp ' + Number(job.estimated_cost).toLocaleString('id-ID') : '-' }}
                </p>
              </td>
            </tr>
            <tr v-if="recentJobs.length === 0">
              <td colspan="4" class="px-5 py-10 text-center text-gray-400 text-sm">No job orders found</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Status Breakdown -->
      <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 shadow-sm">
        <div class="mb-4">
          <h3 class="font-semibold text-gray-800 dark:text-gray-100">Status Breakdown</h3>
          <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">All job orders by status</p>
        </div>

        <div class="space-y-3">
          <div v-for="item in statusBreakdown" :key="item.status" class="flex items-center gap-3">
            <div :class="`w-2.5 h-2.5 rounded-full flex-shrink-0 ${item.dot}`" />
            <p class="text-sm text-gray-600 dark:text-gray-400 flex-1">{{ item.label }}</p>
            <div class="w-24 bg-gray-100 dark:bg-gray-800 rounded-full h-1.5">
              <div :class="`h-full rounded-full ${item.bar}`"
                :style="`width: ${totalJobs > 0 ? Math.round(item.count / totalJobs * 100) : 0}%`" />
            </div>
            <span class="text-sm font-semibold text-gray-800 dark:text-gray-100 w-6 text-right">{{ item.count }}</span>
          </div>
        </div>

        <div class="mt-5 pt-4 border-t border-gray-100 dark:border-gray-800">
          <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Completion Rate</p>
          <div class="flex items-center gap-2">
            <div class="flex-1 bg-gray-100 dark:bg-gray-800 rounded-full h-2.5">
              <div class="h-full bg-emerald-500 rounded-full transition-all duration-700"
                :style="`width: ${completionRate}%`" />
            </div>
            <span class="text-sm font-bold text-emerald-600 dark:text-emerald-400 w-10 text-right">{{ completionRate }}%</span>
          </div>
        </div>

        <div class="mt-4 grid grid-cols-2 gap-2">
          <div class="bg-blue-50 dark:bg-blue-950/30 rounded-xl p-3 text-center">
            <p class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ activeCount }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Active</p>
          </div>
          <div class="bg-emerald-50 dark:bg-emerald-950/30 rounded-xl p-3 text-center">
            <p class="text-lg font-bold text-emerald-600 dark:text-emerald-400">{{ completedCount }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Completed</p>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import {
  ClipboardList, Truck, CheckCircle2, AlertTriangle,
  DollarSign, Loader2
} from 'lucide-vue-next'
import api from '@/lib/axios'
import { useThemeStore } from '@/stores/theme'

// ── Store & State ─────────────────────────────────────────
const themeStore = useThemeStore()
const loading    = ref(false)
const jobs       = ref<any[]>([])

// ── Chart refs ────────────────────────────────────────────
const areaChartRef = ref<HTMLCanvasElement | null>(null)
const barChartRef  = ref<HTMLCanvasElement | null>(null)

// ── Fetch ─────────────────────────────────────────────────
async function fetchJobs() {
  loading.value = true
  try {
    const res = await api.get('/job-orders', { params: { per_page: 100 } })
    const raw = typeof res.data === 'string'
      ? (() => { const m = res.data.match(/\{[\s\S]*\}/); return m ? JSON.parse(m[0]) : {} })()
      : res.data
    jobs.value = raw?.data?.data ?? raw?.data ?? raw ?? []
  } catch {
    jobs.value = []
  } finally {
    loading.value = false
  }
}

// ── Computed ──────────────────────────────────────────────
const recentJobs     = computed(() => [...jobs.value].slice(0, 8))
const totalJobs      = computed(() => jobs.value.length)
const completedCount = computed(() => jobs.value.filter(j => ['delivered', 'completed'].includes(j.status)).length)
const activeCount    = computed(() => jobs.value.filter(j => ['in_progress', 'picked_up', 'in_transit', 'assigned'].includes(j.status)).length)
const failedCount    = computed(() => jobs.value.filter(j => j.status === 'cancelled').length)
const pendingCount   = computed(() => jobs.value.filter(j => j.status === 'pending').length)

const completionRate = computed(() =>
  totalJobs.value > 0 ? Math.round(completedCount.value / totalJobs.value * 100) : 0
)

const totalRevenue = computed(() =>
  jobs.value
    .filter(j => ['delivered', 'completed'].includes(j.status))
    .reduce((s, j) => s + Number(j.estimated_cost || 0), 0)
)

const stats = computed(() => [
  {
    label: 'Total Orders', value: totalJobs.value,
    icon: ClipboardList, iconColor: 'text-blue-600', iconBg: 'bg-blue-100 dark:bg-blue-950',
    delta: null,
  },
  {
    label: 'Active', value: activeCount.value,
    icon: Truck, iconColor: 'text-cyan-600', iconBg: 'bg-cyan-100 dark:bg-cyan-950',
    delta: null,
  },
  {
    label: 'Completed', value: completedCount.value,
    icon: CheckCircle2, iconColor: 'text-emerald-600', iconBg: 'bg-emerald-100 dark:bg-emerald-950',
    delta: { value: `${completionRate.value}% rate`, positive: true },
  },
  {
    label: 'Failed', value: failedCount.value,
    icon: AlertTriangle, iconColor: 'text-red-500', iconBg: 'bg-red-100 dark:bg-red-950',
    delta: null,
  },
  {
    label: 'Revenue', value: 'Rp ' + (totalRevenue.value / 1_000_000).toFixed(1) + 'M',
    icon: DollarSign, iconColor: 'text-emerald-600', iconBg: 'bg-emerald-100 dark:bg-emerald-950',
    delta: null,
  },
])

const statusBreakdown = computed(() => [
  { status: 'completed',   label: 'Completed',   count: completedCount.value,                                                        dot: 'bg-emerald-500', bar: 'bg-emerald-500' },
  { status: 'in_transit',  label: 'In Transit',  count: jobs.value.filter(j => j.status === 'in_transit').length,                   dot: 'bg-purple-400',  bar: 'bg-purple-400'  },
  { status: 'picked_up',   label: 'Picked Up',   count: jobs.value.filter(j => j.status === 'picked_up').length,                   dot: 'bg-blue-400',    bar: 'bg-blue-400'    },
  { status: 'pending',     label: 'Pending',     count: pendingCount.value,                                                         dot: 'bg-yellow-400',  bar: 'bg-yellow-400'  },
  { status: 'in_progress', label: 'In Progress', count: jobs.value.filter(j => j.status === 'in_progress').length,                 dot: 'bg-indigo-400',  bar: 'bg-indigo-400'  },
  { status: 'draft',       label: 'Draft',       count: jobs.value.filter(j => j.status === 'draft').length,                       dot: 'bg-gray-400',    bar: 'bg-gray-400'    },
  { status: 'cancelled',   label: 'Cancelled',   count: failedCount.value,                                                         dot: 'bg-red-400',     bar: 'bg-red-400'     },
])

// ── Chart helpers ─────────────────────────────────────────
function isDark() {
  return themeStore.isDark
}

function getChartColors() {
  return {
    grid:      isDark() ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)',
    label:     isDark() ? '#6b7280' : '#9ca3af',
    line:      '#3b82f6',
    lineFill0: isDark() ? 'rgba(59,130,246,0.3)' : 'rgba(59,130,246,0.15)',
    lineFill1: 'rgba(59,130,246,0)',
    bar:       '#10b981',
  }
}

function drawAreaChart() {
  const canvas = areaChartRef.value
  if (!canvas) return
  const ctx = canvas.getContext('2d')
  if (!ctx) return
  const parent = canvas.parentElement!
  canvas.width  = parent.clientWidth
  canvas.height = parent.clientHeight
  const { width: W, height: H } = canvas
  const pad = { top: 16, right: 16, bottom: 28, left: 44 }
  const c = getChartColors()

  const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
  const now  = new Date()
  const data = days.map((day, i) => {
    const d = new Date(now)
    d.setDate(now.getDate() - (6 - i))
    const dateStr = d.toISOString().split('T')[0]
    const dayJobs = jobs.value.filter(j => {
      const created = j.created_at?.split('T')[0]
      return created === dateStr && ['delivered', 'completed'].includes(j.status)
    })
    return { day, revenue: dayJobs.reduce((s, j) => s + Number(j.estimated_cost || 0), 0) }
  })

  const maxVal = Math.max(...data.map(d => d.revenue), 1)
  const scaleX = (i: number) => pad.left + (i / (data.length - 1)) * (W - pad.left - pad.right)
  const scaleY = (v: number) => H - pad.bottom - (v / maxVal) * (H - pad.top - pad.bottom)

  ctx.clearRect(0, 0, W, H)

  for (let i = 0; i <= 4; i++) {
    const y = pad.top + (i / 4) * (H - pad.top - pad.bottom)
    ctx.strokeStyle = c.grid
    ctx.lineWidth = 1
    ctx.beginPath(); ctx.moveTo(pad.left, y); ctx.lineTo(W - pad.right, y); ctx.stroke()
    ctx.fillStyle = c.label
    ctx.font = '11px system-ui, sans-serif'
    ctx.textAlign = 'right'
    const val = maxVal - (i / 4) * maxVal
    ctx.fillText(
      val > 1_000_000 ? (val / 1_000_000).toFixed(1) + 'M' : val > 1000 ? (val / 1000).toFixed(0) + 'K' : val.toFixed(0),
      pad.left - 6, y + 4
    )
  }

  const gradient = ctx.createLinearGradient(0, pad.top, 0, H - pad.bottom)
  gradient.addColorStop(0, c.lineFill0)
  gradient.addColorStop(1, c.lineFill1)
  ctx.beginPath()
  data.forEach((d, i) => i === 0 ? ctx.moveTo(scaleX(i), scaleY(d.revenue)) : ctx.lineTo(scaleX(i), scaleY(d.revenue)))
  ctx.lineTo(scaleX(data.length - 1), H - pad.bottom)
  ctx.lineTo(scaleX(0), H - pad.bottom)
  ctx.closePath()
  ctx.fillStyle = gradient
  ctx.fill()

  ctx.beginPath()
  data.forEach((d, i) => i === 0 ? ctx.moveTo(scaleX(i), scaleY(d.revenue)) : ctx.lineTo(scaleX(i), scaleY(d.revenue)))
  ctx.strokeStyle = c.line
  ctx.lineWidth = 2.5
  ctx.lineJoin = 'round'
  ctx.stroke()

  data.forEach((d, i) => {
    ctx.beginPath()
    ctx.arc(scaleX(i), scaleY(d.revenue), 4, 0, Math.PI * 2)
    ctx.fillStyle = c.line
    ctx.fill()
    ctx.strokeStyle = isDark() ? '#111827' : '#ffffff'
    ctx.lineWidth = 2
    ctx.stroke()
  })

  ctx.fillStyle = c.label
  ctx.font = '11px system-ui, sans-serif'
  ctx.textAlign = 'center'
  data.forEach((d, i) => ctx.fillText(d.day, scaleX(i), H - 8))
}

function drawBarChart() {
  const canvas = barChartRef.value
  if (!canvas) return
  const ctx = canvas.getContext('2d')
  if (!ctx) return
  const parent = canvas.parentElement!
  canvas.width  = parent.clientWidth
  canvas.height = parent.clientHeight
  const { width: W, height: H } = canvas
  const pad = { top: 16, right: 10, bottom: 28, left: 40 }
  const c = getChartColors()

  const hours = Array.from({ length: 12 }, (_, i) => i + 8)
  const today = new Date().toISOString().split('T')[0]
  const data  = hours.map(h => {
    const dayJobs = jobs.value.filter(j => {
      if (!j.created_at) return false
      const d = new Date(j.created_at)
      return d.toISOString().split('T')[0] === today && d.getHours() === h && ['delivered', 'completed'].includes(j.status)
    })
    return { hour: h.toString().padStart(2, '0'), revenue: dayJobs.reduce((s, j) => s + Number(j.estimated_cost || 0), 0) }
  })

  const maxVal = Math.max(...data.map(d => d.revenue), 1)
  const barW   = ((W - pad.left - pad.right) / data.length) * 0.65
  const gap    = (W - pad.left - pad.right) / data.length

  ctx.clearRect(0, 0, W, H)

  for (let i = 0; i <= 4; i++) {
    const y = pad.top + (i / 4) * (H - pad.top - pad.bottom)
    ctx.strokeStyle = c.grid
    ctx.lineWidth = 1
    ctx.beginPath(); ctx.moveTo(pad.left, y); ctx.lineTo(W - pad.right, y); ctx.stroke()
  }

  data.forEach((d, i) => {
    const x    = pad.left + i * gap + (gap - barW) / 2
    const barH = Math.max((d.revenue / maxVal) * (H - pad.top - pad.bottom), d.revenue > 0 ? 4 : 0)
    const y    = H - pad.bottom - barH

    const grad = ctx.createLinearGradient(0, y, 0, H - pad.bottom)
    grad.addColorStop(0, '#10b981')
    grad.addColorStop(1, isDark() ? 'rgba(16,185,129,0.4)' : 'rgba(16,185,129,0.3)')
    ctx.fillStyle = grad
    ctx.beginPath()
    ctx.roundRect(x, y, barW, barH, [3, 3, 0, 0])
    ctx.fill()

    if (i % 2 === 0) {
      ctx.fillStyle = c.label
      ctx.font = '10px system-ui, sans-serif'
      ctx.textAlign = 'center'
      ctx.fillText(d.hour, x + barW / 2, H - 8)
    }
  })
}

// ── Watch theme ───────────────────────────────────────────
watch(() => themeStore.isDark, () => {
  setTimeout(() => { drawAreaChart(); drawBarChart() }, 50)
})

// ── Lifecycle ─────────────────────────────────────────────
let resizeObserver: ResizeObserver | null = null

onMounted(async () => {
  await fetchJobs()
  setTimeout(() => { drawAreaChart(); drawBarChart() }, 100)

  resizeObserver = new ResizeObserver(() => { drawAreaChart(); drawBarChart() })
  if (areaChartRef.value?.parentElement) resizeObserver.observe(areaChartRef.value.parentElement)
  if (barChartRef.value?.parentElement)  resizeObserver.observe(barChartRef.value.parentElement)
})

onUnmounted(() => resizeObserver?.disconnect())

// ── Helpers ───────────────────────────────────────────────
function statusLabel(status: string) {
  const map: Record<string, string> = {
    draft: 'Draft', pending: 'Pending', assigned: 'Assigned',
    in_progress: 'In Progress', picked_up: 'Picked Up',
    in_transit: 'In Transit', delivered: 'Delivered',
    completed: 'Completed', cancelled: 'Cancelled',
  }
  return map[status] ?? status
}

function statusClass(status: string) {
  const map: Record<string, string> = {
    draft:       'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400',
    pending:     'bg-yellow-100 dark:bg-yellow-950 text-yellow-700 dark:text-yellow-400',
    assigned:    'bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-400',
    in_progress: 'bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-400',
    picked_up:   'bg-purple-100 dark:bg-purple-950 text-purple-700 dark:text-purple-400',
    in_transit:  'bg-cyan-100 dark:bg-cyan-950 text-cyan-700 dark:text-cyan-400',
    delivered:   'bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-400',
    completed:   'bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-400',
    cancelled:   'bg-red-100 dark:bg-red-950 text-red-700 dark:text-red-400',
  }
  return map[status] ?? 'bg-gray-100 text-gray-600'
}
</script>