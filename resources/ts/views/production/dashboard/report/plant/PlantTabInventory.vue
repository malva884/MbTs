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

const agingRanges = [
  '0-30 Days',
  '31-60 Days',
  '61-90 Days',
  '91-120 Days',
  '121-180 Days',
  '180 Days & above',
]

const agingColors: Record<string, string> = {
  '0-30 Days': '#1E90FF',
  '31-60 Days': '#FF1493',
  '61-90 Days': '#008000',
  '91-120 Days': '#FFD700',
  '121-180 Days': '#FF8C00',
  '180 Days & above': '#E9967A',
}

const weekCategories = computed(() => Object.keys(itemsWeek.value).length)
const itemsCategories = computed(() => Object.keys(items.value).length)

// 👉 Heatmap data: current month + Rif per each range
const heatmapWeeks = computed(() => {
  if (!itemsAll.value || typeof itemsAll.value !== 'object') return []
  const catData = itemsAll.value[categoria.value]
  if (!catData) return []
  return Object.keys(catData).sort()
})

const heatmapMatrix = computed(() => {
  if (!itemsAll.value || typeof itemsAll.value !== 'object') return []
  const catData = itemsAll.value[categoria.value]
  if (!catData) return []
  const weeks = heatmapWeeks.value
  const result: { name: string; data: any[]; isRif: boolean }[] = []

  agingRanges.forEach(range => {
    // Current month row
    result.push({
      name: range,
      data: weeks.map(week => {
        const weekData = catData[week]
        return weekData && weekData[range] != null ? weekData[range] : 0
      }),
      isRif: false,
    })
    // Rif (previous month) row for same range
    const rifSeriesItem = Array.isArray(seriesInvetory.value)
      ? seriesInvetory.value.find((s: any) => s.name === `${range} Rif`)
      : null
    if (rifSeriesItem && rifSeriesItem.data) {
      result.push({
        name: `${range} (Rif)`,
        data: rifSeriesItem.data,
        isRif: true,
      })
    }
  })

  return result
})

// 👉 Heatmap for Rif row uses different color
const isRifRow = (name: string) => name.includes('(Rif)')

const heatmapMaxValue = computed(() => {
  let max = 0
  heatmapMatrix.value.forEach((series: any) => {
    series.data.forEach((val: number) => {
      if (val > max) max = val
    })
  })
  return max || 1
})

function getHeatmapColor(value: number): string {
  if (value === 0) return 'transparent'
  const intensity = value / heatmapMaxValue.value
  const opacity = Math.max(0.15, intensity).toFixed(2)
  return `rgba(30, 144, 255, ${opacity})`
}

function getRifHeatmapColor(value: number): string {
  if (value === 0) return 'transparent'
  const intensity = value / heatmapMaxValue.value
  const opacity = Math.max(0.15, intensity).toFixed(2)
  return `rgba(158, 158, 158, ${opacity})`
}

function getTrend(seriesName: string, weekIdx: number): 'up' | 'down' | 'equal' | null {
  const rifRow = heatmapMatrix.value.find((r: any) => r.name === `${seriesName} (Rif)`)
  if (!rifRow) return null
  const currentRow = heatmapMatrix.value.find((r: any) => r.name === seriesName && !r.isRif)
  if (!currentRow) return null
  const currentVal = currentRow.data[weekIdx] || 0
  const rifVal = rifRow.data[weekIdx] || 0
  if (currentVal > rifVal) return 'up'
  if (currentVal < rifVal) return 'down'
  return 'equal'
}

const themeColors = {
  disabledText: 'rgba(var(--v-theme-on-surface), 0.6)',
  borderColor: 'rgba(var(--v-theme-on-surface), 0.12)',
}

