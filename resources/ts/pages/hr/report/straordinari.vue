<script setup lang="ts">
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useTheme } from 'vuetify'
import moment from 'moment'

definePage({
  meta: {
    action: 'list',
    subject: 'Hr-Straordinari',
  },
})

const { t } = useI18n()
const theme = useTheme()
const loading = ref(false)
const dateRange = ref([moment().startOf('month').format('YYYY-MM-DD'), moment().endOf('month').format('YYYY-MM-DD')])
const causali = ref(['RSTR'])
const causaliOptions = ref([
  { title: 'Recupero Straordinari', value: 'RSTR' },
  { title: 'Straordinario Pagato', value: 'STRP' },
  { title: 'Straordinario Non Pagato', value: 'STRN' },
])

const reportData = ref<any[]>([])
const selectedCdc = ref<string | null>(null)
const dettaglioData = ref<any[]>([])
const showDettaglio = ref(false)

const weeks = ['Settimana 1', 'Settimana 2', 'Settimana 3', 'Settimana 4']

const chartSeries = computed(() => {
  return reportData.value.map((cdc: any) => {
    const weekData = weeks.map(week => {
      const weekItem = cdc.ore_per_settimana?.find((w: any) => w.settimana === week)
      return weekItem ? weekItem.ore : 0
    })

    return {
      name: cdc.cdc,
      data: weekData
    }
  })
})

const chartOptions = computed(() => {
  const theme = useTheme()
  const isDark = theme.global.current.value.dark

  return {
    chart: {
      type: 'bar',
      height: 350,
      stacked: false,
      toolbar: {
        show: true
      },
      background: 'transparent',
      foreColor: isDark ? '#e0e0e0' : '#333333'
    },
    plotOptions: {
      bar: {
        horizontal: false,
        columnWidth: '55%',
        endingShape: 'rounded',
        borderRadius: 4
      }
    },
    colors: ['#6366f1', '#8b5cf6', '#ec4899', '#f43f5e', '#f97316', '#eab308', '#22c55e', '#14b8a6', '#06b6d4', '#3b82f6'],
    xaxis: {
      categories: weeks,
      title: {
        text: 'Settimana'
      },
      labels: {
        style: {
          colors: isDark ? '#e0e0e0' : '#333333'
        }
      }
    },
    yaxis: {
      title: {
        text: 'Ore'
      },
      labels: {
        formatter: (value: number) => formatOre(value),
        style: {
          colors: isDark ? '#e0e0e0' : '#333333'
        }
      }
    },
    tooltip: {
      y: {
        formatter: (value: number) => formatOre(value)
      },
      theme: isDark ? 'dark' : 'light'
    },
    legend: {
      position: 'top',
      labels: {
        colors: isDark ? '#e0e0e0' : '#333333'
      }
    },
    fill: {
      opacity: 1
    },
    grid: {
      borderColor: isDark ? '#424242' : '#e0e0e0'
    },
    dataLabels: {
      enabled: true,
      formatter: (value: number) => formatOre(value),
      style: {
        colors: [isDark ? '#e0e0e0' : '#333333']
      }
    }
  }
})

const fetchReport = async () => {
  loading.value = true
  try {
    // Estrai le date dal range (il date picker restituisce una stringa "YYYY-MM-DD to YYYY-MM-DD")
    let dataInizio = null
    let dataFine = null

    if (dateRange.value) {
      if (typeof dateRange.value === 'string' && dateRange.value.includes(' to ')) {
        const parts = dateRange.value.split(' to ')
        dataInizio = parts[0]
        dataFine = parts[1]
      } else if (Array.isArray(dateRange.value) && dateRange.value.length === 2) {
        dataInizio = dateRange.value[0]
        dataFine = dateRange.value[1]
      } else if (dateRange.value && typeof dateRange.value === 'object') {
        dataInizio = dateRange.value.from
        dataFine = dateRange.value.to
      }
    }

    const { data } = await useApi<any>(createUrl('/teamsystem/straordinari/centro-di-costo', {
      query: {
        data_inizio: dataInizio,
        data_fine: dataFine,
        causali: causali.value,
      },
    }))

    if (data.value && data.value.data) {
      reportData.value = data.value.data
    }
  } catch (error) {
    console.error('Errore durante il recupero del report:', error)
  } finally {
    loading.value = false
  }
}

