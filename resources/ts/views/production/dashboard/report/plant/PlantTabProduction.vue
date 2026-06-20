<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { sortBy } from 'lodash'
import { useTheme } from 'vuetify'
import {
  getColumnChartCustom2Config,
  getColumnChartCustomConfig,
  getLineBubleChartCustomConfig,
  getLineColumnChartCustomConfig,
} from '@core/libs/apex-chart/apexCharConfig'

interface Props {
  periodoData: string
  meseSelezionato: string
}

const props = defineProps<Props>()
const { t } = useI18n()

const key = ref(0)
const loadingPage = ref(false)
const meseSelezionato = ref('')
const items = ref<any[]>([])
const seriesCc = ref<any>({})
const seriesOfc = ref<any>({})
const seriesScrap = ref<any>({})
const seriesOoe = ref<any>({})
const seriesFtr = ref<any>({})
const seriesCapacity = ref<any>({})
const scrapStorageItems = ref<any>({})

const vuetifyTheme = useTheme()
const ccConfig = computed(() => getLineColumnChartCustomConfig(vuetifyTheme.current.value))
const ofcConfig = computed(() => getLineColumnChartCustomConfig(vuetifyTheme.current.value))
const scrapConfig = computed(() => getLineBubleChartCustomConfig(vuetifyTheme.current.value))
const ooeConfig = computed(() => getColumnChartCustomConfig(vuetifyTheme.current.value))
const ftrConfig = computed(() => getColumnChartCustomConfig(vuetifyTheme.current.value))
const scrapStorageConfig = computed(() => getLineBubleChartCustomConfig(vuetifyTheme.current.value))
const capacityConfig = computed(() => getColumnChartCustom2Config(vuetifyTheme.current.value))

// Configurazioni custom stili grafici
ftrConfig.value.colors = ['#c06ce8']
ftrConfig.value.dataLabels.style.colors = ['#409E1CFF']
capacityConfig.value.colors = ['#03b642']

ccConfig.value.yaxis[0].title.text = 'Kg'
ccConfig.value.yaxis[1].title.text = 'Ckm'
ofcConfig.value.yaxis[0].title.text = 'KfKm'
ofcConfig.value.yaxis[1].title.text = 'Ckm'

const loadItems = async () => {
  if (!props.periodoData) return

  loadingPage.value = true
  try {
    const { data: resultData } = await useApi<any>(createUrl('/production/plant/production/', {
      query: { periodo: props.periodoData },
    }))

    items.value = sortBy(resultData.value || [], ['posizione'])

    if (props.meseSelezionato) {
      const parsedDate = new Date(props.meseSelezionato)
      if (!isNaN(parsedDate.getTime())) {
        meseSelezionato.value = parsedDate.toLocaleString('en', { month: 'short' })
      }
    }

    key.value = 1

    // Chiamate parallele per ottimizzare i tempi di caricamento
    const [
      { data: datiProduzione },
      { data: datiScreep },
      { data: datiOoe },
      { data: datiFtr },
      { data: datiCapacity },
      { data: datiScrapStage }
    ] = await Promise.all([
      useApi<any>(createUrl('/production/plant/datiProduttivi/', { query: { periodo: props.periodoData } })),
      useApi<any>(createUrl('/production/plant/datiScreep/', { query: { periodo: props.periodoData } })),
      useApi<any>(createUrl('/production/plant/datiOoe/', { query: { periodo: props.periodoData } })),
      useApi<any>(createUrl('/production/plant/datiFtr/', { query: { periodo: props.periodoData } })),
      useApi<any>(createUrl('/production/plant/datiCapacity/', { query: { periodo: props.periodoData } })),
      useApi<any>(createUrl('/production/plant/datiScreepStage/', { query: { periodo: props.periodoData } }))
    ])

    ccConfig.value.xaxis.categories = datiProduzione.value?.series?.categoryes || []
    ofcConfig.value.xaxis.categories = datiProduzione.value?.series?.categoryes || []
    seriesCc.value = datiProduzione.value?.series?.cc || {}
    seriesOfc.value = datiProduzione.value?.series?.ofc || {}

    seriesScrap.value = datiScreep.value?.series || {}
    scrapConfig.value.xaxis.categories = datiScreep.value?.categories || []

    seriesOoe.value = datiOoe.value?.series || {}
    ooeConfig.value.xaxis.categories = datiOoe.value?.categories || []

    seriesFtr.value = datiFtr.value?.series || {}
    ftrConfig.value.xaxis.categories = datiFtr.value?.categories || []

    seriesCapacity.value = datiCapacity.value?.series || {}
    capacityConfig.value.xaxis.categories = datiCapacity.value?.categories || []

    scrapStorageConfig.value.dataLabels.enabled = false
    scrapStorageConfig.value.stroke.width = [4, 4, 4, 4, 4]
    scrapStorageConfig.value.stroke.curve = 'straight'
    scrapStorageConfig.value.yaxis.title.text = ''
    scrapStorageConfig.value.markers.size = 1
    scrapStorageConfig.value.colors = []
    scrapStorageItems.value = datiScrapStage.value?.series || {}
    scrapStorageConfig.value.xaxis.categories = datiScrapStage.value?.categories || []

    key.value += 1
  } catch (e) {
    console.error("Errore durante il caricamento dei dati della dashboard", e)
  } finally {
    loadingPage.value = false
  }
}

