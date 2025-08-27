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

const tabs = [
  { icon: 'tabler-chart-histogram', title: t('Label.Performance') },
  { icon: 'tabler-currency-euro', title: t('Label.Fatturato') },
  { icon: 'tabler-building-factory-2', title: t('Label.Produzione') },
  { icon: 'tabler-tir', title: t('Label.Spedito') },
  { icon: 'tabler-building-warehouse', title: t('Label.Magazzino') },
  { icon: 'tabler-coin-euro', title: t('Label.Costi') },
  { icon: 'tabler-users-group', title: t('Label.HR') },
  { icon: 'tabler-timeline', title: t('Label.Ore-Macchina') },
]
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
        <VWindowItem>
          <PlantTabPerformance :periodo-data="date" />
        </VWindowItem>

        <VWindowItem>
          <PlantTabRevenue :periodo-data="date" :mese-selezionato="date" />
        </VWindowItem>

        <VWindowItem>
          <PlantTabProduction :periodo-data="date" :mese-selezionato="date" />
        </VWindowItem>

        <VWindowItem>
          <PlantTabDispatch :periodo-data="date" :mese-selezionato="date" />
        </VWindowItem>

        <VWindowItem>
          <PlantTabInventory :periodo-data="date" :mese-selezionato="date" />
        </VWindowItem>

        <VWindowItem>
          <PlantTabCost :periodo-data="date" :mese-selezionato="date" />
        </VWindowItem>

        <VWindowItem>
          <PlantTabHR :periodo-data="date" :mese-selezionato="date" />
        </VWindowItem>

        <VWindowItem>
          <PlantTabMacchine :periodo-data="date" :mese-selezionato="date" />
        </VWindowItem>
      </VWindow>
    </VCol>
  </VCol>
</template>
