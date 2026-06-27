<script setup lang="ts">
import { useRouter } from 'vue-router'
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import moment from 'moment'
import { useI18n } from 'vue-i18n'
import { ref, computed, onMounted, onUnmounted } from 'vue'
import {can} from "@layouts/plugins/casl";
import DefineAbilities from "@/plugins/casl/DefineAbilities";

definePage({
  meta: { action: 'list', subject: 'Qualita-ValidazioneDocumenti' },
})

const { t } = useI18n()
const router = useRouter()

// State Management
const itemsPerPage = ref(10)
const loading = ref(false)
const totalItems = ref(0)
const page = ref(1)
const sortBy = ref('created_at')
const orderBy = ref('asc')
const serverItems = ref<any[]>([])

// Filters
const filters = ref({ riferimento: '', stato: null, dal: null as string | null, al: null as string | null })
const dateRange = ref<string | null>(null)

const onDateRangeChange = () => {
  if (dateRange.value && dateRange.value.includes(' to ')) {
    const parts = dateRange.value.split(' to ')
    filters.value.dal = parts[0] || null
    filters.value.al = parts[1] || null
  } else {
    filters.value.dal = dateRange.value || null
    filters.value.al = null
  }
  loadItems()
}

// UI States
const isLoading = ref(false)
const dialogs = ref({ approve: false })
const snackbar = ref({ visible: false, message: '', color: '' })

// Riferimento all'iframe nascosto per iniettare la stampa di Windows
const printIframeRef = ref<HTMLIFrameElement | null>(null)

// Gestione del file selezionato per l'anteprima laterale (Split View)
const selectedDriveId = ref<string | null>(null)

const statoOptions = [
  { text: 'Da Fare', value: 'DA-FARE' },
  { text: 'DDC OK', value: 'DDC-OK' },
  { text: 'ORDINE OK', value: 'ORDINE-OK' },
]

const editedItem = ref<any>({ id: undefined, nome_file: '', riferimento: '', stato: 'DA-FARE' })

const resolveSemaforo = (stato: string | null | undefined) => {
  const statoEffettivo = (stato || 'DA-FARE').toUpperCase().trim()

  const map: Record<string, { color: string; label: string; icon: string }> = {
    'DA-FARE': { color: 'error', label: 'Da Fare', icon: 'tabler-point-filled' },
    'DDC-OK':  { color: 'warning', label: 'DDC OK', icon: 'tabler-point-filled' },
    'ORDINE-OK':  { color: 'success', label: 'ORDINE OK', icon: 'tabler-circle-check-filled' },
    'APPROVATO': { color: 'success', label: 'ORDINE OK', icon: 'tabler-circle-check-filled' },
    'PENDENTE':  { color: 'warning', label: 'DDC OK', icon: 'tabler-point-filled' },
  }

  return map[statoEffettivo] || { color: 'secondary', label: 'Da Fare', icon: 'tabler-point' }
}

const loadItems = async () => {
  loading.value = true
  try {
    const { data: resultData } = await useApi<any>(createUrl('/qt/document/pending-list', {
      query: {
        page: page.value,
        itemsPerPage: itemsPerPage.value,
        sortBy: sortBy.value,
        orderBy: orderBy.value,
        riferimento: filters.value.riferimento,
        stato: filters.value.stato,
        dal: filters.value.dal,
        al: filters.value.al,
      },
    }))

    if (resultData.value) {
      const raw = resultData.value
      serverItems.value = Array.isArray(raw) ? raw : (raw.data || [])
      totalItems.value = raw.total || serverItems.value.length
    } else {
      serverItems.value = []
      totalItems.value = 0
    }
  } catch (error) {
    console.error("Errore nel caricamento dei dati", error)
  } finally {
    loading.value = false
  }
}

const updateOptions = (options: any) => {
  sortBy.value = options.sortBy[0]?.key || 'created_at'
  orderBy.value = options.sortBy[0]?.order || 'asc'
  page.value = options.page
  itemsPerPage.value = options.itemsPerPage
  loadItems()
}

const showSnackbar = (msg: string, col = 'success') => {
  snackbar.value = { visible: true, message: msg, color: col }
}

