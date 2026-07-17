<template>
  <div class="workspace-container w-100 d-flex flex-column pa-4 gap-3">
    <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
      <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
        <div class="d-flex align-center gap-2">
          <VIcon icon="tabler-clock" size="24" color="primary" />
          <div>
            <div class="text-h6 font-weight-medium">Cron Jobs</div>
            <div class="text-caption text-medium-emphasis">{{ cronJobs.length }} cron jobs configurati</div>
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
            @click="loadCronJobs"
          >
            Refresh
          </VBtn>
        </div>
      </VCardText>
      <VDivider />
      <!-- 👉 Datatable  -->
      <VDataTable
        :headers="headers"
        :items="cronJobs"
        :loading="loading"
        density="comfortable"
        hover
        class="elevation-0"
      >
        <template #no-data>
          <div class="py-10 text-center">
            <VIcon icon="tabler-clock" size="40" class="text-disabled mb-2" />
            <p class="text-body-1 text-disabled mb-0">Nessun cron job trovato</p>
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
          <template #item.schedule="{ item }">
            <div v-if="item.schedule_info">
              <strong>{{ item.schedule_info.method }}</strong>
              <span v-if="item.schedule_info.parameters">({{ item.schedule_info.parameters }})</span>
            </div>
            <small v-if="item.schedule_info && item.schedule_info.timezone">{{ item.schedule_info.timezone }}</small>
          </template>
        <template #item.actions="{ item }">
          <div class="d-flex gap-1">
            <IconBtn
              color="success"
              size="small"
              :loading="runningCrons[item.command]"
              @click="runCron(item)"
            >
              <VIcon icon="tabler-player-play" size="18" />
            </IconBtn>
            <IconBtn
              color="primary"
              size="small"
              @click="editSchedule(item)"
            >
              <VIcon icon="tabler-edit" size="18" />
            </IconBtn>
          </div>
        </template>
      </VDataTable>
    </VCard>

    <VDialog v-model="showScheduleDialog" max-width="600px">
      <VCard title="Edit Cron Schedule">
        <VCardText>
          <VTextField
            v-model="editingCron.command"
            label="Command"
            disabled
            class="mb-4"
          />
          <VTextField
            v-model="newSchedule"
            label="New Schedule"
            placeholder="e.g., ->dailyAt('08:00')"
            hint="Enter the full schedule method call"
            persistent-hint
          />
          <VAlert type="info" class="mt-2">
            Examples:
            <ul>
              <li>->daily()</li>
              <li>->dailyAt('08:00')</li>
              <li>->hourly()</li>
              <li>->everyFiveMinutes()</li>
              <li>->weeklyOn(1, '18:00')</li>
              <li>->monthlyOn(1, '08:00')</li>
            </ul>
          </VAlert>
        </VCardText>
        <VCardActions>
          <VBtn color="grey" @click="showScheduleDialog = false">Cancel</VBtn>
          <VBtn color="primary" @click="saveSchedule" :loading="savingSchedule">Save</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <VDialog v-model="showLogDialog" max-width="800px">
      <VCard title="Cron Log Details">
        <VCardText>
          <VList>
            <VListItem>
              <VListItemTitle>Command</VListItemTitle>
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
import { ref, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { VDataTable } from 'vuetify/labs/VDataTable'

definePage({
  meta: {
    action: 'view',
    subject: 'System',
  },
})

const cronJobs = ref([])
const loading = ref(false)
const runningCrons = ref({})
const showScheduleDialog = ref(false)
const showLogDialog = ref(false)
const editingCron = ref(null)
const newSchedule = ref('')
const savingSchedule = ref(false)
const selectedLog = ref(null)

const headers = [
  { title: 'Command', key: 'command' },
  { title: 'Schedule', key: 'schedule' },
  { title: 'Last Status', key: 'last_run' },
  { title: 'Last Run', key: 'last_run_time' },
  { title: 'Actions', key: 'actions', sortable: false },
]

const loadCronJobs = async () => {
  loading.value = true
  try {
    const { data: resultData } = await useApi<any>('/jobs/cron')
    if (resultData.value) {
      cronJobs.value = Array.isArray(resultData.value) ? resultData.value : []
    }
  } catch (error) {
    console.error('Error loading cron jobs:', error)
    cronJobs.value = []
  } finally {
    loading.value = false
  }
}

const runCron = async (job) => {
  runningCrons.value[job.command] = true
  try {
    const response = await $api('/jobs/run-cron', {
      method: 'POST',
      body: {
        command: job.command,
      },
    })
    alert(`Command ${job.command} executed successfully`)
    await loadCronJobs()
  } catch (error: any) {
    console.error('Error running cron:', error)
    alert(`Error running cron: ${error.message || 'Unknown error'}`)
  } finally {
    runningCrons.value[job.command] = false
  }
}

const editSchedule = (job) => {
  editingCron.value = job
  newSchedule.value = `->${job.schedule_info.method}(${job.schedule_info.parameters || ''})`
  showScheduleDialog.value = true
}

const saveSchedule = async () => {
  savingSchedule.value = true
  try {
    const response = await $api('/jobs/update-schedule', {
      method: 'POST',
      body: {
        command: editingCron.value.command,
        new_schedule: newSchedule.value,
      },
    })
    alert('Schedule updated successfully')
    showScheduleDialog.value = false
    await loadCronJobs()
  } catch (error: any) {
    console.error('Error updating schedule:', error)
    alert(`Error updating schedule: ${error.message || 'Unknown error'}`)
  } finally {
    savingSchedule.value = false
  }
}

const viewLog = async (job) => {
  if (job.last_run) {
    try {
      const { data: resultData } = await useApi<any>(`/jobs/logs/${job.last_run.id}`)
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
  loadCronJobs()
})
</script>
