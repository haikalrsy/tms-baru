// src/composable/useWarehouses.ts
// Path sesuai import di WarehousesView.vue: @/composable/useWarehouses

import { ref, computed } from 'vue'
import api from '@/lib/axios'

// ── Types ──────────────────────────────────────────────────
export interface Warehouse {
  id:          number
  name:        string
  code:        string
  address:     string | null
  city:        string | null
  latitude:    number | null
  longitude:   number | null
  phone:       string | null
  pic_name:    string | null
  status:      'active' | 'inactive'
  capacity:    number
  used:        number
  utilization: number
  total_zones: number
  total_racks: number
  created_at:  string
  updated_at:  string
}

export interface WarehouseForm {
  name:      string
  code:      string
  address:   string
  city:      string
  latitude:  string
  longitude: string
  phone:     string
  pic_name:  string
  status:    'active' | 'inactive'
}

// ── Composable ─────────────────────────────────────────────
export function useWarehouses() {
  const warehouses = ref<Warehouse[]>([])
  const loading    = ref(false)
  const error      = ref<string | null>(null)

  // GET /api/wms/warehouses
  async function fetchWarehouses(params?: Record<string, string>) {
    loading.value = true
    error.value   = null
    try {
      const res = await api.get('/wms/warehouses', { params })
      // Backend bisa return { success, data: [...] } atau langsung array
      const raw = res.data
      warehouses.value = raw?.data ?? raw ?? []
    } catch (e: any) {
      error.value = e.response?.data?.message ?? 'Failed to load warehouses'
      warehouses.value = []
    } finally {
      loading.value = false
    }
  }

  // POST /api/wms/warehouses
  async function createWarehouse(form: WarehouseForm): Promise<Warehouse> {
    const payload = buildPayload(form)
    const res = await api.post('/wms/warehouses', payload)
    const wh  = res.data?.data ?? res.data
    warehouses.value.unshift(wh)
    return wh
  }

  // PUT /api/wms/warehouses/{id}
  async function updateWarehouse(id: number, form: Partial<WarehouseForm>): Promise<Warehouse> {
    const payload = buildPayload(form)
    const res = await api.put(`/wms/warehouses/${id}`, payload)
    const wh  = res.data?.data ?? res.data
    const idx = warehouses.value.findIndex(w => w.id === id)
    if (idx !== -1) warehouses.value[idx] = { ...warehouses.value[idx], ...wh }
    return wh
  }

  // DELETE /api/wms/warehouses/{id}
  async function deleteWarehouse(id: number): Promise<void> {
    await api.delete(`/wms/warehouses/${id}`)
    warehouses.value = warehouses.value.filter(w => w.id !== id)
  }

  // GET /api/wms/warehouses/{id}
  async function getWarehouse(id: number): Promise<Warehouse> {
    const res = await api.get(`/wms/warehouses/${id}`)
    return res.data?.data ?? res.data
  }

  // GET /api/wms/warehouses/{id}/zones
  async function getWarehouseZones(id: number) {
    const res = await api.get(`/wms/warehouses/${id}/zones`)
    return res.data?.data ?? res.data ?? []
  }

  // GET /api/wms/warehouses/{id}/stocks
  async function getWarehouseStocks(id: number, params?: Record<string, any>) {
    const res = await api.get(`/wms/warehouses/${id}/stocks`, { params })
    return res.data?.data ?? res.data ?? []
  }

  // ── Computed helpers ─────────────────────────────────────
  const activeWarehouses   = computed(() => warehouses.value.filter(w => w.status === 'active'))
  const inactiveWarehouses = computed(() => warehouses.value.filter(w => w.status !== 'active'))
  const totalRacks         = computed(() => warehouses.value.reduce((s, w) => s + (w.total_racks ?? 0), 0))
  const avgUtilization     = computed(() => {
    if (!warehouses.value.length) return 0
    return Math.round(warehouses.value.reduce((s, w) => s + (w.utilization ?? 0), 0) / warehouses.value.length)
  })

  return {
    // State
    warehouses,
    loading,
    error,
    // Actions
    fetchWarehouses,
    createWarehouse,
    updateWarehouse,
    deleteWarehouse,
    getWarehouse,
    getWarehouseZones,
    getWarehouseStocks,
    // Computed
    activeWarehouses,
    inactiveWarehouses,
    totalRacks,
    avgUtilization,
  }
}

// ── Internal helper ────────────────────────────────────────
function buildPayload(form: Partial<WarehouseForm>): Record<string, any> {
  return {
    name:      form.name,
    code:      form.code,
    address:   form.address   || null,
    city:      form.city      || null,
    latitude:  form.latitude  ? parseFloat(form.latitude)  : null,
    longitude: form.longitude ? parseFloat(form.longitude) : null,
    phone:     form.phone     || null,
    pic_name:  form.pic_name  || null,
    status:    form.status    ?? 'active',
  }
}