<script setup lang="ts">
import { useTheme } from 'vuetify'
import { useI18n } from 'vue-i18n'
import { getLineChartCustomNullValueConfig } from '@core/libs/apex-chart/apexCharConfig'
//getLineChartCustomNullValueConfig

interface Emit {
  (e: 'update:isChartVisible', value: boolean): void
}

interface Props {
  isChartVisible: boolean
  macchinaId: string
  macchinaNome: string
  quatroPuntoZero: boolean
}

const props = defineProps<Props>()
const emit = defineEmits<Emit>()
const loading = ref(true)
const itemsPerPage = ref(10)
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const page = ref(1)
const serverItems = ref<any>([])
const vuetifyTheme = useTheme()
const { t } = useI18n()
const view = ref(false)
const loadingPage = ref(true)
const today = new Date()
const date = `${today.getFullYear()}-${today.getMonth() + 1}-${today.getDate()}`
const time = `${today.getHours()}:${today.getMinutes()}:${today.getSeconds()}`
const periodoDa = ref()
const periodoA = ref()
const dateFilter = ref(`${date} to ${date}`)

const series = []

const chartConfig = computed(() => getLineChartCustomNullValueConfig(vuetifyTheme.current.value))

const loadItems = async () => {
  loadingPage.value = true
  view.value = false
  series.length = 0

  const { data: resultData } = await useApi<any>(createUrl('/gp/dettaglioMacchina', {
    query: {
      macchina: props.macchinaId,
      quatroPuntoZero: props.quatroPuntoZero,
      periodo: dateFilter.value,
      select: { dataDa: periodoDa.value, dataA: periodoA.value },
    },
  }))

  if (!resultData.value) {
    console.error('No data returned from API')
    loadingPage.value = false
    return
  }

  // Funzione per campionare i dati (sampling) per migliorare performance
  const sampleData = (data: any[], maxPoints: number = 100) => {
    if (!data || data.length <= maxPoints) return data
    const step = Math.ceil(data.length / maxPoints)
    return data.filter((_, index) => index % step === 0)
  }

  // Campiona i dati per ridurre il numero di punti (ridotto a 100)
  const metriData = sampleData(resultData.value.Metri, 100)
  const fermiData = sampleData(resultData.value.Fermi, 100)
  const lineaData = resultData.value.Linea ? sampleData(resultData.value.Linea, 100) : undefined
  const estrusioneData = resultData.value.Estrusione ? sampleData(resultData.value.Estrusione, 100) : undefined

  series.push({ name: 'Metri', data: metriData, color: '#7367f0' })
  if (lineaData !== undefined)
    series.push({ name: 'Linea', data: lineaData, color: '#00d4bd' })
  series.push({ name: 'Fermi', data: fermiData, color: '#fdd835' })
  if (estrusioneData !== undefined)
    series.push({ name: 'Estrusione', data: estrusioneData, color: '#db1212' })

  // Usa nextTick per il rendering asincrono
  await nextTick()
  view.value = true
  loadingPage.value = false
}



const click = (event: any, chartContext: any, config: any) => {
  if (config.config.xaxis.min === 'undefined' && config.config.xaxis.max === 'undefined') {
    periodoDa.value = null
    periodoA.value = null
  }
  else {
    periodoDa.value = config.config.xaxis.min
    periodoA.value = config.config.xaxis.max
  }
  loadItems()
}

const settimana = () => {
  const da = new Date()

  periodoDa.value = da.setDate(da.getDate() - 7)
  periodoA.value = new Date().getTime()
  loadItems()
}

const oggi = () => {
  periodoDa.value = new Date().getTime()
  periodoA.value = new Date().getTime()
  loadItems()
}

const mese = () => {
  const da = new Date()

  periodoDa.value = da.setMonth(da.getMonth() - 1)
  periodoA.value = new Date().getTime()
  loadItems()
}

const treGioni = () => {
  const d = new Date()

  periodoDa.value = d.setDate(d.getDate() - 3)
  periodoA.value = new Date().getTime()
  loadItems()
}

const quindiciGionri = () => {
  const da = new Date()

  periodoDa.value = da.setDate(da.getDate() - 15)
  periodoA.value = new Date().getTime()
  loadItems()
}

