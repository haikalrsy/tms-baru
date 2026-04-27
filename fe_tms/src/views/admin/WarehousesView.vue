<template>
  <div class="flex flex-col gap-6">
    <!-- Header -->
    <div class="flex items-start justify-between">
      <div>
        <h1 class="font-display text-2xl font-semibold tracking-tight">Warehouses</h1>
        <p class="text-sm text-muted-foreground">
          Network of fulfillment locations with geo-coordinates ready for routing.
        </p>
      </div>
      <button
        @click="openForm()"
        class="flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition hover:opacity-90"
      >
        <Plus class="h-4 w-4" />
        Add Warehouse
      </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
      <div v-for="stat in statsCards" :key="stat.label"
        class="rounded-xl border bg-card p-4 shadow-sm">
        <div class="flex items-center justify-between mb-2">
          <p class="text-xs font-medium text-muted-foreground">{{ stat.label }}</p>
          <div :class="`flex h-7 w-7 items-center justify-center rounded-lg ${stat.bg}`">
            <component :is="stat.icon" :class="`h-3.5 w-3.5 ${stat.color}`" />
          </div>
        </div>
        <p class="text-2xl font-bold">{{ stat.value }}</p>
        <p class="text-xs text-muted-foreground mt-0.5">{{ stat.sub }}</p>
      </div>
    </div>

    <!-- Error -->
    <div v-if="error"
      class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600 dark:border-red-800 dark:bg-red-950 dark:text-red-400">
      {{ error }}
    </div>

    <!-- Table card -->
    <div class="rounded-xl border bg-card shadow-sm overflow-hidden">

      <!-- Toolbar -->
      <div class="flex items-center gap-3 border-b px-4 py-3">
        <div class="flex flex-1 items-center gap-2 rounded-lg border bg-background px-3 py-1.5">
          <Search class="h-4 w-4 text-muted-foreground flex-shrink-0" />
          <input
            v-model="search"
            placeholder="Search warehouses..."
            class="flex-1 bg-transparent text-sm outline-none placeholder:text-muted-foreground"
          />
        </div>
        <select v-model="statusFilter"
          class="rounded-lg border bg-background px-3 py-1.5 text-sm outline-none">
          <option value="">All Status</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
        <button @click="reload" :disabled="loading"
          class="flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-sm transition hover:bg-accent disabled:opacity-50">
          <RefreshCw :class="['h-3.5 w-3.5', loading && 'animate-spin']" />
          Refresh
        </button>
      </div>

      <!-- Loading skeleton -->
      <div v-if="loading" class="divide-y">
        <div v-for="i in 5" :key="i" class="flex items-center gap-4 px-4 py-3.5">
          <div class="h-4 w-16 animate-pulse rounded bg-muted" />
          <div class="h-4 w-40 animate-pulse rounded bg-muted" />
          <div class="h-4 flex-1 animate-pulse rounded bg-muted" />
          <div class="h-4 w-24 animate-pulse rounded bg-muted" />
          <div class="h-4 w-20 animate-pulse rounded bg-muted" />
        </div>
      </div>

      <!-- Empty -->
      <div v-else-if="filtered.length === 0"
        class="flex flex-col items-center justify-center py-16 text-muted-foreground">
        <Building2 class="mb-3 h-10 w-10 opacity-20" />
        <p class="text-sm">No warehouses found</p>
        <button @click="openForm()" class="mt-3 text-xs text-primary hover:underline">
          Add your first warehouse
        </button>
      </div>

      <!-- Table -->
      <table v-else class="w-full text-sm">
        <thead class="border-b bg-muted/30">
          <tr>
            <th v-for="col in columns" :key="col.key"
              :class="['px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground', col.class ?? '']">
              <button v-if="col.sortable" @click="toggleSort(col.key)"
                class="flex items-center gap-1 hover:text-foreground transition">
                {{ col.label }}<ArrowUpDown class="h-3 w-3" />
              </button>
              <span v-else>{{ col.label }}</span>
            </th>
          </tr>
        </thead>
        <tbody class="divide-y">
          <tr v-for="wh in sorted" :key="wh.id" class="transition hover:bg-muted/30">

            <!-- Code -->
            <td class="px-4 py-3.5">
              <span class="font-mono text-sm font-semibold">{{ wh.code }}</span>
            </td>

            <!-- Name -->
            <td class="px-4 py-3.5">
              <p class="font-medium">{{ wh.name }}</p>
              <p v-if="wh.city" class="text-xs text-muted-foreground">{{ wh.city }}</p>
            </td>

            <!-- Address -->
            <td class="hidden px-4 py-3.5 md:table-cell max-w-[200px]">
              <span class="line-clamp-1 text-muted-foreground text-xs">{{ wh.address || '—' }}</span>
            </td>

            <!-- Coordinates -->
            <td class="hidden px-4 py-3.5 lg:table-cell">
              <span v-if="wh.latitude && wh.longitude"
                class="font-mono text-xs tabular-nums text-muted-foreground">
                {{ Number(wh.latitude).toFixed(4) }}, {{ Number(wh.longitude).toFixed(4) }}
              </span>
              <span v-else class="text-muted-foreground">—</span>
            </td>

            <!-- Layout -->
            <td class="hidden px-4 py-3.5 md:table-cell">
              <div class="flex items-center gap-1 text-xs text-muted-foreground">
                <LayoutGrid class="h-3.5 w-3.5" />
                {{ wh.total_zones ?? 0 }} zones / {{ wh.total_racks ?? 0 }} racks
              </div>
            </td>

            <!-- Utilization -->
            <td class="px-4 py-3.5">
              <div class="flex items-center gap-2">
                <div class="h-1.5 w-20 overflow-hidden rounded-full bg-muted">
                  <div
                    :class="[
                      'h-full rounded-full transition-all',
                      (wh.utilization ?? 0) > 80 ? 'bg-red-500' :
                      (wh.utilization ?? 0) > 50 ? 'bg-amber-500' : 'bg-primary'
                    ]"
                    :style="{ width: `${Math.min(wh.utilization ?? 0, 100)}%` }"
                  />
                </div>
                <span class="text-xs tabular-nums text-muted-foreground">
                  {{ wh.utilization ?? 0 }}%
                </span>
              </div>
            </td>

            <!-- Status -->
            <td class="px-4 py-3.5">
              <span :class="[
                'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold',
                wh.status === 'active'
                  ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300'
                  : 'bg-muted text-muted-foreground'
              ]">
                {{ wh.status === 'active' ? 'Active' : 'Inactive' }}
              </span>
            </td>

            <!-- Actions -->
            <td class="px-4 py-3.5">
              <div class="flex items-center gap-1">
                <button @click="openForm(wh)"
                  class="flex h-7 w-7 items-center justify-center rounded-lg border transition hover:bg-accent">
                  <Pencil class="h-3.5 w-3.5" />
                </button>
                <button @click="confirmDelete(wh)"
                  class="flex h-7 w-7 items-center justify-center rounded-lg border border-red-200 text-red-500 transition hover:bg-red-50 dark:border-red-800 dark:hover:bg-red-950">
                  <Trash2 class="h-3.5 w-3.5" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Footer -->
      <div v-if="!loading && filtered.length > 0"
        class="flex items-center justify-between border-t px-4 py-3 text-xs text-muted-foreground">
        <span>{{ filtered.length }} warehouse{{ filtered.length !== 1 ? 's' : '' }}</span>
        <span>Updated: {{ lastUpdated }}</span>
      </div>
    </div>

    <!-- ── Modal Form ──────────────────────────────────────── -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showForm"
          class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 backdrop-blur-sm"
          @click.self="closeForm">
          <div class="modal-box w-full max-w-lg">
            <div class="flex items-center justify-between border-b px-6 py-4">
              <h3 class="text-lg font-semibold">
                {{ isEdit ? 'Edit Warehouse' : 'Add Warehouse' }}
              </h3>
              <button @click="closeForm"
                class="flex h-8 w-8 items-center justify-center rounded-lg transition hover:bg-accent">
                <X class="h-4 w-4" />
              </button>
            </div>

            <div class="max-h-[65vh] overflow-y-auto p-6">
              <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                  <label class="field-label">Warehouse Name *</label>
                  <input v-model="form.name" placeholder="Main Warehouse" class="field-input" />
                </div>
                <div>
                  <label class="field-label">Code *</label>
                  <input v-model="form.code" placeholder="WH-001" class="field-input font-mono"
                    @input="form.code = (form.code ?? '').toUpperCase()" />
                </div>
                <div>
                  <label class="field-label">City</label>
                  <input v-model="form.city" placeholder="Jakarta" class="field-input" />
                </div>
                <div class="col-span-2">
                  <label class="field-label">Address</label>
                  <textarea v-model="form.address" placeholder="Jl. Example No. 1" rows="2"
                    class="field-input resize-none" />
                </div>
                <div>
                  <label class="field-label">Latitude</label>
                  <input v-model="form.latitude" placeholder="-6.2088" type="number" step="any"
                    class="field-input font-mono" />
                </div>
                <div>
                  <label class="field-label">Longitude</label>
                  <input v-model="form.longitude" placeholder="106.8456" type="number" step="any"
                    class="field-input font-mono" />
                </div>
                <div>
                  <label class="field-label">Phone</label>
                  <input v-model="form.phone" placeholder="021-1234567" class="field-input" />
                </div>
                <div>
                  <label class="field-label">PIC</label>
                  <input v-model="form.pic_name" placeholder="Person in Charge" class="field-input" />
                </div>
                <div>
                  <label class="field-label">Status</label>
                  <select v-model="form.status" class="field-input">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                  </select>
                </div>
              </div>

              <div v-if="formError"
                class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-2.5 text-sm text-red-600 dark:border-red-800 dark:bg-red-950 dark:text-red-400">
                {{ formError }}
              </div>
            </div>

            <div class="flex gap-3 border-t px-6 py-4">
              <button @click="closeForm"
                class="flex-1 rounded-xl border py-2.5 text-sm font-medium transition hover:bg-accent">
                Cancel
              </button>
              <button @click="submitForm" :disabled="submitting"
                class="flex flex-1 items-center justify-center gap-2 rounded-xl bg-primary py-2.5 text-sm font-medium text-primary-foreground transition hover:opacity-90 disabled:opacity-50">
                <Loader2 v-if="submitting" class="h-4 w-4 animate-spin" />
                {{ isEdit ? 'Save Changes' : 'Create Warehouse' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ── Modal Delete ────────────────────────────────────── -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="deleteTarget"
          class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 backdrop-blur-sm"
          @click.self="deleteTarget = null">
          <div class="modal-box w-full max-w-sm">
            <div class="p-6 text-center">
              <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-red-100 dark:bg-red-950">
                <Trash2 class="h-6 w-6 text-red-500" />
              </div>
              <h3 class="mb-1 text-lg font-semibold">Delete Warehouse?</h3>
              <p class="text-sm text-muted-foreground">
                <span class="font-medium text-foreground">{{ deleteTarget.name }}</span>
                will be permanently deleted.
              </p>
            </div>
            <div class="flex gap-3 border-t px-6 py-4">
              <button @click="deleteTarget = null"
                class="flex-1 rounded-xl border py-2.5 text-sm font-medium transition hover:bg-accent">
                Cancel
              </button>
              <button @click="handleDelete" :disabled="submitting"
                class="flex flex-1 items-center justify-center gap-2 rounded-xl bg-red-500 py-2.5 text-sm font-medium text-white transition hover:bg-red-600 disabled:opacity-50">
                <Loader2 v-if="submitting" class="h-4 w-4 animate-spin" />
                Delete
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Toast -->
    <Transition name="toast">
      <div v-if="toast"
        class="fixed bottom-6 right-6 z-50 flex items-center gap-2 rounded-xl bg-foreground px-4 py-3 text-sm text-background shadow-lg">
        <CheckCircle2 class="h-4 w-4 text-emerald-400" />
        {{ toast }}
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import {
  Plus, Search, Pencil, Trash2, X, Loader2, RefreshCw,
  Building2, CheckCircle2, ArrowUpDown, LayoutGrid,
  Warehouse as WarehouseIcon, CheckCircle, XCircle,
} from 'lucide-vue-next'
import { useWarehouses, type Warehouse, type WarehouseForm } from '@/composable/useWarehouses'

const {
  warehouses, loading, error,
  fetchWarehouses, createWarehouse, updateWarehouse, deleteWarehouse,
} = useWarehouses()

// ── State ──────────────────────────────────────────────────
const search        = ref('')
const statusFilter  = ref('')
const sortKey       = ref<string | null>(null)
const sortAsc       = ref(true)
const showForm      = ref(false)
const isEdit        = ref(false)
const submitting    = ref(false)
const formError     = ref('')
const toast         = ref('')
const deleteTarget  = ref<Warehouse | null>(null)
const lastUpdated   = ref('—')
const editingId     = ref<number | null>(null)

const emptyForm = (): WarehouseForm => ({
  name: '', code: '', address: '', city: '',
  latitude: '', longitude: '', phone: '', pic_name: '', status: 'active',
})
const form = ref<WarehouseForm>(emptyForm())

// ── Stats ──────────────────────────────────────────────────
const statsCards = computed(() => [
  {
    label: 'Total', value: warehouses.value.length,
    sub: 'All locations', icon: WarehouseIcon,
    color: 'text-blue-600', bg: 'bg-blue-100 dark:bg-blue-950',
  },
  {
    label: 'Active', value: warehouses.value.filter(w => w.status === 'active').length,
    sub: 'Operational', icon: CheckCircle,
    color: 'text-emerald-600', bg: 'bg-emerald-100 dark:bg-emerald-950',
  },
  {
    label: 'Inactive', value: warehouses.value.filter(w => w.status !== 'active').length,
    sub: 'Not operational', icon: XCircle,
    color: 'text-gray-500', bg: 'bg-gray-100 dark:bg-gray-800',
  },
  {
    label: 'Avg Utilization',
    value: warehouses.value.length
      ? Math.round(warehouses.value.reduce((s, w) => s + (w.utilization ?? 0), 0) / warehouses.value.length) + '%'
      : '—',
    sub: 'Across all', icon: LayoutGrid,
    color: 'text-amber-600', bg: 'bg-amber-100 dark:bg-amber-950',
  },
])

// ── Columns ────────────────────────────────────────────────
const columns = [
  { key: 'code',        label: 'Code',        sortable: true },
  { key: 'name',        label: 'Name',        sortable: true },
  { key: 'address',     label: 'Address',     sortable: false, class: 'hidden md:table-cell' },
  { key: 'coordinates', label: 'Coordinates', sortable: false, class: 'hidden lg:table-cell' },
  { key: 'layout',      label: 'Layout',      sortable: false, class: 'hidden md:table-cell' },
  { key: 'utilization', label: 'Utilization', sortable: true },
  { key: 'status',      label: 'Status',      sortable: true },
  { key: 'actions',     label: '',            sortable: false },
]

// ── Filter & Sort ──────────────────────────────────────────
const filtered = computed(() => {
  let list = warehouses.value
  if (statusFilter.value) list = list.filter(w => w.status === statusFilter.value)
  if (search.value) {
    const q = search.value.toLowerCase()
    list = list.filter(w =>
      w.name.toLowerCase().includes(q) ||
      w.code.toLowerCase().includes(q) ||
      (w.address ?? '').toLowerCase().includes(q) ||
      (w.city ?? '').toLowerCase().includes(q)
    )
  }
  return list
})

const sorted = computed(() => {
  if (!sortKey.value) return filtered.value
  return [...filtered.value].sort((a, b) => {
    let av: any = (a as any)[sortKey.value!]
    let bv: any = (b as any)[sortKey.value!]
    if (typeof av === 'string') av = av.toLowerCase()
    if (typeof bv === 'string') bv = bv.toLowerCase()
    return sortAsc.value ? (av > bv ? 1 : -1) : (av < bv ? 1 : -1)
  })
})

function toggleSort(key: string) {
  sortKey.value === key ? (sortAsc.value = !sortAsc.value) : (sortKey.value = key, sortAsc.value = true)
}

// ── Form ───────────────────────────────────────────────────
function openForm(wh?: Warehouse) {
  isEdit.value    = !!wh
  editingId.value = wh?.id ?? null
  formError.value = ''
  form.value = wh ? {
    name:      wh.name,
    code:      wh.code,
    address:   wh.address   ?? '',
    city:      wh.city      ?? '',
    latitude:  String(wh.latitude  ?? ''),
    longitude: String(wh.longitude ?? ''),
    phone:     wh.phone     ?? '',
    pic_name:  wh.pic_name  ?? '',
    status:    wh.status,
  } : emptyForm()
  showForm.value = true
}

function closeForm() {
  showForm.value  = false
  formError.value = ''
  editingId.value = null
}

async function submitForm() {
  if (!form.value.name?.trim() || !form.value.code?.trim()) {
    formError.value = 'Name and code are required.'
    return
  }
  submitting.value = true
  formError.value  = ''
  try {
    if (isEdit.value && editingId.value) {
      await updateWarehouse(editingId.value, form.value)
      showToast('Warehouse updated')
    } else {
      await createWarehouse(form.value)
      showToast('Warehouse created')
    }
    closeForm()
  } catch (e: any) {
    formError.value = e.message ?? 'Something went wrong.'
  } finally {
    submitting.value = false
  }
}

// ── Delete ─────────────────────────────────────────────────
function confirmDelete(wh: Warehouse) { deleteTarget.value = wh }

async function handleDelete() {
  if (!deleteTarget.value) return
  submitting.value = true
  try {
    await deleteWarehouse(deleteTarget.value.id)
    showToast('Warehouse deleted')
    deleteTarget.value = null
  } catch {
    showToast('Failed to delete')
  } finally {
    submitting.value = false
  }
}

// ── Helpers ────────────────────────────────────────────────
async function reload() {
  await fetchWarehouses()
  lastUpdated.value = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
}

function showToast(msg: string) {
  toast.value = msg
  setTimeout(() => (toast.value = ''), 3000)
}

onMounted(reload)
</script>

<style scoped>
@reference "../../styles.css";

.field-label {
  @apply mb-1.5 block text-xs font-semibold text-muted-foreground;
}
.field-input {
  @apply w-full rounded-xl border bg-background px-3 py-2.5 text-sm outline-none
         focus:ring-2 focus:ring-ring focus:border-transparent
         placeholder:text-muted-foreground;
}
.modal-box {
  @apply rounded-2xl bg-card shadow-2xl;
  animation: modalIn 0.18s ease-out;
}
@keyframes modalIn {
  from { transform: scale(0.96) translateY(6px); opacity: 0; }
  to   { transform: scale(1) translateY(0); opacity: 1; }
}
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.toast-enter-active, .toast-leave-active { transition: all 0.25s ease; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateY(8px); }
</style>