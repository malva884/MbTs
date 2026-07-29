<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'

definePage({
  meta: { action: 'list', subject: 'Qualita-ValidazioneDocumenti' },
})

const { t } = useI18n()

const files = ref<any[]>([])
const loadingFiles = ref(false)
const selectedFile = ref<string | null>(null)
const testing = ref(false)
const testSteps = ref<any[]>([])
const generatedPdfs = ref<any[]>([])
const snackbar = ref({ visible: false, message: '', color: '' })

const showSnackbar = (msg: string, col = 'success') => {
  snackbar.value = { visible: true, message: msg, color: col }
}

const loadFiles = async () => {
  loadingFiles.value = true
  try {
    const result = await $api<any>('/qt/document/list-pdf-files', { method: 'GET' })
    files.value = result?.files || []
  } catch (error) {
    console.error('Errore caricamento file', error)
    showSnackbar('Errore nel caricamento dei file da Drive.', 'error')
  } finally {
    loadingFiles.value = false
  }
}

const runTest = async (e?: Event) => {
  e?.preventDefault()
  e?.stopPropagation()
  if (!selectedFile.value) {
    showSnackbar('Seleziona un file da testare.', 'warning')
    return
  }

  testing.value = true
  testSteps.value = []

  try {
    const result = await $api<any>('/qt/document/test-process-pdf', {
      method: 'POST',
      body: { path: selectedFile.value },
    })

    testSteps.value = result?.steps || []
    generatedPdfs.value = result?.pdfs || []
  } catch (error: any) {
    const detail = error?.body?.error || error?.message || 'Errore sconosciuto'
    showSnackbar(`Errore: ${detail}`, 'error')
    if (error?.body?.steps) {
      testSteps.value = error.body.steps
    }
    if (error?.body?.pdfs) {
      generatedPdfs.value = error.body.pdfs
    }
  } finally {
    testing.value = false
  }
}

const stepColor = (status: string) => {
  switch (status) {
    case 'ok': return 'success'
    case 'error': return 'error'
    case 'warning': return 'warning'
    case 'pending': return 'info'
    default: return 'secondary'
  }
}

const stepIcon = (status: string) => {
  switch (status) {
    case 'ok': return 'tabler-circle-check-filled'
    case 'error': return 'tabler-circle-x-filled'
    case 'warning': return 'tabler-alert-triangle-filled'
    case 'pending': return 'tabler-loader'
    default: return 'tabler-circle'
  }
}

const downloadPdf = (pdf: any) => {
  if (!pdf?.base64) return
  const byteChars = atob(pdf.base64)
  const byteNumbers = new Array(byteChars.length)
  for (let i = 0; i < byteChars.length; i++) {
    byteNumbers[i] = byteChars.charCodeAt(i)
  }
  const byteArray = new Uint8Array(byteNumbers)
  const blob = new Blob([byteArray], { type: 'application/pdf' })
  const url = window.URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = pdf.nome_file || 'output.pdf'
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  window.URL.revokeObjectURL(url)
}

onMounted(loadFiles)
</script>

