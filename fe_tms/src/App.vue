<template>
  <div :class="{ dark: isDark }">
    <RouterView />
    <Toaster rich-colors position="top-right" />
  </div>
</template>

<script setup lang="ts">
import { Toaster } from "vue-sonner";
import { useThemeStore } from "@/stores/theme";
import { storeToRefs } from "pinia";
import { onMounted } from "vue";
import { useAuthStore } from "@/stores/auth";

const themeStore = useThemeStore();
const { isDark } = storeToRefs(themeStore);
themeStore.init();

const authStore = useAuthStore();
onMounted(() => authStore.init());
</script>
