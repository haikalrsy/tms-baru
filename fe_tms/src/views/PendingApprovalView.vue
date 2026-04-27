<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-white rounded-xl shadow-sm p-8 text-center">

      <div class="w-16 h-16 bg-warning/10 rounded-full flex items-center justify-center mx-auto mb-6">
        <Clock class="w-8 h-8 text-warning" />
      </div>

      <h1 class="text-2xl font-bold mb-2">Menunggu Persetujuan</h1>
      <p class="text-muted-foreground text-sm leading-relaxed mb-8">
        Akun kamu sedang ditinjau oleh admin. Kamu akan mendapat notifikasi melalui email setelah akun disetujui.
      </p>

      <div class="bg-warning/5 border border-warning/20 rounded-lg p-4 text-left mb-8">
        <p class="text-warning-foreground text-sm font-medium mb-2">Yang perlu kamu tahu:</p>
        <ul class="text-muted-foreground text-sm space-y-1.5">
          <li class="flex items-start gap-2">
            <span class="text-warning mt-0.5">•</span>
            Proses persetujuan biasanya 1x24 jam
          </li>
          <li class="flex items-start gap-2">
            <span class="text-warning mt-0.5">•</span>
            Notifikasi akan dikirim ke email kamu
          </li>
          <li class="flex items-start gap-2">
            <span class="text-warning mt-0.5">•</span>
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
import { Clock } from 'lucide-vue-next'
</script>