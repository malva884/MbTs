<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { getLineChartConfig } from '@core/libs/apex-chart/apexCharConfig'
import { useTheme } from 'vuetify'

interface Props {
  periodoData: string
  meseSelezionato: string
}

const props = defineProps<Props>()
const vuetifyTheme = useTheme()
const lineoChartConfig = computed(() => getLineChartConfig(vuetifyTheme.current.value))
const dataCorrente = new Date()
const { t } = useI18n()
const key = ref(0)
const dataFilter = ref(`${dataCorrente.getFullYear()}-${dataCorrente.getMonth()+1}-${dataCorrente.getUTCDate()} to ${dataCorrente.getFullYear()}-${dataCorrente.getMonth()+1}-${dataCorrente.getUTCDate()}`)
const loadingPage = ref(false)
const isRunVisible = ref(false)
const serverItems = ref()
const series = ref()
const macchinaName = ref()

const loadItems = async () => {
  loadingPage.value = true

  const { data: resultData } = await useApi<any>(createUrl('/production/plant/downtime/', {
    query: {
      periodo: dataFilter.value,
    },
  }))

  serverItems.value = resultData.value
  loadingPage.value = false
}

const loadRun = async (macchina: string) => {
  loadingPage.value = true

  const { data: resultData } = await useApi<any>(createUrl('/production/plant/speedMachine/', {
    query: {
      periodo: dataFilter.value,
      macchina_n: macchina,
    },
  }))

  macchinaName.value = macchina
  series.value = [resultData.value.series]

  key.value = key.value + 1
  loadingPage.value = false
  isRunVisible.value = true
}

const resolveDowntime = (downtime: any) => {
  if (downtime <= 40)
    return { color: 'error', labelColor: 'text-error', percentuale: downtime }
  else if (downtime <= 70)
    return { color: 'warning', labelColor: 'text-warning', percentuale: downtime }
  else if (downtime <= 100)
    return { color: 'success', labelColor: 'text-success', percentuale: downtime }
  else
    return { color: 'primary', labelColor: 'text-primary', percentuale: 0 }
}

const formatNumber = (value: number, decimals = 2) => {
  return Number(`${Math.round(Number(`${value}e${decimals}`))}e-${decimals}`)
}

