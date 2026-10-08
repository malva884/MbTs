<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { VForm } from 'vuetify/components/VForm'

definePage({
  meta: {
    action: 'read',
    subject: 'Finanze-Fatturato',
  },
})

// Currency & Date formatters
const euroFormatter = new Intl.NumberFormat('it-IT', {
  style: 'currency',
  currency: 'EUR',
})

const formatEuro = (value: number | string | null | undefined): string => {
  const num = typeof value === 'string' ? parseFloat(value) : (value ?? 0)
  return isNaN(num) ? '€ 0,00' : euroFormatter.format(num)
}

const formatDateIT = (dateStr: string | null | undefined): string => {
  if (!dateStr) return '-'
  const dateOnly = dateStr.includes('T') ? dateStr.split('T')[0] : dateStr.split(' ')[0]
  const parts = dateOnly.split('-')
  if (parts.length !== 3) return dateStr
  return `${parts[2]}/${parts[1]}/${parts[0]}`
}

// Table state
const itemsPerPage = ref(10)
const loading = ref(false)
const totalItems = ref(0)
const sortBy = ref('id')
const orderBy = ref('desc')
const page = ref(1)
const serverItems = ref<any[]>([])

// Stats summary from controller
const stats = ref({
  totale_attivi: 0,
  totale_passivi: 0,
  totale_importo: 0,
  totale_competenza: 0,
  totale_mensile: 0,
  totale_risconto: 0,
  totale_operazioni: 0,
})

// Filters
const searchFilter = ref('')
const tipoFilter = ref<string | null>(null)
const annoFilter = ref<number | null>(null)

// Feedback snackbar
const isSnackbarVisible = ref(false)
const snackbarMessage = ref('')
const snackbarColor = ref('success')

const showMessage = (msg: string, color = 'success') => {
  snackbarMessage.value = msg
  snackbarColor.value = color
  isSnackbarVisible.value = true
}

// Years options for filter
const currentYear = new Date().getFullYear()
const anniOptions = computed(() => [
  { title: 'Tutti gli esercizi', value: null },
  { title: `${currentYear + 1}`, value: currentYear + 1 },
  { title: `${currentYear}`, value: currentYear },
  { title: `${currentYear - 1}`, value: currentYear - 1 },
  { title: `${currentYear - 2}`, value: currentYear - 2 },
])

// Table headers
const headers = [
  { title: 'Tipo', key: 'tipo', sortable: true },
  { title: 'Descrizione', key: 'descrizione', sortable: true },
  { title: 'Importo Totale', key: 'importo_totale', align: 'end' as const, sortable: true },
  { title: 'Periodo Contratto', key: 'periodo', sortable: false },
  { title: 'Gg Tot / Futuri', key: 'giorni', align: 'center' as const, sortable: false },
  { title: 'Quota Mensile', key: 'quota_mensile', align: 'end' as const, sortable: true },
  { title: 'Quota Competenza', key: 'quota_competenza', align: 'end' as const, sortable: true },
  { title: 'Risconto', key: 'importo_risconto', align: 'end' as const, sortable: true },
  { title: 'Azioni', key: 'actions', align: 'center' as const, sortable: false },
]

// Fetch data from controller
const loadItems = async () => {
  loading.value = true
  try {
    const response: any = await $api('/fi/risconti/list', {
      method: 'GET',
      params: {
        page: page.value,
        itemsPerPage: itemsPerPage.value,
        sortBy: sortBy.value,
        orderBy: orderBy.value,
        search: searchFilter.value || undefined,
        tipo: tipoFilter.value || undefined,
        anno: annoFilter.value || undefined,
      },
    })

    if (response) {
      serverItems.value = response.data || []
      totalItems.value = response.total || 0
      if (response.stats) {
        stats.value = response.stats
      }
    }
  } catch (error: any) {
    showMessage(error.message || 'Errore durante il caricamento dei risconti', 'error')
  } finally {
    loading.value = false
  }
}

const updateOptions = (options: any) => {
  sortBy.value = options.sortBy[0]?.key || 'id'
  orderBy.value = options.sortBy[0]?.order || 'desc'
  page.value = options.page
  itemsPerPage.value = options.itemsPerPage
  loadItems()
}

const resetFilters = () => {
  searchFilter.value = ''
  tipoFilter.value = null
  annoFilter.value = null
  page.value = 1
  loadItems()
}

