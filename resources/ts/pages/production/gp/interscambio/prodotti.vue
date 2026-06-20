<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import moment from 'moment'
import { useI18n } from 'vue-i18n'
import {can} from "@layouts/plugins/casl";
import {da} from "vuetify/locale";

definePage({
  meta: {
    action: 'admin',
    subject: 'Produzione-Business-Intelligence',
  },
})

const { t } = useI18n()
const itemsPerPage = ref(10)
let loading = true
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const materialeFilter = ref('')
const numeroFibraFilter = ref()
const tipologiaFilter = ref('')
const page = ref(1)
const serverItems = ref<any>([])
const isSnackbarScrollReverseVisible = ref(false)
const message = ref('')
const color = ref('')
const isDialogLoading = ref(false)

const updateOptions = (options: any) => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
  page.value = options.page
  itemsPerPage.value = options.itemsPerPage

  // eslint-disable-next-line @typescript-eslint/no-use-before-define
  loadItems()
}

const loadItems = async () => {
  loading = true
  isDialogLoading.value = true

  const { data:resultData, error } = await useApi<any>(createUrl('/gp/prodotti', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      materiale: materialeFilter.value,
      conversione: numeroFibraFilter.value,
      tipologia: tipologiaFilter.value,
    },
  }))

  if (resultData.value !== null) {
    serverItems.value = resultData.value.data
    totalItems.value = resultData.value.total
  } else {
    serverItems.value = []
    totalItems.value = 0
  }
  loading = false
  isDialogLoading.value = false
}


// headers
const headers = [
  { title: t('Label.Materiale'), key: 'cdProdotto' },
  { title: t('Label.Descrizione'), key: 'dsProdotto' },
  { title: t('Label.Um'), key: 'cdUM' },
  { title: t('Label.Raggruppamento'), key: 'cdRaggruppamento' },
  { title: t('Label.UMTecn'), key: 'cdUMTecn' },
  { title: t('Label.Conversione'), key: 'Conversione' },
  { title: t('Label.Valore'), key: 'Valore' },
  { title: t('Label.dtUltimoMovimento'), key: 'dtUltimoMovimento' },
  { title: t('Label.Data-Inserimento'), key: 'dataInserimento' },
]

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
  <VCol cols="12">
    <VCard
      title="Filters"
      class="mb-6"
    >
      <VCardText>
        <VRow>
          <!-- 👉 Materiale -->
          <VCol
            cols="12"
            sm="2"
          >
            <AppTextField
              v-model="materialeFilter"
              :label="$t('Label.Codice Materiale')"
              :placeholder="$t('Label.Codice Materiale')"
              clearable
              clear-icon="tabler-x"
              @focusout="loadItems"
            />
          </VCol>
          <!-- 👉 Numero Fibra -->
          <VCol
            cols="12"
            sm="1"
          >
            <AppTextField
              v-model="numeroFibraFilter"
              type="number"
              :label="$t('Label.Numero Fibre')"
              :placeholder="$t('Label.Numero Fibre')"
              clearable
              clear-icon="tabler-x"
              @focusout="loadItems"
            />
          </VCol>
          <!-- 👉 Tipologia -->
          <VCol
            cols="12"
            sm="2"
          >
            <AppSelect
              v-model="tipologiaFilter"
              :items="[{ titolo: 'Rame', id: 40 }, { titolo: 'Fibra', id: 20 }]"
              :label="$t('Label.Um')"
              :placeholder="$t('Label.Um')"
              item-title="titolo"
              item-value="id"
              clearable
              clear-icon="tabler-x"
              persistent-hint
              @focusout="loadItems"
            />
          </VCol>
        </VRow>
      </VCardText>
    </VCard>
    <VCard :title="$t('Label.Lista-Strisciate')">
      <VCardText class="d-flex flex-wrap py-4 gap-4">
        <VSnackbar
          v-model="isSnackbarScrollReverseVisible"
          transition="scroll-y-reverse-transition"
          location="top central"
          :color="color"
        >
          {{ $t(message) }}
        </VSnackbar>
      </VCardText>
      <!-- 👉 Datatable  -->
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        :headers="headers"
        :items="serverItems"
        :items-length="totalItems"
        :loading="loading"
        @update:options="updateOptions"
      >

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
  </VCol>

  <!-- Dialog -->
  <VDialog
    v-model="isDialogLoading"
    width="300"
  >
    <VCard
      color="primary"
      width="300"
    >
      <VCardText class="pt-3">
        <span class="ml-4 mb-3">Please stand by</span>
        <VProgressLinear
          :size="40"
          color="warning"
          class="mt-3"
          indeterminate
        />
      </VCardText>
    </VCard>
  </VDialog>
</template>

<style>
.v-table > .v-table__wrapper > table > tbody > tr > td, .v-table > .v-table__wrapper > table > thead > tr > td, .v-table > .v-table__wrapper > table > tfoot > tr > td {
  font-size: 15px !important;
}
</style>
