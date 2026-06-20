<script setup lang="ts">
import {useI18n} from 'vue-i18n'
import {useTheme} from 'vuetify'
import {
  getLine_ChartConfig,
  getLineChartCustomConfig,
  getLineChartSimpleConfig
} from '@core/libs/apex-chart/apexCharConfig'

const vuetifyTheme = useTheme()

const balanceChartConfig = computed(() => getLineChartCustomConfig(vuetifyTheme.current.value))
const lineChartConfig = computed(() => getLine_ChartConfig(vuetifyTheme.current.value))


interface Props {
  periodoData: string
  meseSelezionato: string
}

const props = defineProps<Props>()
const {t} = useI18n()
const key = ref(1)
const meseSelezionato = ref('')
const items = ref({})
const itemsWeek = ref({})
const itemsAll = ref({})
const series = ref({})
const seriesInvetory = ref({})
const categorie = ref({})
const categorieWeek = ref({})
const loadingPage = ref(false)
const categoria = ref('Fiber Optic OFC')

const loadItems = async () => {
  loadingPage.value = true
  const {data: resultData} = await useApi<any>(createUrl('/production/plant/inventory/', {
    query: {
      periodo: props.periodoData,
    },
  }))

  items.value = resultData.value.dati
  series.value = resultData.value.series
  balanceChartConfig.value.xaxis.categories = resultData.value.categories
  categorie.value = resultData.value.categories
  key.value = key.value + 1
  meseSelezionato.value = new Date(props.meseSelezionato).toLocaleString('en', {month: 'short'})
  loadingPage.value = false
}

loadItems()

const loadWeek = async () => {
  loadingPage.value = true

  const { data: resultDataWeek } = await useApi<any>(createUrl('/production/plant/inventoryWeek/', {
    query: {
      periodo: props.periodoData,
      categoria: categoria.value,
    },
  }))

  itemsAll.value = resultDataWeek.value?.all
  itemsWeek.value = resultDataWeek.value?.week
  seriesInvetory.value = resultDataWeek.value?.gf
  lineChartConfig.value.xaxis.categories = resultDataWeek.value?.gfc
  key.value = key.value + 1
  loadingPage.value = false
}

loadWeek()

watch(props, () => {
  loadItems()
  loadWeek()
})
</script>

<template>
  <VRow>
    <VCol cols="6">
      <VCol cols="12">
        <VCard :title="`${$t('Label.Andamento generale Magazzino')}`">
          <VTable
            density="compact"
            class="text-no-wrap"
          >
            <thead>
            <tr>
              <th>
                Label
              </th>
              <th v-for="categoria in categorie">
                {{ categoria }}
              </th>
            </tr>
            </thead>

            <tbody>
            <tr
              v-for="(dati, name) in items"
              :class="name === 'Total' ? 'bg-total' : ''"
            >
              <td>
                {{ name }}
              </td>
              <td v-for="item in dati">
                {{ item }}
              </td>
            </tr>
            </tbody>
          </VTable>
        </VCard>
      </VCol>
      <VCol cols="12">
        <VCard
          title="Riepilogo generale"
          class="mt-3"
        >
          <VTable
            density="compact"
            class="text-no-wrap"
          >
            <thead>
            <tr>
              <th>
                Label
              </th>
              <th>
                0-30 Days
              </th>
              <th>
                31-60 Days
              </th>
              <th>
                61-90 Days
              </th>
              <th>
                91-120 Days
              </th>
              <th>
                121-180 Days
              </th>
              <th>
                180 Days & above
              </th>
              <th>
                Grand Total
              </th>
            </tr>
            </thead>

            <tbody>
            <tr
              v-for="(dati, name) in itemsWeek"
              :class="name === 'Total' ? 'bg-total' : ''"
            >
              <td>
                {{ name }}
              </td>
              <td>{{ dati['0-30 Days'] }}</td>
              <td>{{ dati['31-60 Days'] }}</td>
              <td>{{ dati['61-90 Days'] }}</td>
              <td>{{ dati['91-120 Days'] }}</td>
              <td>{{ dati['121-180 Days'] }}</td>
              <td>{{ dati['180 Days & above'] }}</td>
              <td>{{ dati['total'] }}</td>
            </tr>
            </tbody>
          </VTable>
        </VCard>
      </VCol>
      <VCol cols="12">
        <VCard
          v-for="(datiAll, name) in itemsAll"
          :title="name"
          class="mt-2"
        >
          <div>
            <VCol
              cols="12"
              offset-md="8"
              md="4"
            >
              <AppSelect
                v-model="categoria"
                :items="['Fiber Optic OFC', 'Finished Product OFC', 'Finished Product CC', 'Packaging', 'Raw Material CC', 'Raw Material OFC',
                       'WIP CC', 'OI', 'WIP OFC']"
                placeholder="Select Item"
                @focusout="loadWeek"
              />
            </VCol>
            <VTable
              density="compact"
              class="text-no-wrap"
            >
              <thead>
              <tr>
                <th>
                  Label
                </th>
                <th>
                  0-30 Days
                </th>
                <th>
                  31-60 Days
                </th>
                <th>
                  61-90 Days
                </th>
                <th>
                  91-120 Days
                </th>
                <th>
                  121-180 Days
                </th>
                <th>
                  180 Days & above
                </th>
                <th>
                  Grand Total
                </th>
              </tr>
              </thead>

              <tbody>
              <tr
                v-for="(dati, name) in datiAll"
                :class="name === 'Total' ? 'bg-total' : ''"
              >
                <td>
                  {{ name }}
                </td>
                <td>{{ dati['0-30 Days'] }}</td>
                <td>{{ dati['31-60 Days'] }}</td>
                <td>{{ dati['61-90 Days'] }}</td>
                <td>{{ dati['91-120 Days'] }}</td>
                <td>{{ dati['121-180 Days'] }}</td>
                <td>{{ dati['180 Days & above'] }}</td>
                <td>{{ dati['total'] }}</td>
              </tr>
              </tbody>
            </VTable>
          </div>

        </VCard>
      </VCol>
    </VCol>
    <VCol cols="6">
      <VCol cols="12">
        <VCard :title="`${$t('Label.Andamento inventario - Prodotti finiti e WIP')}`">
          <VueApexCharts
            :key="key"
            type="bar"
            :options="balanceChartConfig"
            :series="series"
          />
        </VCard>
      </VCol>
      <VCol cols="12">
        <VCard :title="`${$t('Label.Andamento inventario Settimanale')}`">
          <VueApexCharts
            :key="key"
            height="450"
            type="line"
            :options="lineChartConfig"
            :series="seriesInvetory"
          />
        </VCard>
      </VCol>
    </VCol>
  </VRow>
  <VRow>

  </VRow>
  <LoadingStandBy v-model="loadingPage"></LoadingStandBy>
</template>

<style scoped lang="scss">

</style>
