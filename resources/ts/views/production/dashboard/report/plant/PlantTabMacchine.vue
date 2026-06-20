<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { useI18n } from 'vue-i18n'

interface Props {
  periodoData: string
  meseSelezionato: string
}

const props = defineProps<Props>()
const dataCorrente = new Date()
const { t } = useI18n()
const dataFilter = ref(`${dataCorrente.getFullYear()}-${dataCorrente.getMonth() + 1}-01 to ${dataCorrente.getFullYear()}-${dataCorrente.getMonth() + 1}-${dataCorrente.getUTCDate()}`)
const loading = ref(true)
const serverItems = ref<any[]>([])
const sortBy = ref<string | null>(null)
const sortDesc = ref(false)


const sortedItems = computed(() => {
  if (!sortBy.value) return serverItems.value
  const key = sortBy.value
  const desc = sortDesc.value ? -1 : 1
  return [...serverItems.value].sort((a, b) => {
    const av = a[key] ?? 0
    const bv = b[key] ?? 0
    const aNum = Number.parseFloat(av)
    const bNum = Number.parseFloat(bv)
    if (!Number.isNaN(aNum) && !Number.isNaN(bNum))
      return (aNum - bNum) * desc
    return String(av).localeCompare(String(bv)) * desc
  })
})

const toggleSort = (key: string) => {
  if (sortBy.value === key) {
    sortDesc.value = !sortDesc.value
  } else {
    sortBy.value = key
    sortDesc.value = false
  }
}

