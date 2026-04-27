<template>
  <span :class="['inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium capitalize', config.className]">
    <span class="inline-block h-1.5 w-1.5 rounded-full bg-current" />
    {{ config.label }}
  </span>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { AccountStatus } from "@/stores/auth";

const props = defineProps<{ status: AccountStatus }>();

const map: Record<AccountStatus, { label: string; className: string }> = {
  pending: { label: "Pending", className: "bg-warning/15 text-warning-foreground border-warning/30" },
  approved: { label: "Approved", className: "bg-success/15 text-success border-success/30" },
  rejected: { label: "Rejected", className: "bg-destructive/15 text-destructive border-destructive/30" },
  suspended: { label: "Suspended", className: "bg-muted text-muted-foreground border-border" },
};

const config = computed(() => map[props.status]);
</script>