const fetchDettaglio = async (cdc: string) => {
  loading.value = true
  selectedCdc.value = cdc
  try {
    // Estrai le date dal range (il date picker restituisce una stringa "YYYY-MM-DD to YYYY-MM-DD")
    let dataInizio = null
    let dataFine = null

    if (dateRange.value) {
      if (typeof dateRange.value === 'string' && dateRange.value.includes(' to ')) {
        const parts = dateRange.value.split(' to ')
        dataInizio = parts[0]
        dataFine = parts[1]
      } else if (Array.isArray(dateRange.value) && dateRange.value.length === 2) {
        dataInizio = dateRange.value[0]
        dataFine = dateRange.value[1]
      } else if (dateRange.value && typeof dateRange.value === 'object') {
        dataInizio = dateRange.value.from
        dataFine = dateRange.value.to
      }
    }

    const { data } = await useApi<any>(createUrl('/teamsystem/straordinari/dettaglio', {
      query: {
        cdc,
        data_inizio: dataInizio,
        data_fine: dataFine,
        causali: causali.value,
      },
    }))

    if (data.value && data.value.data) {
      dettaglioData.value = data.value.data
      showDettaglio.value = true
    }
  } catch (error) {
    console.error('Errore durante il recupero del dettaglio:', error)
  } finally {
    loading.value = false
  }
}

const closeDettaglio = () => {
  showDettaglio.value = false
  selectedCdc.value = null
  dettaglioData.value = []
}

const hourlyRates = ref<Record<string, number>>({})

const fetchHourlyRates = async () => {
  try {
    const { data } = await useApi<any>('/overtime-costs/rates')
    if (data.value && data.value.success) {
      hourlyRates.value = data.value.data
    }
  } catch (error) {
    console.error('Errore nel recupero tariffe:', error)
  }
}

const annualReportData = ref<any[]>([])

const fetchAnnualReport = async () => {
  try {
    const { data } = await useApi<any>(createUrl('/overtime-costs/annual-report', {
      query: { causali: causali.value },
    }))
    if (data.value && data.value.success) {
      annualReportData.value = data.value.data
    }
  } catch (error) {
    console.error('Errore nel recupero del report annuale:', error)
  }
}

const monthlyReportData = computed(() => {
  const groupMapping: Record<string, string> = {
    'BlueCollar OFC': 'BlueCollar OFC',
    'BlueCollar CC': 'BlueCollar CC',
    'Quality': 'Quality',
    'Maintenance': 'Maintenance',
    'Logistics': 'Logistic OFC',
    'Office': 'Offices',
    'Warehouse CC': 'Warehouse CC',
    'Werehouse CC': 'Warehouse CC',
  }

  const report: Record<string, { department: string, hours: number, cost: number }> = {}

  reportData.value.forEach((item: any) => {
    const dept = groupMapping[item.cdc] || item.cdc
    const hours = (item.totali_ore || 0) / 3600
    const rate = hourlyRates.value[dept] || 0
    const cost = hours * rate

    if (!report[dept]) {
      report[dept] = { department: dept, hours: 0, cost: 0 }
    }

    report[dept].hours += hours
    report[dept].cost += cost
  })

  return Object.values(report)
})

const totalMonthlyHours = computed(() => {
  return monthlyReportData.value.reduce((sum, item) => sum + (Number(item.hours) || 0), 0)
})

const totalMonthlyCost = computed(() => {
  return monthlyReportData.value.reduce((sum, item) => sum + (Number(item.cost) || 0), 0)
})

const monthlyDepartments = computed(() => monthlyReportData.value.map(item => item.department))

const annualYears = computed(() => annualReportData.value.map(item => item.year))

const totalAnnualHours = computed(() => {
  return annualReportData.value.reduce((sum, item) => sum + (Number(item.hours) || 0), 0)
})

const totalAnnualCost = computed(() => {
  return annualReportData.value.reduce((sum, item) => sum + (Number(item.cost) || 0), 0)
})

