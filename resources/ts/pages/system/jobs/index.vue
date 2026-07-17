<template>
  <div class="workspace-container w-100 d-flex flex-column pa-4 gap-3">
    <!-- Stats Cards -->
    <VRow>
      <VCol cols="12" sm="6" md="3">
        <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
          <VCardText class="d-flex align-center gap-3">
            <div class="v-avatar v-avatar--variant-tonal v-avatar--density-default v-avatar--rounded bg-primary">
              <VIcon icon="tabler-stack" size="24" />
            </div>
            <div>
              <div class="text-h6 font-weight-medium">{{ stats?.total_jobs || 0 }}</div>
              <div class="text-caption text-medium-emphasis">Total Jobs</div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" sm="6" md="3">
        <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
          <VCardText class="d-flex align-center gap-3">
            <div class="v-avatar v-avatar--variant-tonal v-avatar--density-default v-avatar--rounded bg-success">
              <VIcon icon="tabler-check" size="24" />
            </div>
            <div>
              <div class="text-h6 font-weight-medium">{{ stats?.success_jobs || 0 }}</div>
              <div class="text-caption text-medium-emphasis">Success</div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" sm="6" md="3">
        <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
          <VCardText class="d-flex align-center gap-3">
            <div class="v-avatar v-avatar--variant-tonal v-avatar--density-default v-avatar--rounded bg-warning">
              <VIcon icon="tabler-loader" size="24" />
            </div>
            <div>
              <div class="text-h6 font-weight-medium">{{ stats?.running_jobs || 0 }}</div>
              <div class="text-caption text-medium-emphasis">Running</div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" sm="6" md="3">
        <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
          <VCardText class="d-flex align-center gap-3">
            <div class="v-avatar v-avatar--variant-tonal v-avatar--density-default v-avatar--rounded bg-error">
              <VIcon icon="tabler-x" size="24" />
            </div>
            <div>
              <div class="text-h6 font-weight-medium">{{ stats?.failed_jobs || 0 }}</div>
              <div class="text-caption text-medium-emphasis">Failed</div>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <VRow>
      <VCol cols="12" md="6">
        <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
          <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
            <div class="d-flex align-center gap-2">
              <VIcon icon="tabler-stack" size="24" color="primary" />
              <div>
                <div class="text-h6 font-weight-medium">Queue Jobs</div>
                <div class="text-caption text-medium-emphasis">{{ queueJobs.length }} queue jobs</div>
              </div>
            </div>
            <div class="d-flex align-center gap-2">
              <VBtn
                prepend-icon="tabler-refresh"
                color="secondary"
                variant="outlined"
                density="comfortable"
                class="px-3"
                @click="loadQueueJobs"
              >
                Refresh
              </VBtn>
              <VBtn
                prepend-icon="tabler-list"
                color="primary"
                variant="flat"
                density="comfortable"
                class="px-3"
                :to="{ name: 'system-jobs-queue' }"
              >
                View All
              </VBtn>
            </div>
          </VCardText>
          <VDivider />
          <VCardText class="pa-3">
            <VList v-if="queueJobs.length > 0" class="pa-0">
              <VListItem v-for="job in queueJobs.slice(0, 5)" :key="job.name">
                <VListItemTitle>{{ job.name }}</VListItemTitle>
                <VListItemSubtitle>
                  Last run: {{ job.last_run ? formatDate(job.last_run.created_at) : 'Never' }}
                </VListItemSubtitle>
                <template #append>
                  <VChip :color="getStatusColor(job.last_run?.status)" size="small">
                    {{ job.last_run?.status || 'Never run' }}
                  </VChip>
                </template>
              </VListItem>
            </VList>
            <VAlert v-else type="info" variant="tonal" class="mt-2">
              No queue jobs found
            </VAlert>
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" md="6">
        <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
          <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
            <div class="d-flex align-center gap-2">
              <VIcon icon="tabler-clock" size="24" color="primary" />
              <div>
                <div class="text-h6 font-weight-medium">Cron Jobs</div>
                <div class="text-caption text-medium-emphasis">{{ cronJobs.length }} cron jobs</div>
              </div>
            </div>
            <div class="d-flex align-center gap-2">
              <VBtn
                prepend-icon="tabler-refresh"
                color="secondary"
                variant="outlined"
                density="comfortable"
                class="px-3"
                @click="loadCronJobs"
              >
                Refresh
              </VBtn>
              <VBtn
                prepend-icon="tabler-list"
                color="primary"
                variant="flat"
                density="comfortable"
                class="px-3"
                :to="{ name: 'system-jobs-cron' }"
              >
                View All
              </VBtn>
            </div>
          </VCardText>
          <VDivider />
          <VCardText class="pa-3">
            <VList v-if="cronJobs.length > 0" class="pa-0">
              <VListItem v-for="job in cronJobs.slice(0, 5)" :key="job.command">
                <VListItemTitle>{{ job.command }}</VListItemTitle>
                <VListItemSubtitle>
                  {{ job.schedule_info?.method || 'unknown' }} {{ job.schedule_info?.parameters || '' }}
                </VListItemSubtitle>
                <template #append>
                  <VChip :color="getStatusColor(job.last_run?.status)" size="small">
                    {{ job.last_run?.status || 'Never run' }}
                  </VChip>
                </template>
              </VListItem>
            </VList>
            <VAlert v-else type="info" variant="tonal" class="mt-2">
              No cron jobs found
            </VAlert>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <VRow class="mt-4">
      <VCol cols="12" md="6">
        <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
          <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
            <div class="d-flex align-center gap-2">
              <VIcon icon="tabler-history" size="24" color="primary" />
              <div>
                <div class="text-h6 font-weight-medium">Recent Jobs</div>
                <div class="text-caption text-medium-emphasis">{{ recentJobs.length }} recent executions</div>
              </div>
            </div>
            <div class="d-flex align-center gap-2">
              <VBtn
                prepend-icon="tabler-list"
                color="primary"
                variant="flat"
                density="comfortable"
                class="px-3"
                :to="{ name: 'system-jobs-logs' }"
              >
                View All Logs
              </VBtn>
            </div>
          </VCardText>
          <VDivider />
          <VCardText class="pa-3">
            <VList v-if="recentJobs.length > 0" class="pa-0">
              <VListItem v-for="job in recentJobs" :key="job.id">
                <VListItemTitle>{{ job.job_name }}</VListItemTitle>
                <VListItemSubtitle>
                  {{ job.job_type }} - {{ formatDate(job.created_at) }}
                </VListItemSubtitle>
                <template #append>
                  <VChip :color="getStatusColor(job.status)" size="small">
                    {{ job.status }}
                  </VChip>
                </template>
              </VListItem>
            </VList>
            <VAlert v-else type="info" variant="tonal" class="mt-2">
              No recent jobs found
            </VAlert>
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" md="6">
        <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
          <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
            <div class="d-flex align-center gap-2">
              <VIcon icon="tabler-alert-circle" size="24" color="error" />
              <div>
                <div class="text-h6 font-weight-medium">Failed Jobs</div>
                <div class="text-caption text-medium-emphasis">{{ failedJobs.length }} failed executions</div>
              </div>
            </div>
            <div class="d-flex align-center gap-2">
              <VBtn
                prepend-icon="tabler-list"
                color="primary"
                variant="flat"
                density="comfortable"
                class="px-3"
                :to="{ name: 'system-jobs-logs' }"
              >
                View All Logs
              </VBtn>
            </div>
          </VCardText>
          <VDivider />
          <VCardText class="pa-3">
            <VList v-if="failedJobs.length > 0" class="pa-0">
              <VListItem v-for="job in failedJobs" :key="job.id">
                <VListItemTitle>{{ job.job_name }}</VListItemTitle>
                <VListItemSubtitle>
                  {{ job.job_type }} - {{ formatDate(job.created_at) }}
                </VListItemSubtitle>
                <template #append>
                  <VChip color="error" size="small">
                    {{ job.status }}
                  </VChip>
                </template>
              </VListItem>
            </VList>
            <VAlert v-else type="success" variant="tonal" class="mt-2">
              No failed jobs
            </VAlert>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'

