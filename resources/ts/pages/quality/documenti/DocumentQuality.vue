<script setup lang="ts">
import { onMounted, ref } from 'vue'
import type { QualityValidationResponse } from 'types'

// Riceve il modello generico e l'ID dell'entità corrente (es. una pratica, un dipendente)
const props = defineProps<{
  modelName: string // Es: 'App\\Models\\User'
  modelId: string // Es: '96b12c82-...'
  userReparto: string // Il reparto dell'utente loggato (passato dal sistema di autenticazione)
}>();

const loading = ref<boolean>(true)
const statusData = ref<QualityValidationResponse | null>(null)
const isSubmitting = ref<boolean>(false)

// 1. CARICAMENTO DATI
const loadValidationStatus = async () => {
  try {
    loading.value = true

    const params = new URLSearchParams({
      model: props.modelName,
      model_id: props.modelId,
    })

    const response = await fetch(`/api/documents/check-status?${params.toString()}`)
    statusData.value = await response.json() as QualityValidationResponse
  }
  catch (error) {
    console.error("Errore nel caricamento degli stati:", error)
  }
  finally {
    loading.value = false
  }
}

// 2. AZIONE DI APPROVAZIONE (Riservata a reparto === 'Qualita')
const approveDocument = async (documentId: string, tipologia: 'Idoneita' | 'Giudizio') => {
  if (props.userReparto !== 'Qualita') {
    alert('Errore: Solo il personale del Reparto Qualità può approvare questo documento.')

    return
  }

  try {
    isSubmitting.value = true;
    const response = await fetch('/api/documents/quality-approve', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        // Se usi Sanctum/Passport inserisci qui il token, es: 'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify({
        wf_document_id: documentId,
        tipologia: tipologia,
      }),
    })

    if (response.ok) {
      // Rinfresca lo stato per aggiornare la riga e farla diventare verde
      await loadValidationStatus();
    } else {
      const errorData = await response.json();
      alert(errorData.error || "Errore durante l'approvazione.")
    }
  } catch (error) {
    console.error("Errore nell'invio dell'approvazione:", error)
  } finally {
    isSubmitting.value = false;
  }
}

onMounted(() => {
  loadValidationStatus()
})
</script>

<template>
  <div v-if="loading" class="loading-text">Verifica in corso nel Reparto Qualità...</div>

  <div v-else-if="statusData" class="table-row-container">
    <div class="document-row" :class="{ 'riga-verde': statusData.riga_completa }">

      <div class="cell entity-info">
        <span class="label">ID Record</span>
        <span class="value">{{ props.modelId.substring(0, 8) }}...</span>
      </div>

      <div class="cell document-zone">
        <span class="label">Idoneità Datore</span>

        <div v-if="statusData.idoneita.valido" class="status text-approved">
          ✅ Approvato da Qualità
          <small class="db-info">il {{ new Date(statusData.idoneita.data_validation!).toLocaleDateString() }}</small>
        </div>

        <div v-else-if="statusData.idoneita.presente" class="status text-pending">
          ⚠️ Da Verificare
          <button
            v-if="props.userReparto === 'Qualita'"
            @click="approveDocument(props.modelId, 'Idoneita')"
            :disabled="isSubmitting"
            class="btn-approve">
            Approva Ora
          </button>
        </div>

        <div v-else class="status text-missing">❌ File Mancante</div>
      </div>

      <div class="cell document-zone">
        <span class="label">Giudizio Tossicodipendenza</span>

        <div v-if="statusData.giudizio.valido" class="status text-approved">
          ✅ Approvato da Qualità
          <small class="db-info">il {{ new Date(statusData.giudizio.data_validation!).toLocaleDateString() }}</small>
        </div>

        <div v-else-if="statusData.giudizio.presente" class="status text-pending">
          ⚠️ Da Verificare
          <button
            v-if="props.userReparto === 'Qualita'"
            @click="approveDocument(props.modelId, 'Giudizio')"
            :disabled="isSubmitting"
            class="btn-approve">
            Approva Ora
          </button>
        </div>

        <div v-else class="status text-missing">❌ File Mancante</div>
      </div>

      <div class="cell status-badge-zone">
        <span v-if="statusData.riga_completa" class="global-badge bg-green">QUALITÀ OK</span>
        <span v-else class="global-badge bg-gray">ATTESA</span>
      </div>

    </div>
  </div>
</template>

<style scoped>
.document-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  margin-bottom: 10px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
  transition: all 0.25s ease;
}

/* LA CLASSE MAGICA: Quando la Qualità approva entrambi, scatta il verde */
.riga-verde {
  background-color: #f0fdf4 !important; /* Sfondo verde chiarissimo e professionale */
  border-color: #bbf7d0;
  color: #166534;
}

.cell {
  display: flex;
  flex-direction: column;
  flex: 1;
  padding: 0 8px;
}

.label {
  font-size: 11px;
  text-transform: uppercase;
  color: #64748b;
  font-weight: 600;
  margin-bottom: 4px;
}

.db-info {
  font-size: 10px;
  color: #64748b;
  display: block;
}

/* Stati */
.text-approved { color: #16a34a; font-weight: bold; }
.text-pending { color: #d97706; font-weight: bold; }
.text-missing { color: #dc2626; font-weight: 500; }

/* Pulsante di approvazione rapida */
.btn-approve {
  margin-top: 4px;
  padding: 4px 10px;
  background-color: #2563eb;
  color: white;
  border: none;
  border-radius: 4px;
  font-size: 11px;
  cursor: pointer;
  font-weight: 600;
  transition: background 0.2s;
}
.btn-approve:hover { background-color: #1d4ed8; }
.btn-approve:disabled { background-color: #94a3b8; cursor: not-allowed; }

/* Badge globali */
.global-badge {
  padding: 6px 12px;
  border-radius: 9999px;
  font-size: 11px;
  font-weight: 700;
  text-align: center;
  width: fit-content;
  align-self: flex-end;
}
.bg-green { background-color: #bbf7d0; color: #166534; }
.bg-gray { background-color: #e2e8f0; color: #475569; }
</style>
