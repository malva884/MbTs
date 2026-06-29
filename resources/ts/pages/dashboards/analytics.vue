<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useTheme } from 'vuetify'
import { can } from '@layouts/plugins/casl'

const { t, locale } = useI18n()
const vuetifyTheme = useTheme()
const currentTheme = vuetifyTheme.current.value.colors

const router = useRouter()
const userData = useCookie<any>('userData')
const userName = computed(() => userData.value?.fullName || userData.value?.full_name || userData.value?.nome || t('Label.Utente'))
const currentTime = ref(new Date().toLocaleTimeString(locale.value, { hour: '2-digit', minute: '2-digit' }))

setInterval(() => {
  currentTime.value = new Date().toLocaleTimeString(locale.value, { hour: '2-digit', minute: '2-digit' })
}, 60000)

const trainingReport = ref({ expired: 0, expiring: 0 })
const competencyReport = ref({ expired: 0, expiring: 0 })
const pendingRequestsReport = ref({ count: 0, items: [] })
const pendingWorkflowOrders = ref({ count: 0, items: [], is_approver: false })
const myItAssets = ref({ count: 0, items: [] })
const plantReport = ref({})
const previousPlantReport = ref({})
const taskStats = ref({
  isResponsabile: false,
  area: {
    taskTotali: 0,
    taskAperti: 0,
    taskChiusi: 0,
    taskScaduti: 0,
    taskSospesi: 0,
    taskLavorazione: 0,
  },
  assigned: {
    taskTotali: 0,
    taskAperti: 0,
    taskChiusi: 0,
    taskScaduti: 0,
  },
})
const visitorsPresent = ref({ count: 0, items: [] })
const recentActivities = ref([])

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

const ccDifference = computed(() => {
  const current = Number(plantReport.value[currentMonthLabel.value]?.Cc_ckm) || 0
  const previous = Number(previousPlantReport.value[previousMonthLabel.value]?.Cc_ckm) || 0
  if (!previous)
    return null
  return current - previous
})

const ofcDifference = computed(() => {
  const current = Number(plantReport.value[currentMonthLabel.value]?.Fkm_ofc) || 0
  const previous = Number(previousPlantReport.value[previousMonthLabel.value]?.Fkm_ofc) || 0
  if (!previous)
    return null
  return current - previous
})

const ofcCkmDifference = computed(() => {
  const current = Number(plantReport.value[currentMonthLabel.value]?.Ckm_ofc) || 0
  const previous = Number(previousPlantReport.value[previousMonthLabel.value]?.Ckm_ofc) || 0
  if (!previous)
    return null
  return current - previous
})

const activeTaskStats = computed(() => taskStats.value.isResponsabile ? taskStats.value.area : taskStats.value.assigned)

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

const fetchPendingWorkflowOrders = async () => {
  const { data } = await useApi<any>('/workflow/commesse/pending_report')
  if (data.value)
    pendingWorkflowOrders.value = data.value
}

const fetchMyItAssets = async () => {
  const { data } = await useApi<any>('/it/assets/my_assets')
  if (data.value)
    myItAssets.value = data.value
}

const fetchPlantReport = async (period: string, target: typeof plantReport) => {
  const { data } = await useApi<any>(createUrl('/production/plant/production', {
    query: { periodo: period },
  }))
  if (data.value)
    target.value = data.value
}

const fetchTaskStats = async () => {
  const { data } = await useApi<any>('/task/statistiche')
  if (data.value)
    taskStats.value = { ...taskStats.value, ...data.value }
}

const fetchVisitorsPresent = async () => {
  const { data } = await useApi<any>('/reception/register/activity/visitorsPresent')
  if (data.value)
    visitorsPresent.value = data.value
}

const fetchRecentActivities = async () => {
  const { data } = await useApi<any>(createUrl('/reception/register/activity/recentActivities', {
    query: { limit: 10 },
  }))
  if (data.value)
    recentActivities.value = data.value
}

if (can('report', 'Hr-Dipendenti')) {
  fetchTrainingReport()
  fetchCompetencyReport()
}

if (can('list', 'Hr-Richieste'))
  fetchPendingRequestsReport()

if (can('list', 'Wf-Commesse'))
  fetchPendingWorkflowOrders()

if (can('view', 'IT-Assets'))
  fetchMyItAssets()

