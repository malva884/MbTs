<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import axios from 'axios'

const spreadsheetId = ref('1PvvVxjciXFacEpaHkrgvQ6DFfi8jo5zjD4P-j-hJJhQ')
const jobId = ref<string | null>(null)
const progress = ref({
  status: 'idle',
  total_rows: 0,
  processed_rows: 0,
  imported_count: 0,
  skipped_count: 0,
  percentage: 0,
  message: ''
})
const isImporting = ref(false)
const pollingInterval = ref<number | null>(null)

const startImport = async () => {
  try {
    const response = await axios.post('/api/import/start', {
      spreadsheet_id: spreadsheetId.value
    })
    
    jobId.value = response.data.job_id
    isImporting.value = true
    startPolling()
  } catch (error) {
    console.error('Error starting import:', error)
  }
}

const getProgress = async () => {
  if (!jobId.value) return
  
  try {
    const response = await axios.get('/api/import/progress', {
      params: { job_id: jobId.value }
    })
    
    progress.value = response.data.progress
    
    if (progress.value.status === 'completed' || progress.value.status === 'failed') {
      stopPolling()
      isImporting.value = false
    }
  } catch (error) {
    console.error('Error getting progress:', error)
    stopPolling()
    isImporting.value = false
  }
}

const startPolling = () => {
  pollingInterval.value = window.setInterval(() => {
    getProgress()
  }, 1000) // Poll every second
}

const stopPolling = () => {
  if (pollingInterval.value) {
    clearInterval(pollingInterval.value)
    pollingInterval.value = null
  }
}

onUnmounted(() => {
  stopPolling()
})
</script>

<template>
  <VCard>
    <VCardTitle>
      Importazione Movimenti da Google Sheets
    </VCardTitle>
    <VCardText>
      <VTextField
        v-model="spreadsheetId"
        label="Google Spreadsheet ID"
        placeholder="Inserisci l'ID del file Google Sheets"
        :disabled="isImporting"
      />
      
      <VBtn
        color="primary"
        @click="startImport"
        :disabled="isImporting"
        class="mt-4"
      >
        Avvia Importazione
      </VBtn>
      
      <VProgressLinear
        v-if="isImporting"
        :model-value="progress.percentage"
        color="primary"
        class="mt-4"
      />
      
      <VAlert
        v-if="progress.message"
        :type="progress.status === 'failed' ? 'error' : progress.status === 'completed' ? 'success' : 'info'"
        class="mt-4"
      >
        {{ progress.message }}
      </VAlert>
      
      <VRow v-if="isImporting || progress.status === 'completed'" class="mt-4">
        <VCol cols="6">
          <VCard>
            <VCardText>
              <div class="text-h6">Righe Totali</div>
              <div class="text-h4">{{ progress.total_rows }}</div>
            </VCardText>
          </VCard>
        </VCol>
        <VCol cols="6">
          <VCard>
            <VCardText>
              <div class="text-h6">Righe Processate</div>
              <div class="text-h4">{{ progress.processed_rows }}</div>
            </VCardText>
          </VCard>
        </VCol>
        <VCol cols="6">
          <VCard>
            <VCardText>
              <div class="text-h6">Importate</div>
              <div class="text-h4 text-success">{{ progress.imported_count }}</div>
            </VCardText>
          </VCard>
        </VCol>
        <VCol cols="6">
          <VCard>
            <VCardText>
              <div class="text-h6">Saltate (Duplicati)</div>
              <div class="text-h4 text-warning">{{ progress.skipped_count }}</div>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>
    </VCardText>
  </VCard>
</template>
