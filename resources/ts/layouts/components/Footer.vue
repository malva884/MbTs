<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useApi } from '@/composables/useApi'

const versionSystem = ref('Loading...')

const fetchAppVersion = async () => {
  try {
    const { data } = await useApi<any>('/settings/app_version')
    if (data.value !== null) {
      versionSystem.value = data.value.value || 'Unknown'
    }
  } catch (error) {
    console.error('Failed to fetch app version:', error)
    versionSystem.value = 'Unknown'
  }
}

onMounted(() => {
  fetchAppVersion()
})
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
