<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { sortBy } from 'lodash'
import { useTheme } from 'vuetify'
import {
  getColumnChartCustom2Config,
  getColumnChartCustomConfig,
  getLineBubleChartCustomConfig,
  getLineColumnChartCustomConfig,
} from '@core/libs/apex-chart/apexCharConfig'
import { ref, watch } from 'vue'
import { useMagicKeys, whenever } from '@vueuse/core'

interface Props {
  periodoData: string
  meseSelezionato: string
}

const { shift_h, current } = useMagicKeys()
const props = defineProps<Props>()
const { t } = useI18n()
const key = ref(0)
const loadingPage = ref(false)
const meseSelezionato = ref('')
const items = ref({})
const seriesCc = ref({})
const seriesOfc = ref({})
const seriesScrap = ref({})
const seriesOoe = ref({})
const seriesFtr = ref({})
const seriesCapacity = ref({})
const scrapStorageItems = ref({})

const vuetifyTheme = useTheme()
const ccConfig = computed(() => getLineColumnChartCustomConfig(vuetifyTheme.current.value))
const ofcConfig = computed(() => getLineColumnChartCustomConfig(vuetifyTheme.current.value))
const scrapConfig = computed(() => getLineBubleChartCustomConfig(vuetifyTheme.current.value))
const ooeConfig = computed(() => getColumnChartCustomConfig(vuetifyTheme.current.value))
const ftrConfig = computed(() => getColumnChartCustomConfig(vuetifyTheme.current.value))
const scrapStorageConfig = computed(() => getLineBubleChartCustomConfig(vuetifyTheme.current.value))

ccConfig.value.title.text = 'pippo'
ftrConfig.value.colors = ['#c06ce8']
ftrConfig.value.dataLabels.style.colors = ['#409E1CFF']

const capacityConfig = computed(() => getColumnChartCustom2Config(vuetifyTheme.current.value))

capacityConfig.value.colors = ['#03b642']
//capacityConfig.value.dataLabels.style.colors = ['#26c23c']


ccConfig.value.yaxis[0].title.text = 'Kg'
ccConfig.value.yaxis[1].title.text = 'Ckm'
ofcConfig.value.yaxis[0].title.text = 'KfKm'
ofcConfig.value.yaxis[1].title.text = 'Ckm'

const loadItems = async () => {
  loadingPage.value = true

  const { data: resultData } = await useApi<any>(createUrl('/production/plant/production/', {
    query: {
      periodo: props.periodoData,
    },
  }))

  items.value = sortBy(resultData.value, ['posizione'])
  meseSelezionato.value = new Date(props.meseSelezionato).toLocaleString('en', { month: 'short' })
  key.value = key.value = 1

  const { data: datiProduzione } = await useApi<any>(createUrl('/production/plant/datiProduttivi/', {
    query: {
      periodo: props.periodoData,
    },
  }))

  const { data: datiScreep } = await useApi<any>(createUrl('/production/plant/datiScreep/', {
    query: {
      periodo: props.periodoData,
    },
  }))

  const { data: datiOoe } = await useApi<any>(createUrl('/production/plant/datiOoe/', {
    query: {
      periodo: props.periodoData,
    },
  }))

  const { data: datiFtr } = await useApi<any>(createUrl('/production/plant/datiFtr/', {
    query: {
      periodo: props.periodoData,
    },
  }))

  const { data: datiCapacity } = await useApi<any>(createUrl('/production/plant/datiCapacity/', {
    query: {
      periodo: props.periodoData,
    },
  }))

  const { data: datiScrapStage } = await useApi<any>(createUrl('/production/plant/datiScreepStage/', {
    query: {
      periodo: props.periodoData,
    },
  }))

  ccConfig.value.xaxis.categories = datiProduzione.value.series.categoryes
  ofcConfig.value.xaxis.categories = datiProduzione.value.series.categoryes
  seriesCc.value = datiProduzione.value.series.cc
  seriesOfc.value = datiProduzione.value.series.ofc
  seriesScrap.value = datiScreep.value.series
  scrapConfig.value.xaxis.categories = datiScreep.value.categories
  seriesOoe.value = datiOoe.value.series
  ooeConfig.value.xaxis.categories = datiOoe.value.categories
  seriesFtr.value = datiFtr.value.series
  ftrConfig.value.xaxis.categories = datiFtr.value.categories
  seriesCapacity.value = datiCapacity.value.series
  capacityConfig.value.xaxis.categories = datiCapacity.value.categories

  scrapStorageConfig.value.dataLabels.enabled = false
  scrapStorageConfig.value.stroke.width = [4, 4, 4, 4, 4]
  scrapStorageConfig.value.stroke.curve = 'straight'
  scrapStorageConfig.value.yaxis.title.text = ''
  scrapStorageConfig.value.markers.size = 1
  scrapStorageConfig.value.colors = []
  scrapStorageItems.value = datiScrapStage.value.series
  scrapStorageConfig.value.xaxis.categories = datiScrapStage.value.categories

  key.value = key.value + 1
  loadingPage.value = false
}

