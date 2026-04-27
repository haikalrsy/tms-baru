<template>
  <div class="flex flex-col gap-6">

    <!-- Header -->
    <div class="flex items-start justify-between">
      <div>
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">Delivery Notes</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">Form dokumen pengiriman barang ke customer.</p>
      </div>
      <button @click="openCreate()"
        class="flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700">
        <Plus class="h-4 w-4" /> Create DN
      </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
      <div v-for="s in stats" :key="s.label"
        class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4 shadow-sm">
        <div class="flex items-center justify-between mb-2">
          <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ s.label }}</p>
          <div :class="`flex h-7 w-7 items-center justify-center rounded-lg ${s.bg}`">
            <component :is="s.icon" :class="`h-3.5 w-3.5 ${s.color}`" />
          </div>
        </div>
        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ s.value }}</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap items-center gap-3">
      <div class="flex flex-1 items-center gap-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 min-w-0">
        <Search class="h-4 w-4 text-gray-400 flex-shrink-0" />
        <input v-model="search" placeholder="Search DN number, customer..."
          class="flex-1 bg-transparent text-sm outline-none text-gray-700 dark:text-gray-300 placeholder:text-gray-400" />
      </div>
      <select v-model="filterStatus"
        class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 text-sm text-gray-700 dark:text-gray-300 outline-none">
        <option value="">All Status</option>
        <option value="draft">Draft</option>
        <option value="issued">Issued</option>
        <option value="delivered">Delivered</option>
        <option value="returned">Returned</option>
      </select>
      <input v-model="filterDateFrom" type="date"
        class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 text-sm text-gray-700 dark:text-gray-300 outline-none" />
      <input v-model="filterDateTo" type="date"
        class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 text-sm text-gray-700 dark:text-gray-300 outline-none" />
      <button @click="applyFilter" :disabled="loading"
        class="flex items-center gap-1.5 rounded-xl border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm text-gray-600 dark:text-gray-400 transition hover:bg-gray-50 dark:hover:bg-gray-800 disabled:opacity-50">
        <RefreshCw :class="['h-3.5 w-3.5', loading && 'animate-spin']" /> Refresh
      </button>
    </div>

    <!-- Table -->
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm overflow-hidden">

      <!-- Loading -->
      <div v-if="loading" class="divide-y divide-gray-50 dark:divide-gray-800">
        <div v-for="i in 5" :key="i" class="flex items-center gap-4 px-4 py-4">
          <div class="h-4 w-28 animate-pulse rounded bg-gray-200 dark:bg-gray-700" />
          <div class="h-4 w-36 animate-pulse rounded bg-gray-200 dark:bg-gray-700 flex-1" />
          <div class="h-4 w-20 animate-pulse rounded bg-gray-200 dark:bg-gray-700" />
          <div class="h-4 w-16 animate-pulse rounded bg-gray-200 dark:bg-gray-700" />
        </div>
      </div>

      <!-- Empty -->
      <div v-else-if="filtered.length === 0"
        class="flex flex-col items-center justify-center py-16 text-gray-400">
        <FileText class="mb-3 h-10 w-10 opacity-20" />
        <p class="text-sm">No delivery notes found</p>
        <button @click="openCreate()" class="mt-3 text-xs text-blue-600 hover:underline">Create first DN</button>
      </div>

      <!-- Table -->
      <table v-else class="w-full text-sm">
        <thead class="border-b border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">DN Number</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Customer</th>
            <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 md:table-cell">Receiver</th>
            <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 md:table-cell">Delivery Date</th>
            <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 lg:table-cell">Driver / Vehicle</th>
            <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 lg:table-cell">Weight</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
          <tr v-for="dn in filtered" :key="dn.id" class="transition hover:bg-gray-50 dark:hover:bg-gray-800">
            <td class="px-4 py-3.5">
              <span class="font-mono text-sm font-semibold text-gray-900 dark:text-gray-100">{{ dn.dn_number }}</span>
              <p v-if="dn.delivery_order" class="text-xs text-gray-400 mt-0.5">{{ dn.delivery_order.do_number }}</p>
            </td>
            <td class="px-4 py-3.5">
              <p class="font-medium text-gray-900 dark:text-gray-100">{{ dn.customer?.name ?? '—' }}</p>
              <p class="text-xs text-gray-400">{{ dn.customer?.code }}</p>
            </td>
            <td class="hidden px-4 py-3.5 md:table-cell">
              <p class="text-gray-900 dark:text-gray-100">{{ dn.receiver_name }}</p>
              <p class="text-xs text-gray-400 line-clamp-1">{{ dn.receiver_address }}</p>
            </td>
            <td class="hidden px-4 py-3.5 md:table-cell">
              <p class="text-gray-700 dark:text-gray-300">{{ formatDate(dn.delivery_date) }}</p>
            </td>
            <td class="hidden px-4 py-3.5 lg:table-cell">
              <p class="text-gray-700 dark:text-gray-300">{{ dn.driver_name ?? '—' }}</p>
              <p class="font-mono text-xs text-gray-400">{{ dn.vehicle_plate ?? '' }}</p>
            </td>
            <td class="hidden px-4 py-3.5 lg:table-cell">
              <span class="tabular-nums text-gray-700 dark:text-gray-300">
                {{ Number(dn.total_weight_kg).toFixed(1) }} kg
              </span>
              <p class="text-xs text-gray-400">{{ dn.total_packages }} pkg</p>
            </td>
            <td class="px-4 py-3.5">
              <span :class="statusClass(dn.status)">
                {{ statusLabel(dn.status) }}
              </span>
            </td>
            <td class="px-4 py-3.5">
              <div class="flex items-center gap-1">
                <!-- Print -->
                <button @click="openPrint(dn)" title="Print"
                  class="flex h-7 w-7 items-center justify-center rounded-lg border border-gray-200 dark:border-gray-700 text-gray-500 transition hover:bg-gray-50 dark:hover:bg-gray-800">
                  <Printer class="h-3.5 w-3.5" />
                </button>
                <!-- Issue (draft only) -->
                <button v-if="dn.status === 'draft'" @click="handleIssue(dn)" title="Issue"
                  class="flex h-7 w-7 items-center justify-center rounded-lg border border-blue-200 dark:border-blue-800 text-blue-600 transition hover:bg-blue-50 dark:hover:bg-blue-950">
                  <Send class="h-3.5 w-3.5" />
                </button>
                <!-- Edit (draft only) -->
                <button v-if="dn.status === 'draft'" @click="openEdit(dn)" title="Edit"
                  class="flex h-7 w-7 items-center justify-center rounded-lg border border-gray-200 dark:border-gray-700 text-gray-500 transition hover:bg-gray-50 dark:hover:bg-gray-800">
                  <Pencil class="h-3.5 w-3.5" />
                </button>
                <!-- Delete (draft only) -->
                <button v-if="dn.status === 'draft'" @click="confirmDelete(dn)" title="Delete"
                  class="flex h-7 w-7 items-center justify-center rounded-lg border border-red-200 dark:border-red-800 text-red-500 transition hover:bg-red-50 dark:hover:bg-red-950">
                  <Trash2 class="h-3.5 w-3.5" />
                </button>
                <!-- View (issued+) -->
                <button v-if="dn.status !== 'draft'" @click="openDetail(dn)" title="View"
                  class="flex h-7 w-7 items-center justify-center rounded-lg border border-gray-200 dark:border-gray-700 text-gray-500 transition hover:bg-gray-50 dark:hover:bg-gray-800">
                  <Eye class="h-3.5 w-3.5" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- ── Modal Form Create / Edit ────────────────────────── -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showForm"
          class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 backdrop-blur-sm"
          @click.self="closeForm">
          <div class="w-full max-w-2xl rounded-2xl bg-white dark:bg-gray-900 shadow-2xl" style="animation: modalIn .18s ease-out">
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 px-6 py-4">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                {{ editingId ? 'Edit Delivery Note' : 'Create Delivery Note' }}
              </h3>
              <button @click="closeForm"
                class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 dark:hover:bg-gray-800">
                <X class="h-4 w-4" />
              </button>
            </div>

            <!-- Body -->
            <div class="max-h-[72vh] overflow-y-auto p-6 space-y-6">

              <!-- DO & Date -->
              <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2 md:col-span-1">
                  <label class="mb-1.5 block text-xs font-semibold text-gray-600 dark:text-gray-400">Delivery Order *</label>
                  <input v-model="form.delivery_order_id" type="number" placeholder="DO ID"
                    class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 dark:text-gray-100" />
                  <p class="mt-1 text-xs text-gray-400">Masukkan ID Delivery Order</p>
                </div>
                <div>
                  <label class="mb-1.5 block text-xs font-semibold text-gray-600 dark:text-gray-400">Delivery Date *</label>
                  <input v-model="form.delivery_date" type="date"
                    class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 dark:text-gray-100" />
                </div>
              </div>

              <!-- Shipper -->
              <div class="space-y-3">
                <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Shipper (Pengirim)</p>
                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label class="mb-1.5 block text-xs font-semibold text-gray-600 dark:text-gray-400">Nama Pengirim</label>
                    <input v-model="form.shipper_name" placeholder="PT. Logistics Indonesia"
                      class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 dark:text-gray-100" />
                  </div>
                  <div>
                    <label class="mb-1.5 block text-xs font-semibold text-gray-600 dark:text-gray-400">Alamat Pengirim</label>
                    <input v-model="form.shipper_address" placeholder="Jl. Industri No. 1"
                      class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 dark:text-gray-100" />
                  </div>
                </div>
              </div>

              <!-- Receiver -->
              <div class="space-y-3">
                <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Receiver (Penerima)</p>
                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label class="mb-1.5 block text-xs font-semibold text-gray-600 dark:text-gray-400">Nama Penerima *</label>
                    <input v-model="form.receiver_name" placeholder="PT. Customer ABC"
                      class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 dark:text-gray-100" />
                  </div>
                  <div>
                    <label class="mb-1.5 block text-xs font-semibold text-gray-600 dark:text-gray-400">No. Telepon</label>
                    <input v-model="form.receiver_phone" placeholder="08xxxxxxxxxx"
                      class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 dark:text-gray-100" />
                  </div>
                  <div class="col-span-2">
                    <label class="mb-1.5 block text-xs font-semibold text-gray-600 dark:text-gray-400">Alamat Pengiriman *</label>
                    <textarea v-model="form.receiver_address" placeholder="Jl. Tujuan No. 1, Kota" rows="2"
                      class="w-full resize-none rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 dark:text-gray-100" />
                  </div>
                </div>
              </div>

              <!-- Items -->
              <div class="space-y-3">
                <div class="flex items-center justify-between">
                  <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Items / Barang</p>
                  <button @click="addItem" type="button"
                    class="flex items-center gap-1 rounded-lg border border-blue-200 dark:border-blue-800 px-2 py-1 text-xs text-blue-600 transition hover:bg-blue-50 dark:hover:bg-blue-950">
                    <Plus class="h-3 w-3" /> Add Item
                  </button>
                </div>

                <!-- Item rows -->
                <div class="space-y-2">
                  <div v-for="(item, idx) in form.items" :key="idx"
                    class="grid grid-cols-12 gap-2 rounded-xl border border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800 p-3">
                    <div class="col-span-12 md:col-span-4">
                      <label class="mb-1 block text-xs text-gray-500">Nama Barang *</label>
                      <input v-model="item.item_name" placeholder="Nama produk"
                        class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-2.5 py-1.5 text-sm outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 dark:text-gray-100" />
                    </div>
                    <div class="col-span-4 md:col-span-2">
                      <label class="mb-1 block text-xs text-gray-500">Qty *</label>
                      <input v-model.number="item.qty" type="number" min="0.01" step="0.01" placeholder="0"
                        class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-2.5 py-1.5 text-sm outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 dark:text-gray-100" />
                    </div>
                    <div class="col-span-4 md:col-span-2">
                      <label class="mb-1 block text-xs text-gray-500">UoM</label>
                      <input v-model="item.uom" placeholder="pcs"
                        class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-2.5 py-1.5 text-sm outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 dark:text-gray-100" />
                    </div>
                    <div class="col-span-4 md:col-span-2">
                      <label class="mb-1 block text-xs text-gray-500">Berat (kg)</label>
                      <input v-model.number="item.weight_kg" type="number" min="0" step="0.001" placeholder="0"
                        class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-2.5 py-1.5 text-sm outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 dark:text-gray-100" />
                    </div>
                    <div class="col-span-4 md:col-span-1">
                      <label class="mb-1 block text-xs text-gray-500">Box</label>
                      <input v-model.number="item.box_count" type="number" min="1" placeholder="1"
                        class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-2.5 py-1.5 text-sm outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 dark:text-gray-100" />
                    </div>
                    <div class="col-span-12 flex items-end justify-end md:col-span-1">
                      <button @click="removeItem(idx)" type="button" :disabled="form.items.length <= 1"
                        class="flex h-8 w-8 items-center justify-center rounded-lg border border-red-200 dark:border-red-800 text-red-500 transition hover:bg-red-50 dark:hover:bg-red-950 disabled:opacity-30">
                        <X class="h-3.5 w-3.5" />
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Totals preview -->
                <div class="flex items-center gap-6 rounded-xl bg-blue-50 dark:bg-blue-950 px-4 py-2.5 text-sm">
                  <div>
                    <span class="text-gray-500 dark:text-gray-400">Total Packages: </span>
                    <span class="font-semibold text-gray-900 dark:text-gray-100">{{ form.items.length }}</span>
                  </div>
                  <div>
                    <span class="text-gray-500 dark:text-gray-400">Total Weight: </span>
                    <span class="font-semibold text-gray-900 dark:text-gray-100">
                      {{ form.items.reduce((s, i) => s + (Number(i.weight_kg) || 0), 0).toFixed(2) }} kg
                    </span>
                  </div>
                  <div>
                    <span class="text-gray-500 dark:text-gray-400">Total Box: </span>
                    <span class="font-semibold text-gray-900 dark:text-gray-100">
                      {{ form.items.reduce((s, i) => s + (Number(i.box_count) || 1), 0) }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Notes -->
              <div>
                <label class="mb-1.5 block text-xs font-semibold text-gray-600 dark:text-gray-400">Catatan</label>
                <textarea v-model="form.notes" placeholder="Instruksi khusus pengiriman..." rows="2"
                  class="w-full resize-none rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 dark:text-gray-100" />
              </div>

              <!-- Error -->
              <div v-if="formError"
                class="rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-sm text-red-600 dark:border-red-800 dark:bg-red-950 dark:text-red-400">
                {{ formError }}
              </div>
            </div>

            <!-- Footer -->
            <div class="flex gap-3 border-t border-gray-100 dark:border-gray-800 px-6 py-4">
              <button @click="closeForm"
                class="flex-1 rounded-xl border border-gray-200 dark:border-gray-700 py-2.5 text-sm font-medium text-gray-600 dark:text-gray-400 transition hover:bg-gray-50 dark:hover:bg-gray-800">
                Cancel
              </button>
              <button @click="submitForm" :disabled="submitting"
                class="flex flex-1 items-center justify-center gap-2 rounded-xl bg-blue-600 py-2.5 text-sm font-medium text-white transition hover:bg-blue-700 disabled:opacity-50">
                <Loader2 v-if="submitting" class="h-4 w-4 animate-spin" />
                {{ editingId ? 'Save Changes' : 'Create Delivery Note' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ── Modal Print Preview ─────────────────────────────── -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="printData"
          class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm">
          <div class="w-full max-w-3xl rounded-2xl bg-white shadow-2xl" style="animation: modalIn .18s ease-out">
            <!-- Print toolbar -->
            <div class="flex items-center justify-between border-b px-6 py-4 print:hidden">
              <h3 class="font-semibold text-gray-900">Delivery Note — {{ printData.dn_number }}</h3>
              <div class="flex items-center gap-2">
                <button @click="doPrint()"
                  class="flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700">
                  <Printer class="h-4 w-4" /> Print
                </button>
                <button @click="printData = null"
                  class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100">
                  <X class="h-4 w-4" />
                </button>
              </div>
            </div>

            <!-- Print content -->
            <div id="print-content" class="max-h-[80vh] overflow-y-auto p-8 print:p-0">
              <!-- DN Header -->
              <div class="mb-6 flex items-start justify-between">
                <div>
                  <h1 class="text-2xl font-bold text-gray-900">SURAT JALAN</h1>
                  <p class="text-sm text-gray-500">Delivery Note</p>
                </div>
                <div class="text-right">
                  <p class="text-lg font-bold text-blue-600">{{ printData.dn_number }}</p>
                  <p class="text-sm text-gray-500">{{ formatDate(printData.delivery_date) }}</p>
                  <span :class="statusClass(printData.status)" class="mt-1 inline-block">
                    {{ statusLabel(printData.status) }}
                  </span>
                </div>
              </div>

              <!-- Shipper & Receiver -->
              <div class="mb-6 grid grid-cols-2 gap-6">
                <div class="rounded-xl border border-gray-200 p-4">
                  <p class="mb-2 text-xs font-bold uppercase tracking-wide text-gray-400">Pengirim</p>
                  <p class="font-semibold text-gray-900">{{ printData.shipper_name ?? '—' }}</p>
                  <p class="text-sm text-gray-500">{{ printData.shipper_address ?? '—' }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 p-4">
                  <p class="mb-2 text-xs font-bold uppercase tracking-wide text-gray-400">Penerima</p>
                  <p class="font-semibold text-gray-900">{{ printData.receiver_name }}</p>
                  <p class="text-sm text-gray-500">{{ printData.receiver_address }}</p>
                  <p v-if="printData.receiver_phone" class="text-sm text-gray-500">{{ printData.receiver_phone }}</p>
                </div>
              </div>

              <!-- Vehicle & Driver -->
              <div class="mb-6 grid grid-cols-3 gap-4">
                <div class="rounded-xl bg-gray-50 p-3">
                  <p class="text-xs font-semibold text-gray-400">Driver</p>
                  <p class="font-medium text-gray-900">{{ printData.driver_name ?? '—' }}</p>
                </div>
                <div class="rounded-xl bg-gray-50 p-3">
                  <p class="text-xs font-semibold text-gray-400">Kendaraan</p>
                  <p class="font-mono font-medium text-gray-900">{{ printData.vehicle_plate ?? '—' }}</p>
                </div>
                <div class="rounded-xl bg-gray-50 p-3">
                  <p class="text-xs font-semibold text-gray-400">Jenis</p>
                  <p class="font-medium text-gray-900 capitalize">{{ printData.vehicle_type ?? '—' }}</p>
                </div>
              </div>

              <!-- Items table -->
              <table class="mb-6 w-full text-sm">
                <thead class="bg-gray-100">
                  <tr>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">No</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">Nama Barang</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">Qty</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">UoM</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">Berat (kg)</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">Box</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                  <tr v-for="(item, i) in printData.items" :key="item.id">
                    <td class="px-3 py-2 text-gray-500">{{ i + 1 }}</td>
                    <td class="px-3 py-2 font-medium text-gray-900">
                      {{ item.item_name }}
                      <span v-if="item.item_sku" class="ml-1 text-xs text-gray-400">[{{ item.item_sku }}]</span>
                    </td>
                    <td class="px-3 py-2 tabular-nums text-gray-700">{{ item.qty }}</td>
                    <td class="px-3 py-2 text-gray-700">{{ item.uom }}</td>
                    <td class="px-3 py-2 tabular-nums text-gray-700">{{ item.weight_kg ?? '—' }}</td>
                    <td class="px-3 py-2 tabular-nums text-gray-700">{{ item.box_count }}</td>
                  </tr>
                </tbody>
                <tfoot class="bg-gray-50">
                  <tr>
                    <td colspan="2" class="px-3 py-2 text-right text-xs font-semibold text-gray-500">Total</td>
                    <td class="px-3 py-2 font-semibold text-gray-900">{{ printData.total_packages }}</td>
                    <td></td>
                    <td class="px-3 py-2 font-semibold text-gray-900 tabular-nums">{{ Number(printData.total_weight_kg).toFixed(2) }}</td>
                    <td></td>
                  </tr>
                </tfoot>
              </table>

              <!-- Notes -->
              <div v-if="printData.notes" class="mb-6 rounded-xl border border-gray-200 p-4">
                <p class="mb-1 text-xs font-semibold text-gray-400">Catatan</p>
                <p class="text-sm text-gray-700">{{ printData.notes }}</p>
              </div>

              <!-- Signature area -->
              <div class="grid grid-cols-3 gap-6 mt-8">
                <div class="text-center">
                  <div class="mb-10 h-px border-b border-dashed border-gray-300"></div>
                  <p class="text-xs font-semibold text-gray-500">Dibuat Oleh</p>
                  <p class="text-sm text-gray-700">{{ printData.created_by_user?.name ?? '—' }}</p>
                </div>
                <div class="text-center">
                  <div class="mb-10 h-px border-b border-dashed border-gray-300"></div>
                  <p class="text-xs font-semibold text-gray-500">Driver / Pengantar</p>
                  <p class="text-sm text-gray-700">{{ printData.driver_name ?? '—' }}</p>
                </div>
                <div class="text-center">
                  <div class="mb-10 h-px border-b border-dashed border-gray-300"></div>
                  <p class="text-xs font-semibold text-gray-500">Penerima</p>
                  <p class="text-sm text-gray-700">{{ printData.receiver_name }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Modal Delete -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="deleteTarget"
          class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 backdrop-blur-sm"
          @click.self="deleteTarget = null">
          <div class="w-full max-w-sm rounded-2xl bg-white dark:bg-gray-900 shadow-2xl" style="animation: modalIn .18s ease-out">
            <div class="p-6 text-center">
              <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-red-100 dark:bg-red-950">
                <Trash2 class="h-6 w-6 text-red-500" />
              </div>
              <h3 class="mb-1 text-lg font-semibold text-gray-900 dark:text-gray-100">Delete Delivery Note?</h3>
              <p class="text-sm text-gray-500">
                <span class="font-medium text-gray-900 dark:text-gray-100">{{ deleteTarget.dn_number }}</span>
                akan dihapus permanen.
              </p>
            </div>
            <div class="flex gap-3 border-t border-gray-100 dark:border-gray-800 px-6 py-4">
              <button @click="deleteTarget = null"
                class="flex-1 rounded-xl border border-gray-200 dark:border-gray-700 py-2.5 text-sm font-medium text-gray-600 transition hover:bg-gray-50 dark:hover:bg-gray-800">
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
        class="fixed bottom-6 right-6 z-50 flex items-center gap-2 rounded-xl bg-gray-900 px-4 py-3 text-sm text-white shadow-lg">
        <CheckCircle2 class="h-4 w-4 text-emerald-400" />
        {{ toast }}
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import {
  Plus, Search, RefreshCw, X, Loader2, Trash2, Pencil,
  FileText, Printer, Send, Eye, CheckCircle2,
  FileCheck, FileClock, FileX, FileWarning,
} from 'lucide-vue-next'
import { useDeliveryNotes, type DeliveryNote, type DeliveryNoteForm, type DeliveryNoteItem } from '@/composable/useDeliveryNotes'

const { notes, loading, error, fetchNotes, createNote, updateNote, issueNote, deleteNote, getPrintData } = useDeliveryNotes()

// ── State ──────────────────────────────────────────────────
const search        = ref('')
const filterStatus  = ref('')
const filterDateFrom = ref('')
const filterDateTo  = ref('')
const showForm      = ref(false)
const editingId     = ref<number | null>(null)
const submitting    = ref(false)
const formError     = ref('')
const toast         = ref('')
const deleteTarget  = ref<DeliveryNote | null>(null)
const printData     = ref<DeliveryNote | null>(null)

// ── Form default ───────────────────────────────────────────
const defaultItem = (): DeliveryNoteItem => ({
  item_name: '', uom: 'pcs', qty: 1, weight_kg: 0, box_count: 1,
})
const emptyForm = (): DeliveryNoteForm => ({
  delivery_order_id: null,
  delivery_date: new Date().toISOString().slice(0, 10),
  shipper_name: '', shipper_address: '',
  receiver_name: '', receiver_address: '', receiver_phone: '',
  cargo_description: '', notes: '',
  items: [defaultItem()],
})
const form = ref<DeliveryNoteForm>(emptyForm())

// ── Stats ──────────────────────────────────────────────────
const stats = computed(() => [
  { label: 'Total', value: notes.value.length, icon: FileText, color: 'text-blue-600', bg: 'bg-blue-100 dark:bg-blue-950' },
  { label: 'Draft', value: notes.value.filter(n => n.status === 'draft').length, icon: FileClock, color: 'text-amber-600', bg: 'bg-amber-100 dark:bg-amber-950' },
  { label: 'Issued', value: notes.value.filter(n => n.status === 'issued').length, icon: FileCheck, color: 'text-emerald-600', bg: 'bg-emerald-100 dark:bg-emerald-950' },
  { label: 'Returned', value: notes.value.filter(n => n.status === 'returned').length, icon: FileX, color: 'text-red-500', bg: 'bg-red-100 dark:bg-red-950' },
])

// ── Filter ─────────────────────────────────────────────────
const filtered = computed(() => {
  let list = notes.value
  if (filterStatus.value) list = list.filter(n => n.status === filterStatus.value)
  if (search.value) {
    const q = search.value.toLowerCase()
    list = list.filter(n =>
      n.dn_number.toLowerCase().includes(q) ||
      (n.customer?.name ?? '').toLowerCase().includes(q) ||
      (n.receiver_name ?? '').toLowerCase().includes(q)
    )
  }
  return list
})

// ── Actions ────────────────────────────────────────────────
async function applyFilter() {
  await fetchNotes({
    status:    filterStatus.value || undefined,
    date_from: filterDateFrom.value || undefined,
    date_to:   filterDateTo.value || undefined,
  })
}

function openCreate() { form.value = emptyForm(); editingId.value = null; formError.value = ''; showForm.value = true }

async function openEdit(dn: DeliveryNote) {
  editingId.value = dn.id
  formError.value = ''
  const detail = await getPrintData(dn.id)
  form.value = {
    delivery_order_id: detail.delivery_order_id,
    delivery_date:     detail.delivery_date?.slice(0, 10) ?? '',
    shipper_name:      detail.shipper_name ?? '',
    shipper_address:   detail.shipper_address ?? '',
    receiver_name:     detail.receiver_name,
    receiver_address:  detail.receiver_address,
    receiver_phone:    detail.receiver_phone ?? '',
    cargo_description: detail.cargo_description ?? '',
    notes:             detail.notes ?? '',
    items:             detail.items?.map(i => ({
      item_id:      i.item_id,
      item_name:    i.item_name,
      item_sku:     i.item_sku,
      uom:          i.uom,
      qty:          Number(i.qty),
      weight_kg:    Number(i.weight_kg ?? 0),
      package_type: i.package_type,
      batch_no:     i.batch_no,
      box_count:    i.box_count,
    })) ?? [defaultItem()],
  }
  showForm.value = true
}

function closeForm() { showForm.value = false; formError.value = '' }

// Items management
function addItem()              { form.value.items.push(defaultItem()) }
function removeItem(idx: number) { form.value.items.splice(idx, 1) }

async function submitForm() {
  if (!form.value.delivery_order_id)   { formError.value = 'Delivery Order wajib diisi.'; return }
  if (!form.value.receiver_name)       { formError.value = 'Nama penerima wajib diisi.'; return }
  if (!form.value.receiver_address)    { formError.value = 'Alamat penerima wajib diisi.'; return }
  if (form.value.items.some(i => !i.item_name || !i.qty)) {
    formError.value = 'Semua item harus memiliki nama dan qty.'
    return
  }
  submitting.value = true; formError.value = ''
  try {
    if (editingId.value) {
      await updateNote(editingId.value, form.value)
      showToast('Delivery note updated')
    } else {
      await createNote(form.value)
      showToast('Delivery note created')
    }
    closeForm()
  } catch (e: any) {
    formError.value = e.response?.data?.message ?? e.message ?? 'Something went wrong.'
  } finally {
    submitting.value = false
  }
}

async function handleIssue(dn: DeliveryNote) {
  if (!confirm(`Issue delivery note ${dn.dn_number}? Status tidak bisa dikembalikan ke draft.`)) return
  try {
    await issueNote(dn.id)
    showToast('Delivery note issued')
  } catch (e: any) {
    showToast(e.response?.data?.message ?? 'Failed to issue')
  }
}

function confirmDelete(dn: DeliveryNote) { deleteTarget.value = dn }

async function handleDelete() {
  if (!deleteTarget.value) return
  submitting.value = true
  try {
    await deleteNote(deleteTarget.value.id)
    showToast('Deleted')
    deleteTarget.value = null
  } catch { showToast('Failed to delete') }
  finally { submitting.value = false }
}

async function openPrint(dn: DeliveryNote) {
  const detail = await getPrintData(dn.id)
  printData.value = detail
}

function openDetail(dn: DeliveryNote) { openPrint(dn) }

function doPrint() {
  window.print()
}

// ── Helpers ────────────────────────────────────────────────
function statusLabel(s: string) {
  return { draft: 'Draft', issued: 'Issued', delivered: 'Delivered', returned: 'Returned' }[s] ?? s
}
function statusClass(s: string) {
  return {
    draft:     'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300',
    issued:    'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
    delivered: 'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300',
    returned:  'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300',
  }[s] ?? 'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-gray-100 text-gray-600'
}
function formatDate(d: string) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
}
function showToast(msg: string) { toast.value = msg; setTimeout(() => toast.value = '', 3000) }

onMounted(() => fetchNotes())
</script>

<style scoped>
@keyframes modalIn {
  from { transform: scale(0.96) translateY(6px); opacity: 0; }
  to   { transform: scale(1) translateY(0); opacity: 1; }
}
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.toast-enter-active, .toast-leave-active { transition: all 0.25s ease; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateY(8px); }

@media print {
  .print\:hidden { display: none !important; }
  body > *:not(#print-content) { display: none; }
}
</style>