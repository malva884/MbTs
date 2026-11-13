<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { useI18n } from 'vue-i18n'
import type { VForm } from 'vuetify/components/VForm'
import moment from 'moment/moment'

import { can } from '@layouts/plugins/casl'
import DefineAbilities from '@/plugins/casl/DefineAbilities'
import ReportFatturato from '@/components/dialogs/ReportFatturato.vue'

definePage({
  meta: {
    action: 'read',
    subject: 'Finanze-Spedito',
  },
})

const { t } = useI18n()
const currentYear = new Date().getFullYear()
const itemsPerPage = ref(10)
const loading = ref(true)
const refForm = ref<VForm>()
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const materialeFilter = ref('')
const dataFilter = ref(`${currentYear}-01-01 to ${currentYear}-12-31`)
const tipologiaCavoFilter = ref([])
const clientiFilter = ref([])
const page = ref(1)
const serverItems = ref<any>([])
const isSnackbarScrollReverseVisible = ref(false)
const message = ref('')
const color = ref('')
const editDialog = ref(false)
const isLoading = ref(false)
const targetCc = ref('')
const targetOfc = ref('')
const targetFkm = ref('')
const file = ref(null)
const data = ref({})
const fileName = computed(() => file.value?.name)
const fileExtension = computed(() => fileName.value?.substr(fileName.value?.lastIndexOf('.') + 1))
const fileMimeType = computed(() => file.value?.type)
const selectedHeaders = ref()
const headersTemp = ref([])
const reportVisibile = ref(false)
const clientiOptions = ref([])
const temp = []

// Lista materiali incollata da Excel
const materialeList = ref<string[]>([])
const isListDialogOpen = ref(false)
const dialogRawText = ref('')

// Parsing helper: divide su spazi, virgole, punto e virgola, a capo (tipico incolla-colonna Excel)
const extractCodes = (raw: string): string[] => {
  if (!raw || !raw.trim())
    return []

  const items = raw.trim().split(/[\s,;\r\n\t]+/).filter(Boolean).map(s => s.trim())

  return Array.from(new Set(items))
}

// Parametro 'materiale' inviato al backend (il backend usa WhereIn quando trova ';')
const materialeParam = computed(() =>
  materialeList.value.length > 0 ? materialeList.value.join(';') : materialeFilter.value,
)

// Statistiche live nel Dialog
const dialogParsed = computed(() => {
  if (!dialogRawText.value || !dialogRawText.value.trim())
    return { raw: 0, unique: 0, duplicates: 0, items: [] as string[] }

  const rawItems = dialogRawText.value.trim().split(/[\s,;\r\n\t]+/).filter(Boolean).map(s => s.trim())
  const uniqueItems = Array.from(new Set(rawItems))

  return {
    raw: rawItems.length,
    unique: uniqueItems.length,
    duplicates: Math.max(0, rawItems.length - uniqueItems.length),
    items: uniqueItems,
  }
})

const openPasteDialog = () => {
  dialogRawText.value = materialeList.value.length > 0 ? materialeList.value.join('\n') : materialeFilter.value
  isListDialogOpen.value = true
}

const applyDialogList = () => {
  const codes = dialogParsed.value.items

  if (codes.length > 1) {
    materialeList.value = codes
    materialeFilter.value = ''
  }
  else {
    materialeList.value = []
    materialeFilter.value = codes[0] || ''
  }
  isListDialogOpen.value = false
  page.value = 1

  // eslint-disable-next-line @typescript-eslint/no-use-before-define
  loadItems()
}

const clearMaterialeList = () => {
  materialeList.value = []
  materialeFilter.value = ''
  page.value = 1

  // eslint-disable-next-line @typescript-eslint/no-use-before-define
  loadItems()
}

// Incolla rapido nel campo singolo: se contiene piu codici diventa lista
const handleSingleMaterialeInput = (val: string) => {
  const parsed = extractCodes(val)

  if (parsed.length > 1) {
    materialeList.value = parsed
    materialeFilter.value = ''
    page.value = 1

    // eslint-disable-next-line @typescript-eslint/no-use-before-define
    loadItems()
  }
}

const updateOptions = (options: any) => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
  page.value = options.page
  itemsPerPage.value = options.itemsPerPage

  // eslint-disable-next-line @typescript-eslint/no-use-before-define
  loadItems()
}

