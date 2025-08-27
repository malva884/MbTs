<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { useI18n } from 'vue-i18n'
import {can} from "@layouts/plugins/casl";
import DefineAbilities from "@/plugins/casl/DefineAbilities";


interface Props {
  periodoData: string
  meseSelezionato: string
}

const props = defineProps<Props>()
const dataCorrente = new Date()
const { t } = useI18n()
const key = ref(0)
const dataFilter = ref(`${dataCorrente.getFullYear()}-${dataCorrente.getMonth()+1}-01 to ${dataCorrente.getFullYear()}-${dataCorrente.getMonth()+1}-${dataCorrente.getUTCDate()}`)
const itemsPerPage = ref(100)
const loading = ref(true)
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const page = ref(1)
const serverItems = ref<any>([])

const headers = [
  { title: t('Label.Macchina'), key: 'Macchina' },
  { title: t('Label.Ore-Macchina'), key: 'SchedaH' },
  { title: t('Label.Fermi-Macchina'), key: 'FermiTotal' },
  { title: t('Label.F1'), key: 'F1' },
  { title: t('Label.F5'), key: 'F5' },
  { title: t('Label.Total-Ore-Macchina'), key: 'TotOreMacchina' },
  { title: t('Label.Ore-Manodopera'), key: 'ManodoperaH' },
  { title: t('Label.Ore-Manodopera-Calcolata'), key: 'ManodoperaCalcolataH' },
  { title: t('Label.Rapporto-mac/man'), key: 'RapportMacchina' },
  { title: t('Label.Efficenza'), key: 'efficenza',  sortable: false },
]

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

  const { data: resultData } = await useApi<any>(createUrl('/production/plant/machines/', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      periodo: dataFilter.value,
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

const resolveEfficenza = (item: object) => {
  const eff = ((item.SchedaH - Number.parseFloat(item.FermiTotal)) / item.SchedaH) * 100

  if (eff >= 70)
    return { color: 'text-success', percentuale: eff }
  else if (eff >= 45)
    return { color: 'text-warning', percentuale: eff }
  else
    return { color: 'text-error', percentuale: eff }
}

const formatNumber = (value: number, decimals = 2) => {
  return Number(`${Math.round(Number(`${value}e${decimals}`))}e-${decimals}`)
}

loadItems()
watch(props, () => {
  loadItems()
})
</script>

<template>
  <VCol>
    <VCard :title="`${$t('Label.Ore-Macchina')}  ${dataFilter}`">
      <VCardText class="d-flex flex-wrap py-4 gap-4">
        <div class="me-3 d-flex gap-3" />
        <VSpacer />

        <div class="app-user-search-filter d-flex align-center flex-wrap gap-4">
          <!-- 👉 Export button -->

          <VBtn
            variant="tonal"
            color="secondary"
            prepend-icon="tabler-screen-share"
            :href="`/api/export/machinesExport?periodo=${dataFilter}`"
          >
            Export
          </VBtn>
          <AppDateTimePicker
            v-model="dataFilter"
            placeholder="Select date"
            :config="{ mode: 'range' }"
            style="width: 250px;"
            @focusout="loadItems"
          />
        </div>
      </VCardText>
      <!-- 👉 Datatable  -->
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        :headers="headers"
        :items="serverItems"
        :items-length="totalItems"
        :loading="loading"
        fixed-header
        height="600"
        @update:options="updateOptions"
        class="text-no-wrap elevation-1"
        density="compact"
      >
        <template #item.SchedaH="{ item }">
          <p class="">{{ formatNumber(item.SchedaH) }}</p>
        </template>

        <template #item.FermiTotal="{ item }">
          <p class="text-error">{{ formatNumber(item.FermiTotal) }}</p>
        </template>

        <template #item.TotOreMacchina="{ item }">
          <p class="text-success">{{ formatNumber(item.SchedaH - item.FermiTotal) }}</p>
        </template>

        <template #item.F1="{ item }">
          <p class="text-info ">{{ formatNumber(item.F1) }}</p>
        </template>

        <template #item.F5="{ item }">
          <p class="text-warning ">{{ formatNumber(item.F5) }}</p>
        </template>

        <template #item.ManodoperaH="{ item }">
          <p class="">{{ formatNumber(item.ManodoperaH) }}</p>
        </template>

        <template #item.ManodoperaCalcolataH="{ item }">
          <p class="">{{ formatNumber(item.ManodoperaCalcolataH) }}</p>
        </template>

        <template #item.RapportMacchina="{ item }">
          <p class="text-error ">{{  formatNumber(parseFloat(item.ManodoperaH) / (item.SchedaH - item.FermiTotal)) }}</p>
        </template>

        <template #item.efficenza="{ item }">
          <p :class="resolveEfficenza(item).color">{{ formatNumber(resolveEfficenza(item).percentuale) }} %</p>
        </template>
      </VDataTableServer>
    </VCard>
  </VCol>
</template>

<style scoped lang="scss">
tbody tr:nth-of-type(odd) {
  /* 'teal lighten-5' basides on material design color */
  background-color: #E0F2F1;
}

tbody tr:nth-of-type(even) {
  /* 'deep-orange lighten-5' basides on material design color */
  background-color: #FBE9E7;
}
</style>
