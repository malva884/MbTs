<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { useTheme } from 'vuetify'
import {
  getLineChartCustom3Config,
} from '@core/libs/apex-chart/apexCharConfig'

interface Props {
  periodoData: string
  meseSelezionato: string
}

const props = defineProps<Props>()
const { t } = useI18n()
const key = ref(0)
const meseSelezionato = ref('')
const items = ref({})
const labourList = ref({})

const vuetifyTheme = useTheme()
const overtimeConfig = computed(() => getLineChartCustom3Config(vuetifyTheme.current.value))

overtimeConfig.value.colors = ['#c71d30', '#193aef']

const loadItems = async () => {
  const { data: resultData } = await useApi<any>(createUrl('/production/plant/datiOvertime/', {
    query: {
      periodo: props.periodoData,
    },
  }))

  const { data: resultTest } = await useApi<any>(createUrl('/production/plant/labourCost/', {
    query: {
      periodo: props.periodoData,
    },
  }))

  items.value = resultData.value.series
  overtimeConfig.value.xaxis.categories = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
  labourList.value = resultTest.value
  console.log( labourList.value)
  key.value = key.value = 1
}

let euro = new Intl.NumberFormat('it-IT', {
  style: 'currency',
  currency: 'EUR',
})

loadItems()
watch(props, () => {
  loadItems()
})
</script>

<template>
  <VRow class="mt-4">
    <VCol cols="6">
      <VCard :title="`${$t('Label.Overtime')}`">
        <VueApexCharts
          :key="key"
          type="line"
          height="350"
          :options="overtimeConfig"
          :series="items"
        />
      </VCard>
    </VCol>
    <!--VCol cols="12">
      <VCard :title="`${$t('Label.Cost')}`">
        <VTable
          density="compact"
          class="text-no-wrap"
        >
          <tbody>
          <tr>
            <td>
            </td>
            <td>
              Q1
            </td>
            <td>
              Q2
            </td>
            <td>
              Q3
            </td>
            <td>
              Q4
            </td>
            <td>
              YTD AGP
            </td>
            <td>
              Q1
            </td>
            <td>
              Q2
            </td>
            <td>
              Q3
            </td>
            <td>
              Q4
            </td>
            <td>
              YTD ACTUAL
            </td>
            <td>
            </td>
          </tr>
          <tr>
            <td>
              Revenues
            </td>
            <td>
              {{ euro.format(labourList[1]?.reavenuesLastYear) }}
            </td>
            <td>
              {{ euro.format(labourList[2]?.reavenuesLastYear) }}
            </td>
            <td>
              {{ euro.format(labourList[3]?.reavenuesLastYear) }}
            </td>
            <td>
              {{ euro.format(labourList[4]?.reavenuesLastYear) }}
            </td>
            <td>
              {{ euro.format(labourList[5]?.YTF_AGP.bR) }}
            </td>
            <td>
              {{ euro.format(labourList[1]?.reavenuesYear) }}
            </td>
            <td>
              {{ euro.format(labourList[2]?.reavenuesYear) }}
            </td>
            <td>
              {{ euro.format(labourList[3]?.reavenuesYear) }}
            </td>
            <td>
              {{ euro.format(labourList[4]?.reavenuesYear) }}
            </td>
            <td>
              {{ euro.format(labourList[5]?.YTF_ACTUAL.bR) }}
            </td>
            <td>
              {{ euro.format(labourList[5]?.YTF_AGP.bR - labourList[5].YTF_ACTUAL.bR) }}
            </td>
          </tr>
          <tr>
            <td>
              Revenues OFC
            </td>
            <td>
              {{ euro.format(labourList[1]?.reavenuesLastYearOfc) }}
            </td>
            <td>
              {{ euro.format(labourList[2]?.reavenuesLastYearOfc) }}
            </td>
            <td>
              {{ euro.format(labourList[3]?.reavenuesLastYearOfc) }}
            </td>
            <td>
              {{ euro.format(labourList[4]?.reavenuesLastYearOfc) }}
            </td>
            <td>
              {{ euro.format(labourList[5]?.YTF_AGP.bOFC) }}
            </td>
            <td>
              {{ euro.format(labourList[1]?.reavenuesYearOfc) }}
            </td>
            <td>
              {{ euro.format(labourList[2]?.reavenuesYearOfc) }}
            </td>
            <td>
              {{ euro.format(labourList[3]?.reavenuesYearOfc) }}
            </td>
            <td>
              {{ euro.format(labourList[4]?.reavenuesYearOfc) }}
            </td>
            <td>
              {{ euro.format(labourList[5]?.YTF_ACTUAL.bOFC) }}
            </td>
            <td>
              {{ euro.format(labourList[5]?.YTF_AGP.bOFC - labourList[5].YTF_ACTUAL.bOFC) }}
            </td>
          </tr>
          <tr>
            <td>
              Revenues Copper
            </td>
            <td>
              {{ euro.format(labourList[1]?.reavenuesLastYearCc) }}
            </td>
            <td>
              {{ euro.format(labourList[2]?.reavenuesLastYearCc) }}
            </td>
            <td>
              {{ euro.format(labourList[3]?.reavenuesLastYearCc) }}
            </td>
            <td>
              {{ euro.format(labourList[4]?.reavenuesLastYearCc) }}
            </td>
            <td>
              {{ euro.format(labourList[5]?.YTF_AGP.bCC) }}
            </td>
            <td>
              {{ euro.format(labourList[1]?.reavenuesYearCc) }}
            </td>
            <td>
              {{ euro.format(labourList[2]?.reavenuesYearCc) }}
            </td>
            <td>
              {{ euro.format(labourList[3]?.reavenuesYearCc) }}
            </td>
            <td>
              {{ euro.format(labourList[4]?.reavenuesYearCc) }}
            </td>
            <td>
              {{ euro.format(labourList[5]?.YTF_ACTUAL.bCC) }}
            </td>
            <td>
              {{ euro.format(labourList[5]?.YTF_AGP.bCC - labourList[5].YTF_ACTUAL.bCC) }}
            </td>
          </tr>
          <tr>
            <td>
              Labour Cost
            </td>
            <td>
              {{ euro.format(labourList[1]?.labourLastYear) }}
            </td>
            <td>
              {{ euro.format(labourList[2]?.labourLastYear) }}
            </td>
            <td>
              {{ euro.format(labourList[3]?.labourLastYear) }}
            </td>
            <td>
              {{ euro.format(labourList[4]?.labourLastYear) }}
            </td>
            <td>
              {{ euro.format(labourList[5]?.YTF_AGP.bCC) }}
            </td>
            <td>
              {{ euro.format(labourList[1]?.labourYear) }}
            </td>
            <td>
              {{ euro.format(labourList[2]?.labourYear) }}
            </td>
            <td>
              {{ euro.format(labourList[3].labourYear) }}
            </td>
            <td>
              {{ euro.format(labourList[4].labourYear) }}
            </td>
            <td>
              {{ euro.format(labourList[5].YTF_ACTUAL.bL) }}
            </td>
            <td>
              {{ euro.format(labourList[5].YTF_AGP.bCC - labourList[5].YTF_ACTUAL.bCC) }}
            </td>
          </tr>
          </tbody>
        </VTable>
      </VCard>
    </VCol -->
  </VRow>
</template>

<style scoped lang="scss">

</style>
