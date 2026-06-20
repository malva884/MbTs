<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import moment from 'moment'

const loading = ref(false)
const stats = ref<any>({
  periodo: { inizio: '', fine: '' },
  volumi: { totale_ricevuti: 0, da_fare: 0, in_corso: 0, completati: 0, tasso_completamento_percentuale: 0 },
  efficienza_ore: { attesa_media: 0, controllo_medio: 0, lead_time_totale: 0 },
  trend: []
})

const serverItems = ref<any[]>([])
const totalItems = ref(0)
const itemsPerPage = ref(5)
const page = ref(1)

const filters = ref({
  start_date: moment().subtract(30, 'days').format('YYYY-MM-DD'),
  end_date: moment().format('YYYY-MM-DD')
})

const headers = [
  { title: 'Operatore', key: 'operatore_nome', sortable: false },
  { title: 'F1', key: 'avanzamenti_fase1', align: 'center', sortable: false, width: '50px' },
  { title: 'F2', key: 'chiusure_fase2', align: 'center', sortable: false, width: '50px' },
  { title: 'Tot', key: 'azioni_totali', align: 'end', sortable: false, width: '60px' },
]

const loadDashboardData = async () => {
  loading.value = true
  try {
    const { data: resultData } = await useApi<any>(createUrl('/qt/document/stats', {
      query: {
        start_date: filters.value.start_date ? `${filters.value.start_date} 00:00:00` : undefined,
        end_date: filters.value.end_date ? `${filters.value.end_date} 23:59:59` : undefined,
        page: page.value,
        itemsPerPage: itemsPerPage.value
      },
    }))

    if (resultData.value) {
      stats.value = resultData.value
      const operatoriRaw = resultData.value.operatori || []
      serverItems.value = Array.isArray(operatoriRaw) ? operatoriRaw : (operatoriRaw.data || [])
      totalItems.value = operatoriRaw.total || serverItems.value.length
    }
  } catch (error) {
    console.error("Errore caricamento dati report", error)
  } finally {
    loading.value = false
  }
}

const updateOptions = (options: any) => {
  page.value = options.page
  itemsPerPage.value = options.itemsPerPage
  loadDashboardData()
}

const maxTrendValue = computed(() => {
  if (!stats.value.trend || stats.value.trend.length === 0) return 1
  return Math.max(...stats.value.trend.map((t: any) => t.documenti_completati), 1)
})

onMounted(loadDashboardData)
</script>

