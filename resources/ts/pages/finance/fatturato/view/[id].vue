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
const headers = [
  { title: t('Table.Data'), key: 'data_documento' },
  { title: t('Table.Quantita'), key: 'quantita', align: 'end' },
  { title: t('Table.Kfkm'), key: 'kfkm', align: 'end' },
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

]

selectedHeaders.value = headers
headersTemp = headers


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
            sm="3"
          >
            <AppTextField
              v-model="materialeFilter"
              :label="$t('Label.Materiale')"
              :placeholder="$t('Label.Materiale')"
              clearable
              clear-icon="tabler-x"
              @focusout="loadItems"
            />
          </VCol>

          <!-- 👉 Clienti -->
          <VCol cols="12" sm="3">

            <AppCombobox
              v-model="clientiFilter"
              :label="$t('Label.Clienti')"
              :placeholder="$t('Label.Clienti')"
              :items="clientiOptions"
              :item-title="item => item.val"
              :item-value="item => item.id"
              chips
              multiple
              eager
              clearable
              clear-icon="tabler-x"
              @focusout="reloadItems"
            />
          </VCol>

          <!-- 👉 tipologia Cavo -->
          <VCol
            cols="12"
            sm="3"
          >
            <AppSelect
              v-model="tipologiaCavoFilter"
              :label="$t('Label.Tipologia-Cavo')"
              :placeholder="$t('Label.Tipologia-Cavo')"
              :items="[{ title: 'Rame', value: 5441 }, { title: 'Ottico', value: 5420 }]"
              clearable
              clear-icon="tabler-x"
              @focusout="loadItems"
            />
          </VCol>

          <!-- 👉 Data -->
          <VCol
            cols="12"
            sm="3"
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
        <VCol
          cols="12"
          class="align-content-lg-center"
          sm="5"
        >
          <div class="d-flex float-end ">
            <!-- 👉 Add user button -->
            <VBtn
              v-if="can(DefineAbilities.rp_finance_fatturato_report.action, DefineAbilities.rp_finance_fatturato_report.subject)"
              color="success"
              prepend-icon="tabler-report"
              class="d-flex float-end"
              @click="reportTargetView = true"
            >
              Apri Target
            </VBtn>
            <VBtn
              v-if="can(DefineAbilities.rp_finance_fatturato_report.action, DefineAbilities.rp_finance_fatturato_report.subject)"
              color="info"
              prepend-icon="tabler-report"
              class="d-flex float-end"
              @click="openReprot"
            >
              Apri Report
            </VBtn>
          </div>
        </VCol>
      </VCardText>

      <!-- 👉 Datatable  -->
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        :headers="headersTemp"
        :items="serverItems"
        :items-length="totalItems"
        :loading="loading"
        height="600"
        fixed-header
        @update:options="updateOptions"
      >
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

        <template #item.kfkm="{ item }">
          <p
            v-if="item.kfkm > 0.000"
            class="text-info"
          >
            {{ item.kfkm }}
          </p>
        </template>

        <template #item.ckm="{ item }">
          <p
            v-if="item.ckm > 0.000"
            class="text-info"
          >
            {{ item.ckm }}
          </p>
        </template>
      </VDataTableServer>
    </VCard>
  </VCol>
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
