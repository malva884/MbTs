<script setup lang="ts">
import { ref, shallowRef, onMounted, onUnmounted } from 'vue'
import MachineProductionAnalysis from '@/views/production/dashboard/machines/MachineProductionAnalysis.vue'
import MachineProductionChart from '@/views/production/dashboard/machines/MachineProductionChart.vue'
import FilterDashboardMachines from '@/views/production/dashboard/machines/FilterDashboardMachines.vue'

definePage({
  meta: {
    action: 'list',
    subject: 'Produzione-Business-Intelligence',
  },
})

const items = shallowRef<any[]>([])
const isChartVisible = ref(false)
const loadingPage = ref(false)
const idMacchina = ref('')
const nomeMacchina = ref('')
const quatroPuntoZero = ref(false)

const macchinaFilter = ref()
const categoriaFilter = ref()
const tipologiaFilter = ref()
const statoFilter = ref('Run')

const loadItems = async () => {
  loadingPage.value = true
  try {
    const { data: resultData } = await useApi<any>(createUrl('/gp/datiMacchina', {
      query: {
        macchina: macchinaFilter.value,
        categoria: categoriaFilter.value,
        tipologia: tipologiaFilter.value,
        stato: statoFilter.value,
      },
    }))

    items.value = resultData.value ? Object.values(resultData.value) : []
  } catch (error) {
    console.error("Errore nel caricamento dati macchina:", error)
  } finally {
    loadingPage.value = false
  }
}

const openChart = (infoMacchina: any) => {
  if (!infoMacchina) return
  idMacchina.value = infoMacchina.id
  nomeMacchina.value = infoMacchina.macchina
  quatroPuntoZero.value = infoMacchina.quatroPuntoZero
  isChartVisible.value = true
}

let filterTimeout: any = null

const setFilter = (filters: any) => {
  macchinaFilter.value = filters.macchina
  categoriaFilter.value = filters.categoria
  tipologiaFilter.value = filters.tipologia
  statoFilter.value = filters.stato
  if (filterTimeout) clearTimeout(filterTimeout)
  filterTimeout = setTimeout(loadItems, 400)
}

// Gestione corretta dell'intervallo per evitare memory leak
let intervalId: any = null

onMounted(() => {
  loadItems()
  intervalId = setInterval(() => {
    if (!loadingPage.value) loadItems()
  }, 300000) // Aggiorna ogni 5 minuti se non in caricamento
})

onUnmounted(() => {
  if (intervalId) clearInterval(intervalId)
  if (filterTimeout) clearTimeout(filterTimeout)
})
</script>

<template>
  <div class="pa-1">

    <VRow class="mb-4">
      <VCol cols="12">
        <FilterDashboardMachines @update:filter="setFilter" />
      </VCol>
    </VRow>

    <VRow align="stretch">
      <VCol
        v-for="item in items"
        :key="item.Macchina"
        cols="12"
        sm="6"
        lg="4"
      >
        <MachineProductionAnalysis
          :macchina="item.Macchina"
          :macchina-id="item.MacchinaId"
          :ordine="item.Ordine"
          :info-macchina="item.DatiMacchina"
          @update:info-macchina="openChart"
        />
      </VCol>

      <VCol cols="12" v-if="!items || items.length === 0" class="text-center text-disabled py-8">
        Nessuna macchina trovata con i filtri selezionati.
      </VCol>
    </VRow>

    <MachineProductionChart
      v-if="isChartVisible"
      v-model:isChartVisible="isChartVisible"
      :macchina-id="idMacchina"
      :macchina-nome="nomeMacchina"
      :quatro-punto-zero="quatroPuntoZero"
    />

    <LoadingStandBy v-model="loadingPage" />
  </div>
</template>

<style lang="scss">
@use "@core-scss/template/libs/apex-chart.scss";
</style>