const annualChartSeries = computed(() => {
  if (!annualReportData.value || annualReportData.value.length === 0) {
    return [
      { name: 'Ore', type: 'column', data: [] },
      { name: 'Costo', type: 'line', data: [] },
    ]
  }
  return [
    {
      name: 'Ore',
      type: 'column',
      data: annualReportData.value.map(item => Number(item.hours) || 0),
    },
    {
      name: 'Costo',
      type: 'line',
      data: annualReportData.value.map(item => Number(item.cost) || 0),
    },
  ]
})

const annualChartOptions = computed(() => {
  const isDark = theme.global.current.value.dark

  return {
    chart: { type: 'line', toolbar: { show: false }, background: 'transparent' },
    plotOptions: {
      bar: { horizontal: false, columnWidth: '45%', borderRadius: 4 },
    },
    stroke: {
      width: [0, 4],
      curve: 'smooth',
    },
    xaxis: {
      categories: annualYears.value,
      labels: { style: { colors: isDark ? '#e0e0e0' : '#333333' } },
      title: { text: 'Anno', style: { color: isDark ? '#e0e0e0' : '#333333' } },
    },
    yaxis: [
      {
        title: { text: 'Ore', style: { color: isDark ? '#e0e0e0' : '#333333' } },
        labels: {
          style: { colors: isDark ? '#e0e0e0' : '#333333' },
          formatter: (value: number) => formatOre(value),
        },
      },
      {
        opposite: true,
        title: { text: 'Costo', style: { color: isDark ? '#e0e0e0' : '#333333' } },
        labels: {
          style: { colors: isDark ? '#e0e0e0' : '#333333' },
          formatter: (value: number) => `€ ${Number(value || 0).toFixed(2)}`,
        },
      },
    ],
    colors: ['#3b82f6', '#22c55e'],
    theme: { mode: isDark ? 'dark' : 'light' },
    legend: { labels: { colors: isDark ? '#e0e0e0' : '#333333' } },
    fill: {
      opacity: 1,
    },
    grid: { borderColor: isDark ? '#424242' : '#e0e0e0' },
    dataLabels: {
      enabled: true,
      formatter: (value: number, { seriesIndex }: { seriesIndex: number }) => {
        return seriesIndex === 0 ? formatOre(value) : `€ ${Number(value || 0).toFixed(2)}`
      },
      style: { colors: [isDark ? '#e0e0e0' : '#333333'] },
    },
    tooltip: {
      y: {
        formatter: (value: number, { seriesIndex }: { seriesIndex: number }) => {
          return seriesIndex === 0 ? formatOre(value) : `€ ${Number(value || 0).toFixed(2)}`
        },
      },
    },
  }
})

const monthlyChartSeries = computed(() => {
  if (!monthlyReportData.value || monthlyReportData.value.length === 0) {
    return [
      { name: 'Ore', type: 'column', data: [] },
      { name: 'Costo', type: 'line', data: [] },
    ]
  }
  return [
    {
      name: 'Ore',
      type: 'column',
      data: monthlyReportData.value.map(item => Number(item.hours) || 0),
    },
    {
      name: 'Costo',
      type: 'line',
      data: monthlyReportData.value.map(item => Number(item.cost) || 0),
    },
  ]
})

