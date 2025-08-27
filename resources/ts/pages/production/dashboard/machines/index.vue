<script setup lang="ts">
import { useTheme } from 'vuetify'
import MachineProductionAnalysis from '@/views/production/dashboard/machines/MachineProductionAnalysis.vue'
import MachineProductionChart from '@/views/production/dashboard/machines/MachineProductionChart.vue'
import FilterDashboardMachines from '@/views/production/dashboard/machines/FilterDashboardMachines.vue'

definePage({
  meta: {
    action: 'list',
    subject: 'Produzione-Business-Intelligence',
  },
})

const vuetifyTheme = useTheme()
const items = ref({})
const tmp = ref(1)
const isChartVisible = ref(false)
const loadingPage = ref(false)
const idMacchina = ref('')
const nomeMacchina = ref('')
const quatroPuntoZero = ref(false)
const macchinaFilter = ref()
const categoriaFilter = ref()
const tipologiaFilter = ref()
const statoFilter = ref()
const t = ref()

const loadItems = async () => {
  loadingPage.value = true
  const { data: resultData } = await useApi<any>(createUrl('/gp/datiMacchina', {
    query: {
      macchina: macchinaFilter.value,
      categoria: categoriaFilter.value,
      tipologia: tipologiaFilter.value,
      stato: statoFilter.value,
    },
  }))

  items.value = resultData.value
  tmp.value = tmp.value + 1
  loadingPage.value = false
}

const openChart = (infoMacchina: object) => {
  idMacchina.value = infoMacchina.id
  nomeMacchina.value = infoMacchina.macchina
  quatroPuntoZero.value = infoMacchina.quatroPuntoZero
  isChartVisible.value = true
}

const setFilter = (filters: any) => {
  macchinaFilter.value = filters.macchina
  categoriaFilter.value = filters.categoria
  tipologiaFilter.value = filters.tipologia
  statoFilter.value = filters.stato
  loadItems()
}

setInterval(loadItems, 120000)
onMounted(() => {
  loadItems()
})
</script>

<template>
  <VRow class="match-height" :key="tmp">
    <!-- 👉 Total Earning -->
    <VCol
      cols="12"
      sm="6"
      lg="4"
      v-for="item in items"
      :key="item.Macchina"
    >
      <MachineProductionAnalysis
        :macchina="item.Macchina"
        :macchina-id="item.MacchinaId"
        :ordine="item.Ordine"
        :info-macchina="item.DatiMacchina"
        @update:info-macchina="openChart"
      />
    </VCol>
  </VRow>

  <MachineProductionChart
    v-if="isChartVisible"
    v-model:isChartVisible="isChartVisible"
    :macchina-id="idMacchina"
    :macchina-nome="nomeMacchina"
    :quatro-punto-zero="quatroPuntoZero"
  />

  <FilterDashboardMachines @update:filter="setFilter" />
  <LoadingStandBy v-model="loadingPage"></LoadingStandBy>
</template>

<style lang="scss">
@use "@core-scss/template/libs/apex-chart.scss";
</style>