const openActionDialog = (item: any) => {
  const rawItem = item.raw ? item.raw : item
  editedItem.value = { ...rawItem }
  dialogs.value.approve = true
}

const confirmApprove = async () => {
  isLoading.value = true
  try {
    const returnData = await $api('/qt/document/quality-approve', {
      method: 'POST',
      body: {
        wf_document_id: editedItem.value.id,
        attuale_stato: editedItem.value.stato,
      },
    })

    if (returnData) {
      await loadItems()
      if (editedItem.value.id_file_drive === selectedDriveId.value && editedItem.value.stato === 'DDC-OK') {
        selectedDriveId.value = null
      }
      dialogs.value.approve = false
      showSnackbar('Stato avanzato con successo.')
    } else {
      showSnackbar('Errore durante l\'aggiornamento', 'error')
    }
  } catch (e) {
    console.log(e)
    showSnackbar('Errore di rete o del server', 'error')
  } finally {
    isLoading.value = false
  }
}

function previewFile(driveId: string) {
  if (!driveId) return
  selectedDriveId.value = (selectedDriveId.value === driveId) ? null : driveId
}

const userOpenFile = async (id: string | number, nomeFile?: string) => {

  try {
    await $api(`/workflow/commesse/userOpenFile/${id}`, {
      method: 'POST',
    })
    showSnackbar(nomeFile ? `Apertura: ${nomeFile}` : 'Apertura e sblocco file eseguiti.')
    await loadItems()
  } catch (error) {
    showSnackbar('Impossibile elaborare l\'apertura del file', 'error')
  } finally {

  }
}

function printDriveFileHidden(driveId: string) {
  if (!driveId || !printIframeRef.value) return

  showSnackbar('Preparazione del documento per la stampa...', 'info')
  const printUrl = `https://drive.google.com/file/d/${driveId}/preview`
  printIframeRef.value.src = printUrl

  printIframeRef.value.onload = () => {
    try {
      const iframeWindow = printIframeRef.value?.contentWindow
      if (iframeWindow) {
        iframeWindow.focus()
        iframeWindow.print()
      }
    } catch (e) {
      const fallbackUrl = `https://drive.google.com/file/d/${driveId}/view?usp=sharing`
      const newWindow = window.open(fallbackUrl, '_blank')
      if (newWindow) {
        newWindow.onload = () => { newWindow.print() }
      }
    }
  }
}

// Auto-refresh
const autoRefresh = ref(false)
let autoRefreshTimer: ReturnType<typeof setInterval> | null = null

const toggleAutoRefresh = () => {
  autoRefresh.value = !autoRefresh.value
  if (autoRefresh.value) {
    autoRefreshTimer = setInterval(loadItems, 60000)
  } else {
    if (autoRefreshTimer) clearInterval(autoRefreshTimer)
    autoRefreshTimer = null
  }
}

const googleDrivePreviewUrl = computed(() => {
  if (!selectedDriveId.value) return ''
  return `https://drive.google.com/file/d/${selectedDriveId.value}/preview`
})

const headers = computed(() => [
  { title: 'Stato', key: 'stato', width: '120px', sortable: true },
  { title: 'DDT/Distinta', key: 'nome_file', sortable: true },
  { title: 'Commessa', key: 'riferimento', sortable: true },
  { title: 'Data Caricamento', key: 'created_at', width: '160px', sortable: true },
  { title: '', key: 'actions', sortable: false, width: '180px', align: 'end' },
])

onMounted(loadItems)
onUnmounted(() => { if (autoRefreshTimer) clearInterval(autoRefreshTimer) })
</script>