const monthlyChartOptions = computed(() => {
  const isDark = theme.global.current.value.dark

  return {
    chart: { type: 'line', toolbar: { show: false }, background: 'transparent' },
    plotOptions: {
      bar: { horizontal: false, columnWidth: '45%', borderRadius: 4 },
    },
    stroke: {
      width: [0, 4],
      curve: 'smooth',
    },
    xaxis: {
      categories: monthlyDepartments.value,
      labels: { style: { colors: isDark ? '#e0e0e0' : '#333333' } },
    },
    yaxis: [
      {
        title: { text: 'Ore', style: { color: isDark ? '#e0e0e0' : '#333333' } },
        labels: {
          style: { colors: isDark ? '#e0e0e0' : '#333333' },
          formatter: (value: number) => formatOre(value),
        },
      },
      {
        opposite: true,
        title: { text: 'Costo', style: { color: isDark ? '#e0e0e0' : '#333333' } },
        labels: {
          style: { colors: isDark ? '#e0e0e0' : '#333333' },
          formatter: (value: number) => `€ ${Number(value || 0).toFixed(2)}`,
        },
      },
    ],
    colors: ['#3b82f6', '#22c55e'],
    theme: { mode: isDark ? 'dark' : 'light' },
    legend: { labels: { colors: isDark ? '#e0e0e0' : '#333333' } },
    fill: {
      opacity: 1,
    },
    grid: { borderColor: isDark ? '#424242' : '#e0e0e0' },
    dataLabels: {
      enabled: true,
      formatter: (value: number, { seriesIndex }: { seriesIndex: number }) => {
        return seriesIndex === 0 ? formatOre(value) : `€ ${Number(value || 0).toFixed(2)}`
      },
      style: { colors: [isDark ? '#e0e0e0' : '#333333'] },
    },
    tooltip: {
      y: {
        formatter: (value: number, { seriesIndex }: { seriesIndex: number }) => {
          return seriesIndex === 0 ? formatOre(value) : `€ ${Number(value || 0).toFixed(2)}`
        },
      },
    },
  }
})

const formatCost = (value: number) => {
  return `€ ${Number(value || 0).toFixed(2)}`
}

const formatOre = (ore: number) => {
  if (!ore) return '0:00'
  
  // Se è un numero grande (es. 25200), sono secondi
  // Se è un numero piccolo (es. 7.5), sono ore decimali
  let totalSeconds: number
  
  if (ore > 1000) {
    // Assume secondi
    totalSeconds = ore
  } else {
    // Assume ore decimali (es. 7.5 = 7 ore e 30 minuti)
    totalSeconds = ore * 3600
  }
  
  const hours = Math.floor(totalSeconds / 3600)
  const minutes = Math.floor((totalSeconds % 3600) / 60)
  return `${hours}:${minutes.toString().padStart(2, '0')}`
}

const totaliOre = computed(() => {
  return reportData.value.reduce((sum, item) => sum + (item.totali_ore || 0), 0)
})

const totaliGiustificazioni = computed(() => {
  return reportData.value.reduce((sum, item) => sum + (item.numero_giustificazioni || 0), 0)
})

const totaliDipendenti = computed(() => {
  return reportData.value.reduce((sum, item) => sum + (item.numero_dipendenti || 0), 0)
})

onMounted(() => {
  fetchHourlyRates()
  fetchReport()
  fetchAnnualReport()
})

watch([dateRange, causali], () => {
  fetchReport()
  fetchAnnualReport()
})
</script>

