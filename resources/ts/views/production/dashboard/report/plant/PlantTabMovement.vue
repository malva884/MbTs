<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { ref, watch } from 'vue'

interface Props {
  periodoData: string
  meseSelezionato: string
}

const props = defineProps<Props>()
const { t } = useI18n()
const loading = ref(true)
const totalItems = ref(0)

const sortBy = ref()
const orderBy = ref()
const page = ref(1)
const itemsPerPage = ref(30)
const matarialeFilter = ref('')
const movimentoFilter = ref([])
const userFilter = ref([])
const loadingPage = ref(false)
const serverItems = ref<any>([])
const scartiItems = ref<any>([])
const latestUpdatedData = ref('')

// Tab attiva per la sezione analitica di destra
const mainDivisionTab = ref('ottico')
const activeAnalysisTab = ref('scarti')
const activeAnalysisTabRame = ref('scarti')

// headers
const headers = [
  { title: t('Label.Utente'), key: 'user' },
  { title: t('Table.Quantita'), key: 'quantita', align: 'end' as const },
  { title: t('Table.Um'), key: 'um', align: 'center' as const },
  { title: t('Label.Importo'), key: 'importo', sortable: false, align: 'end' as const },
]

const updateOptions = (options: any) => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
  page.value = options.page
  itemsPerPage.value = options.itemsPerPage
  loadItems()
}

const loadItems = async () => {
  loading.value = true
  const { data: resultData } = await useApi<any>(createUrl('/production/plant/movement/', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      matariale: matarialeFilter.value,
      movimento: JSON.stringify(movimentoFilter.value),
      user: JSON.stringify(userFilter.value),
      periodo: props.periodoData,
    },
  }))

  if (resultData.value !== null) {
    serverItems.value = resultData.value.data
    totalItems.value = resultData.value.total
  } else {
    serverItems.value = []
    totalItems.value = 0
  }
  loading.value = false
}

const loadScarti = async () => {
  loadingPage.value = true
  const { data: resultSData } = await useApi<any>(createUrl('/production/plant/scarti/'))
  scartiItems.value = resultSData.value.dati
  latestUpdatedData.value = resultSData.value.latestUpdatedData
  loadingPage.value = false
}

const resolveUser = (user: string) => {
  const users: Record<string, string> = {
    '23920632': 'Ghidin Roberta',
    '23910700': 'Varisco Francesca',
    '23910519': 'Busetti Daniela',
    '23910470': 'Vignoni Davide',
    '23910430': 'Guerreschi Antonio',
    '23910263': 'Betella Gloria',
    '23920511': 'Fogliata Vanni',
    '23920619': 'Carrera Chiara',
    '23920599': 'Vitarelli Gianpaolo',
    '23910730': 'Singh Sunpreet',
    '23910839': 'Ricca Asia'
  }
  return users[user] || user
}

const formatValue = (number: number) => {
  if (!Number(number)) return '-'
  return new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' }).format(number)
}

const euro = new Intl.NumberFormat('it-IT', {
  style: 'currency',
  currency: 'EUR',
})

loadItems()
loadScarti()

watch(props, () => {
  loadItems()
  loadScarti()
})
</script>