const loadItems = async () => {
  loadingPage.value = true
  const {data: resultData} = await useApi<any>(createUrl('/production/plant/inventory/', {
    query: {
      periodo: props.periodoData,
    },
  }))

  items.value = resultData.value.dati
  series.value = Array.isArray(resultData.value?.series) ? resultData.value.series : []
  balanceChartConfig.value.xaxis.categories = resultData.value.categories || []
  categorie.value = resultData.value.categories || []
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
  seriesInvetory.value = Array.isArray(resultDataWeek.value?.gf) ? resultDataWeek.value.gf : []
  lineChartConfig.value.xaxis.categories = resultDataWeek.value?.gfc || []
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
        <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
          <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
            <div class="d-flex align-center gap-2">
              <VIcon icon="tabler-building-warehouse" size="24" color="primary" />
              <div>
                <div class="text-h6 font-weight-medium">{{ $t('Label.Andamento generale Magazzino') }}</div>
                <div class="text-caption text-medium-emphasis">{{ itemsCategories }} categorie</div>
              </div>
            </div>
          </VCardText>
          <VDivider />

          <div class="overflow-x-auto">
            <table class="aging-table">
              <thead>
                <tr>
                  <th class="cat-header">Categoria</th>
                  <th
                    v-for="cat in categorie"
                    :key="cat"
                    class="aging-header"
                  >
                    {{ cat }}
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(dati, name) in items"
                  :key="name"
                  :class="name === 'Total' ? 'row-total' : ''"
                >
                  <td class="cat-cell">
                    <div class="font-weight-medium">{{ name }}</div>
                  </td>
                  <td
                    v-for="(item, idx) in dati"
                    :key="idx"
                    class="aging-cell"
                  >
                    <span class="aging-value">{{ item }}</span>
                  </td>
                </tr>
                <tr v-if="itemsCategories === 0">
                  <td :colspan="(Array.isArray(categorie) ? categorie.length : 0) + 1" class="text-center py-4 text-disabled">
                    Nessun dato trovato
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </VCard>
      </VCol>
      <VCol cols="12">
        <VCard variant="outlined" class="bg-surface border-thin rounded-lg mt-3">
          <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
            <div class="d-flex align-center gap-2">
              <VIcon icon="tabler-chart-arrows-vertical" size="24" color="primary" />
              <div>
                <div class="text-h6 font-weight-medium">Riepilogo generale</div>
                <div class="text-caption text-medium-emphasis">{{ weekCategories }} categorie</div>
              </div>
            </div>

            <div class="d-flex align-center gap-3 flex-wrap">
              <div class="text-subtitle-2 font-weight-medium">Legenda:</div>
              <div
                v-for="range in agingRanges"
                :key="range"
                class="d-flex align-center gap-1"
              >
                <span
                  class="aging-badge"
                  :style="{ backgroundColor: agingColors[range] }"
                >
                  {{ range.split(' ')[0] }}
                </span>
                <span class="text-caption">{{ range }}</span>
              </div>
            </div>
          </VCardText>
          <VDivider />

          <div class="overflow-x-auto">
            <table class="aging-table">
              <thead>
                <tr>
                  <th class="cat-header">Categoria</th>
                  <th
                    v-for="range in agingRanges"
                    :key="range"
                    class="aging-header"
                    :style="{ borderBottom: `3px solid ${agingColors[range]}` }"
                  >
                    {{ range }}
                  </th>
                  <th class="aging-header total-header">Grand Total</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(dati, name) in itemsWeek"
                  :key="name"
                  :class="name === 'Total' ? 'row-total' : ''"
                >
                  <td class="cat-cell">
                    <div class="font-weight-medium">{{ name }}</div>
                  </td>
                  <td
                    v-for="range in agingRanges"
                    :key="range"
                    class="aging-cell"
                    :style="{ borderLeft: `3px solid ${agingColors[range]}` }"
                  >
                    <span
                      v-if="dati[range]"
                      class="aging-value"
                      :style="{ color: agingColors[range] }"
                    >
                      {{ dati[range] }}
                    </span>
                    <span v-else class="text-disabled">0</span>
                  </td>
                  <td class="aging-cell total-cell">
                    <span class="font-weight-bold">{{ dati['total'] }}</span>
                  </td>
                </tr>
                <tr v-if="weekCategories === 0">
                  <td :colspan="agingRanges.length + 2" class="text-center py-4 text-disabled">
                    Nessun dato trovato
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </VCard>
      </VCol>
      <VCol cols="12">
        <VCard
          v-for="(datiAll, name) in itemsAll"
          :key="name"
          variant="outlined"
          class="bg-surface border-thin rounded-lg mt-3"
        >
          <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
            <div class="d-flex align-center gap-2">
              <VAvatar color="primary" variant="tonal" size="38">
                <VIcon icon="tabler-grid-pattern" size="20" />
              </VAvatar>
              <div>
                <div class="text-h6 font-weight-medium">{{ name }}</div>
                <div class="text-caption text-medium-emphasis">{{ Object.keys(datiAll).length }} settimane</div>
              </div>
            </div>
            <div class="d-flex align-center gap-3 flex-wrap">
              <AppSelect
                v-model="categoria"
                :items="['Fiber Optic OFC', 'Finished Product OFC', 'Finished Product CC', 'Packaging', 'Raw Material CC', 'Raw Material OFC', 'WIP CC', 'OI', 'WIP OFC']"
                density="compact"
                style="min-width: 200px;"
                placeholder="Select Item"
                @update:model-value="loadWeek"
              />
            </div>
          </VCardText>
          <VDivider />
          <div class="d-flex align-center gap-3 flex-wrap px-4 py-2">
            <div class="text-subtitle-2 font-weight-medium me-2">Legenda:</div>
            <div
              v-for="range in agingRanges"
              :key="range"
              class="d-flex align-center gap-1"
            >
              <span
                class="aging-badge"
                :style="{ backgroundColor: agingColors[range] }"
              >
                {{ range.split(' ')[0] }}
              </span>
              <span class="text-caption">{{ range }}</span>
            </div>
          </div>

          <div class="overflow-x-auto">
            <table class="heatmap-table">
              <thead>
                <tr>
                  <th class="heatmap-header heatmap-cat-header">Settimana</th>
                  <th
                    v-for="range in agingRanges"
                    :key="range"
                    class="heatmap-header"
                    :style="{ borderBottom: `3px solid ${agingColors[range]}` }"
                  >
                    {{ range }}
                  </th>
                  <th class="heatmap-header total-header">Grand Total</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(dati, weekLabel) in datiAll"
                  :key="weekLabel"
                  :class="weekLabel === 'Total' ? 'row-total' : ''"
                >
                  <td class="heatmap-cat-cell">
                    <div class="font-weight-medium">{{ weekLabel }}</div>
                  </td>
                  <td
                    v-for="range in agingRanges"
                    :key="range"
                    class="heatmap-cell"
                    :style="{ borderLeft: `3px solid ${agingColors[range]}` }"
                  >
                    <span
                      v-if="dati[range]"
                      class="heatmap-value"
                      :style="{ color: agingColors[range] }"
                    >
                      {{ dati[range] }}M
                    </span>
                    <span v-else class="text-disabled">-</span>
                  </td>
                  <td class="heatmap-cell total-cell">
                    <span class="font-weight-bold">{{ dati['total'] }}</span>
                  </td>
                </tr>
                <tr v-if="Object.keys(datiAll).length === 0">
                  <td :colspan="agingRanges.length + 2" class="text-center py-4 text-disabled">
                    Nessun dato trovato
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </VCard>
      </VCol>
    </VCol>
    <VCol cols="6">
      <VCol cols="12">
        <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
          <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
            <div class="d-flex align-center gap-2">
              <VIcon icon="tabler-chart-bar" size="24" color="primary" />
              <div>
                <div class="text-h6 font-weight-medium">{{ $t('Label.Andamento inventario - Prodotti finiti e WIP') }}</div>
                <div class="text-caption text-medium-emphasis">{{ Array.isArray(series) ? series.length : 0 }} serie</div>
              </div>
            </div>
          </VCardText>
          <VDivider />
          <VCardText class="pa-2">
            <VueApexCharts
              :key="key"
              type="bar"
              :options="balanceChartConfig"
              :series="series"
            />
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12">
        <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
          <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
            <div class="d-flex align-center gap-2">
              <VAvatar color="primary" variant="tonal" size="38">
                <VIcon icon="tabler-chart-line" size="20" />
              </VAvatar>
              <div>
                <div class="text-h6 font-weight-medium">{{ $t('Label.Andamento inventario Settimanale') }}</div>
                <div class="text-caption text-medium-emphasis">{{ categoria }}</div>
              </div>
            </div>
            <div class="d-flex align-center gap-3 flex-wrap">
              <AppSelect
                v-model="categoria"
                :items="['Fiber Optic OFC', 'Finished Product OFC', 'Finished Product CC', 'Packaging', 'Raw Material CC', 'Raw Material OFC', 'WIP CC', 'OI', 'WIP OFC']"
                density="compact"
                style="min-width: 200px;"
                @update:model-value="loadWeek"
              />
            </div>
          </VCardText>
          <VDivider />

          <VCardText class="pa-4">
            <div class="overflow-x-auto">
              <table class="heatmap-table">
                    <thead>
                      <tr>
                        <th class="heatmap-header heatmap-cat-header">Range \\ Settimana</th>
                        <th
                          v-for="week in heatmapWeeks"
                          :key="week"
                          class="heatmap-header"
                        >
                          {{ week }}
                        </th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr
                        v-for="series in heatmapMatrix"
                        :key="series.name"
                        :class="series.isRif ? 'rif-row' : ''"
                      >
                        <td class="heatmap-cat-cell">
                          <div class="d-flex align-center gap-1">
                            <span
                              v-if="!series.isRif"
                              class="aging-badge"
                              :style="{ backgroundColor: agingColors[series.name] }"
                            >
                              {{ series.name.split(' ')[0] }}
                            </span>
                            <span
                              v-else
                              class="aging-badge"
                              :style="{ backgroundColor: agingColors[series.name.replace(' (Rif)', '')] }"
                            >
                              RIF
                            </span>
                            <span class="text-caption font-weight-medium">{{ series.name }}</span>
                          </div>
                        </td>
                        <td
                          v-for="(val, idx) in series.data"
                          :key="idx"
                          class="heatmap-cell"
                          :style="{ backgroundColor: series.isRif ? getRifHeatmapColor(val) : getHeatmapColor(val) }"
                        >
                          <template v-if="val > 0">
                            <div class="d-flex align-center justify-center gap-1">
                              <span class="heatmap-value">{{ val }}M</span>
                              <VIcon
                                v-if="!series.isRif && getTrend(series.name, idx) === 'up'"
                                icon="tabler-trending-up"
                                :size="14"
                                color="success"
                              />
                              <VIcon
                                v-else-if="!series.isRif && getTrend(series.name, idx) === 'down'"
                                icon="tabler-trending-down"
                                :size="14"
                                color="error"
                              />
                              <VIcon
                                v-else-if="!series.isRif && getTrend(series.name, idx) === 'equal'"
                                icon="tabler-equal"
                                :size="14"
                                color="grey"
                              />
                            </div>
                          </template>
                          <span v-else class="text-disabled">-</span>
                        </td>
                      </tr>
                      <tr v-if="heatmapMatrix.length === 0">
                        <td :colspan="heatmapWeeks.length + 1" class="text-center py-4 text-disabled">
                          Nessun dato trovato
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
          </VCardText>
        </VCard>
      </VCol>
    </VCol>
  </VRow>
  <VRow>

  </VRow>
  <LoadingStandBy v-model="loadingPage"></LoadingStandBy>
</template>

<style scoped lang="scss">

.aging-table {
  border-collapse: collapse;
  width: 100%;
  font-size: 12px;
}

.aging-table th,
.aging-table td {
  border: 1px solid rgba(var(--v-theme-on-surface), 0.12);
  text-align: center;
  padding: 6px 8px;
  height: 36px;
}

.cat-header {
  background-color: rgb(var(--v-theme-primary));
  color: rgb(var(--v-theme-on-primary));
  font-weight: 600;
  text-align: left;
  min-width: 140px;
  position: sticky;
  left: 0;
  z-index: 2;
}

.aging-header {
  background-color: rgb(var(--v-theme-primary));
  color: rgb(var(--v-theme-on-primary));
  font-weight: 600;
  padding: 4px 6px;
  min-width: 80px;
  font-size: 11px;
}

.total-header {
  background-color: rgba(var(--v-theme-primary), 0.85);
}

.cat-cell {
  text-align: left;
  background-color: rgb(var(--v-theme-surface));
  color: rgb(var(--v-theme-on-surface));
  position: sticky;
  left: 0;
  z-index: 1;
  font-weight: 500;
}

.aging-cell {
  transition: background-color 0.2s;
}

.aging-cell:hover {
  background-color: rgba(var(--v-theme-on-surface), 0.08);
}

.total-cell {
  background-color: rgba(var(--v-theme-primary), 0.05);
}

.aging-value {
  font-weight: 600;
  font-size: 13px;
}

.aging-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  padding: 2px 6px;
  font-size: 10px;
  font-weight: 700;
  min-width: 28px;
  height: 20px;
  color: #fff;
}

