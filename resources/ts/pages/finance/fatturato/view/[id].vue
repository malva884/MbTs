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

// eslint-disable-next-line @typescript-eslint/no-unused-vars
const route = useRoute('finance-fatturato-view-id')

const { t } = useI18n()
const itemsPerPage = ref(10)
const loading = ref(true)
const refForm = ref<VForm>()
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const materialeFilter = ref('')
const dataFilter = ref('')
const tipologiaCavoFilter = ref([])
const clientiFilter = ref([])
const page = ref(1)
const serverItems = ref<any>([])
const isSnackbarScrollReverseVisible = ref(false)
const message = ref('')
const color = ref('')
const reportTargetView = ref(false)
const file = ref(null)
const data = ref({})
const fileName = computed(() => file.value?.name)
const fileExtension = computed(() => fileName.value?.substr(fileName.value?.lastIndexOf('.') + 1))
const fileMimeType = computed(() => file.value?.type)
const selectedHeaders = ref()
let headersTemp = []
const reportVisibile = ref(false)
const clientiOptions = ref([])
const temp = []


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
      materiale: materialeFilter.value,
      data: dataFilter.value,
      tipologiaCavo: tipologiaCavoFilter.value,
      clienti:  (temp !== '' ? [temp]:''),
      id: route.params.id,
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
  clientiFilter.value.forEach(function (value) {
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
  { title: t('Table.Amount'), key: 'importo_valuta_locale', align: 'end' },
  { title: t('Table.Numero-Documento'), key: 'documento_numero' },
  { title: t('Table.Cliente'), key: 'cliente' },
  { title: t('Table.Tipologia-Cavo'), key: 'tipologia_cavo' },
  { title: t('Table.Tipo-Docuemnto'), key: 'documento_tipo' },
  { title: t('Table.Data-Publicazione'), key: 'data_publicazione' },
  { title: t('Table.Posting Date'), key: 'chiave_publicazione' },
  { title: t('Table.Valuta Locale'), key: 'valuta_locale' },
  { title: t('Table.Tax-Code'), key: 'tax_code' },
  { title: t('Table.Account-Tipo'), key: 'account_tipo' },
  { title: t('Table.Codice Chiente'), key: 'codice_cliente' },
  { title: t('Table.Valore Unitario'), key: 'valore_unitario' },
  { title: t('Table.Valore Totale'), key: 'valore_totale' },
  { title: t('Table.Realization'), key: 'realization' },
])

selectedHeaders.value = headers.value
headersTemp = headers.value


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
  headersTemp = []
  headers.forEach(element => {
    if (selectedHeaders.value.includes(element.key))
      headersTemp.push(element)
  })
  loadItems()
}

const targetData = ref()
const targetView = ref(false)

const target = async () => {
  const { data: tergetData } = await useApi<any>(createUrl(`/fi/turnover/get_target/${route.params.id}`))

  targetData.value = tergetData.value
  targetView.value = true
}

target()

const exportData = async () => {
  const accessToken = useCookie('accessToken').value
  const baseUrl = import.meta.env.VITE_API_BASE_URL || '/api'

  const params = new URLSearchParams()
  if (materialeFilter.value) params.append('materiale', materialeFilter.value)
  if (dataFilter.value) params.append('data', dataFilter.value)
  if (tipologiaCavoFilter.value) params.append('tipologiaCavo', tipologiaCavoFilter.value)
  const clientiIds = clientiFilter.value.map((c: any) => c.id)
  if (clientiIds.length) params.append('clienti', JSON.stringify(clientiIds))

  const url = `${baseUrl}/fi/turnover/rows/export/${route.params.id}?${params.toString()}`

  const response = await fetch(url, {
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
  link.download = `fatturato_righe_export_${route.params.id}_${new Date().toISOString().slice(0, 10)}.xlsx`
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
            <div class="text-h6 font-weight-medium">Dettaglio Fatturato</div>
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
            @click="exportData"
          >
            Esporta Excel
          </VBtn>
          <VBtn
            v-if="can(DefineAbilities.rp_finance_fatturato_report.action, DefineAbilities.rp_finance_fatturato_report.subject)"
            prepend-icon="tabler-report"
            color="secondary"
            variant="outlined"
            density="comfortable"
            class="px-3 me-1"
            @click="reportTargetView = true"
          >
            Apri Target
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
            Apri Report
          </VBtn>
        </div>
      </VCardText>
      <VDivider />
      <VCardText class="pa-3">
        <VRow class="mb-2">
          <!-- 👉 Materiale -->
          <VCol cols="12" sm="3">
            <AppTextField
              v-model="materialeFilter"
              :label="$t('Label.Materiale')"
              placeholder="Cerca materiale"
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-search"
              @keyup.enter="loadItems"
              @click:clear="loadItems"
            />
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
          <VCol cols="12" sm="3">
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
          <VCol cols="12" sm="3">
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
          <VCol cols="12" sm="3">
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
  <ReportTarget
    v-model:isDialogVisible="reportTargetView"
    :targets-data="targetData"
    :titolo-data="$t('Label.Target-Fatturato')"
  />
  <ReportFatturato
    v-model:isDialogVisible="reportVisibile"
    :data-filter-data="dataFilter"
    :materiale-filter-data="materialeFilter"
    :tipologia-cavo-filter-data="tipologiaCavoFilter"
    :clienti-filter-data="clientiFilter"
    :id-head-data="route.params.id"
  />
</template>
