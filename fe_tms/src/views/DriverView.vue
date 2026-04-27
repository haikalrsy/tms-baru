<template>
  <div v-if="authStore.loading || !authStore.user || !authStore.profile"
    class="flex min-h-screen items-center justify-center bg-background">
    <div class="h-10 w-10 animate-spin rounded-full border-2 border-primary border-t-transparent" />
  </div>
  <template v-else-if="authStore.profile.status === 'approved' && authStore.roles.includes('driver')">
    <DashboardLayout role="driver">
      <div class="flex flex-col gap-6">
        <div>
          <h1 class="font-display text-2xl font-semibold tracking-tight">Today's deliveries</h1>
          <p class="text-sm text-muted-foreground">Tap a delivery to view route and update status.</p>
        </div>

        <!-- Active delivery card -->
        <div v-if="active && activeWarehouse" class="rounded-xl border bg-card shadow-soft overflow-hidden">
          <div class="flex flex-col gap-3 border-b p-5 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex flex-col gap-1">
              <span class="inline-flex items-center rounded-full border border-primary/30 bg-primary/10 px-2.5 py-0.5 text-xs font-medium text-primary capitalize w-fit">
                {{ active.status.replace("_", " ") }}
              </span>
              <h2 class="font-display text-lg font-semibold mt-1">{{ active.customerName }}</h2>
              <span class="text-xs text-muted-foreground font-mono">{{ active.orderRef }}</span>
            </div>
            <div class="text-right text-sm">
              <div class="flex items-center justify-end gap-1.5 text-muted-foreground">
                <MapPin :size="14" /> {{ active.customerAddress }}
              </div>
              <div class="mt-1 font-medium tabular-nums">${{ active.amount.toLocaleString() }}</div>
            </div>
          </div>
          <div class="h-[340px]">
            <MapView :markers="markers" :routes="mapRoutes" :center="[activeWarehouse.lat, activeWarehouse.lng]" :zoom="12" />
          </div>
          <div class="flex flex-wrap gap-2 border-t p-4">
            <button class="btn-outline"><Phone class="mr-2 h-4 w-4" />Call customer</button>
            <button class="btn-primary"><Play class="mr-2 h-4 w-4" />Start delivery</button>
            <button class="btn-outline"><CheckCircle2 class="mr-2 h-4 w-4" />Complete</button>
            <button class="btn-outline"><Upload class="mr-2 h-4 w-4" />Upload POD</button>
          </div>
        </div>

        <!-- Queue -->
        <div class="flex flex-col gap-3">
          <h3 class="text-sm font-semibold uppercase tracking-wider text-muted-foreground">Queue</h3>
          <div class="flex flex-col gap-3">
            <div
              v-for="d in myDeliveries"
              :key="d.id"
              class="flex items-center gap-3 rounded-xl border bg-card p-4 shadow-soft hover:shadow-elevated transition-all"
            >
              <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                <Package :size="18" />
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                  <span class="font-medium truncate">{{ d.customerName }}</span>
                  <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-medium capitalize">
                    {{ d.status.replace("_", " ") }}
                  </span>
                </div>
                <div class="text-xs text-muted-foreground truncate">{{ d.customerAddress }}</div>
              </div>
              <div class="text-right">
                <div class="font-medium tabular-nums text-sm">${{ d.amount }}</div>
                <div class="text-xs text-muted-foreground tabular-nums">
                  {{ new Date(d.scheduledAt).toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" }) }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </DashboardLayout>
  </template>
</template>

<script setup lang="ts">
import { computed, watch } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import { storeToRefs } from "pinia";
import DashboardLayout from "@/components/DashboardLayout.vue";
import MapView, { type MapMarker, type MapRoute } from "@/components/MapView.vue";
import { deliveries, warehouses } from "@/lib/mock-data";
import { MapPin, Phone, Play, CheckCircle2, Upload, Package } from "lucide-vue-next";

const router = useRouter();
const authStore = useAuthStore();
const { user, loading, profile, roles } = storeToRefs(authStore);

watch([user, loading, profile, roles], () => {
  if (loading.value) return;
  if (!user.value) { router.push("/login"); return; }
  if (profile.value && profile.value.status !== "approved") { router.push("/pending-approval"); return; }
  if (!roles.value.includes("driver")) router.push(roles.value.includes("admin") ? "/admin" : "/login");
});

const myDeliveries = computed(() =>
  deliveries.filter((d) => ["in_transit", "packed", "picking"].includes(d.status)).slice(0, 3)
);

const active = computed(() => myDeliveries.value[0]);
const activeWarehouse = computed(() => warehouses.find((w) => w.id === active.value?.warehouseId));

const markers = computed<MapMarker[]>(() => {
  if (!active.value || !activeWarehouse.value) return [];
  return [
    { id: "wh", lat: activeWarehouse.value.lat, lng: activeWarehouse.value.lng, type: "warehouse", popup: activeWarehouse.value.name },
    { id: "cu", lat: active.value.customerLat, lng: active.value.customerLng, type: "customer", popup: active.value.customerName },
    {
      id: "me",
      lat: (activeWarehouse.value.lat + active.value.customerLat) / 2,
      lng: (activeWarehouse.value.lng + active.value.customerLng) / 2,
      type: "driver",
      popup: "You",
    },
  ];
});

const mapRoutes = computed<MapRoute[]>(() => {
  if (!active.value || !activeWarehouse.value) return [];
  const points = active.value.routePolyline.length
    ? active.value.routePolyline
    : [[activeWarehouse.value.lat, activeWarehouse.value.lng], [active.value.customerLat, active.value.customerLng]] as [number, number][];
  return [{ id: "r", points }];
});
</script>

<style scoped>
.btn-primary {
  @apply inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90;
}
.btn-outline {
  @apply inline-flex items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium transition-colors hover:bg-muted;
}
</style>