// Export CSV
const isExporting = ref(false)
const exportCSV = async () => {
  isExporting.value = true
  try {
    const accessToken = useCookie('accessToken').value
    const baseUrl = import.meta.env.VITE_API_BASE_URL || '/api'

    const params = new URLSearchParams()
    if (searchFilter.value) params.append('search', searchFilter.value)
    if (tipoFilter.value) params.append('tipo', tipoFilter.value)
    if (annoFilter.value) params.append('anno', annoFilter.value.toString())

    const url = `${baseUrl}/fi/risconti/export?${params.toString()}`

    const response = await fetch(url, {
      headers: {
        Authorization: `Bearer ${accessToken}`,
        Accept: 'text/csv',
      },
    })

    if (!response.ok) {
      throw new Error(`Errore durante l'esportazione: ${response.statusText}`)
    }

    const blob = await response.blob()
    const link = document.createElement('a')
    link.href = URL.createObjectURL(blob)
    link.download = `risconti_contabili_export_${new Date().toISOString().slice(0, 10)}.csv`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    URL.revokeObjectURL(link.href)

    showMessage('Esportazione CSV completata con successo')
  } catch (error: any) {
    showMessage(error.message || 'Errore durante l\'esportazione', 'error')
  } finally {
    isExporting.value = false
  }
}

// Dialog: Nuovo Risconto / Calcolo
const isNewDialogOpen = ref(false)
const isSaving = ref(false)
const isCalculating = ref(false)
const refForm = ref<VForm>()

const defaultFormData = () => ({
  tipo: 'ATTIVO',
  descrizione: '',
  importo_totale: null as number | null,
  data_inizio: `${currentYear}-10-01`,
  data_fine: `${currentYear + 1}-09-30`,
  data_chiusura_bilancio: `${currentYear}-12-31`,
  note: '',
})

const formData = ref(defaultFormData())
const calculationResult = ref<any>(null)
const calculationError = ref<string | null>(null)

const openNewDialog = () => {
  formData.value = defaultFormData()
  calculationResult.value = null
  calculationError.value = null
  isNewDialogOpen.value = true
  // Auto-calcola anteprima iniziale con i default
  nextTick(() => {
    calculatePreview()
  })
}

// Chiamata al controller Laravel per eseguire TUTTI i calcoli
const calculatePreview = async () => {
  if (!formData.value.importo_totale || !formData.value.data_inizio || !formData.value.data_fine || !formData.value.data_chiusura_bilancio) {
    calculationResult.value = null
    return
  }

  isCalculating.value = true
  calculationError.value = null

  try {
    const response: any = await $api('/fi/risconti/calculate', {
      method: 'POST',
      body: {
        tipo: formData.value.tipo,
        descrizione: formData.value.descrizione || 'Operazione',
        importo_totale: formData.value.importo_totale,
        data_inizio: formData.value.data_inizio,
        data_fine: formData.value.data_fine,
        data_chiusura_bilancio: formData.value.data_chiusura_bilancio,
      },
    })

    if (response?.success) {
      calculationResult.value = response.data
    }
  } catch (error: any) {
    calculationResult.value = null
    calculationError.value = error.data?.message || error.message || 'Errore durante il calcolo contabile'
  } finally {
    isCalculating.value = false
  }
}

// Salva risconto nel backend
const saveRisconto = async () => {
  refForm.value?.validate().then(async ({ valid }) => {
    if (!valid) return

    isSaving.value = true
    try {
      const response: any = await $api('/fi/risconti/store', {
        method: 'POST',
        body: {
          tipo: formData.value.tipo,
          descrizione: formData.value.descrizione,
          importo_totale: formData.value.importo_totale,
          data_inizio: formData.value.data_inizio,
          data_fine: formData.value.data_fine,
          data_chiusura_bilancio: formData.value.data_chiusura_bilancio,
          note: formData.value.note,
        },
      })

      if (response?.success) {
        showMessage(response.message || 'Risconto registrato con successo')
        isNewDialogOpen.value = false
        loadItems()
      }
    } catch (error: any) {
      showMessage(error.data?.message || error.message || 'Errore durante il salvataggio', 'error')
    } finally {
      isSaving.value = false
    }
  })
}

// Dialog Dettaglio / Partita Doppia
const isDetailDialogOpen = ref(false)
const selectedRisconto = ref<any>(null)
const isLoadingDetail = ref(false)

const openDetailDialog = async (item: any) => {
  isLoadingDetail.value = true
  isDetailDialogOpen.value = true
  try {
    const response: any = await $api(`/fi/risconti/show/${item.id}`, {
      method: 'GET',
    })
    selectedRisconto.value = response?.data || item
  } catch {
    selectedRisconto.value = item
  } finally {
    isLoadingDetail.value = false
  }
}

// Dialog Eliminazione
const isDeleteDialogOpen = ref(false)
const itemToDelete = ref<any>(null)
const isDeleting = ref(false)

const confirmDelete = (item: any) => {
  itemToDelete.value = item
  isDeleteDialogOpen.value = true
}