<template>
  <div class="crm-analytics-container pa-6">
    <VRow dense>

      <VCol cols="12" class="d-flex justify-between align-center mb-5 flex-wrap gap-4">
        <div>
          <h2 class="text-h4 font-weight-bold text-high-emphasis tracking-tight mb-1">Analytics Qualità</h2>
          <p class="text-sm text-secondary mb-0">Monitoraggio tempi di attraversamento, carichi di lavoro e produttività del reparto.</p>
        </div>

        <div class="d-flex align-center gap-2 bg-surface border rounded-lg pa-2 shadow-sm filter-bar-crm">
          <div class="d-flex align-center date-input-wrapper px-2">
            <VIcon icon="tabler-calendar" size="16" class="text-secondary mr-2" />
            <input type="date" v-model="filters.start_date" class="custom-date-input" @change="loadDashboardData" />
            <span class="text-disabled mx-2 text-xs font-weight-bold">AL</span>
            <input type="date" v-model="filters.end_date" class="custom-date-input" @change="loadDashboardData" />
          </div>
          <VDivider vertical inset class="mx-1" />
          <VBtn
            icon="tabler-refresh"
            variant="text"
            density="comfortable"
            color="primary"
            :loading="loading"
            @click="loadDashboardData"
          />
        </div>
      </VCol>

      <VCol cols="12" class="mb-4">
        <VRow dense>

          <VCol cols="12" sm="6" md="3">
            <VCard variant="flat" class="crm-kpi-card border pa-4 bg-surface">
              <div class="d-flex align-center justify-between mb-2">
                <span class="text-xs font-weight-medium text-secondary uppercase tracking-wider">Lead Time Medio</span>
                <VAvatar color="primary" variant="tonal" rounded="md" size="34">
                  <VIcon icon="tabler-clock-bolt" size="18" />
                </VAvatar>
              </div>
              <h3 class="text-h4 font-weight-bold text-high-emphasis mb-1">{{ stats.efficienza_ore.lead_time_totale }}h</h3>
              <div class="text-xxs text-secondary d-flex gap-1 align-center mt-1">
                <span class="font-weight-medium">Attesa: {{ stats.efficienza_ore.attesa_media }}h</span>
                <span class="text-disabled">•</span>
                <span class="font-weight-medium">Controllo: {{ stats.efficienza_ore.controllo_medio }}h</span>
              </div>
            </VCard>
          </VCol>

          <VCol cols="12" sm="6" md="3">
            <VCard variant="flat" class="crm-kpi-card border pa-4 bg-surface">
              <div class="d-flex align-center justify-between mb-2">
                <span class="text-xs font-weight-medium text-secondary uppercase tracking-wider">Documenti Chiusi</span>
                <VAvatar color="success" variant="tonal" rounded="md" size="34">
                  <VIcon icon="tabler-circle-check" size="18" />
                </VAvatar>
              </div>
              <h3 class="text-h4 font-weight-bold text-success mb-1">{{ stats.volumi.completati }}</h3>
              <div class="text-xxs text-secondary mt-1">
                Su un totale registrato di <strong>{{ stats.volumi.totale_ricevuti }}</strong> pratiche
              </div>
            </VCard>
          </VCol>

          <VCol cols="12" sm="6" md="3">
            <VCard variant="flat" class="crm-kpi-card border pa-4 bg-surface">
              <div class="d-flex align-center justify-between mb-2">
                <span class="text-xs font-weight-medium text-secondary uppercase tracking-wider">Tasso Evasione</span>
                <VAvatar color="info" variant="tonal" rounded="md" size="34">
                  <VIcon icon="tabler-chart-pie" size="18" />
                </VAvatar>
              </div>
              <h3 class="text-h4 font-weight-bold text-high-emphasis mb-2">{{ stats.volumi.tasso_completamento_percentuale }}%</h3>
              <VProgressLinear :model-value="stats.volumi.tasso_completamento_percentuale" color="info" height="5" rounded />
            </VCard>
          </VCol>

          <VCol cols="12" sm="6" md="3">
            <VCard variant="flat" class="crm-kpi-card border pa-4 bg-surface">
              <div class="d-flex align-center justify-between mb-2">
                <span class="text-xs font-weight-medium text-secondary uppercase tracking-wider">In Coda (WIP)</span>
                <VAvatar color="warning" variant="tonal" rounded="md" size="34">
                  <VIcon icon="tabler-hourglass-high" size="18" />
                </VAvatar>
              </div>
              <h3 class="text-h4 font-weight-bold text-warning mb-1">{{ stats.volumi.in_corso + stats.volumi.da_fare }}</h3>
              <div class="text-xxs text-secondary d-flex gap-1 align-center mt-1">
                <span class="font-weight-medium">Fase 1: {{ stats.volumi.in_corso }}</span>
                <span class="text-disabled">•</span>
                <span class="font-weight-medium">Da Fare: {{ stats.volumi.da_fare }}</span>
              </div>
            </VCard>
          </VCol>

        </VRow>
      </VCol>

      <VCol cols="12" md="6" class="pr-md-2 mb-3">
        <VCard variant="flat" class="border bg-surface h-100 crm-dashboard-card d-flex flex-column justify-between">
          <div class="card-header-action pa-4 border-b">
            <div class="d-flex align-center justify-between">
              <div>
                <h4 class="text-subtitle-1 font-weight-bold text-high-emphasis mb-0">Trend Giornaliero Evasioni</h4>
                <p class="text-xs text-secondary mb-0">Volumi di pratiche portate in Fase 2 per giorno.</p>
              </div>
              <VIcon icon="tabler-chart-bar" class="text-secondary" size="20" />
            </div>
          </div>

          <div class="card-body-content pa-4 flex-grow-1 d-flex align-end">
            <div v-if="stats.trend.length > 0" class="chart-wrapper d-flex align-end justify-between w-100 pt-4">
              <div v-for="day in stats.trend" :key="day.data_giorno" class="chart-column d-flex flex-column align-center flex-grow-1 mx-1">
                <span class="text-xxs font-weight-bold text-high-emphasis mb-1">{{ day.documenti_completati }}</span>
                <div
                  class="chart-bar rounded-t bg-primary-gradient"
                  :style="{ height: `${(day.documenti_completati / maxTrendValue) * 120}px` }"
                  :title="`${day.documenti_completati} Doc il ${moment(day.data_giorno).format('DD/MM')}`"
                ></div>
                <span class="text-xxs text-secondary mt-2 font-weight-medium">{{ moment(day.data_giorno).format('DD/MM') }}</span>
              </div>
            </div>

            <div v-else class="d-flex flex-column align-center justify-center py-10 text-secondary text-sm gap-2 w-100">
              <VIcon icon="tabler-chart-bar-off" size="28" class="text-disabled" />
              Nessun movimento registrato nel periodo selezionato.
            </div>
          </div>
        </VCard>
      </VCol>

      <VCol cols="12" md="6" class="pl-md-2 mb-3">
        <VCard variant="flat" class="border bg-surface h-100 crm-dashboard-card d-flex flex-column">
          <div class="card-header-action pa-4 border-b">
            <div class="d-flex align-center justify-between">
              <div>
                <h4 class="text-subtitle-1 font-weight-bold text-high-emphasis mb-0">Produttività Operatori</h4>
                <p class="text-xs text-secondary mb-0">Riepilogo delle azioni gestite dai singoli validatori.</p>
              </div>
              <VIcon icon="tabler-users" class="text-secondary" size="20" />
            </div>
          </div>

          <div class="card-body-content flex-grow-1 pa-2">
            <VDataTableServer
              v-model:items-per-page="itemsPerPage"
              :headers="headers"
              :items="serverItems"
              :items-length="totalItems"
              :loading="loading"
              density="comfortable"
              class="crm-premium-table"
              @update:options="updateOptions"
            >
              <template #item.operatore_nome="{ item }">
                <div class="d-flex align-center gap-2">
                  <VAvatar size="26" color="primary" variant="tonal" class="text-xs font-weight-bold">
                    {{ (item.raw?.operatore_nome || item.operatore_nome || 'U').substring(0, 2).toUpperCase() }}
                  </VAvatar>
                  <span class="font-weight-semibold text-high-emphasis text-xs">
                    {{ item.raw?.operatore_nome || item.operatore_nome }}
                  </span>
                </div>
              </template>

              <template #item.avanzamenti_fase1="{ item }">
                <VChip variant="tonal" color="warning" size="x-small" class="font-weight-bold px-2">
                  {{ item.raw?.avanzamenti_fase1 ?? item.avanzamenti_fase1 }}
                </VChip>
              </template>

              <template #item.chiusure_fase2="{ item }">
                <VChip variant="tonal" color="success" size="x-small" class="font-weight-bold px-2">
                  {{ item.raw?.chiusure_fase2 ?? item.chiusure_fase2 }}
                </VChip>
              </template>

              <template #item.azioni_totali="{ item }">
                <span class="text-xs font-weight-bold text-primary mr-2">
                  {{ item.raw?.azioni_totali || item.azioni_totali }}
                </span>
              </template>
            </VDataTableServer>
          </div>
        </VCard>
      </VCol>

    </VRow>
  </div>
