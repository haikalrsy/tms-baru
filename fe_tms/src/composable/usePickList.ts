import { ref } from 'vue'
import api from '@/lib/axios'
 
export function usePickLists() {
  const pickLists = ref<any[]>([])
  const loading   = ref(false)
 
  async function fetchPickLists(params?: Record<string, any>) {
    loading.value = true
    try {
      const res   = await api.get('/wms/pick-lists', { params })
      pickLists.value = res.data?.data?.data ?? res.data?.data ?? []
    } finally { loading.value = false }
  }
 
  async function createPickList(payload: any) {
    const res = await api.post('/wms/pick-lists', payload)
    const pl  = res.data?.data ?? res.data
    pickLists.value.unshift(pl)
    return pl
  }
 
  async function completePickList(id: number, items: Array<{ pl_item_id: number; qty_picked: number }>) {
    const res = await api.post(`/wms/pick-lists/${id}/complete`, { items })
    const idx = pickLists.value.findIndex(p => p.id === id)
    if (idx !== -1) pickLists.value[idx].status = 'completed'
    return res.data
  }
 
  return { pickLists, loading, fetchPickLists, createPickList, completePickList }
}
 
 