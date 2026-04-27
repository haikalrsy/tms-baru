<template>
  <div class="flex min-h-screen w-full bg-background">
    <!-- Sidebar desktop -->
    <aside
      :class="[
        'hidden lg:flex flex-col border-r bg-sidebar transition-[width] duration-300',
        collapsed ? 'w-[72px]' : 'w-[248px]',
      ]"
    >
      <div class="flex h-16 items-center gap-2 border-b border-sidebar-border px-4">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary text-primary-foreground font-bold">
          LX
        </div>
        <div v-if="!collapsed" class="flex flex-col leading-tight">
          <span class="font-display text-sm font-semibold tracking-tight">LogiX</span>
          <span class="text-[10px] uppercase tracking-wider text-muted-foreground">TMS · WMS</span>
        </div>
      </div>

      <nav class="flex-1 overflow-y-auto px-3 py-4">
        <ul class="flex flex-col gap-1">
          <li v-for="item in nav" :key="item.to">
            <RouterLink
              :to="item.to"
              :class="[
                'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all',
                isActive(item.to)
                  ? 'bg-sidebar-accent text-sidebar-accent-foreground shadow-sm'
                  : 'text-sidebar-foreground hover:bg-sidebar-accent/60',
                collapsed && 'justify-center px-2',
              ]"
            >
              <component :is="item.icon" class="h-4.5 w-4.5 shrink-0" :size="18" />
              <span v-if="!collapsed" class="truncate">{{ item.label }}</span>
            </RouterLink>
          </li>
        </ul>
      </nav>

      <div class="border-t border-sidebar-border p-3">
        <button
          @click="collapsed = !collapsed"
          class="flex w-full items-center justify-center gap-2 rounded-lg p-2 text-xs text-muted-foreground hover:bg-sidebar-accent/60"
        >
          <ChevronLeft :class="['h-4 w-4 transition-transform', collapsed && 'rotate-180']" />
          <span v-if="!collapsed">Collapse</span>
        </button>
      </div>
    </aside>

    <!-- Mobile drawer -->
    <Transition name="fade">
      <div v-if="mobileOpen" class="fixed inset-0 z-50 lg:hidden">
        <div class="absolute inset-0 bg-foreground/30" @click="mobileOpen = false" />
        <aside class="absolute left-0 top-0 h-full w-[260px] bg-sidebar shadow-elevated animate-fade-in">
          <div class="flex h-16 items-center gap-2 border-b border-sidebar-border px-4">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary text-primary-foreground font-bold">LX</div>
            <span class="font-display font-semibold">LogiX</span>
          </div>
          <nav class="px-3 py-4">
            <ul class="flex flex-col gap-1">
              <li v-for="item in nav" :key="item.to">
                <RouterLink
                  :to="item.to"
                  @click="mobileOpen = false"
                  class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-sidebar-foreground hover:bg-sidebar-accent/60"
                >
                  <component :is="item.icon" :size="18" />
                  <span>{{ item.label }}</span>
                </RouterLink>
              </li>
            </ul>
          </nav>
        </aside>
      </div>
    </Transition>

    <div class="flex min-w-0 flex-1 flex-col">
      <!-- Topbar -->
      <header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b bg-surface/80 px-4 backdrop-blur-md sm:px-6">
        <button
          class="rounded-md p-2 hover:bg-muted lg:hidden"
          @click="mobileOpen = true"
          aria-label="Open menu"
        >
          <Menu class="h-5 w-5" />
        </button>
        <div class="relative hidden flex-1 max-w-md md:block">
          <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
          <input
            placeholder="Search orders, drivers, deliveries..."
            class="flex h-10 w-full rounded-md border border-transparent bg-surface-2 pl-9 pr-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
          />
        </div>
        <div class="flex flex-1 md:flex-none" />
        <button
          @click="themeStore.toggle()"
          class="rounded-md p-2 text-muted-foreground hover:bg-muted hover:text-foreground"
          aria-label="Toggle theme"
        >
          <Sun v-if="themeStore.isDark" class="h-4 w-4" :size="18" />
          <Moon v-else class="h-4 w-4" :size="18" />
        </button>
        <button class="relative rounded-md p-2 text-muted-foreground hover:bg-muted hover:text-foreground">
          <Bell class="h-4 w-4" :size="18" />
          <span class="absolute right-1.5 top-1.5 h-1.5 w-1.5 rounded-full bg-destructive" />
        </button>
        <div class="flex items-center gap-3 rounded-lg border bg-surface-2 px-2.5 py-1.5">
          <div class="flex h-7 w-7 items-center justify-center rounded-md bg-primary text-primary-foreground text-xs font-semibold">
            {{ initials }}
          </div>
          <div class="hidden flex-col text-xs leading-tight sm:flex">
            <span class="font-semibold text-foreground">{{ authStore.profile?.full_name || authStore.profile?.email }}</span>
            <span class="text-muted-foreground capitalize">{{ role }}</span>
          </div>
          <button
            @click="authStore.signOut()"
            class="inline-flex items-center justify-center rounded-md p-1.5 hover:bg-muted"
            aria-label="Sign out"
          >
            <LogOut class="h-4 w-4" />
          </button>
        </div>
      </header>

      <main class="flex-1 overflow-x-hidden p-4 sm:p-6 lg:p-8 animate-fade-in">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import { useRoute } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import { useThemeStore } from "@/stores/theme";
import {
  LayoutDashboard, Users, Truck, Package, Warehouse, Boxes, ClipboardList,
  LogOut, Moon, Sun, Bell, Search, ChevronLeft, Menu,
} from "lucide-vue-next";

const props = defineProps<{ role: "admin" | "driver" }>();

const authStore = useAuthStore();
const themeStore = useThemeStore();
const route = useRoute();

const collapsed = ref(false);
const mobileOpen = ref(false);

const adminNav = [
  { to: "/admin", label: "Overview", icon: LayoutDashboard },
  { to: "/admin/accounts", label: "Accounts", icon: Users },
  { to: "/admin/deliveries", label: "Deliveries", icon: ClipboardList },
  { to: "/admin/drivers", label: "Drivers", icon: Users },
  { to: "/admin/vehicles", label: "Vehicles", icon: Truck },
  { to: "/admin/warehouses", label: "Warehouses", icon: Warehouse },
  { to: "/admin/stock", label: "Stock", icon: Boxes },
];

const driverNav = [{ to: "/driver", label: "My Deliveries", icon: Package }];

const nav = computed(() => (props.role === "admin" ? adminNav : driverNav));

const isActive = (to: string) => {
  if (to === "/admin" || to === "/driver") return route.path === to;
  return route.path.startsWith(to);
};

const initials = computed(() => {
  const name = authStore.profile?.full_name || authStore.profile?.email || "U";
  return name.split(" ").map((s) => s[0]).slice(0, 2).join("").toUpperCase();
});
</script>
