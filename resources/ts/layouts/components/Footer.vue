<script setup lang="ts">
const versionSystem = import.meta.env.VITE_APP_MAJOR
</script>

<template>
  <div class="h-100 d-flex align-center justify-space-between">
    <!-- 👉 Footer: left content -->
    <span class="d-flex align-center">
    </span>
    <!-- 👉 Footer: right content -->
    <span class="d-md-flex gap-x-4 text-primary d-none">
      V. {{ versionSystem }}
    </span>
  </div>
</template>
