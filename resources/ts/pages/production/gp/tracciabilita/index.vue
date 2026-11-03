<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import moment from 'moment'
import { useI18n } from 'vue-i18n'

definePage({
  meta: {
    action: 'report',
    subject: 'Produzione-Interscambio',
  },
})

const { t } = useI18n()

// Modalità di ricerca: 'lotto' | 'ordine'
const searchMode = ref<'lotto' | 'ordine'>('lotto')

// Filtri per lotto
const lottoSingle = ref('')
const lottoList = ref<string[]>([])
const tipoLottoFilter = ref<string>('tutti')

// Filtri per ordine
const ordineSingle = ref('')
const ordineList = ref<string[]>([])

// Dialog per incollare lista (Excel / testo)
const isListDialogOpen = ref(false)
const dialogRawText = ref('')
const dialogTarget = ref<'lotto' | 'ordine'>('lotto')

const tipoLottoOptions = [
  { title: 'Tutti i lotti', value: 'tutti' },
  { title: 'Solo Componente (MP)', value: 'componente' },
  { title: 'Solo Produzione (Bobina)', value: 'produzione' },
]

// Tabella & Paginazione
const itemsPerPage = ref(15)
const page = ref(1)
const loading = ref(false)
const exportLoading = ref(false)
const exportFabbLoading = ref(false)
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const serverItems = ref<any[]>([])
const infoMessage = ref('')

// Parsing helper
const extractCodes = (raw: string): string[] => {
  if (!raw || !raw.trim()) return []
  const items = raw.trim().split(/[\s,;\r\n\t]+/).filter(Boolean).map(s => s.trim())
  return Array.from(new Set(items))
}

// Lista attiva di lotti per la ricerca
const activeLotti = computed<string[]>(() => {
  if (lottoList.value.length > 0) return lottoList.value
  return extractCodes(lottoSingle.value)
})

// Lista attiva di ordini per la ricerca
const activeOrdini = computed<string[]>(() => {
  if (ordineList.value.length > 0) return ordineList.value
  return extractCodes(ordineSingle.value)
})

const hasFilters = computed(() => {
  if (searchMode.value === 'lotto') {
    return activeLotti.value.length > 0
  }
  return activeOrdini.value.length > 0
})

// Statistiche live nel Dialog
const dialogParsed = computed(() => {
  if (!dialogRawText.value || !dialogRawText.value.trim()) {
    return { raw: 0, unique: 0, duplicates: 0, items: [] as string[] }
  }
  const rawItems = dialogRawText.value.trim().split(/[\s,;\r\n\t]+/).filter(Boolean).map(s => s.trim())
  const uniqueItems = Array.from(new Set(rawItems))
  return {
    raw: rawItems.length,
    unique: uniqueItems.length,
    duplicates: Math.max(0, rawItems.length - uniqueItems.length),
    items: uniqueItems,
  }
})

// Apertura dialog incolla da Excel
const openPasteDialog = (target: 'lotto' | 'ordine') => {
  dialogTarget.value = target
  if (target === 'lotto') {
    dialogRawText.value = lottoList.value.length > 0 ? lottoList.value.join('\n') : lottoSingle.value
  } else {
    dialogRawText.value = ordineList.value.length > 0 ? ordineList.value.join('\n') : ordineSingle.value
  }
  isListDialogOpen.value = true
}

// Applicazione codici dal dialog
const applyDialogList = () => {
  const codes = dialogParsed.value.items
  if (dialogTarget.value === 'lotto') {
    if (codes.length > 1) {
      lottoList.value = codes
      lottoSingle.value = ''
    } else {
      lottoList.value = []
      lottoSingle.value = codes[0] || ''
    }
  } else {
    if (codes.length > 1) {
      ordineList.value = codes
      ordineSingle.value = ''
    } else {
      ordineList.value = []
      ordineSingle.value = codes[0] || ''
    }
  }
  isListDialogOpen.value = false
  if (codes.length > 0) {
    page.value = 1
    loadItems()
  }
}