if (can('list', 'Reception-Register')) {
  fetchVisitorsPresent()
  fetchRecentActivities()

  setInterval(() => {
    fetchVisitorsPresent()
    fetchRecentActivities()
  }, 300000)
}

const loadPreviousPlantReport = async () => {
  if (previousMonthLabel.value in plantReport.value)
    previousPlantReport.value = plantReport.value
  else
    await fetchPlantReport(previousPeriod.value, previousPlantReport)
}

if (can('report', 'Produzione-Performance'))
  fetchPlantReport(currentPeriod.value, plantReport)

fetchTaskStats()

watch(() => plantReport.value, (newVal) => {
  if (Object.keys(newVal).length)
    loadPreviousPlantReport()
})

const upcomingDeadlines = computed(() => {
  const mapItem = (item: any, type: string, typeKey: string, status: string, statusKey: string) => ({
    ...item,
    type,
    typeKey,
    status,
    statusKey,
  })

  const training = [
    ...(trainingReport.value.expired || []).map((i: any) => mapItem(i, t('Dashboard.HR.TrainingType'), 'Formazione', t('Dashboard.HR.ExpiredStatus'), 'scaduta')),
    ...(trainingReport.value.expiring || []).map((i: any) => mapItem(i, t('Dashboard.HR.TrainingType'), 'Formazione', t('Dashboard.HR.ExpiringStatus'), 'in scadenza')),
  ]
  const competency = [
    ...(competencyReport.value.expired || []).map((i: any) => mapItem(i, t('Dashboard.HR.CompetencyType'), 'Competenza', t('Dashboard.HR.ExpiredStatus'), 'scaduta')),
    ...(competencyReport.value.expiring || []).map((i: any) => mapItem(i, t('Dashboard.HR.CompetencyType'), 'Competenza', t('Dashboard.HR.ExpiringStatus'), 'in scadenza')),
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
  <VRow class="match-height">
    <VCol cols="12" md="6">
      <VCard
        class="welcome-card h-100 overflow-hidden d-flex align-center"
        :style="{ background: `linear-gradient(135deg, ${currentTheme.primary}, #6f42c1)` }"
        flat
      >
        <VCardText class="d-flex align-center py-6 text-white w-100">
          <VAvatar size="64" color="rgba(255,255,255,0.2)" class="me-4 text-white">
            <VIcon icon="tabler-user-circle" size="40" />
          </VAvatar>
          <div class="flex-grow-1">
            <div class="text-h4 font-weight-bold">{{ t('Dashboard.Welcome.Title', { name: userName }) }}</div>
            <div class="text-body-1 opacity-80">{{ t('Dashboard.Welcome.Subtitle') }}</div>
          </div>
          <div class="d-flex align-center ms-auto text-end">
            <VIcon icon="tabler-clock" class="me-2" />
            <div class="text-h5 font-weight-bold">{{ currentTime }}</div>
          </div>
        </VCardText>
      </VCard>
    </VCol>

    <VCol cols="12" sm="6" md="6">
      <VCard class="h-100 py-4" flat border>
        <VCardText>
          <div class="d-flex align-center h-100">
            <div class="flex-grow-1 text-center">
              <div class="text-body-2 text-uppercase font-weight-medium text-medium-emphasis mb-2">
                {{ taskStats.isResponsabile ? t('Dashboard.Task.CompletionArea') : t('Dashboard.Task.Completion') }}
              </div>
              <VProgressCircular
                :model-value="activeTaskStats.taskTotali ? Math.round((activeTaskStats.taskChiusi / activeTaskStats.taskTotali) * 100) : 0"
                size="90"
                width="10"
                color="primary"
              >
                <span class="text-h6 font-weight-bold">
                  {{ activeTaskStats.taskTotali ? Math.round((activeTaskStats.taskChiusi / activeTaskStats.taskTotali) * 100) : 0 }}%
                </span>
              </VProgressCircular>
              <div class="text-caption mt-2 text-medium-emphasis">
                {{ t('Dashboard.Task.ClosedOfTotal', { closed: activeTaskStats.taskChiusi, total: activeTaskStats.taskTotali }) }}
              </div>
            </div>

            <VDivider vertical class="mx-4 align-self-stretch" />

            <div class="flex-grow-1 text-center">
              <div class="text-body-2 text-uppercase font-weight-medium text-medium-emphasis mb-2">
                {{ taskStats.isResponsabile ? t('Dashboard.Task.ExpiredUrgentArea') : t('Dashboard.Task.ExpiredUrgent') }}
              </div>
              <div class="text-h3 font-weight-bold text-error">{{ activeTaskStats.taskScaduti }}</div>

              <template v-if="taskStats.isResponsabile">
                <VDivider class="my-3" />
                <div class="text-caption text-medium-emphasis mb-2">{{ t('Dashboard.Task.YourAssigned') }}</div>
                <div class="d-flex justify-center flex-wrap gap-1">
                  <VChip size="small" color="secondary" variant="flat">{{ t('Dashboard.Task.Open') }}: {{ taskStats.assigned.taskAperti }}</VChip>
                  <VChip size="small" color="success" variant="flat">{{ t('Dashboard.Task.Closed') }}: {{ taskStats.assigned.taskChiusi }}</VChip>
                  <VChip size="small" color="error" variant="flat" v-if="taskStats.assigned.taskScaduti">{{ t('Dashboard.Task.Expired') }}: {{ taskStats.assigned.taskScaduti }}</VChip>
                </div>
              </template>
            </div>
          </div>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>

  <VRow class="match-height mt-3">
    <VCol v-if="can('report', 'Hr-Dipendenti')" cols="12" sm="6" md="3">
      <VCard class="h-100" flat border @click="router.push({ name: 'hr-scadenze', query: { tab: 'formazioni' } })">
        <VCardItem class="pb-2">
          <template #prepend>
            <VAvatar color="error" variant="tonal" size="42" class="me-2">
              <VIcon icon="tabler-school" />
            </VAvatar>
          </template>
          <VCardTitle class="text-body-1 font-weight-bold">{{ t('Dashboard.HR.Training') }}</VCardTitle>
        </VCardItem>
        <VCardText>
          <div class="d-flex flex-column gap-2 mt-2">
            <div class="d-flex justify-between align-center">
              <span class="text-body-2 text-medium-emphasis">{{ t('Dashboard.HR.Expired') }}</span>
              <VChip color="error" size="small" label class="font-weight-bold">
                {{ trainingReport.expired?.length || 0 }}
              </VChip>
            </div>
            <div class="d-flex justify-between align-center">
              <span class="text-body-2 text-medium-emphasis">{{ t('Dashboard.HR.Expiring') }}</span>
              <VChip color="warning" size="small" label class="font-weight-bold">
                {{ trainingReport.expiring?.length || 0 }}
              </VChip>
            </div>
          </div>
        </VCardText>
      </VCard>
    </VCol>

    <VCol v-if="can('report', 'Hr-Dipendenti')" cols="12" sm="6" md="3">
      <VCard class="h-100" flat border @click="router.push({ name: 'hr-scadenze', query: { tab: 'competenze' } })">
        <VCardItem class="pb-2">
          <template #prepend>
            <VAvatar color="info" variant="tonal" size="42" class="me-2">
              <VIcon icon="tabler-clipboard-check" />
            </VAvatar>
          </template>
          <VCardTitle class="text-body-1 font-weight-bold">{{ t('Dashboard.HR.Competencies') }}</VCardTitle>
        </VCardItem>
        <VCardText>
          <div class="d-flex flex-column gap-2 mt-2">
            <div class="d-flex justify-between align-center">
              <span class="text-body-2 text-medium-emphasis">{{ t('Dashboard.HR.Expired') }}</span>
              <VChip color="error" size="small" label class="font-weight-bold">
                {{ competencyReport.expired?.length || 0 }}
              </VChip>
            </div>
            <div class="d-flex justify-between align-center">
              <span class="text-body-2 text-medium-emphasis">{{ t('Dashboard.HR.Expiring') }}</span>
              <VChip color="info" size="small" label class="font-weight-bold">
                {{ competencyReport.expiring?.length || 0 }}
              </VChip>
            </div>
          </div>
        </VCardText>
      </VCard>
    </VCol>

    <VCol v-if="can('list', 'Hr-Richieste')" cols="12" sm="6" md="3">
      <VCard class="h-100 d-flex flex-column justify-space-between" flat border @click="router.push({ name: 'hr-richieste-list' })">
        <VCardItem>
          <template #prepend>
            <VAvatar color="success" variant="tonal" size="42" class="me-2">
              <VIcon icon="tabler-list-check" />
            </VAvatar>
          </template>
          <VCardTitle class="text-body-1 font-weight-bold">{{ t('Dashboard.HR.Requests') }}</VCardTitle>
        </VCardItem>
        <VCardText class="pt-0">
          <div class="text-h3 font-weight-bold text-success mb-1">
            {{ pendingRequestsReport.count }}
          </div>
          <div class="text-body-2 text-medium-emphasis">{{ t('Dashboard.HR.PendingApproval') }}</div>
        </VCardText>
      </VCard>
    </VCol>

    <VCol v-if="can('list', 'Wf-Commesse') && pendingWorkflowOrders.is_approver" cols="12" sm="6" md="3">
      <VCard class="h-100 d-flex flex-column justify-space-between" flat border @click="router.push({ name: 'workflow-commesse-list' })">
        <VCardItem>
          <template #prepend>
            <VAvatar color="warning" variant="tonal" size="42" class="me-2">
              <VIcon icon="tabler-file-invoice" />
            </VAvatar>
          </template>
          <VCardTitle class="text-body-1 font-weight-bold">{{ t('Dashboard.Workflow.Orders') }}</VCardTitle>
        </VCardItem>
        <VCardText class="pt-0">
          <div class="text-h3 font-weight-bold text-warning mb-1">
            {{ pendingWorkflowOrders.count }}
          </div>
          <div class="text-body-2 text-medium-emphasis">
            {{ pendingWorkflowOrders.is_approver ? t('Dashboard.Workflow.Sign') : t('Dashboard.Workflow.Approve') }}
          </div>
        </VCardText>
      </VCard>
    </VCol>

    <VCol v-if="can('view', 'IT-Assets')" cols="12" sm="6" md="3">
      <VCard class="h-100 d-flex flex-column justify-space-between" flat border @click="router.push({ name: 'it-assets-list' })">
        <VCardItem>
          <template #prepend>
            <VAvatar color="info" variant="tonal" size="42" class="me-2">
              <VIcon icon="tabler-device-laptop" />
            </VAvatar>
          </template>
          <VCardTitle class="text-body-1 font-weight-bold">{{ t('Dashboard.IT.Assets') }}</VCardTitle>
        </VCardItem>
        <VCardText class="pt-0">
          <div class="text-h3 font-weight-bold text-info mb-1">
            {{ myItAssets.count }}
          </div>
          <div class="text-body-2 text-medium-emphasis">{{ t('Dashboard.IT.AssignedAssets') }}</div>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>

  <VRow v-if="can('report', 'Produzione-Performance')" class="mt-3">
    <VCol cols="12">
      <VCard flat border>
        <VCardItem>
          <template #prepend>
            <VAvatar color="primary" variant="tonal" size="40">
              <VIcon icon="tabler-building-factory" />
            </VAvatar>
          </template>
          <VCardTitle class="font-weight-bold">{{ t('Dashboard.Production.Title') }}</VCardTitle>
        </VCardItem>
        <VDivider />
        <VCardText>
          <VRow>
            <VCol cols="12" md="6" class="border-e-md">
              <div class="d-flex align-center justify-space-between mb-2">
                <div>
                  <div class="text-h4 font-weight-bold">{{ plantReport[currentMonthLabel]?.Cc_ckm || 0 }}</div>
                  <div class="text-caption text-medium-emphasis">{{ t('Dashboard.Production.CopperCkm') }} ({{ currentMonthLabel }})</div>
                  <div class="text-caption text-medium-emphasis">
                    {{ t('Dashboard.Production.VsMonth', { month: previousMonthLabel }) }}: {{ previousPlantReport[previousMonthLabel]?.Cc_ckm || 0 }}
                  </div>
                </div>
                <div class="text-end">
                  <div v-if="ccDifference !== null" class="d-flex align-center justify-end">
                    <VIcon :icon="ccDifference >= 0 ? 'tabler-arrow-up' : 'tabler-arrow-down'" :color="ccDifference >= 0 ? 'success' : 'error'" size="18" class="me-1" />
                    <span :class="ccDifference >= 0 ? 'text-success font-weight-bold' : 'text-error font-weight-bold'">{{ ccDifference >= 0 ? '+' : '' }}{{ ccDifference }}</span>
                  </div>
                  <div class="text-caption text-medium-emphasis">{{ t('Dashboard.Production.DiffVsMonth', { month: previousMonthLabel }) }}</div>
                </div>
              </div>
            </VCol>

            <VCol cols="12" md="6">
              <VRow>
                <VCol cols="6">
                  <div class="text-h5 font-weight-bold">{{ plantReport[currentMonthLabel]?.Fkm_ofc || 0 }}</div>
                  <div class="text-caption text-medium-emphasis">{{ t('Dashboard.Production.OpticalKfkm') }}</div>
                  <div class="text-caption text-medium-emphasis">
                    {{ t('Dashboard.Production.VsMonth', { month: previousMonthLabel }) }}: {{ previousPlantReport[previousMonthLabel]?.Fkm_ofc || 0 }}
                  </div>
                  <div class="text-caption" :class="ofcDifference && ofcDifference >= 0 ? 'text-success' : 'text-error'">
                    {{ ofcDifference && ofcDifference >= 0 ? '+' : '' }}{{ ofcDifference }}
                  </div>
                </VCol>
                <VCol cols="6">
                  <div class="text-h5 font-weight-bold">{{ plantReport[currentMonthLabel]?.Ckm_ofc || 0 }}</div>
                  <div class="text-caption text-medium-emphasis">{{ t('Dashboard.Production.OpticalCkm') }}</div>
                  <div class="text-caption text-medium-emphasis">
                    {{ t('Dashboard.Production.VsMonth', { month: previousMonthLabel }) }}: {{ previousPlantReport[previousMonthLabel]?.Ckm_ofc || 0 }}
                  </div>
                  <div class="text-caption" :class="ofcCkmDifference && ofcCkmDifference >= 0 ? 'text-success' : 'text-error'">
                    {{ ofcCkmDifference && ofcCkmDifference >= 0 ? '+' : '' }}{{ ofcCkmDifference }}
                  </div>
                </VCol>
              </VRow>
            </VCol>
          </VRow>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>

  <VRow class="match-height mt-3">
    <VCol v-if="can('report', 'Hr-Dipendenti')" cols="12" md="6">
      <VCard flat border class="h-100">
        <VCardItem>
          <VCardTitle class="font-weight-bold">{{ t('Dashboard.HR.UpcomingDeadlines') }}</VCardTitle>
        </VCardItem>
        <VDivider />
        <VTable class="text-no-wrap backend-dashboard-table">
          <thead>
          <tr>
            <th>{{ t('Dashboard.HR.Status') }}</th>
            <th>{{ t('Dashboard.HR.Employee') }}</th>
            <th>{{ t('Dashboard.HR.Type') }}</th>
            <th>{{ t('Dashboard.HR.ExpiryDate') }}</th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="(item, index) in upcomingDeadlines" :key="index" @click="router.push({ name: 'hr-scadenze', query: { tab: item.typeKey === 'Formazione' ? 'formazioni' : 'competenze' } })" class="cursor-pointer">
            <td>
              <VChip :color="item.statusKey === 'scaduta' ? 'error' : 'warning'" size="small" label class="font-weight-bold text-uppercase">
                {{ item.status }}
              </VChip>
            </td>
            <td class="font-weight-medium">{{ item.nome_completo }}</td>
            <td class="text-medium-emphasis text-caption">{{ item.type }} - {{ item.typeKey === 'Formazione' ? item.formazione : item.attivita }}</td>
            <td>{{ item.data_scadenza }}</td>
          </tr>
          <tr v-if="!upcomingDeadlines.length">
            <td colspan="4" class="text-center text-medium-emphasis py-4">{{ t('Dashboard.HR.NoUpcomingDeadlines') }}</td>
          </tr>
          </tbody>
        </VTable>
      </VCard>
    </VCol>

    <VCol v-if="can('list', 'Hr-Richieste')" cols="12" md="6">
      <VCard flat border class="h-100">
        <VCardItem>
          <VCardTitle class="font-weight-bold">{{ t('Dashboard.HR.PendingRequests') }}</VCardTitle>
        </VCardItem>
        <VDivider />
        <VTable class="text-no-wrap backend-dashboard-table">
          <thead>
          <tr>
            <th>{{ t('Dashboard.HR.Employee') }}</th>
            <th>{{ t('Dashboard.HR.Type') }}</th>
            <th>{{ t('Dashboard.HR.RequestDate') }}</th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="(item, index) in pendingRequestsReport.items" :key="index" @click="router.push({ name: 'hr-richieste-view-id', params: { id: item.id } })" class="cursor-pointer">
            <td class="font-weight-medium">{{ item.dipendente_cognome }} {{ item.dipendente_nome }}</td>
            <td><VChip color="success" variant="tonal" size="small">{{ item.tipologia }}</VChip></td>
            <td class="text-medium-emphasis">{{ item.data_richiesta }}</td>
          </tr>
          <tr v-if="!pendingRequestsReport.items.length">
            <td colspan="3" class="text-center text-medium-emphasis py-4">{{ t('Dashboard.HR.NoPendingRequests') }}</td>
          </tr>
          </tbody>
        </VTable>
      </VCard>
    </VCol>
  </VRow>

  <VRow v-if="can('list', 'Reception-Register')" class="match-height mt-3">
    <VCol cols="12" md="6">
      <VCard flat border class="h-100">
        <VCardItem>
          <VCardTitle class="font-weight-bold">{{ t('Dashboard.Reception.ActiveVisitors') }}</VCardTitle>
          <template #append>
            <VBtn icon variant="text" size="small" :href="`/api/export/visitorsPresent/excel`" target="_blank">
              <VIcon icon="tabler-download" />
            </VBtn>
          </template>
        </VCardItem>
        <VDivider />
        <div style="max-height: 320px; overflow-y: auto;">
          <VTable>
            <tbody>
            <tr v-for="(item, index) in visitorsPresent.items" :key="index">
              <td>
                <div class="font-weight-medium">{{ item.nome }}</div>
                <div class="text-caption text-medium-emphasis">{{ item.azienda }}</div>
              </td>
              <td class="text-end">
                <VChip color="success" size="small" label class="me-2">{{ t('Dashboard.Reception.InCompany') }}</VChip>
                <span class="text-caption text-medium-emphasis">{{ new Date(item.data_azione).toLocaleTimeString(locale.value, { hour: '2-digit', minute: '2-digit' }) }}</span>
              </td>
            </tr>
            <tr v-if="!visitorsPresent.items.length">
              <td class="text-center text-medium-emphasis py-4">{{ t('Dashboard.Reception.NoVisitors') }}</td>
            </tr>
            </tbody>
          </VTable>
        </div>
      </VCard>
    </VCol>

    <VCol cols="12" md="6">
      <VCard flat border class="h-100">
        <VCardItem>
          <VCardTitle class="font-weight-bold">{{ t('Dashboard.Reception.RecentAccess') }}</VCardTitle>
        </VCardItem>
        <VDivider />
        <div style="max-height: 320px; overflow-y: auto;">
          <VTable>
            <tbody>
            <tr v-for="(item, index) in recentActivities" :key="index">
              <td>
                <div class="font-weight-medium">{{ item.nome }}</div>
                <div class="text-caption text-medium-emphasis">{{ item.azienda }} — {{ t('Dashboard.Reception.GuestOf', { name: item.full_name }) }}</div>
              </td>
              <td class="text-end">
                <VChip :color="item.azione === 'Entrata' ? 'success' : 'warning'" size="small" label>
                  {{ item.azione === 'Entrata' ? t('Dashboard.Reception.Entry') : t('Dashboard.Reception.Exit') }}
                </VChip>
                <div class="text-caption text-medium-emphasis mt-1">
                  {{ new Date(item.data_azione).toLocaleString(locale.value, { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }) }}
                </div>
              </td>
            </tr>
            </tbody>
          </VTable>
        </div>
      </VCard>
    </VCol>
  </VRow>
</template>

<style lang="scss">
@use "@core-scss/template/libs/apex-chart.scss";

.backend-dashboard-table {
  th {
    font-weight: 600 !important;
    letter-spacing: 0.5px;
    font-size: 0.75rem !important;
    color: rgba(var(--v-theme-on-surface), 0.6) !important;
    background-color: rgba(var(--v-theme-on-surface), 0.02) !important;
  }
  td {
    height: 52px !important;
  }
}
</style>