// Caricamento iniziale
loadItems()

watch(() => props.periodoData, () => {
  loadItems()
})
</script>

<template>
  <div class="production-dashboard pa-1">

    <VCard class="mb-6 elevation-1 border-card">
      <div class="py-3 px-4 bg-header d-flex align-center gap-2 border-b">
        <VIcon icon="tabler-table" color="primary" size="20" />
        <span class="text-subtitle-1 font-weight-bold text-high-emphasis">
          {{ `${$t('Label.Production Summary for')} - ${props.periodoData}` }}
        </span>
      </div>

      <VTable density="compact" class="text-no-wrap elegant-production-table">
        <thead>
        <tr>
          <th class="border-r" />
          <th colspan="3" class="text-center bg-info text-white font-weight-bold border-r header-group-th">
            OFC
          </th>
          <th colspan="2" class="text-center bg-primary text-white font-weight-bold header-group-th">
            Cc
          </th>
        </tr>
        <tr class="sub-header-row">
          <th class="border-r font-weight-bold">Mese</th>
          <th>Ckm</th>
          <th>Kfkm</th>
          <th class="border-r">Afc</th>
          <th>Ckm</th>
          <th class="font-weight-bold">Kg</th>
        </tr>
        </thead>

        <tbody>
        <tr
          :key="key"
          v-for="item in items"
          :class="item.mese === meseSelezionato ? 'bg-critico-row' : ''"
        >
          <td :class="item.mese === 'Total' ? 'bg-total font-weight-bold border-r' : 'border-r'">
            {{ item.mese }}
          </td>
          <td :class="item.mese === 'Total' ? 'bg-total font-weight-bold' : ''">{{ item.Ckm_ofc }}</td>
          <td :class="item.mese === 'Total' ? 'bg-total font-weight-bold' : ''">{{ item.Fkm_ofc }}</td>
          <td :class="item.mese === 'Total' ? 'bg-total font-weight-bold border-r' : 'border-r'">{{ item.Ofc_afc }}</td>
          <td :class="item.mese === 'Total' ? 'bg-total font-weight-bold' : ''">{{ item.Cc_ckm }}</td>
          <td :class="item.mese === 'Total' ? 'bg-total font-weight-bold' : ''">{{ item?.Cc_kg }}</td>
        </tr>
        </tbody>
      </VTable>
    </VCard>

    <VRow>
      <VCol cols="12" md="6">
        <VCard variant="outlined" class="chart-section-card h-100">
          <div class="py-2.5 px-4 bg-header border-b d-flex align-center gap-2">
            <VIcon icon="tabler-chart-line" color="primary" size="18" />
            <span class="text-caption font-weight-bold text-high-emphasis">{{ $t('Label.Rame-Ckm-Kg-Produzione') }}</span>
          </div>
          <VCardText class="pa-2">
            <VueApexCharts :key="key" type="line" height="320" :options="ccConfig" :series="seriesCc" />
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" md="6">
        <VCard variant="outlined" class="chart-section-card h-100">
          <div class="py-2.5 px-4 bg-header border-b d-flex align-center gap-2">
            <VIcon icon="tabler-chart-arrows" color="primary" size="18" />
            <span class="text-caption font-weight-bold text-high-emphasis">{{ $t('Label.Ottico-Ckm-KfKm-Produzione') }}</span>
          </div>
          <VCardText class="pa-2">
            <VueApexCharts :key="key" type="line" height="320" :options="ofcConfig" :series="seriesOfc" />
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" md="6">
        <VCard variant="outlined" class="chart-section-card h-100">
          <div class="py-2.5 px-4 bg-header border-b d-flex align-center gap-2">
            <VIcon icon="tabler-coin-euro" color="warning" size="18" />
            <span class="text-caption font-weight-bold text-high-emphasis">{{ $t('Label.Scrap-Value-By-Stage') }} €</span>
          </div>
          <VCardText class="pa-2">
            <VueApexCharts :key="key" type="line" height="320" :options="scrapStorageConfig" :series="scrapStorageItems" />
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" md="6">
        <VCard variant="outlined" class="chart-section-card h-100">
          <div class="py-2.5 px-4 bg-header border-b d-flex align-center gap-2">
            <VIcon icon="tabler-trash-x" color="error" size="18" />
            <span class="text-caption font-weight-bold text-high-emphasis">{{ $t('Label.Plant-Scrap') }} %</span>
          </div>
          <VCardText class="pa-2">
            <VueApexCharts :key="key" type="line" height="320" :options="scrapConfig" :series="seriesScrap" />
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" md="6">
        <VCard variant="outlined" class="chart-section-card h-100">
          <div class="py-2.5 px-4 bg-header border-b d-flex align-center gap-2">
            <VIcon icon="tabler-chart-bar" color="success" size="18" />
            <span class="text-caption font-weight-bold text-high-emphasis">{{ $t('Label.Oee') }} %</span>
          </div>
          <VCardText class="pa-2">
            <VueApexCharts :key="key" type="bar" height="320" :options="ooeConfig" :series="seriesOoe" />
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" md="6">
        <VCard variant="outlined" class="chart-section-card h-100">
          <div class="py-2.5 px-4 bg-header border-b d-flex align-center gap-2">
            <VIcon icon="tabler-trending-up" color="secondary" size="18" />
            <span class="text-caption font-weight-bold text-high-emphasis">{{ $t('Label.Overall Ftr') }} %</span>
          </div>
          <VCardText class="pa-2">
            <VueApexCharts :key="key" type="bar" height="320" :options="ftrConfig" :series="seriesFtr" />
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" md="6">
        <VCard variant="outlined" class="chart-section-card h-100">
          <div class="py-2.5 px-4 bg-header border-b d-flex align-center gap-2">
            <VIcon icon="tabler-bolt" color="info" size="18" />
            <span class="text-caption font-weight-bold text-high-emphasis">{{ $t('Label.Ofc-Capacity') }}</span>
          </div>
          <VCardText class="pa-2">
            <VueApexCharts :key="key" type="bar" height="320" :options="capacityConfig" :series="seriesCapacity" />
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <LoadingStandBy v-model="loadingPage" />
  </div>