<template>
  <VRow class="pa-4 app-quality-container" no-gutters>
    <VSnackbar v-model="snackbar.visible" transition="scroll-y-reverse-transition" location="top center" :color="snackbar.color">
      {{ snackbar.message }}
    </VSnackbar>

    <iframe ref="printIframeRef" style="display: none; width: 0; height: 0; border: none;"></iframe>

    <VCol :cols="selectedDriveId ? 7 : 12" class="transition-all pr-4">

      <div class="d-flex justify-between align-center mb-6">
        <div>
          <h1 class="text-h4 font-weight-bold tracking-tight text-high-emphasis mb-1">Controllo Qualità</h1>
          <p class="text-sm text-muted mb-0">Monitora e approva i documenti in coda di lavorazione.</p>
        </div>
        <div class="d-flex align-center gap-3">
          <VChip variant="flat" color="primary" size="small" class="font-weight-bold">
            {{ totalItems }} Pratiche
          </VChip>
          <VBtn
            :icon="autoRefresh ? 'tabler-player-stop' : 'tabler-refresh'"
            :color="autoRefresh ? 'success' : 'secondary'"
            :variant="autoRefresh ? 'tonal' : 'text'"
            density="comfortable"
            size="small"
            :title="autoRefresh ? 'Aggiornamento automatico attivo (1 min) — clicca per disattivare' : 'Attiva aggiornamento automatico'"
            @click="toggleAutoRefresh"
          />
        </div>
      </div>

      <VCard variant="flat" class="filter-card border mb-6 pa-4">
        <VRow class="align-center" dense>
          <VCol cols="12" sm="4">
            <AppTextField
              v-model="filters.riferimento"
              placeholder="Filtra per Documento..."
              clearable
              density="compact"
              hide-details
              prepend-inner-icon="tabler-search"
              class="modern-input"
              @focusout="loadItems"
            />
          </VCol>
          <VCol cols="12" sm="3">
            <AppSelect
              v-model="filters.stato"
              placeholder="Tutti gli stati"
              :items="statoOptions"
              item-title="text"
              item-value="value"
              clearable
              density="compact"
              hide-details
              class="modern-input"
              @update:model-value="loadItems"
            />
          </VCol>
          <VCol cols="12" sm="4">
            <AppDateTimePicker
              v-model="dateRange"
              placeholder="Seleziona periodo..."
              :config="{ mode: 'range' }"
              clearable
              density="compact"
              hide-details
              class="modern-input"
              @update:model-value="onDateRangeChange"
            />
          </VCol>
          <VCol cols="auto">
            <VBtn icon="tabler-refresh" variant="text" color="secondary" density="comfortable" @click="loadItems" />
          </VCol>
        </VRow>
      </VCard>

      <VCard variant="flat" class="border table-card">
        <VDataTableServer
          v-model:items-per-page="itemsPerPage"
          :headers="headers"
          :items="serverItems"
          :items-length="totalItems"
          :loading="loading"
          density="comfortable"
          class="premium-table"
          @update:options="updateOptions"
        >
          <template #item.stato="{ item }">
            <VChip :color="resolveSemaforo(item.raw?.stato || item.stato).color" variant="tonal" size="small" class="font-weight-bold style-badge">
              <VIcon :icon="resolveSemaforo(item.raw?.stato || item.stato).icon" size="10" class="mr-1" />
              {{ resolveSemaforo(item.raw?.stato || item.stato).label }}
            </VChip>
          </template>

          <template #item.riferimento="{ item }">
            <VIcon v-if="can(DefineAbilities.document_quality_create.action, DefineAbilities.document_quality_create.subject)" icon="tabler-writing" size="18" class="mr-2 text-muted file-icon" @click="userOpenFile(item.id_file_drive_commessa)" />
            <div
              class="file-link-wrapper d-inline-flex align-center cursor-pointer"
              :class="{ 'text-active-preview': selectedDriveId === (item.raw?.id_file_drive || item.id_file_drive) }"
              @click="previewFile(item.raw?.id_file_drive_commessa || item.id_file_drive_commessa)"
            >
              <span class="file-text text-sm">{{ item.raw?.riferimento || item.riferimento }}</span>
              <VIcon
                :icon="selectedDriveId === (item.raw?.id_file_drive_commessa || item.id_file_drive_commessa) ? 'tabler-eye-off' : 'tabler-layout-sidebar-right-expand'"
                size="14"
                class="ml-2 open-indicator"
              />
            </div>
          </template>

          <template #item.nome_file="{ item }">
            <VIcon v-if="can(DefineAbilities.document_quality_create.action, DefineAbilities.document_quality_create.subject)" icon="tabler-writing" size="18" class="mr-2 text-muted file-icon" @click="userOpenFile(item.nome_file)" />

            <div
              class="file-link-wrapper d-inline-flex align-center cursor-pointer"
              :class="{ 'text-active-preview': selectedDriveId === (item.raw?.id_file_drive || item.id_file_drive) }"
              @click="previewFile(item.raw?.id_file_drive || item.id_file_drive)"
            >
              <span class="file-text text-sm">{{ item.raw?.nome_file || item.nome_file }}</span>
              <VIcon
                :icon="selectedDriveId === (item.raw?.id_file_drive || item.id_file_drive) ? 'tabler-eye-off' : 'tabler-layout-sidebar-right-expand'"
                size="14"
                class="ml-2 open-indicator"
              />
            </div>
          </template>

          <template #item.created_at="{ item }">
            <span class="text-sm text-medium-emphasis">
              {{ (item.raw?.created_at || item.created_at) ? moment(item.raw?.created_at || item.created_at).format('DD MMM, HH:mm') : '—' }}
            </span>
          </template>

          <template #item.actions="{ item }">
            <div class="d-flex justify-end align-center gap-1">

              <VBtn
                icon="tabler-printer"
                :variant="(item.raw?.id_file_drive_ddc || item.id_file_drive_ddc) ? 'tonal' : 'text'"
                :color="(item.raw?.id_file_drive_ddc || item.id_file_drive_ddc) ? 'primary' : 'secondary'"
                :disabled="!(item.raw?.id_file_drive_ddc || item.id_file_drive_ddc)"
                density="comfortable"
                size="small"
                title="Stampa DDC"
                @click.stop="printDriveFileHidden(item.raw?.id_file_drive_ddc || item.id_file_drive_ddc)"
              />

              <VBtn
                :icon="selectedDriveId === (item.raw?.id_file_drive || item.id_file_drive) ? 'tabler-eye-off' : 'tabler-eye'"
                :color="selectedDriveId === (item.raw?.id_file_drive || item.id_file_drive) ? 'primary' : 'secondary'"
                variant="text"
                density="comfortable"
                size="small"
                title="Anteprima a schermo"
                @click.stop="previewFile(item.raw?.id_file_drive || item.id_file_drive)"
              />

              <VBtn
                v-if="can(DefineAbilities.document_quality_create.action, DefineAbilities.document_quality_create.subject)"
                :variant="(item.raw?.stato || item.stato) === 'ORDINE-OK' ? 'tonal' : 'elevated'"
                :color="(item.raw?.stato || item.stato) === 'ORDINE-OK' ? 'secondary' : 'success'"
                :icon="(item.raw?.stato || item.stato) === 'ORDINE-OK' ? 'tabler-check' : 'tabler-chevron-right'"
                :disabled="(item.raw?.stato || item.stato) === 'ORDINE-OK'"
                density="comfortable"
                size="small"
                class="ml-1 approval-btn shadow-sm"
                :class="{ 'approved-done': (item.raw?.stato || item.stato) === 'ORDINE-OK' }"
                title="Avanza Fase"
                @click.stop="openActionDialog(item)"
              />
            </div>
          </template>
        </VDataTableServer>
      </VCard>
    </VCol>

    <VCol v-if="selectedDriveId" cols="5" class="transition-all preview-sidebar-column">
      <VCard variant="flat" class="border preview-container-card d-flex flex-column">

        <div class="d-flex align-center justify-between pa-3 border-b bg-light-translucent">
          <div class="d-flex align-center text-sm font-weight-bold text-high-emphasis">
            <VIcon icon="tabler-file-analytics" class="mr-2 text-primary" size="20" />
            Ispezione Documento
          </div>
          <VBtn icon="tabler-x" variant="text" density="comfortable" size="small" color="secondary" @click="selectedDriveId = null" />
        </div>

        <div class="flex-grow-1 bg-dark-placeholder iframe-wrapper">
          <VBtn
            icon="tabler-printer"
            variant="flat"
            color="primary"
            size="default"
            class="floating-print-btn"
            title="Stampa documento corrente"
            @click="printDriveFileHidden(selectedDriveId)"
          />

          <iframe
            :src="googleDrivePreviewUrl"
            width="100%"
            height="100%"
            frameborder="0"
            allow="autoplay"
            class="absolute-iframe"
          ></iframe>
        </div>
      </VCard>
    </VCol>
  </VRow>

  <VDialog v-model="dialogs.approve" max-width="400px" overlay-opacity="0.2">
    <VCard variant="flat" class="border pa-4 rounded-xl">
      <VCardTitle class="text-h6 font-weight-bold px-0 pt-0">Avanzamento Fase</VCardTitle>
      <VCardText class="px-0 py-3 text-sm text-medium-emphasis">
        Vuoi promuovere la pratica alla fase successiva del controllo qualità?
      </VCardText>
      <VCardActions class="px-0 pb-0 pt-2">
        <VSpacer />
        <VBtn color="secondary" variant="text" size="small" @click="dialogs.approve = false">Annulla</VBtn>
        <VBtn color="primary" variant="flat" size="small" :loading="isLoading" class="rounded-lg" @click="confirmApprove">Conferma</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<style scoped>