<template>
  <VRow class="g-3 align-stretch">

    <VCol cols="12" class="pb-1">
      <VCard variant="outlined" class="d-flex align-center px-4 py-2 bg-surface system-status-bar">
        <div class="status-dot pulsing me-2.5"></div>
        <span class="text-caption font-weight-medium text-secondary me-1">Ultimo Aggiornamento:</span>
        <span class="text-caption font-weight-bold text-success">{{ latestUpdatedData || '---' }}</span>
      </VCard>
    </VCol>

    <VCol cols="12" md="7">
      <VCard variant="outlined" class="main-dashboard-card h-100">
        <div class="px-4 py-3 bg-header border-b d-flex align-center justify-space-between">
          <div class="d-flex align-center gap-2">
            <VIcon icon="tabler-arrows-transfer-down" color="primary" size="20" />
            <span class="text-subtitle-1 font-weight-bold text-high-emphasis">
              {{ $t('Label.Movimenti del') }} <span class="text-primary font-weight-semibold">{{ props.periodoData }}</span>
            </span>
          </div>
        </div>

        <div class="px-4 py-3 bg-light-filters border-b">
          <VRow class="g-2">
            <VCol cols="12" sm="6">
              <AppSelect
                v-model="movimentoFilter"
                :placeholder="$t('Label.Moviemnti')"
                :items="[{ title: '201', value: 201 }, { title: '202', value: 202 }, { title: '261', value: 261 }, { title: '262', value: 262 }, { title: '551', value: 551 }, { title: '552', value: 552 }, { title: '553', value: 553 }, { title: '701', value: 701 }, { title: '702', value: 702 }]"
                clearable
                clear-icon="tabler-x"
                multiple
                density="compact"
                hide-details
                @focusout="loadItems"
              >
                <template #prepend-inner>
                  <VIcon icon="tabler-filter" size="16" class="text-disabled" />
                </template>
              </AppSelect>
            </VCol>

            <VCol cols="12" sm="6">
              <AppSelect
                v-model="userFilter"
                :placeholder="$t('Label.Utenti')"
                :items="[{ title: 'Ghidin Roberta', value: '23920632' }, { title: 'Varisco Francesca', value: '23910700' }, { title: 'Busetti Daniela', value: '23910519' }, { title: 'Fogliata Vanni', value: '23920511' }, { title: 'Carrera Chiara', value: '23920619' }, { title: 'Vitarelli Gianpaolo', value: '23920599' }, { title: 'Singh Sunpreet', value: '23910730' }, { title: 'Vignoni Davide', value: '23910470' }, { title: 'Guerreschi Antonio', value: '23910430' }, { title: 'Betella Gloria', value: '23910263' }]"
                clearable
                clear-icon="tabler-x"
                multiple
                density="compact"
                hide-details
                @focusout="loadItems"
              >
                <template #prepend-inner>
                  <VIcon icon="tabler-user" size="16" class="text-disabled" />
                </template>
              </AppSelect>
            </VCol>
          </VRow>
        </div>

        <VDataTableServer
          v-model:items-per-page="itemsPerPage"
          :headers="headers"
          :items="serverItems"
          :items-length="totalItems"
          :loading="loading"
          density="compact"
          class="compact-server-table"
          @update:options="updateOptions"
        >
          <template #item.user="{ item }">
            <span class="font-weight-medium text-high-emphasis">{{ resolveUser(item.user) }}</span>
          </template>

          <template #item.quantita="{ item }">
            <span class="font-weight-bold">{{ item.quantita }}</span>
          </template>

          <template #item.importo="{ item }">
            <span class="font-weight-bold text-warning font-mono">{{ euro.format(item.importo) }}</span>
          </template>
        </VDataTableServer>
      </VCard>
    </VCol>

    <VCol cols="12" md="5">
      <VCard variant="outlined" class="main-dashboard-card">

        <!-- Tab Principale: Ottico / Rame -->
        <VTabs v-model="mainDivisionTab" color="primary" grow density="compact" class="border-b bg-division-header">
          <VTab value="ottico" class="text-subtitle-2 font-weight-bold px-3">
            <VIcon icon="tabler-antenna" size="18" class="me-1" /> Ottico
          </VTab>
          <VTab value="rame" class="text-subtitle-2 font-weight-bold px-3">
            <VIcon icon="tabler-bolt" size="18" class="me-1" /> Rame
          </VTab>
        </VTabs>

        <VWindow v-model="mainDivisionTab">
          <!-- SEZIONE OTTICO -->
          <VWindowItem value="ottico">
            <VTabs v-model="activeAnalysisTab" color="primary" grow density="compact" class="border-b bg-header">
              <VTab value="scarti" class="text-caption font-weight-bold px-2">
                <VIcon icon="tabler-trash-x" size="16" class="me-1" /> Scarti
              </VTab>
              <VTab value="consumi" class="text-caption font-weight-bold px-2">
                <VIcon icon="tabler-droplet" size="16" class="me-1" /> Consumi
              </VTab>
              <VTab value="differenze" class="text-caption font-weight-bold px-2">
                <VIcon icon="tabler-scale" size="16" class="me-1" /> Diff %
              </VTab>
            </VTabs>

            <VWindow v-model="activeAnalysisTab">

              <VWindowItem value="scarti">
                <VTable density="compact" class="compact-kpi-table text-no-wrap">
                  <thead>
                  <tr>
                    <th>Mese</th>
                    <th>Reparto / Valori Settimanali</th>
                    <th class="text-end">Totale</th>
                  </tr>
                  </thead>
                  <tbody>
                  <tr v-for="(items, month) in scartiItems" :key="month">
                    <td class="font-weight-bold text-primary align-baseline pt-3">{{ month }}</td>
                    <td class="pa-1">
                      <div class="kpi-subrow">
                        <span class="subrow-badge jack">JK</span>
                        <div class="subrow-weeks">
                          <span>W1: <b>{{ formatValue(items.JACK[1].Scarto) }}</b></span>
                          <span>W2: <b>{{ formatValue(items.JACK[2].Scarto) }}</b></span>
                          <span>W3: <b>{{ formatValue(items.JACK[3].Scarto) }}</b></span>
                          <span>W4: <b>{{ formatValue(items.JACK[4].Scarto) }}</b></span>
                        </div>
                      </div>
                      <div class="kpi-subrow">
                        <span class="subrow-badge szd">SZ</span>
                        <div class="subrow-weeks">
                          <span>W1: <b>{{ formatValue(items.SZD[1].Scarto) }}</b></span>
                          <span>W2: <b>{{ formatValue(items.SZD[2].Scarto) }}</b></span>
                          <span>W3: <b>{{ formatValue(items.SZD[3].Scarto) }}</b></span>
                          <span>W4: <b>{{ formatValue(items.SZD[4].Scarto) }}</b></span>
                        </div>
                      </div>
                      <div class="kpi-subrow border-0">
                        <span class="subrow-badge buf">BF</span>
                        <div class="subrow-weeks">
                          <span>W1: <b>{{ formatValue(items.BUF[1].Scarto) }}</b></span>
                          <span>W2: <b>{{ formatValue(items.BUF[2].Scarto) }}</b></span>
                          <span>W3: <b>{{ formatValue(items.BUF[3].Scarto) }}</b></span>
                          <span>W4: <b>{{ formatValue(items.BUF[4].Scarto) }}</b></span>
                        </div>
                      </div>
                      <div class="kpi-subrow border-0">
                        <span class="subrow-badge pe">PE</span>
                        <div class="subrow-weeks">
                          <span>W1: <b>{{ formatValue(items.PE[1].Scarto) }}</b></span>
                          <span>W2: <b>{{ formatValue(items.PE[2].Scarto) }}</b></span>
                          <span>W3: <b>{{ formatValue(items.PE[3].Scarto) }}</b></span>
                          <span>W4: <b>{{ formatValue(items.PE[4].Scarto) }}</b></span>
                        </div>
                      </div>
                      <div class="kpi-subrow border-0">
                        <span class="subrow-badge mo">MO</span>
                        <div class="subrow-weeks">
                          <span>W1: <b>{{ formatValue(items.FO[1].Scarto) }}</b></span>
                          <span>W2: <b>{{ formatValue(items.FO[2].Scarto) }}</b></span>
                          <span>W3: <b>{{ formatValue(items.FO[3].Scarto) }}</b></span>
                          <span>W4: <b>{{ formatValue(items.FO[4].Scarto) }}</b></span>
                        </div>
                      </div>
                      <div class="d-flex align-center justify-end pt-1 pe-2 font-weight-bold text-caption text-secondary border-t" style="height: 24px;">
                        <span>Totale Mese:</span>
                      </div>
                    </td>
                    <td class="text-end align-baseline pt-2">
                      <div class="d-flex flex-column align-end font-mono font-weight-bold text-warning gap-1">
                        <div style="height: 22px;">{{ formatValue(items.JACK.t_scarto) }}</div>
                        <div style="height: 22px;">{{ formatValue(items.SZD.t_scarto) }}</div>
                        <div style="height: 22px;">{{ formatValue(items.BUF.t_scarto) }}</div>
                        <div style="height: 22px;">{{ formatValue(items.PE.t_scarto) }}</div>
                        <div style="height: 22px;">{{ formatValue(items.FO.t_scarto) }}</div>
                        <div class="border-t pt-1 text-error" style="height: 24px;">{{ formatValue(items.totale_scarto) }}</div>
                      </div>
                    </td>
                  </tr>
                  </tbody>
                </VTable>
              </VWindowItem>

              <VWindowItem value="consumi">
                <VTable density="compact" class="compact-kpi-table text-no-wrap">
                  <thead>
                  <tr>
                    <th>Mese</th>
                    <th>Settimane (W1 - W4)</th>
                    <th class="text-end">Totale Consumo</th>
                  </tr>
                  </thead>
                  <tbody>
                  <tr v-for="(items, month) in scartiItems" :key="month">
                    <td class="font-weight-bold text-primary py-2">{{ month }}</td>
                    <td>
                      <div class="subrow-weeks py-1">
                        <span>W1: <b>{{ formatValue(items[1]?.Consumi) }}</b></span>
                        <span>W2: <b>{{ formatValue(items[2]?.Consumi) }}</b></span>
                        <span>W3: <b>{{ formatValue(items[3]?.Consumi) }}</b></span>
                        <span>W4: <b>{{ formatValue(items[4]?.Consumi) }}</b></span>
                      </div>
                    </td>
                    <td class="text-end font-weight-bold text-warning font-mono py-2">
                      {{ formatValue(items.Consumi) }}
                    </td>
                  </tr>
                  </tbody>
                </VTable>
              </VWindowItem>

              <VWindowItem value="differenze">
                <VTable density="compact" class="compact-kpi-table text-no-wrap">
                  <thead>
                  <tr>
                    <th>Mese</th>
                    <th>Scostamento Fasi Effettivo</th>
                    <th class="text-end">Totale</th>
                  </tr>
                  </thead>
                  <tbody>
                  <tr v-for="(items, month) in scartiItems" :key="month">
                    <td class="font-weight-bold text-primary align-baseline pt-3">{{ month }}</td>
                    <td class="pa-1">
                      <div class="kpi-subrow">
                        <span class="subrow-badge jack">JK</span>
                        <div class="subrow-weeks">
                          <span>W1: <b>{{ items.JACK[1].Dif }}%</b></span>
                          <span>W2: <b>{{ items.JACK[2].Dif }}%</b></span>
                          <span>W3: <b>{{ items.JACK[3].Dif }}%</b></span>
                          <span>W4: <b>{{ items.JACK[4].Dif }}%</b></span>
                        </div>
                      </div>
                      <div class="kpi-subrow">
                        <span class="subrow-badge szd">SZ</span>
                        <div class="subrow-weeks">
                          <span>W1: <b>{{ items.SZD[1].Dif }}%</b></span>
                          <span>W2: <b>{{ items.SZD[2].Dif }}%</b></span>
                          <span>W3: <b>{{ items.SZD[3].Dif }}%</b></span>
                          <span>W4: <b>{{ items.SZD[4].Dif }}%</b></span>
                        </div>
                      </div>
                      <div class="kpi-subrow border-0">
                        <span class="subrow-badge buf">BF</span>
                        <div class="subrow-weeks">
                          <span>W1: <b>{{ items.BUF[1].Dif }}%</b></span>
                          <span>W2: <b>{{ items.BUF[2].Dif }}%</b></span>
                          <span>W3: <b>{{ items.BUF[3].Dif }}%</b></span>
                          <span>W4: <b>{{ items.BUF[4].Dif }}%</b></span>
                        </div>
                      </div>
                      <div class="kpi-subrow border-0">
                        <span class="subrow-badge pe">PE</span>
                        <div class="subrow-weeks">
                          <span>W1: <b>{{ items.PE[1].Dif }}%</b></span>
                          <span>W2: <b>{{ items.PE[2].Dif }}%</b></span>
                          <span>W3: <b>{{ items.PE[3].Dif }}%</b></span>
                          <span>W4: <b>{{ items.PE[4].Dif }}%</b></span>
                        </div>
                      </div>
                      <div class="kpi-subrow border-0">
                        <span class="subrow-badge mo">MO</span>
                        <div class="subrow-weeks">
                          <span>W1: <b>{{ items.FO[1].Dif }}%</b></span>
                          <span>W2: <b>{{ items.FO[2].Dif }}%</b></span>
                          <span>W3: <b>{{ items.FO[3].Dif }}%</b></span>
                          <span>W4: <b>{{ items.FO[4].Dif }}%</b></span>
                        </div>
                      </div>
                      <div class="d-flex align-center justify-space-between pt-1 border-t text-caption font-weight-bold" style="height: 24px;">
                        <span class="text-secondary ps-1">Totale Diff:</span>
                        <div class="subrow-weeks pe-1">
                          <span>W1: <b>{{ items.totale_dif_settimana?.[1] ?? 0 }}%</b></span>
                          <span>W2: <b>{{ items.totale_dif_settimana?.[2] ?? 0 }}%</b></span>
                          <span>W3: <b>{{ items.totale_dif_settimana?.[3] ?? 0 }}%</b></span>
                          <span>W4: <b>{{ items.totale_dif_settimana?.[4] ?? 0 }}%</b></span>
                        </div>
                      </div>
                    </td>
                    <td class="text-end align-baseline pt-2">
                      <div class="d-flex flex-column align-end font-weight-bold text-warning gap-1">
                        <div style="height: 22px;">{{ items.JACK.t_dif }} %</div>
                        <div style="height: 22px;">{{ items.SZD.t_dif }} %</div>
                        <div style="height: 22px;">{{ items.BUF.t_dif }} %</div>
                        <div style="height: 22px;">{{ items.PE.t_dif }} %</div>
                        <div style="height: 22px;">{{ items.FO.t_dif }} %</div>
                        <div class="border-t pt-1 text-error" style="height: 24px;">{{ items.totale_dif }} %</div>
                      </div>
                    </td>
                  </tr>
                  </tbody>
                </VTable>
              </VWindowItem>
            </VWindow>
          </VWindowItem>

          <!-- SEZIONE RAME -->
          <VWindowItem value="rame">
            <VTabs v-model="activeAnalysisTabRame" color="primary" grow density="compact" class="border-b bg-header">
              <VTab value="scarti" class="text-caption font-weight-bold px-2">
                <VIcon icon="tabler-trash-x" size="16" class="me-1" /> Scarti
              </VTab>
              <VTab value="consumi" class="text-caption font-weight-bold px-2">
                <VIcon icon="tabler-droplet" size="16" class="me-1" /> Consumi
              </VTab>
              <VTab value="differenze" class="text-caption font-weight-bold px-2">
                <VIcon icon="tabler-scale" size="16" class="me-1" /> Diff %
              </VTab>
            </VTabs>

            <VWindow v-model="activeAnalysisTabRame">
              <VWindowItem value="scarti">
                <VTable density="compact" class="compact-kpi-table text-no-wrap">
                  <thead>
                  <tr>
                    <th>Mese</th>
                    <th>Reparto / Valori Settimanali</th>
                    <th class="text-end">Totale</th>
                  </tr>
                  </thead>
                  <tbody>
                  <tr v-for="(items, month) in scartiItems" :key="month">
                    <td class="font-weight-bold text-primary align-baseline pt-3">{{ month }}</td>
                    <td class="pa-1">
                      <div class="kpi-subrow">
                        <span class="subrow-badge pf">PF</span>
                        <div class="subrow-weeks">
                          <span>W1: <b>{{ formatValue(items.PF?.[1]?.Scarto) }}</b></span>
                          <span>W2: <b>{{ formatValue(items.PF?.[2]?.Scarto) }}</b></span>
                          <span>W3: <b>{{ formatValue(items.PF?.[3]?.Scarto) }}</b></span>
                          <span>W4: <b>{{ formatValue(items.PF?.[4]?.Scarto) }}</b></span>
                        </div>
                      </div>
                      <div class="kpi-subrow">
                        <span class="subrow-badge sm">SM</span>
                        <div class="subrow-weeks">
                          <span>W1: <b>{{ formatValue(items.SM?.[1]?.Scarto) }}</b></span>
                          <span>W2: <b>{{ formatValue(items.SM?.[2]?.Scarto) }}</b></span>
                          <span>W3: <b>{{ formatValue(items.SM?.[3]?.Scarto) }}</b></span>
                          <span>W4: <b>{{ formatValue(items.SM?.[4]?.Scarto) }}</b></span>
                        </div>
                      </div>
                      <div class="kpi-subrow">
                        <span class="subrow-badge mr">MR</span>
                        <div class="subrow-weeks">
                          <span>W1: <b>{{ formatValue(items.MR?.[1]?.Scarto) }}</b></span>
                          <span>W2: <b>{{ formatValue(items.MR?.[2]?.Scarto) }}</b></span>
                          <span>W3: <b>{{ formatValue(items.MR?.[3]?.Scarto) }}</b></span>
                          <span>W4: <b>{{ formatValue(items.MR?.[4]?.Scarto) }}</b></span>
                        </div>
                      </div>
                      <div class="kpi-subrow border-0">
                        <span class="subrow-badge wr">WR</span>
                        <div class="subrow-weeks">
                          <span>W1: <b>{{ formatValue(items.WR?.[1]?.Scarto) }}</b></span>
                          <span>W2: <b>{{ formatValue(items.WR?.[2]?.Scarto) }}</b></span>
                          <span>W3: <b>{{ formatValue(items.WR?.[3]?.Scarto) }}</b></span>
                          <span>W4: <b>{{ formatValue(items.WR?.[4]?.Scarto) }}</b></span>
                        </div>
                      </div>
                      <div class="d-flex align-center justify-end pt-1 pe-2 font-weight-bold text-caption text-secondary border-t" style="height: 24px;">
                        <span>Totale Mese:</span>
                      </div>
                    </td>
                    <td class="text-end align-baseline pt-2">
                      <div class="d-flex flex-column align-end font-mono font-weight-bold text-warning gap-1">
                        <div style="height: 22px;">{{ formatValue(items.PF?.t_scarto) }}</div>
                        <div style="height: 22px;">{{ formatValue(items.SM?.t_scarto) }}</div>
                        <div style="height: 22px;">{{ formatValue(items.MR?.t_scarto) }}</div>
                        <div style="height: 22px;">{{ formatValue(items.WR?.t_scarto) }}</div>
                        <div class="border-t pt-1 text-error" style="height: 24px;">{{ formatValue(items.totale_scarto_rame) }}</div>
                      </div>
                    </td>
                  </tr>
                  </tbody>
                </VTable>
              </VWindowItem>

              <VWindowItem value="consumi">
                <VTable density="compact" class="compact-kpi-table text-no-wrap">
                  <thead>
                  <tr>
                    <th>Mese</th>
                    <th>Settimane (W1 - W4)</th>
                    <th class="text-end">Totale Consumo</th>
                  </tr>
                  </thead>
                  <tbody>
                  <tr v-for="(items, month) in scartiItems" :key="month">
                    <td class="font-weight-bold text-primary py-2">{{ month }}</td>
                    <td>
                      <div class="subrow-weeks py-1">
                        <span>W1: <b>{{ formatValue(items.rame?.[1]?.Consumi) }}</b></span>
                        <span>W2: <b>{{ formatValue(items.rame?.[2]?.Consumi) }}</b></span>
                        <span>W3: <b>{{ formatValue(items.rame?.[3]?.Consumi) }}</b></span>
                        <span>W4: <b>{{ formatValue(items.rame?.[4]?.Consumi) }}</b></span>
                      </div>
                    </td>
                    <td class="text-end font-weight-bold text-warning font-mono py-2">
                      {{ formatValue(items.Consumi_Rame) }}
                    </td>
                  </tr>
                  </tbody>
                </VTable>
              </VWindowItem>

              <VWindowItem value="differenze">
                <VTable density="compact" class="compact-kpi-table text-no-wrap">
                  <thead>
                  <tr>
                    <th>Mese</th>
                    <th>Scostamento Fasi Effettivo</th>
                    <th class="text-end">Totale</th>
                  </tr>
                  </thead>
                  <tbody>
                  <tr v-for="(items, month) in scartiItems" :key="month">
                    <td class="font-weight-bold text-primary align-baseline pt-3">{{ month }}</td>
                    <td class="pa-1">
                      <div class="kpi-subrow">
                        <span class="subrow-badge pf">PF</span>
                        <div class="subrow-weeks">
                          <span>W1: <b>{{ items.PF?.[1]?.Dif ?? 0 }}%</b></span>
                          <span>W2: <b>{{ items.PF?.[2]?.Dif ?? 0 }}%</b></span>
                          <span>W3: <b>{{ items.PF?.[3]?.Dif ?? 0 }}%</b></span>
                          <span>W4: <b>{{ items.PF?.[4]?.Dif ?? 0 }}%</b></span>
                        </div>
                      </div>
                      <div class="kpi-subrow">
                        <span class="subrow-badge sm">SM</span>
                        <div class="subrow-weeks">
                          <span>W1: <b>{{ items.SM?.[1]?.Dif ?? 0 }}%</b></span>
                          <span>W2: <b>{{ items.SM?.[2]?.Dif ?? 0 }}%</b></span>
                          <span>W3: <b>{{ items.SM?.[3]?.Dif ?? 0 }}%</b></span>
                          <span>W4: <b>{{ items.SM?.[4]?.Dif ?? 0 }}%</b></span>
                        </div>
                      </div>
                      <div class="kpi-subrow">
                        <span class="subrow-badge mr">MR</span>
                        <div class="subrow-weeks">
                          <span>W1: <b>{{ items.MR?.[1]?.Dif ?? 0 }}%</b></span>
                          <span>W2: <b>{{ items.MR?.[2]?.Dif ?? 0 }}%</b></span>
                          <span>W3: <b>{{ items.MR?.[3]?.Dif ?? 0 }}%</b></span>
                          <span>W4: <b>{{ items.MR?.[4]?.Dif ?? 0 }}%</b></span>
                        </div>
                      </div>
                      <div class="kpi-subrow border-0">
                        <span class="subrow-badge wr">WR</span>
                        <div class="subrow-weeks">
                          <span>W1: <b>{{ items.WR?.[1]?.Dif ?? 0 }}%</b></span>
                          <span>W2: <b>{{ items.WR?.[2]?.Dif ?? 0 }}%</b></span>
                          <span>W3: <b>{{ items.WR?.[3]?.Dif ?? 0 }}%</b></span>
                          <span>W4: <b>{{ items.WR?.[4]?.Dif ?? 0 }}%</b></span>
                        </div>
                      </div>
                      <div class="d-flex align-center justify-space-between pt-1 border-t text-caption font-weight-bold" style="height: 24px;">
                        <span class="text-secondary ps-1">Totale Diff:</span>
                        <div class="subrow-weeks pe-1">
                          <span>W1: <b>{{ items.totale_dif_settimana_rame?.[1] ?? 0 }}%</b></span>
                          <span>W2: <b>{{ items.totale_dif_settimana_rame?.[2] ?? 0 }}%</b></span>
                          <span>W3: <b>{{ items.totale_dif_settimana_rame?.[3] ?? 0 }}%</b></span>
                          <span>W4: <b>{{ items.totale_dif_settimana_rame?.[4] ?? 0 }}%</b></span>
                        </div>
                      </div>
                    </td>
                    <td class="text-end align-baseline pt-2">
                      <div class="d-flex flex-column align-end font-weight-bold text-warning gap-1">
                        <div style="height: 22px;">{{ items.PF?.t_dif ?? 0 }} %</div>
                        <div style="height: 22px;">{{ items.SM?.t_dif ?? 0 }} %</div>
                        <div style="height: 22px;">{{ items.MR?.t_dif ?? 0 }} %</div>
                        <div style="height: 22px;">{{ items.WR?.t_dif ?? 0 }} %</div>
                        <div class="border-t pt-1 text-error" style="height: 24px;">{{ items.totale_dif_rame ?? 0 }} %</div>
                      </div>
                    </td>
                  </tr>
                  </tbody>
                </VTable>
              </VWindowItem>
            </VWindow>
          </VWindowItem>
        </VWindow>
      </VCard>
    </VCol>
  </VRow>

  <LoadingStandBy v-model="loadingPage" />
