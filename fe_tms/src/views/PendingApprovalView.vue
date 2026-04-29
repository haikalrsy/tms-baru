<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-white rounded-xl shadow-sm p-8 text-center">

      <!-- Icon — berbeda berdasarkan reason -->
      <div
        class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6"
        :class="isReactivation ? 'bg-destructive/10' : 'bg-warning/10'"
      >
        <ShieldOff v-if="isReactivation" class="w-8 h-8 text-destructive" />
        <Clock v-else class="w-8 h-8 text-warning" />
      </div>

      <!-- Judul & deskripsi -->
      <template v-if="isReactivation">
        <h1 class="text-2xl font-bold mb-2">Akun Kamu Disuspend</h1>
        <p class="text-muted-foreground text-sm leading-relaxed mb-8">
          Akun kamu sebelumnya telah disuspend oleh admin. Permintaan reaktivasi sudah otomatis dikirim —
          tunggu persetujuan admin untuk bisa mengakses sistem kembali.
        </p>
      </template>
      <template v-else>
        <h1 class="text-2xl font-bold mb-2">Menunggu Persetujuan</h1>
        <p class="text-muted-foreground text-sm leading-relaxed mb-8">
          Akun kamu sedang ditinjau oleh admin. Kamu akan mendapat notifikasi melalui email setelah akun disetujui.
        </p>
      </template>

      <div
        class="border rounded-lg p-4 text-left mb-8"
        :class="isReactivation ? 'bg-destructive/5 border-destructive/20' : 'bg-warning/5 border-warning/20'"
      >
        <p
          class="text-sm font-medium mb-2"
          :class="isReactivation ? 'text-destructive' : 'text-warning-foreground'"
        >
          Yang perlu kamu tahu:
        </p>
        <ul class="text-muted-foreground text-sm space-y-1.5">
          <li class="flex items-start gap-2">
            <span :class="isReactivation ? 'text-destructive' : 'text-warning'" class="mt-0.5">•</span>
            {{ isReactivation ? 'Kamu perlu disetujui ulang oleh admin' : 'Proses persetujuan biasanya 1x24 jam' }}
          </li>
          <li class="flex items-start gap-2">
            <span :class="isReactivation ? 'text-destructive' : 'text-warning'" class="mt-0.5">•</span>
            Notifikasi akan dikirim ke email kamu
          </li>
          <li class="flex items-start gap-2">
            <span :class="isReactivation ? 'text-destructive' : 'text-warning'" class="mt-0.5">•</span>
            Hubungi admin jika lebih dari 24 jam
          </li>
        </ul>
      </div>

      <RouterLink to="/login"
        class="block w-full inline-flex items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium hover:bg-muted transition-colors">
        Kembali ke Login
      </RouterLink>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { Clock, ShieldOff } from 'lucide-vue-next'

const route = useRoute()
const isReactivation = computed(() => route.query.reason === 'reactivation')
</script>