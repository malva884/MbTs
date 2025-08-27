<script setup lang="ts">
import AssetTabInfo from '@/views/plant/asset/view/AssetTabInfo.vue'
import AssetTabInterventions from '@/views/plant/asset/view/AssetTabInterventions.vue'
import AssetTabMonitoring from '@/views/plant/asset/view/AssetTabMonitoring.vue'
import AssetTabDevice from '@/views/plant/asset/view/AssetTabDevice.vue'

definePage({
  meta: {
    action: 'list',
    subject: 'Plant-Asset',
  },
})

const assetData = ref({})
const route = useRoute('plant-asset-view-id')
const assetTab = ref(null)

const tabs = [
  { icon: 'tabler-info-circle', title: 'Dettaglio' },
  { icon: 'tabler-hand-stop', title: 'Interventi' },
  { icon: 'tabler-heart-rate-monitor', title: 'Monitoriaggio' },
  { icon: 'tabler-mouse', title: 'Device' },
]
</script>

<template>
  <VRow v-if="assetData">
    <VCol
      cols="12"
      md="12"
      lg="12"
    >
      <VTabs
        v-model="assetTab"
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
        v-model="assetTab"
        class="mt-6 disable-tab-transition"
        :touch="false"
      >

        <VWindowItem>
          <AssetTabInfo :id="route.params.id" />
        </VWindowItem>
        <VWindowItem>
          <AssetTabInterventions :id="route.params.id" />
        </VWindowItem>
        <VWindowItem>
          <AssetTabMonitoring :id="route.params.id"/>
        </VWindowItem>
        <VWindowItem>
          <AssetTabDevice :id="route.params.id"/>
        </VWindowItem>
      </VWindow>
    </VCol>
  </VRow>
  <VCard v-else>
    <VCardTitle class="text-center">
      No User Found
    </VCardTitle>
  </VCard>
</template>
