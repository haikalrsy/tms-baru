// mock-data.ts — Dummy data removed. All data now comes from Laravel API.
// Types kept for backward compatibility with any component that still imports them.

export interface Warehouse {
  id: string
  code: string
  name: string
  address: string
  lat: number
  lng: number
  capacity: number
  used: number
}

export interface Driver {
  id: string
  name: string
  phone: string
  vehicleId: string | null
  status: 'available' | 'on_delivery' | 'off_duty'
  currentLat?: number
  currentLng?: number
}

export interface Vehicle {
  id: string
  plate: string
  type: 'Van' | 'Truck' | 'Motorcycle'
  capacityKg: number
  status: 'active' | 'maintenance' | 'idle'
}

export interface Delivery {
  id: string
  orderRef: string
  customerName: string
  customerAddress: string
  customerLat: number
  customerLng: number
  warehouseId: string
  driverId: string | null
  status: 'pending' | 'picking' | 'packed' | 'in_transit' | 'delivered' | 'failed'
  amount: number
  createdAt: string
  scheduledAt: string
  routePolyline: [number, number][]
}

export interface StockItem {
  id: string
  sku: string
  name: string
  warehouseId: string
  quantity: number
  reorderLevel: number
}

// All data arrays are empty — fetched from API at runtime
export const warehouses: Warehouse[] = []
export const drivers: Driver[]       = []
export const vehicles: Vehicle[]     = []
export const deliveries: Delivery[]  = []
export const stock: StockItem[]      = []

// Helper functions return empty/zero — real data comes from DashboardController
export function computeAdminStats() {
  return { today: 0, active: 0, completed: 0, failed: 0, revenue: 0 }
}

export function revenuePerDay() {
  return [] as { day: string; revenue: number }[]
}

export function revenuePerHour() {
  return [] as { hour: string; revenue: number }[]
}