.row-total {
  background-color: rgba(var(--v-theme-primary), 0.08);

  .cat-cell {
    font-weight: 700;
    background-color: rgba(var(--v-theme-primary), 0.08);
  }

  .aging-cell {
    font-weight: 700;
  }
}

.overflow-x-auto {
  overflow-x: auto;
  max-height: 60vh;
}

.overflow-x-auto thead {
  position: sticky;
  top: 0;
  z-index: 3;
}

.aging-table tbody tr:nth-child(even) .aging-cell:not(.total-cell) {
  background-color: rgba(var(--v-theme-on-surface), 0.03);
}

.aging-table tbody tr:nth-child(even) .cat-cell {
  background-color: rgba(var(--v-theme-on-surface), 0.03);
}

.heatmap-table {
  border-collapse: collapse;
  width: 100%;
  font-size: 12px;
}

.heatmap-table th,
.heatmap-table td {
  border: 1px solid rgba(var(--v-theme-on-surface), 0.12);
  text-align: center;
  padding: 8px 10px;
  height: 40px;
}

.heatmap-header {
  background-color: rgb(var(--v-theme-primary));
  color: rgb(var(--v-theme-on-primary));
  font-weight: 600;
  padding: 4px 6px;
  min-width: 100px;
  font-size: 11px;
}

