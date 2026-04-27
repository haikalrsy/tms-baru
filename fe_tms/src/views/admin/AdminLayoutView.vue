<template>
  <div v-if="authStore.loading || !authStore.user || !authStore.profile"
    class="flex min-h-screen items-center justify-center bg-background">
    <div class="h-10 w-10 animate-spin rounded-full border-2 border-primary border-t-transparent" />
  </div>
  <template v-else-if="authStore.profile.status === 'approved' && authStore.roles.includes('admin')">
    <DashboardLayout role="admin">
      <RouterView />
    </DashboardLayout>
  </template>
</template>

<script setup lang="ts">
import { watch } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import { storeToRefs } from "pinia";
import DashboardLayout from "@/components/DashboardLayout.vue";

const router = useRouter();
const authStore = useAuthStore();
const { user, loading, profile, roles } = storeToRefs(authStore);

watch([user, loading, profile, roles], () => {
  if (loading.value) return;
  if (!user.value) { router.push("/login"); return; }
  if (profile.value && profile.value.status !== "approved") { router.push("/pending-approval"); return; }
  if (!roles.value.includes("admin")) router.push("/driver");
});
</script>
