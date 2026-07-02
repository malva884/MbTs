<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import moment from 'moment'
import { useTheme } from 'vuetify'

const vuetifyTheme = useTheme()
const yearFilter = ref(moment().year().toString())
const monthFilter = ref<string | null>(null)
const repartoFilter = ref<string[] | null>(null)
const centroDiCostoFilter = ref<string[] | null>(null)
const companyFilter = ref<string[] | null>(null)
const loading = ref(false)
const reportData = ref<any>(null)

const companies = [
  { value: 'metallurgica', title: 'Metallurgica' },
  { value: 'optotec', title: 'Optotec' },
]

const textColor = computed(() => vuetifyTheme.current.value.dark ? '#ffffff' : '#000000')

const months = [
  { value: null, title: 'Tutto l\'anno' },
  { value: '1', title: 'Gennaio' },
  { value: '2', title: 'Febbraio' },
  { value: '3', title: 'Marzo' },
  { value: '4', title: 'Aprile' },
  { value: '5', title: 'Maggio' },
  { value: '6', title: 'Giugno' },
  { value: '7', title: 'Luglio' },
  { value: '8', title: 'Agosto' },
  { value: '9', title: 'Settembre' },
  { value: '10', title: 'Ottobre' },
  { value: '11', title: 'Novembre' },
  { value: '12', title: 'Dicembre' },
]

const years = Array.from({ length: 5 }, (_, i) => {
  const y = moment().year() - i
  return { value: y.toString(), title: y.toString() }
})

const reparti = ref<any[]>([])
const centriDiCosto = ref<any[]>([])

const fetchReport = async () => {
  loading.value = true
  try {
    const params: any = { year: yearFilter.value }
    if (monthFilter.value) params.month = monthFilter.value
    if (repartoFilter.value) params.reparto_id = repartoFilter.value
    if (centroDiCostoFilter.value) params.centro_di_costo = centroDiCostoFilter.value
    if (companyFilter.value) params.company_id = companyFilter.value

    const response = await $api('/hr/dipendenti/report', { params })
    reportData.value = response
  }
  catch (e) {
    console.error('Error fetching report:', e)
  }
  finally {
    loading.value = false
  }
}

const fetchFilters = async () => {
  try {
    const [repartiRes, centriRes] = await Promise.all([
      $api('/hr/reparti/getList'),
      $api('/hr/centro_di_costo/get_list'),
    ])
    reparti.value = repartiRes
    centriDiCosto.value = centriRes
  }
  catch (e) {
    console.error('Error fetching filters:', e)
  }
}

const tipologiaChartOptions = computed(() => ({
  chart: { type: 'donut', height: 350 },
  labels: ['Ferie', '104', 'Permesso', 'Malattie', 'Assenza'],
  colors: ['#ff9800', '#0288d1', '#00897b', '#9c27b0', '#f44336'],
  legend: {
    position: 'bottom',
    labels: { colors: textColor.value },
  },
  plotOptions: {
    pie: {
      donut: {
        labels: {
          show: true,
          name: { color: textColor.value },
          value: { color: textColor.value },
          total: { color: textColor.value },
        },
      },
    },
  },
}))

const tipologiaSeries = computed(() => {
  if (!reportData.value?.kpi?.by_tipologia) return []
  const by = reportData.value.kpi.by_tipologia
  return [
    by[1] || 0, // Ferie
    by[2] || 0, // 104
    by[5] || 0, // Permesso
    by[3] || 0, // Malattie
    by[4] || 0, // Assenza
  ]
})

const monthlyChartOptions = computed(() => {
  const months = Object.keys(reportData.value?.kpi?.by_month_tipologia || {}).sort()
  return {
    chart: { type: 'bar', height: 350, toolbar: { show: false }, stacked: true },
    xaxis: { categories: months.map(k => moment(k).format('MMM YY')), labels: { style: { colors: textColor.value } } },
    yaxis: { labels: { style: { colors: textColor.value } } },
    plotOptions: { bar: { borderRadius: 4, columnWidth: '60%' } },
    colors: ['#ff9800', '#0288d1', '#00897b', '#9c27b0', '#f44336'],
    legend: { labels: { colors: textColor.value } },
  }
})