</template>

<style scoped lang="scss">
.production-dashboard {
  .gap-2 { gap: 8px; }

  .border-b {
    border-bottom: 1px solid rgba(var(--v-border-color), 0.08) !important;
  }
  .border-r {
    border-right: 1px solid rgba(var(--v-border-color), 0.08) !important;
  }

  .bg-header {
    background-color: rgba(var(--v-theme-on-surface), 0.02);
  }

  .border-card {
    border: 1px solid rgba(var(--v-border-color), 0.12) !important;
    border-radius: 8px;
  }

  .chart-section-card {
    border-radius: 8px;
    background-color: rgb(var(--v-theme-surface));
    border: 1px solid rgba(var(--v-border-color), 0.08);
  }

  // Personalizzazione ed eleganza VTable
  .elegant-production-table {
    .header-group-th {
      font-size: 0.85rem !important;
      letter-spacing: 0.5px;
      height: 38px !important;
    }

    .sub-header-row th {
      font-size: 0.75rem !important;
      text-transform: uppercase;
      color: rgba(var(--v-theme-on-surface), 0.6) !important;
      background-color: rgba(var(--v-theme-on-surface), 0.01) !important;
      height: 34px !important;
    }

    tbody tr {
      height: 34px !important;
      transition: background-color 0.15s ease;

      &:hover {
        background-color: rgba(var(--v-theme-primary), 0.02) !important;
      }
    }

    // Classi per la riga Totale e riga Selezionata Critica
    .bg-total {
      background-color: rgba(var(--v-theme-on-surface), 0.05) !important;
      color: rgb(var(--v-theme-on-surface)) !important;
    }

    .bg-critico-row {
      background-color: rgba(var(--v-theme-warning), 0.08) !important;
      td {
        color: rgb(var(--v-theme-warning)) !important;
        font-weight: 600;
      }
    }
  }
}
</style>
