<template>
  <div class="flex flex-col gap-6">
    <div>
      <h1 class="font-display text-2xl font-semibold tracking-tight">Drivers</h1>
      <p class="text-sm text-muted-foreground">Driver directory and current operational status.</p>
    </div>
    <DataTable
      :columns="columns"
      :data="drivers"
      :row-key="(r) => r.id"
      :search-accessor="(r) => `${r.name} ${r.phone}`"
      search-placeholder="Search drivers..."
    />
  </div>
</template>

<script setup lang="ts">
import { h } from "vue";
import DataTable, { type DataTableColumn } from "@/components/DataTable.vue";
import { drivers, vehicles, type Driver } from "@/lib/mock-data";

const statusColor: Record<Driver["status"], string> = {
  available: "bg-success/15 text-success",
  on_delivery: "bg-primary/15 text-primary",
  off_duty: "bg-muted text-muted-foreground",
};

const columns: DataTableColumn<Driver>[] = [
  {
    key: "name", header: "Driver", sortable: true, sortValue: (r) => r.name,
    cell: (r) => h("span", { class: "font-medium" }, r.name),
  },
  {
    key: "phone", header: "Phone",
    cell: (r) => h("span", { class: "text-muted-foreground tabular-nums" }, r.phone),
  },
  {
    key: "vehicle", header: "Vehicle",
    cell: (r) => {
      const plate = vehicles.find((v) => v.id === r.vehicleId)?.plate;
      return plate
        ? h("span", { class: "font-mono text-sm" }, plate)
        : h("span", { class: "text-muted-foreground" }, "None");
    },
  },
  {
    key: "status", header: "Status", sortable: true, sortValue: (r) => r.status,
    cell: (r) => h(
      "span",
      { class: `inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium capitalize ${statusColor[r.status]}` },
      r.status.replace("_", " ")
    ),
  },
];
</script>
