<script setup lang="ts">
import {useI18n} from 'vue-i18n'
import { VDataTableServer } from 'vuetify/labs/VDataTable'

const { t } = useI18n()
const loading = ref(true)
const sortBy = ref()
const orderBy = ref()
const page = ref(1)
const itemsPerPage = ref(15)
const serverItems = ref<any>([])
const totalItems = ref(0)
const selectedHeaders = ref()
let headersTemp = []

interface Props {
  materialeFilter: string
  classeFilter: string
}

const props = defineProps<Props>()

const route = useRoute('production-warehouse-view-id')

// headers
const headers = [
  { title: t('Table.Materiale'), key: 'materiale' },
  { title: t('Table.Descrizione'), key: 'descrizione' },
  { title: t('Table.Quantita'), key: 'quantita' },
  { title: t('Table.Um'), key: 'um' },
  { title: t('Table.Valore-Unitario'), key: 'valore_unitario' },
  { title: t('Table.Valore-Totale'), key: 'valore_totale' },
  { title: t('Table.Ultimo-Movimento'), key: 'ultimo_movimento' },
]

selectedHeaders.value = headers
headersTemp = headers

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
  const { data: resultData } = await useApi<any>(createUrl(`/pr/magazzino/view/${route.params.id}`, {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      materiale: props.materialeFilter,
      classe: props.classeFilter,
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

const euro = new Intl.NumberFormat('it-IT', {
  style: 'currency',
  currency: 'EUR',
})

watch(props, () => {
  loadItems()
})
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard :title="$t('Label.Dettaglio-Magazino')">
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

          <template #item.valore_unitario="{ item }">
            <p
              v-if="item.valore_unitario > 0.000"
              class="text-info"
            >
              {{ euro.format(item.valore_unitario) }}
            </p>
          </template>

          <template #item.valore_totale="{ item }">
            <p
              v-if="item.valore_totale > 0.000"
              class="text-info"
            >
              {{ euro.format(item.valore_totale) }}
            </p>
          </template>
        </VDataTableServer>
      </VCard>
    </VCol>
  </VRow>
</template>