loadItems()
watch(props, () => {
  loadItems()
})
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard variant="outlined" class="efficiency-card">

        <div class="py-3 px-4 bg-header d-flex flex-wrap align-center justify-space-between gap-3 border-b">
          <div class="d-flex align-center gap-2">
            <VIcon icon="tabler-gauge" color="primary" size="22" />
            <div>
              <span class="text-subtitle-1 font-weight-bold text-high-emphasis">
                {{ t('Label.Efficenza-Macchine') }}
              </span>
              <span class="text-caption text-disabled ms-2">({{ dataFilter }})</span>
            </div>
          </div>

          <div class="d-flex align-center gap-2">
            <AppDateTimePicker
              v-model="dataFilter"
              placeholder="Select date"
              :config="{ mode: 'range' }"
              density="compact"
              style="width: 240px;"
              @focusout="loadItems"
            />
          </div>
        </div>

        <VTable
          height="650"
          fixed-header
          class="text-no-wrap elegant-table"
        >
          <thead>
          <tr>
            <th class="text-start font-weight-bold">{{ t('Label.Macchina') }}</th>
            <th class="text-center font-weight-bold">{{ `1° ${t('Label.Turno Efficenza')}` }}</th>
            <th class="text-center font-weight-bold">{{ `2° ${t('Label.Turno Efficenza')}` }}</th>
            <th class="text-center font-weight-bold">{{ `3° ${t('Label.Turno Efficenza')}` }}</th>
            <th class="text-center font-weight-bold">{{ t('Label.Total Efficenza') }}</th>
          </tr>
          </thead>

          <tbody>
          <tr v-for="(item, index) in serverItems" :key="index">
            <td class="text-start font-weight-bold text-high-emphasis">
                <span class="interactive-link" @click="loadRun(item.Macchina)">
                  <VIcon icon="tabler-square-chevron-right" size="16" class="me-1 text-primary" />
                  {{ item.Macchina }}
                </span>
            </td>

            <td class="text-center">
                <span class="font-weight-semibold" :class="resolveDowntime(item.uno).labelColor">
                  {{ formatNumber(resolveDowntime(item.uno).percentuale) }} %
                </span>
            </td>

            <td class="text-center">
                <span class="font-weight-semibold" :class="resolveDowntime(item.due).labelColor">
                  {{ formatNumber(resolveDowntime(item.due).percentuale) }} %
                </span>
            </td>

            <td class="text-center">
                <span class="font-weight-semibold" :class="resolveDowntime(item.tre).labelColor">
                  {{ formatNumber(resolveDowntime(item.tre).percentuale) }} %
                </span>
            </td>

            <td class="text-center">
              <VChip
                size="x-small"
                variant="tonal"
                :color="resolveDowntime(item.tot).color"
                class="font-weight-bold px-3"
              >
                {{ formatNumber(resolveDowntime(item.tot).percentuale) }} %
              </VChip>
            </td>
          </tr>
          </tbody>
        </VTable>
      </VCard>
    </VCol>
  </VRow>

  <VDialog
    v-model="isRunVisible"
    persistent
    max-width="1200"
  >
    <VCard class="rounded-lg overflow-hidden shadow-lg">
      <div class="py-3 px-4 bg-header border-b d-flex align-center justify-space-between">
        <div class="d-flex align-center gap-2">
          <VIcon icon="tabler-chart-line" color="info" size="22" />
          <span class="text-body-1 font-weight-bold text-high-emphasis">
            {{ $t('Label.Velocita-Macchina') }}: <span class="text-primary font-weight-black">{{ macchinaName }}</span>
          </span>
          <span class="text-caption text-disabled ms-1">({{ dataFilter }})</span>
        </div>
        <VBtn icon="tabler-x" variant="text" density="comfortable" color="medium-emphasis" @click="isRunVisible = false" />
      </div>

      <VCardText class="pa-4 bg-surface">
        <div class="chart-container rounded border pa-2">
          <VueApexCharts
            :key="key"
            type="line"
            height="500"
            :options="lineoChartConfig"
            :series="series"
          />
        </div>
      </VCardText>

      <VDivider />
      <div class="py-3 px-4 bg-header d-flex justify-end">
        <VBtn
          color="secondary"
          variant="tonal"
          size="small"
          class="font-weight-bold"
          @click="isRunVisible = false"
        >
          Close
        </VBtn>
      </div>
    </VCard>
  </VDialog>

  <LoadingStandBy v-model="loadingPage" />
</template>

<style scoped lang="scss">
.efficiency-card {
  border-radius: 8px;
  background-color: rgb(var(--v-theme-surface));
  border: 1px solid rgba(var(--v-border-color), 0.12) !important;
  overflow: hidden;

  .border-b {
    border-bottom: 1px solid rgba(var(--v-border-color), 0.08) !important;
  }

  .bg-header {
    background-color: rgba(var(--v-theme-on-surface), 0.015);
  }

  // Personalizzazione stilistica della tabella interna
  .elegant-table {
    background: transparent !important;

    thead th {
      background-color: rgba(var(--v-theme-on-surface), 0.02) !important;
      font-size: 0.75rem !important;
      text-transform: uppercase;
      font-weight: 700 !important;
      color: rgba(var(--v-theme-on-surface), 0.65) !important;
      letter-spacing: 0.5px;
      height: 44px !important;
      border-bottom: 2px solid rgba(var(--v-border-color), 0.12) !important;
    }

    tbody tr {
      height: 40px !important;
      transition: background-color 0.15s ease;

      // Sfondo alternato leggerissimo per non appesantire la vista
      &:nth-of-type(odd) {
        background-color: rgba(var(--v-theme-on-surface), 0.01) !important;
      }

      &:hover {
        background-color: rgba(var(--v-theme-primary), 0.04) !important;
      }

      td {
        font-size: 0.85rem !important;
        border-bottom: 1px solid rgba(var(--v-border-color), 0.05) !important;
      }
    }

    // Link interattivo sulla prima colonna delle macchine
    .interactive-link {
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      transition: color 0.15s ease;

      &:hover {
        color: rgb(var(--v-theme-primary)) !important;
        text-decoration: underline;
      }
    }
  }
}

// Container del grafico interno al pop-up
.chart-container {
  background-color: rgb(var(--v-theme-surface));
  border: 1px solid rgba(var(--v-border-color), 0.08) !important;
}
</style>