const monthlySeries = computed(() => {
  const byMonthTipologia = reportData.value?.kpi?.by_month_tipologia || {}
  const months = Object.keys(byMonthTipologia).sort()

  return [
    {
      name: 'Ferie',
      data: months.map(m => byMonthTipologia[m]?.[1] || 0),
    },
    {
      name: '104',
      data: months.map(m => byMonthTipologia[m]?.[2] || 0),
    },
    {
      name: 'Permesso',
      data: months.map(m => byMonthTipologia[m]?.[5] || 0),
    },
    {
      name: 'Malattie',
      data: months.map(m => byMonthTipologia[m]?.[3] || 0),
    },
    {
      name: 'Assenza',
      data: months.map(m => byMonthTipologia[m]?.[4] || 0),
    },
  ]
})

const employeeChartOptions = computed(() => {
  const byEmployee = reportData.value?.kpi?.by_employee || {}
  const topEmployees = Object.entries(byEmployee)
    .sort(([, a], [, b]) => b - a)
    .slice(0, 10)
    .map(([matricola]) => matricola)

  return {
    chart: { type: 'bar', height: 350, toolbar: { show: false }, stacked: true },
    plotOptions: { bar: { borderRadius: 4, horizontal: true } },
    xaxis: { categories: topEmployees, labels: { style: { colors: textColor.value } } },
    yaxis: { labels: { style: { colors: textColor.value } } },
    colors: ['#ff9800', '#0288d1', '#00897b', '#9c27b0', '#f44336'],
    legend: { labels: { colors: textColor.value } },
  }
})

const employeeSeries = computed(() => {
  const byEmployeeTipologia = reportData.value?.kpi?.by_employee_tipologia || {}
  const byEmployee = reportData.value?.kpi?.by_employee || {}
  const topEmployees = Object.entries(byEmployee)
    .sort(([, a], [, b]) => b - a)
    .slice(0, 10)
    .map(([matricola]) => matricola)

  return [
    {
      name: 'Ferie',
      data: topEmployees.map(m => byEmployeeTipologia[m]?.[1] || 0),
    },
    {
      name: '104',
      data: topEmployees.map(m => byEmployeeTipologia[m]?.[2] || 0),
    },
    {
      name: 'Permesso',
      data: topEmployees.map(m => byEmployeeTipologia[m]?.[5] || 0),
    },
    {
      name: 'Malattie',
      data: topEmployees.map(m => byEmployeeTipologia[m]?.[3] || 0),
    },
    {
      name: 'Assenza',
      data: topEmployees.map(m => byEmployeeTipologia[m]?.[4] || 0),
    },
  ]
})

const dayChartOptions = computed(() => {
  const days = Array.from({ length: 31 }, (_, i) => i + 1)
  return {
    chart: { type: 'bar', height: 350, toolbar: { show: false }, stacked: true },
    xaxis: { categories: days.map(d => d.toString()), labels: { style: { colors: textColor.value } } },
    yaxis: { labels: { style: { colors: textColor.value } } },
    plotOptions: { bar: { borderRadius: 4, columnWidth: '60%' } },
    colors: ['#ff9800', '#0288d1', '#00897b', '#9c27b0', '#f44336'],
    legend: { labels: { colors: textColor.value } },
  }
})

const daySeries = computed(() => {
  const byDayTipologia = reportData.value?.kpi?.by_day_tipologia || {}
  const days = Array.from({ length: 31 }, (_, i) => i + 1)

  return [
    {
      name: 'Ferie',
      data: days.map(d => byDayTipologia[d]?.[1] || 0),
    },
    {
      name: '104',
      data: days.map(d => byDayTipologia[d]?.[2] || 0),
    },
    {
      name: 'Permesso',
      data: days.map(d => byDayTipologia[d]?.[5] || 0),
    },
    {
      name: 'Malattie',
      data: days.map(d => byDayTipologia[d]?.[3] || 0),
    },
    {
      name: 'Assenza',
      data: days.map(d => byDayTipologia[d]?.[4] || 0),
    },
  ]
})