const loadItems = async () => {
  loading.value = true

  // eslint-disable-next-line no-template-curly-in-string
  const { data: resultData, error } = await useApi<any>(createUrl('/fi/turnover/rows/list', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      materiale: materialeParam.value,
      data: dataFilter.value,
      tipologiaCavo: tipologiaCavoFilter.value,
      clienti: (temp !== '' ? [temp] : ''),
    },
  }))

  if (resultData.value !== null) {
    serverItems.value = resultData.value.data
    totalItems.value = resultData.value.total
  }
  else {
    serverItems.value = []
    totalItems.value = 0
  }
  loading.value = false
}

const reloadItems = () => {
  temp.length = 0
  clientiFilter.value.forEach(value => {
    temp.push(value.id)
  })
  loadItems()
}

const clienti = async () => {
  const { data: clientiResult } = await useApi<any>(createUrl('/fi/get_clienti'))
  const arr = []

  clientiResult.value.forEach(value => {
    arr.push({ val: value.cliente, id: value.codice_cliente })
  })
  clientiOptions.value = arr
}

clienti()

// headers
const headers = computed(() => [
  { title: t('Table.Data'), key: 'data_documento' },
  { title: t('Table.Quantita'), key: 'quantita', align: 'end' },
  { title: t('Table.Kfkm'), key: 'fkm', align: 'end' },
  { title: t('Table.Ckm'), key: 'ckm', align: 'end' },
  { title: t('Table.Um'), key: 'unit' },
  { title: t('Table.Materiale'), key: 'materiale' },
  { title: t('Table.Descrizione'), key: 'descrizione' },
  { title: t('Table.Amount'), key: 'importo_valuta_locale', align: 'end' },
  { title: t('Table.Numero-Documento'), key: 'documento_numero' },
  { title: t('Table.Cliente'), key: 'cliente' },
  { title: t('Table.Tipologia-Cavo'), key: 'tipologia_cavo' },
  { title: t('Table.Tipo-Documento'), key: 'documento_tipo' },
  { title: t('Table.Data-Publicazione'), key: 'data_publicazione' },
  { title: t('Table.Posting Date'), key: 'chiave_publicazione' },
  { title: t('Table.Valuta Locale'), key: 'valuta_locale' },
  { title: t('Table.Tax-Code'), key: 'tax_code' },
  { title: t('Table.Account-Tipo'), key: 'account_tipo' },
  { title: t('Table.Codice-Cliente'), key: 'codice_cliente' },

])

selectedHeaders.value = headers.value.map(h => h.key)
headersTemp.value = headers.value

const resolveLavorazione = (lavorazione: string) => {
  if (lavorazione === '2')
    return { color: 'warning', text: 'Ottico' }
  else if (lavorazione === '1')
    return { color: 'success', text: 'Rame' }
  else
    return { color: 'primary', text: 'Ottivo/Rame' }
}

const save = async () => {
  const retuenData = await $api('fi/import', {
    method: 'POST',
    body: {
      targhetCc: targetCc.value,
      targhetOfc: targetOfc.value,
      targhetKfm: targetFkm.value,
      file_upload: data.value,
    },
  })
}

const uploadFile = (event: any) => {
  file.value = event.target.files[0]

  const reader = new FileReader()

  reader.readAsDataURL(file.value)
  reader.onload = async () => {
    const encodedFile = reader.result.split(',')[1]

    data.value = {
      file: encodedFile,
      fileName: fileName.value,
      fileExtension: fileExtension.value,
      fileMimeType: fileMimeType.value,
    }
  }
}

const newItem = () => {
  editDialog.value = true
}

const close = () => {
  isLoading.value = false
  editDialog.value = false
  refForm.value?.reset()
}

function formatDate(date: string): string {
  return moment(String(date)).format('YYYY - MMMM')
}

const euro = new Intl.NumberFormat('it-IT', {
  style: 'currency',
  currency: 'EUR',
})

const openReprot = async () => {
  reportVisibile.value = true
}

const closeReprot = async () => {
  reportVisibile.value = true
}

const test = async () => {
  headersTemp.value = []
  headers.value.forEach(element => {
    if (selectedHeaders.value.includes(element.key))
      headersTemp.value.push(element)
  })
  loadItems()
}