const close = () => {
  emit('update:isChartVisible', false)
}

// setInterval(loadItems, 40000)
onMounted(() => {
  loadItems()
  loadingPage.value = false
})
</script>

<template>
  <VDialog
    :model-value="props.isChartVisible"
    fullscreen
    persistent
    :scrim="false"
    transition="dialog-bottom-transition"
  >
    <!-- Dialog Content -->
    <VCard>
      <VToolbar color="primary">
        <VBtn
          icon
          variant="plain"
          @click="close"
        >
          <VIcon
            color="white"
            icon="tabler-x"
          />
        </VBtn>

        <VToolbarTitle>{{ props.macchinaNome }} - {{ props.macchinaId }}</VToolbarTitle>

        <VSpacer />
      </VToolbar>

      <VContainer class="pa-2" fluid>
        <!-- Period Selection Card - Compact -->
        <VCard class="mb-2">
          <VCardItem class="py-2">
            <VCardTitle class="text-h6">Seleziona Periodo</VCardTitle>
          </VCardItem>
          <VCardText class="pt-0 pb-2">
            <div class="d-flex flex-wrap gap-1">
              <VBtn
                variant="outlined"
                color="success"
                size="x-small"
                @click="oggi"
              >
                <VIcon start icon="tabler-calendar-today" size="16" />
                Oggi
              </VBtn>
              <VBtn
                variant="outlined"
                color="primary"
                size="x-small"
                @click="treGioni"
              >
                <VIcon start icon="tabler-calendar" size="16" />
                3 Giorni
              </VBtn>
              <VBtn
                variant="outlined"
                color="primary"
                size="x-small"
                @click="settimana"
              >
                <VIcon start icon="tabler-calendar-week" size="16" />
                Settimana
              </VBtn>
              <VBtn
                variant="outlined"
                color="warning"
                size="x-small"
                @click="quindiciGionri"
              >
                <VIcon start icon="tabler-calendar-days" size="16" />
                15 Giorni
              </VBtn>
              <VBtn
                variant="outlined"
                color="error"
                size="x-small"
                @click="mese"
              >
                <VIcon start icon="tabler-calendar-month" size="16" />
                Mese
              </VBtn>
            </div>
          </VCardText>
        </VCard>

        <!-- Chart Card - Full Width Compact -->
        <VCard class="mb-2" style="height: calc(100vh - 120px);">
          <VCardItem class="py-2">
            <VCardTitle class="text-h6">Grafico Produzione</VCardTitle>
          </VCardItem>
          <VCardText v-if="view" class="d-flex flex-column pt-0" style="height: calc(100% - 50px);">
            <VueApexCharts
              type="line"
              height="100%"
              :options="chartConfig"
              :series="series"
              @click="click"
              :animations="false"
            />
          </VCardText>
          <VCardText v-else class="text-center py-8 d-flex flex-column align-center justify-center" style="height: calc(100% - 50px);">
            <VProgressCircular
              indeterminate
              color="primary"
              size="48"
            />
            <p class="mt-2 text-body-2">Caricamento...</p>
          </VCardText>
        </VCard>

        <!-- Legend Card - Compact -->
        <VCard v-if="view && series.length > 0" class="mb-2">
          <VCardItem class="py-2">
            <VCardTitle class="text-h6">Legenda</VCardTitle>
          </VCardItem>
          <VCardText class="pt-0 pb-2">
            <div class="d-flex flex-wrap gap-2">
              <div
                v-for="s in series"
                :key="s.name"
                class="d-flex align-center gap-1"
              >
                <div
                  :style="{
                    width: '12px',
                    height: '12px',
                    backgroundColor: s.color,
                    borderRadius: '50%'
                  }"
                />
                <span class="text-caption">{{ s.name }}</span>
              </div>
            </div>
          </VCardText>
        </VCard>
      </VContainer>
    </VCard>
  </VDialog>
  <LoadingStandBy v-model="loadingPage"></LoadingStandBy>
</template>

<style scoped lang="scss">
.v-col-12 {
  flex: 0 0 0 !important;
  max-width: 100%;
}
</style>
