<script setup lang="ts">
import { VDataTable } from 'vuetify/labs/VDataTable'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'

const { t } = useI18n()
const route = useRoute()

const loadingTraining = ref(true)
const trainingExpired = ref<any[]>([])
const trainingExpiring = ref<any[]>([])
const trainingExpiredCount = ref(0)
const trainingExpiringCount = ref(0)

const loadingCompetency = ref(true)
const competencyExpired = ref<any[]>([])
const competencyExpiring = ref<any[]>([])
const competencyExpiredCount = ref(0)
const competencyExpiringCount = ref(0)

const fetchTraining = async () => {
  const { data } = await useApi<any>('/hr/formazioni/obbligatori/scadenze')
  if (data.value) {
    trainingExpired.value = data.value.expired
    trainingExpiring.value = data.value.expiring
    trainingExpiredCount.value = data.value.expired_count
    trainingExpiringCount.value = data.value.expiring_count
  }
  loadingTraining.value = false
}

const fetchCompetency = async () => {
  const { data } = await useApi<any>('/hr/competenze/scadenze')
  if (data.value) {
    competencyExpired.value = data.value.expired
    competencyExpiring.value = data.value.expiring
    competencyExpiredCount.value = data.value.expired_count
    competencyExpiringCount.value = data.value.expiring_count
  }
  loadingCompetency.value = false
}

fetchTraining()
fetchCompetency()

const activeTab = ref(route.query.tab === 'competenze' ? 1 : 0)
watch(() => route.query.tab, (val) => {
  activeTab.value = val === 'competenze' ? 1 : 0
})
const tabs = [
  { icon: 'tabler-school', title: 'Formazioni obbligatorie' },
  { icon: 'tabler-clipboard-check', title: 'Competenze' },
]

const trainingHeaders = [
  { title: 'Dipendente', key: 'nome_completo' },
  { title: 'Formazione', key: 'formazione' },
  { title: 'Scadenza', key: 'data_scadenza' },
  { title: 'Stato', key: 'stato', sortable: false },
  { title: 'Giorni', key: 'days_left', sortable: false },
]

const competencyHeaders = [
  { title: 'Dipendente', key: 'nome_completo' },
  { title: 'Attività', key: 'attivita' },
  { title: 'Valutazione', key: 'valutazione' },
  { title: 'Scadenza', key: 'data_scadenza' },
  { title: 'Stato', key: 'stato', sortable: false },
  { title: 'Giorni', key: 'days_left', sortable: false },
]

definePage({
  meta: {
    action: 'report',
    subject: 'Hr-Dipendenti',
  },
})
</script>

<template>
  <div class="workspace-container w-100 d-flex flex-column pa-4 gap-4">
    <!-- Header -->
    <VCard variant="outlined" class="bg-surface border-thin rounded-lg overflow-hidden">
      <VCardText class="d-flex flex-column flex-sm-row align-center gap-4 pa-6">
        <VAvatar color="warning" variant="tonal" size="56">
          <VIcon icon="tabler-alert-triangle" size="28" />
        </VAvatar>
        <div class="flex-grow-1 text-center text-sm-left">
          <h2 class="text-h5 font-weight-semibold text-high-emphasis">Scadenze</h2>
          <p class="text-caption text-medium-emphasis mb-0">Formazioni obbligatorie e competenze in scadenza o scadute</p>
        </div>
        <div class="d-flex gap-4">
          <div class="text-center">
            <div class="text-error text-h5 font-weight-bold">{{ trainingExpiredCount + competencyExpiredCount }}</div>
            <div class="text-caption text-medium-emphasis">Scadute</div>
          </div>
          <div class="text-center">
            <div class="text-warning text-h5 font-weight-bold">{{ trainingExpiringCount + competencyExpiringCount }}</div>
            <div class="text-caption text-medium-emphasis">&lt; 3 mesi</div>
          </div>
        </div>
      </VCardText>
    </VCard>

    <!-- Tabs -->
    <VTabs v-model="activeTab" class="v-tabs-pill">
      <VTab v-for="tab in tabs" :key="tab.icon">
        <VIcon :size="18" :icon="tab.icon" class="me-2" />
        <span class="font-weight-medium">{{ tab.title }}</span>
      </VTab>
    </VTabs>

    <VCard variant="outlined" class="bg-surface border-thin rounded-lg pa-4">
      <VWindow v-model="activeTab" class="disable-tab-transition" :touch="false">
        <VWindowItem>
          <VDataTable
            :headers="trainingHeaders"
            :items="[...trainingExpired, ...trainingExpiring]"
            :loading="loadingTraining"
            item-value="id"
            class="text-no-wrap"
          >
            <template #item.stato="{ item }">
              <VChip
                label
                size="small"
                :color="item.days_left < 0 ? 'error' : 'warning'"
                variant="tonal"
              >
                {{ item.days_left < 0 ? 'Scaduta' : 'In scadenza' }}
              </VChip>
            </template>
            <template #item.days_left="{ item }">
              <span>{{ item.days_left < 0 ? `${Math.abs(item.days_left)} gg fa` : `${item.days_left} gg` }}</span>
            </template>
          </VDataTable>
        </VWindowItem>

        <VWindowItem>
          <VDataTable
            :headers="competencyHeaders"
            :items="[...competencyExpired, ...competencyExpiring]"
            :loading="loadingCompetency"
            item-value="id"
            class="text-no-wrap"
          >
            <template #item.stato="{ item }">
              <VChip
                label
                size="small"
                :color="item.days_left < 0 ? 'error' : 'warning'"
                variant="tonal"
              >
                {{ item.days_left < 0 ? 'Scaduta' : 'In scadenza' }}
              </VChip>
            </template>
            <template #item.days_left="{ item }">
              <span>{{ item.days_left < 0 ? `${Math.abs(item.days_left)} gg fa` : `${item.days_left} gg` }}</span>
            </template>
          </VDataTable>
        </VWindowItem>
      </VWindow>
    </VCard>
  </div>
</template>