const exportUrl = computed(() => {
  const temp: any[] = []
  clientiFilter.value.forEach((value: any) => {
    temp.push(value.id)
  })

  const params = new URLSearchParams({
    materiale: materialeParam.value,
    data: dataFilter.value,
    tipologiaCavo: tipologiaCavoFilter.value,
    clienti: JSON.stringify(temp),
    sortBy: sortBy.value || 'data_documento',
    orderBy: orderBy.value || 'desc',
  })

  return `/api/fi/turnover/rows/export_report?${params.toString()}`
})

const exportExcel = async () => {
  const accessToken = useCookie('accessToken').value
  const baseUrl = import.meta.env.VITE_API_BASE_URL || '/api'

  const response = await fetch(exportUrl.value, {
    headers: {
      Authorization: `Bearer ${accessToken}`,
      Accept: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    },
  })

  if (!response.ok) {
    isSnackbarScrollReverseVisible.value = true
    message.value = 'Errore esportazione'
    color.value = 'error'
    return
  }

  const blob = await response.blob()
  const link = document.createElement('a')
  link.href = URL.createObjectURL(blob)
  link.download = `fatturato_report_export_${new Date().toISOString().slice(0, 10)}.xlsx`
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  URL.revokeObjectURL(link.href)
}
</script>

