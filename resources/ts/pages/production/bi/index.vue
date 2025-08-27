<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import BiTabSummary from '@/views/production/bi/BiTabSummary.vue'

definePage({
  meta: {
    action: 'report',
    subject: 'Produzione-Business-Intelligence',
  },
})

const { t } = useI18n()
const date = new Date()
const day = date.getDate()
const month = date.getMonth() + 1
const year = date.getFullYear()
const dataFilter = ref(`${year}-${month}-${day}`)
const groupFilter = ref()
const tipologiaFilter = ref()
const macchineFilter = ref()
const materialeFilter = ref()
const macchineOptions = []

const userTab = ref(null)

const tabs = [
  { icon: 'tabler-list-search', title: t('Label.Report') },
  //{ icon: 'tabler-clipboard-data', title: t('Label.Riepilogo') },
]

const group = [
  { id: 'NumeroFibre', titolo: 'Numero Fibre / Kg' },
  { id: 'Macchina', titolo: 'Macchine' },
  { id: 'clienti', titolo: 'Clienti' },
  { id: 'Periodo', titolo: 'Data' },
]

const tipologia = [
  { id: 'bu', titolo: 'Buffering' },
  { id: 'sz', titolo: 'Stranding' },
  { id: 'sf', titolo: 'Jacketing' },
  { id: 'mk', titolo: 'Marck' },
  { id: 'f', titolo: 'Prodotto Finito' },
  { id: 's', titolo: 'Semi lavorato' },
]

const macchine = useApi<any>(createUrl('/macchine/get_list', {
  query: {
    attivo: true,
  },
}))
</script>

<template>
  <VCol cols="12">
    <VCard
      title="Filters"
      class="mb-3"
    >
      <VCardText>
        <VRow>
          <!-- 👉 Group -->
          <VCol
            cols="12"
            sm="2"
          >
            <AppSelect
              v-model="groupFilter"
              :items="group"
              :label="$t('Label.Ragruppa')"
              :placeholder="$t('Label.Ragruppa')"
              item-title="titolo"
              item-value="id"
              clearable
              clear-icon="tabler-x"
              persistent-hint
            />
          </VCol>
          <!-- 👉 Tipologia -->
          <VCol
            cols="12"
            sm="2"
          >
            <AppSelect
              v-model="tipologiaFilter"
              :items="tipologia"
              :label="$t('Label.Lavorazione')"
              :placeholder="$t('Label.Lavorazione')"
              item-title="titolo"
              item-value="id"
              clearable
              clear-icon="tabler-x"
              persistent-hint
            />
          </VCol>
          <!-- 👉 Materiale -->
          <VCol
            cols="12"
            sm="2"
          >
            <AppTextField
              v-model="materialeFilter"
              :label="$t('Label.Materiale')"
              :placeholder="$t('Label.Materiale')"
              clearable
              clear-icon="tabler-x"
            />
          </VCol>
          <!-- 👉 Macchine -->
          <VCol
            cols="12"
            sm="2"
          >
            <AppSelect
              v-model="macchineFilter"
              :items="macchineOptions"
              :menu-props="{ transition: 'scroll-y-transition', maxHeight: '400' }"
              :label="$t('Label.Macchina')"
              :placeholder="$t('Label.Macchina')"
              item-title="nome"
              item-value="id"
              clearable
              clear-icon="tabler-x"
              multiple
              persistent-hint
            />
          </VCol>
          <!-- 👉 Data -->
          <VCol
            cols="12"
            sm="2"
          >
            <AppDateTimePicker
              v-model="dataFilter"
              :label="$t('Label.Data')"
              :placeholder="$t('Label.Data')"
              :config="{ mode: 'range' }"
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
      </VTabs>

      <VWindow
        v-model="userTab"
        class="mt-6 disable-tab-transition"
        :touch="false"
      >
        <VWindowItem>
          <VRow>
            <VCol
              cols="12"
              sm="6"
            >
              <BiTabSummary
                titolo="Report-Ottico"
                tipologia="20"
                :data-filter="dataFilter"
                :group-filter="groupFilter"
                :macchine-filter="macchineFilter"
                :lavorazione-filter="tipologiaFilter"
                :materiale-filter="materialeFilter"
              />
            </VCol>
            <VCol
              cols="12"
              sm="6"
            >
              <BiTabSummary
                titolo="Report-Rame"
                tipologia="41"
                :data-filter="dataFilter"
                :group-filter="groupFilter"
                :macchine-filter="macchineFilter"
                :lavorazione-filter="tipologiaFilter"
                :materiale-filter="materialeFilter"
              />
            </VCol>
          </VRow>
        </VWindowItem>

        <VWindowItem>

        </VWindowItem>
      </VWindow>
    </VCol>
  </VCol>
</template>
