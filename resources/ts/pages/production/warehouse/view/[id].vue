<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import WarehouseTabDetails from '@/views/production/warehouse/view/WarehouseTabDetails.vue'
import WarehouseTabSummary from '@/views/production/warehouse/view/WarehouseTabSummary.vue'
import {can} from "@layouts/plugins/casl";
import DefineAbilities from "@/plugins/casl/DefineAbilities";

definePage({
  meta: {
    action: 'read',
    subject: 'Produzione-Magazzino',
  },
})

const route = useRoute('production-warehouse-view-id')

const { data: resultData } = await useApi<any>(createUrl(`/pr/magazzino/head/${route.params.id}`))

const { t } = useI18n()
const materialeFilter = ref()
const classeFilter = ref()

const userTab = ref(null)

const tabs = [
  { icon: 'tabler-list-search', title: t('Label.Dettaglio') },
  { icon: 'tabler-clipboard-data', title: t('Label.Riepilogo') },
]

const classi = [
  { id: 'Packaging', titolo: 'Packaging' },
  { id: 'Raw Materials OFC', titolo: 'Raw Materials OFC' },
  { id: 'Raw Materials CC', titolo: 'Raw Materials CC' },
  { id: 'WIP OFC', titolo: 'WIP OFC' },
  { id: 'WIP CC', titolo: 'WIP CC' },
  { id: 'Finished Products OFC', titolo: 'Finished Products OFC' },
  { id: 'Finished Products CC', titolo: 'Finished Products CC' },
  { id: 'Fiber Optics OFC', titolo: 'Fiber Optics OFC' },
]
</script>

<template>
  <div class="workspace-container w-100 d-flex flex-column pa-4 gap-4">
    <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
      <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
        <div class="d-flex align-center gap-2">
          <VAvatar color="primary" variant="tonal" size="38">
            <VIcon icon="tabler-building-warehouse" size="20" />
          </VAvatar>
          <div>
            <div class="text-h6 font-weight-medium">{{ resultData?.titolo }}</div>
            <div class="text-caption text-medium-emphasis">{{ $t('Label.Magazzino-Produzione') }}</div>
          </div>
        </div>
        <div class="d-flex align-center gap-2">
          <VBtn
            color="success"
            variant="outlined"
            density="comfortable"
            class="px-3"
            target="_blank"
            :href="`https://docs.google.com/spreadsheets/d/${resultData.path_drive}`"
            prepend-icon="tabler-file-spreadsheet"
          >
            Google Sheet
          </VBtn>
        </div>
      </VCardText>
      <VDivider />

      <VCardText class="pa-3">
        <VRow class="mb-2">
          <!-- 👉 Materiale -->
          <VCol cols="12" sm="3">
            <AppTextField
              v-model="materialeFilter"
              :label="$t('Label.Materiale')"
              :placeholder="$t('Label.Materiale')"
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-search"
            />
          </VCol>

          <!-- 👉 Classe -->
          <VCol cols="12" sm="3">
            <AppSelect
              v-model="classeFilter"
              :items="classi"
              :menu-props="{ transition: 'scroll-y-transition' }"
              :label="$t('Label.Classe')"
              :placeholder="$t('Label.Classe')"
              item-title="titolo"
              item-value="id"
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-filter"
            />
          </VCol>
        </VRow>
      </VCardText>
      <VDivider />

      <!-- 👉 Tabs -->
      <VCardText class="pa-4">
        <VTabs
          v-model="userTab"
          class="v-tabs-pill mb-4"
        >
          <VTab
            v-for="tab in tabs"
            :key="tab.icon"
          >
            <VIcon
              :size="18"
              :icon="tab.icon"
              class="me-2"
            />
            <span class="font-weight-medium">{{ tab.title }}</span>
          </VTab>
        </VTabs>

        <VCard variant="outlined" class="bg-surface border-thin rounded-lg pa-4 overflow-hidden">
          <VWindow
            v-model="userTab"
            :touch="false"
          >
            <VWindowItem>
              <WarehouseTabDetails
                :materiale-filter="materialeFilter"
                :classe-filter="classeFilter"
              />
            </VWindowItem>

            <VWindowItem>
              <WarehouseTabSummary
                :materiale-filter="materialeFilter"
                :classe-filter="classeFilter"
              />
            </VWindowItem>
          </VWindow>
        </VCard>
      </VCardText>
    </VCard>
  </div>
</template>