<template>
  <div class="workspace-container w-100 d-flex flex-column pa-4 gap-3">
    <VSnackbar v-model="isSnackbarScrollReverseVisible" transition="scroll-y-reverse-transition" location="top center" :timeout="3000">
      {{ $t(message) }}
    </VSnackbar>

    <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
      <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
        <div class="d-flex align-center gap-2">
          <VIcon icon="tabler-currency-euro" size="24" color="primary" />
          <div>
            <div class="text-h6 font-weight-medium">Report Fatturato</div>
            <div class="text-caption text-medium-emphasis">{{ totalItems }} righe</div>
          </div>
        </div>
        <div class="d-flex align-center gap-2">
          <VBtn
            prepend-icon="tabler-file-export"
            color="secondary"
            variant="outlined"
            density="comfortable"
            class="px-3 me-1"
            @click="exportExcel"
          >
            {{$t('Button.Export')}}
          </VBtn>
          <VBtn
            v-if="can(DefineAbilities.rp_finance_fatturato_report.action, DefineAbilities.rp_finance_fatturato_report.subject)"
            prepend-icon="tabler-report"
            color="primary"
            variant="flat"
            density="comfortable"
            class="px-3"
            @click="openReprot"
          >
            {{$t('label.Apri-Report')}}
          </VBtn>
        </div>
      </VCardText>
      <VDivider />
      <VCardText class="pa-3">
        <VRow class="mb-2">
          <!-- 👉 Materiale -->
          <VCol cols="12" sm="3">
            <!-- Lista materiali caricata da Excel -->
            <div
              v-if="materialeList.length > 0"
              class="d-flex align-center justify-space-between gap-2 rounded border px-3"
              style="min-block-size: 56px;"
            >
              <div class="d-flex align-center gap-2 overflow-hidden">
                <VIcon icon="tabler-list-check" size="20" color="primary" />
                <div class="overflow-hidden">
                  <div class="d-flex align-center gap-2">
                    <span class="text-body-2 font-weight-medium">Lista materiali</span>
                    <VChip color="primary" size="x-small" variant="flat">
                      {{ materialeList.length }}
                    </VChip>
                  </div>
                  <div class="text-caption text-medium-emphasis text-truncate">
                    {{ materialeList.slice(0, 3).join(', ') }}
                    <span v-if="materialeList.length > 3">(+{{ materialeList.length - 3 }})</span>
                  </div>
                </div>
              </div>
              <div class="d-flex align-center">
                <VBtn
                  icon="tabler-edit"
                  variant="text"
                  size="x-small"
                  color="primary"
                  @click="openPasteDialog"
                />
                <VBtn
                  icon="tabler-x"
                  variant="text"
                  size="x-small"
                  color="error"
                  @click="clearMaterialeList"
                />
              </div>
            </div>

            <!-- Input singolo + incolla da Excel -->
            <AppTextField
              v-else
              v-model="materialeFilter"
              :label="$t('Label.Materiale')"
              placeholder="Cerca materiale"
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-search"
              @update:model-value="handleSingleMaterialeInput"
              @keyup.enter="loadItems"
              @click:clear="loadItems"
            >
              <template #append-inner>
                <VTooltip location="top" text="Incolla lista da Excel">
                  <template #activator="{ props }">
                    <VIcon
                      v-bind="props"
                      icon="tabler-clipboard-data"
                      class="cursor-pointer"
                      @click="openPasteDialog"
                    />
                  </template>
                </VTooltip>
              </template>
            </AppTextField>
          </VCol>

          <!-- 👉 Clienti -->
          <VCol cols="12" sm="3">
            <AppCombobox
              v-model="clientiFilter"
              :label="$t('Label.Clienti')"
              placeholder="Tutti"
              :items="clientiOptions"
              :item-title="item => item.val"
              :item-value="item => item.id"
              chips
              multiple
              eager
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-filter"
              @update:model-value="reloadItems"
              @click:clear="reloadItems"
            />
          </VCol>

          <!-- 👉 tipologia Cavo -->
          <VCol cols="12" sm="2">
            <AppSelect
              v-model="tipologiaCavoFilter"
              :label="$t('Label.Tipologia-Cavo')"
              placeholder="Tutte"
              :items="[{ title: 'Rame', value: 5441 }, { title: 'Ottico', value: 5420 }]"
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-filter"
              @update:model-value="loadItems"
              @click:clear="loadItems"
            />
          </VCol>

          <!-- 👉 Data -->
          <VCol cols="12" sm="2">
            <AppDateTimePicker
              v-model="dataFilter"
              :label="$t('Label.Data')"
              placeholder="Seleziona data"
              :config="{ mode: 'range' }"
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-calendar"
              @update:model-value="loadItems"
              @click:clear="loadItems"
            />
          </VCol>

          <!-- 👉 Colonne -->
          <VCol cols="12" sm="2">
            <AppSelect
              v-model="selectedHeaders"
              :label="$t('Label.Colonne')"
              :items="headers"
              :item-title="item => item.title"
              :item-value="item => item.key"
              chips
              multiple
              eager
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-columns"
              @update:model-value="test"
              @click:clear="test"
            />
          </VCol>
        </VRow>
      </VCardText>
      <VDivider />

      <!-- 👉 Datatable  -->
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        :headers="headersTemp"
        :items="serverItems"
        :items-length="totalItems"
        :loading="loading"
        height="600"
        fixed-header
        density="comfortable"
        hover
        @update:options="updateOptions"
      >
        <template #no-data>
          <div class="py-10 text-center">
            <VIcon icon="tabler-currency-euro" size="40" class="text-disabled mb-2" />
            <p class="text-body-1 text-disabled mb-0">Nessuna riga trovata</p>
          </div>
        </template>
        <template #item.importo_valuta_locale="{ item }">
          <p class="text-success">
            {{ euro.format(item.importo_valuta_locale) }}
          </p>
        </template>

        <template #item.quantita="{ item }">
          <p
            v-if="item.quantita === '.000'"
            class=""
          >
            0
          </p>
          <p
            v-else
            class=""
          >
            {{ item.quantita }}
          </p>
        </template>

        <template #item.fkm="{ item }">
          <p class="text-info">
            {{ item.fkm ? Number(item.fkm).toFixed(3) : '0.000' }}
          </p>
        </template>

        <template #item.ckm="{ item }">
          <p class="text-info">
            {{ item.ckm ? Number(item.ckm).toFixed(3) : '0.000' }}
          </p>
        </template>
      </VDataTableServer>
    </VCard>
  </div>
  <ReportFatturato
    v-model:isDialogVisible="reportVisibile"
    :data-filter-data="dataFilter"
    :materiale-filter-data="materialeParam"
    :tipologia-cavo-filter-data="tipologiaCavoFilter"
    :clienti-filter-data="clientiFilter"
  />

  <!-- 👉 DIALOG: Incolla Lista Materiali da Excel -->
  <VDialog
    v-model="isListDialogOpen"
    max-width="650"
  >
    <VCard>
      <VCardItem class="pb-2">
        <div class="d-flex align-center gap-2">
          <VIcon
            icon="tabler-clipboard-data"
            size="26"
            color="primary"
          />
          <div>
            <VCardTitle class="text-h6">
              Incolla Lista Materiali da Excel
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
          placeholder="Incolla qui la colonna da Excel...
Esempio:
FCM4S1C0144FGA1037
FCM4S1C0072FGA1037
FCM4S1C0096FGA1037"
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
</template>
