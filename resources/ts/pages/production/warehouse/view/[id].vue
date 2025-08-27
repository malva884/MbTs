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
  <VCol cols="12">
    <VCard
      title="Filters"
      class="mb-6"
    >
      <VCardText>
        <VRow>
          <!-- 👉 Materiale -->
          <VCol
            cols="12"
            sm="3"
          >
            <AppTextField
              v-model="materialeFilter"
              :label="$t('Label.Materiale')"
              :placeholder="$t('Label.Materiale')"
              clearable
              clear-icon="tabler-x"
            />
          </VCol>

          <!-- 👉 Classe -->
          <VCol
            cols="12"
            sm="3"
          >
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
        <VBtn
          color="success"
          target="_blank"
          :href="`https://docs.google.com/spreadsheets/d/${resultData.path_drive}`"
        >
          <VIcon
            start
            icon="tabler-file-spreadsheet"
          />Google Sheet
        </VBtn>
      </VTabs>

      <VWindow
        v-model="userTab"
        class="mt-6 disable-tab-transition"
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
    </VCol>
  </VCol>
</template>
