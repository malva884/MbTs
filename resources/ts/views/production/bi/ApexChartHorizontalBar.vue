<script lang="ts" setup>
import { useTheme } from 'vuetify'
import { getBarChartConfig } from '@core/libs/apex-chart/apexCharConfig'
import {hexToRgb} from "@layouts/utils";

const vuetifyTheme = useTheme()

const chartOptions = computed(() => {
  return {
    series: [{
      name: 'sales',
      data: [],
    }],
    chart: {
      type: 'bar',
      height: 380,
    },
    xaxis: {
      type: 'category',
      labels: {
        formatter: function(val) {
          return 'Q'
        },
      },
      group: {
        style: {
          fontSize: '10px',
          fontWeight: 700,
        },
        groups: [
          { title: '2019', cols: 4 },
          { title: '2020', cols: 4 },
        ],
      },
    },
    title: {
      text: 'Grouped Labels on the X-axis',
    },
    tooltip: {
      x: {
        formatter: function(val) {
          return 'Q'
        },
      },
    },
  }
})

const series = [{
  data: [{
    x: '2019/01/01',
    y: 400,
  }],
}]
</script>

<template>
  <VueApexCharts
    type="bar"
    :options="chartOptions"
    :series="series"
  />
</template>

<style>
.apexchartsw6f8kpwx {
  max-width: 650px;
  margin: 35px auto;
}

.apexcharts-yaxis-annotation-label {
  font-weight: bold;
}
</style>