.app-quality-container {
  background-color: rgb(var(--v-theme-background));
  min-height: 100vh;
}
.transition-all { transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1); }
.border { border: 1px solid rgba(var(--v-theme-on-surface), 0.08) !important; box-shadow: none !important; border-radius: 12px !important; }
.filter-card { background-color: rgb(var(--v-theme-surface)) !important; }
.table-card { background-color: rgb(var(--v-theme-surface)) !important; }

.premium-table :deep(th) {
  background-color: rgba(var(--v-theme-on-surface), 0.02) !important;
  border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.08) !important;
  height: 44px !important;
}
.premium-table :deep(th .v-data-table-header__content) {
  color: rgb(var(--v-theme-text-secondary)) !important;
  font-weight: 600 !important;
  font-size: 12px;
}
.premium-table :deep(td) {
  height: 54px !important;
  border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.04) !important;
}

.file-link-wrapper { color: rgb(var(--v-theme-on-surface)); transition: color 0.2s ease; }
.file-text { font-weight: 500; }
.open-indicator { opacity: 0; transition: opacity 0.2s ease; }
.file-link-wrapper:hover .file-text { color: rgb(var(--v-theme-primary)); }
.file-link-wrapper:hover .open-indicator { opacity: 1; color: rgb(var(--v-theme-primary)); }