const deleteItem = async () => {
  if (!itemToDelete.value) return
  isDeleting.value = true
  try {
    const response: any = await $api(`/fi/risconti/delete/${itemToDelete.value.id}`, {
      method: 'DELETE',
    })
    showMessage(response?.message || 'Risconto eliminato con successo')
    isDeleteDialogOpen.value = false
    loadItems()
  } catch (error: any) {
    showMessage(error.message || 'Errore durante l\'eliminazione', 'error')
  } finally {
    isDeleting.value = false
  }
}

onMounted(() => {
  loadItems()
})
</script>

<template>
  <div class="workspace-container w-100 d-flex flex-column pa-4 gap-3">
    <!-- Snackbar feedback -->
    <VSnackbar
      v-model="isSnackbarVisible"
      transition="scroll-y-reverse-transition"
      location="top center"
      :timeout="3000"
      :color="snackbarColor"
    >
      {{ snackbarMessage }}
    </VSnackbar>

    <!-- Top KPI Dashboard Summary Cards -->
    <VRow>
      <!-- Totale Risconti Attivi -->
      <VCol cols="12" md="4">
        <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
          <VCardText class="d-flex align-center justify-space-between py-4">
            <div>
              <div class="text-caption text-uppercase font-weight-bold text-success mb-1">
                Totale Risconti Attivi
              </div>
              <div class="text-h5 font-weight-bold text-success font-mono-num">
                {{ formatEuro(stats.totale_attivi) }}
              </div>
              <div class="text-caption text-medium-emphasis mt-1">
                Costi sospesi (Esercizio futuro)
              </div>
            </div>
            <VAvatar size="48" color="success" variant="tonal" class="rounded-lg">
              <VIcon icon="tabler-arrow-down-left" size="28" />
            </VAvatar>
          </VCardText>
        </VCard>
      </VCol>

      <!-- Totale Risconti Passivi -->
      <VCol cols="12" md="4">
        <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
          <VCardText class="d-flex align-center justify-space-between py-4">
            <div>
              <div class="text-caption text-uppercase font-weight-bold text-warning mb-1">
                Totale Risconti Passivi
              </div>
              <div class="text-h5 font-weight-bold text-warning font-mono-num">
                {{ formatEuro(stats.totale_passivi) }}
              </div>
              <div class="text-caption text-medium-emphasis mt-1">
                Ricavi sospesi (Esercizio futuro)
              </div>
            </div>
            <VAvatar size="48" color="warning" variant="tonal" class="rounded-lg">
              <VIcon icon="tabler-arrow-up-right" size="28" />
            </VAvatar>
          </VCardText>
        </VCard>
      </VCol>

      <!-- Operazioni Registrate -->
      <VCol cols="12" md="4">
        <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
          <VCardText class="d-flex align-center justify-space-between py-4">
            <div>
              <div class="text-caption text-uppercase font-weight-bold text-primary mb-1">
                Operazioni Registrate
              </div>
              <div class="text-h5 font-weight-bold text-primary font-mono-num">
                {{ stats.totale_operazioni }}
              </div>
              <div class="text-caption text-medium-emphasis mt-1">
                Totale operazioni nel bilancio
              </div>
            </div>
            <VAvatar size="48" color="primary" variant="tonal" class="rounded-lg">
              <VIcon icon="tabler-calculator" size="28" />
            </VAvatar>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Main Card - Layout identico ad Anagrafica Dipendenti -->
    <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
      <!-- Card Header -->
      <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
        <div class="d-flex align-center gap-2">
          <VIcon icon="tabler-calculator" size="24" color="primary" />
          <div>
            <div class="text-h6 font-weight-medium">Calcolatore Risconti Contabili</div>
            <div class="text-caption text-medium-emphasis">{{ totalItems }} Operazioni Registrate</div>
          </div>
        </div>

        <div class="d-flex align-center gap-2">
          <VBtn
            prepend-icon="tabler-file-spreadsheet"
            color="secondary"
            variant="outlined"
            density="comfortable"
            class="px-3 me-1"
            :loading="isExporting"
            @click="exportCSV"
          >
            Esporta CSV
          </VBtn>

          <VBtn
            prepend-icon="tabler-plus"
            color="primary"
            variant="flat"
            density="comfortable"
            class="px-3"
            @click="openNewDialog"
          >
            Nuovo Risconto
          </VBtn>
        </div>
      </VCardText>

      <VDivider />

      <!-- Filters Bar (Layout Dipendenti) -->
      <VCardText class="pa-3">
        <VRow class="mb-2">
          <!-- Cerca Descrizione / Note -->
          <VCol cols="12" sm="5">
            <AppTextField
              v-model="searchFilter"
              label="Cerca"
              placeholder="Cerca descrizione operazione..."
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-search"
              @keyup.enter="loadItems"
              @click:clear="loadItems"
            />
          </VCol>

          <!-- Tipologia Risconto -->
          <VCol cols="12" sm="3">
            <AppSelect
              v-model="tipoFilter"
              label="Tipologia"
              placeholder="Tutte le tipologie"
              :items="[
                { title: 'Tutti', value: null },
                { title: 'Risconto Attivo (Costo)', value: 'ATTIVO' },
                { title: 'Risconto Passivo (Ricavo)', value: 'PASSIVO' },
              ]"
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-filter"
              @update:model-value="loadItems"
              @click:clear="loadItems"
            />
          </VCol>

          <!-- Esercizio di Bilancio -->
          <VCol cols="12" sm="2">
            <AppSelect
              v-model="annoFilter"
              label="Esercizio"
              placeholder="Tutti gli anni"
              :items="anniOptions"
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-calendar"
              @update:model-value="loadItems"
              @click:clear="loadItems"
            />
          </VCol>

          <!-- Reset filtri -->
          <VCol cols="12" sm="2" class="d-flex align-center mt-auto">
            <VBtn
              variant="tonal"
              color="secondary"
              density="comfortable"
              prepend-icon="tabler-rotate-clockwise"
              class="w-100"
              @click="resetFilters"
            >
              Reset
            </VBtn>
          </VCol>
        </VRow>
      </VCardText>

      <VDivider />

      <!-- DataTable Server -->
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        :headers="headers"
        :items="serverItems"
        :items-length="totalItems"
        :loading="loading"
        density="comfortable"
        hover
        @update:options="updateOptions"
      >
        <!-- No data state -->
        <template #no-data>
          <div class="py-10 text-center">
            <VIcon icon="tabler-calculator" size="40" class="text-disabled mb-2" />
            <p class="text-body-1 text-disabled mb-0">Nessun risconto contabile trovato</p>
          </div>
        </template>

        <!-- Tipo Chip -->
        <template #item.tipo="{ item }">
          <VChip
            size="small"
            variant="tonal"
            :color="item.tipo === 'ATTIVO' ? 'success' : 'warning'"
            class="font-weight-medium"
          >
            <VIcon
              :icon="item.tipo === 'ATTIVO' ? 'tabler-shield-check' : 'tabler-cash'"
              start
              size="14"
            />
            {{ item.tipo === 'ATTIVO' ? 'Attivo (Costo)' : 'Passivo (Ricavo)' }}
          </VChip>
        </template>

        <!-- Descrizione -->
        <template #item.descrizione="{ item }">
          <div class="d-flex flex-column">
            <span class="font-weight-medium text-high-emphasis">{{ item.descrizione }}</span>
            <span v-if="item.note" class="text-caption text-medium-emphasis text-truncate" style="max-width: 250px;">
              {{ item.note }}
            </span>
          </div>
        </template>

        <!-- Importo Totale -->
        <template #item.importo_totale="{ item }">
          <span class="font-mono-num font-weight-medium">
            {{ formatEuro(item.importo_totale) }}
          </span>
        </template>

        <!-- Periodo Contratto -->
        <template #item.periodo="{ item }">
          <div class="d-flex flex-column text-caption text-medium-emphasis">
            <span>Dal: <strong class="text-high-emphasis">{{ formatDateIT(item.data_inizio) }}</strong></span>
            <span>Al: <strong class="text-high-emphasis">{{ formatDateIT(item.data_fine) }}</strong></span>
          </div>
        </template>

        <!-- Giorni Totali / Futuri -->
        <template #item.giorni="{ item }">
          <div class="text-center font-mono-num">
            <span>{{ item.giorni_totali }} gg</span>
            <span class="text-medium-emphasis"> / </span>
            <strong class="text-primary">{{ item.giorni_futuri }} gg</strong>
          </div>
        </template>

        <!-- Quota Mensile -->
        <template #item.quota_mensile="{ item }">
          <span class="font-mono-num text-info">
            {{ formatEuro(item.quota_mensile) }}
          </span>
        </template>

        <!-- Quota Competenza -->
        <template #item.quota_competenza="{ item }">
          <span class="font-mono-num text-medium-emphasis">
            {{ formatEuro(item.quota_competenza) }}
          </span>
        </template>

        <!-- Valore Risconto -->
        <template #item.importo_risconto="{ item }">
          <span
            class="font-mono-num font-weight-bold"
            :class="item.tipo === 'ATTIVO' ? 'text-success' : 'text-warning'"
          >
            {{ formatEuro(item.importo_risconto) }}
          </span>
        </template>

        <!-- Azioni (Layout Dipendenti) -->
        <template #item.actions="{ item }">
          <div class="d-flex gap-1 justify-center">
            <!-- Dettaglio & Partita Doppia -->
            <IconBtn
              color="primary"
              size="small"
              title="Visualizza Scrittura Contabile"
              @click="openDetailDialog(item)"
            >
              <VIcon icon="tabler-file-invoice" size="18" />
            </IconBtn>

            <!-- Elimina -->
            <IconBtn
              color="error"
              size="small"
              title="Elimina Risconto"
              @click="confirmDelete(item)"
            >
              <VIcon icon="tabler-trash" size="18" />
            </IconBtn>
          </div>
        </template>
      </VDataTableServer>
    </VCard>

    <!-- Dialog: Nuovo Risconto / Calcolatore -->
    <VDialog v-model="isNewDialogOpen" max-width="900" persistent>
      <VCard class="rounded-lg">
        <VCardItem class="pb-2">
          <div class="d-flex align-center justify-space-between w-100">
            <div class="d-flex align-center gap-2">
              <VAvatar color="primary" variant="tonal" size="36" class="rounded">
                <VIcon icon="tabler-calculator" size="20" />
              </VAvatar>
              <div>
                <VCardTitle class="text-h6">Nuovo Risconto Contabile</VCardTitle>
                <VCardSubtitle class="text-caption">Calcolo pro-rata temporis & scrittura in partita doppia</VCardSubtitle>
              </div>
            </div>
            <IconBtn size="small" @click="isNewDialogOpen = false">
              <VIcon icon="tabler-x" size="20" />
            </IconBtn>
          </div>
        </VCardItem>

        <VDivider />

        <VCardText class="pa-4">
          <VForm ref="refForm" @submit.prevent="saveRisconto">
            <VRow>
              <!-- Selezione Tipologia Risconto -->
              <VCol cols="12">
                <label class="text-caption font-weight-bold text-uppercase d-block mb-2">Tipologia Risconto</label>
                <div class="d-flex gap-3">
                  <VBtn
                    :variant="formData.tipo === 'ATTIVO' ? 'flat' : 'outlined'"
                    :color="formData.tipo === 'ATTIVO' ? 'success' : 'secondary'"
                    prepend-icon="tabler-shield-check"
                    class="flex-grow-1"
                    @click="formData.tipo = 'ATTIVO'; calculatePreview()"
                  >
                    Risconto Attivo (Costo Sospeso)
                  </VBtn>

                  <VBtn
                    :variant="formData.tipo === 'PASSIVO' ? 'flat' : 'outlined'"
                    :color="formData.tipo === 'PASSIVO' ? 'warning' : 'secondary'"
                    prepend-icon="tabler-cash"
                    class="flex-grow-1"
                    @click="formData.tipo = 'PASSIVO'; calculatePreview()"
                  >
                    Risconto Passivo (Ricavo Sospeso)
                  </VBtn>
                </div>
              </VCol>

              <!-- Descrizione Operazione -->
              <VCol cols="12" sm="8">
                <AppTextField
                  v-model="formData.descrizione"
                  label="Descrizione Operazione *"
                  placeholder="Es. Polizza Assicurativa, Canone Locazione, Noleggio"
                  :rules="[v => !!v || 'La descrizione è obbligatoria']"
                  prepend-inner-icon="tabler-tag"
                  @update:model-value="calculatePreview"
                />
              </VCol>

              <!-- Importo Totale -->
              <VCol cols="12" sm="4">
                <AppTextField
                  v-model.number="formData.importo_totale"
                  type="number"
                  step="0.01"
                  min="0.01"
                  label="Importo Totale (€) *"
                  placeholder="1200.00"
                  prefix="€"
                  :rules="[v => (v !== null && v > 0) || 'Importo obbligatorio (> 0)']"
                  @update:model-value="calculatePreview"
                />
              </VCol>

              <!-- Data Inizio -->
              <VCol cols="12" sm="4">
                <AppDateTimePicker
                  v-model="formData.data_inizio"
                  label="Data Inizio Contratto *"
                  placeholder="AAAA-MM-GG"
                  :rules="[v => !!v || 'Data inizio obbligatoria']"
                  prepend-inner-icon="tabler-calendar"
                  @update:model-value="calculatePreview"
                />
              </VCol>

              <!-- Data Fine -->
              <VCol cols="12" sm="4">
                <AppDateTimePicker
                  v-model="formData.data_fine"
                  label="Data Fine Contratto *"
                  placeholder="AAAA-MM-GG"
                  :rules="[v => !!v || 'Data fine obbligatoria']"
                  prepend-inner-icon="tabler-calendar"
                  @update:model-value="calculatePreview"
                />
              </VCol>

              <!-- Data Chiusura Bilancio -->
              <VCol cols="12" sm="4">
                <AppDateTimePicker
                  v-model="formData.data_chiusura_bilancio"
                  label="Data Chiusura Bilancio *"
                  placeholder="AAAA-MM-GG"
                  :rules="[v => !!v || 'Data bilancio obbligatoria']"
                  prepend-inner-icon="tabler-calendar-event"
                  @update:model-value="calculatePreview"
                />
              </VCol>

              <!-- Note Aggiuntive -->
              <VCol cols="12">
                <AppTextField
                  v-model="formData.note"
                  label="Note Aggiuntive (Opzionale)"
                  placeholder="Eventuali annotazioni contabili o riferimenti a fatture"
                />
              </VCol>
            </VRow>

            <!-- Errore validazione calcolo dal controller -->
            <VAlert
              v-if="calculationError"
              type="error"
              variant="tonal"
              density="compact"
              class="mt-3"
            >
              {{ calculationError }}
            </VAlert>

            <!-- Box Risultati Calcolati dal Controller -->
            <div v-if="calculationResult" class="mt-4">
              <VDivider class="my-3" />

              <div class="d-flex align-center justify-space-between mb-3">
                <div class="d-flex align-center gap-2">
                  <VIcon icon="tabler-chart-bar" color="primary" size="20" />
                  <span class="text-subtitle-2 font-weight-bold">Elaborazione Contabile del Controller</span>
                </div>
                <VChip
                  size="small"
                  variant="flat"
                  :color="calculationResult.tipo === 'ATTIVO' ? 'success' : 'warning'"
                  class="font-weight-bold"
                >
                  {{ calculationResult.partita_doppia?.descrizione_tipo || calculationResult.tipo }}
                </VChip>
              </div>

              <!-- Metriche di calcolo -->
              <VRow class="mb-3">
                <VCol cols="6" sm="3">
                  <div class="bg-light rounded pa-3 text-center border">
                    <div class="text-caption text-medium-emphasis">Giorni Totali</div>
                    <div class="text-h6 font-weight-bold font-mono-num">{{ calculationResult.giorni_totali }}</div>
                  </div>
                </VCol>

                <VCol cols="6" sm="3">
                  <div class="bg-light rounded pa-3 text-center border">
                    <div class="text-caption text-medium-emphasis">Giorni Anno Corr.</div>
                    <div class="text-h6 font-weight-bold font-mono-num">{{ calculationResult.giorni_competenza }}</div>
                  </div>
                </VCol>

                <VCol cols="6" sm="3">
                  <div class="bg-light rounded pa-3 text-center border">
                    <div class="text-caption text-medium-emphasis text-primary">Giorni Futuri</div>
                    <div class="text-h6 font-weight-bold font-mono-num text-primary">{{ calculationResult.giorni_futuri }}</div>
                  </div>
                </VCol>

                <VCol cols="6" sm="3">
                  <div class="bg-light rounded pa-3 text-center border">
                    <div class="text-caption text-medium-emphasis">Quota Giornaliera</div>
                    <div class="text-h6 font-weight-bold font-mono-num">{{ formatEuro(calculationResult.quota_giornaliera) }}</div>
                  </div>
                </VCol>

                <VCol cols="6" sm="4">
                  <div class="bg-light rounded pa-3 text-center border">
                    <div class="text-caption text-medium-emphasis text-info">Quota Mensile Media</div>
                    <div class="text-h6 font-weight-bold font-mono-num text-info">{{ formatEuro(calculationResult.quota_mensile) }}</div>
                  </div>
                </VCol>

                <VCol cols="6" sm="4">
                  <div class="bg-light rounded pa-3 text-center border">
                    <div class="text-caption text-medium-emphasis">Quota Competenza</div>
                    <div class="text-h6 font-weight-bold font-mono-num">{{ formatEuro(calculationResult.quota_competenza) }}</div>
                  </div>
                </VCol>

                <VCol cols="12" sm="4">
                  <div class="rounded pa-3 text-center border bg-light-primary">
                    <div class="text-caption text-uppercase font-weight-bold" :class="calculationResult.tipo === 'ATTIVO' ? 'text-success' : 'text-warning'">
                      Valore Risconto
                    </div>
                    <div class="text-h5 font-weight-bold font-mono-num" :class="calculationResult.tipo === 'ATTIVO' ? 'text-success' : 'text-warning'">
                      {{ formatEuro(calculationResult.importo_risconto) }}
                    </div>
                  </div>
                </VCol>
              </VRow>

              <!-- Progress bar ripartizione -->
              <div class="mb-4">
                <div class="d-flex justify-space-between text-caption mb-1">
                  <span>Competenza: <strong>{{ calculationResult.percentuale_competenza }}%</strong></span>
                  <span class="text-primary">Risconto Futuro: <strong>{{ calculationResult.percentuale_risconto }}%</strong></span>
                </div>
                <VProgressLinear
                  :model-value="calculationResult.percentuale_competenza"
                  height="10"
                  color="secondary"
                  bg-color="primary"
                  rounded
                />
              </div>

              <!-- Box Partita Doppia elaborata dal controller -->
              <VCard variant="flat" color="surface-variant" class="pa-4 rounded-lg">
                <div class="d-flex align-center justify-space-between mb-3 border-b pb-2">
                  <div class="d-flex align-center gap-2">
                    <VIcon icon="tabler-book" size="18" color="primary" />
                    <span class="text-caption font-weight-bold text-uppercase">
                      Scrittura di Assestamento al {{ formatDateIT(calculationResult.data_chiusura_bilancio) }}
                    </span>
                  </div>
                  <VChip size="x-small" variant="tonal" color="primary">Partita Doppia</VChip>
                </div>

                <div class="d-flex flex-column gap-2 font-mono-num text-body-2">
                  <!-- Riga DARE -->
                  <div class="d-flex align-center justify-space-between bg-surface pa-2 rounded border">
                    <div class="d-flex align-center gap-2">
                      <VChip size="x-small" color="success" class="font-weight-bold">DARE</VChip>
                      <span class="font-weight-medium">{{ calculationResult.partita_doppia?.conto_dare }}</span>
                    </div>
                    <span class="font-weight-bold text-success">{{ formatEuro(calculationResult.partita_doppia?.dare_importo) }}</span>
                  </div>

                  <!-- Riga AVERE -->
                  <div class="d-flex align-center justify-space-between bg-surface pa-2 rounded border">
                    <div class="d-flex align-center gap-2">
                      <VChip size="x-small" color="warning" class="font-weight-bold">AVERE</VChip>
                      <span class="font-weight-medium">{{ calculationResult.partita_doppia?.conto_avere }}</span>
                    </div>
                    <span class="font-weight-bold text-warning">{{ formatEuro(calculationResult.partita_doppia?.avere_importo) }}</span>
                  </div>
                </div>

                <div class="text-caption text-medium-emphasis font-italic mt-2">
                  {{ calculationResult.partita_doppia?.spiegazione }}
                </div>
              </VCard>
            </div>
          </VForm>
        </VCardText>

        <VDivider />

        <VCardActions class="pa-4 d-flex justify-end gap-2">
          <VBtn variant="outlined" color="secondary" @click="isNewDialogOpen = false">
            Annulla
          </VBtn>
          <VBtn
            variant="flat"
            color="primary"
            prepend-icon="tabler-device-floppy"
            :loading="isSaving"
            @click="saveRisconto"
          >
            Salva Risconto
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Dialog: Dettaglio Scrittura Contabile & Risconto -->
    <VDialog v-model="isDetailDialogOpen" max-width="700">
      <VCard v-if="selectedRisconto" class="rounded-lg">
        <VCardItem class="pb-2">
          <div class="d-flex align-center justify-space-between w-100">
            <div class="d-flex align-center gap-2">
              <VAvatar
                :color="selectedRisconto.tipo === 'ATTIVO' ? 'success' : 'warning'"
                variant="tonal"
                size="36"
                class="rounded"
              >
                <VIcon :icon="selectedRisconto.tipo === 'ATTIVO' ? 'tabler-shield-check' : 'tabler-cash'" size="20" />
              </VAvatar>
              <div>
                <VCardTitle class="text-h6">{{ selectedRisconto.descrizione }}</VCardTitle>
                <VCardSubtitle class="text-caption">
                  {{ selectedRisconto.tipo === 'ATTIVO' ? 'Risconto Attivo (Costo Sospeso)' : 'Risconto Passivo (Ricavo Sospeso)' }}
                </VCardSubtitle>
              </div>
            </div>
            <IconBtn size="small" @click="isDetailDialogOpen = false">
              <VIcon icon="tabler-x" size="20" />
            </IconBtn>
          </div>
        </VCardItem>

        <VDivider />

        <VCardText class="pa-4">
          <!-- Riepilogo metriche principali -->
          <VRow class="mb-4">
            <VCol cols="6" sm="4">
              <div class="text-caption text-medium-emphasis">Importo Totale</div>
              <div class="text-h6 font-weight-bold font-mono-num">{{ formatEuro(selectedRisconto.importo_totale) }}</div>
            </VCol>

            <VCol cols="6" sm="4">
              <div class="text-caption text-medium-emphasis">Quota Competenza</div>
              <div class="text-h6 font-weight-bold font-mono-num">{{ formatEuro(selectedRisconto.quota_competenza) }}</div>
            </VCol>

            <VCol cols="12" sm="4">
              <div class="text-caption text-medium-emphasis font-weight-bold" :class="selectedRisconto.tipo === 'ATTIVO' ? 'text-success' : 'text-warning'">
                Valore Risconto
              </div>
              <div class="text-h5 font-weight-bold font-mono-num" :class="selectedRisconto.tipo === 'ATTIVO' ? 'text-success' : 'text-warning'">
                {{ formatEuro(selectedRisconto.importo_risconto) }}
              </div>
            </VCol>

            <VCol cols="12" sm="6">
              <div class="text-caption text-medium-emphasis">Periodo Contratto</div>
              <div class="text-body-2 font-weight-medium">
                {{ formatDateIT(selectedRisconto.data_inizio) }} - {{ formatDateIT(selectedRisconto.data_fine) }} ({{ selectedRisconto.giorni_totali }} giorni)
              </div>
            </VCol>

            <VCol cols="12" sm="6">
              <div class="text-caption text-medium-emphasis">Chiusura Bilancio</div>
              <div class="text-body-2 font-weight-medium">
                {{ formatDateIT(selectedRisconto.data_chiusura_bilancio) }} ({{ selectedRisconto.giorni_futuri }} giorni futuri)
              </div>
            </VCol>

            <VCol v-if="selectedRisconto.note" cols="12">
              <div class="text-caption text-medium-emphasis">Note</div>
              <div class="text-body-2">{{ selectedRisconto.note }}</div>
            </VCol>
          </VRow>

          <!-- Scrittura Partita Doppia -->
          <VCard variant="flat" color="surface-variant" class="pa-4 rounded-lg">
            <div class="d-flex align-center justify-space-between mb-3 border-b pb-2">
              <div class="d-flex align-center gap-2">
                <VIcon icon="tabler-book" size="18" color="primary" />
                <span class="text-caption font-weight-bold text-uppercase">
                  Scrittura di Assestamento al {{ formatDateIT(selectedRisconto.data_chiusura_bilancio) }}
                </span>
              </div>
              <VChip size="x-small" variant="tonal" color="primary">Partita Doppia</VChip>
            </div>

            <div class="d-flex flex-column gap-2 font-mono-num text-body-2">
              <div class="d-flex align-center justify-space-between bg-surface pa-2 rounded border">
                <div class="d-flex align-center gap-2">
                  <VChip size="x-small" color="success" class="font-weight-bold">DARE</VChip>
                  <span class="font-weight-medium">{{ selectedRisconto.conto_dare || selectedRisconto.partita_doppia?.conto_dare }}</span>
                </div>
                <span class="font-weight-bold text-success">{{ formatEuro(selectedRisconto.importo_risconto) }}</span>
              </div>

              <div class="d-flex align-center justify-space-between bg-surface pa-2 rounded border">
                <div class="d-flex align-center gap-2">
                  <VChip size="x-small" color="warning" class="font-weight-bold">AVERE</VChip>
                  <span class="font-weight-medium">{{ selectedRisconto.conto_avere || selectedRisconto.partita_doppia?.conto_avere }}</span>
                </div>
                <span class="font-weight-bold text-warning">{{ formatEuro(selectedRisconto.importo_risconto) }}</span>
              </div>
            </div>

            <div v-if="selectedRisconto.partita_doppia?.spiegazione" class="text-caption text-medium-emphasis font-italic mt-2">
              {{ selectedRisconto.partita_doppia.spiegazione }}
            </div>
          </VCard>
        </VCardText>

        <VDivider />

        <VCardActions class="pa-3 d-flex justify-end">
          <VBtn variant="tonal" color="secondary" @click="isDetailDialogOpen = false">
            Chiudi
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Dialog Conferma Eliminazione -->
    <VDialog v-model="isDeleteDialogOpen" max-width="450">
      <VCard class="rounded-lg">
        <VCardItem class="pb-2">
          <VCardTitle class="text-h6 text-error d-flex align-center gap-2">
            <VIcon icon="tabler-alert-triangle" />
            Conferma Eliminazione
          </VCardTitle>
        </VCardItem>

        <VCardText class="pa-4">
          Sei sicuro di voler eliminare il risconto <strong>"{{ itemToDelete?.descrizione }}"</strong> di <strong>{{ formatEuro(itemToDelete?.importo_totale) }}</strong>?
          L'operazione non può essere annullata.
        </VCardText>

        <VDivider />

        <VCardActions class="pa-3 d-flex justify-end gap-2">
          <VBtn variant="outlined" color="secondary" @click="isDeleteDialogOpen = false">
            Annulla
          </VBtn>
          <VBtn
            variant="flat"
            color="error"
            :loading="isDeleting"
            @click="deleteItem"
          >
            Elimina
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>

<style scoped>
.font-mono-num {
  font-family: monospace;
}
</style>
