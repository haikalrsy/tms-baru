// Centralized mock data — replace with Laravel/Python middleware API later.

export interface Warehouse {
  id: string;
  code: string;
  name: string;
  address: string;
  lat: number;
  lng: number;
  capacity: number;
  used: number;
}

export interface Driver {
  id: string;
  name: string;
  phone: string;
  vehicleId: string | null;
  status: "available" | "on_delivery" | "off_duty";
  currentLat?: number;
  currentLng?: number;
}

export interface Vehicle {
  id: string;
  plate: string;
  type: "Van" | "Truck" | "Motorcycle";
  capacityKg: number;
  status: "active" | "maintenance" | "idle";
}

export interface Delivery {
  id: string;
  orderRef: string;
  customerName: string;
  customerAddress: string;
  customerLat: number;
  customerLng: number;
  warehouseId: string;
  driverId: string | null;
  status: "pending" | "picking" | "packed" | "in_transit" | "delivered" | "failed";
  amount: number;
  createdAt: string;
  scheduledAt: string;
  routePolyline: [number, number][];
}

export interface StockItem {
  id: string;
  sku: string;
  name: string;
  warehouseId: string;
  quantity: number;
  reorderLevel: number;
}

export const warehouses: Warehouse[] = [
  { id: "wh-1", code: "WH-CTR", name: "Central Hub", address: "123 Logistics Park", lat: 40.7128, lng: -74.006, capacity: 12000, used: 8400 },
  { id: "wh-2", code: "WH-NTH", name: "North Depot", address: "55 North Industrial Way", lat: 40.78, lng: -73.95, capacity: 8000, used: 5100 },
  { id: "wh-3", code: "WH-STH", name: "South Depot", address: "201 Harbor Rd", lat: 40.65, lng: -74.05, capacity: 6500, used: 2200 },
];

export const drivers: Driver[] = [
  { id: "drv-1", name: "Marcus Chen", phone: "+1 555 0101", vehicleId: "veh-1", status: "on_delivery", currentLat: 40.74, currentLng: -73.99 },
  { id: "drv-2", name: "Aisha Rahman", phone: "+1 555 0102", vehicleId: "veh-2", status: "on_delivery", currentLat: 40.72, currentLng: -74.02 },
  { id: "drv-3", name: "Diego Santos", phone: "+1 555 0103", vehicleId: "veh-3", status: "available" },
  { id: "drv-4", name: "Yuki Tanaka", phone: "+1 555 0104", vehicleId: null, status: "off_duty" },
  { id: "drv-5", name: "Olivia Brown", phone: "+1 555 0105", vehicleId: "veh-4", status: "on_delivery", currentLat: 40.76, currentLng: -73.97 },
];

export const vehicles: Vehicle[] = [
  { id: "veh-1", plate: "LX-1042", type: "Van", capacityKg: 1500, status: "active" },
  { id: "veh-2", plate: "LX-2055", type: "Truck", capacityKg: 5000, status: "active" },
  { id: "veh-3", plate: "LX-3017", type: "Van", capacityKg: 1500, status: "active" },
  { id: "veh-4", plate: "MC-0891", type: "Motorcycle", capacityKg: 80, status: "active" },
  { id: "veh-5", plate: "LX-4422", type: "Truck", capacityKg: 5000, status: "maintenance" },
];

