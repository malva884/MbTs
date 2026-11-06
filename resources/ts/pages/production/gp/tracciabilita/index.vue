<script setup lang="ts">
import { VDataTable, VDataTableServer } from 'vuetify/labs/VDataTable'
import moment from 'moment'
import { useI18n } from 'vue-i18n'

definePage({
  meta: {
    action: 'report',
    subject: 'Produzione-Interscambio',
  },
})

const { t } = useI18n()

// Modalità di ricerca: 'lotto' | 'ordine' | 'velocita' | 'cavo' | 'confronto'
const searchMode = ref<'lotto' | 'ordine' | 'velocita' | 'cavo' | 'confronto'>('lotto')

// Filtri per lotto
const lottoSingle = ref('')
const lottoList = ref<string[]>([])
const tipoLottoFilter = ref<string>('tutti')

// Filtri per ordine
const ordineSingle = ref('')
const ordineList = ref<string[]>([])

// Filtro macchina (modalità velocita, cavo e confronto)
const macchinaFilter = ref<string | null>(null)
const macchineOptions = ref<string[]>([])

// Filtri per cavo (modalità cavo): codice prodotto parziale + periodo obbligatorio
const cavoInput = ref('')
const periodoCavo = ref('')

// Confronto periodi
const periodoA = ref('')
const periodoB = ref('')
const confrontoData = ref<any | null>(null)

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
const exportVelocLoading = ref(false)
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
  if (searchMode.value === 'confronto') {
    return !!macchinaFilter.value && !!periodoA.value && !!periodoB.value
  }
  if (searchMode.value === 'cavo') {
    return !!cavoInput.value.trim() && !!periodoCavo.value
  }
  // 'ordine' e 'velocita' condividono la stessa lista OL
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

// Cambio modalità: svuota i risultati e ricarica se ci sono già filtri attivi
watch(searchMode, () => {
  serverItems.value = []
  totalItems.value = 0
  confrontoData.value = null
  page.value = 1
  sortBy.value = undefined
  orderBy.value = undefined
  if (hasFilters.value) {
    loadItems()
  }
})

const updateOptions = (options: any) => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
  page.value = options.page
  itemsPerPage.value = options.itemsPerPage

  loadItems()
}

// Confronto velocità media macchina tra due periodi
const loadConfronto = async () => {
  if (!hasFilters.value) {
    confrontoData.value = null
    infoMessage.value = 'Seleziona la macchina e entrambi i periodi per avviare il confronto.'
    return
  }

  infoMessage.value = ''
  loading.value = true
  confrontoData.value = null

  try {
    const { data } = await useApi<any>(createUrl('/gp/tracciabilita/confronto-velocita', {
      query: {
        macchina: macchinaFilter.value || undefined,
        periodo_a: periodoA.value || undefined,
        periodo_b: periodoB.value || undefined,
      },
    }))

    if (data.value?.success) {
      confrontoData.value = data.value
      const totRecords = (data.value.periodo_a?.records?.length || 0) + (data.value.periodo_b?.records?.length || 0)
      if (totRecords === 0) {
        infoMessage.value = 'Nessuna lavorazione trovata nei periodi selezionati.'
      }
    } else {
      infoMessage.value = data.value?.message || 'Errore durante il confronto.'
    }
  } catch (err) {
    console.error('Confronto error:', err)
    infoMessage.value = 'Errore durante il caricamento del confronto.'
  } finally {
    loading.value = false
  }
}

