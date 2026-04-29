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
      <div v-for="stat in statsCards" :key="stat.label" class="rounded-xl border bg-card p-4 shadow-sm">
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
          <input v-model="search" placeholder="Search warehouses..."
            class="flex-1 bg-transparent text-sm outline-none placeholder:text-muted-foreground" />
        </div>
        <select v-model="statusFilter" class="rounded-lg border bg-background px-3 py-1.5 text-sm outline-none">
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
        <button @click="openForm()" class="mt-3 text-xs text-primary hover:underline">Add your first warehouse</button>
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
          <tr v-for="wh in sorted" :key="wh.id"
            class="transition hover:bg-muted/30 cursor-pointer"
            @click="openStockDrawer(wh)">
            <td class="px-4 py-3.5"><span class="font-mono text-sm font-semibold">{{ wh.code }}</span></td>
            <td class="px-4 py-3.5">
              <p class="font-medium">{{ wh.name }}</p>
              <p v-if="wh.city" class="text-xs text-muted-foreground">{{ wh.city }}</p>
            </td>
            <td class="hidden px-4 py-3.5 md:table-cell max-w-[200px]">
              <span class="line-clamp-1 text-muted-foreground text-xs">{{ wh.address || '—' }}</span>
            </td>
            <td class="hidden px-4 py-3.5 md:table-cell">
              <div class="flex items-center gap-1 text-xs text-muted-foreground">
                <LayoutGrid class="h-3.5 w-3.5" />
                {{ wh.total_zones ?? 0 }} zones / {{ wh.total_racks ?? 0 }} racks
              </div>
            </td>
            <td class="px-4 py-3.5">
              <div class="flex items-center gap-2">
                <div class="h-1.5 w-20 overflow-hidden rounded-full bg-muted">
                  <div :class="['h-full rounded-full transition-all', utilizationColor(wh.utilization)]"
                    :style="{ width: `${Math.min(wh.utilization ?? 0, 100)}%` }" />
                </div>
                <span class="text-xs tabular-nums text-muted-foreground">{{ wh.utilization ?? 0 }}%</span>
              </div>
            </td>
            <td class="px-4 py-3.5">
              <span :class="['inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold',
                wh.status === 'active'
                  ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300'
                  : 'bg-muted text-muted-foreground']">
                {{ wh.status === 'active' ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td class="px-4 py-3.5" @click.stop>
              <div class="flex items-center gap-1">
                <button @click="openStockDrawer(wh)"
                  class="flex items-center gap-1 h-7 px-2 rounded-lg border border-blue-200 text-blue-600 text-xs transition hover:bg-blue-50 dark:border-blue-800 dark:hover:bg-blue-950">
                  <Boxes class="h-3.5 w-3.5" />
                  Stock
                </button>
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

      <div v-if="!loading && filtered.length > 0"
        class="flex items-center justify-between border-t px-4 py-3 text-xs text-muted-foreground">
        <span>{{ filtered.length }} warehouse{{ filtered.length !== 1 ? 's' : '' }}</span>
        <span>Updated: {{ lastUpdated }}</span>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         STOCK DRAWER
    ══════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <Transition name="slide">
        <div v-if="stockDrawer.open" class="fixed inset-0 z-50 flex justify-end">
          <div class="absolute inset-0 bg-foreground/30 backdrop-blur-sm" @click="closeStockDrawer" />
          <div class="relative flex h-full w-full max-w-2xl flex-col bg-card shadow-2xl border-l">

            <!-- Drawer header -->
            <div class="flex items-center justify-between border-b px-6 py-4">
              <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-500/10">
                  <Boxes class="h-5 w-5 text-blue-500" />
                </div>
                <div>
                  <p class="font-semibold">{{ stockDrawer.warehouse?.name }}</p>
                  <p class="text-xs text-muted-foreground font-mono">{{ stockDrawer.warehouse?.code }} · Stock Management</p>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <button @click="openAddStock"
                  class="flex items-center gap-1.5 rounded-lg bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground hover:opacity-90 transition">
                  <Plus class="h-3.5 w-3.5" /> Add Item
                </button>
                <button @click="closeStockDrawer" class="p-2 rounded-lg hover:bg-muted transition">
                  <X class="h-4 w-4" />
                </button>
              </div>
            </div>

            <!-- Stock stats -->
            <div class="grid grid-cols-3 gap-3 px-6 py-4 border-b">
              <div class="rounded-xl border bg-muted/30 p-3 text-center">
                <p class="text-lg font-bold">{{ stockDrawer.items.length }}</p>
                <p class="text-xs text-muted-foreground">Total SKUs</p>
              </div>
              <div class="rounded-xl border bg-muted/30 p-3 text-center">
                <p class="text-lg font-bold text-amber-500">
                  {{ stockDrawer.items.filter(s => Number(s.qty) <= Number(s.reorder_level)).length }}
                </p>
                <p class="text-xs text-muted-foreground">Low Stock</p>
              </div>
              <div class="rounded-xl border bg-muted/30 p-3 text-center">
                <p class="text-lg font-bold">
                  {{ stockDrawer.items.reduce((s, i) => s + Number(i.qty), 0).toLocaleString() }}
                </p>
                <p class="text-xs text-muted-foreground">Total Units</p>
              </div>
            </div>

            <!-- Search stock -->
            <div class="px-6 py-3 border-b">
              <div class="flex items-center gap-2 rounded-lg border bg-background px-3 py-1.5">
                <Search class="h-4 w-4 text-muted-foreground shrink-0" />
                <input v-model="stockSearch" placeholder="Search SKU or item name..."
                  class="flex-1 bg-transparent text-sm outline-none placeholder:text-muted-foreground" />
              </div>
            </div>

            <!-- Stock list -->
            <div class="flex-1 overflow-y-auto">
              <div v-if="stockDrawer.loading" class="divide-y">
                <div v-for="i in 6" :key="i" class="flex items-center gap-4 px-6 py-3">
                  <div class="h-4 w-20 animate-pulse rounded bg-muted" />
                  <div class="h-4 flex-1 animate-pulse rounded bg-muted" />
                  <div class="h-4 w-16 animate-pulse rounded bg-muted" />
                </div>
              </div>

              <div v-else-if="filteredStock.length === 0"
                class="flex flex-col items-center justify-center py-16 text-muted-foreground">
                <PackageOpen class="mb-3 h-10 w-10 opacity-20" />
                <p class="text-sm">No stock items found</p>
                <button @click="openAddStock" class="mt-2 text-xs text-primary hover:underline">Add first item</button>
              </div>

              <table v-else class="w-full text-sm">
                <thead class="sticky top-0 border-b bg-card z-10">
                  <tr>
                    <th class="px-6 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">SKU</th>
                    <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Item Name</th>
                    <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">UOM</th>
                    <th class="px-4 py-2.5 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground">Qty</th>
                    <th class="px-4 py-2.5 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground">Reorder</th>
                    <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Status</th>
                    <th class="px-4 py-2.5" />
                  </tr>
                </thead>
                <tbody class="divide-y">
                  <tr v-for="item in filteredStock" :key="item.id" class="hover:bg-muted/30 transition">
                    <td class="px-6 py-3">
                      <span class="font-mono text-xs font-semibold text-primary">{{ item.sku }}</span>
                    </td>
                    <td class="px-4 py-3 font-medium">{{ item.name }}</td>
                    <td class="px-4 py-3 text-muted-foreground text-xs">{{ item.uom }}</td>
                    <td class="px-4 py-3 text-right">
                      <span :class="['font-semibold tabular-nums', Number(item.qty) <= Number(item.reorder_level) ? 'text-amber-500' : '']">
                        {{ Number(item.qty).toLocaleString() }}
                      </span>
                    </td>
                    <td class="px-4 py-3 text-right text-muted-foreground tabular-nums text-xs">{{ item.reorder_level }}</td>
                    <td class="px-4 py-3">
                      <span :class="['inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                        Number(item.qty) <= 0
                          ? 'bg-red-100 text-red-600 dark:bg-red-950 dark:text-red-400'
                          : Number(item.qty) <= Number(item.reorder_level)
                            ? 'bg-amber-100 text-amber-600 dark:bg-amber-950 dark:text-amber-400'
                            : 'bg-emerald-100 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400']">
                        {{ Number(item.qty) <= 0 ? 'Out of Stock' : Number(item.qty) <= Number(item.reorder_level) ? 'Low Stock' : 'Healthy' }}
                      </span>
                    </td>
                    <td class="px-4 py-3">
                      <div class="flex items-center gap-1 justify-end">
                        <button @click="adjustQty(item, -1)"
                          class="h-6 w-6 flex items-center justify-center rounded border text-muted-foreground hover:bg-muted transition text-xs font-bold">−</button>
                        <button @click="adjustQty(item, 1)"
                          class="h-6 w-6 flex items-center justify-center rounded border text-muted-foreground hover:bg-muted transition text-xs font-bold">+</button>
                        <button @click="openEditStock(item)"
                          class="h-6 w-6 flex items-center justify-center rounded border hover:bg-accent transition ml-1">
                          <Pencil class="h-3 w-3" />
                        </button>
                        <button @click="deleteStock(item)"
                          class="h-6 w-6 flex items-center justify-center rounded border border-red-200 text-red-500 hover:bg-red-50 transition">
                          <Trash2 class="h-3 w-3" />
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="border-t px-6 py-3 text-xs text-muted-foreground flex justify-between">
              <span>{{ filteredStock.length }} items</span>
              <span>Click row or use +/− to adjust quantity</span>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ══════════════════════════════════════════════════════
         ADD / EDIT STOCK MODAL
    ══════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="stockForm.show"
          class="fixed inset-0 z-[60] flex items-center justify-center bg-black/40 p-4 backdrop-blur-sm"
          @click.self="stockForm.show = false">
          <div class="modal-box w-full max-w-md">
            <div class="flex items-center justify-between border-b px-6 py-4">
              <h3 class="font-semibold">{{ stockForm.isEdit ? 'Edit Stock Item' : 'Add Stock Item' }}</h3>
              <button @click="stockForm.show = false" class="p-2 rounded-lg hover:bg-muted transition">
                <X class="h-4 w-4" />
              </button>
            </div>
            <div class="p-6 space-y-4">
              <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                  <label class="field-label">Item Name *</label>
                  <input v-model="stockForm.data.name" placeholder="e.g. Cardboard Box 50x40" class="field-input" />
                  <p class="mt-1 text-xs text-muted-foreground">SKU will be auto-generated from the name</p>
                </div>
                <div>
                  <label class="field-label">UOM</label>
                  <select v-model="stockForm.data.uom" class="field-input">
                    <option>pcs</option><option>kg</option><option>box</option>
                    <option>pallet</option><option>roll</option><option>liter</option>
                  </select>
                </div>
                <div>
                  <label class="field-label">Quantity *</label>
                  <input v-model.number="stockForm.data.qty" type="number" min="0" class="field-input" />
                </div>
                <div class="col-span-2">
                  <label class="field-label">Reorder Level</label>
                  <input v-model.number="stockForm.data.reorder_level" type="number" min="0" class="field-input" />
                </div>
              </div>
              <div v-if="stockForm.error"
                class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-600">
                {{ stockForm.error }}
              </div>
            </div>
            <div class="flex gap-3 border-t px-6 py-4">
              <button @click="stockForm.show = false"
                class="flex-1 rounded-xl border py-2.5 text-sm font-medium hover:bg-accent transition">Cancel</button>
              <button @click="submitStock" :disabled="stockForm.submitting"
                class="flex flex-1 items-center justify-center gap-2 rounded-xl bg-primary py-2.5 text-sm font-medium text-primary-foreground hover:opacity-90 disabled:opacity-50 transition">
                <Loader2 v-if="stockForm.submitting" class="h-4 w-4 animate-spin" />
                {{ stockForm.isEdit ? 'Save Changes' : 'Add to Stock' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ══════════════════════════════════════════════════════
         WAREHOUSE FORM MODAL
    ══════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showForm"
          class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 backdrop-blur-sm"
          @click.self="closeForm">
          <div class="modal-box w-full max-w-lg">
            <div class="flex items-center justify-between border-b px-6 py-4">
              <h3 class="text-lg font-semibold">{{ isEdit ? 'Edit Warehouse' : 'Add Warehouse' }}</h3>
              <button @click="closeForm" class="flex h-8 w-8 items-center justify-center rounded-lg transition hover:bg-accent">
                <X class="h-4 w-4" />
              </button>
            </div>
            <div class="max-h-[65vh] overflow-y-auto p-6">
              <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                  <label class="field-label">Warehouse Name *</label>
                  <input v-model="form.name" placeholder="Main Warehouse" class="field-input" />
                  <p class="mt-1 text-xs text-muted-foreground">Code will be auto-generated from the name</p>
                </div>

                <!-- ── Location Search (Nominatim / OpenStreetMap) ── -->
                <div class="col-span-2">
                  <label class="field-label">
                    Address *
                    <span class="ml-1 font-normal text-muted-foreground">(ketik minimal 3 karakter untuk cari)</span>
                  </label>
                  <div class="relative">
                    <!-- Input -->
                    <div class="flex items-center gap-2 rounded-xl border bg-background px-3 py-2.5"
                      :class="locationError ? 'border-red-400' : ''">
                      <MapPin class="h-4 w-4 text-muted-foreground shrink-0" />
                      <input
                        ref="locationInputRef"
                        v-model="locationQuery"
                        placeholder="Cari alamat, nama gedung, atau kota..."
                        class="flex-1 bg-transparent text-sm outline-none placeholder:text-muted-foreground"
                        autocomplete="off"
                        @input="onLocationInput"
                        @keydown.escape="closeSuggestions"
                        @keydown.down.prevent="highlightNext"
                        @keydown.up.prevent="highlightPrev"
                        @keydown.enter.prevent="selectHighlighted"
                      />
                      <Loader2 v-if="locationLoading" class="h-4 w-4 animate-spin text-muted-foreground shrink-0" />
                      <button v-else-if="locationQuery" @click="clearLocation"
                        class="text-muted-foreground hover:text-foreground transition shrink-0">
                        <X class="h-3.5 w-3.5" />
                      </button>
                    </div>

                    <!-- Dropdown suggestions -->
                    <Transition name="dropdown">
                      <div v-if="locationSuggestions.length > 0"
                        class="absolute top-full left-0 right-0 z-[70] mt-1 overflow-hidden rounded-xl border bg-card shadow-xl">
                        <button
                          v-for="(s, idx) in locationSuggestions"
                          :key="s.place_id"
                          @click="selectSuggestion(s)"
                          @mouseenter="highlightedIndex = idx"
                          :class="[
                            'flex w-full items-start gap-3 px-4 py-3 text-left text-sm transition border-b last:border-0',
                            highlightedIndex === idx ? 'bg-muted/70' : 'hover:bg-muted/40',
                          ]">
                          <MapPin class="mt-0.5 h-3.5 w-3.5 shrink-0 text-primary" />
                          <div class="flex-1 min-w-0">
                            <p class="font-medium text-sm truncate">{{ s.mainText }}</p>
                            <p class="text-xs text-muted-foreground truncate">{{ s.secondaryText }}</p>
                          </div>
                        </button>
                      </div>
                    </Transition>

                    <!-- No results hint -->
                    <p v-if="locationNoResult" class="mt-1.5 text-xs text-muted-foreground">
                      Alamat tidak ditemukan. Coba kata kunci lain.
                    </p>
                  </div>
                </div>

                <!-- Selected location preview -->
                <div v-if="form.address" class="col-span-2 rounded-xl border bg-muted/30 px-4 py-3">
                  <div class="flex items-start gap-2">
                    <MapPin class="mt-0.5 h-4 w-4 shrink-0 text-primary" />
                    <div class="flex-1 min-w-0">
                      <p class="text-sm font-medium">{{ form.address }}</p>
                      <p v-if="form.city" class="text-xs text-muted-foreground">{{ form.city }}</p>
                      <p v-if="form.latitude && form.longitude"
                        class="font-mono text-xs text-muted-foreground mt-0.5">
                        {{ Number(form.latitude).toFixed(6) }}, {{ Number(form.longitude).toFixed(6) }}
                      </p>
                    </div>
                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 shrink-0">
                      <CheckCircle2 class="h-2.5 w-2.5" /> Verified
                    </span>
                  </div>
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
              <button @click="closeForm" class="flex-1 rounded-xl border py-2.5 text-sm font-medium transition hover:bg-accent">Cancel</button>
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

    <!-- ── Delete Modal ────────────────────────────────────── -->
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
                <span class="font-medium text-foreground">{{ deleteTarget.name }}</span> will be permanently deleted.
              </p>
            </div>
            <div class="flex gap-3 border-t px-6 py-4">
              <button @click="deleteTarget = null" class="flex-1 rounded-xl border py-2.5 text-sm font-medium transition hover:bg-accent">Cancel</button>
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
import { ref, computed, onMounted, reactive } from 'vue'
import {
  Plus, Search, Pencil, Trash2, X, Loader2, RefreshCw,
  Building2, CheckCircle2, ArrowUpDown, LayoutGrid, MapPin,
  Warehouse as WarehouseIcon, CheckCircle, XCircle, Boxes, PackageOpen,
} from 'lucide-vue-next'
import { useWarehouses, type Warehouse, type WarehouseForm } from '@/composable/useWarehouses'
import api from '@/lib/axios'

const {
  warehouses, loading, error,
  fetchWarehouses, createWarehouse, updateWarehouse, deleteWarehouse,
} = useWarehouses()

// ── Types ──────────────────────────────────────────────────
interface StockItem {
  id: number
  sku: string
  name: string
  uom: string
  qty: number | string
  reorder_level: number | string
  warehouse_id: number
}

// Nominatim suggestion (normalized)
interface LocationSuggestion {
  place_id: string   // always string (Nominatim returns number, we convert)
  mainText: string
  secondaryText: string
  fullAddress: string
  lat: number
  lng: number
  city: string
}

// ── State ──────────────────────────────────────────────────
const search       = ref('')
const statusFilter = ref('')
const sortKey      = ref<string | null>(null)
const sortAsc      = ref(true)
const showForm     = ref(false)
const isEdit       = ref(false)
const submitting   = ref(false)
const formError    = ref('')
const toast        = ref('')
const deleteTarget = ref<Warehouse | null>(null)
const lastUpdated  = ref('—')
const editingId    = ref<number | null>(null)
const stockSearch  = ref('')

// ── Location search state ──────────────────────────────────
const locationInputRef    = ref<HTMLInputElement | null>(null)
const locationQuery       = ref('')
const locationSuggestions = ref<LocationSuggestion[]>([])
const locationLoading     = ref(false)
const locationNoResult    = ref(false)
const locationError       = ref(false)
const highlightedIndex    = ref(-1)
let locationDebounce: ReturnType<typeof setTimeout> | null = null

// ── Nominatim search (OpenStreetMap, no API key needed) ────
async function onLocationInput() {
  locationNoResult.value = false
  locationError.value    = false
  highlightedIndex.value = -1

  if (locationDebounce) clearTimeout(locationDebounce)

  const q = locationQuery.value.trim()
  if (q.length < 3) {
    locationSuggestions.value = []
    return
  }

  locationDebounce = setTimeout(() => searchNominatim(q), 400)
}

async function searchNominatim(query: string) {
  locationLoading.value = true
  locationSuggestions.value = []
  try {
    const url = new URL('https://nominatim.openstreetmap.org/search')
    url.searchParams.set('format', 'json')
    url.searchParams.set('q', query)
    url.searchParams.set('limit', '6')
    url.searchParams.set('addressdetails', '1')
    url.searchParams.set('countrycodes', 'id')  // Indonesia only, hapus baris ini kalau mau global

    const res  = await fetch(url.toString(), {
      headers: { 'Accept-Language': 'id,en', 'User-Agent': 'TMS-WMS-App/1.0' },
    })
    const data = await res.json()

    if (!Array.isArray(data) || data.length === 0) {
      locationNoResult.value = true
      locationSuggestions.value = []
      return
    }

    locationSuggestions.value = data.map((item: any): LocationSuggestion => {
      const addr = item.address ?? {}
      // Ambil bagian pertama sebagai main text (nama jalan / gedung / nama tempat)
      const mainText =
        addr.road ??
        addr.pedestrian ??
        addr.amenity ??
        addr.building ??
        addr.neighbourhood ??
        item.name ??
        item.display_name.split(',')[0]

      // Sisanya sebagai secondary text
      const secondary = [
        addr.suburb ?? addr.village ?? '',
        addr.city ?? addr.town ?? addr.county ?? '',
        addr.state ?? '',
      ].filter(Boolean).join(', ')

      const city =
        addr.city ??
        addr.town ??
        addr.county ??
        addr.state ??
        ''

      return {
        place_id:      String(item.place_id),
        mainText:      mainText.trim(),
        secondaryText: secondary,
        fullAddress:   item.display_name,
        lat:           parseFloat(item.lat),
        lng:           parseFloat(item.lon),
        city,
      }
    })
  } catch (e) {
    console.error('Nominatim error:', e)
    locationError.value = true
    locationSuggestions.value = []
  } finally {
    locationLoading.value = false
  }
}

function selectSuggestion(s: LocationSuggestion) {
  // Isi form
  form.value.address   = s.fullAddress
  form.value.city      = s.city
  form.value.latitude  = String(s.lat)
  form.value.longitude = String(s.lng)

  // Update input display
  locationQuery.value       = s.mainText + (s.secondaryText ? `, ${s.secondaryText}` : '')
  locationSuggestions.value = []
  locationNoResult.value    = false
  highlightedIndex.value    = -1
}

function clearLocation() {
  locationQuery.value       = ''
  locationSuggestions.value = []
  locationNoResult.value    = false
  locationError.value       = false
  highlightedIndex.value    = -1
  form.value.address        = ''
  form.value.city           = ''
  form.value.latitude       = ''
  form.value.longitude      = ''
}

function closeSuggestions() {
  locationSuggestions.value = []
  highlightedIndex.value    = -1
}

// Keyboard navigation
function highlightNext() {
  if (locationSuggestions.value.length === 0) return
  highlightedIndex.value = (highlightedIndex.value + 1) % locationSuggestions.value.length
}
function highlightPrev() {
  if (locationSuggestions.value.length === 0) return
  highlightedIndex.value = highlightedIndex.value <= 0
    ? locationSuggestions.value.length - 1
    : highlightedIndex.value - 1
}
function selectHighlighted() {
  if (highlightedIndex.value >= 0 && locationSuggestions.value[highlightedIndex.value]) {
    selectSuggestion(locationSuggestions.value[highlightedIndex.value])
  }
}

// ── Stock Drawer ───────────────────────────────────────────
const stockDrawer = reactive({
  open:      false,
  warehouse: null as Warehouse | null,
  items:     [] as StockItem[],
  loading:   false,
})

// ── Stock Form ─────────────────────────────────────────────
const stockForm = reactive({
  show:       false,
  isEdit:     false,
  submitting: false,
  error:      '',
  editingId:  null as number | null,
  data: { name: '', uom: 'pcs', qty: 0, reorder_level: 0 },
})

// ── Warehouse Form ─────────────────────────────────────────
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

const columns = [
  { key: 'code',        label: 'Code',        sortable: true },
  { key: 'name',        label: 'Name',        sortable: true },
  { key: 'address',     label: 'Address',     sortable: false, class: 'hidden md:table-cell' },
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

const filteredStock = computed(() => {
  if (!stockSearch.value) return stockDrawer.items
  const q = stockSearch.value.toLowerCase()
  return stockDrawer.items.filter(i =>
    i.name?.toLowerCase().includes(q) || i.sku?.toLowerCase().includes(q)
  )
})

function toggleSort(key: string) {
  sortKey.value === key
    ? (sortAsc.value = !sortAsc.value)
    : (sortKey.value = key, sortAsc.value = true)
}

function utilizationColor(u?: number) {
  if (!u) return 'bg-primary'
  return u > 80 ? 'bg-red-500' : u > 50 ? 'bg-amber-500' : 'bg-primary'
}

// ── Stock Drawer ───────────────────────────────────────────
async function openStockDrawer(wh: Warehouse) {
  stockDrawer.warehouse = wh
  stockDrawer.open      = true
  stockDrawer.loading   = true
  stockSearch.value     = ''
  try {
    const res = await api.get(`/wms/warehouses/${wh.id}/stocks`)
    const raw = res.data?.data ?? res.data
    stockDrawer.items = Array.isArray(raw) ? raw : (raw?.data ?? [])
  } catch {
    stockDrawer.items = []
  } finally {
    stockDrawer.loading = false
  }
}

function closeStockDrawer() {
  stockDrawer.open      = false
  stockDrawer.warehouse = null
  stockDrawer.items     = []
}

// ── Adjust Qty ─────────────────────────────────────────────
async function adjustQty(item: StockItem, delta: number) {
  const newQty = Math.max(0, Number(item.qty) + delta)
  try {
    await api.patch(`/wms/stocks/${item.id}/adjust`, {
      qty: newQty,
      notes: delta > 0 ? 'Manual increase' : 'Manual decrease',
    })
    item.qty = newQty
    showToast(`Stock updated: ${item.name}`)
  } catch {
    showToast('Failed to update stock')
  }
}

// ── Add / Edit Stock ───────────────────────────────────────
function openAddStock() {
  stockForm.isEdit    = false
  stockForm.editingId = null
  stockForm.error     = ''
  stockForm.data      = { name: '', uom: 'pcs', qty: 0, reorder_level: 0 }
  stockForm.show      = true
}

function openEditStock(item: StockItem) {
  stockForm.isEdit    = true
  stockForm.editingId = item.id
  stockForm.error     = ''
  stockForm.data      = {
    name: item.name, uom: item.uom,
    qty: Number(item.qty), reorder_level: Number(item.reorder_level),
  }
  stockForm.show = true
}

async function submitStock() {
  if (!stockForm.data.name.trim()) {
    stockForm.error = 'Item name is required.'
    return
  }
  stockForm.submitting = true
  stockForm.error      = ''
  try {
    if (stockForm.isEdit && stockForm.editingId) {
      const res     = await api.put(`/wms/stocks/${stockForm.editingId}`, stockForm.data)
      const updated = res.data?.data ?? res.data
      const idx     = stockDrawer.items.findIndex(i => i.id === stockForm.editingId)
      if (idx !== -1) Object.assign(stockDrawer.items[idx], updated)
      showToast('Stock item updated')
    } else {
      const res     = await api.post(`/wms/warehouses/${stockDrawer.warehouse!.id}/stocks`, stockForm.data)
      const created = res.data?.data ?? res.data
      stockDrawer.items.push(created)
      showToast('Stock item added')
    }
    stockForm.show = false
  } catch (e: any) {
    stockForm.error = e.response?.data?.message ??
      (e.response?.data?.errors
        ? Object.values(e.response.data.errors).flat().join(', ')
        : 'Something went wrong.')
  } finally {
    stockForm.submitting = false
  }
}

async function deleteStock(item: StockItem) {
  if (!confirm(`Delete ${item.name}?`)) return
  try {
    await api.delete(`/wms/stocks/${item.id}`)
    stockDrawer.items = stockDrawer.items.filter(i => i.id !== item.id)
    showToast('Stock item deleted')
  } catch {
    showToast('Failed to delete')
  }
}

// ── Warehouse Form ─────────────────────────────────────────
function openForm(wh?: Warehouse) {
  isEdit.value          = !!wh
  editingId.value       = wh?.id ?? null
  formError.value       = ''
  locationSuggestions.value = []
  locationNoResult.value = false

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

  // Pre-fill query kalau edit & ada address
  locationQuery.value = wh?.address ? (wh.address.split(',')[0] ?? wh.address) : ''

  showForm.value = true
}

function closeForm() {
  showForm.value    = false
  formError.value   = ''
  editingId.value   = null
  locationQuery.value = ''
  locationSuggestions.value = []
  locationNoResult.value = false
}

async function submitForm() {
  if (!form.value.name?.trim()) {
    formError.value = 'Warehouse name is required.'
    return
  }
  if (!form.value.address?.trim()) {
    formError.value = 'Address is required. Please search and select a location.'
    return
  }
  submitting.value = true
  formError.value  = ''
  try {
    const payload = {
      name:      form.value.name,
      address:   form.value.address,
      city:      form.value.city,
      latitude:  form.value.latitude  ? parseFloat(form.value.latitude)  : null,
      longitude: form.value.longitude ? parseFloat(form.value.longitude) : null,
      phone:     form.value.phone,
      pic_name:  form.value.pic_name,
      status:    form.value.status,
    }

    if (isEdit.value && editingId.value) {
      await updateWarehouse(editingId.value, payload as any)
      showToast('Warehouse updated')
    } else {
      await createWarehouse(payload as any)
      showToast('Warehouse created')
    }
    closeForm()
    await fetchWarehouses()
  } catch (e: any) {
    formError.value = e.response?.data?.message ?? e.message ?? 'Something went wrong.'
  } finally {
    submitting.value = false
  }
}

// ── Delete Warehouse ───────────────────────────────────────
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

.field-label { @apply mb-1.5 block text-xs font-semibold text-muted-foreground; }
.field-input {
  @apply w-full rounded-xl border bg-background px-3 py-2.5 text-sm outline-none
         focus:ring-2 focus:ring-ring focus:border-transparent placeholder:text-muted-foreground;
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
.slide-enter-active, .slide-leave-active { transition: transform 0.3s ease; }
.slide-enter-from, .slide-leave-to { transform: translateX(100%); }
.toast-enter-active, .toast-leave-active { transition: all 0.25s ease; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateY(8px); }
.dropdown-enter-active, .dropdown-leave-active { transition: all 0.15s ease; }
.dropdown-enter-from, .dropdown-leave-to { opacity: 0; transform: translateY(-4px); }
</style>