.heatmap-cat-header {
  text-align: left;
  min-width: 180px;
  position: sticky;
  left: 0;
  z-index: 2;
}

.total-header {
  background-color: rgba(var(--v-theme-primary), 0.85);
}

.total-cell {
  background-color: rgba(var(--v-theme-primary), 0.05);
}

.heatmap-cat-cell {
  text-align: left;
  background-color: rgb(var(--v-theme-surface));
  position: sticky;
  left: 0;
  z-index: 1;
}

.heatmap-cell {
  transition: background-color 0.2s;
  min-width: 80px;
}

.heatmap-cell:hover {
  filter: brightness(1.1);
}

.heatmap-value {
  font-weight: 700;
  font-size: 13px;
}

.cursor-pointer {
  cursor: pointer;
}

.rif-row {
  border-top: 2px solid rgba(var(--v-theme-on-surface), 0.2);

  .heatmap-cat-cell {
    background-color: rgba(158, 158, 158, 0.08);
  }
}

.heatmap-table .row-total {
  background-color: rgba(var(--v-theme-primary), 0.08);

  .heatmap-cat-cell {
    font-weight: 700;
    background-color: rgba(var(--v-theme-primary), 0.08);
  }

  .heatmap-cell {
    font-weight: 700;
  }
}
</style>