// Caricamento dati via POST / GET sicuro
const loadItems = async () => {
  if (searchMode.value === 'confronto') {
    await loadConfronto()
    return
  }

  if (!hasFilters.value) {
    serverItems.value = []
    totalItems.value = 0
    infoMessage.value = 'Inserisci un codice o incolla una lista da Excel per avviare la ricerca.'
    return
  }

  infoMessage.value = ''
  loading.value = true

  const lottiParam = searchMode.value === 'lotto' && activeLotti.value.length > 0 ? activeLotti.value.join(',') : undefined
  const ordiniParam = (searchMode.value === 'ordine' || searchMode.value === 'velocita') && activeOrdini.value.length > 0 ? activeOrdini.value.join(',') : undefined

  try {
    const endpoint = searchMode.value === 'velocita'
      ? '/gp/tracciabilita/velocita'
      : (searchMode.value === 'cavo' ? '/gp/tracciabilita/velocita-cavo' : '/gp/tracciabilita')
    const { data: resultData } = await useApi<any>(createUrl(endpoint, {
      query: {
        page: page.value,
        itemsPerPage: itemsPerPage.value,
        sortBy: sortBy.value,
        orderBy: orderBy.value,
        lotto: lottiParam,
        ordine: ordiniParam,
        cavo: searchMode.value === 'cavo' ? cavoInput.value.trim() : undefined,
        periodo: searchMode.value === 'cavo' ? periodoCavo.value : undefined,
        macchina: (searchMode.value === 'velocita' || searchMode.value === 'cavo') && macchinaFilter.value ? macchinaFilter.value : undefined,
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

// Esportazione velocità media macchina per lista ordini (STL_DatiMacchina_V)
const downloadVelocita = async () => {
  if (searchMode.value !== 'velocita' || activeOrdini.value.length === 0) return

  exportVelocLoading.value = true
  try {
    const response = await fetch('/api/gp/tracciabilita/export-velocita', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/octet-stream',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({
        ordine: activeOrdini.value.join(','),
        macchina: macchinaFilter.value || undefined,
      }),
    })

    if (!response.ok) {
      const body = await response.text()
      console.error('Export velocita failed', response.status, body.slice(0, 500))
      throw new Error(`Errore durante la generazione del file Excel (HTTP ${response.status})`)
    }

    const blob = await response.blob()
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `velocita_ordini_${moment().format('YYYYMMDD_HHmmss')}.xlsx`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    window.URL.revokeObjectURL(url)
  } catch (err) {
    console.error('Export velocita error:', err)
  } finally {
    exportVelocLoading.value = false
  }
}

// Esportazione velocità media filtrata per codice cavo (match parziale su NomeProdotto)
const downloadVelocitaCavo = async () => {
  if (searchMode.value !== 'cavo' || !cavoInput.value.trim() || !periodoCavo.value) return

  exportVelocLoading.value = true
  try {
    const response = await fetch('/api/gp/tracciabilita/export-velocita-cavo', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/octet-stream',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({
        cavo: cavoInput.value.trim(),
        periodo: periodoCavo.value,
        macchina: macchinaFilter.value || undefined,
      }),
    })

    if (!response.ok) {
      const body = await response.text()
      console.error('Export velocita cavo failed', response.status, body.slice(0, 500))
      throw new Error(`Errore durante la generazione del file Excel (HTTP ${response.status})`)
    }

    const blob = await response.blob()
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `velocita_cavo_${moment().format('YYYYMMDD_HHmmss')}.xlsx`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    window.URL.revokeObjectURL(url)
  } catch (err) {
    console.error('Export velocita cavo error:', err)
  } finally {
    exportVelocLoading.value = false
  }
}

const resetAll = () => {
  lottoSingle.value = ''
  lottoList.value = []
  ordineSingle.value = ''
  ordineList.value = []
  macchinaFilter.value = null
  cavoInput.value = ''
  periodoCavo.value = ''
  periodoA.value = ''
  periodoB.value = ''
  confrontoData.value = null
  tipoLottoFilter.value = 'tutti'
  page.value = 1
  serverItems.value = []
  totalItems.value = 0
  infoMessage.value = ''
}

const headers = computed(() => {
  if (searchMode.value === 'velocita' || searchMode.value === 'cavo') {
    return [
      { title: 'Numero Ordine', key: 'NumeroOrdine', sortable: true },
      { title: 'Macchina', key: 'Macchina', sortable: true },
      { title: 'Prodotto', key: 'Prodotto', sortable: true },
      { title: 'Descrizione Prodotto', key: 'DescrizioneProdotto', sortable: true },
      { title: 'Data Inizio', key: 'DataOraInizio', sortable: true },
      { title: 'Data Fine', key: 'DataOraFine', sortable: true },
      { title: 'Velocità Media', key: 'VelocitaMedia', sortable: false },
    ]
  }
  return [
    { title: 'Lotto Componente (MP)', key: 'LottoComponente', sortable: true },
    { title: 'Lotto Produzione (Bobina)', key: 'LottoProduzione', sortable: true },
    { title: 'Ordine (NRigaOrd)', key: 'NRigaOrd', sortable: true },
    { title: 'Codice Prodotto', key: 'NomeProdotto', sortable: true },
    { title: 'Descrizione Prodotto', key: 'DescrizioneProdotto', sortable: true },
    { title: 'Data Inizio', key: 'DataOraInizio', sortable: true },
    { title: 'Data Fine', key: 'DataOraFine', sortable: true },
  ]
})