// Reset lista specifica
const clearLottoList = () => {
  lottoList.value = []
  lottoSingle.value = ''
  serverItems.value = []
  totalItems.value = 0
}

const clearOrdineList = () => {
  ordineList.value = []
  ordineSingle.value = ''
  serverItems.value = []
  totalItems.value = 0
}

// Riconoscimento automatico incollamento rapido nel campo singolo
const handleSingleLottoInput = (val: string) => {
  const parsed = extractCodes(val)
  if (parsed.length > 1) {
    lottoList.value = parsed
    lottoSingle.value = ''
  }
}

const handleSingleOrdineInput = (val: string) => {
  const parsed = extractCodes(val)
  if (parsed.length > 1) {
    ordineList.value = parsed
    ordineSingle.value = ''
  }
}

const updateOptions = (options: any) => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
  page.value = options.page
  itemsPerPage.value = options.itemsPerPage

  loadItems()
}

// Caricamento dati via POST / GET sicuro
const loadItems = async () => {
  if (!hasFilters.value) {
    serverItems.value = []
    totalItems.value = 0
    infoMessage.value = 'Inserisci un codice o incolla una lista da Excel per avviare la ricerca.'
    return
  }

  infoMessage.value = ''
  loading.value = true

  const lottiParam = searchMode.value === 'lotto' && activeLotti.value.length > 0 ? activeLotti.value.join(',') : undefined
  const ordiniParam = searchMode.value === 'ordine' && activeOrdini.value.length > 0 ? activeOrdini.value.join(',') : undefined

  try {
    const { data: resultData } = await useApi<any>(createUrl('/gp/tracciabilita', {
      query: {
        page: page.value,
        itemsPerPage: itemsPerPage.value,
        sortBy: sortBy.value,
        orderBy: orderBy.value,
        lotto: lottiParam,
        ordine: ordiniParam,
        tipo_lotto: searchMode.value === 'lotto' && tipoLottoFilter.value !== 'tutti' ? tipoLottoFilter.value : undefined,
      },
    }))

    if (resultData.value) {
      serverItems.value = resultData.value.data || []
      totalItems.value = resultData.value.total || 0
      if (serverItems.value.length === 0) {
        infoMessage.value = 'Nessun risultato trovato per i criteri specificati.'
      }
    } else {
      serverItems.value = []
      totalItems.value = 0
    }
  } catch (err) {
    serverItems.value = []
    totalItems.value = 0
  } finally {
    loading.value = false
  }
}

// Esportazione Excel via Blob (POST garantito senza limiti di URL)
const downloadExcel = async () => {
  if (!hasFilters.value) return

  exportLoading.value = true
  try {
    const lottiParam = searchMode.value === 'lotto' && activeLotti.value.length > 0 ? activeLotti.value.join(',') : undefined
    const ordiniParam = searchMode.value === 'ordine' && activeOrdini.value.length > 0 ? activeOrdini.value.join(',') : undefined

    const response = await fetch('/api/gp/tracciabilita/export', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/octet-stream',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({
        lotto: lottiParam,
        ordine: ordiniParam,
        tipo_lotto: searchMode.value === 'lotto' && tipoLottoFilter.value !== 'tutti' ? tipoLottoFilter.value : undefined,
      }),
    })

    if (!response.ok) {
      const body = await response.text()
      console.error('Export failed', response.status, body.slice(0, 500))
      throw new Error(`Errore durante la generazione del file Excel (HTTP ${response.status})`)
    }

    const blob = await response.blob()
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `tracciabilita_${moment().format('YYYYMMDD_HHmmss')}.xlsx`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    window.URL.revokeObjectURL(url)
  } catch (err) {
    console.error('Export error:', err)
  } finally {
    exportLoading.value = false
  }
}