</template>

<style scoped lang="scss">
.main-dashboard-card {
  border-radius: 8px;
  background-color: rgb(var(--v-theme-surface));
  border: 1px solid rgba(var(--v-border-color), 0.12) !important;
  overflow: hidden;
}

.bg-header {
  background-color: rgba(var(--v-theme-on-surface), 0.015);
}

.bg-division-header {
  background-color: rgba(var(--v-theme-on-surface), 0.04);
}

.bg-light-filters {
  background-color: rgba(var(--v-theme-on-surface), 0.005);
}

.border-b {
  border-bottom: 1px solid rgba(var(--v-border-color), 0.08) !important;
}

.border-t {
  border-top: 1px solid rgba(var(--v-border-color), 0.12) !important;
}

.font-mono {
  font-family: monospace, monospace !important;
  font-size: 0.875rem;
}

// Stile barra di stato superiore
.system-status-bar {
  border: 1px solid rgba(var(--v-border-color), 0.1) !important;
  border-radius: 6px;

  .status-dot {
    width: 7px;
    height: 7px;
    background-color: rgb(var(--v-theme-success));
    border-radius: 50%;
    &.pulsing {
      animation: statusPulse 2s infinite ease-in-out;
    }
  }
}

// Tabella Server Movimenti Compattata
.compact-server-table {
  background: transparent !important;
  :deep(thead th) {
    background-color: rgba(var(--v-theme-on-surface), 0.015) !important;
    font-size: 0.75rem !important;
    text-transform: uppercase;
    font-weight: 700 !important;
    color: rgba(var(--v-theme-on-surface), 0.6) !important;
    height: 38px !important;
  }
  :deep(tbody tr) {
    height: 34px !important;
    &:hover { background-color: rgba(var(--v-theme-primary), 0.03) !important; }
    td { font-size: 0.825rem !important; }
  }
}