const formatDate = (dateStr?: string) => {
  if (!dateStr) return '-'
  const m = moment(dateStr)
  return m.isValid() ? m.format('DD/MM/YYYY HH:mm') : dateStr
}

// 👉 Confronto periodi: KPI, delta, righe e grafico
const confrontoKpiA = computed(() => confrontoData.value?.periodo_a?.kpi || null)
const confrontoKpiB = computed(() => confrontoData.value?.periodo_b?.kpi || null)

const confrontoDelta = computed(() => {
  const a = confrontoKpiA.value?.media_pesata
  const b = confrontoKpiB.value?.media_pesata
  if (a == null || b == null || a === 0) return null
  return ((b - a) / a) * 100
})

const confrontoRows = computed<any[]>(() => [
  ...(confrontoData.value?.periodo_a?.records || []),
  ...(confrontoData.value?.periodo_b?.records || []),
])

const confrontoHeaders = [
  { title: 'Periodo', key: 'Periodo' },
  { title: 'Numero Ordine', key: 'NumeroOrdine' },
  { title: 'Prodotto', key: 'Prodotto' },
  { title: 'Data Inizio', key: 'DataOraInizio' },
  { title: 'Data Fine', key: 'DataOraFine' },
  { title: 'Velocità Media', key: 'VelocitaMedia' },
]

const confrontoChartSeries = computed(() => [{
  name: 'Velocità media pesata',
  data: [
    confrontoKpiA.value?.media_pesata ?? 0,
    confrontoKpiB.value?.media_pesata ?? 0,
  ],
}])

const confrontoChartOptions = computed(() => ({
  chart: { type: 'bar' as const, toolbar: { show: false } },
  plotOptions: {
    bar: { columnWidth: '45%', borderRadius: 6, distributed: true },
  },
  dataLabels: {
    enabled: true,
    formatter: (val: number) => (val != null ? Number(val).toFixed(1) : ''),
  },
  legend: { show: false },
  xaxis: { categories: ['Periodo A', 'Periodo B'] },
  yaxis: { title: { text: 'Velocità media pesata' } },
  colors: ['#7367F0', '#28C76F'],
  tooltip: {
    y: { formatter: (val: number) => (val != null ? `${Number(val).toFixed(2)}` : '') },
  },
}))

// Elenco macchine (Risorse.Modello) per il filtro del report velocità
const loadMacchine = async () => {
  try {
    const { data } = await useApi<any>(createUrl('/gp/tracciabilita/velocita-macchine'))
    if (data.value) {
      macchineOptions.value = data.value
    }
  } catch (err) {
    console.error('Errore caricamento macchine:', err)
  }
}

onMounted(() => {
  loadMacchine()
})