</template>

<style scoped>
/* CONTENITORE GENERALE CONFIGURATO IN STILE ATHERA */
.crm-analytics-container {
  background-color: rgb(var(--v-theme-background));
  min-height: 100%;
}

/* CARDS E STRUTTURE DEL CORE CRM */
.border {
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08) !important;
  box-shadow: none !important;
  border-radius: 12px !important;
}
.border-b {
  border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.08) !important;
}
.bg-surface {
  background-color: rgb(var(--v-theme-surface)) !important;
}
.crm-dashboard-card {
  overflow: hidden;
}
.crm-kpi-card {
  transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
.crm-kpi-card:hover {
  transform: translateY(-2px);
}

/* BARRA FILTRO DATA IN LINEA */
.filter-bar-crm {
  border-color: rgba(var(--v-theme-on-surface), 0.12) !important;
}
.date-input-wrapper {
  background-color: rgba(var(--v-theme-on-surface), 0.02);
  border-radius: 6px;
  border: 1px solid rgba(var(--v-theme-on-surface), 0.06);
}
.custom-date-input {
  border: none;
  outline: none;
  color: rgb(var(--v-theme-on-surface));
  font-family: inherit;
  font-weight: 600;
  font-size: 13px;
  background: transparent;
  cursor: pointer;
  width: 115px;
}

/* RENDERING DELLE TABELLE IN STILE PREMIUM CRM */
.crm-premium-table {
  background: transparent !important;
}
.crm-premium-table :deep(th) {
  background-color: rgba(var(--v-theme-on-surface), 0.02) !important;
  border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.08) !important;
  height: 38px !important;
}
.crm-premium-table :deep(th .v-data-table-header__content) {
  color: rgb(var(--v-theme-text-secondary)) !important;
  font-weight: 700 !important;
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.crm-premium-table :deep(td) {
  height: 44px !important;
  border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.04) !important;
}
.crm-premium-table :deep(.v-data-table-footer) {
  border-top: 1px solid rgba(var(--v-theme-on-surface), 0.04) !important;
  padding-top: 4px;
}

/* COMPATTEZZA MINI GRAFICO NATIVO */
.chart-wrapper {
  height: 150px;
}
.chart-column {
  max-width: 40px;
}
.chart-bar {
  width: 100%;
  min-height: 2px;
  transition: height 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border-top-left-radius: 4px !important;
  border-top-right-radius: 4px !important;
}
.bg-primary-gradient {
  background: linear-gradient(180deg, rgb(var(--v-theme-primary)) 0%, rgba(var(--v-theme-primary), 0.25) 100%);
}
.chart-bar:hover {
  filter: brightness(1.15);
}

/* UTILITIES ATOMICE TYPOGRAPHY */
.text-secondary {
  color: rgba(var(--v-theme-on-surface), 0.6) !important;
}
.text-disabled {
  color: rgba(var(--v-theme-on-surface), 0.35) !important;
}
.uppercase {
  text-transform: uppercase;
}
.tracking-wider {
  letter-spacing: 0.5px !important;
}
.gap-1 { gap: 4px; }
.gap-2 { gap: 8px; }
.gap-4 { gap: 16px; }
.text-xxs { font-size: 11px !important; }
</style>
