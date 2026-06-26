<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useTheme } from 'vuetify'
import { can } from '@layouts/plugins/casl'

const vuetifyTheme = useTheme()
const currentTheme = vuetifyTheme.current.value.colors

const router = useRouter()
const userData = useCookie<any>('userData')
const userName = computed(() => userData.value?.fullName || userData.value?.full_name || userData.value?.nome || 'Utente')
const currentTime = ref(new Date().toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' }))

setInterval(() => {
  currentTime.value = new Date().toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' })
}, 60000)

const trainingReport = ref({ expired: 0, expiring: 0 })
const competencyReport = ref({ expired: 0, expiring: 0 })
const pendingRequestsReport = ref({ count: 0, items: [] })
const plantReport = ref({})
const previousPlantReport = ref({})

const currentPeriod = computed(() => {
  const now = new Date()
  return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`
})

const currentMonthLabel = computed(() => new Date().toLocaleString('en', { month: 'short' }))

const previousPeriod = computed(() => {
  const now = new Date()
  const prev = new Date(now.getFullYear(), now.getMonth() - 1, 1)

  return `${prev.getFullYear()}-${String(prev.getMonth() + 1).padStart(2, '0')}`
})

const previousMonthLabel = computed(() => {
  const prev = new Date()
  prev.setMonth(prev.getMonth() - 1)

  return prev.toLocaleString('en', { month: 'short' })
})

const ccVariation = computed(() => {
  const current = Number(plantReport.value[currentMonthLabel.value]?.Cc_ckm) || 0
  const previous = Number(previousPlantReport.value[previousMonthLabel.value]?.Cc_ckm) || 0
  if (!previous)
    return null
  return Math.round(((current - previous) / previous) * 100)
})

const ofcVariation = computed(() => {
  const current = Number(plantReport.value[currentMonthLabel.value]?.Fkm_ofc) || 0
  const previous = Number(previousPlantReport.value[previousMonthLabel.value]?.Fkm_ofc) || 0
  if (!previous)
    return null
  return Math.round(((current - previous) / previous) * 100)
})

const ofcCkmVariation = computed(() => {
  const current = Number(plantReport.value[currentMonthLabel.value]?.Ckm_ofc) || 0
  const previous = Number(previousPlantReport.value[previousMonthLabel.value]?.Ckm_ofc) || 0
  if (!previous)
    return null
  return Math.round(((current - previous) / previous) * 100)
})

const fetchTrainingReport = async () => {
  const { data } = await useApi<any>('/hr/formazioni/obbligatori/scadenze')
  if (data.value)
    trainingReport.value = data.value
}

const fetchCompetencyReport = async () => {
  const { data } = await useApi<any>('/hr/competenze/scadenze')
  if (data.value)
    competencyReport.value = data.value
}

const fetchPendingRequestsReport = async () => {
  const { data } = await useApi<any>('/hr/requests/pending_approval_report')
  if (data.value)
    pendingRequestsReport.value = data.value
}

const fetchPlantReport = async (period: string, target: typeof plantReport) => {
  const { data } = await useApi<any>(createUrl('/production/plant/production', {
    query: { periodo: period },
  }))
  if (data.value)
    target.value = data.value
}

if (can('report', 'Hr-Dipendenti')) {
  fetchTrainingReport()
  fetchCompetencyReport()
}

if (can('list', 'Hr-Richieste'))
  fetchPendingRequestsReport()

const loadPreviousPlantReport = async () => {
  if (previousMonthLabel.value in plantReport.value)
    previousPlantReport.value = plantReport.value
  else
    await fetchPlantReport(previousPeriod.value, previousPlantReport)
}

if (can('report', 'Produzione-Performance'))
  fetchPlantReport(currentPeriod.value, plantReport)

watch(() => plantReport.value, (newVal) => {
  if (Object.keys(newVal).length)
    loadPreviousPlantReport()
})

const upcomingDeadlines = computed(() => {
  const mapItem = (item: any, type: string, status: string) => ({
    ...item,
    type,
    status,
  })

  const training = [
    ...(trainingReport.value.expired || []).map((i: any) => mapItem(i, 'Formazione', 'scaduta')),
    ...(trainingReport.value.expiring || []).map((i: any) => mapItem(i, 'Formazione', 'in scadenza')),
  ]
  const competency = [
    ...(competencyReport.value.expired || []).map((i: any) => mapItem(i, 'Competenza', 'scaduta')),
    ...(competencyReport.value.expiring || []).map((i: any) => mapItem(i, 'Competenza', 'in scadenza')),
  ]

  return [...training, ...competency]
    .sort((a, b) => new Date(a.data_scadenza).getTime() - new Date(b.data_scadenza).getTime())
    .slice(0, 5)
})

definePage({
  meta: {
    action: 'view',
    subject: 'Dashboards',
  },
})

</script>

<template>
  <!-- 👉 Benvenuto -->
  <VRow>
    <VCol cols="12">
      <VCard
        class="welcome-card overflow-hidden"
        :style="{ background: `linear-gradient(135deg, ${currentTheme.primary}, ${currentTheme.info})` }"
      >
        <VCardText class="d-flex align-center py-6 text-white">
          <VAvatar
            size="64"
            color="white"
            class="me-4 text-primary"
            icon="tabler-user-circle"
          />
          <div class="flex-grow-1">
            <div class="text-h4 font-weight-bold">
              Buongiorno, {{ userName }}
            </div>
            <div class="text-body-1 opacity-80">
              Benvenuto nella tua dashboard. Ecco un riepilogo delle attività che richiedono attenzione.
            </div>
          </div>
          <div class="d-flex align-center ms-auto text-end">
            <VIcon icon="tabler-clock" class="me-2" />
            <div class="text-h5 font-weight-bold">
              {{ currentTime }}
            </div>
          </div>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>

  <!-- 👉 Metric cards -->
  <VRow class="match-height">
    <!-- 👉 Formazioni scadute -->
    <VCol
      v-if="can('report', 'Hr-Dipendenti')"
      cols="12"
      sm="4"
      md="2"
    >
      <VCard
        class="h-100"
        @click="router.push({ name: 'hr-scadenze', query: { tab: 'formazioni' } })"
      >
        <VCardText class="d-flex align-center py-4">
          <VAvatar
            color="error"
            variant="tonal"
            size="48"
            class="me-3"
            icon="tabler-school"
          />
          <div>
            <div class="text-h5 font-weight-bold">
              {{ trainingReport.expired_count }}
            </div>
            <div class="text-caption text-medium-emphasis">
              Formazioni scadute
            </div>
          </div>
        </VCardText>
      </VCard>
    </VCol>

    <!-- 👉 Formazioni in scadenza -->
    <VCol
      v-if="can('report', 'Hr-Dipendenti')"
      cols="12"
      sm="4"
      md="2"
    >
      <VCard
        class="h-100"
        @click="router.push({ name: 'hr-scadenze', query: { tab: 'formazioni' } })"
      >
        <VCardText class="d-flex align-center py-4">
          <VAvatar
            color="warning"
            variant="tonal"
            size="48"
            class="me-3"
            icon="tabler-school"
          />
          <div>
            <div class="text-h5 font-weight-bold">
              {{ trainingReport.expiring_count }}
            </div>
            <div class="text-caption text-medium-emphasis">
              Formazioni in scadenza
            </div>
          </div>
        </VCardText>
      </VCard>
    </VCol>

    <!-- 👉 Competenze scadute -->
    <VCol
      v-if="can('report', 'Hr-Dipendenti')"
      cols="12"
      sm="4"
      md="2"
    >
      <VCard
        class="h-100"
        @click="router.push({ name: 'hr-scadenze', query: { tab: 'competenze' } })"
      >
        <VCardText class="d-flex align-center py-4">
          <VAvatar
            color="error"
            variant="tonal"
            size="48"
            class="me-3"
            icon="tabler-clipboard-check"
          />
          <div>
            <div class="text-h5 font-weight-bold">
              {{ competencyReport.expired_count }}
            </div>
            <div class="text-caption text-medium-emphasis">
              Competenze scadute
            </div>
          </div>
        </VCardText>
      </VCard>
    </VCol>

    <!-- 👉 Competenze in scadenza -->
    <VCol
      v-if="can('report', 'Hr-Dipendenti')"
      cols="12"
      sm="4"
      md="2"
    >
      <VCard
        class="h-100"
        @click="router.push({ name: 'hr-scadenze', query: { tab: 'competenze' } })"
      >
        <VCardText class="d-flex align-center py-4">
          <VAvatar
            color="info"
            variant="tonal"
            size="48"
            class="me-3"
            icon="tabler-clipboard-check"
          />
          <div>
            <div class="text-h5 font-weight-bold">
              {{ competencyReport.expiring_count }}
            </div>
            <div class="text-caption text-medium-emphasis">
              Competenze in scadenza
            </div>
          </div>
        </VCardText>
      </VCard>
    </VCol>

    <!-- 👉 Richieste in attesa -->
    <VCol
      v-if="can('list', 'Hr-Richieste')"
      cols="12"
      sm="4"
      md="2"
    >
      <VCard
        class="h-100"
        @click="router.push({ name: 'hr-richieste-list' })"
      >
        <VCardText class="d-flex align-center py-4">
          <VAvatar
            color="success"
            variant="tonal"
            size="48"
            class="me-3"
            icon="tabler-list-check"
          />
          <div>
            <div class="text-h5 font-weight-bold">
              {{ pendingRequestsReport.count }}
            </div>
            <div class="text-caption text-medium-emphasis">
              Richieste in attesa
            </div>
          </div>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>

  <!-- 👉 Produzione Plant -->
  <VRow v-if="can('report', 'Produzione-Performance')">
    <VCol cols="12">
      <VCardTitle class="ps-0 py-2 text-h6">
        Produzione Plant
      </VCardTitle>
    </VCol>

    <!-- 👉 Produzione CC -->
    <VCol
      cols="12"
      sm="6"
      md="3"
    >
      <VCard class="h-100">
        <VCardText class="d-flex align-center py-4">
          <VAvatar
            color="primary"
            variant="tonal"
            size="48"
            class="me-3"
            icon="tabler-building-factory"
          />
          <div>
            <div class="text-h5 font-weight-bold">
              {{ plantReport[currentMonthLabel]?.Cc_ckm || 0 }}
            </div>
            <div class="text-caption text-medium-emphasis">
              CKM Rame ({{ currentMonthLabel }})
            </div>
            <div class="d-flex align-center mt-1">
              <VIcon
                v-if="ccVariation !== null"
                :icon="ccVariation >= 0 ? 'tabler-arrow-up' : 'tabler-arrow-down'"
                :color="ccVariation >= 0 ? 'success' : 'error'"
                size="small"
                class="me-1"
              />
              <div class="text-caption">
                <span v-if="ccVariation !== null" :class="ccVariation >= 0 ? 'text-success' : 'text-error'">
                  {{ ccVariation >= 0 ? '+' : '' }}{{ ccVariation }}%
                </span>
                <span v-else class="text-medium-emphasis">n/d</span>
                <span class="text-medium-emphasis"> vs {{ previousMonthLabel }}</span>
              </div>
            </div>
          </div>
        </VCardText>
      </VCard>
    </VCol>

    <!-- 👉 Produzione OFC -->
    <VCol
      cols="12"
      sm="6"
      md="4"
    >
      <VCard class="h-100">
        <VCardText class="d-flex align-center py-4">
          <VAvatar
            color="info"
            variant="tonal"
            size="48"
            class="me-3"
            icon="tabler-building-factory-2"
          />
          <div class="flex-grow-1">
            <div class="text-caption text-medium-emphasis mb-2">
              Produzione Ottico ({{ currentMonthLabel }})
            </div>
            <VRow no-gutters>
              <VCol cols="6">
                <div class="text-h5 font-weight-bold">
                  {{ plantReport[currentMonthLabel]?.Fkm_ofc || 0 }}
                </div>
                <div class="text-caption text-medium-emphasis">
                  KfKM
                </div>
                <div class="d-flex align-center mt-1">
                  <VIcon
                    v-if="ofcVariation !== null"
                    :icon="ofcVariation >= 0 ? 'tabler-arrow-up' : 'tabler-arrow-down'"
                    :color="ofcVariation >= 0 ? 'success' : 'error'"
                    size="small"
                    class="me-1"
                  />
                  <div class="text-caption">
                    <span v-if="ofcVariation !== null" :class="ofcVariation >= 0 ? 'text-success' : 'text-error'">
                      {{ ofcVariation >= 0 ? '+' : '' }}{{ ofcVariation }}%
                    </span>
                    <span v-else class="text-medium-emphasis">n/d</span>
                    <span class="text-medium-emphasis"> vs {{ previousMonthLabel }}</span>
                  </div>
                </div>
              </VCol>
              <VCol cols="6">
                <div class="text-h5 font-weight-bold">
                  {{ plantReport[currentMonthLabel]?.Ckm_ofc || 0 }}
                </div>
                <div class="text-caption text-medium-emphasis">
                  CKM
                </div>
                <div class="d-flex align-center mt-1">
                  <VIcon
                    v-if="ofcCkmVariation !== null"
                    :icon="ofcCkmVariation >= 0 ? 'tabler-arrow-up' : 'tabler-arrow-down'"
                    :color="ofcCkmVariation >= 0 ? 'success' : 'error'"
                    size="small"
                    class="me-1"
                  />
                  <div class="text-caption">
                    <span v-if="ofcCkmVariation !== null" :class="ofcCkmVariation >= 0 ? 'text-success' : 'text-error'">
                      {{ ofcCkmVariation >= 0 ? '+' : '' }}{{ ofcCkmVariation }}%
                    </span>
                    <span v-else class="text-medium-emphasis">n/d</span>
                    <span class="text-medium-emphasis"> vs {{ previousMonthLabel }}</span>
                  </div>
                </div>
              </VCol>
            </VRow>
          </div>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>

  <!-- 👉 Report lists -->
  <VRow class="match-height">
    <!-- 👉 Scadenze -->
    <VCol
      v-if="can('report', 'Hr-Dipendenti')"
      cols="12"
      md="6"
    >
      <VCard class="h-100">
        <VCardItem>
          <template #prepend>
            <VAvatar
              color="warning"
              variant="tonal"
              size="38"
              class="me-2"
              icon="tabler-alert-triangle"
            />
          </template>
          <VCardTitle>Scadenze</VCardTitle>
        </VCardItem>
        <VDivider />
        <VCardText class="pt-2">
          <div
            v-for="(item, index) in upcomingDeadlines"
            :key="index"
            class="d-flex align-center py-3 cursor-pointer"
            :class="{ 'border-b': index !== upcomingDeadlines.length - 1 }"
            @click="router.push({ name: 'hr-scadenze', query: { tab: item.type === 'Formazione' ? 'formazioni' : 'competenze' } })"
          >
            <VAvatar
              :color="item.status === 'scaduta' ? 'error' : 'warning'"
              variant="tonal"
              size="36"
              class="me-3"
              :icon="item.type === 'Formazione' ? 'tabler-school' : 'tabler-clipboard-check'"
            />
            <div class="flex-grow-1">
              <div class="font-weight-medium">
                {{ item.nome_completo }}
              </div>
              <div class="text-caption text-medium-emphasis">
                {{ item.type }} - {{ item.type === 'Formazione' ? item.formazione : item.attivita }}
              </div>
            </div>
            <div class="text-end">
              <VChip
                :color="item.status === 'scaduta' ? 'error' : 'warning'"
                label
                size="small"
                variant="tonal"
                class="text-capitalize"
              >
                {{ item.status }}
              </VChip>
              <div class="text-caption text-medium-emphasis mt-1">
                {{ item.data_scadenza }}
              </div>
            </div>
          </div>
          <div
            v-if="!upcomingDeadlines.length"
            class="text-center text-medium-emphasis py-4"
          >
            Nessuna scadenza imminente
          </div>
        </VCardText>
      </VCard>
    </VCol>

    <!-- 👉 Richieste in attesa -->
    <VCol
      v-if="can('list', 'Hr-Richieste')"
      cols="12"
      md="6"
    >
      <VCard class="h-100">
        <VCardItem>
          <template #prepend>
            <VAvatar
              color="success"
              variant="tonal"
              size="38"
              class="me-2"
              icon="tabler-list-check"
            />
          </template>
          <VCardTitle>Richieste in attesa</VCardTitle>
        </VCardItem>
        <VDivider />
        <VCardText class="pt-2">
          <div
            v-for="(item, index) in pendingRequestsReport.items"
            :key="index"
            class="d-flex align-center py-3 cursor-pointer"
            :class="{ 'border-b': index !== pendingRequestsReport.items.length - 1 }"
            @click="router.push({ name: 'hr-richieste-view-id', params: { id: item.id } })"
          >
            <VAvatar
              color="success"
              variant="tonal"
              size="36"
              class="me-3"
              icon="tabler-user"
            />
            <div class="flex-grow-1">
              <div class="font-weight-medium">
                {{ item.dipendente_cognome }} {{ item.dipendente_nome }}
              </div>
              <div class="text-caption text-medium-emphasis">
                Tipologia {{ item.tipologia }}
              </div>
            </div>
            <div class="text-end">
              <VChip
                color="success"
                label
                size="small"
                variant="tonal"
              >
                Da approvare
              </VChip>
              <div class="text-caption text-medium-emphasis mt-1">
                {{ item.data_richiesta }}
              </div>
            </div>
          </div>
          <div
            v-if="!pendingRequestsReport.items.length"
            class="text-center text-medium-emphasis py-4"
          >
            Nessuna richiesta in attesa
          </div>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

<style lang="scss">
@use "@core-scss/template/libs/apex-chart.scss";
</style>