export const deliveries: Delivery[] = [
  {
    id: "dlv-1001", orderRef: "ERP-SO-88421", customerName: "Acme Corp", customerAddress: "401 Market St",
    customerLat: 40.745, customerLng: -73.985, warehouseId: "wh-1", driverId: "drv-1",
    status: "in_transit", amount: 1240, createdAt: "2026-04-19T08:12:00Z", scheduledAt: "2026-04-19T11:00:00Z",
    routePolyline: [[40.7128, -74.006], [40.725, -73.998], [40.74, -73.99], [40.745, -73.985]],
  },
  {
    id: "dlv-1002", orderRef: "ERP-SO-88422", customerName: "Brightline Studios", customerAddress: "88 Canal St",
    customerLat: 40.717, customerLng: -74.001, warehouseId: "wh-1", driverId: "drv-2",
    status: "in_transit", amount: 860, createdAt: "2026-04-19T08:30:00Z", scheduledAt: "2026-04-19T10:30:00Z",
    routePolyline: [[40.7128, -74.006], [40.72, -74.02], [40.717, -74.001]],
  },
  {
    id: "dlv-1003", orderRef: "ERP-SO-88423", customerName: "Northwind Traders", customerAddress: "12 Park Ave",
    customerLat: 40.77, customerLng: -73.965, warehouseId: "wh-2", driverId: "drv-5",
    status: "in_transit", amount: 2100, createdAt: "2026-04-19T07:55:00Z", scheduledAt: "2026-04-19T10:00:00Z",
    routePolyline: [[40.78, -73.95], [40.76, -73.97], [40.77, -73.965]],
  },
  {
    id: "dlv-1004", orderRef: "ERP-SO-88424", customerName: "Vertex Labs", customerAddress: "9 River Rd",
    customerLat: 40.66, customerLng: -74.04, warehouseId: "wh-3", driverId: null,
    status: "packed", amount: 540, createdAt: "2026-04-19T09:10:00Z", scheduledAt: "2026-04-19T13:00:00Z",
    routePolyline: [],
  },
  {
    id: "dlv-1005", orderRef: "ERP-SO-88425", customerName: "Helio Foods", customerAddress: "76 Pine St",
    customerLat: 40.71, customerLng: -74.012, warehouseId: "wh-1", driverId: null,
    status: "picking", amount: 380, createdAt: "2026-04-19T09:25:00Z", scheduledAt: "2026-04-19T14:00:00Z",
    routePolyline: [],
  },
  {
    id: "dlv-1006", orderRef: "ERP-SO-88419", customerName: "Pioneer Hardware", customerAddress: "33 Elm Way",
    customerLat: 40.69, customerLng: -74.02, warehouseId: "wh-3", driverId: "drv-3",
    status: "delivered", amount: 720, createdAt: "2026-04-19T06:00:00Z", scheduledAt: "2026-04-19T09:00:00Z",
    routePolyline: [[40.65, -74.05], [40.69, -74.02]],
  },
  {
    id: "dlv-1007", orderRef: "ERP-SO-88418", customerName: "Quill & Co", customerAddress: "210 Spruce Ln",
    customerLat: 40.7, customerLng: -73.99, warehouseId: "wh-1", driverId: "drv-1",
    status: "failed", amount: 195, createdAt: "2026-04-19T05:30:00Z", scheduledAt: "2026-04-19T08:30:00Z",
    routePolyline: [],
  },
];

export const stock: StockItem[] = [
  { id: "stk-1", sku: "SKU-A100", name: "Standard Carton 40x30", warehouseId: "wh-1", quantity: 1240, reorderLevel: 200 },
  { id: "stk-2", sku: "SKU-A101", name: "Insulated Cooler", warehouseId: "wh-1", quantity: 88, reorderLevel: 50 },
  { id: "stk-3", sku: "SKU-B220", name: "Pallet Wrap (per roll)", warehouseId: "wh-2", quantity: 540, reorderLevel: 100 },
  { id: "stk-4", sku: "SKU-B221", name: "Heavy-Duty Strap", warehouseId: "wh-2", quantity: 32, reorderLevel: 40 },
  { id: "stk-5", sku: "SKU-C330", name: "Fragile Foam Insert", warehouseId: "wh-3", quantity: 410, reorderLevel: 80 },
];

// Aggregated stats for dashboard
export function computeAdminStats() {
  const today = deliveries.length;
  const active = deliveries.filter((d) => ["picking", "packed", "in_transit"].includes(d.status)).length;
  const completed = deliveries.filter((d) => d.status === "delivered").length;
  const failed = deliveries.filter((d) => d.status === "failed").length;
  const revenue = deliveries.filter((d) => d.status === "delivered").reduce((s, d) => s + d.amount, 0);
  return { today, active, completed, failed, revenue };
}

export function revenuePerDay() {
  const labels = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"];
  return labels.map((day, i) => ({ day, revenue: 4200 + Math.round(Math.sin(i * 1.3) * 800 + i * 350) }));
}

export function revenuePerHour() {
  return Array.from({ length: 12 }).map((_, i) => {
    const hour = 8 + i;
    return { hour: `${hour.toString().padStart(2, "0")}:00`, revenue: Math.round(180 + Math.sin(i * 0.6) * 90 + i * 35) };
  });
}
