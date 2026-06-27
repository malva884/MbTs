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

  const { data:resultData, error } = await useApi<any>(createUrl('/gp/strisciate', {
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
  loading = false
  isDialogLoading.value = false
}

// status options
const selectedOptions = [
  { text: 'Positivo', value: 1 },
  { text: 'Negativo', value: 2 },
]

// headers
const headers = computed(() => [
  { title: t('Label.Ordine'), key: 'NumeroOrdineAcquisto' },
  { title: t('Label.Materiale'), key: 'NomeProdotto' },
  { title: t('Label.Descrizione-Matariale'), key: 'DescrizioneProdotto' },
  { title: t('Label.Data-Inizio'), key: 'DataOraInizio' },
  { title: t('Label.Data-Fine'), key: 'DataOraFine' },
  { title: t('Label.Macchina'), key: 'Modello' },
  { title: t('Label.Numero Fibre'), key: 'Conversione12' },
  { title: t('Label.Um'), key: 'UM' },
  { title: t('Label.Quantita'), key: 'quantita' },
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
  <VCol cols="12">
    <VCard
      title="Filters"
      class="mb-6"
    >
      <VCardText>
        <VRow>
          <!-- 👉 Ordine -->
          <VCol
            cols="12"
            sm="2"
          >
            <AppTextField
              v-model="olFilter"
              :label="$t('Label.Numero Ordine')"
              :placeholder="$t('Label.Numero Ordine')"
              clearable
              clear-icon="tabler-x"
              @focusout="loadItems"
            />
          </VCol>

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
          <!-- 👉 Unita -->
          <VCol
            cols="12"
            sm="2"
          >
            <AppSelect
              v-model="umFilter"
              :items="[{ titolo: 'Mt', id: 'MT' }, { titolo: 'Km', id: 'KM' }]"
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
          <!-- 👉 Data -->
          <VCol
            cols="12"
            sm="2"
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
          <VCol
            cols="12"
            sm="1"
          >
            <VCheckbox v-model="noQuantitaFilter" @change="loadItems">
              <template #label>
                <div>
                  {{$t('Label.No-Quantita')}}
                </div>
              </template>
            </VCheckbox>
          </VCol>

          <VCol cols="2" class="d-flex ">
            <VSpacer />

            <div class="app-user-search-filter d-flex align-center ">
              <!-- 👉 Export button -->

              <VBtn
                variant="tonal"
                color="success"
                prepend-icon="tabler-screen-share"
                :href="`/api/export/production/biProduction/excel?ol=${olFilter}&materiale=${materialeFilter}&data=${dataFilter}&conversione=${numeroFibraFilter}&um=${umFilter}`"
              >
                Export
              </VBtn>
            </div>
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
