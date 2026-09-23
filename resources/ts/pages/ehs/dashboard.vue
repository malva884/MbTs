<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { useTheme } from 'vuetify'

definePage({
  meta: {
    action: 'report',
    subject: 'Ehs-Eventi',
  },
})

const { t } = useI18n()
const vuetifyTheme = useTheme()

const isDark = computed(() => vuetifyTheme.current.value.dark)
const textColor = computed(() => isDark.value ? '#ffffff' : '#000000')
const tooltipTheme = computed(() => isDark.value ? 'dark' : 'light')

const loading = ref(false)
const stats = ref<any>(null)
const tipoScheda = ref(1)
const dataDa = ref('')
const dataA = ref('')

const tipoSchedaItems = [
  { title: t('Ehs.Infortuni'), value: 1 },
  { title: t('Ehs.Eventi-Ambientali'), value: 2 },
]

const loadStats = async () => {
  loading.value = true

  const { data } = await useApi<any>(createUrl('/ehs/events/stats', {
    query: {
      tipo_scheda: tipoScheda.value,
      data_da: dataDa.value,
      data_a: dataA.value,
    },
  }))

  if (data.value)
    stats.value = data.value
  loading.value = false
}

await loadStats()

watch([tipoScheda, dataDa, dataA], loadStats)

const chartPalette = ['#7367f0', '#28c76f', '#ff9f43', '#00cfe8', '#ea5455', '#9c27b0', '#0288d1', '#00897b', '#ffc107', '#ff5722']

const downloadToolbar = {
  show: true,
  offsetY: -8,
  tools: {
    download: true,
    selection: false,
    zoom: false,
    zoomin: false,
    zoomout: false,
    pan: false,
    reset: false,
  },
}

const barChartOptions = (categories: string[]) => ({
  chart: { type: 'bar', toolbar: downloadToolbar },
  colors: chartPalette,
  xaxis: {
    categories,
    labels: { style: { colors: textColor.value } },
  },
  yaxis: {
    labels: { style: { colors: textColor.value } },
  },
  plotOptions: { bar: { borderRadius: 4, columnWidth: '50%', distributed: true } },
  dataLabels: {
    enabled: true,
    style: { colors: [textColor.value] },
    background: { enabled: false },
  },
  legend: { show: false },
  tooltip: { theme: tooltipTheme.value },
})

const lineChartOptions = (categories: string[], color = '#7367f0') => ({
  chart: { type: 'line', toolbar: downloadToolbar },
  colors: [color],
  xaxis: {
    categories,
    labels: { style: { colors: textColor.value } },
  },
  yaxis: {
    labels: { style: { colors: textColor.value } },
  },
  stroke: { curve: 'smooth', width: 3 },
  markers: { size: 4, colors: [color] },
  dataLabels: {
    enabled: true,
    offsetY: -8,
    style: { colors: [textColor.value] },
    background: { enabled: false },
  },
  legend: { labels: { colors: textColor.value } },
  tooltip: { theme: tooltipTheme.value },
})
</script>

<template>
  <div class="workspace-container w-100 d-flex flex-column pa-4 gap-3">
    <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
      <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
        <div class="d-flex align-center gap-2">
          <VIcon icon="tabler-chart-bar" size="24" color="primary" />
          <div class="text-h6 font-weight-medium">{{ $t('Ehs.Dashboard') }}</div>
        </div>
        <div class="d-flex align-center gap-3 flex-wrap">
          <AppSelect
            v-model="tipoScheda"
            :items="tipoSchedaItems"
            density="compact"
            style="min-width: 200px"
            hide-details
          />
          <AppTextField
            v-model="dataDa"
            :label="$t('Ehs.Data-Da')"
            type="date"
            density="compact"
            hide-details
            clearable
          />
          <AppTextField
            v-model="dataA"
            :label="$t('Ehs.Data-A')"
            type="date"
            density="compact"
            hide-details
            clearable
          />
        </div>
      </VCardText>
      <VDivider />

      <VCardText v-if="loading" class="pa-4 text-center">
        <VProgressCircular indeterminate color="primary" />
      </VCardText>

      <VCardText v-else-if="stats" class="pa-4">
        <VRow>
          <!-- Grafici infortuni -->
          <template v-if="tipoScheda === 1">
            <VCol v-if="stats.lesioni" cols="12" md="6">
              <VCard variant="outlined" class="pa-3">
                <div class="text-subtitle-2 font-weight-medium mb-2">{{ $t('Ehs.Per-Sede-Anatomica') }}</div>
                <VueApexCharts
                  type="bar"
                  height="300"
                  :options="barChartOptions(stats.lesioni.categories)"
                  :series="[{ name: t('Ehs.Eventi'), data: stats.lesioni.data }]"
                />
              </VCard>
            </VCol>
            <VCol v-if="stats.tipolesioni" cols="12" md="6">
              <VCard variant="outlined" class="pa-3">
                <div class="text-subtitle-2 font-weight-medium mb-2">{{ $t('Ehs.Per-Tipo-Lesione') }}</div>
                <VueApexCharts
                  type="bar"
                  height="300"
                  :options="barChartOptions(stats.tipolesioni.categories)"
                  :series="[{ name: t('Ehs.Eventi'), data: stats.tipolesioni.data }]"
                />
              </VCard>
            </VCol>
            <VCol v-if="stats.dinamica" cols="12" md="6">
              <VCard variant="outlined" class="pa-3">
                <div class="text-subtitle-2 font-weight-medium mb-2">{{ $t('Ehs.Per-Causa') }}</div>
                <VueApexCharts
                  type="bar"
                  height="300"
                  :options="barChartOptions(stats.dinamica.categories)"
                  :series="[{ name: t('Ehs.Eventi'), data: stats.dinamica.data }]"
                />
              </VCard>
            </VCol>
          </template>

          <!-- Grafici eventi ambientali -->
          <template v-else>
            <VCol v-if="stats.eventi" cols="12" md="6">
              <VCard variant="outlined" class="pa-3">
                <div class="text-subtitle-2 font-weight-medium mb-2">{{ $t('Ehs.Per-Tipo-Evento') }}</div>
                <VueApexCharts
                  type="bar"
                  height="300"
                  :options="barChartOptions(stats.eventi.categories)"
                  :series="[{ name: t('Ehs.Eventi'), data: stats.eventi.data }]"
                />
              </VCard>
            </VCol>
          </template>

          <!-- Trend annuale -->
          <VCol v-if="stats.year" cols="12" md="6">
            <VCard variant="outlined" class="pa-3">
              <div class="text-subtitle-2 font-weight-medium mb-2">{{ $t('Ehs.Trend-Annuale') }}</div>
              <VueApexCharts
                type="line"
                height="300"
                :options="lineChartOptions(stats.year.categories, '#7367f0')"
                :series="[{ name: t('Ehs.Eventi'), data: stats.year.data }]"
              />
            </VCard>
          </VCol>

          <!-- Media giorni infortunio -->
          <VCol v-if="stats.mediaInf" cols="12" md="6">
            <VCard variant="outlined" class="pa-3">
              <div class="text-subtitle-2 font-weight-medium mb-2">{{ $t('Ehs.Media-Giorni-Per-Anno') }}</div>
              <VueApexCharts
                type="line"
                height="300"
                :options="lineChartOptions(stats.mediaInf.categories, '#ff9f43')"
                :series="[{ name: t('Ehs.Media-Giorni'), data: stats.mediaInf.data }]"
              />
            </VCard>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>
  </div>
</template>