// Esportazione fabbisogni per lista ordini (AGG_EXP_PRODUZIONE_FABB_TMP)
const downloadFabbisogni = async () => {
  if (searchMode.value !== 'ordine' || activeOrdini.value.length === 0) return

  exportFabbLoading.value = true
  try {
    const response = await fetch('/api/gp/tracciabilita/export-fabbisogni', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/octet-stream',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({
        ordine: activeOrdini.value.join(','),
      }),
    })

    if (!response.ok) {
      const body = await response.text()
      console.error('Export fabbisogni failed', response.status, body.slice(0, 500))
      throw new Error(`Errore durante la generazione del file Excel (HTTP ${response.status})`)
    }

    const blob = await response.blob()
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `fabbisogni_${moment().format('YYYYMMDD_HHmmss')}.xlsx`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    window.URL.revokeObjectURL(url)
  } catch (err) {
    console.error('Export fabbisogni error:', err)
  } finally {
    exportFabbLoading.value = false
  }
}

const resetAll = () => {
  lottoSingle.value = ''
  lottoList.value = []
  ordineSingle.value = ''
  ordineList.value = []
  tipoLottoFilter.value = 'tutti'
  page.value = 1
  serverItems.value = []
  totalItems.value = 0
  infoMessage.value = ''
}

const headers = computed(() => [
  { title: 'Lotto Componente (MP)', key: 'LottoComponente', sortable: true },
  { title: 'Lotto Produzione (Bobina)', key: 'LottoProduzione', sortable: true },
  { title: 'Ordine (NRigaOrd)', key: 'NRigaOrd', sortable: true },
  { title: 'Codice Prodotto', key: 'NomeProdotto', sortable: true },
  { title: 'Descrizione Prodotto', key: 'DescrizioneProdotto', sortable: true },
  { title: 'Data Inizio', key: 'DataOraInizio', sortable: true },
  { title: 'Data Fine', key: 'DataOraFine', sortable: true },
])

const formatDate = (dateStr?: string) => {
  if (!dateStr) return '-'
  const m = moment(dateStr)
  return m.isValid() ? m.format('DD/MM/YYYY HH:mm') : dateStr
}
</script>

