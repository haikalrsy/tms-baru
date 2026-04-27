<template>
  <div class="flex flex-col gap-6">
    <div>
      <h1 class="font-display text-2xl font-semibold tracking-tight">Vehicles</h1>
      <p class="text-sm text-muted-foreground">Fleet inventory and operational state.</p>
    </div>
    <DataTable
      :columns="columns"
      :data="vehicles"
      :row-key="(r) => r.id"
      :search-accessor="(r) => `${r.plate} ${r.type}`"
      search-placeholder="Search vehicles..."
    />
  </div>
</template>

<script setup lang="ts">
import { h } from "vue";
import DataTable, { type DataTableColumn } from "@/components/DataTable.vue";
import { vehicles, type Vehicle } from "@/lib/mock-data";

const statusColor: Record<Vehicle["status"], string> = {
  active: "bg-success/15 text-success",
  maintenance: "bg-warning/15 text-warning-foreground",
  idle: "bg-muted text-muted-foreground",
};

const columns: DataTableColumn<Vehicle>[] = [
  {
    key: "plate", header: "Plate", sortable: true, sortValue: (r) => r.plate,
    cell: (r) => h("span", { class: "font-mono text-sm font-medium" }, r.plate),
  },
  {
    key: "type", header: "Type", sortable: true, sortValue: (r) => r.type,
    cell: (r) => r.type,
  },
  {
    key: "cap", header: "Capacity", sortable: true, sortValue: (r) => r.capacityKg,
    cell: (r) => h("span", { class: "tabular-nums" }, `${r.capacityKg.toLocaleString()} kg`),
  },
  {
    key: "status", header: "Status", sortable: true, sortValue: (r) => r.status,
    cell: (r) => h(
      "span",
      { class: `inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium capitalize ${statusColor[r.status]}` },
      r.status
    ),
  },
];
</script>