<template>
  <VRow class="pa-4">
    <VSnackbar v-model="snackbar.visible" location="top center" :color="snackbar.color">
      {{ snackbar.message }}
    </VSnackbar>

    <VCol cols="12">
      <h1 class="text-h4 font-weight-bold tracking-tight text-high-emphasis mb-1">
        Test Elaborazione DDT
      </h1>
      <p class="text-sm text-muted mb-6">
        Seleziona un file PDF da Drive ed esegui un test dry-run per verificare come viene elaborato dal job ProcessQualityPdf.
      </p>

      <form @submit.prevent.stop>
      <VCard variant="flat" class="border mb-6 pa-4 rounded-xl">
        <VRow class="align-center" dense>
          <VCol cols="12" sm="8">
            <VAutocomplete
              v-model="selectedFile"
              :items="files"
              :loading="loadingFiles"
              item-title="name"
              item-value="path"
              placeholder="Seleziona un file PDF..."
              clearable
              density="compact"
              hide-details
              prepend-inner-icon="tabler-file-pdf"
              class="modern-input"
            />
          </VCol>
          <VCol cols="12" sm="4">
            <VBtn
              type="button"
              block
              color="primary"
              variant="tonal"
              prepend-icon="tabler-play"
              :loading="testing"
              :disabled="!selectedFile"
              @click.prevent.stop="runTest($event)"
            >
              Esegui Test
            </VBtn>
          </VCol>
        </VRow>
      </VCard>
      </form>

      <VCard v-if="generatedPdfs.length > 0" variant="flat" class="border rounded-xl mb-6">
        <VCardTitle class="text-h6 font-weight-bold pa-4 d-flex align-center">
          <VIcon icon="tabler-download" class="mr-2 text-primary" size="22" />
          PDF Generati (download)
        </VCardTitle>
        <VDivider />
        <VCardText class="pa-4">
          <VList density="compact" class="py-0">
            <VListItem
              v-for="(pdf, i) in generatedPdfs"
              :key="i"
              class="px-0"
            >
              <template #prepend>
                <VIcon icon="tabler-file-pdf" size="20" class="text-error mr-2" />
              </template>
              <VListItemTitle class="text-sm font-weight-bold">{{ pdf.nome_file }}</VListItemTitle>
              <VListItemSubtitle class="text-xs">
                Commessa: {{ pdf.commessa }} | DDT: {{ pdf.ddt }} | Pagine valide: {{ pdf.pagine_valide?.join(', ') }} | Pagine scartate accodate: {{ pdf.pagine_scartate_accodate?.join(', ') || 'nessuna' }}
              </VListItemSubtitle>
              <template #append>
                <VBtn
                  type="button"
                  size="small"
                  variant="tonal"
                  color="primary"
                  prepend-icon="tabler-download"
                  @click.prevent="downloadPdf(pdf)"
                >
                  Scarica
                </VBtn>
              </template>
            </VListItem>
          </VList>
        </VCardText>
      </VCard>

      <VCard v-if="testSteps.length > 0" variant="flat" class="border rounded-xl">
        <VCardTitle class="text-h6 font-weight-bold pa-4 d-flex align-center">
          <VIcon icon="tabler-list-check" class="mr-2 text-primary" size="22" />
          Risultati Analisi
        </VCardTitle>

        <VDivider />

        <VCardText class="pa-4">
          <VTimeline side="left" density="compact" class="mt-2">
            <VTimelineItem
              v-for="(step, i) in testSteps"
              :key="i"
              :dot-color="stepColor(step.status)"
              size="x-small"
            >
              <template #icon>
                <VIcon :icon="stepIcon(step.status)" size="14" />
              </template>

              <div class="mb-3">
                <div class="d-flex align-center gap-2 mb-1">
                  <span class="text-sm font-weight-bold">{{ step.step }}</span>
                  <VChip :color="stepColor(step.status)" variant="tonal" size="x-small">
                    {{ step.status }}
                  </VChip>
                </div>
                <p class="text-xs text-medium-emphasis mb-2">{{ step.detail }}</p>

                <VCard
                  v-if="step.data"
                  variant="outlined"
                  density="compact"
                  class="mt-2"
                >
                  <VCardText class="pa-3">
                    <pre class="text-xs" style="white-space: pre-wrap; word-break: break-word;">{{ JSON.stringify(step.data, null, 2) }}</pre>
                  </VCardText>
                </VCard>
              </div>
            </VTimelineItem>
          </VTimeline>
        </VCardText>
      </VCard>

      <VCard v-else-if="!testing" variant="flat" class="border rounded-xl pa-8 text-center">
        <VIcon icon="tabler-test-pipe" size="48" class="text-muted mb-3" />
        <p class="text-sm text-muted">
          Seleziona un file e clicca "Esegui Test" per vedere i risultati dell'analisi step-by-step.
        </p>
      </VCard>
    </VCol>
  </VRow>
</template>

<style scoped>
.modern-input :deep(.v-field--variant-outlined) {
  border-radius: 8px !important;
  --v-field-border-opacity: 0.08;
}
</style>
