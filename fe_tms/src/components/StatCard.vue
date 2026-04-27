<template>
  <div
    class="group relative flex min-w-[14rem] flex-col gap-3 rounded-xl border bg-card p-5 shadow-soft transition-all hover:shadow-elevated hover:-translate-y-0.5"
  >
    <div class="flex items-center justify-between">
      <span class="text-xs font-medium uppercase tracking-wider text-muted-foreground">{{ label }}</span>
      <span v-if="icon" :class="['flex h-9 w-9 items-center justify-center rounded-lg', accentClass]">
        <component :is="icon" :size="18" />
      </span>
    </div>
    <div class="text-3xl font-semibold tracking-tight text-foreground">{{ value }}</div>
    <div v-if="delta" :class="['text-xs font-medium', delta.positive ? 'text-success' : 'text-destructive']">
      {{ delta.positive ? "▲" : "▼" }} {{ delta.value }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { Component } from "vue";

interface Delta {
  value: string;
  positive?: boolean;
}

const props = withDefaults(
  defineProps<{
    label: string;
    value: string | number;
    delta?: Delta;
    icon?: Component;
    accent?: "primary" | "success" | "warning" | "destructive" | "info";
  }>(),
  { accent: "primary" }
);

const accentMap: Record<string, string> = {
  primary: "bg-primary/10 text-primary",
  success: "bg-success/10 text-success",
  warning: "bg-warning/15 text-warning-foreground",
  destructive: "bg-destructive/10 text-destructive",
  info: "bg-info/10 text-info",
};

const accentClass = computed(() => accentMap[props.accent]);
</script>
