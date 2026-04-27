<template>
  <Transition name="splash" appear>
    <div
      v-if="show"
      class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-background"
    >
      <div class="splash-logo flex h-20 w-20 items-center justify-center rounded-2xl bg-primary text-primary-foreground shadow-elevated">
        <span class="font-display text-3xl font-bold">LX</span>
      </div>
      <h1 class="splash-title mt-6 font-display text-2xl font-semibold tracking-tight">LogiX</h1>
      <p class="splash-sub mt-1 text-sm text-muted-foreground">Logistics, unified.</p>
      <div class="splash-bar mt-10">
        <div class="h-1 w-32 overflow-hidden rounded-full bg-muted">
          <div class="progress-bar h-full w-1/2 bg-primary" />
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import { storeToRefs } from "pinia";

const router = useRouter();
const authStore = useAuthStore();
const { user, profile, roles, loading } = storeToRefs(authStore);

const show = ref(true);

onMounted(() => {
  setTimeout(() => (show.value = false), 1400);
});

watch([show, loading], () => {
  if (show.value || loading.value) return;
  if (!user.value) { router.push("/login"); return; }
  if (profile.value && profile.value.status !== "approved") { router.push("/pending-approval"); return; }
  if (roles.value.includes("admin")) router.push("/admin");
  else router.push("/driver");
});
</script>

<style scoped>
.splash-enter-active { transition: opacity 0.4s ease; }
.splash-leave-active { transition: opacity 0.4s ease; }
.splash-leave-to { opacity: 0; }

.splash-logo {
  animation: scale-in 0.6s ease-out both;
}
.splash-title {
  animation: fade-up 0.5s 0.25s ease-out both;
}
.splash-sub {
  animation: fade-up 0.5s 0.4s ease-out both;
}
.splash-bar {
  animation: fade-up 0.4s 0.7s ease-out both;
}
.progress-bar {
  animation: slide-progress 1.2s ease-in-out infinite;
}

@keyframes scale-in {
  from { transform: scale(0.7); opacity: 0; }
  to { transform: scale(1); opacity: 1; }
}
@keyframes fade-up {
  from { transform: translateY(12px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}
@keyframes slide-progress {
  from { transform: translateX(-100%); }
  to { transform: translateX(200%); }
}
</style>
