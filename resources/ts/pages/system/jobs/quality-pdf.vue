<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const jobLogs = ref([])
const stats = ref(null)
const loading = ref(false)
const limit = ref(20)

// Ottieni gli ultimi job
const fetchJobLogs = async () => {
  loading.value = true
  try {
    const { data } = await useApi(`/job-logs?limit=${limit.value}`)
    if (data.value && data.value.data) {
      jobLogs.value = data.value.data
    }
  } catch (error) {
    console.error('Errore nel caricamento dei job logs:', error)
  } finally {
    loading.value = false
  }
}

// Ottieni statistiche
const fetchStats = async () => {
  try {
    const { data } = await useApi('/job-logs/stats')
    if (data.value) {
      stats.value = data.value
    }
  } catch (error) {
    console.error('Errore nel caricamento delle statistiche:', error)
  }
}

// Refresh dati
const refreshData = () => {
  fetchJobLogs()
  fetchStats()
}

onMounted(() => {
  fetchJobLogs()
  fetchStats()
})
</script>

<template>
  <div class="workspace-container w-100 d-flex flex-column pa-4 gap-3">
    <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
      <VCardText class="d-flex align-center justify-space-between py-3">
        <div class="d-flex align-center gap-2">
          <VIcon icon="tabler-file-pdf" size="24" color="primary" />
          <div>
            <div class="text-h6 font-weight-medium">ProcessQualityPdf Job Logs</div>
            <div class="text-caption text-medium-emphasis">Monitoraggio elaborazione PDF Quality</div>
          </div>
        </div>
        <div class="d-flex align-center gap-2">
          <VSelect
            v-model="limit"
            :items="[10, 20, 50, 100]"
            label="Risultati"
            density="compact"
            style="width: 100px"
            @update:model-value="fetchJobLogs"
          />
          <VBtn
            icon="tabler-refresh"
            variant="text"
            :loading="loading"
            @click="refreshData"
          />
        </div>
      </VCardText>
      <VDivider />
      
      <!-- Statistiche -->
      <VCardText v-if="stats" class="pa-4">
        <VRow>
          <VCol cols="12" sm="2.4">
            <VCard class="stat-card" variant="tonal" color="default">
              <VCardText class="text-center">
                <div class="text-h4 font-weight-bold">{{ stats.total }}</div>
                <div class="text-caption text-medium-emphasis">Totali</div>
              </VCardText>
            </VCard>
          </VCol>
          <VCol cols="12" sm="2.4">
            <VCard class="stat-card" variant="tonal" color="info">
              <VCardText class="text-center">
                <div class="text-h4 font-weight-bold">{{ stats.running }}</div>
                <div class="text-caption text-medium-emphasis">Running</div>
              </VCardText>
            </VCard>
          </VCol>
          <VCol cols="12" sm="2.4">
            <VCard class="stat-card" variant="tonal" color="success">
              <VCardText class="text-center">
                <div class="text-h4 font-weight-bold">{{ stats.success }}</div>
                <div class="text-caption text-medium-emphasis">Success</div>
              </VCardText>
            </VCard>
          </VCol>
          <VCol cols="12" sm="2.4">
            <VCard class="stat-card" variant="tonal" color="error">
              <VCardText class="text-center">
                <div class="text-h4 font-weight-bold">{{ stats.failed }}</div>
                <div class="text-caption text-medium-emphasis">Failed</div>
              </VCardText>
            </VCard>
          </VCol>
          <VCol cols="12" sm="2.4">
            <VCard class="stat-card" variant="tonal" color="warning">
              <VCardText class="text-center">
                <div class="text-h4 font-weight-bold">{{ stats.last_24h }}</div>
                <div class="text-caption text-medium-emphasis">24h</div>
              </VCardText>
            </VCard>
          </VCol>
        </VRow>
      </VCardText>
      <VDivider />
      
      <!-- Lista job -->
      <VCardText class="pa-4">
        <div v-if="loading" class="text-center py-8">
          <VProgressCircular indeterminate color="primary" />
          <div class="text-caption mt-2">Caricamento...</div>
        </div>
        
        <div v-else-if="jobLogs.length === 0" class="text-center py-8">
          <VIcon icon="tabler-file-off" size="48" color="grey" />
          <div class="text-caption text-medium-emphasis mt-2">Nessun job trovato</div>
        </div>
        
        <VTimeline v-else density="compact" side="end">
          <VTimelineItem
            v-for="log in jobLogs"
            :key="log.id"
            :dot-color="log.status === 'success' ? 'success' : log.status === 'failed' ? 'error' : 'info'"
            size="small"
          >
            <template #icon>
              <VIcon size="20">
                {{ log.status === 'success' ? 'tabler-check' : log.status === 'failed' ? 'tabler-x' : 'tabler-loader' }}
              </VIcon>
            </template>
            
            <VCard variant="outlined" class="mb-2">
              <VCardText class="pa-3">
                <div class="d-flex align-center justify-space-between mb-2">
                  <div class="d-flex align-center gap-2">
                    <VIcon icon="tabler-file" size="16" color="primary" />
                    <span class="font-weight-medium">{{ log.payload?.file }}</span>
                  </div>
                  <VChip
                    :color="log.status === 'success' ? 'success' : log.status === 'failed' ? 'error' : 'info'"
                    size="small"
                    variant="flat"
                  >
                    {{ log.status }}
                  </VChip>
                </div>
                
                <div class="text-body-2 mb-2">{{ log.output }}</div>
                
                <div v-if="log.error_message" class="text-error text-caption mb-2">
                  <VIcon icon="tabler-alert-circle" size="14" class="mr-1" />
                  {{ log.error_message }}
                </div>
                
                <div class="d-flex align-center gap-3 text-caption text-medium-emphasis">
                  <span>
                    <VIcon icon="tabler-clock" size="12" class="mr-1" />
                    {{ new Date(log.started_at).toLocaleString() }}
                  </span>
                  <span v-if="log.finished_at">
                    <VIcon icon="tabler-flag" size="12" class="mr-1" />
                    {{ new Date(log.finished_at).toLocaleString() }}
                  </span>
                  <span v-if="log.duration">
                    <VIcon icon="tabler-hourglass" size="12" class="mr-1" />
                    {{ log.duration.toFixed(2) }}s
                  </span>
                </div>
              </VCardText>
            </VCard>
          </VTimelineItem>
        </VTimeline>
      </VCardText>
    </VCard>
  </div>
</template>

<style scoped>
.stat-card {
  height: 100%;
}
</style>
