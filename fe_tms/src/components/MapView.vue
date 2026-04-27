<template>
  <div ref="containerRef" :class="className" :style="{ height, minHeight: '280px', width: '100%' }" />
</template>

<script setup lang="ts">
import { ref, watch, onMounted, onUnmounted } from "vue";
import L, { type LatLngExpression } from "leaflet";

export type MapProvider = "osm" | "carto-light" | "carto-dark";

export interface MapMarker {
  id: string;
  lat: number;
  lng: number;
  label?: string;
  type: "warehouse" | "delivery" | "driver" | "customer";
  popup?: string;
}

export interface MapRoute {
  id: string;
  points: [number, number][];
  color?: string;
}

const TILE_URLS: Record<MapProvider, { url: string; attribution: string }> = {
  osm: { url: "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", attribution: "© OpenStreetMap" },
  "carto-light": { url: "https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png", attribution: "© OpenStreetMap, © CARTO" },
  "carto-dark": { url: "https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png", attribution: "© OpenStreetMap, © CARTO" },
};

const MARKER_STYLES: Record<MapMarker["type"], { color: string; icon: string }> = {
  warehouse: { color: "#6366f1", icon: "W" },
  delivery: { color: "#10b981", icon: "D" },
  driver: { color: "#f59e0b", icon: "•" },
  customer: { color: "#3b82f6", icon: "C" },
};

function makeIcon(type: MapMarker["type"]) {
  const style = MARKER_STYLES[type];
  const pulse = type === "driver"
    ? `<span class="absolute inset-0 rounded-full animate-ping" style="background:${style.color};opacity:.4"></span>`
    : "";
  const html = `
    <div class="relative flex h-8 w-8 items-center justify-center">
      ${pulse}
      <span class="relative flex h-7 w-7 items-center justify-center rounded-full text-[11px] font-bold text-white shadow-md ring-2 ring-white" style="background:${style.color}">
        ${style.icon}
      </span>
    </div>
  `;
  return L.divIcon({ html, className: "logistics-marker", iconSize: [32, 32], iconAnchor: [16, 16] });
}

const props = withDefaults(
  defineProps<{
    center?: [number, number];
    zoom?: number;
    markers?: MapMarker[];
    routes?: MapRoute[];
    provider?: MapProvider;
    className?: string;
    height?: string;
  }>(),
  {
    center: () => [40.73, -74.0] as [number, number],
    zoom: 12,
    markers: () => [],
    routes: () => [],
    provider: "carto-light",
    height: "100%",
  }
);

const containerRef = ref<HTMLDivElement | null>(null);
let mapInstance: L.Map | null = null;
let layerGroup: L.LayerGroup | null = null;

const updateLayers = () => {
  if (!mapInstance || !layerGroup) return;
  layerGroup.clearLayers();

  props.routes.forEach((r) => {
    L.polyline(r.points as LatLngExpression[], {
      color: r.color ?? "#6366f1",
      weight: 4,
      opacity: 0.7,
      dashArray: "8 6",
    }).addTo(layerGroup!);
  });

  props.markers.forEach((m) => {
    const marker = L.marker([m.lat, m.lng], { icon: makeIcon(m.type) }).addTo(layerGroup!);
    if (m.popup) marker.bindPopup(m.popup);
  });
};

onMounted(() => {
  if (!containerRef.value) return;
  mapInstance = L.map(containerRef.value, { zoomControl: true, attributionControl: true }).setView(props.center, props.zoom);
  layerGroup = L.layerGroup().addTo(mapInstance);
  const { url, attribution } = TILE_URLS[props.provider];
  L.tileLayer(url, { attribution, subdomains: "abcd", maxZoom: 19 }).addTo(mapInstance);
  updateLayers();
});

onUnmounted(() => {
  mapInstance?.remove();
  mapInstance = null;
});

watch([() => props.markers, () => props.routes], updateLayers, { deep: true });
</script>