.text-active-preview .file-text { color: rgb(var(--v-theme-primary)) !important; font-weight: 600; }
.text-active-preview .open-indicator { opacity: 1 !important; color: rgb(var(--v-theme-primary)) !important; }

.preview-sidebar-column { height: calc(100vh - 120px); position: sticky; top: 85px; }
.preview-container-card { height: 100%; background-color: rgb(var(--v-theme-surface)) !important; }
.bg-light-translucent { background-color: rgba(var(--v-theme-on-surface), 0.01) !important; }
.bg-dark-placeholder { background-color: #1e1e1e; }
.absolute-iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border-radius: 0 0 11px 11px; z-index: 1; }

.iframe-wrapper {
  position: relative;
  width: 100%;
  height: 100%;
}
.floating-print-btn {
  position: absolute;
  top: 16px;
  right: 16px;
  z-index: 10;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2) !important;
  border-radius: 8px !important;
  opacity: 0.85;
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.floating-print-btn:hover {
  opacity: 1 !important;
  transform: scale(1.05);
}

.approval-btn {
  border-radius: 6px !important;
  transition: transform 0.2s ease, box-shadow 0.2s ease !important;
  color: #fff !important;
}
.approval-btn:not(:disabled):hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(var(--v-theme-success), 0.3) !important;
}
.approved-done {
  background-color: rgba(var(--v-theme-on-surface), 0.04) !important;
  color: rgba(var(--v-theme-on-surface), 0.26) !important;
}
.shadow-sm {
  box-shadow: 0 2px 4px rgba(0,0,0,0.08) !important;
}

.style-badge { letter-spacing: 0.5px; font-size: 11px !important; height: 24px !important; }
.text-muted { color: rgba(var(--v-theme-on-surface), 0.6) !important; }
.modern-input :deep(.v-field--variant-outlined) { border-radius: 8px !important; --v-field-border-opacity: 0.08; }
.gap-1 { gap: 4px; }
.tracking-tight { letter-spacing: -0.5px; }
</style>
