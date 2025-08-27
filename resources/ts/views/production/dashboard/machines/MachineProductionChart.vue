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

  series.push({ name: 'Metri', data: resultData.value.Metri, color: '#7367f0' })
  if (resultData.value.Linea !== undefined)
    series.push({ name: 'Linea', data: resultData.value.Linea, color: '#00d4bd' })
  series.push({ name: 'Fermi', data: resultData.value.Fermi, color: '#fdd835' })
  if (resultData.value.Estrusione !== undefined)
    series.push({ name: 'Estrusione', data: resultData.value.Estrusione, color: '#db1212' })

  chartConfig.value.labels = resultData.value.Label
  console.log(series)
  //serverItems.value =

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
      <div>
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

          <VToolbarTitle>{{ props.macchinaNome + props.macchinaId }}</VToolbarTitle>

          <VSpacer />
        </VToolbar>
      </div>
      <VCol cols="12">
        <VCard>
          <VCardItem class="d-flex flex-wrap justify-space-between gap-4">
            <VCardTitle>Produzione Macchina</VCardTitle>
            <VCardSubtitle></VCardSubtitle>

            <div class="demo-space-x">
              <VBtn
                variant="outlined"
                color="error"
                size="small"
                @click="mese"
              >
                {{$t('Label.Mese')}}
              </VBtn>
              <VBtn
                variant="outlined"
                color="warning"
                size="small"
                @click="quindiciGionri"
              >
                {{$t('Label.Quindici-Gionri')}}
              </VBtn>
              <VBtn
                variant="outlined"
                color="primary"
                size="small"
                @click="settimana"
              >
                {{$t('Label.Settimana')}}
              </VBtn>
              <VBtn
                variant="outlined"
                color="primary"
                size="small"
                @click="treGioni"
              >
                {{$t('Label.Tre-Gionri')}}
              </VBtn>
              <VBtn
                variant="outlined"
                color="success"
                size="small"
                @click="oggi"
              >
                {{$t('Label.Oggi')}}
              </VBtn>
            </div>
          </VCardItem>

          <VCardText v-if="view">
            <VueApexCharts
              type="line"
              height="400"
              :options="chartConfig"
              :series="series"
              @click="click"
            />
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12">
        <VCard>
          <!-- 👉 Datatable  -->
          <VTable
            height="250"
            fixed-header
            class="text-no-wrap"
          >
            <thead>
            <tr>
              <th>
                {{ $t('Label.Ordine')}}
              </th>
              <th>
                {{ $t('Label.Materiale')}}
              </th>
              <th>
                {{ $t('Label.Metri-Prodotti')}}
              </th>
              <th>
                {{ $t('Label.Inizio-Lavorazione')}}
              </th>
              <th>
                {{ $t('Label.Fine-Lavorazione')}}
              </th>
            </tr>
            </thead>

            <tbody>
            <tr
              v-for="item in desserts"
              :key="item.dessert"
            >
              <td>
                {{ item.dessert }}
              </td>
              <td>
                {{ item.calories }}
              </td>
              <td>
                {{ item.fat }}
              </td>
              <td>
                {{ item.carbs }}
              </td>
              <td>
                {{ item.protein }}
              </td>
            </tr>
            </tbody>
          </VTable>
        </VCard>
      </VCol>
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