<template>
  <VRow>
    <VCol cols="12">
      <!-- 👉 Sezione Filtri Riprogettata e Ordinata -->
      <VCard class="mb-6">
        <!-- Header con scelta Modalità -->
        <VCardItem class="pb-3 border-b">
          <div class="d-flex align-center justify-space-between flex-wrap gap-4">
            <div>
              <VCardTitle class="text-h6 font-weight-bold">
                Tracciabilità GP
              </VCardTitle>
              <VCardSubtitle class="text-caption">
                Ricerca incrociata tra fabbisogni, lotti e produzione
              </VCardSubtitle>
            </div>

            <!-- Selettore Modalità: Lotto vs Ordine -->
            <VBtnToggle
              v-model="searchMode"
              mandatory
              color="primary"
              variant="outlined"
              density="comfortable"
            >
              <VBtn value="lotto" prepend-icon="tabler-barcode">
                Ricerca per Lotto
              </VBtn>
              <VBtn value="ordine" prepend-icon="tabler-clipboard-list">
                Ricerca per Ordine
              </VBtn>
            </VBtnToggle>
          </div>
        </VCardItem>

        <VCardText class="pt-5">
          <!-- 👉 CASO 1: MODALITÀ LOTTO -->
          <div v-if="searchMode === 'lotto'">
            <!-- Se è caricata una lista multipla (>1 lotti) -->
            <div
              v-if="lottoList.length > 0"
              class="d-flex align-center justify-space-between flex-wrap gap-4 p-3 rounded border mb-4 bg-var-theme-background"
            >
              <div class="d-flex align-center gap-3">
                <VAvatar color="primary" variant="tonal" size="42">
                  <VIcon icon="tabler-list-check" size="24" />
                </VAvatar>
                <div>
                  <div class="d-flex align-center gap-2">
                    <span class="text-subtitle-1 font-weight-bold">
                      Lista Lotti Caricata:
                    </span>
                    <VChip color="primary" size="small" variant="flat">
                      {{ lottoList.length }} lotti unici
                    </VChip>
                  </div>
                  <div class="text-caption text-medium-emphasis">
                    Anteprima: {{ lottoList.slice(0, 4).join(', ') }}
                    <span v-if="lottoList.length > 4" class="font-weight-medium">
                      (+ altri {{ lottoList.length - 4 }})
                    </span>
                  </div>
                </div>
              </div>

              <div class="d-flex align-center gap-2">
                <VBtn
                  variant="outlined"
                  size="small"
                  color="primary"
                  prepend-icon="tabler-edit"
                  @click="openPasteDialog('lotto')"
                >
                  Modifica Lista
                </VBtn>
                <VBtn
                  variant="text"
                  size="small"
                  color="error"
                  prepend-icon="tabler-trash"
                  @click="clearLottoList"
                >
                  Svuota
                </VBtn>
              </div>
            </div>

            <!-- Se NON c'è lista multipla: Input compatto a riga singola + pulsante Incolla Excel -->
            <VRow v-else align="center" class="mb-2">
              <VCol cols="12" md="6" sm="7">
                <AppTextField
                  v-model="lottoSingle"
                  label="Codice Lotto"
                  placeholder="Es. SYE1031760 (o incolla più lotti)"
                  clearable
                  prepend-inner-icon="tabler-barcode"
                  @update:model-value="handleSingleLottoInput"
                  @keyup.enter="loadItems"
                />
              </VCol>

              <VCol cols="12" md="3" sm="5">
                <VBtn
                  variant="tonal"
                  color="primary"
                  block
                  prepend-icon="tabler-clipboard-data"
                  class="mt-md-5"
                  @click="openPasteDialog('lotto')"
                >
                  Incolla Lista da Excel
                </VBtn>
              </VCol>

              <VCol cols="12" md="3">
                <AppSelect
                  v-model="tipoLottoFilter"
                  :items="tipoLottoOptions"
                  item-title="title"
                  item-value="value"
                  label="Ambito Lotto"
                />
              </VCol>
            </VRow>
          </div>

          <!-- 👉 CASO 2: MODALITÀ ORDINE -->
          <div v-else>
            <!-- Se è caricata una lista multipla (>1 ordini) -->
            <div
              v-if="ordineList.length > 0"
              class="d-flex align-center justify-space-between flex-wrap gap-4 p-3 rounded border mb-4 bg-var-theme-background"
            >
              <div class="d-flex align-center gap-3">
                <VAvatar color="info" variant="tonal" size="42">
                  <VIcon icon="tabler-clipboard-check" size="24" />
                </VAvatar>
                <div>
                  <div class="d-flex align-center gap-2">
                    <span class="text-subtitle-1 font-weight-bold">
                      Lista Ordini Caricata:
                    </span>
                    <VChip color="info" size="small" variant="flat">
                      {{ ordineList.length }} ordini unici
                    </VChip>
                  </div>
                  <div class="text-caption text-medium-emphasis">
                    Anteprima: {{ ordineList.slice(0, 4).join(', ') }}
                    <span v-if="ordineList.length > 4" class="font-weight-medium">
                      (+ altri {{ ordineList.length - 4 }})
                    </span>
                  </div>
                </div>
              </div>

              <div class="d-flex align-center gap-2">
                <VBtn
                  variant="outlined"
                  size="small"
                  color="info"
                  prepend-icon="tabler-edit"
                  @click="openPasteDialog('ordine')"
                >
                  Modifica Lista
                </VBtn>
                <VBtn
                  variant="text"
                  size="small"
                  color="error"
                  prepend-icon="tabler-trash"
                  @click="clearOrdineList"
                >
                  Svuota
                </VBtn>
              </div>
            </div>

            <!-- Se NON c'è lista multipla: Input compatto a riga singola + pulsante Incolla Excel -->
            <VRow v-else align="center" class="mb-2">
              <VCol cols="12" md="8" sm="7">
                <AppTextField
                  v-model="ordineSingle"
                  label="Numero Ordine"
                  placeholder="Es. 00004567 o 4567"
                  clearable
                  prepend-inner-icon="tabler-clipboard-list"
                  @update:model-value="handleSingleOrdineInput"
                  @keyup.enter="loadItems"
                />
              </VCol>

              <VCol cols="12" md="4" sm="5">
                <VBtn
                  variant="tonal"
                  color="info"
                  block
                  prepend-icon="tabler-clipboard-data"
                  class="mt-md-5"
                  @click="openPasteDialog('ordine')"
                >
                  Incolla Lista da Excel
                </VBtn>
              </VCol>
            </VRow>
          </div>

          <!-- 👉 Barra Azioni: Cerca & Reset -->
          <div class="d-flex align-center justify-end gap-3 mt-3 pt-3 border-t">
            <VBtn
              variant="tonal"
              color="secondary"
              prepend-icon="tabler-rotate"
              @click="resetAll"
            >
              Reimposta
            </VBtn>

            <VBtn
              color="primary"
              prepend-icon="tabler-search"
              :loading="loading"
              :disabled="!hasFilters"
              @click="loadItems"
            >
              Cerca
            </VBtn>
          </div>
        </VCardText>
      </VCard>

      <!-- 👉 Card Risultati con Datatable & Export -->
      <VCard>
        <VCardItem class="pb-2">
          <div class="d-flex align-center justify-space-between flex-wrap gap-4">
            <div>
              <VCardTitle class="text-h6">
                Risultati Tracciabilità
              </VCardTitle>
              <VCardSubtitle v-if="totalItems > 0" class="text-caption text-success font-weight-medium">
                Trovati {{ totalItems }} record corrispondenti
              </VCardSubtitle>
            </div>

            <div class="d-flex align-center gap-2">
              <!-- Pulsante Esporta Fabbisogni (solo modalità ordine) -->
              <VBtn
                v-if="searchMode === 'ordine'"
                variant="tonal"
                color="info"
                prepend-icon="tabler-package-export"
                :disabled="activeOrdini.length === 0"
                :loading="exportFabbLoading"
                @click="downloadFabbisogni"
              >
                Esporta Fabbisogni
              </VBtn>

              <!-- Pulsante Esporta in Excel con Download Diretto -->
              <VBtn
                variant="tonal"
                color="success"
                prepend-icon="tabler-file-spreadsheet"
                :disabled="!hasFilters || totalItems === 0"
                :loading="exportLoading"
                @click="downloadExcel"
              >
                Esporta in Excel ({{ totalItems }})
              </VBtn>
            </div>
          </div>
        </VCardItem>

        <VCardText v-if="!hasFilters && serverItems.length === 0" class="py-6">
          <VAlert
            type="info"
            variant="tonal"
            icon="tabler-info-circle"
            title="Nessun filtro inserito"
            text="Seleziona la modalità e inserisci un lotto o ordine, oppure clicca 'Incolla Lista da Excel' per cercare elenchi estesi."
          />
        </VCardText>

        <VCardText v-else-if="infoMessage && serverItems.length === 0" class="py-6">
          <VAlert
            type="warning"
            variant="tonal"
            icon="tabler-alert-circle"
            :text="infoMessage"
          />
        </VCardText>

        <!-- 👉 Datatable Server -->
        <VDataTableServer
          v-if="hasFilters"
          v-model:items-per-page="itemsPerPage"
          :headers="headers"
          :items="serverItems"
          :items-length="totalItems"
          :loading="loading"
          @update:options="updateOptions"
        >
          <!-- Lotto Componente -->
          <template #item.LottoComponente="{ item }">
            <VChip
              v-if="item.LottoComponente"
              color="primary"
              size="small"
              variant="tonal"
            >
              {{ item.LottoComponente }}
            </VChip>
            <span v-else class="text-disabled">-</span>
          </template>

          <!-- Lotto Produzione -->
          <template #item.LottoProduzione="{ item }">
            <VChip
              v-if="item.LottoProduzione"
              color="info"
              size="small"
              variant="tonal"
            >
              {{ item.LottoProduzione }}
            </VChip>
            <span v-else class="text-disabled">-</span>
          </template>

          <!-- Ordine -->
          <template #item.NRigaOrd="{ item }">
            <span class="font-weight-medium">{{ item.NRigaOrd }}</span>
          </template>

          <!-- Prodotto -->
          <template #item.NomeProdotto="{ item }">
            <span class="font-weight-medium">{{ item.NomeProdotto }}</span>
          </template>

          <!-- Data Ora Inizio -->
          <template #item.DataOraInizio="{ item }">
            <span>{{ formatDate(item.DataOraInizio) }}</span>
          </template>

          <!-- Data Ora Fine -->
          <template #item.DataOraFine="{ item }">
            <span>{{ formatDate(item.DataOraFine) }}</span>
          </template>
        </VDataTableServer>
      </VCard>

      <!-- 👉 DIALOG MODALE DEDICATO: Incolla Lista da Excel -->
      <VDialog
        v-model="isListDialogOpen"
        max-width="650"
      >
        <VCard>
          <VCardItem class="pb-2">
            <div class="d-flex align-center gap-2">
              <VIcon
                :icon="dialogTarget === 'lotto' ? 'tabler-barcode' : 'tabler-clipboard-list'"
                size="26"
                color="primary"
              />
              <div>
                <VCardTitle class="text-h6">
                  Incolla Lista {{ dialogTarget === 'lotto' ? 'Lotti' : 'Ordini' }} da Excel
                </VCardTitle>
                <VCardSubtitle class="text-caption">
                  Copia una colonna da Excel e incollala direttamente qui sotto.
                </VCardSubtitle>
              </div>
            </div>
          </VCardItem>

          <VCardText class="pt-3">
            <AppTextarea
              v-model="dialogRawText"
              :placeholder="`Incolla qui la colonna da Excel...\nEsempio:\n${dialogTarget === 'lotto' ? 'SYE1080609\nSYE1086258\nSAE2187739' : '00004567\n00004568\n00004569'}`"
              rows="9"
              autofocus
            />

            <!-- Box Statistiche Live -->
            <div class="d-flex align-center justify-space-between flex-wrap gap-2 mt-3 p-2 rounded bg-var-theme-background border">
              <div class="d-flex align-center gap-2 flex-wrap">
                <VChip size="small" color="primary" variant="flat">
                  Rilevati: {{ dialogParsed.raw }}
                </VChip>
                <VChip size="small" color="success" variant="flat">
                  Unici: {{ dialogParsed.unique }}
                </VChip>
                <VChip
                  v-if="dialogParsed.duplicates > 0"
                  size="small"
                  color="warning"
                  variant="flat"
                >
                  Duplicati rimossi: {{ dialogParsed.duplicates }}
                </VChip>
              </div>

              <VBtn
                v-if="dialogRawText"
                variant="text"
                size="x-small"
                color="secondary"
                @click="dialogRawText = ''"
              >
                Cancella testo
              </VBtn>
            </div>
          </VCardText>

          <VCardActions class="px-6 pb-4">
            <VSpacer />
            <VBtn
              variant="tonal"
              color="secondary"
              @click="isListDialogOpen = false"
            >
              Annulla
            </VBtn>
            <VBtn
              color="primary"
              variant="elevated"
              :disabled="dialogParsed.unique === 0"
              @click="applyDialogList"
            >
              Applica Lista ({{ dialogParsed.unique }})
            </VBtn>
          </VCardActions>
        </VCard>
      </VDialog>
    </VCol>
  </VRow>
</template>
