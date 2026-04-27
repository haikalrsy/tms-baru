import { ref } from 'vue'
import api from '@/lib/axios'
 
export function useGoodsReceipts() {
  const goodsReceipts = ref<any[]>([])
  const loading       = ref(false)
 
  async function fetchGoodsReceipts(params?: Record<string, any>) {
    loading.value = true
    try {
      const res = await api.get('/wms/goods-receipts', { params })
      goodsReceipts.value = res.data?.data?.data ?? res.data?.data ?? []
    } finally { loading.value = false }
  }
 
  async function createGoodsReceipt(payload: any) {
    const res = await api.post('/wms/goods-receipts', payload)
    const gr  = res.data?.data
    if (gr) goodsReceipts.value.unshift(gr)
    return gr
  }
 
  async function receiveItems(id: number, items: any[]) {
    const res = await api.post(`/wms/goods-receipts/${id}/receive`, { items })
    return res.data
  }
 
  async function putaway(id: number, items: any[]) {
    const res = await api.post(`/wms/goods-receipts/${id}/putaway`, { items })
    return res.data
  }
 
  return { goodsReceipts, loading, fetchGoodsReceipts, createGoodsReceipt, receiveItems, putaway }
}
 