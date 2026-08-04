<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'

definePage({
  meta: {
    action: 'create',
    subject: 'Produzione-Magazzino',
  },
})

const spreadsheetId = ref('')
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

const startImport = async (event: Event) => {
  if (event) {
    event.preventDefault()
    event.stopPropagation()
  }

  try {
    const response = await $api('/import/start', {
      method: 'POST',
      body: {
        spreadsheet_id: spreadsheetId.value
      }
    })

    if (!response.job_id) {
      alert('Errore: Nessun job_id restituito dal server')
      return
    }

    jobId.value = response.job_id
    isImporting.value = true

    // Salva in localStorage per recuperare dopo refresh
    localStorage.setItem('import_job_id', response.job_id)

    startPolling()
  } catch (error) {
    alert('Errore durante l\'avvio dell\'importazione: ' + error)
  }
}

const getProgress = async () => {
  if (!jobId.value) return

  try {
    const response = await $api('/import/progress', {
      method: 'GET',
      query: { job_id: jobId.value }
    })

    progress.value = response.progress

    if (progress.value.status === 'completed' || progress.value.status === 'failed') {
      stopPolling()
      isImporting.value = false
      // Pulisci localStorage quando completato
      localStorage.removeItem('import_job_id')
    }
  } catch (error) {
    stopPolling()
    isImporting.value = false
    localStorage.removeItem('import_job_id')
  }
}

const startPolling = () => {
  pollingInterval.value = window.setInterval(() => {
    getProgress()
  }, 2000)
}

const stopPolling = () => {
  if (pollingInterval.value) {
    clearInterval(pollingInterval.value)
    pollingInterval.value = null
  }
}

const cancelImport = () => {
  stopPolling()
  isImporting.value = false
  jobId.value = null
  localStorage.removeItem('import_job_id')
  progress.value = {
    status: 'idle',
    total_rows: 0,
    processed_rows: 0,
    imported_count: 0,
    skipped_count: 0,
    percentage: 0,
    message: ''
  }
}

const resetState = () => {
  cancelImport()
  spreadsheetId.value = ''
}

onMounted(() => {
  // Recupera job_id dal localStorage se esiste
  const savedJobId = localStorage.getItem('import_job_id')
  if (savedJobId) {
    jobId.value = savedJobId
    isImporting.value = true
    startPolling()
  }
})

onUnmounted(() => {
  stopPolling()
})
</script>

<template>
  <VCol cols="12">
    <VCard>
      <VCardTitle>
        Importazione Movimenti da Google Sheets
      </VCardTitle>
      <VCardText>
        <VAlert type="info" class="mb-4">
          <strong>Struttura del file Google Sheets:</strong>
          <ul class="mt-2">
            <li>Colonna 0: Materiale</li>
            <li>Colonna 1: Descrizione</li>
            <li>Colonna 2: Quantità (formato: 1.000,00)</li>
            <li>Colonna 3: Importo (formato: 1.000,00)</li>
            <li>Colonna 4: UM (Unità di Misura)</li>
            <li>Colonna 5: Lotto</li>
            <li>Colonna 6: Plant</li>
            <li>Colonna 7: Posizione Archiviazione</li>
            <li>Colonna 8: Tipo Movimento</li>
            <li>Colonna 9: Special Stock</li>
            <li>Colonna 10: Documento Materiale</li>
            <li>Colonna 11: Data Pubblicazione (formato: dd/mm/yyyy)</li>
            <li>Colonna 12: Data Documento (formato: dd/mm/yyyy)</li>
            <li>Colonna 13: Data Inserimento (formato: dd/mm/yyyy)</li>
            <li>Colonna 14: Testo Movimento</li>
            <li>Colonna 15: User</li>
            <li>Colonna 16: Suffisso per chiave univoca</li>
          </ul>
        </VAlert>

        <VTextField
          v-model="spreadsheetId"
          label="Google Spreadsheet ID"
          placeholder="Inserisci l'ID del file Google Sheets"
          :disabled="isImporting"
        />
        
        <VBtn
          color="primary"
          @click.stop="startImport"
          :disabled="isImporting"
          class="mt-4 mr-2"
          type="button"
        >
          Avvia Importazione
        </VBtn>
        
        <VBtn
          v-if="isImporting"
          color="error"
          @click="cancelImport"
          class="mt-4"
        >
          Cancella
        </VBtn>
        
        <VBtn
          v-if="progress.status === 'failed' || progress.status === 'completed'"
          color="secondary"
          @click="resetState"
          class="mt-4"
        >
          Reset
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
  </VCol>
</template>
