<template>
  <div class="workspace-container w-100 d-flex flex-column pa-4 gap-3">
    <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
      <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
        <div class="d-flex align-center gap-2">
          <VIcon icon="tabler-alert-circle" size="24" color="error" />
          <div>
            <div class="text-h6 font-weight-medium">Failed Jobs</div>
            <div class="text-caption text-medium-emphasis">{{ failedJobs.length }} failed jobs</div>
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
            @click="loadFailedJobs"
          >
            Refresh
          </VBtn>
          <VBtn
            prepend-icon="tabler-trash"
            color="error"
            variant="outlined"
            density="comfortable"
            class="px-3"
            :loading="deletingAll"
            :disabled="failedJobs.length === 0"
            @click="deleteAllJobs"
          >
            Delete All
          </VBtn>
        </div>
      </VCardText>
      <VDivider />
      <!-- 👉 Datatable  -->
      <VDataTable
        :headers="headers"
        :items="failedJobs"
        :loading="loading"
        density="comfortable"
        hover
        class="elevation-0"
      >
        <template #no-data>
          <div class="py-10 text-center">
            <VIcon icon="tabler-alert-circle" size="40" class="text-disabled mb-2" />
            <p class="text-body-1 text-disabled mb-0">No failed jobs found</p>
          </div>
        </template>
        <template #item.failed_at="{ item }">
          {{ formatDate(item.failed_at) }}
        </template>
        <template #item.actions="{ item }">
          <div class="d-flex gap-1">
            <IconBtn
              color="success"
              size="small"
              :loading="retryingJobs[item.id]"
              @click="retryJob(item)"
            >
              <VIcon icon="tabler-refresh" size="18" />
            </IconBtn>
            <IconBtn
              color="primary"
              size="small"
              @click="viewLog(item)"
            >
              <VIcon icon="tabler-file-text" size="18" />
            </IconBtn>
            <IconBtn
              color="error"
              size="small"
              :loading="deletingJobs[item.id]"
              @click="deleteJob(item)"
            >
              <VIcon icon="tabler-trash" size="18" />
            </IconBtn>
          </div>
        </template>
      </VDataTable>
    </VCard>

    <VDialog v-model="showLogDialog" max-width="800px">
      <VCard title="Failed Job Details">
        <VCardText>
          <VList>
            <VListItem>
              <VListItemTitle>Job Name</VListItemTitle>
              <VListItemSubtitle>{{ selectedLog?.job_name }}</VListItemSubtitle>
            </VListItem>
            <VListItem>
              <VListItemTitle>UUID</VListItemTitle>
              <VListItemSubtitle>{{ selectedLog?.uuid }}</VListItemSubtitle>
            </VListItem>
            <VListItem>
              <VListItemTitle>Connection</VListItemTitle>
              <VListItemSubtitle>{{ selectedLog?.connection }}</VListItemSubtitle>
            </VListItem>
            <VListItem>
              <VListItemTitle>Queue</VListItemTitle>
              <VListItemSubtitle>{{ selectedLog?.queue }}</VListItemSubtitle>
            </VListItem>
            <VListItem>
              <VListItemTitle>Failed At</VListItemTitle>
              <VListItemSubtitle>{{ formatDate(selectedLog?.failed_at) }}</VListItemSubtitle>
            </VListItem>
            <VListItem v-if="selectedLog?.exception">
              <VListItemTitle>Exception</VListItemTitle>
              <VListItemSubtitle>
                <pre class="mt-2 text-error">{{ selectedLog.exception }}</pre>
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

  <VSnackbar
    v-model="snackbar.show"
    :color="snackbar.color"
    timeout="3000"
    location="top right"
  >
    {{ snackbar.message }}
  </VSnackbar>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { VDataTable } from 'vuetify/labs/VDataTable'

const failedJobs = ref([])
const loading = ref(false)
const retryingJobs = ref({})
const deletingJobs = ref({})
const deletingAll = ref(false)
const showLogDialog = ref(false)
const selectedLog = ref(null)
const snackbar = ref({ show: false, message: '', color: 'success' })

const showNotification = (message: string, color = 'success') => {
  snackbar.value = { show: true, message, color }
}

const headers = [
  { title: 'Job Name', key: 'job_name' },
  { title: 'Connection', key: 'connection' },
  { title: 'Queue', key: 'queue' },
  { title: 'Failed At', key: 'failed_at' },
  { title: 'Actions', key: 'actions', sortable: false },
]

const loadFailedJobs = async () => {
  loading.value = true
  try {
    const { data: resultData } = await useApi<any>('/jobs/failed')
    if (resultData.value) {
      failedJobs.value = Array.isArray(resultData.value) ? resultData.value : []
    }
  } catch (error) {
    console.error('Error loading failed jobs:', error)
    failedJobs.value = []
  } finally {
    loading.value = false
  }
}

const viewLog = async (log) => {
  selectedLog.value = log
  showLogDialog.value = true
}

const retryJob = async (job) => {
  retryingJobs.value[job.id] = true
  try {
    const response = await $api('/jobs/retry-failed', {
      method: 'POST',
      body: {
        id: job.uuid,
      },
    })
    showNotification(`Job ${job.job_name} retried successfully`)
    await loadFailedJobs()
  } catch (error: any) {
    console.error('Error retrying job:', error)
    showNotification(`Error retrying job: ${error.message || 'Unknown error'}`, 'error')
  } finally {
    retryingJobs.value[job.id] = false
  }
}

const deleteJob = async (job) => {
  deletingJobs.value[job.id] = true
  try {
    const response = await $api('/jobs/delete-failed', {
      method: 'POST',
      body: {
        id: job.uuid,
      },
    })
    showNotification(`Job ${job.job_name} deleted successfully`)
    await loadFailedJobs()
  } catch (error: any) {
    console.error('Error deleting job:', error)
    showNotification(`Error deleting job: ${error.message || 'Unknown error'}`, 'error')
  } finally {
    deletingJobs.value[job.id] = false
  }
}

const deleteAllJobs = async () => {
  if (!confirm('Are you sure you want to delete all failed jobs? This action cannot be undone.')) {
    return
  }
  deletingAll.value = true
  try {
    const response = await $api('/jobs/delete-all-failed', {
      method: 'POST',
    })
    showNotification('All failed jobs deleted successfully')
    await loadFailedJobs()
  } catch (error: any) {
    console.error('Error deleting all jobs:', error)
    showNotification(`Error deleting all jobs: ${error.message || 'Unknown error'}`, 'error')
  } finally {
    deletingAll.value = false
  }
}

const formatDate = (date) => {
  if (!date) return 'Never'
  return new Date(date).toLocaleString()
}

onMounted(() => {
  loadFailedJobs()
})
</script>