<template>
  <div class="workspace-container w-100 d-flex flex-column pa-4 gap-3">
    <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
      <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
        <div class="d-flex align-center gap-2">
          <VIcon icon="tabler-clock" size="24" color="primary" />
          <div>
            <div class="text-h6 font-weight-medium">Report Straordinari</div>
            <div class="text-caption text-medium-emphasis">Analisi straordinari per centro di costo</div>
          </div>
        </div>
      </VCardText>
      <VDivider />
      <VCardText class="pa-3">
        <VRow class="mb-2">
          <!-- 👉 Periodo -->
          <VCol cols="12" sm="4">
            <AppDateTimePicker
              v-model="dateRange"
              label="Periodo"
              placeholder="Seleziona periodo"
              :config="{ mode: 'range' }"
              clearable
            />
          </VCol>

          <!-- 👉 Causali -->
          <VCol cols="12" sm="4">
            <AppSelect
              v-model="causali"
              label="Causali"
              placeholder="Seleziona causali"
              :items="causaliOptions"
              item-title="title"
              item-value="value"
              multiple
              chips
              closable-chips
            />
          </VCol>

          <!-- 👉 Aggiorna -->
          <VCol cols="12" sm="4">
            <VBtn
              color="primary"
              variant="elevated"
              @click="fetchReport"
              :loading="loading"
            >
              <VIcon icon="tabler-refresh" start />
              Aggiorna
            </VBtn>
          </VCol>
        </VRow>
      </VCardText>
      <VDivider />

      <!-- 👉 Riepilogo Totali -->
      <VCardText class="pa-3 bg-primary-lighten-5">
        <VRow>
          <VCol cols="12" sm="4">
            <div class="text-subtitle-2 font-weight-medium">Totali Ore Straordinari</div>
            <div class="text-h4 font-weight-bold">{{ formatOre(totaliOre) }}</div>
          </VCol>
          <VCol cols="12" sm="4">
            <div class="text-subtitle-2 font-weight-medium">Numero Giustificazioni</div>
            <div class="text-h4 font-weight-bold">{{ totaliGiustificazioni }}</div>
          </VCol>
          <VCol cols="12" sm="4">
            <div class="text-subtitle-2 font-weight-medium">Dipendenti Coinvolti</div>
            <div class="text-h4 font-weight-bold">{{ totaliDipendenti }}</div>
          </VCol>
        </VRow>
      </VCardText>
      <VDivider />

      <!-- 👉 Grafico Ore per Settimana -->
      <VCardText class="pa-3" v-if="reportData.length > 0">
        <div class="text-h6 font-weight-medium mb-3">Ore Straordinari per Settimana</div>
        <VueApexCharts
          type="bar"
          height="350"
          :options="chartOptions"
          :series="chartSeries"
        />
      </VCardText>
      <VDivider v-if="reportData.length > 0" />

      <!-- 👉 Tabella Report -->
      <VCardText class="pa-3">
        <VDataTable
          :headers="[
            { title: 'Centro di Costo', key: 'cdc' },
            { title: 'Ore Totali', key: 'totali_ore' },
            { title: 'Giustificazioni', key: 'numero_giustificazioni' },
            { title: 'Dipendenti', key: 'numero_dipendenti' },
            { title: 'Azioni', key: 'actions', sortable: false },
          ]"
          :items="reportData"
          :loading="loading"
          hover
        >
          <template #item.totali_ore="{ item }">
            <span class="font-weight-bold">{{ formatOre(item.totali_ore) }}</span>
          </template>
          <template #item.numero_giustificazioni="{ item }">
            <VChip color="primary" size="small">
              {{ item.numero_giustificazioni }}
            </VChip>
          </template>
          <template #item.numero_dipendenti="{ item }">
            <VChip color="info" size="small">
              {{ item.numero_dipendenti }}
            </VChip>
          </template>
          <template #item.actions="{ item }">
            <VBtn
              icon
              size="small"
              color="primary"
              @click="fetchDettaglio(item.cdc)"
            >
              <VIcon icon="tabler-eye" />
              <VTooltip activator="parent" location="top">
                Vedi dettaglio
              </VTooltip>
            </VBtn>
          </template>
        </VDataTable>
      </VCardText>
    </VCard>

    <!-- 👉 Report Mensile Ore e Costi -->
    <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
      <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
        <div class="d-flex align-center gap-2">
          <VIcon icon="tabler-currency-euro" size="24" color="primary" />
          <div>
            <div class="text-h6 font-weight-medium">Report Mensile Ore e Costi</div>
            <div class="text-caption text-medium-emphasis">Ore e costi straordinari per reparto nel mese del periodo selezionato</div>
          </div>
        </div>
      </VCardText>
      <VDivider />
      <VCardText class="pa-3">
        <VRow>
          <VCol cols="12" sm="6">
            <div class="text-subtitle-2 font-weight-medium">Totale Ore</div>
            <div class="text-h4 font-weight-bold">{{ formatOre(totalMonthlyHours) }}</div>
          </VCol>
          <VCol cols="12" sm="6">
            <div class="text-subtitle-2 font-weight-medium">Totale Costo</div>
            <div class="text-h4 font-weight-bold">{{ formatCost(totalMonthlyCost) }}</div>
          </VCol>
        </VRow>
      </VCardText>
      <VDivider />
      <VCardText class="pa-3">
        <div class="text-h6 font-weight-medium mb-3">Ore e Costi per Reparto</div>
        <VueApexCharts
          type="bar"
          height="350"
          :options="monthlyChartOptions"
          :series="monthlyChartSeries"
        />
      </VCardText>
      <VDivider />
      <VCardText class="pa-3">
        <VDataTable
          :headers="[
            { title: 'Reparto', key: 'department' },
            { title: 'Ore', key: 'hours', align: 'end' },
            { title: 'Costo', key: 'cost', align: 'end' },
          ]"
          :items="monthlyReportData"
          hover
          hide-default-footer
        >
          <template #item.hours="{ item }">
            <span class="font-weight-bold">{{ formatOre(item.hours) }}</span>
          </template>
          <template #item.cost="{ item }">
            <span class="font-weight-bold">{{ formatCost(item.cost) }}</span>
          </template>
        </VDataTable>
      </VCardText>
    </VCard>

    <!-- 👉 Report Annuale Ore e Costi -->
    <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
      <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
        <div class="d-flex align-center gap-2">
          <VIcon icon="tabler-calendar" size="24" color="primary" />
          <div>
            <div class="text-h6 font-weight-medium">Report Annuale Ore e Costi</div>
            <div class="text-caption text-medium-emphasis">Ore e costi straordinari per reparto negli ultimi 4 anni</div>
          </div>
        </div>
      </VCardText>
      <VDivider />
      <VCardText class="pa-3">
        <VRow>
          <VCol cols="12" sm="6">
            <div class="text-subtitle-2 font-weight-medium">Totale Ore</div>
            <div class="text-h4 font-weight-bold">{{ formatOre(totalAnnualHours) }}</div>
          </VCol>
          <VCol cols="12" sm="6">
            <div class="text-subtitle-2 font-weight-medium">Totale Costo</div>
            <div class="text-h4 font-weight-bold">{{ formatCost(totalAnnualCost) }}</div>
          </VCol>
        </VRow>
      </VCardText>
      <VDivider />
      <VCardText class="pa-3">
        <div class="text-h6 font-weight-medium mb-3">Ore e Costi per Anno (Ultimi 4 Anni)</div>
        <VueApexCharts
          type="bar"
          height="350"
          :options="annualChartOptions"
          :series="annualChartSeries"
        />
      </VCardText>
      <VDivider />
      <VCardText class="pa-3">
        <VDataTable
          :headers="[
            { title: 'Anno', key: 'year' },
            { title: 'Ore', key: 'hours', align: 'end' },
            { title: 'Costo', key: 'cost', align: 'end' },
          ]"
          :items="annualReportData"
          hover
          hide-default-footer
        >
          <template #item.hours="{ item }">
            <span class="font-weight-bold">{{ formatOre(item.hours) }}</span>
          </template>
          <template #item.cost="{ item }">
            <span class="font-weight-bold">{{ formatCost(item.cost) }}</span>
          </template>
        </VDataTable>
      </VCardText>
    </VCard>

    <!-- 👉 Dialog Dettaglio -->
    <VDialog v-model="showDettaglio" max-width="1200">
      <VCard>
        <VCardText class="d-flex align-center justify-space-between py-3">
          <div>
            <div class="text-h6 font-weight-medium">Dettaglio Giustificazioni</div>
            <div class="text-caption text-medium-emphasis">Centro di Costo: {{ selectedCdc }}</div>
          </div>
          <VBtn
            icon
            variant="text"
            @click="closeDettaglio"
          >
            <VIcon icon="tabler-x" />
          </VBtn>
        </VCardText>
        <VDivider />
        <VCardText class="pa-3">
          <VDataTable
            :headers="[
              { title: 'Matricola', key: 'matricola' },
              { title: 'Causale', key: 'causale' },
              { title: 'Data', key: 'data_competenza' },
              { title: 'Ore', key: 'ore' },
              { title: 'Stato', key: 'stato' },
            ]"
            :items="dettaglioData"
            :loading="loading"
            hover
          >
            <template #item.ore="{ item }">
              <span class="font-weight-bold">{{ formatOre(item.ore) }}</span>
            </template>
            <template #item.data_competenza="{ item }">
              {{ item.inizio ? moment(item.inizio).format('DD/MM/YYYY') : '-' }}
            </template>
            <template #item.stato="{ item }">
              <VChip
                :color="item.stato === 'A' ? 'success' : 'warning'"
                size="small"
              >
                {{ item.stato }}
              </VChip>
            </template>
          </VDataTable>
        </VCardText>
      </VCard>
    </VDialog>

    <LoadingStandBy v-model="loading"></LoadingStandBy>
  </div>
</template>

<style scoped lang="scss">

</style>