// Struttura per i KPI Analitici ed eliminazione delle TR nidificate
.compact-kpi-table {
  background: transparent !important;
  width: 100%;

  thead th {
    font-size: 0.725rem !important;
    text-transform: uppercase;
    color: rgba(var(--v-theme-on-surface), 0.55) !important;
    height: 36px !important;
  }
  tbody td {
    font-size: 0.8rem !important;
    border-bottom: 1px solid rgba(var(--v-border-color), 0.06) !important;
  }

  .kpi-subrow {
    display: flex;
    align-items: center;
    padding: 3px 0;
    border-bottom: 1px dashed rgba(var(--v-border-color), 0.05);
    height: 23px;

    .subrow-badge {
      font-size: 0.625rem;
      font-weight: 900;
      padding: 1px 4px;
      border-radius: 3px;
      min-width: 20px;
      text-align: center;
      margin-right: 8px;
      color: #fff;

      &.jack { background-color: #6c5ce7; }
      &.szd { background-color: #00cec9; }
      &.buf { background-color: #e17055; }
      &.pe { background-color: #eec706; }
      &.mo { background-color: #258604; }
      &.pf { background-color: #e67e22; }
      &.sm { background-color: #3498db; }
      &.mr { background-color: #9c27b0; }
      &.wr { background-color: #00b894; }
    }
  }
}

.subrow-weeks {
  display: flex;
  gap: 10px;
  font-size: 0.725rem;
  color: rgba(var(--v-theme-on-surface), 0.7);
  b { color: rgb(var(--v-theme-on-surface)); font-family: monospace; }
}

@keyframes statusPulse {
  0% { opacity: 0.4; }
  50% { opacity: 1; }
  100% { opacity: 0.4; }
}
</style>
