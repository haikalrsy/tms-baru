import { ref } from 'vue'
import api from '@/lib/axios'
 
export interface Stock {
  id:           number
  item_id:      number
  rack_id:      number
  qty:          number
  reserved_qty: number
  batch_no:     string | null
  expiry_date:  string | null
  item: { id: number; sku: string; name: string; uom: string }
  rack: { id: number; code: string; full_code: string; zone: { name: string; warehouse: { name: string } } }
}
 
export function useStocks() {
  const stocks   = ref<Stock[]>([])
  const summary  = ref<any[]>([])
  const lowStock = ref<any[]>([])
  const movements = ref<any[]>([])
  const loading  = ref(false)
 
  async function fetchStocks(params?: Record<string, any>) {
    loading.value = true
    try {
      const res = await api.get('/wms/stocks', { params })
      stocks.value = res.data?.data?.data ?? res.data?.data ?? []
    } finally { loading.value = false }
  }
 
  async function fetchSummary(params?: Record<string, any>) {
    const res = await api.get('/wms/stocks/summary', { params })
    summary.value = res.data?.data ?? []
    return summary.value
  }
 
  async function fetchLowStock() {
    const res = await api.get('/wms/stocks/low-stock')
    lowStock.value = res.data?.data ?? []
    return lowStock.value
  }
 
  async function fetchMovements(params?: Record<string, any>) {
    loading.value = true
    try {
      const res = await api.get('/wms/stocks/movements', { params })
      movements.value = res.data?.data?.data ?? res.data?.data ?? []
    } finally { loading.value = false }
  }
 
  async function adjustStock(payload: {
    item_id: number; rack_id: number; qty: number
    type: 'adjustment' | 'opname'; notes?: string; batch_no?: string
  }) {
    const res = await api.post('/wms/stocks/adjust', payload)
    return res.data
  }
 
  return { stocks, summary, lowStock, movements, loading, fetchStocks, fetchSummary, fetchLowStock, fetchMovements, adjustStock }
}