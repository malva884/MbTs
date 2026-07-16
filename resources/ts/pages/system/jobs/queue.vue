<template>
  <div class="workspace-container w-100 d-flex flex-column pa-4 gap-3">
    <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
      <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
        <div class="d-flex align-center gap-2">
          <VIcon icon="tabler-stack" size="24" color="primary" />
          <div>
            <div class="text-h6 font-weight-medium">Queue Jobs</div>
            <div class="text-caption text-medium-emphasis">{{ queueJobs.length }} queue jobs disponibili</div>
          </div>
        </div>
        <div class="d-flex align-center gap-2">
          <VBtn
            prepend-icon="tabler-refresh"
            color="secondary"
            variant="outlined"
            density="comfortable"
            class="px-3"
            :loading="loading"
            @click="loadQueueJobs"
          >
            Refresh
          </VBtn>
        </div>
      </VCardText>
      <VDivider />
      <!-- 👉 Datatable  -->
      <VDataTable
        :headers="headers"
        :items="queueJobs"
        :loading="loading"
        density="comfortable"
        hover
        class="elevation-0"
      >
        <template #no-data>
          <div class="py-10 text-center">
            <VIcon icon="tabler-stack" size="40" class="text-disabled mb-2" />
            <p class="text-body-1 text-disabled mb-0">Nessun queue job trovato</p>
          </div>
        </template>
          <template #item.last_run="{ item }">
            <VChip :color="getStatusColor(item.last_run?.status)" size="small">
              {{ item.last_run?.status || 'Never run' }}
            </VChip>
          </template>
          <template #item.last_run_time="{ item }">
            {{ item.last_run ? formatDate(item.last_run.created_at) : 'Never' }}
          </template>
          <template #item.actions="{ item }">
            <div class="d-flex gap-1">
              <IconBtn
                color="success"
                size="small"
                :loading="runningJobs[item.name]"
                @click="runJob(item)"
              >
                <VIcon icon="tabler-player-play" size="18" />
              </IconBtn>
              <IconBtn
                v-if="item.last_run"
                color="primary"
                size="small"
                @click="viewLog(item)"
              >
                <VIcon icon="tabler-file-text" size="18" />
              </IconBtn>
            </div>
          </template>
        </VDataTable>
      </VCard>

    <VDialog v-model="showLogDialog" max-width="800px">
      <VCard title="Job Log Details">
        <VCardText>
          <VList>
            <VListItem>
              <VListItemTitle>Job Name</VListItemTitle>
              <VListItemSubtitle>{{ selectedLog?.job_name }}</VListItemSubtitle>
            </VListItem>
            <VListItem>
              <VListItemTitle>Status</VListItemTitle>
              <VListItemSubtitle>
                <VChip :color="getStatusColor(selectedLog?.status)" size="small">
                  {{ selectedLog?.status }}
                </VChip>
              </VListItemSubtitle>
            </VListItem>
            <VListItem>
              <VListItemTitle>Started At</VListItemTitle>
              <VListItemSubtitle>{{ formatDate(selectedLog?.started_at) }}</VListItemSubtitle>
            </VListItem>
            <VListItem>
              <VListItemTitle>Finished At</VListItemTitle>
              <VListItemSubtitle>{{ formatDate(selectedLog?.finished_at) }}</VListItemSubtitle>
            </VListItem>
            <VListItem>
              <VListItemTitle>Duration</VListItemTitle>
              <VListItemSubtitle>{{ selectedLog?.duration }} seconds</VListItemSubtitle>
            </VListItem>
            <VListItem v-if="selectedLog?.output">
              <VListItemTitle>Output</VListItemTitle>
              <VListItemSubtitle>
                <pre class="mt-2">{{ selectedLog.output }}</pre>
              </VListItemSubtitle>
            </VListItem>
            <VListItem v-if="selectedLog?.error_message">
              <VListItemTitle>Error</VListItemTitle>
              <VListItemSubtitle>
                <pre class="mt-2 text-error">{{ selectedLog.error_message }}</pre>
              </VListItemSubtitle>
            </VListItem>
          </VList>
        </VCardText>
        <VCardActions>
          <VBtn color="primary" @click="showLogDialog = false">Close</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'

const queueJobs = ref([])
const loading = ref(false)
const runningJobs = ref({})
const showLogDialog = ref(false)
const selectedLog = ref(null)

const headers = [
  { title: 'Name', key: 'name' },
  { title: 'Class', key: 'full_class' },
  { title: 'Last Status', key: 'last_run' },
  { title: 'Last Run', key: 'last_run_time' },
  { title: 'Actions', key: 'actions', sortable: false },
]

const loadQueueJobs = async () => {
  loading.value = true
  try {
    const { data: resultData } = await useApi<any>('/api/jobs/queue')
    if (resultData.value) {
      queueJobs.value = resultData.value
    }
  } catch (error) {
    console.error('Error loading queue jobs:', error)
  } finally {
    loading.value = false
  }
}

const runJob = async (job) => {
  runningJobs.value[job.name] = true
  try {
    const response = await $api('/api/jobs/run-queue', {
      method: 'POST',
      body: {
        job_class: job.full_class,
      },
    })
    alert(`Job ${job.name} dispatched successfully`)
    await loadQueueJobs()
  } catch (error: any) {
    console.error('Error running job:', error)
    alert(`Error running job: ${error.message || 'Unknown error'}`)
  } finally {
    runningJobs.value[job.name] = false
  }
}

const viewLog = async (job) => {
  if (job.last_run) {
    try {
      const { data: resultData } = await useApi<any>(`/api/jobs/logs/${job.last_run.id}`)
      if (resultData.value) {
        selectedLog.value = resultData.value
        showLogDialog.value = true
      }
    } catch (error) {
      console.error('Error loading log:', error)
    }
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
  loadQueueJobs()
})
</script>
