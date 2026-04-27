<template>
  <div class="flex flex-col gap-6">
    <div>
      <h1 class="font-display text-2xl font-semibold tracking-tight">Account management</h1>
      <p class="text-sm text-muted-foreground">Approve, reject, or suspend driver and admin accounts.</p>
    </div>

    <DataTable
      :columns="columns"
      :data="filtered"
      :row-key="(r) => r.id.toString()"
      :search-accessor="(r) => `${r.name} ${r.email} ${r.phone ?? ''}`"
      search-placeholder="Search by name, email, phone..."
      :empty-message="loading ? 'Loading...' : 'No accounts found.'"
    >
      <template #filters>
        <div class="flex flex-wrap items-center gap-1 rounded-lg border bg-surface-2 p-1">
          <button
            v-for="s in filterButtons"
            :key="s"
            @click="statusFilter = s"
            :class="[
              'rounded-md px-3 py-1 text-xs font-medium capitalize transition-colors',
              statusFilter === s
                ? 'bg-primary text-primary-foreground'
                : 'text-muted-foreground hover:text-foreground',
            ]"
          >
            {{ s }}
          </button>
        </div>
      </template>
    </DataTable>

    <!-- Reject Modal -->
    <Teleport to="body">
      <div v-if="rejectTarget" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <div class="w-full max-w-sm rounded-xl bg-background border p-6 shadow-xl">
          <h2 class="text-base font-semibold mb-1">Reject Account</h2>
          <p class="text-sm text-muted-foreground mb-4">
            Reject <strong>{{ rejectTarget.name }}</strong>? Kamu bisa tambahkan alasan (opsional).
          </p>
          <textarea
            v-model="rejectReason"
            rows="3"
            placeholder="Alasan penolakan..."
            class="w-full rounded-lg border bg-surface-2 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary resize-none mb-4"
          />
          <div class="flex justify-end gap-2">
            <button
              @click="rejectTarget = null; rejectReason = ''"
              class="rounded-md border px-4 py-1.5 text-sm hover:bg-muted transition-colors"
            >
              Batal
            </button>
            <button
              @click="confirmReject"
              :disabled="actionLoading"
              class="rounded-md bg-destructive text-destructive-foreground px-4 py-1.5 text-sm font-medium hover:bg-destructive/90 transition-colors disabled:opacity-50"
            >
              {{ actionLoading ? 'Rejecting...' : 'Reject' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, h } from 'vue'
import api from '@/lib/axios'
import DataTable, { type DataTableColumn } from '@/components/DataTable.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import { Check, X, Pause } from 'lucide-vue-next'
import { toast } from 'vue-sonner'

type AccountStatus = 'pending' | 'approved' | 'rejected' | 'suspended'

interface AccountRow {
  id: number
  name: string
  email: string
  phone: string | null
  role: string
  account_status: AccountStatus
  created_at: string
}

const rows          = ref<AccountRow[]>([])
const loading       = ref(true)
const actionLoading = ref(false)
const statusFilter  = ref<AccountStatus | 'all'>('all')
const rejectTarget  = ref<AccountRow | null>(null)
const rejectReason  = ref('')

const filterButtons: (AccountStatus | 'all')[] = ['all', 'pending', 'approved', 'rejected', 'suspended']

// ── Load accounts ──────────────────────────────────────────────────────────────
const load = async () => {
  loading.value = true
  try {
    const { data } = await api.get('/admin/accounts', { params: { per_page: 100 } })
    rows.value = data.data.data ?? []
  } catch (err: any) {
    toast.error(err.response?.data?.message ?? 'Gagal memuat data akun.')
  } finally {
    loading.value = false
  }
}

onMounted(load)

// ── Actions ────────────────────────────────────────────────────────────────────
const approve = async (user: AccountRow) => {
  actionLoading.value = true
  try {
    await api.post(`/admin/accounts/${user.id}/approve`)
    toast.success(`Akun ${user.name} disetujui. Email notifikasi dikirim.`)
    load()
  } catch (err: any) {
    toast.error(err.response?.data?.message ?? 'Gagal approve akun.')
  } finally {
    actionLoading.value = false
  }
}

const openReject = (user: AccountRow) => {
  rejectTarget.value = user
  rejectReason.value = ''
}

const confirmReject = async () => {
  if (!rejectTarget.value) return
  actionLoading.value = true
  try {
    await api.post(`/admin/accounts/${rejectTarget.value.id}/reject`, {
      reason: rejectReason.value || null,
    })
    toast.success(`Akun ${rejectTarget.value.name} ditolak.`)
    rejectTarget.value = null
    rejectReason.value = ''
    load()
  } catch (err: any) {
    toast.error(err.response?.data?.message ?? 'Gagal reject akun.')
  } finally {
    actionLoading.value = false
  }
}

const suspend = async (user: AccountRow) => {
  actionLoading.value = true
  try {
    await api.post(`/admin/accounts/${user.id}/suspend`)
    toast.success(`Akun ${user.name} disuspend.`)
    load()
  } catch (err: any) {
    toast.error(err.response?.data?.message ?? 'Gagal suspend akun.')
  } finally {
    actionLoading.value = false
  }
}

// ── Filter ─────────────────────────────────────────────────────────────────────
const filtered = computed(() =>
  statusFilter.value === 'all'
    ? rows.value
    : rows.value.filter((r) => r.account_status === statusFilter.value)
)

// ── Button styles ──────────────────────────────────────────────────────────────
const btnCls = (variant: 'success' | 'destructive' | 'default') => {
  const base = 'inline-flex items-center gap-1 rounded-md border px-2.5 py-1 text-xs font-medium transition-colors disabled:opacity-50'
  if (variant === 'success')     return `${base} border-success/40 text-success hover:bg-success/10`
  if (variant === 'destructive') return `${base} border-destructive/40 text-destructive hover:bg-destructive/10`
  return `${base} border-border hover:bg-muted`
}

// ── Columns ────────────────────────────────────────────────────────────────────
const columns: DataTableColumn<AccountRow>[] = [
  {
    key: 'name', header: 'Name', sortable: true,
    sortValue: (r) => r.name || r.email,
    cell: (r) => h('div', { class: 'flex flex-col' }, [
      h('span', { class: 'font-medium text-foreground' }, r.name || '—'),
      h('span', { class: 'text-xs text-muted-foreground' }, r.email),
    ]),
  },
  {
    key: 'phone', header: 'Phone',
    cell: (r) => h('span', { class: 'text-muted-foreground' }, r.phone || '—'),
  },
  {
    key: 'role', header: 'Role', sortable: true,
    sortValue: (r) => r.role,
    cell: (r) => h('span', { class: 'capitalize' }, r.role),
  },
  {
    key: 'status', header: 'Status', sortable: true,
    sortValue: (r) => r.account_status,
    cell: (r) => h(StatusBadge, { status: r.account_status }),
  },
  {
    key: 'created', header: 'Registered', sortable: true,
    sortValue: (r) => r.created_at,
    cell: (r) => h('span', { class: 'text-muted-foreground' },
      new Date(r.created_at).toLocaleDateString('id-ID', {
        day: '2-digit', month: 'short', year: 'numeric',
      })
    ),
  },
  {
    key: 'actions', header: 'Actions',
    cell: (r) => h('div', { class: 'flex items-center gap-1.5' }, [

      // Approve — hanya pending
      (r.account_status === 'pending') &&
        h('button', {
          class: btnCls('success'),
          disabled: actionLoading.value,
          onClick: () => approve(r),
        }, [h(Check, { class: 'h-3.5 w-3.5' }), 'Approve']),

      // Reject — hanya pending
      (r.account_status === 'pending') &&
        h('button', {
          class: btnCls('destructive'),
          disabled: actionLoading.value,
          onClick: () => openReject(r),
        }, [h(X, { class: 'h-3.5 w-3.5' }), 'Reject']),

      // Suspend — hanya driver yang approved (admin tidak bisa di-suspend)
      (r.account_status === 'approved' && r.role !== 'admin') &&
        h('button', {
          class: btnCls('default'),
          disabled: actionLoading.value,
          onClick: () => suspend(r),
        }, [h(Pause, { class: 'h-3.5 w-3.5' }), 'Suspend']),

      // Suspended — tidak ada aksi (harus daftar ulang)
      (r.account_status === 'suspended') &&
        h('span', { class: 'text-xs text-muted-foreground italic' }, 'Daftar ulang'),

    ].filter(Boolean)),
  },
]
</script>