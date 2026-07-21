<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'

definePage({
  meta: {
    action: 'list',
    subject: 'Plant-Asset',
  },
})

const { t } = useI18n()

const devices = ref([])
const loading = ref(true)
const selectedDevice = ref(null)
const deviceLogs = ref([])
const logsLoading = ref(false)

const fetchDevices = async () => {
  loading.value = true
  try {
    const { data } = await useApi<any>('/it/network-devices')
    devices.value = data.value || []
  } catch (error) {
    console.error('Error fetching devices:', error)
  } finally {
    loading.value = false
  }
}

const fetchDeviceLogs = async (deviceId: string) => {
  logsLoading.value = true
  try {
    const { data } = await useApi<any>(`/it/network-devices/${deviceId}/logs`)
    deviceLogs.value = data.value || []
  } catch (error) {
    console.error('Error fetching device logs:', error)
  } finally {
    logsLoading.value = false
  }
}

const selectDevice = (device: any) => {
  selectedDevice.value = device
  fetchDeviceLogs(device.id)
}

const getStatusColor = (status: string) => {
  switch (status) {
    case 'online':
      return 'success'
    case 'offline':
      return 'error'
    default:
      return 'grey'
  }
}

const formatDateTime = (date: string) => {
  if (!date) return '--'
  return new Date(date).toLocaleString()
}

onMounted(() => {
  fetchDevices()
})
</script>

<template>
  <VCard>
    <VCardTitle class="d-flex align-center justify-space-between pa-4">
      <span>{{ t('IT.NetworkMonitoring') }}</span>
      <VBtn
        icon="tabler-refresh"
        size="small"
        variant="text"
        @click="fetchDevices"
      />
    </VCardTitle>
    <VDivider />
    <VCardText class="pa-4">
      <VRow>
        <VCol cols="12" md="6">
          <h3 class="text-h6 mb-4">
            {{ t('IT.NetworkMonitoring.Devices') }}
          </h3>
          <div
            v-if="loading"
            class="text-center pa-4"
          >
            <VProgressCircular indeterminate />
          </div>
          <div
            v-else-if="devices.length === 0"
            class="text-center text-disabled pa-4"
          >
            {{ t('IT.NetworkMonitoring.NoDevices') }}
          </div>
          <VList v-else>
            <VListItem
              v-for="device in devices"
              :key="device.id"
              @click="selectDevice(device)"
              class="cursor-pointer"
              :class="{ 'bg-grey-lighten-4': selectedDevice?.id === device.id }"
            >
              <template #prepend>
                <VIcon
                  :color="getStatusColor(device.status)"
                  :icon="device.status === 'online' ? 'tabler-circle-check' : device.status === 'offline' ? 'tabler-circle-x' : 'tabler-circle-dotted'"
                />
              </template>
              <VListItemTitle>
                {{ device.asset?.serial_number || device.ip_address }}
              </VListItemTitle>
              <VListItemSubtitle>
                {{ device.ip_address }} - {{ device.device_type }}
              </VListItemSubtitle>
              <template #append>
                <VChip
                  :color="getStatusColor(device.status)"
                  size="small"
                >
                  {{ device.status }}
                </VChip>
              </template>
            </VListItem>
          </VList>
        </VCol>
        <VCol cols="12" md="6">
          <h3 class="text-h6 mb-4">
            {{ t('IT.NetworkMonitoring.DeviceDetails') }}
          </h3>
          <div
            v-if="!selectedDevice"
            class="text-center text-disabled pa-4"
          >
            {{ t('IT.NetworkMonitoring.SelectDevice') }}
          </div>
          <VCard
            v-else
            flat
            border
          >
            <VCardText>
              <VRow>
                <VCol cols="12" sm="6">
                  <VTextField
                    :model-value="selectedDevice.ip_address || '--'"
                    :label="t('IT.NetworkDevice.IPAddress')"
                    readonly
                  />
                </VCol>
                <VCol cols="12" sm="6">
                  <VTextField
                    :model-value="selectedDevice.status || '--'"
                    :label="t('IT.NetworkDevice.Status')"
                    readonly
                  />
                </VCol>
                <VCol cols="12" sm="6">
                  <VTextField
                    :model-value="selectedDevice.response_time_ms ? `${selectedDevice.response_time_ms}ms` : '--'"
                    :label="t('IT.NetworkDevice.ResponseTime')"
                    readonly
                  />
                </VCol>
                <VCol cols="12" sm="6">
                  <VTextField
                    :model-value="selectedDevice.uptime_percentage ? `${selectedDevice.uptime_percentage}%` : '--'"
                    :label="t('IT.NetworkDevice.Uptime')"
                    readonly
                  />
                </VCol>
                <VCol cols="12" sm="6">
                  <VTextField
                    :model-value="formatDateTime(selectedDevice.last_check_at)"
                    :label="t('IT.NetworkDevice.LastCheck')"
                    readonly
                  />
                </VCol>
                <VCol cols="12" sm="6">
                  <VTextField
                    :model-value="formatDateTime(selectedDevice.last_online_at)"
                    :label="t('IT.NetworkDevice.LastOnline')"
                    readonly
                  />
                </VCol>
              </VRow>
            </VCardText>
          </VCard>

          <h3 class="text-h6 mb-4 mt-4">
            {{ t('IT.NetworkMonitoring.RecentLogs') }}
          </h3>
          <div
            v-if="logsLoading"
            class="text-center pa-4"
          >
            <VProgressCircular indeterminate />
          </div>
          <VList v-else-if="deviceLogs.length > 0">
            <VListItem
              v-for="log in deviceLogs"
              :key="log.id"
            >
              <template #prepend>
                <VIcon
                  :color="getStatusColor(log.status)"
                  :icon="log.status === 'online' ? 'tabler-circle-check' : 'tabler-circle-x'"
                />
              </template>
              <VListItemTitle>
                {{ log.status }}
              </VListItemTitle>
              <VListItemSubtitle>
                {{ formatDateTime(log.checked_at) }} - {{ log.response_time_ms ? `${log.response_time_ms}ms` : '--' }}
              </VListItemSubtitle>
            </VListItem>
          </VList>
          <div
            v-else
            class="text-center text-disabled pa-4"
          >
            {{ t('IT.NetworkMonitoring.NoLogs') }}
          </div>
        </VCol>
      </VRow>
    </VCardText>
  </VCard>
</template>