const loadItems = async () => {
  loading.value = true

  const { data: resultData } = await useApi<any>(createUrl('/production/plant/machines/', {
    query: {
      periodo: dataFilter.value,
    },
  }))

  serverItems.value = resultData.value ?? []
  loading.value = false
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
            <VIcon icon="tabler-clock" color="primary" size="22" />
            <div>
              <span class="text-subtitle-1 font-weight-bold text-high-emphasis">
                {{ t('Label.Ore-Macchina') }}
              </span>
              <span class="text-caption text-disabled ms-2">({{ dataFilter }})</span>
            </div>
          </div>

          <div class="d-flex align-center gap-2">
            <VBtn
              variant="tonal"
              color="secondary"
              size="small"
              prepend-icon="tabler-screen-share"
              :href="`/api/export/machinesExport?periodo=${dataFilter}`"
            >
              Export
            </VBtn>
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
              <th class="text-start font-weight-bold col-machine sortable" @click="toggleSort('Macchina')">
                {{ t('Label.Macchina') }}
                <VIcon v-if="sortBy === 'Macchina'" :icon="sortDesc ? 'tabler-chevron-down' : 'tabler-chevron-up'" size="14" class="ms-1" />
              </th>
              <th class="text-center font-weight-bold col-num sortable" @click="toggleSort('OreMacchina')">
                {{ t('Label.Ore-Macchina') }}
                <VIcon v-if="sortBy === 'OreMacchina'" :icon="sortDesc ? 'tabler-chevron-down' : 'tabler-chevron-up'" size="14" />
              </th>
              <th class="text-center font-weight-bold col-num sortable" @click="toggleSort('FermiMacchina')">
                {{ t('Label.Fermi-Macchina') }}
                <VIcon v-if="sortBy === 'FermiMacchina'" :icon="sortDesc ? 'tabler-chevron-down' : 'tabler-chevron-up'" size="14" />
              </th>
              <th class="text-center font-weight-bold col-num sortable" @click="toggleSort('F1')">
                {{ t('Label.F1') }}
                <VIcon v-if="sortBy === 'F1'" :icon="sortDesc ? 'tabler-chevron-down' : 'tabler-chevron-up'" size="14" />
              </th>
              <th class="text-center font-weight-bold col-num sortable" @click="toggleSort('F5')">
                {{ t('Label.F5') }}
                <VIcon v-if="sortBy === 'F5'" :icon="sortDesc ? 'tabler-chevron-down' : 'tabler-chevron-up'" size="14" />
              </th>
              <th class="text-center font-weight-bold col-num sortable" @click="toggleSort('TotaleOreMacchina')">
                {{ t('Label.Total-Ore-Macchina') }}
                <VIcon v-if="sortBy === 'TotaleOreMacchina'" :icon="sortDesc ? 'tabler-chevron-down' : 'tabler-chevron-up'" size="14" />
              </th>
              <th class="text-center font-weight-bold col-num sortable" @click="toggleSort('CostoMacchina')">
                {{ t('Label.Costo-Macchina') }}
                <VIcon v-if="sortBy === 'CostoMacchina'" :icon="sortDesc ? 'tabler-chevron-down' : 'tabler-chevron-up'" size="14" />
              </th>
              <th class="text-center font-weight-bold col-num sortable" @click="toggleSort('TotaleCostoMecchina')">
                {{ t('Label.Total-Costo-Macchina') }}
                <VIcon v-if="sortBy === 'TotaleCostoMecchina'" :icon="sortDesc ? 'tabler-chevron-down' : 'tabler-chevron-up'" size="14" />
              </th>
              <th class="text-center font-weight-bold col-num sortable" @click="toggleSort('OreManodopera')">
                {{ t('Label.Ore-Manodopera') }}
                <VIcon v-if="sortBy === 'OreManodopera'" :icon="sortDesc ? 'tabler-chevron-down' : 'tabler-chevron-up'" size="14" />
              </th>
              <th class="text-center font-weight-bold col-num sortable" @click="toggleSort('FermiManoDopera')">
                {{ t('Label.Fermi-Manodopera') }}
                <VIcon v-if="sortBy === 'FermiManoDopera'" :icon="sortDesc ? 'tabler-chevron-down' : 'tabler-chevron-up'" size="14" />
              </th>
              <th class="text-center font-weight-bold col-num sortable" @click="toggleSort('ManodoperaCalcolataH')">
                {{ t('Label.Ore-Manodopera-Calcolata') }}
                <VIcon v-if="sortBy === 'ManodoperaCalcolataH'" :icon="sortDesc ? 'tabler-chevron-down' : 'tabler-chevron-up'" size="14" />
              </th>
              <th class="text-center font-weight-bold col-num sortable" @click="toggleSort('RapportMacchina')">
                {{ t('Label.Rapporto-mac/man') }}
                <VIcon v-if="sortBy === 'RapportMacchina'" :icon="sortDesc ? 'tabler-chevron-down' : 'tabler-chevron-up'" size="14" />
              </th>
              <th class="text-center font-weight-bold col-eff sortable" @click="toggleSort('efficenza')">
                {{ t('Label.Efficenza') }}
                <VIcon v-if="sortBy === 'efficenza'" :icon="sortDesc ? 'tabler-chevron-down' : 'tabler-chevron-up'" size="14" />
              </th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="(item, index) in sortedItems" :key="index">
              <td class="text-start font-weight-bold text-high-emphasis">
                {{ item.Macchina }}
              </td>
              <td class="text-center">
                <span class="font-weight-semibold">{{ formatNumber(item.OreMacchina) }}</span>
              </td>
              <td class="text-center">
                <span class="font-weight-semibold text-error">{{ formatNumber(item.FermiMacchina) }}</span>
              </td>
              <td class="text-center">
                <span class="font-weight-semibold text-info">{{ formatNumber(item.F1) }}</span>
              </td>
              <td class="text-center">
                <span class="font-weight-semibold text-warning">{{ formatNumber(item.F5) }}</span>
              </td>
              <td class="text-center">
                <span class="font-weight-semibold text-success">{{ formatNumber(item.TotaleOreMacchina) }}</span>
              </td>
              <td class="text-center">
                <span class="font-weight-semibold">{{ formatNumber(item.CostoMacchina) }}</span>
              </td>
              <td class="text-center">
                <span class="font-weight-semibold">{{ formatNumber(item.TotaleCostoMecchina) }}</span>
              </td>
              <td class="text-center">
                <span class="font-weight-semibold">{{ formatNumber(item.OreManodopera) }}</span>
              </td>
              <td class="text-center">
                <span class="font-weight-semibold text-error">{{ formatNumber(item.FermiManoDopera) }}</span>
              </td>
              <td class="text-center">
                <span class="font-weight-semibold">{{ formatNumber(item.ManodoperaCalcolataH) }}</span>
              </td>
              <td class="text-center">
                <span class="font-weight-semibold text-primary">{{ formatNumber(item.RapportMacchina) }}</span>
              </td>
              <td class="text-center">
                <VChip
                  size="x-small"
                  variant="tonal"
                  :color="item.efficenza >= 70 ? 'success' : item.efficenza >= 45 ? 'warning' : 'error'"
                  class="font-weight-bold px-3"
                >
                  {{ formatNumber(item.efficenza) }} %
                </VChip>
              </td>
            </tr>
          </tbody>
        </VTable>
      </VCard>
    </VCol>
  </VRow>

  <LoadingStandBy v-model="loading" />
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

  .elegant-table {
    thead th {
      position: sticky;
      top: 0;
      z-index: 2;
      background-color: rgb(var(--v-theme-surface)) !important;
      font-size: 0.7rem !important;
      text-transform: uppercase;
      font-weight: 700 !important;
      color: rgba(var(--v-theme-on-surface), 0.65) !important;
      letter-spacing: 0.5px;
      height: 40px !important;
      padding-inline: 4px !important;
      border-bottom: 2px solid rgba(var(--v-border-color), 0.12) !important;

      &.col-machine { width: 120px; min-width: 120px; }
      &.col-num     { width: 70px; min-width: 70px; }
      &.col-eff     { width: 80px; min-width: 80px; }

      &.sortable {
        cursor: pointer;
        user-select: none;
        transition: color 0.15s ease;

        &:hover {
          color: rgb(var(--v-theme-primary)) !important;
        }
      }
    }

    tbody tr {
      height: 36px !important;
      transition: background-color 0.15s ease;

      &:nth-of-type(odd) {
        background-color: rgba(var(--v-theme-on-surface), 0.01) !important;
      }

      &:hover {
        background-color: rgba(var(--v-theme-primary), 0.04) !important;
      }

      td {
        font-size: 0.78rem !important;
        padding-inline: 4px !important;
        border-bottom: 1px solid rgba(var(--v-border-color), 0.05) !important;
      }
    }
  }
}
</style>
