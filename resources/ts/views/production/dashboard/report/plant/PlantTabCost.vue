<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { useTheme } from 'vuetify'
import {ref} from 'vue'
import {
  getLineBubleChartCustomConfig,
  getLineChartCustomConfig,
  getLineColumnChartCustomConfig,
} from '@core/libs/apex-chart/apexCharConfig'

const props = defineProps<Props>()
const vuetifyTheme = useTheme()
const balanceChartConfig = computed(() => getLineChartCustomConfig(vuetifyTheme.current.value))
const otticoChartConfig = computed(() => getLineBubleChartCustomConfig(vuetifyTheme.current.value))
const rameChartConfig = computed(() => getLineBubleChartCustomConfig(vuetifyTheme.current.value))
const powerConfig = computed(() => getLineColumnChartCustomConfig(vuetifyTheme.current.value))
const methaneConfig = computed(() => getLineColumnChartCustomConfig(vuetifyTheme.current.value))

otticoChartConfig.value.dataLabels.enabled = false
otticoChartConfig.value.stroke.width = [4, 4, 4, 4]
otticoChartConfig.value.stroke.curve = 'straight'
otticoChartConfig.value.yaxis.title.text = ''
otticoChartConfig.value.markers.size = 1
otticoChartConfig.value.colors = []
rameChartConfig.value.dataLabels.enabled = false
rameChartConfig.value.stroke.width = [4, 4, 4, 4]
rameChartConfig.value.stroke.curve = 'straight'
rameChartConfig.value.yaxis.title.text = ''
rameChartConfig.value.markers.size = 1
rameChartConfig.value.colors = []
powerConfig.value.yaxis[0].title.text = 'Kwh'
powerConfig.value.yaxis[1].title.text = 'Cost'
powerConfig.value.dataLabels = {
  enabled: true,
  enabledOnSeries: [0, 1],
  formatter(val, opts) {
    if (opts.w.config.series[opts.seriesIndex].type === 'line')
      return `${val} €`
    else
      return val
  },
  textAnchor: 'middle',
  distributed: false,
  offsetX: 0,
  offsetY: 0,
  style: {
    fontSize: '13px',
    fontFamily: 'Helvetica, Arial, sans-serif',
    fontWeight: 'bold',
  },
  background: {
    enabled: true,
    foreColor: '#fff',
    padding: 4,
    borderRadius: 2,
    borderWidth: 1,
    borderColor: '#fff',
    opacity: 0.9,
    dropShadow: {
      enabled: false,
      top: -10,
      left: 1,
      blur: 1,
      color: '#000',
      opacity: 0.45,
    },
  },
  dropShadow: {
    enabled: true,
    top: 1,
    left: 1,
    blur: 10,
    color: '#000',
    opacity: 0.45,
  },
}
powerConfig.value.colors = ['#bc26cf', '#5ccd32']
methaneConfig.value.yaxis[0].title.text = 'Smc'
methaneConfig.value.yaxis[1].title.text = 'Cost'
methaneConfig.value.colors = ['#cb3234', '#5ccd32']
methaneConfig.value.xaxis.categories = []
rameChartConfig.value.dataLabels = otticoChartConfig.value.dataLabels = {
    enabled: true,
    enabledOnSeries: [0, 1, 2, 3],
    formatter(val, opts) {
      if (opts.w.config.series[opts.seriesIndex].type === 'line')
        return `${val} €`
      else
        return `${val} €`
    },
    textAnchor: 'middle',
    distributed: false,
    offsetX: 0,
    offsetY: 0,
    style: {
      fontSize: '13px',
      fontFamily: 'Helvetica, Arial, sans-serif',
      fontWeight: 'bold',
    },
    background: {
      enabled: true,
      foreColor: '#fff',
      padding: 4,
      borderRadius: 2,
      borderWidth: 1,
      borderColor: '#fff',
      opacity: 0.9,
      dropShadow: {
        enabled: false,
        top: -10,
        left: 1,
        blur: 1,
        color: '#000',
        opacity: 0.45,
      },
    },
    dropShadow: {
      enabled: true,
      top: 1,
      left: 1,
      blur: 10,
      color: '#000',
      opacity: 0.45,
    },
  }
