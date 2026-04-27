<template>
  <div class="flex flex-col gap-4 rounded-xl border bg-card shadow-soft">
    <div class="flex flex-col gap-3 border-b px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
      <div v-if="searchAccessor" class="relative w-full max-w-sm">
        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
        <input
          v-model="query"
          :placeholder="searchPlaceholder"
          class="flex h-10 w-full rounded-md border border-input bg-background pl-9 pr-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
        />
      </div>
      <div v-else />
      <div v-if="$slots.filters" class="flex items-center gap-2">
        <slot name="filters" />
      </div>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b bg-surface-2/60">
            <th
              v-for="col in columns"
              :key="col.key"
              :class="[
                'px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground',
                col.sortable && 'cursor-pointer select-none hover:text-foreground',
                col.className,
              ]"
              @click="col.sortable ? toggleSort(col.key) : undefined"
            >
              <span class="inline-flex items-center gap-1.5">
                {{ col.header }}
                <template v-if="col.sortable">
                  <ArrowUp v-if="sort?.key === col.key && sort.direction === 'asc'" class="h-3 w-3" />
                  <ArrowDown v-else-if="sort?.key === col.key && sort.direction === 'desc'" class="h-3 w-3" />
                  <ArrowUpDown v-else class="h-3 w-3 opacity-40" />
                </template>
              </span>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="processed.length === 0">
            <td :colspan="columns.length" class="px-5 py-12 text-center text-sm text-muted-foreground">
              {{ emptyMessage }}
            </td>
          </tr>
          <tr
            v-for="row in processed"
            :key="rowKey(row)"
            :class="[
              'border-b transition-colors last:border-b-0 hover:bg-surface-2/60',
              onRowClick && 'cursor-pointer',
            ]"
            @click="onRowClick?.(row)"
          >
            <td
              v-for="col in columns"
              :key="col.key"
              :class="['px-5 py-3.5 text-foreground', col.className]"
            >
              <component :is="col.cell(row)" v-if="isVNode(col.cell(row))" />
              <span v-else>{{ col.cell(row) }}</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="px-5 py-3 text-xs text-muted-foreground">
      Showing <span class="font-medium text-foreground">{{ processed.length }}</span> of {{ data.length }}
    </div>
  </div>
</template>

<script setup lang="ts" generic="T">
import { ref, computed } from "vue";
import { isVNode } from "vue";
import { Search, ArrowUpDown, ArrowUp, ArrowDown } from "lucide-vue-next";

export interface DataTableColumn<Row> {
  key: string;
  header: string;
  cell: (row: Row) => unknown;
  sortable?: boolean;
  sortValue?: (row: Row) => string | number;
  className?: string;
}

const props = withDefaults(
  defineProps<{
    columns: DataTableColumn<T>[];
    data: T[];
    searchPlaceholder?: string;
    searchAccessor?: (row: T) => string;
    emptyMessage?: string;
    rowKey: (row: T) => string;
    onRowClick?: (row: T) => void;
  }>(),
  {
    searchPlaceholder: "Search...",
    emptyMessage: "No records found.",
  }
);

const query = ref("");
const sort = ref<{ key: string; direction: "asc" | "desc" } | null>(null);

const toggleSort = (key: string) => {
  if (!sort.value || sort.value.key !== key) {
    sort.value = { key, direction: "asc" };
  } else if (sort.value.direction === "asc") {
    sort.value = { key, direction: "desc" };
  } else {
    sort.value = null;
  }
};

const processed = computed(() => {
  let rows = props.data;

  if (query.value.trim() && props.searchAccessor) {
    const q = query.value.toLowerCase();
    rows = rows.filter((r) => props.searchAccessor!(r).toLowerCase().includes(q));
  }

  if (sort.value) {
    const s = sort.value;
    const col = props.columns.find((c) => c.key === s.key);
    if (col?.sortValue) {
      rows = [...rows].sort((a, b) => {
        const av = col.sortValue!(a);
        const bv = col.sortValue!(b);
        if (av < bv) return s.direction === "asc" ? -1 : 1;
        if (av > bv) return s.direction === "asc" ? 1 : -1;
        return 0;
      });
    }
  }

  return rows;
});
</script>
