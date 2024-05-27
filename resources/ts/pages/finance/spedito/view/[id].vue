<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { useI18n } from 'vue-i18n'
import type { VForm } from 'vuetify/components/VForm'
import moment from 'moment/moment'

import { can } from '@layouts/plugins/casl'

definePage({
  meta: {
    action: 'read',
    subject: 'Finanze-Spedito',
  },
})

const { t } = useI18n()
const itemsPerPage = ref(10)
const loading = ref(true)
const refForm = ref<VForm>()
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const macchinaeFilter = ref('')
const dataFilter = ref('')
const lavorazioneFilter = ref('')
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
let headersTemp = []
const columns = ref()
const table = ref()

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
  const { data: resultData, error } = await useApi<any>(createUrl(`/fi/rows/list/${route.params.id}`, {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      macchina: macchinaeFilter.value,
      data: dataFilter.value,
      lavorazione: lavorazioneFilter.value,
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

// headers
const headers = [
  { title: t('Table.Data'), key: 'date_row' },
  { title: t('Table.Cod-Cliente'), key: 'code_client' },
  { title: t('Table.Cliente'), key: 'client' },
  { title: t('Table.Item'), key: 'item' },
  { title: t('Table.Materiale'), key: 'material' },
  { title: t('Table.Descrizione'), key: 'description' },
  { title: t('Table.Tipo-Cavo'), key: 'type' },
  { title: t('Table.Commessa'), key: 'commessa' },
  { title: t('Table.Codice-Cliente'), key: 'code_recipient' },
  { title: t('Table.Cliente'), key: 'recipient' },
  { title: t('Table.Unit'), key: 'unit' },
  { title: t('Table.Totale-Spedito'), key: 'qty_value' },
  { title: t('Table.cost_value'), key: 'cost_value' },
  { title: t('Table.Fibre'), key: 'fiber_counter' },
  { title: t('Table.Quantita-Spedito'), key: 'delivered_qty' },
  { title: t('Table.Codice-Kfm'), key: 'qty_fkm' },
  { title: t('Table.Prezzo-Km'), key: 'price_km' },
  { title: t('Table.Costo-Al-Km'), key: 'cost_km' },
  { title: t('Table.std_price'), key: 'std_price' },
  { title: t('Table.Ordine'), key: 'order' },
  { title: t('Table.Profitto-Netto'), key: 'net_profit' },
  { title: t('Table.Profitto-Percentuale'), key: 'profit_perc' },
  { title: t('Table.exchange_rate'), key: 'exchange_rate' },
  { title: t('Table.Cap'), key: 'postal_code' },
  { title: t('Table.Citta'), key: 'city' },
  { title: t('Table.Docuemnto'), key: 'document' },
  { title: t('Table.Distanza'), key: 'km_distance' },
]

selectedHeaders.value = headers
headersTemp = headers

const resolveLavorazione = (lavorazione: string) => {
  if (lavorazione === '2')
    return {color: 'warning', text: 'Ottico'}
  else if (lavorazione === '1')
    return {color: 'success', text: 'Rame'}
  else
    return {color: 'primary', text: 'Ottivo/Rame'}
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

let euro = new Intl.NumberFormat('it-IT', {
  style: 'currency',
  currency: 'EUR',
})

const test = async () => {
  headersTemp = []
  headers.forEach(element => {
    if (selectedHeaders.value.includes(element.key))
      headersTemp.push(element)
  })
  loadItems()
}
</script>

<template>
  <VCol cols="12">
    <VCard
      title="Filters"
      class="mb-6"
    >
      <VCardText>
        <VRow>
          <!-- 👉 Visitatore -->
          <VCol
            cols="12"
            sm="4"
          >
            <AppTextField
              v-model="macchinaeFilter"
              :label="$t('Label.Visitatore')"
              clearable
              clear-icon="tabler-x"
              @focusout="loadItems"
            />
          </VCol>

          <!-- 👉 Lavorazione -->
          <VCol
            cols="12"
            sm="4"
          >
            <AppSelect
              v-model="lavorazioneFilter"
              :label="$t('Label.Lavorazione')"
              :placeholder="$t('Label.Lavorazione')"
              :items="[{ title: 'Rame', value: 1 }, { title: 'Ottico', value: 2 }, { title: 'Entrambi', value: 3 }]"
              clearable
              clear-icon="tabler-x"
              @focusout="loadItems"
            />
          </VCol>
          <!-- 👉 Data -->
          <VCol
            cols="12"
            sm="4"
          >
            <AppDateTimePicker
              v-model="dataFilter"
              :label="$t('Label.Data')"
              :placeholder="$t('Label.Data')"
              :config="{ mode: 'range' }"
              clearable
              clear-icon="tabler-x"
              @focusout="loadItems"
            />

          </VCol>
        </VRow>
      </VCardText>
    </VCard>
    <VCard>
      <VCardText class="d-flex flex-wrap py-4 gap-4">
        <VSnackbar
          v-model="isSnackbarScrollReverseVisible"
          transition="scroll-y-reverse-transition"
          location="top central"
          :color="color"
        >
          {{ $t(message) }}
        </VSnackbar>
        <VCol
          cols="12"
          sm="6"
        >
          <AppSelect
            v-model="selectedHeaders"
            :label="$t('Label.Colonne')"
            :items="headers"
            :item-title="item => item.title"
            :item-value="item => item.key"
            chips
            multiple
            eager
            @focusout="test"
          />

        </VCol>
      </VCardText>
      <!-- 👉 Datatable  -->
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        :headers="headersTemp"
        :items="serverItems"
        :items-length="totalItems"
        :loading="loading"
        @update:options="updateOptions"
        height="600"
        fixed-header
      >
        <template #item.net_profit="{ item }">
          <p
            v-if="item.profit_perc > 0.00"
            class="text-success">
            {{euro.format(item.net_profit)}}
          </p>
          <p v-else class="text-warning">
            {{euro.format(item.net_profit)}}
          </p>
        </template>

        <template #item.profit_perc="{ item }">
          <p
            v-if="item.profit_perc > 0.00"
            class="text-success">
            {{item.profit_perc}} %
          </p>
          <p v-else class="text-warning">
            {{item.profit_perc}} %
          </p>
        </template>

        <template #item.km_distance="{ item }">
          {{item.km_distance}} Km
        </template>
      </VDataTableServer>
    </VCard>
  </VCol>
</template>