loadItems()

watch(props, () => {
  loadItems()
})
whenever(shift_h, pressed => console.log(pressed))
</script>

<template>
  <VCard :title="`${$t('Label.Production Summary for')} - ${props.periodoData}`">
    {{ current }}
    {{ shift_h }}

    <VTable
      density="compact"
      class="text-no-wrap"
    >
      <thead>
      <tr>
        <th />
        <th
          colspan="3"
          class="text-center bg-info"
        >
          <h3 class="text-white">
            OFC
          </h3>
        </th>
        <th
          colspan="2"
          class="text-center bg-primary"
        >
          <h3 class="text-white">
            Cc
          </h3>
        </th>
      </tr>
      <tr>
        <th />
        <th>
          Ckm
        </th>
        <th>
          Kfkm
        </th>
        <th>
          Afc
        </th>
        <th>
          Ckm
        </th>
        <th>
         Kg
        </th>
      </tr>
      </thead>

      <tbody>
      <tr
        :key="key"
        v-for="item in items"
        :class="item.mese === meseSelezionato ? 'bg-critico' : ''"
      >
        <td v-if="item.mese === 'Total'" class="bg-total">{{item.mese}}</td>
        <td v-else>{{item.mese}}</td>
        <td v-if="item.mese === 'Total'" class="bg-total">{{ item.Ckm_ofc}}</td>
        <td v-else>{{ item.Ckm_ofc}}</td>
        <td v-if="item.mese === 'Total'" class="bg-total">{{ item.Fkm_ofc}}</td>
        <td v-else>{{ item.Fkm_ofc}}</td>
        <td v-if="item.mese === 'Total'" class="bg-total">{{ item.Ofc_afc}}</td>
        <td v-else>{{ item.Ofc_afc}}</td>
        <td v-if="item.mese === 'Total'" class="bg-total">{{ item.Cc_ckm}}</td>
        <td v-else>{{ item.Cc_ckm}}</td>
        <td v-if="item.mese === 'Total'" class="bg-total">{{ item?.Cc_kg}}</td>
        <td v-else>{{ item?.Cc_kg}}</td>
      </tr>
      </tbody>
    </VTable>
  </VCard>

  <VRow class="mt-4">
    <VCol cols="6">
      <VCard :title="`${$t('Label.Rame-Ckm-Kg-Produzione')}`">
        <VueApexCharts
          :key="key"
          type="line"
          height="350"
          :options="ccConfig"
          :series="seriesCc"
        />
      </VCard>
    </VCol>
    <VCol cols="6">
      <VCard :title="`${$t('Label.Ottico-Ckm-KfKm-Produzione')}`">
        <VueApexCharts
          :key="key"
          type="line"
          height="350"
          :options="ofcConfig"
          :series="seriesOfc"
        />
      </VCard>
    </VCol>
  </VRow>

  <VRow class="mt-4">
    <VCol cols="6">
      <VCard :title="`${$t('Label.Scrap-Value-By-Stage')}`">
        <VueApexCharts
          :key="key"
          type="line"
          height="350"
          :options="scrapStorageConfig"
          :series="scrapStorageItems"
        />
      </VCard>
    </VCol>
    <VCol cols="6">
      <VCard :title="`${$t('Label.Plant-Scrap')} %`">
        <VueApexCharts
          :key="key"
          type="line"
          height="350"
          :options="scrapConfig"
          :series="seriesScrap"
        />
      </VCard>
    </VCol>
    <VCol cols="6">
      <VCard :title="`${$t('Label.Oee')} %`">
        <VueApexCharts
          :key="key"
          type="bar"
          height="350"
          :options="ooeConfig"
          :series="seriesOoe"
        />
      </VCard>
    </VCol>
    <VCol cols="6">
      <VCard :title="`${$t('Label.Overall Ftr')} %`">
        <VueApexCharts
          :key="key"
          type="bar"
          height="350"
          :options="ftrConfig"
          :series="seriesFtr"
        />
      </VCard>
    </VCol>
    <VCol cols="6">
      <VCard :title="`${$t('Label.Ofc-Capacity')}`">
        <VueApexCharts
          :key="key"
          type="bar"
          height="350"
          :options="capacityConfig"
          :series="seriesCapacity"
        />
      </VCard>
    </VCol>
  </VRow>
  <LoadingStandBy v-model="loadingPage"></LoadingStandBy>
</template>

<style scoped lang="scss">

</style>
