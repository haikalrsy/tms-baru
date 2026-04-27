<template>
  <div class="flex flex-col gap-6">
    <div>
      <h1 class="font-display text-2xl font-semibold tracking-tight">Deliveries</h1>
      <p class="text-sm text-muted-foreground">All deliveries flowing from ERP through warehouse to last-mile.</p>
    </div>
    <DataTable
      :columns="columns"
      :data="deliveries"
      :row-key="(r) => r.id"
      :search-accessor="(r) => `${r.orderRef} ${r.customerName} ${r.customerAddress}`"
      search-placeholder="Search by order ref, customer, address..."
    />
  </div>
</template>

<script setup lang="ts">
import { h } from "vue";
import DataTable, { type DataTableColumn } from "@/components/DataTable.vue";
import { deliveries, drivers, warehouses, type Delivery } from "@/lib/mock-data";

const statusColor: Record<Delivery["status"], string> = {
  pending: "bg-muted text-muted-foreground",
  picking: "bg-info/15 text-info",
  packed: "bg-accent text-accent-foreground",
  in_transit: "bg-primary/15 text-primary",
  delivered: "bg-success/15 text-success",
  failed: "bg-destructive/15 text-destructive",
};

const badge = (status: Delivery["status"]) =>
  h("span", {
    class: `inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium capitalize ${statusColor[status]}`,
  }, status.replace("_", " "));

const columns: DataTableColumn<Delivery>[] = [
  {
    key: "ref", header: "Order", sortable: true, sortValue: (r) => r.orderRef,
    cell: (r) => h("span", { class: "font-mono text-xs font-medium" }, r.orderRef),
  },
  {
    key: "customer", header: "Customer", sortable: true, sortValue: (r) => r.customerName,
    cell: (r) => h("div", { class: "flex flex-col" }, [
      h("span", { class: "font-medium" }, r.customerName),
      h("span", { class: "text-xs text-muted-foreground" }, r.customerAddress),
    ]),
  },
  {
    key: "wh", header: "Warehouse",
    cell: (r) => warehouses.find((w) => w.id === r.warehouseId)?.code ?? "—",
  },
  {
    key: "driver", header: "Driver",
    cell: (r) => {
      const name = drivers.find((d) => d.id === r.driverId)?.name;
      return name ? h("span", {}, name) : h("span", { class: "text-muted-foreground" }, "Unassigned");
    },
  },
  {
    key: "status", header: "Status", sortable: true, sortValue: (r) => r.status,
    cell: (r) => badge(r.status),
  },
  {
    key: "amount", header: "Amount", sortable: true, sortValue: (r) => r.amount,
    cell: (r) => h("span", { class: "font-medium tabular-nums" }, `$${r.amount.toLocaleString()}`),
  },
  {
    key: "scheduled", header: "Scheduled", sortable: true, sortValue: (r) => r.scheduledAt,
    cell: (r) => h("span", { class: "text-muted-foreground tabular-nums" },
      new Date(r.scheduledAt).toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" })),
  },
];
</script>
