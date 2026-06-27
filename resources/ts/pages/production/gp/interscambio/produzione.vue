<script setup lang="ts">
import { VDataTableServer, VDataTable } from 'vuetify/labs/VDataTable'
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
const page = ref(1)
const serverItems = ref<any>([])
const isSnackbarScrollReverseVisible = ref(false)
const message = ref('')
const color = ref('')

const fabbDialog = ref(false)
const fabbLoading = ref(false)
const fabbItems = ref<any>([])
const fabbTotal = ref(0)
const fabbIdProduzione = ref('')
const fabbHeaders = computed(() => [
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

const updateOptions = (options: any) => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
  page.value = options.page
  itemsPerPage.value = options.itemsPerPage

  // eslint-disable-next-line @typescript-eslint/no-use-before-define
  loadItems()
}

const openFabbisogni = async (_event: any, row: { item: any }) => {
  fabbIdProduzione.value = row.item.IDProduzione
  fabbDialog.value = true
  fabbLoading.value = true
  fabbItems.value = []
  fabbTotal.value = 0

  const { data: resultData } = await useApi<any>(createUrl('/gp/fabbisogni', {
    query: {
      id_produzione: fabbIdProduzione.value,
      itemsPerPage: 100,
    },
  }))

  if (resultData.value !== null) {
    fabbItems.value = resultData.value.data
    fabbTotal.value = resultData.value.total
  }
  fabbLoading.value = false
}

const loadItems = async () => {
  loading.value = true

  const { data:resultData, error } = await useApi<any>(createUrl('/gp/produzione', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      ordine: olFilter.value,
      materiale: materialeFilter.value,
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


// headers
const headers = computed(() => [
  //{ title: 'ID', key: 'Id', sortable: true },
  { title: t('Label.IDProduzione'), key: 'IDProduzione', sortable: true },
  { title: t('Label.Ordine'), key: 'Ordine', sortable: true },
  { title: t('Label.Fase'), key: 'Fase', sortable: true },
  { title: t('Label.DataMov'), key: 'DataMov', sortable: true },
  { title: t('Label.Risorsa'), key: 'Risorsa', sortable: true },
  { title: t('Label.Quantita'), key: 'quantita', sortable: true },
  { title: t('Label.TempoTotale'), key: 'TempoTotale', sortable: true },
  { title: t('Label.TempoLavorato'), key: 'TempoLavorato', sortable: true },
  { title: t('Label.TempoFermi'), key: 'TempoFermi', sortable: true },
  { title: t('Label.TempoOperatore'), key: 'TempoOperatore', sortable: true },
  { title: t('Label.Operatore'), key: 'OperatorCode', sortable: true },
  { title: t('Label.UMTime'), key: 'UMTime', sortable: true },
  { title: t('Label.Esportato'), key: 'Esportato', sortable: true },
  { title: t('Label.DataEsportazione'), key: 'DataEsportazione', sortable: true },
  { title: 'MSG', key: 'MSG', sortable: true },
  { title: t('Label.Errore'), key: 'Errore', sortable: true },
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
          <VIcon icon="tabler-building-factory-2" size="24" color="primary" />
          <div>
            <div class="text-h6 font-weight-medium">{{ $t('Label.Lista-Produzione') }}</div>
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
        @click:row="openFabbisogni"
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

    <!-- 👉 Dialog Fabbisogni -->
    <VDialog v-model="fabbDialog" max-width="1200">
      <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
        <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
          <div class="d-flex align-center gap-2">
            <VIcon icon="tabler-package-export" size="24" color="primary" />
            <div>
              <div class="text-h6 font-weight-medium">Fabbisogni - ID Produzione: {{ fabbIdProduzione }}</div>
              <div class="text-caption text-medium-emphasis">{{ fabbTotal }} record</div>
            </div>
          </div>
          <VBtn icon="tabler-x" variant="text" density="comfortable" @click="fabbDialog = false" />
        </VCardText>
        <VDivider />
        <VDataTable
          :headers="fabbHeaders"
          :items="fabbItems"
          :loading="fabbLoading"
          density="comfortable"
          hover
          height="400"
        >
          <template #no-data>
            <div class="py-10 text-center">
              <VIcon icon="tabler-database-off" size="40" class="text-disabled mb-2" />
              <p class="text-body-1 text-disabled mb-0">Nessun fabbisogno trovato</p>
            </div>
          </template>
        </VDataTable>
      </VCard>
    </VDialog>
  </div>
</template>