const formatVelocita = (v?: number | string | null) => {
  if (v === null || v === undefined || v === '') return '-'
  const n = Number(v)
  return Number.isNaN(n) ? '-' : n.toFixed(2)
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
              <VBtn value="velocita" prepend-icon="tabler-gauge">
                Velocità Macchina
              </VBtn>
              <VBtn value="cavo" prepend-icon="tabler-plug">
                Velocità per Cavo
              </VBtn>
              <VBtn value="confronto" prepend-icon="tabler-chart-bar">
                Confronto Periodi
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

          <!-- 👉 CASO 2: MODALITÀ ORDINE / VELOCITÀ (condividono la lista OL) -->
          <div v-else-if="searchMode === 'ordine' || searchMode === 'velocita'">
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
              <VCol cols="12" :md="searchMode === 'velocita' ? 5 : 8" sm="7">
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

              <VCol v-if="searchMode === 'velocita'" cols="12" md="3" sm="5">
                <AppSelect
                  v-model="macchinaFilter"
                  :items="macchineOptions"
                  label="Macchina"
                  placeholder="Tutte le macchine"
                  clearable
                  prepend-inner-icon="tabler-assembly"
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

          <!-- 👉 CASO 3: MODALITÀ VELOCITÀ PER CAVO -->
          <div v-else-if="searchMode === 'cavo'">
            <VRow align="center" class="mb-2">
              <VCol cols="12" md="4">
                <AppTextField
                  v-model="cavoInput"
                  label="Codice Cavo"
                  placeholder="Es. codice prodotto anche parziale"
                  clearable
                  prepend-inner-icon="tabler-plug"
                  @keyup.enter="loadItems"
                />
              </VCol>

              <VCol cols="12" md="4">
                <AppDateTimePicker
                  v-model="periodoCavo"
                  label="Periodo (obbligatorio)"
                  placeholder="Seleziona intervallo date"
                  :config="{ mode: 'range' }"
                  clearable
                  clear-icon="tabler-x"
                />
              </VCol>

              <VCol cols="12" md="4">
                <AppSelect
                  v-model="macchinaFilter"
                  :items="macchineOptions"
                  label="Macchina"
                  placeholder="Tutte le macchine"
                  clearable
                  prepend-inner-icon="tabler-assembly"
                />
              </VCol>
            </VRow>
          </div>

          <!-- 👉 CASO 4: MODALITÀ CONFRONTO PERIODI -->
          <div v-else-if="searchMode === 'confronto'">
            <VRow align="center" class="mb-2">
              <VCol cols="12" md="4">
                <AppSelect
                  v-model="macchinaFilter"
                  :items="macchineOptions"
                  label="Macchina"
                  placeholder="Seleziona macchina"
                  clearable
                  prepend-inner-icon="tabler-assembly"
                />
              </VCol>

              <VCol cols="12" md="4">
                <AppDateTimePicker
                  v-model="periodoA"
                  label="Periodo A"
                  placeholder="Seleziona intervallo date"
                  :config="{ mode: 'range' }"
                  clearable
                  clear-icon="tabler-x"
                />
              </VCol>

              <VCol cols="12" md="4">
                <AppDateTimePicker
                  v-model="periodoB"
                  label="Periodo B"
                  placeholder="Seleziona intervallo date"
                  :config="{ mode: 'range' }"
                  clearable
                  clear-icon="tabler-x"
                />
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
                {{ searchMode === 'velocita' ? 'Velocità Macchina per Ordine' : (searchMode === 'cavo' ? 'Velocità per Cavo' : (searchMode === 'confronto' ? 'Confronto Velocità tra Periodi' : 'Risultati Tracciabilità')) }}
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

              <!-- Pulsante Esporta Velocità (solo modalità velocita) -->
              <VBtn
                v-if="searchMode === 'velocita'"
                variant="tonal"
                color="info"
                prepend-icon="tabler-file-spreadsheet"
                :disabled="activeOrdini.length === 0"
                :loading="exportVelocLoading"
                @click="downloadVelocita"
              >
                Esporta Velocità
              </VBtn>

              <!-- Pulsante Esporta Velocità per Cavo (solo modalità cavo) -->
              <VBtn
                v-if="searchMode === 'cavo'"
                variant="tonal"
                color="info"
                prepend-icon="tabler-file-spreadsheet"
                :disabled="!hasFilters"
                :loading="exportVelocLoading"
                @click="downloadVelocitaCavo"
              >
                Esporta Velocità
              </VBtn>

              <!-- Pulsante Esporta in Excel con Download Diretto -->
              <VBtn
                v-if="searchMode === 'lotto' || searchMode === 'ordine'"
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

        <!-- 👉 Blocco Confronto Periodi -->
        <template v-if="searchMode === 'confronto'">
          <VProgressLinear
            v-if="loading"
            indeterminate
            color="primary"
          />

          <VCardText v-if="confrontoData">
            <!-- KPI per periodo -->
            <VRow class="mb-4">
              <VCol cols="12" md="4">
                <VCard variant="tonal" color="primary">
                  <VCardText class="text-center py-4">
                    <div class="text-caption text-medium-emphasis mb-1">
                      Periodo A — Media pesata
                    </div>
                    <div class="text-h4 font-weight-bold">
                      {{ confrontoKpiA?.media_pesata != null ? confrontoKpiA.media_pesata.toFixed(2) : '-' }}
                    </div>
                    <div class="text-caption text-medium-emphasis mt-1">
                      {{ confrontoKpiA?.lavorazioni_con_velocita ?? 0 }}/{{ confrontoKpiA?.lavorazioni ?? 0 }} lavorazioni · media semplice {{ confrontoKpiA?.media != null ? confrontoKpiA.media.toFixed(2) : '-' }} · max {{ confrontoKpiA?.max != null ? confrontoKpiA.max.toFixed(2) : '-' }}
                    </div>
                  </VCardText>
                </VCard>
              </VCol>

              <VCol cols="12" md="4">
                <VCard variant="tonal" color="success">
                  <VCardText class="text-center py-4">
                    <div class="text-caption text-medium-emphasis mb-1">
                      Periodo B — Media pesata
                    </div>
                    <div class="text-h4 font-weight-bold">
                      {{ confrontoKpiB?.media_pesata != null ? confrontoKpiB.media_pesata.toFixed(2) : '-' }}
                    </div>
                    <div class="text-caption text-medium-emphasis mt-1">
                      {{ confrontoKpiB?.lavorazioni_con_velocita ?? 0 }}/{{ confrontoKpiB?.lavorazioni ?? 0 }} lavorazioni · media semplice {{ confrontoKpiB?.media != null ? confrontoKpiB.media.toFixed(2) : '-' }} · max {{ confrontoKpiB?.max != null ? confrontoKpiB.max.toFixed(2) : '-' }}
                    </div>
                  </VCardText>
                </VCard>
              </VCol>

              <VCol cols="12" md="4">
                <VCard
                  variant="tonal"
                  :color="confrontoDelta == null ? 'secondary' : (confrontoDelta >= 0 ? 'success' : 'error')"
                >
                  <VCardText class="text-center py-4">
                    <div class="text-caption text-medium-emphasis mb-1">
                      Variazione B vs A
                    </div>
                    <div class="text-h4 font-weight-bold d-flex align-center justify-center gap-2">
                      <VIcon
                        v-if="confrontoDelta != null"
                        :icon="confrontoDelta >= 0 ? 'tabler-trending-up' : 'tabler-trending-down'"
                        size="28"
                      />
                      {{ confrontoDelta != null ? `${confrontoDelta >= 0 ? '+' : ''}${confrontoDelta.toFixed(1)}%` : '-' }}
                    </div>
                    <div class="text-caption text-medium-emphasis mt-1">
                      differenza media pesata tra i due periodi
                    </div>
                  </VCardText>
                </VCard>
              </VCol>
            </VRow>

            <!-- Grafico confronto -->
            <VueApexCharts
              v-if="confrontoKpiA?.media_pesata != null || confrontoKpiB?.media_pesata != null"
              type="bar"
              height="280"
              :options="confrontoChartOptions"
              :series="confrontoChartSeries"
            />

            <!-- Dettaglio lavorazioni -->
            <VDataTable
              :headers="confrontoHeaders"
              :items="confrontoRows"
              :items-per-page="15"
              class="mt-4"
            >
              <template #item.Periodo="{ item }">
                <VChip
                  :color="item.Periodo === 'A' ? 'primary' : 'success'"
                  size="small"
                  variant="flat"
                >
                  {{ item.Periodo }}
                </VChip>
              </template>

              <template #item.DataOraInizio="{ item }">
                <span>{{ formatDate(item.DataOraInizio) }}</span>
              </template>

              <template #item.DataOraFine="{ item }">
                <span>{{ formatDate(item.DataOraFine) }}</span>
              </template>

              <template #item.VelocitaMedia="{ item }">
                <VChip
                  v-if="item.VelocitaMedia !== null && item.VelocitaMedia !== undefined && item.VelocitaMedia !== ''"
                  color="success"
                  size="small"
                  variant="flat"
                >
                  {{ formatVelocita(item.VelocitaMedia) }}
                </VChip>
                <span v-else class="text-disabled">-</span>
              </template>
            </VDataTable>
          </VCardText>
        </template>

        <!-- 👉 Datatable Server -->
        <VDataTableServer
          v-if="hasFilters && searchMode !== 'confronto'"
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

          <!-- 👉 Slot modalità Velocità -->

          <!-- Numero Ordine -->
          <template #item.NumeroOrdine="{ item }">
            <span class="font-weight-medium">{{ item.NumeroOrdine }}</span>
          </template>

          <!-- Macchina -->
          <template #item.Macchina="{ item }">
            <VChip
              v-if="item.Macchina"
              color="secondary"
              size="small"
              variant="tonal"
            >
              {{ item.Macchina }}
            </VChip>
            <span v-else class="text-disabled">-</span>
          </template>

          <!-- Prodotto -->
          <template #item.Prodotto="{ item }">
            <span class="font-weight-medium">{{ item.Prodotto }}</span>
          </template>

          <!-- Velocità Media -->
          <template #item.VelocitaMedia="{ item }">
            <VChip
              v-if="item.VelocitaMedia !== null && item.VelocitaMedia !== undefined && item.VelocitaMedia !== ''"
              color="success"
              size="small"
              variant="flat"
            >
              {{ formatVelocita(item.VelocitaMedia) }}
            </VChip>
            <span v-else class="text-disabled">-</span>
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
