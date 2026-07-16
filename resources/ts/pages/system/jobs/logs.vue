<template>
  <div class="workspace-container w-100 d-flex flex-column pa-4 gap-3">
    <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
      <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
        <div class="d-flex align-center gap-2">
          <VIcon icon="tabler-history" size="24" color="primary" />
          <div>
            <div class="text-h6 font-weight-medium">Log dei Job</div>
            <div class="text-caption text-medium-emphasis">{{ logs.length }} esecuzioni di job</div>
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
            @click="loadLogs"
          >
            Aggiorna
          </VBtn>
        </div>
      </VCardText>
      <VDivider />
      <!-- 👉 Datatable  -->
      <VDataTable
        :headers="headers"
        :items="logs"
        :loading="loading"
        density="comfortable"
        hover
        class="elevation-0"
      >
        <template #no-data>
          <div class="py-10 text-center">
            <VIcon icon="tabler-history" size="40" class="text-disabled mb-2" />
            <p class="text-body-1 text-disabled mb-0">Nessun log di job trovato</p>
          </div>
        </template>
        <template #item.status="{ item }">
          <VChip :color="getStatusColor(item.status)" size="small">
            {{ item.status }}
          </VChip>
        </template>
        <template #item.created_at="{ item }">
          {{ formatDate(item.created_at) }}
        </template>
        <template #item.duration="{ item }">
          {{ item.duration }}s
        </template>
        <template #item.actions="{ item }">
          <div class="d-flex gap-1">
            <IconBtn
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
      <VCard title="Dettagli Log Job">
        <VCardText>
          <VList>
            <VListItem>
              <VListItemTitle>Nome Job</VListItemTitle>
              <VListItemSubtitle>{{ selectedLog?.job_name }}</VListItemSubtitle>
            </VListItem>
            <VListItem>
              <VListItemTitle>Tipo Job</VListItemTitle>
              <VListItemSubtitle>{{ selectedLog?.job_type }}</VListItemSubtitle>
            </VListItem>
            <VListItem>
              <VListItemTitle>Stato</VListItemTitle>
              <VListItemSubtitle>
                <VChip :color="getStatusColor(selectedLog?.status)" size="small">
                  {{ selectedLog?.status }}
                </VChip>
              </VListItemSubtitle>
            </VListItem>
            <VListItem>
              <VListItemTitle>Iniziato alle</VListItemTitle>
              <VListItemSubtitle>{{ formatDate(selectedLog?.started_at) }}</VListItemSubtitle>
            </VListItem>
            <VListItem>
              <VListItemTitle>Terminato alle</VListItemTitle>
              <VListItemSubtitle>{{ formatDate(selectedLog?.finished_at) }}</VListItemSubtitle>
            </VListItem>
            <VListItem>
              <VListItemTitle>Durata</VListItemTitle>
              <VListItemSubtitle>{{ selectedLog?.duration }} secondi</VListItemSubtitle>
            </VListItem>
            <VListItem v-if="selectedLog?.output">
              <VListItemTitle>Output</VListItemTitle>
              <VListItemSubtitle>
                <pre class="mt-2">{{ selectedLog.output }}</pre>
              </VListItemSubtitle>
            </VListItem>
            <VListItem v-if="selectedLog?.error_message">
              <VListItemTitle>Errore</VListItemTitle>
              <VListItemSubtitle>
                <pre class="mt-2 text-error">{{ selectedLog.error_message }}</pre>
              </VListItemSubtitle>
            </VListItem>
          </VList>
        </VCardText>
        <VCardActions>
          <VBtn color="primary" @click="showLogDialog = false">Chiudi</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'

const logs = ref([])
const loading = ref(false)
const showLogDialog = ref(false)
const selectedLog = ref(null)

const headers = [
  { title: 'Nome Job', key: 'job_name' },
  { title: 'Tipo', key: 'job_type' },
  { title: 'Stato', key: 'status' },
  { title: 'Iniziato', key: 'created_at' },
  { title: 'Durata', key: 'duration' },
  { title: 'Azioni', key: 'actions', sortable: false },
]

const loadLogs = async () => {
  loading.value = true
  try {
    const { data: resultData } = await useApi<any>('/api/jobs/logs')
    if (resultData.value) {
      logs.value = resultData.value
    }
  } catch (error) {
    console.error('Error loading logs:', error)
  } finally {
    loading.value = false
  }
}

const viewLog = async (log) => {
  try {
    const { data: resultData } = await useApi<any>(`/api/jobs/logs/${log.id}`)
    if (resultData.value) {
      selectedLog.value = resultData.value
      showLogDialog.value = true
    }
  } catch (error) {
    console.error('Error loading log:', error)
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
  loadLogs()
})
</script>