definePage({
  meta: {
    action: 'view',
    subject: 'System',
  },
})

const router = useRouter()
const stats = ref({
  total_jobs: 0,
  running_jobs: 0,
  failed_jobs: 0,
  success_jobs: 0,
  queue_jobs: 0,
  cron_jobs: 0,
})
const queueJobs = ref([])
const cronJobs = ref([])
const recentJobs = ref([])
const failedJobs = ref([])

const loadDashboard = async () => {
  try {
    const { data: resultData } = await useApi<any>('/jobs/dashboard')
    if (resultData.value && resultData.value.stats) {
      stats.value = resultData.value.stats
      recentJobs.value = resultData.value.recent_jobs || []
      failedJobs.value = resultData.value.failed_jobs || []
    }
  } catch (error) {
    console.error('Error loading dashboard:', error)
  }
}

const loadQueueJobs = async () => {
  try {
    const { data: resultData } = await useApi<any>('/jobs/queue')
    if (resultData.value) {
      queueJobs.value = Array.isArray(resultData.value) ? resultData.value : []
    }
  } catch (error) {
    console.error('Error loading queue jobs:', error)
    queueJobs.value = []
  }
}

const loadCronJobs = async () => {
  try {
    const { data: resultData } = await useApi<any>('/jobs/cron')
    if (resultData.value && Array.isArray(resultData.value)) {
      cronJobs.value = resultData.value.map((job: any) => ({
        ...job,
        schedule_info: job.schedule_info || { method: 'unknown', parameters: '' },
      }))
    } else {
      cronJobs.value = []
    }
  } catch (error) {
    console.error('Error loading cron jobs:', error)
    cronJobs.value = []
  }
}

const formatDate = (date) => {
  if (!date) return 'Never'
  return new Date(date).toLocaleString()
}

const getStatusColor = (status) => {
  const colors = {
    success: 'success',
    failed: 'error',
    running: 'warning',
    pending: 'info',
  }
  return colors[status] || 'grey'
}

onMounted(() => {
  loadDashboard()
  loadQueueJobs()
  loadCronJobs()
})
</script>