methaneConfig.value.dataLabels = {
  enabled: true,
  enabledOnSeries: [0, 1],
  formatter(val, opts) {
    if (opts.w.config.series[opts.seriesIndex].type === 'line')
      return `${val} €`
    else
      return val
  },
  textAnchor: 'middle',
  distributed: false,
  offsetX: 0,
  offsetY: 0,
  style: {
    fontSize: '13px',
    fontFamily: 'Helvetica, Arial, sans-serif',
    fontWeight: 'bold',
  },
  background: {
    enabled: true,
    foreColor: '#fff',
    padding: 4,
    borderRadius: 2,
    borderWidth: 1,
    borderColor: '#fff',
    opacity: 0.9,
    dropShadow: {
      enabled: false,
      top: -10,
      left: 1,
      blur: 1,
      color: '#000',
      opacity: 0.45,
    },
  },
  dropShadow: {
    enabled: true,
    top: 1,
    left: 1,
    blur: 10,
    color: '#000',
    opacity: 0.45,
  },
}

interface Props {
  periodoData: string
  meseSelezionato: string
}

const { t } = useI18n()
const key = ref(1)
const loadingPage = ref(false)
const items = ref({})
const ottcoItems = ref({})
const rameItems = ref({})
const seriesPower = ref({})
const seriesMethane = ref({})
const view = ref(false)

const loadItems = async () => {
  view.value = false
  loadingPage.value = true

  const { data: resultCost } = await useApi<any>(createUrl('/production/plant/datiCosti/', {
    query: {
      periodo: props.periodoData,
    },
  }))

  balanceChartConfig.value.xaxis.categories = resultCost.value.categories
  items.value = resultCost.value.series

  ottcoItems.value = resultCost.value.seriesOttico
  otticoChartConfig.value.xaxis.categories = resultCost.value.categories

  rameItems.value = resultCost.value.seriesRame
  rameChartConfig.value.xaxis.categories = resultCost.value.categories

  seriesPower.value = resultCost.value.seriesCost.power
  powerConfig.value.xaxis.categories = resultCost.value.seriesCost.categoryes

  seriesMethane.value = resultCost.value.seriesCost.methane
  methaneConfig.value.xaxis.categories = resultCost.value.seriesCost.categoryes

  key.value = key.value + 1
  view.value = true
  loadingPage.value = false
}

loadItems()
watch(props, () => {
  loadItems()
})
</script>

<template>
  <VRow class="mt-4">
    <VCol v-if="view" cols="12">
      <VCard :title="`${$t('Label.Costo-Per-Ckm-Euro')} ( OFC+CC )` ">
        <VueApexCharts
          :key="key"
          type="bar"
          height="350"
          :options="balanceChartConfig"
          :series="items"
        />
      </VCard>
    </VCol>
  </VRow>
  <VRow class="mt-4">
    <VCol v-if="view" cols="6">
      <VCard :title="`${$t('Label.Ofc-Costo-Per-Fkm-Euro')}` ">
        <VueApexCharts
          :key="key"
          type="bar"
          height="350"
          :options="balanceChartConfig"
          :series="ottcoItems"
        />
      </VCard>
    </VCol>
    <VCol v-if="view" cols="6">
      <VCard :title="`${$t('Label.Cc-Costo-Per-Ckm-Euro')}` ">
        <VueApexCharts
          :key="key"
          type="bar"
          height="350"
          :options="balanceChartConfig"
          :series="rameItems"
        />
      </VCard>
    </VCol>
  </VRow>
  <VRow class="mt-4">
    <VCol v-if="view" cols="6">
      <VCard :title="`${$t('Label.Ofc-Costo-Per-Fkm-Euro')}`">
        <VueApexCharts
          :key="key"
          type="line"
          height="350"
          :options="otticoChartConfig"
          :series="ottcoItems"
        />
      </VCard>
    </VCol>
    <VCol v-if="view" cols="6">
      <VCard :title="`${$t('Label.Cc-Costo-Per-Ckm-Euro')}`">
        <VueApexCharts
          :key="key"
          type="line"
          height="350"
          :options="rameChartConfig"
          :series="rameItems"
        />
      </VCard>
    </VCol>
  </VRow>
  <VRow class="mt-4">
    <VCol cols="6">
      <VCard :title="`${$t('Label.Costo-Consumo-Energia')} (Ofc + Ccc)`">
        <VueApexCharts
          :key="key"
          type="line"
          height="350"
          :options="powerConfig"
          :series="seriesPower"
        />
      </VCard>
    </VCol>

    <VCol cols="6">
      <VCard :title="`${$t('Label.Costo-Consumo-Matano')} (Ofc + Ccc)`">
        <VueApexCharts
          :key="key"
          type="line"
          height="350"
          :options="methaneConfig"
          :series="seriesMethane"
        />
      </VCard>
    </VCol>
  </VRow>
  <LoadingStandBy v-model="loadingPage"></LoadingStandBy>
</template>

<style scoped lang="scss">

</style>
