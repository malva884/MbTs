<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import moment from 'moment'
import { useI18n } from 'vue-i18n'

definePage({
  meta: {
    action: 'admin',
    subject: 'Produzione-Business-Intelligence',
  },
})

const { t } = useI18n()
const itemsPerPage = ref(10)
const loading = ref(true)
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const olFilter = ref('')
const materialeFilter = ref('')
const umFilter = ref('')
const numeroFibraFilter = ref()
const date = new Date()
const noQuantitaFilter = ref(true)
const day = date.getDate()
const month = date.getMonth() + 1
const year = date.getFullYear()
const dataFilter = ref(`${year}-${month}-${day}`)
const page = ref(1)
const serverItems = ref<any>([])
const isSnackbarScrollReverseVisible = ref(false)
const message = ref('')
const color = ref('')

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

  const { data:resultData, error } = await useApi<any>(createUrl('/gp/fabbisogni', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      ordine: olFilter.value,
      materiale: materialeFilter.value,
      um: umFilter.value,
      data: dataFilter.value,
      num_fibre: numeroFibraFilter.value,
      no_quantita: noQuantitaFilter.value,
    },
  }))

  if (resultData.value !== null) {
    serverItems.value = resultData.value.data
    totalItems.value = resultData.value.total
  } else {
    serverItems.value = []
    totalItems.value = 0
  }
  loading.value = false
}

// status options
const selectedOptions = [
  { text: 'Positivo', value: 1 },
  { text: 'Negativo', value: 2 },
]

// headers
const headers = computed(() => [
  { title: t('Label.IDProduzione'), key: 'IDProduzione' },
  { title: t('Label.Materiale'), key: 'cdProdotto' },
  { title: t('Label.Lotto'), key: 'cdLotto' },
  { title: t('Label.Quantita'), key: 'Qta' },
  { title: t('Label.Esportato'), key: 'Esportato' },
  { title: t('Label.DataEsportazione'), key: 'DataEsportazione' },
  { title: 'MSG', key: 'MSG' },
  { title: t('Label.Errore'), key: 'Errore' },
  { title: t('Label.Ordine'), key: 'Ordine' },
  { title: t('Label.Fase'), key: 'Fase' },
  { title: t('Label.CoeffImpegno'), key: 'CoeffImpegno' },
  { title: t('Label.QtaProdotta'), key: 'QtaProdotta' },
  { title: t('Label.Consumo'), key: 'Consumo' },
])

function formatDate(date: string): string {
  return moment(String(date)).format('MM/DD/YYYY H:m:s')
}

const formatNum = (numero: number, decimal: boolean) => {
  let num = 3
  if (!decimal) {
    numero = Math.trunc(numero)
    num = 0
  }

  return new Intl.NumberFormat('it-IT', { minimumFractionDigits: num, maximumFractionDigits: 3 }).format(numero)
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
          <VIcon icon="tabler-package-export" size="24" color="primary" />
          <div>
            <div class="text-h6 font-weight-medium">{{ $t('Label.Lista-Strisciate') }}</div>
            <div class="text-caption text-medium-emphasis">{{ totalItems }} record</div>
          </div>
        </div>
      </VCardText>
      <VDivider />
      <VCardText class="pa-3">
        <VRow class="mb-2">
          <!-- 👉 Ordine -->
          <VCol cols="12" sm="3">
            <AppTextField
              v-model="olFilter"
              :label="$t('Label.Numero Ordine')"
              :placeholder="$t('Label.Numero Ordine')"
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-search"
              @keyup.enter="loadItems"
              @click:clear="loadItems"
            />
          </VCol>

          <!-- 👉 Materiale -->
          <VCol cols="12" sm="3">
            <AppTextField
              v-model="materialeFilter"
              :label="$t('Label.Codice Materiale')"
              :placeholder="$t('Label.Codice Materiale')"
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-search"
              @keyup.enter="loadItems"
              @click:clear="loadItems"
            />
          </VCol>

          <!-- 👉 Numero Fibra -->
          <VCol cols="12" sm="2">
            <AppTextField
              v-model="numeroFibraFilter"
              type="number"
              :label="$t('Label.Numero Fibre')"
              :placeholder="$t('Label.Numero Fibre')"
              clearable
              clear-icon="tabler-x"
              @keyup.enter="loadItems"
              @click:clear="loadItems"
            />
          </VCol>

          <!-- 👉 Unita -->
          <VCol cols="12" sm="2">
            <AppSelect
              v-model="umFilter"
              :items="[{ titolo: 'Mt', id: 'MT' }, { titolo: 'Km', id: 'KM' }]"
              :label="$t('Label.Um')"
              :placeholder="$t('Label.Um')"
              item-title="titolo"
              item-value="id"
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
              :placeholder="$t('Label.Data')"
              :config="{ mode: 'range' }"
              clearable
              clear-icon="tabler-x"
              @keyup.enter="loadItems"
              @click:clear="loadItems"
            />
          </VCol>

          <VCol cols="12" sm="3">
            <VCheckbox v-model="noQuantitaFilter" @change="loadItems">
              <template #label>
                <div>
                  {{$t('Label.No-Quantita')}}
                </div>
              </template>
            </VCheckbox>
          </VCol>
        </VRow>
      </VCardText>
      <VDivider />
      <!-- 👉 Datatable  -->
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
        <template #no-data>
          <div class="py-10 text-center">
            <VIcon icon="tabler-database-off" size="40" class="text-disabled mb-2" />
            <p class="text-body-1 text-disabled mb-0">Nessun record trovato</p>
          </div>
        </template>

        <!-- Quantità -->
        <template #item.quantita="{ item }">
          <div v-if="item.UM == 'km' && item.quantita > 100" class="d-flex gap-1 text-warning" >
            {{ formatNum(item.quantita, true) }}
          </div>
          <div v-else class="d-flex gap-1 text-success" >
            {{ formatNum(item.quantita, true) }}
          </div>
        </template>

        <!-- Numero Fibre -->
        <template #item.Conversione12="{ item }">
          <div class="d-flex gap-1">
            {{ formatNum(item.Conversione12,false) }}
          </div>
        </template>

        <!-- date -->
        <template #item.DataOraInizio="{ item }">
          <div class="d-flex gap-1">
            {{ formatDate(item.DataOraInizio) }}
          </div>
        </template>

        <!-- date -->
        <template #item.DataOraFine="{ item }">
          <div class="d-flex gap-1">
            {{ formatDate(item.DataOraFine) }}
          </div>
        </template>

        <!-- descrizione -->
        <template #item.DescrizioneProdotto="{ item }">
          <div class="d-flex gap-1" >
            {{ item.DescrizioneProdotto }}
          </div>
        </template>
      </VDataTableServer>
    </VCard>
  </div>
</template>