const exportToCSV = () => {
  if (!reportData.value?.details) return

  const headers = ['Dipendente', 'Matricola', 'Reparto', 'Centro di Costo', 'Data', 'Tipologia', 'Ora Inizio', 'Ora Fine', 'Stato']
  const rows = reportData.value.details.map((d: any) => [
    d.dipendente,
    d.matricola,
    d.reparto,
    d.centro_di_costo,
    d.data,
    d.tipologia_testo,
    d.ora_inizio || '',
    d.ora_fine || '',
    d.stato === 1 ? 'Approvato' : 'In attesa',
  ])

  const csv = [headers, ...rows].map(row => row.join(',')).join('\n')
  const blob = new Blob([csv], { type: 'text/csv' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `report_${yearFilter.value}_${monthFilter.value || 'all'}.csv`
  a.click()
}

onMounted(() => {
  fetchFilters()
  fetchReport()
})
</script>

<template>
  <div class="workspace-container w-100 d-flex flex-column pa-4 gap-3">
    <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
      <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
        <div class="d-flex align-center gap-2">
          <VIcon icon="tabler-chart-bar" size="24" color="primary" />
          <div>
            <div class="text-h6 font-weight-medium">Report Assenze</div>
            <div class="text-caption text-medium-emphasis">{{ reportData?.details?.length || 0 }} richieste</div>
          </div>
        </div>
      </VCardText>
      <VDivider />
      <VCardText class="pa-3">
        <VRow class="mb-2">
          <VCol cols="12" sm="2">
            <AppSelect
              v-model="yearFilter"
              :items="years"
              label="Anno"
              @update:model-value="fetchReport"
            />
          </VCol>
          <VCol cols="12" sm="2">
            <AppSelect
              v-model="monthFilter"
              :items="months"
              label="Mese"
              @update:model-value="fetchReport"
            />
          </VCol>
          <VCol cols="12" sm="2">
            <AppSelect
              v-model="companyFilter"
              :items="companies"
              item-title="title"
              item-value="value"
              label="Azienda"
              clearable
              multiple
              chips
              @update:model-value="fetchReport"
            />
          </VCol>
          <VCol cols="12" sm="2">
            <AppSelect
              v-model="repartoFilter"
              :items="reparti"
              item-title="reparto"
              item-value="id"
              label="Reparto"
              clearable
              multiple
              chips
              @update:model-value="fetchReport"
            />
          </VCol>
          <VCol cols="12" sm="2">
            <AppSelect
              v-model="centroDiCostoFilter"
              :items="centriDiCosto"
              item-title="centro_di_costo"
              item-value="id"
              label="Centro di Costo"
              clearable
              multiple
              chips
              @update:model-value="fetchReport"
            />
          </VCol>
        </VRow>
      </VCardText>
      <VDivider />

      <VProgressCircular v-if="loading" indeterminate color="primary" class="d-block mx-auto" />

      <div v-else-if="reportData">
        <!-- KPI Cards -->
        <VRow class="mb-6">
          <VCol cols="12" sm="2">
            <VCard variant="outlined" class="text-center pa-3">
              <div class="text-h4 font-weight-bold">{{ reportData.kpi.total_days }}</div>
              <div class="text-caption">Totale Giorni</div>
            </VCard>
          </VCol>
          <VCol cols="12" sm="2">
            <VCard variant="outlined" class="text-center pa-3" style="background-color: rgba(255, 152, 0, 0.1); border-color: #ff9800;">
              <div class="text-h4 font-weight-bold" style="color: #ff9800;">{{ reportData.kpi.by_tipologia?.[1] || 0 }}</div>
              <div class="text-caption">Ferie</div>
            </VCard>
          </VCol>
          <VCol cols="12" sm="2">
            <VCard variant="outlined" class="text-center pa-3" style="background-color: rgba(156, 39, 176, 0.1); border-color: #9c27b0;">
              <div class="text-h4 font-weight-bold" style="color: #9c27b0;">{{ reportData.kpi.by_tipologia?.[3] || 0 }}</div>
              <div class="text-caption">Malattie</div>
            </VCard>
          </VCol>
          <VCol cols="12" sm="2">
            <VCard variant="outlined" class="text-center pa-3" style="background-color: rgba(2, 136, 209, 0.1); border-color: #0288d1;">
              <div class="text-h4 font-weight-bold" style="color: #0288d1;">{{ reportData.kpi.by_tipologia?.[2] || 0 }}</div>
              <div class="text-caption">104</div>
            </VCard>
          </VCol>
          <VCol cols="12" sm="2">
            <VCard variant="outlined" class="text-center pa-3" style="background-color: rgba(0, 137, 123, 0.1); border-color: #00897b;">
              <div class="text-h4 font-weight-bold" style="color: #00897b;">{{ reportData.kpi.by_tipologia?.[5] || 0 }}</div>
              <div class="text-caption">Permesso</div>
            </VCard>
          </VCol>
          <VCol cols="12" sm="2">
            <VCard variant="outlined" class="text-center pa-3" style="background-color: rgba(244, 67, 54, 0.1); border-color: #f44336;">
              <div class="text-h4 font-weight-bold" style="color: #f44336;">{{ reportData.kpi.by_tipologia?.[4] || 0 }}</div>
              <div class="text-caption">Assenza</div>
            </VCard>
          </VCol>
        </VRow>

        <!-- Charts -->
        <VRow class="mb-6">
          <VCol cols="12" md="6">
            <VCard variant="outlined" class="pa-3">
              <VCardTitle class="text-h6">Distribuzione per Tipologia</VCardTitle>
              <VueApexCharts
                type="donut"
                :options="tipologiaChartOptions"
                :series="tipologiaSeries"
                height="350"
              />
            </VCard>
          </VCol>
          <VCol cols="12" md="6">
            <VCard variant="outlined" class="pa-3">
              <VCardTitle class="text-h6">Andamento Mensile</VCardTitle>
              <VueApexCharts
                type="bar"
                :options="monthlyChartOptions"
                :series="monthlySeries"
                height="350"
              />
            </VCard>
          </VCol>
        </VRow>

        <VRow class="mb-6">
          <VCol cols="12">
            <VCard variant="outlined" class="pa-3">
              <VCardTitle class="text-h6">Top 10 Dipendenti per Giorni di Assenza</VCardTitle>
              <VueApexCharts
                type="bar"
                :options="employeeChartOptions"
                :series="employeeSeries"
                height="350"
              />
            </VCard>
          </VCol>
        </VRow>

        <VRow class="mb-6">
          <VCol cols="12">
            <VCard variant="outlined" class="pa-3">
              <VCardTitle class="text-h6">Distribuzione per Giorno del Mese</VCardTitle>
              <VueApexCharts
                type="bar"
                :options="dayChartOptions"
                :series="daySeries"
                height="350"
              />
            </VCard>
          </VCol>
        </VRow>

        <!-- Details Table -->
        <VCard variant="outlined" class="pa-3">
          <div class="d-flex justify-space-between align-center mb-4">
            <VCardTitle class="text-h6 pa-0">Dettaglio Richieste</VCardTitle>
            <VBtn color="primary" size="small" @click="exportToCSV">
              <VIcon start icon="tabler-download" />
              Esporta CSV
            </VBtn>
          </div>
          <VDataTable
            :headers="[
              { title: 'Dipendente', key: 'dipendente' },
              { title: 'Matricola', key: 'matricola' },
              { title: 'Reparto', key: 'reparto' },
              { title: 'Centro di Costo', key: 'centro_di_costo' },
              { title: 'Data', key: 'data' },
              { title: 'Tipologia', key: 'tipologia_testo' },
              { title: 'Ora Inizio', key: 'ora_inizio' },
              { title: 'Ora Fine', key: 'ora_fine' },
              { title: 'Stato', key: 'stato' },
            ]"
            :items="reportData.details"
            :items-per-page="25"
          >
            <template #item.stato="{ item }">
              <VChip :color="item.stato === 1 ? 'success' : 'warning'" size="small">
                {{ item.stato === 1 ? 'Approvato' : 'In attesa' }}
              </VChip>
            </template>
          </VDataTable>
        </VCard>
      </div>
    </VCard>
  </div>
</template>
