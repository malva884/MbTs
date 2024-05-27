<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { useI18n } from 'vue-i18n'

interface Emit {
  (e: 'update:isDialogVisible', value: boolean): void
  (e: 'dataFilter', value: string): void
}

interface Props {
  dataFilterData?: string
  materialeFilterData?: string
  tipologiaCavoFilterData?: string
  codiceClienteData?: string
  clienteData?: string
  tipologiaData?: string
  isDialogVisible: boolean
}

const { t } = useI18n()
const itemsPerPage = ref(15)
const props = defineProps<Props>()
const emit = defineEmits<Emit>()
const totalItems = ref(0)
const search = ref('')
const serverItems = ref<any>([])
const loading = ref(true)
const sortBy = ref()
const orderBy = ref()
const page = ref(1)

// headers
const headers = [
  { title: t('Table.Materiale'), key: 'materiale' },
  { title: t('Table.Totale'), key: 'totale', sortable: false },
  { title: t('Table.Ckm'), key: 'ckm_t', sortable: false },
  { title: t('Table.Kfkm'), key: 'fkm_t', sortable: false },
]

const updateOptions = (options: any) => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
  page.value = options.page
  itemsPerPage.value = options.itemsPerPage

  // eslint-disable-next-line @typescript-eslint/no-use-before-define
  loadReport()
}

const loadReport = async () => {
  loading.value = true

  const resultData = await useApi<any>(createUrl(`/fi/turnover/cavi/list/${props.codiceClienteData}`, {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      data: props.dataFilterData,
      materiale: props.materialeFilterData,
      search: search.value,
      tipologiaCavo: props.tipologiaData,
    },
  }))

  serverItems.value = resultData.data.value.data
  totalItems.value = resultData.data.value.total
  loading.value = false
  props.isDialogVisible = true
}

let euro = new Intl.NumberFormat('it-IT', {
  style: 'currency',
  currency: 'EUR',
})


const close = () => {
  emit('update:isDialogVisible', false)
}

const printInvoice = () => {
  window.print()
}

watch(props, () => {
  serverItems.value = []
  loadReport()
})
</script>

<template>
  <VDialog
    :model-value="props.isDialogVisible"
    persistent
    class="v-dialog-lg"
  >
    <!-- Dialog close btn -->
    <DialogCloseBtn @click="close" class="d-print-none"/>

    <!-- Dialog Content -->
    <VCard title="Lista Cavi">
      <VCardText class="d-flex flex-wrap justify-space-between flex-column flex-sm-row print-row">
        <VCardText>
          <VRow>
            <VCol
              cols="12"
              md="4"
            >
             <h3 class="text-primary">{{props.clienteData}}</h3>
            </VCol>
            <VCol
              cols="12"
              offset-md="8"
              md="4"
            >
              <AppTextField
                v-model="search"
                placeholder="Search ..."
                append-inner-icon="tabler-search"
                single-line
                hide-details
                dense
                outlined
                @focusout="loadReport"
                class="d-print-none"
              />
            </VCol>
          </VRow>
        </VCardText>
        <VDataTableServer
          v-model:items-per-page="itemsPerPage"
          :headers="headers"
          :items="serverItems"
          :items-length="totalItems"
          :loading="loading"
          @update:options="updateOptions"
          class="text-no-wrap mb-0"
          density="compact"
        >
          <template #item.totale="{ item }">
            <p class="text-success">
              {{euro.format(item.totale)}}
            </p>
          </template>

          <template #item.fkm_t="{ item }">
            <p class="text-success">
              {{(item.fkm_t ? euro.format(item.fkm_t / 1000):'')}}
            </p>
          </template>
        </VDataTableServer>
      </VCardText>
      <VCardText class="d-flex justify-end gap-3 flex-wrap d-print-none">
        <DownloadExcel :data="serverItems">
          Download Data
        </DownloadExcel>
        <VBtn
          color="secondary"
          variant="tonal"
          @click="printInvoice"
        >
          Print
        </VBtn>
        <VBtn color="success" @click="close">
          Close
        </VBtn>
      </VCardText>
    </VCard>
  </VDialog>
</template>
