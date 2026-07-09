<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { VCard, VCardTitle, VCardText, VRow, VCol, VChip, VBtn, VProgressLinear, VAlert } from 'vuetify/components'

const { t } = useI18n()

definePage({
  meta: {
    action: 'admin',
    subject: 'SystemHealth',
  },
})

const services = ref([])
const overallStatus = ref('')
const checkedAt = ref('')
const loading = ref(false)
const error = ref(null)

const fetchHealthStatus = async () => {
  loading.value = true
  error.value = null
  try {
    const { data: resultData, error: apiError } = await useApi<any>('/system/health')
    if (resultData.value !== null) {
      services.value = resultData.value.services || []
      overallStatus.value = resultData.value.overall_status || 'unknown'
      checkedAt.value = resultData.value.checked_at || ''
      if (resultData.value.error) {
        error.value = resultData.value.error
      }
    } else {
      services.value = []
      overallStatus.value = 'error'
      error.value = apiError?.message || 'Unknown error'
    }
  } catch (err) {
    error.value = err.message || 'Unknown error'
    services.value = []
  } finally {
    loading.value = false
  }
}

const getStatusColor = (status: string) => {
  switch (status) {
    case 'healthy':
      return 'success'
    case 'unhealthy':
      return 'error'
    case 'degraded':
      return 'warning'
    default:
      return 'grey'
  }
}

const getStatusIcon = (status: string) => {
  switch (status) {
    case 'healthy':
      return 'tabler-check'
    case 'unhealthy':
      return 'tabler-x'
    case 'degraded':
      return 'tabler-alert-triangle'
    default:
      return 'tabler-help'
  }
}

const getOverallStatusColor = (status: string) => {
  switch (status) {
    case 'healthy':
      return 'success'
    case 'unhealthy':
      return 'error'
    case 'degraded':
      return 'warning'
    default:
      return 'grey'
  }
}

