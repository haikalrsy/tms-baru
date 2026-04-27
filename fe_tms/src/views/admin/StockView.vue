<template>
  <div class="flex flex-col gap-6">
    <div>
      <h1 class="font-display text-2xl font-semibold tracking-tight">Warehouse stock</h1>
      <p class="text-sm text-muted-foreground">SKU-level inventory across the warehouse network.</p>
    </div>
    <DataTable
      :columns="columns"
      :data="stock"
      :row-key="(r) => r.id"
      :search-accessor="(r) => `${r.sku} ${r.name}`"
      search-placeholder="Search SKUs..."
    />
  </div>
</template>

<script setup lang="ts">
import { h } from "vue";
import DataTable, { type DataTableColumn } from "@/components/DataTable.vue";
import { stock, warehouses, type StockItem } from "@/lib/mock-data";

const columns: DataTableColumn<StockItem>[] = [
  {
    key: "sku", header: "SKU", sortable: true, sortValue: (r) => r.sku,
    cell: (r) => h("span", { class: "font-mono text-sm font-medium" }, r.sku),
  },
  {
    key: "name", header: "Item", sortable: true, sortValue: (r) => r.name,
    cell: (r) => h("span", { class: "font-medium" }, r.name),
  },
  {
    key: "wh", header: "Warehouse",
    cell: (r) => warehouses.find((w) => w.id === r.warehouseId)?.code ?? "—",
  },
  {
    key: "qty", header: "Quantity", sortable: true, sortValue: (r) => r.quantity,
    cell: (r) => h("span", { class: "tabular-nums font-medium" }, r.quantity.toLocaleString()),
  },
  {
    key: "reorder", header: "Reorder level", sortable: true, sortValue: (r) => r.reorderLevel,
    cell: (r) => h("span", { class: "tabular-nums text-muted-foreground" }, r.reorderLevel),
  },
  {
    key: "alert", header: "Status",
    cell: (r) => {
      const low = r.quantity <= r.reorderLevel;
      return h(
        "span",
        {
          class: `inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium ${
            low
              ? "border-warning/40 bg-warning/15 text-warning-foreground"
              : "border-success/40 bg-success/15 text-success"
          }`,
        },
        low ? "Reorder soon" : "Healthy"
      );
    },
  },
];
</script>
