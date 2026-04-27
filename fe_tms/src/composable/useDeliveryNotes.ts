// src/composable/useDeliveryNotes.ts

import { ref } from 'vue'
import api from '@/lib/axios'

// ── Types ──────────────────────────────────────────────────
export interface DeliveryNoteItem {
  id?:          number
  item_id?:     number | null
  item_name:    string
  item_sku?:    string
  uom:          string
  qty:          number
  weight_kg?:   number
  package_type?: string
  batch_no?:    string
  box_count:    number
  notes?:       string
}

export interface DeliveryNote {
  id:                 number
  dn_number:          string
  delivery_order_id:  number
  customer_id:        number
  created_by:         number
  delivery_date:      string
  shipper_name:       string | null
  shipper_address:    string | null
  receiver_name:      string
  receiver_address:   string
  receiver_phone:     string | null
  vehicle_plate:      string | null
  vehicle_type:       string | null
  driver_name:        string | null
  driver_phone:       string | null
  total_packages:     number
  total_weight_kg:    number
  total_volume_m3:    number
  cargo_description:  string | null
  status:             'draft' | 'issued' | 'delivered' | 'returned'
  notes:              string | null
  issued_at:          string | null
  created_at:         string
  items?:             DeliveryNoteItem[]
  customer?:          { id: number; name: string; code: string }
  delivery_order?:    { id: number; do_number: string; status: string }
  created_by_user?:   { id: number; name: string }
}

export interface DeliveryNoteForm {
  delivery_order_id: number | null
  delivery_date:     string
  shipper_name:      string
  shipper_address:   string
  receiver_name:     string
  receiver_address:  string
  receiver_phone:    string
  cargo_description: string
  notes:             string
  items:             DeliveryNoteItem[]
}

// ── Composable ─────────────────────────────────────────────
export function useDeliveryNotes() {
  const notes    = ref<DeliveryNote[]>([])
  const loading  = ref(false)
  const error    = ref<string | null>(null)
  const meta     = ref<any>(null)

  async function fetchNotes(params?: Record<string, any>) {
    loading.value = true
    error.value   = null
    try {
      const res    = await api.get('/tms/delivery-notes', { params })
      const raw    = res.data?.data
      notes.value  = raw?.data ?? raw ?? []
      meta.value   = raw?.meta ?? null
    } catch (e: any) {
      error.value = e.response?.data?.message ?? 'Failed to load delivery notes'
    } finally {
      loading.value = false
    }
  }

  async function getNote(id: number): Promise<DeliveryNote> {
    const res = await api.get(`/tms/delivery-notes/${id}`)
    return res.data?.data ?? res.data
  }

  async function createNote(form: DeliveryNoteForm): Promise<DeliveryNote> {
    const res  = await api.post('/tms/delivery-notes', form)
    const note = res.data?.data ?? res.data
    notes.value.unshift(note)
    return note
  }

  async function updateNote(id: number, form: Partial<DeliveryNoteForm>): Promise<DeliveryNote> {
    const res  = await api.put(`/tms/delivery-notes/${id}`, form)
    const note = res.data?.data ?? res.data
    const idx  = notes.value.findIndex(n => n.id === id)
    if (idx !== -1) notes.value[idx] = { ...notes.value[idx], ...note }
    return note
  }

  async function issueNote(id: number): Promise<void> {
    await api.post(`/tms/delivery-notes/${id}/issue`)
    const idx = notes.value.findIndex(n => n.id === id)
    if (idx !== -1) notes.value[idx].status = 'issued'
  }

  async function deleteNote(id: number): Promise<void> {
    await api.delete(`/tms/delivery-notes/${id}`)
    notes.value = notes.value.filter(n => n.id !== id)
  }

  async function getPrintData(id: number): Promise<DeliveryNote> {
    const res = await api.get(`/tms/delivery-notes/${id}/print`)
    return res.data?.data ?? res.data
  }

  return {
    notes, loading, error, meta,
    fetchNotes, getNote, createNote, updateNote,
    issueNote, deleteNote, getPrintData,
  }
}