const formatBytes = (bytes: number | null) => {
  if (bytes === null || bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return `${(bytes / Math.pow(k, i)).toFixed(2)} ${sizes[i]}`
}

const formatNumber = (num: number | null) => {
  if (num === null) return 'N/A'
  return num.toLocaleString()
}

onMounted(() => {
  fetchHealthStatus()
  // Auto-refresh every 30 seconds
  setInterval(fetchHealthStatus, 30000)
})
</script>

<template>
  <div>
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between">
        <span>{{ t('SystemHealth.Title') }}</span>
        <VBtn
          color="primary"
          variant="tonal"
          :loading="loading"
          @click="fetchHealthStatus"
        >
          <VIcon start icon="tabler-refresh" />
          {{ t('SystemHealth.Refresh') }}
        </VBtn>
      </VCardTitle>

      <VCardText>
        <VAlert
          v-if="error"
          type="error"
          class="mb-4"
        >
          {{ error }}
        </VAlert>

        <!-- Overall Status -->
        <VCard
          class="mb-4"
          :color="getOverallStatusColor(overallStatus)"
          variant="outlined"
        >
          <VCardText class="d-flex align-center justify-space-between">
            <div class="d-flex align-center">
              <VIcon
                :icon="getStatusIcon(overallStatus)"
                size="32"
                class="mr-3"
              />
              <div>
                <div class="text-h6">
                  {{ t('SystemHealth.OverallStatus') }}: {{ overallStatus?.toUpperCase() }}
                </div>
                <div class="text-caption">
                  {{ t('SystemHealth.LastChecked') }}: {{ checkedAt ? new Date(checkedAt).toLocaleString() : t('SystemHealth.Never') }}
                </div>
              </div>
            </div>
            <VChip
              :color="getOverallStatusColor(overallStatus)"
              size="large"
            >
              {{ services.filter(s => s.status === 'healthy').length }} / {{ services.length }} {{ t('SystemHealth.Healthy') }}
            </VChip>
          </VCardText>
        </VCard>

        <!-- Services List -->
        <VRow>
          <VCol
            v-for="service in services"
            :key="service.name"
            cols="12"
            md="6"
            lg="4"
          >
            <VCard
              :color="getStatusColor(service.status)"
              variant="outlined"
              class="h-100"
            >
              <VCardText>
                <div class="d-flex align-center mb-2">
                  <VIcon
                    :icon="getStatusIcon(service.status)"
                    :color="getStatusColor(service.status)"
                    size="24"
                    class="mr-2"
                  />
                  <div class="text-h6">
                    {{ service.name }}
                  </div>
                </div>

                <VChip
                  :color="getStatusColor(service.status)"
                  size="small"
                  class="mb-2"
                >
                  {{ service.status?.toUpperCase() }}
                </VChip>

                <div class="text-body-2 mb-2">
                  {{ service.message }}
                </div>

                <div
                  v-if="service.host"
                  class="text-caption mb-1"
                >
                  <strong>{{ t('SystemHealth.Host') }}:</strong> {{ service.host }}:{{ service.port || 'default' }}
                </div>

                <div
                  v-if="service.database"
                  class="text-caption mb-1"
                >
                  <strong>{{ t('SystemHealth.Database') }}:</strong> {{ service.database }}
                </div>

                <div
                  v-if="service.response_time"
                  class="d-flex align-center"
                >
                  <VIcon
                    icon="tabler-clock"
                    size="16"
                    class="mr-1"
                  />
                  <span class="text-caption">
                    {{ service.response_time }}ms
                  </span>
                </div>

                <div
                  v-if="service.cpu_usage !== undefined"
                  class="mt-2"
                >
                  <div class="text-caption mb-1">
                    <strong>{{ t('SystemHealth.CPU') }}:</strong> {{ service.cpu_usage }}%
                    <VProgressLinear
                      :model-value="service.cpu_usage"
                      :color="service.cpu_usage > 80 ? 'error' : service.cpu_usage > 60 ? 'warning' : 'success'"
                      height="6"
                      class="mt-1"
                    />
                  </div>
                  <div class="text-caption mb-1">
                    <strong>{{ t('SystemHealth.Memory') }}:</strong> {{ service.memory_usage }}%
                    <VProgressLinear
                      :model-value="service.memory_usage"
                      :color="service.memory_usage > 80 ? 'error' : service.memory_usage > 60 ? 'warning' : 'success'"
                      height="6"
                      class="mt-1"
                    />
                  </div>
                  <div class="text-caption mb-1">
                    <strong>{{ t('SystemHealth.Disk') }}:</strong> {{ service.disk_usage }}%
                    <VProgressLinear
                      :model-value="service.disk_usage"
                      :color="service.disk_usage > 90 ? 'error' : service.disk_usage > 75 ? 'warning' : 'success'"
                      height="6"
                      class="mt-1"
                    />
                  </div>
                  <div
                    v-if="service.load_average"
                    class="mt-2"
                  >
                    <div v-if="service.load_average.type === 'queue_length'" class="text-caption">
                      <strong>{{ t('SystemHealth.QueueLength') }}:</strong> {{ service.load_average['1min']?.toFixed(2) || 'N/A' }}
                    </div>
                    <div v-else class="text-caption">
                      <strong>{{ t('SystemHealth.LoadAverage') }}:</strong>
                      <div v-if="service.load_average['1min'] !== null" class="text-caption">
                        {{ t('SystemHealth.1min') }}: {{ service.load_average['1min'].toFixed(2) }}
                      </div>
                      <div v-if="service.load_average['5min'] !== null" class="text-caption">
                        {{ t('SystemHealth.5min') }}: {{ service.load_average['5min'].toFixed(2) }}
                      </div>
                      <div v-if="service.load_average['15min'] !== null" class="text-caption">
                        {{ t('SystemHealth.15min') }}: {{ service.load_average['15min'].toFixed(2) }}
                      </div>
                    </div>
                  </div>
                </div>

                <div
                  v-if="service.storage_usage_percent !== undefined"
                  class="mt-2"
                >
                  <div class="text-caption mb-1">
                    <strong>{{ t('SystemHealth.StorageUsage') }}:</strong> {{ service.storage_usage_percent }}%
                    <VProgressLinear
                      :model-value="service.storage_usage_percent"
                      :color="service.storage_usage_percent > 90 ? 'error' : service.storage_usage_percent > 75 ? 'warning' : 'success'"
                      height="6"
                      class="mt-1"
                    />
                  </div>
                  <div class="text-caption">
                    <strong>{{ t('SystemHealth.StorageUsed') }}:</strong> {{ formatBytes(service.storage_used) }} / {{ formatBytes(service.storage_limit) }}
                  </div>
                </div>

                <div
                  v-if="service.api_quota_limit !== undefined"
                  class="mt-2"
                >
                  <div class="text-caption mb-1">
                    <strong>{{ t('SystemHealth.ApiQuota') }}:</strong>
                  </div>
                  <div class="text-caption">
                    <strong>{{ t('SystemHealth.ApiQuotaLimit') }}:</strong> {{ formatNumber(service.api_quota_limit) }}
                  </div>
                  <div v-if="service.api_quota_used !== null" class="text-caption">
                    <strong>{{ t('SystemHealth.ApiQuotaUsed') }}:</strong> {{ formatNumber(service.api_quota_used) }}
                  </div>
                  <div v-else class="text-caption text-medium-emphasis">
                    <em>{{ t('SystemHealth.ApiQuotaUsed') }}: N/A (requires Google Cloud Platform monitoring)</em>
                  </div>
                </div>

                <div
                  v-if="service.models && service.models.length > 0"
                  class="mt-2"
                >
                  <div class="text-caption mb-1">
                    <strong>Models Status:</strong>
                  </div>
                  <div
                    v-for="model in service.models"
                    :key="model.model"
                    class="text-caption mb-1"
                  >
                    <div class="d-flex align-center">
                      <VIcon
                        :icon="getStatusIcon(model.status)"
                        :color="getStatusColor(model.status)"
                        size="16"
                        class="mr-1"
                      />
                      <span class="mr-2">{{ model.model }}:</span>
                      <VChip
                        :color="getStatusColor(model.status)"
                        size="x-small"
                        class="mr-2"
                      >
                        {{ model.status?.toUpperCase() }}
                      </VChip>
                      <span v-if="model.response_time" class="text-caption">
                        {{ model.response_time }}ms
                      </span>
                      <span v-if="model.quota_info" class="text-caption text-warning ml-2">
                        ({{ model.quota_info }})
                      </span>
                    </div>
                  </div>
                </div>
              </VCardText>
            </VCard>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>
  </div>
</template>
