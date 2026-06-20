<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import PlantTabPerformance from '@/views/production/dashboard/report/plant/PlantTabPerformance.vue'
import PlantTabRevenue from '@/views/production/dashboard/report/plant/PlantTabRevenue.vue'
import PlantTabProduction from '@/views/production/dashboard/report/plant/PlantTabProduction.vue'
import PlantTabDispatch from '@/views/production/dashboard/report/plant/PlantTabDispatch.vue'
import PlantTabInventory from '@/views/production/dashboard/report/plant/PlantTabInventory.vue'
import PlantTabHR from '@/views/production/dashboard/report/plant/PlantTabHR.vue'
import PlantTabCost from '@/views/production/dashboard/report/plant/PlantTabCost.vue'
import PlantTabMacchine from '@/views/production/dashboard/report/plant/PlantTabMacchine.vue'
import PlantTabFermi from '@/views/production/dashboard/report/plant/PlantTabFermi.vue'
import { can } from '@layouts/plugins/casl'
import PlantTabMovement from '@/views/production/dashboard/report/plant/PlantTabMovement.vue'

definePage({
  meta: {
    action: 'report',
    subject: 'Produzione-Performance',
  },
})

const { t } = useI18n()
const d = new Date()
const month = d.toLocaleString('en', { month: 'long' })
const year = d.getFullYear()
const date = ref(`${year} ${month}`)

const userTab = ref(null)

const tabs = []

if (can('report', 'Produzione-Performance')) {
  tabs.push(
    { icon: 'tabler-chart-histogram', title: t('Label.Performance') },
    { icon: 'tabler-building-factory-2', title: t('Label.Produzione') },
    { icon: 'tabler-timeline', title: t('Label.Ore-Macchina') },
    { icon: 'tabler-player-pause', title: t('Label.Efficenza-Macchine') },
  )
}

if (can('report', 'Finanze-Fatturato')) {
  tabs.push({ icon: 'tabler-currency-euro', title: t('Label.Fatturato') },
    { icon: 'tabler-coin-euro', title: t('Label.Costi') },
    { icon: 'tabler-users-group', title: t('Label.HR') },
  )
}

if (can('report', 'Finanze-Spedito'))
  tabs.push({ icon: 'tabler-tir', title: t('Label.Spedito') })

if (can('report', 'Produzione-Magazzino')) {
  tabs.push(
    { icon: 'tabler-building-warehouse', title: t('Label.Magazzino') },
    { icon: 'tabler-arrows-exchange-2', title: t('Label.Movimenti di Magazzino') },
  )
}

/*
const tabs = [
  { icon: 'tabler-chart-histogram', title: t('Label.Performance') },
  { icon: 'tabler-currency-euro', title: t('Label.Fatturato') },
  { icon: 'tabler-building-factory-2', title: t('Label.Produzione') },
  { icon: 'tabler-tir', title: t('Label.Spedito') },
  { icon: 'tabler-building-warehouse', title: t('Label.Magazzino') },
  { icon: 'tabler-coin-euro', title: t('Label.Costi') },
  { icon: 'tabler-users-group', title: t('Label.HR') },
  { icon: 'tabler-timeline', title: t('Label.Ore-Macchina') },
  { icon: 'tabler-player-pause', title: t('Label.Efficenza-Macchine') },
]
*/
</script>

<template>
  <VCol cols="12">
    <VCard
      title=""
      class="mb-6"
    >
      <VCardText>
        <VRow>
          <!-- 👉 Periodo Riferimento -->
          <VCol
            cols="12"
            sm="3"
          >
            <AppDateTimePicker
              v-model="date"
              :label="$t('Local.Periodo-Riferimento')"
              :placeholder="$t('Local.Periodo-Riferimento')"
              :config="{ shorthand: true, dateFormat: 'Y F', altFormat: 'Y F' }"
            />
          </VCol>
        </VRow>
      </VCardText>
    </VCard>
    <VCol cols="12">
      <VTabs
        v-model="userTab"
        class="v-tabs-pill"
      >
        <VTab
          v-for="tab in tabs"
          :key="tab.icon"
        >
          <VIcon
            :size="18"
            :icon="tab.icon"
            class="me-1"
          />
          <span>{{ tab.title }}</span>
        </VTab>
      </VTabs>

      <VWindow
        v-model="userTab"
        class="mt-6 disable-tab-transition"
        :touch="false"
      >
        <VWindowItem v-if="can('report', 'Produzione-Performance')">
          <PlantTabPerformance :periodo-data="date" />
        </VWindowItem>

        <VWindowItem v-if="can('report', 'Produzione-Performance')">
          <PlantTabProduction
            :periodo-data="date"
            :mese-selezionato="date"
          />
        </VWindowItem>

        <VWindowItem v-if="can('report', 'Produzione-Performance')">
          <PlantTabMacchine
            :periodo-data="date"
            :mese-selezionato="date"
          />
        </VWindowItem>

        <VWindowItem v-if="can('report', 'Produzione-Performance')">
          <PlantTabFermi
            :periodo-data="date"
            :mese-selezionato="date"
          />
        </VWindowItem>

        <VWindowItem v-if="can('report', 'Finanze-Fatturato')">
          <PlantTabRevenue
            :periodo-data="date"
            :mese-selezionato="date"
          />
        </VWindowItem>

        <VWindowItem v-if="can('report', 'Produzione-Fatturato')">
          <PlantTabCost
            :periodo-data="date"
            :mese-selezionato="date"
          />
        </VWindowItem>

        <VWindowItem v-if="can('report', 'Produzione-Fatturato')">
          <PlantTabHR
            :periodo-data="date"
            :mese-selezionato="date"
          />
        </VWindowItem>

        <VWindowItem v-if="can('report', 'Finanze-Spedito')">
          <PlantTabDispatch
            :periodo-data="date"
            :mese-selezionato="date"
          />
        </VWindowItem>

        <VWindowItem v-if="can('report', 'Produzione-Magazzino')">
          <PlantTabInventory
            :periodo-data="date"
            :mese-selezionato="date"
          />
        </VWindowItem>

        <VWindowItem v-if="can('report', 'Produzione-Magazzino')">
          <PlantTabMovement
            :periodo-data="date"
            :mese-selezionato="date"
          />
        </VWindowItem>
      </VWindow>
    </VCol>
  </VCol>
</template>
