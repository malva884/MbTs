<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { useTheme } from 'vuetify'
import { getLineChartCustomConfig } from '@core/libs/apex-chart/apexCharConfig'

const vuetifyTheme = useTheme()

const balanceChartConfig = computed(() => getLineChartCustomConfig(vuetifyTheme.current.value))


interface Props {
  periodoData: string
  meseSelezionato: string
}

const props = defineProps<Props>()
const { t } = useI18n()
const key = ref(1)
const meseSelezionato = ref('')
const items = ref({})
const series = ref({})
const categorie = ref({})
const loadingPage = ref(false)

const loadItems = async () => {
  loadingPage.value = true
  const { data: resultData } = await useApi<any>(createUrl('/production/plant/inventory/', {
    query: {
      periodo: props.periodoData,
    },
  }))

  items.value = resultData.value.dati
  series.value = resultData.value.series
  balanceChartConfig.value.xaxis.categories = resultData.value.categories
  categorie.value = resultData.value.categories
  key.value = key.value + 1
  meseSelezionato.value = new Date(props.meseSelezionato).toLocaleString('en', { month: 'short' })
  loadingPage.value = false
}

loadItems()
watch(props, () => {
  loadItems()
})
</script>

<template>
  <VRow>
    <VCol cols="6">
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
              {{categoria}}
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
    <VCol cols="6">
      <VCard :title="`${$t('Label.Andamento inventario - Prodotti finiti e WIP')}`">
        <VueApexCharts
          :key="key"
          type="bar"

          :options="balanceChartConfig"
          :series="series"
        />
      </VCard>
    </VCol>
  </VRow>
  <LoadingStandBy v-model="loadingPage"></LoadingStandBy>
</template>

<style scoped lang="scss">

</style>
