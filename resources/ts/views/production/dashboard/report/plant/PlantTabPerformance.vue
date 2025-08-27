<script setup lang="ts">
import { useI18n } from 'vue-i18n'

interface Props {
  periodoData: string
}

const props = defineProps<Props>()
const { t } = useI18n()
const key = ref(1)
const productsData = ref({})
const dispatchsData = ref({})
const reveniueData = ref({})

const loadItems = async () => {
  const { data: resultOfcData } = await useApi<any>(createUrl('/production/plant/performance/', {
    query: {
      periodo: props.periodoData,
    },
  }))

  productsData.value = resultOfcData.value.produzione
  dispatchsData.value = resultOfcData.value.spedito
  reveniueData.value = resultOfcData.value.fatturato

  key.value = key.value + 1
}

loadItems()

const getChartConfig = (min: number, max: number) => {
  const borderColor = 'rgba(var(--v-border-color), var(--v-border-opacity))'

  const tmp = {
    chart: {
      height: 10,
      type: 'bar',
      toolbar: {
        show: false,
      },
    },

    plotOptions: {
      bar: {
        horizontal: true,
        barHeight: '100%',
        distributed: true,
        startingShape: 'rounded',
        borderRadius: 7,
      },
    },

    colors: [
      function ({ value }) {
        if (value > 0)
          return '#64d94f'
        else
          return '#e70a24'
      },
    ],
    grid: {
      borderColor,
      strokeDashArray: 1,
      xaxis: {
        lines: {
          show: true,
        },
      },
      yaxis: {
        lines: {
          show: false,
        },
      },
      padding: {
        top: 0,
        bottom: 0,
      },
    },

    dataLabels: {
      enabled: true,
      style: {
        colors: ['#fff'],
        fontWeight: 200,
        fontSize: '10px',

      },
      offsetX: 0,
      dropShadow: {
        enabled: false,
      },
      formatter(val: string, opt: any) {
        return val
      },
    },
    xaxis: {
      categories: [''],
      axisBorder: {
        show: false,
      },
      axisTicks: {
        show: false,
      },
      labels: {
        style: {
          colors: 'rgba(var(--v-theme-on-background), var(--v-disabled-opacity))',
          fontSize: '9px',
        },
      },
      max: 100,
      min: -100,
    },

    yaxis: {
      labels: {
        style: {
          colors: 'rgba(var(--v-theme-on-background), var(--v-disabled-opacity))',
          fontSize: '13px',
        },
        formatter(val: string) {
          return `${val}`
        },
      },
    },
    tooltip: {
      enabled: false,
      style: {
        fontSize: '12px',
      },
      onDatasetHover: {
        highlightDataSeries: false,
      },
    },
    legend: {
      show: false,
    },

  }

  tmp.xaxis.min = min
  tmp.xaxis.max = max

  return tmp
}

watch(props, () => {
  loadItems()
})
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VRow>
        <VCol
          cols="12"
          md="4"
          id="my-node"
        >
          <VCard :title="`${$t('Label.Production Plant Vs Actual')} - ${props.periodoData}`">
            <VCardText>
              <VRow>
                <VCol
                  v-for="topic in productsData"
                  :key="topic.title"
                  cols="6"
                  :md="topic.col"
                >
                  <VBadge
                    v-if="topic.title !== 'Variance'"
                    dot
                    inline
                    class="mt-1 mb-0 custom-badge"
                    :color="topic.color"
                  />
                  <span class="text-center text-subtitle-2">{{ topic.title }}</span>
                  <h4
                    v-if="topic.title !== 'Variance'"
                    class="text-h6 mt-1 mb-0 text-center"
                  >
                    {{ topic.value }}
                  </h4>
                  <div v-else>
                    <VueApexCharts
                      :key="key"
                      type="bar"
                      height="80"
                      :options="getChartConfig(topic.series[0].data[0] < 0 ? topic.series[0].data[0] : -topic.series[0].data[0], topic.series[0].data[0] > 0 ? topic.series[0].data[0] : -topic.series[0].data[0])"
                      :series="topic.series"
                    />
                  </div>
                </VCol>
              </VRow>
            </VCardText>
          </VCard>
        </VCol>
        <VCol
          cols="12"
          md="4"
        >
          <VCard :title="`${$t('Label.Dispatch Plant Vs Actual')} - ${props.periodoData}`">
            <VCardText>
              <VRow>
                <VCol
                  v-for="topic in dispatchsData"
                  :key="topic.title"
                  cols="6"
                  :md="topic.col"
                >
                  <VBadge
                    v-if="topic.title !== 'Variance'"
                    dot
                    inline
                    class="mt-1 custom-badge"
                    :color="topic.color"
                  />
                  <span class="text-center text-subtitle-2">{{ topic.title }}</span>
                  <h4
                    v-if="topic.title !== 'Variance'"
                    class="text-h6 mt-1 text-center"
                  >
                    {{ topic.value }}
                  </h4>
                  <div v-else>
                    <VueApexCharts
                      :key="key"
                      type="bar"
                      height="80"
                      :options="getChartConfig(topic.series[0].data[0] < 0 ? topic.series[0].data[0] : -topic.series[0].data[0], topic.series[0].data[0] > 0 ? topic.series[0].data[0] : -topic.series[0].data[0])"
                      :series="topic.series"
                    />
                  </div>
                </VCol>
              </VRow>
            </VCardText>
          </VCard>
        </VCol>
        <VCol
          cols="12"
          md="4"
        >
          <VCard :title="`${$t('Label.Revenue Plant Vs Actual')} - ${props.periodoData}`">
            <VCardText>
              <VRow>
                <VCol
                  v-for="topic in reveniueData"
                  :key="topic.title"
                  cols="6"
                  :md="topic.col"
                >
                  <VBadge
                    v-if="topic.title !== 'Variance'"
                    dot
                    inline
                    class="mt-1 custom-badge"
                    :color="topic.color"
                  />
                  <span class="text-center text-subtitle-2">{{ topic.title }}</span>
                  <h4
                    v-if="topic.title !== 'Variance'"
                    class="text-h6 mt-1 text-center"
                  >
                    {{ topic.value }}
                  </h4>
                  <div v-else>
                    <VueApexCharts
                      :key="key"
                      type="bar"
                      height="80"
                      :options="getChartConfig(topic.series[0].data[0] < 0 ? topic.series[0].data[0] : -topic.series[0].data[0], topic.series[0].data[0] > 0 ? topic.series[0].data[0] : -topic.series[0].data[0])"
                      :series="topic.series"
                    />
                  </div>
                </VCol>
              </VRow>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>
    </VCol>
  </VRow>
</template>

<style>
.text-subtitle-2 {
  font-size: 12px!important;
}

#chartContainer .apexcharts-tooltip {
  color: #000000;
}

#chartContainer .apexcharts-tooltip .apexcharts-tooltip-series-group.active {
  background: #ffffff !important;
}
</style>
