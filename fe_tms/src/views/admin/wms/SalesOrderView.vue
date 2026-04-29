<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold tracking-tight">Sales Orders</h1>
        <p class="text-sm text-muted-foreground mt-0.5">Manage outbound sales orders and transfer stocks</p>
      </div>
      <button @click="openCreateSO"
        class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors">
        <Plus :size="16" /> Create Sales Order
      </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div v-for="stat in stats" :key="stat.label" class="rounded-xl border bg-card p-4">
        <div class="flex items-center justify-between mb-2">
          <span class="text-xs text-muted-foreground font-medium uppercase tracking-wide">{{ stat.label }}</span>
          <component :is="stat.icon" :size="16" class="text-muted-foreground" />
        </div>
        <p class="text-2xl font-bold">{{ stat.value }}</p>
        <p class="text-xs text-muted-foreground mt-1">{{ stat.sub }}</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap items-center gap-3">
      <div class="relative flex-1 min-w-[200px] max-w-sm">
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" :size="15" />
        <input v-model="search" placeholder="Search SO number, customer..."
          class="w-full rounded-lg border bg-background pl-9 pr-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring h-9" />
      </div>
      <div class="flex gap-2 flex-wrap">
        <button v-for="f in filters" :key="f.value" @click="activeFilter = f.value"
          :class="['px-3 py-1.5 rounded-lg text-xs font-medium transition-colors border',
            activeFilter === f.value
              ? 'bg-primary text-primary-foreground border-primary'
              : 'bg-background text-muted-foreground border-border hover:border-primary/50']">
          {{ f.label }} <span class="ml-1 opacity-60">{{ f.count }}</span>
        </button>
      </div>
      <button @click="fetchSalesOrders" :disabled="loading"
        class="ml-auto inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs hover:bg-muted transition-colors disabled:opacity-50">
        <RefreshCw :size="13" :class="loading && 'animate-spin'" /> Refresh
      </button>
    </div>

    <!-- Error -->
    <div v-if="error" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600">{{ error }}</div>

    <!-- Table -->
    <div class="rounded-xl border bg-card overflow-hidden">
      <!-- Loading -->
      <div v-if="loading" class="divide-y">
        <div v-for="i in 5" :key="i" class="flex items-center gap-4 px-4 py-3.5">
          <div class="h-4 w-28 animate-pulse rounded bg-muted" />
          <div class="h-4 flex-1 animate-pulse rounded bg-muted" />
          <div class="h-4 w-20 animate-pulse rounded bg-muted" />
          <div class="h-4 w-16 animate-pulse rounded bg-muted" />
        </div>
      </div>

      <!-- Empty -->
      <div v-else-if="filteredOrders.length === 0"
        class="flex flex-col items-center justify-center py-16 text-muted-foreground">
        <Package :size="32" class="mb-2 opacity-30" />
        <p class="text-sm">No sales orders found</p>
      </div>

      <table v-else class="w-full text-sm">
        <thead>
          <tr class="border-b bg-muted/40">
            <th class="px-4 py-3 text-left font-medium text-muted-foreground">SO Number</th>
            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Customer</th>
            <th class="px-4 py-3 text-left font-medium text-muted-foreground hidden md:table-cell">Items</th>
            <th class="px-4 py-3 text-left font-medium text-muted-foreground hidden lg:table-cell">Date</th>
            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Status</th>
            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-border">
          <tr v-for="so in filteredOrders" :key="so.id"
            class="hover:bg-muted/30 transition-colors cursor-pointer"
            @click="selectedSO = so">
            <td class="px-4 py-3"><span class="font-mono font-medium text-primary">{{ so.so_number }}</span></td>
            <td class="px-4 py-3">
              <p class="font-medium">{{ so.customer?.name ?? so.customer_name ?? '—' }}</p>
              <p class="text-xs text-muted-foreground">{{ so.customer?.email ?? '' }}</p>
            </td>
            <td class="px-4 py-3 hidden md:table-cell text-muted-foreground">{{ so.items?.length ?? 0 }} items</td>
            <td class="px-4 py-3 hidden lg:table-cell text-muted-foreground text-xs">
              {{ so.created_at ? new Date(so.created_at).toLocaleDateString('id-ID', { day:'2-digit', month:'short', year:'numeric' }) : '—' }}
            </td>
            <td class="px-4 py-3">
              <span :class="['inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium', statusClass(so.status)]">
                <span class="h-1.5 w-1.5 rounded-full" :class="statusDot(so.status)" />
                {{ so.status }}
              </span>
            </td>
            <td class="px-4 py-3" @click.stop>
              <div class="flex items-center gap-2">
                <button v-if="so.status === 'confirmed'"
                  @click="openTransferStock(so)"
                  class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-blue-500/10 text-blue-600 hover:bg-blue-500/20 text-xs font-medium transition-colors">
                  <ArrowRightLeft :size="12" /> Transfer Stock
                </button>
                <button @click="selectedSO = so"
                  class="p-1.5 rounded-lg hover:bg-muted text-muted-foreground transition-colors">
                  <Eye :size="14" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <div class="px-4 py-3 border-t text-xs text-muted-foreground flex justify-between">
        <span>Showing {{ filteredOrders.length }} of {{ salesOrders.length }} orders</span>
        <span v-if="pagination.last_page > 1">Page {{ pagination.current_page }} / {{ pagination.last_page }}</span>
      </div>
    </div>

    <!-- ───── CREATE SO MODAL ───── -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showCreateSO" class="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-foreground/40 backdrop-blur-sm" @click="showCreateSO = false" />
          <div class="relative w-full max-w-2xl bg-card rounded-2xl shadow-2xl border overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b">
              <div>
                <h2 class="font-semibold text-lg">Create Sales Order</h2>
                <p class="text-xs text-muted-foreground">ERP integration ready — input manual sementara.</p>
              </div>
              <button @click="showCreateSO = false" class="p-2 rounded-lg hover:bg-muted"><X :size="16" /></button>
            </div>
            <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
              <div class="rounded-xl border bg-muted/20 p-4 space-y-3">
                <h3 class="text-sm font-semibold flex items-center gap-2"><Users :size="14" /> Customer</h3>
                <div class="grid grid-cols-2 gap-3">
                  <div class="col-span-2">
                    <label class="text-xs font-medium text-muted-foreground">Customer Name *</label>
                    <input v-model="soForm.customer_name" placeholder="PT Maju Bersama" class="input-field mt-1" />
                  </div>
                  <div>
                    <label class="text-xs font-medium text-muted-foreground">Delivery Date</label>
                    <input v-model="soForm.delivery_date" type="date" class="input-field mt-1" />
                  </div>
                  <div>
                    <label class="text-xs font-medium text-muted-foreground">Notes</label>
                    <input v-model="soForm.notes" placeholder="Optional notes" class="input-field mt-1" />
                  </div>
                </div>
              </div>

              <div class="rounded-xl border bg-muted/20 p-4 space-y-3">
                <h3 class="text-sm font-semibold flex items-center gap-2"><Boxes :size="14" /> Items</h3>
                <div v-for="(item, i) in soForm.items" :key="i" class="flex gap-2 items-end">
                  <div class="flex-1">
                    <label class="text-xs text-muted-foreground">Item Name</label>
                    <input v-model="item.name" placeholder="Item name" class="input-field mt-1" />
                  </div>
                  <div class="w-20">
                    <label class="text-xs text-muted-foreground">Qty</label>
                    <input v-model.number="item.qty_ordered" type="number" min="1" class="input-field mt-1" />
                  </div>
                  <div class="w-24">
                    <label class="text-xs text-muted-foreground">UOM</label>
                    <select v-model="item.uom" class="input-field mt-1">
                      <option>pcs</option><option>kg</option><option>box</option><option>pallet</option>
                    </select>
                  </div>
                  <button @click="soForm.items.splice(i, 1)" class="p-2 text-destructive hover:bg-destructive/10 rounded-lg mb-0.5">
                    <Trash2 :size="14" />
                  </button>
                </div>
                <button @click="soForm.items.push({ name: '', qty_ordered: 1, uom: 'pcs' })"
                  class="w-full py-2 rounded-lg border border-dashed text-xs text-muted-foreground hover:border-primary hover:text-primary transition-colors flex items-center justify-center gap-1">
                  <Plus :size="12" /> Add Item
                </button>
              </div>

              <div v-if="soFormError" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-600">{{ soFormError }}</div>
            </div>
            <div class="px-6 py-4 border-t flex justify-end gap-2">
              <button @click="showCreateSO = false" class="px-4 py-2 rounded-lg border text-sm hover:bg-muted">Cancel</button>
              <button @click="submitSO" :disabled="soSubmitting"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-primary text-primary-foreground text-sm hover:bg-primary/90 disabled:opacity-50">
                <Loader2 v-if="soSubmitting" :size="14" class="animate-spin" /> Create Order
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ───── TRANSFER STOCK MODAL ───── -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showTransfer" class="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-foreground/40 backdrop-blur-sm" @click="showTransfer = false" />
          <div class="relative w-full max-w-lg bg-card rounded-2xl shadow-2xl border overflow-hidden">

            <!-- Step indicator -->
            <div class="flex border-b">
              <div v-for="(step, i) in transferSteps" :key="i"
                :class="['flex-1 py-3 px-4 text-xs font-medium flex items-center gap-2 transition-colors',
                  transferStep === i ? 'bg-primary/10 text-primary' :
                  transferStep > i ? 'text-green-600 bg-green-500/5' : 'text-muted-foreground']">
                <div :class="['h-5 w-5 rounded-full flex items-center justify-center text-[10px] font-bold shrink-0',
                  transferStep > i ? 'bg-green-500 text-white' :
                  transferStep === i ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground']">
                  <Check v-if="transferStep > i" :size="10" /><span v-else>{{ i + 1 }}</span>
                </div>
                <span class="hidden sm:block">{{ step }}</span>
              </div>
            </div>

            <div class="p-6">
              <!-- Step 0: Origin warehouse -->
              <div v-if="transferStep === 0" class="space-y-4">
                <div>
                  <h2 class="font-semibold">Select Origin Warehouse</h2>
                  <p class="text-xs text-muted-foreground mt-0.5">Where are the items being transferred from?</p>
                </div>
                <div class="space-y-2 max-h-64 overflow-y-auto">
                  <div v-if="warehousesLoading" class="py-8 text-center text-muted-foreground text-xs">Loading warehouses...</div>
                  <button v-for="wh in warehouseOptions" :key="wh.id" @click="transferForm.originWarehouse = wh"
                    :class="['w-full flex items-start gap-3 p-3 rounded-xl border text-left transition-all',
                      transferForm.originWarehouse?.id === wh.id ? 'border-primary bg-primary/5' : 'hover:border-primary/40 hover:bg-muted/40']">
                    <div class="h-8 w-8 rounded-lg bg-blue-500/10 flex items-center justify-center shrink-0">
                      <Warehouse :size="16" class="text-blue-500" />
                    </div>
                    <div class="flex-1">
                      <p class="font-medium text-sm">{{ wh.name }}</p>
                      <p class="text-xs text-muted-foreground">{{ wh.code }} · {{ wh.city }}</p>
                    </div>
                    <CheckCircle2 v-if="transferForm.originWarehouse?.id === wh.id" :size="18" class="text-primary shrink-0" />
                  </button>
                </div>
              </div>

              <!-- Step 1: Destination warehouse -->
              <div v-if="transferStep === 1" class="space-y-4">
                <div>
                  <h2 class="font-semibold">Select Destination Warehouse</h2>
                  <p class="text-xs text-muted-foreground mt-0.5">Where to transfer stock for SO <span class="font-mono text-primary">{{ transferSO?.so_number }}</span></p>
                </div>
                <div class="space-y-2 max-h-64 overflow-y-auto">
                  <button v-for="wh in warehouseOptions.filter(w => w.id !== transferForm.originWarehouse?.id)" :key="wh.id"
                    @click="transferForm.destWarehouse = wh"
                    :class="['w-full flex items-start gap-3 p-3 rounded-xl border text-left transition-all',
                      transferForm.destWarehouse?.id === wh.id ? 'border-primary bg-primary/5' : 'hover:border-primary/40 hover:bg-muted/40']">
                    <div class="h-8 w-8 rounded-lg bg-blue-500/10 flex items-center justify-center shrink-0">
                      <Warehouse :size="16" class="text-blue-500" />
                    </div>
                    <div class="flex-1">
                      <p class="font-medium text-sm">{{ wh.name }}</p>
                      <p class="text-xs text-muted-foreground">{{ wh.code }} · {{ wh.city }}</p>
                    </div>
                    <CheckCircle2 v-if="transferForm.destWarehouse?.id === wh.id" :size="18" class="text-primary shrink-0" />
                  </button>
                </div>
              </div>

              <!-- Step 2: Select driver — semua driver bisa dipilih -->
              <div v-if="transferStep === 2" class="space-y-4">
                <div>
                  <h2 class="font-semibold">Assign Driver</h2>
                  <p class="text-xs text-muted-foreground mt-0.5">Select driver for this transfer</p>
                </div>
                <div v-if="driversLoading" class="py-8 text-center text-muted-foreground text-xs">Loading drivers...</div>
                <div v-else-if="driverOptions.length === 0" class="py-8 text-center text-muted-foreground text-xs">No drivers found</div>
                <div v-else class="space-y-2 max-h-64 overflow-y-auto">
                  <button v-for="driver in driverOptions" :key="driver.id"
                    @click="transferForm.driver = driver"
                    :class="['w-full flex items-center gap-3 p-3 rounded-xl border text-left transition-all',
                      transferForm.driver?.id === driver.id ? 'border-primary bg-primary/5' : 'hover:border-primary/40 hover:bg-muted/40']">
                    <div class="h-9 w-9 rounded-full bg-primary/10 flex items-center justify-center shrink-0 font-semibold text-primary text-sm">
                      {{ driver.name.split(' ').map((s: string) => s[0]).slice(0,2).join('') }}
                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="font-medium text-sm">{{ driver.name }}</p>
                      <p class="text-xs text-muted-foreground truncate">{{ driver.email }}</p>
                    </div>
                    <span :class="['text-[10px] px-2 py-0.5 rounded-full font-medium shrink-0',
                      driver.availability_status === 'available' ? 'bg-green-500/10 text-green-600' :
                      driver.availability_status === 'on_trip' ? 'bg-amber-500/10 text-amber-600' :
                      'bg-muted text-muted-foreground']">
                      {{ driver.availability_status ?? 'off_duty' }}
                    </span>
                    <CheckCircle2 v-if="transferForm.driver?.id === driver.id" :size="18" class="text-primary shrink-0" />
                  </button>
                </div>
              </div>

              <!-- Step 3: Confirm -->
              <div v-if="transferStep === 3" class="space-y-4">
                <h2 class="font-semibold">Confirm Transfer Stock</h2>
                <div class="rounded-xl border bg-muted/30 divide-y text-sm">
                  <div class="flex justify-between px-4 py-2.5"><span class="text-muted-foreground">Sales Order</span><span class="font-mono font-medium text-primary">{{ transferSO?.so_number }}</span></div>
                  <div class="flex justify-between px-4 py-2.5"><span class="text-muted-foreground">Origin</span><span class="font-medium">{{ transferForm.originWarehouse?.name }}</span></div>
                  <div class="flex justify-between px-4 py-2.5"><span class="text-muted-foreground">Destination</span><span class="font-medium">{{ transferForm.destWarehouse?.name }}</span></div>
                  <div class="flex justify-between px-4 py-2.5"><span class="text-muted-foreground">Driver</span><span class="font-medium">{{ transferForm.driver?.name }}</span></div>
                  <div class="flex justify-between px-4 py-2.5"><span class="text-muted-foreground">Items</span><span class="font-medium">{{ transferSO?.items?.length ?? 0 }} items</span></div>
                </div>
                <div class="rounded-xl border border-blue-200 bg-blue-50 dark:bg-blue-500/10 dark:border-blue-500/30 px-4 py-3 text-xs text-blue-700 dark:text-blue-400 flex gap-2">
                  <AlertTriangle :size="14" class="shrink-0 mt-0.5" />
                  Transfer akan langsung masuk ke status picking. Stock di origin warehouse akan dikurangi otomatis.
                </div>
                <div v-if="transferError" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-600">{{ transferError }}</div>
              </div>
            </div>

            <div class="px-6 py-4 border-t flex justify-between gap-2">
              <button @click="transferStep > 0 ? transferStep-- : showTransfer = false"
                class="px-4 py-2 rounded-lg border text-sm hover:bg-muted transition-colors">
                {{ transferStep === 0 ? 'Cancel' : 'Back' }}
              </button>
              <button @click="nextTransferStep" :disabled="!canProceedTransfer || transferSubmitting"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-primary text-primary-foreground text-sm hover:bg-primary/90 disabled:opacity-50">
                <Loader2 v-if="transferSubmitting" :size="14" class="animate-spin" />
                {{ transferStep === 3 ? 'Confirm & Dispatch' : 'Next' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ───── SO DETAIL DRAWER ───── -->
    <Teleport to="body">
      <Transition name="slide">
        <div v-if="selectedSO" class="fixed inset-0 z-50 flex justify-end">
          <div class="absolute inset-0 bg-foreground/30" @click="selectedSO = null" />
          <div class="relative w-full max-w-md bg-card h-full shadow-2xl border-l flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b">
              <div>
                <p class="font-mono font-semibold text-primary">{{ selectedSO.so_number }}</p>
                <p class="text-xs text-muted-foreground">
                  {{ selectedSO.created_at ? new Date(selectedSO.created_at).toLocaleDateString('id-ID') : '—' }}
                </p>
              </div>
              <button @click="selectedSO = null" class="p-2 rounded-lg hover:bg-muted"><X :size="16" /></button>
            </div>
            <div class="flex-1 overflow-y-auto p-6 space-y-5">
              <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-2">Customer</p>
                <p class="font-medium">{{ selectedSO.customer?.name ?? selectedSO.customer_name ?? '—' }}</p>
              </div>
              <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-2">Status</p>
                <span :class="['inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium', statusClass(selectedSO.status)]">
                  <span class="h-1.5 w-1.5 rounded-full" :class="statusDot(selectedSO.status)" />
                  {{ selectedSO.status }}
                </span>
              </div>
              <div v-if="selectedSO.items?.length">
                <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-2">Items</p>
                <div class="space-y-2">
                  <div v-for="item in selectedSO.items" :key="item.id"
                    class="flex justify-between items-center py-2 border-b last:border-0 text-sm">
                    <span>{{ item.item?.name ?? item.name ?? '—' }}</span>
                    <span class="text-muted-foreground">{{ item.qty_ordered }} {{ item.uom ?? 'pcs' }}</span>
                  </div>
                </div>
              </div>
            </div>
            <div class="p-4 border-t">
              <button v-if="selectedSO.status === 'confirmed'"
                @click="openTransferStock(selectedSO); selectedSO = null"
                class="w-full py-2.5 rounded-lg bg-blue-500 text-white text-sm font-medium hover:bg-blue-600 transition-colors flex items-center justify-center gap-2">
                <ArrowRightLeft :size="15" /> Create Transfer Stock
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, reactive, onMounted } from 'vue'
import {
  Plus, Search, Eye, X, Package, Boxes, Warehouse, Users,
  ArrowRightLeft, Check, CheckCircle2, AlertTriangle, Trash2,
  ClipboardList, TrendingUp, Truck, RefreshCw, Loader2,
} from 'lucide-vue-next'
import api from '@/lib/axios'

// ── Types ───────────────────────────────────────────────────────────────────
interface SalesOrder {
  id: number
  so_number: string
  status: string
  customer?: { name: string; email: string }
  customer_name?: string
  items?: any[]
  created_at?: string
}
interface WarehouseOption { id: number; name: string; code: string; city: string }
interface DriverOption { id: number; name: string; email: string; availability_status: string }

// ── State ────────────────────────────────────────────────────────────────────
const search       = ref('')
const activeFilter = ref('all')
const loading      = ref(false)
const error        = ref('')
const salesOrders  = ref<SalesOrder[]>([])
const pagination   = reactive({ current_page: 1, last_page: 1 })
const selectedSO   = ref<SalesOrder | null>(null)
const showCreateSO = ref(false)
const soSubmitting = ref(false)
const soFormError  = ref('')
const showTransfer    = ref(false)
const transferStep    = ref(0)
const transferSO      = ref<SalesOrder | null>(null)
const transferSubmitting = ref(false)
const transferError   = ref('')
const warehouseOptions = ref<WarehouseOption[]>([])
const warehousesLoading = ref(false)
const driverOptions    = ref<DriverOption[]>([])
const driversLoading   = ref(false)

const transferSteps = ['Origin', 'Destination', 'Assign Driver', 'Confirm']

const transferForm = reactive<{
  originWarehouse: WarehouseOption | null
  destWarehouse: WarehouseOption | null
  driver: DriverOption | null
}>({ originWarehouse: null, destWarehouse: null, driver: null })

const soForm = reactive({
  customer_name: '', delivery_date: '', notes: '',
  items: [{ name: '', qty_ordered: 1, uom: 'pcs' }],
})

// ── Fetch data ───────────────────────────────────────────────────────────────
async function fetchSalesOrders() {
  loading.value = true
  error.value   = ''
  try {
    const res = await api.get('/wms/sales-orders', { params: { per_page: 50 } })
    const raw = res.data?.data ?? res.data
    salesOrders.value     = raw?.data ?? raw ?? []
    pagination.current_page = raw?.current_page ?? 1
    pagination.last_page    = raw?.last_page ?? 1
  } catch (e: any) {
    error.value = e.response?.data?.message ?? 'Failed to load sales orders'
  } finally {
    loading.value = false
  }
}

async function fetchWarehouses() {
  warehousesLoading.value = true
  try {
    const res = await api.get('/wms/warehouses')
    const raw = res.data?.data ?? res.data
    warehouseOptions.value = Array.isArray(raw) ? raw : (raw?.data ?? [])
  } catch { warehouseOptions.value = [] }
  finally { warehousesLoading.value = false }
}

async function fetchDrivers() {
  driversLoading.value = true
  try {
    // GET semua driver — admin endpoint
    const res = await api.get('/admin/drivers/online')
    driverOptions.value = res.data?.data ?? []
  } catch { driverOptions.value = [] }
  finally { driversLoading.value = false }
}

// ── Computed ─────────────────────────────────────────────────────────────────
const stats = computed(() => [
  { label: 'Total SO',    value: salesOrders.value.length,                                             sub: 'All time',       icon: ClipboardList },
  { label: 'Confirmed',   value: salesOrders.value.filter(s => s.status === 'confirmed').length,       sub: 'Ready to transfer', icon: CheckCircle2 },
  { label: 'In Transfer', value: salesOrders.value.filter(s => s.status === 'in_transfer').length,     sub: 'Moving',         icon: Truck },
  { label: 'Completed',   value: salesOrders.value.filter(s => s.status === 'completed').length,       sub: 'Done',           icon: TrendingUp },
])

const filters = computed(() => [
  { label: 'All',         value: 'all',         count: salesOrders.value.length },
  { label: 'Pending',     value: 'pending',     count: salesOrders.value.filter(s => s.status === 'pending').length },
  { label: 'Confirmed',   value: 'confirmed',   count: salesOrders.value.filter(s => s.status === 'confirmed').length },
  { label: 'In Transfer', value: 'in_transfer', count: salesOrders.value.filter(s => s.status === 'in_transfer').length },
  { label: 'Completed',   value: 'completed',   count: salesOrders.value.filter(s => s.status === 'completed').length },
])

const filteredOrders = computed(() => {
  let list = salesOrders.value
  if (activeFilter.value !== 'all') list = list.filter(s => s.status === activeFilter.value)
  if (search.value) {
    const q = search.value.toLowerCase()
    list = list.filter(s =>
      s.so_number?.toLowerCase().includes(q) ||
      (s.customer?.name ?? s.customer_name ?? '').toLowerCase().includes(q)
    )
  }
  return list
})

const canProceedTransfer = computed(() => {
  if (transferStep.value === 0) return !!transferForm.originWarehouse
  if (transferStep.value === 1) return !!transferForm.destWarehouse
  if (transferStep.value === 2) return !!transferForm.driver
  return true
})

// ── Methods ──────────────────────────────────────────────────────────────────
function statusClass(status: string) {
  const map: Record<string, string> = {
    pending:     'bg-muted text-muted-foreground',
    confirmed:   'bg-blue-500/10 text-blue-600',
    in_transfer: 'bg-amber-500/10 text-amber-600',
    completed:   'bg-green-500/10 text-green-600',
    cancelled:   'bg-destructive/10 text-destructive',
  }
  return map[status] ?? 'bg-muted text-muted-foreground'
}

function statusDot(status: string) {
  const map: Record<string, string> = {
    pending: 'bg-muted-foreground', confirmed: 'bg-blue-500',
    in_transfer: 'bg-amber-500',    completed: 'bg-green-500', cancelled: 'bg-destructive',
  }
  return map[status] ?? 'bg-muted-foreground'
}

function openCreateSO() {
  Object.assign(soForm, { customer_name: '', delivery_date: '', notes: '', items: [{ name: '', qty_ordered: 1, uom: 'pcs' }] })
  soFormError.value = ''
  showCreateSO.value = true
}

async function submitSO() {
  if (!soForm.customer_name.trim()) { soFormError.value = 'Customer name required'; return }
  soSubmitting.value = true
  soFormError.value  = ''
  try {
    await api.post('/wms/sales-orders', soForm)
    showCreateSO.value = false
    await fetchSalesOrders()
  } catch (e: any) {
    soFormError.value = e.response?.data?.message ?? 'Failed to create order'
  } finally {
    soSubmitting.value = false
  }
}

async function openTransferStock(so: SalesOrder) {
  transferSO.value   = so
  transferStep.value = 0
  transferError.value = ''
  Object.assign(transferForm, { originWarehouse: null, destWarehouse: null, driver: null })
  showTransfer.value = true
  await fetchWarehouses()
}

async function nextTransferStep() {
  if (transferStep.value === 1) {
    // Fetch drivers saat mau ke step driver
    await fetchDrivers()
  }

  if (transferStep.value < 3) {
    transferStep.value++
    return
  }

  // Step 3: confirm & submit
  transferSubmitting.value = true
  transferError.value      = ''
  try {
    await api.post('/wms/transfer-stocks', {
      sales_order_id:           transferSO.value!.id,
      origin_warehouse_id:      transferForm.originWarehouse!.id,
      destination_warehouse_id: transferForm.destWarehouse!.id,
      driver_id:                transferForm.driver!.id,
    })
    showTransfer.value = false
    await fetchSalesOrders()
  } catch (e: any) {
    transferError.value = e.response?.data?.message ?? 'Failed to create transfer'
  } finally {
    transferSubmitting.value = false
  }
}

onMounted(fetchSalesOrders)
</script>

<style>
.input-field {
  display: flex; height: 36px; width: 100%;
  border-radius: 0.5rem; border: 1px solid hsl(var(--border));
  background-color: hsl(var(--background));
  padding: 0.375rem 0.75rem; font-size: 0.875rem; color: hsl(var(--foreground));
}
.input-field::placeholder { color: hsl(var(--muted-foreground)); }
.input-field:focus { outline: none; box-shadow: 0 0 0 2px hsl(var(--ring)); }
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; transform: scale(0.95); }
.slide-enter-active, .slide-leave-active { transition: transform 0.3s ease; }
.slide-enter-from, .slide-leave-to { transform: translateX(100%); }
</style>