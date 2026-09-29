<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'

definePage({
  meta: {
    action: 'list',
    subject: 'Spedizioni-Ddt',
  },
})

const itemsPerPage = ref(10)
const loading = ref(true)
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const page = ref(1)
const serverItems = ref<any[]>([])

// Filtri
const vettoreFilter = ref()
const numeroDdtFilter = ref('')
const provinciaFilter = ref('')
const regioneFilter = ref('')
const conCostoFilter = ref()
const dataDaFilter = ref()
const dataAFilter = ref()
const vettori = ref<string[]>([])

// Stats
const stats = ref<any>({})

// Dialog dettaglio
const detailDialog = ref(false)
const detailItem = ref<any>(null)

const opzioniCosto = [
  { title: 'Con costo', value: 'si' },
  { title: 'Senza costo', value: 'no' },
]

const headers = [
  { title: 'N° DDT', key: 'numero_ddt' },
  { title: 'Data', key: 'data_ddt' },
  { title: 'Vettore', key: 'vettore' },
  { title: 'Destinazione', key: 'destinazione_nome', sortable: false },
  { title: 'Prov.', key: 'destinazione_provincia' },
  { title: 'Regione', key: 'destinazione_regione' },
  { title: 'Colli', key: 'n_colli' },
  { title: 'Peso (kg)', key: 'peso_lordo_kg' },
  { title: 'Costo', key: 'costo_spedizione' },
  { title: 'Calcolo', key: 'costo_tipo_calcolo', sortable: false },
  { title: '', key: 'actions', sortable: false },
]

const tipoCalcoloColor = (tipo: string | null) => {
  if (tipo === 'pallet') return 'primary'
  if (tipo === 'peso') return 'info'

  return 'secondary'
}

const formatEuro = (value: any) => {
  if (value === null || value === undefined || value === '')
    return '-'

  return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(Number(value))
}

const formatNumero = (value: any) => {
  if (value === null || value === undefined || value === '')
    return '-'

  return Number(value).toLocaleString('it-IT', { maximumFractionDigits: 2 })
}

const updateOptions = (options: any) => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
  page.value = options.page
  itemsPerPage.value = options.itemsPerPage

  // eslint-disable-next-line @typescript-eslint/no-use-before-define
  loadItems()
}

const loadItems = async () => {
  loading.value = true

  const { data: resultData } = await useApi<any>(createUrl('/sp/ddt/', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      vettore: vettoreFilter.value,
      numero_ddt: numeroDdtFilter.value,
      provincia: provinciaFilter.value,
      regione: regioneFilter.value,
      con_costo: conCostoFilter.value,
      data_da: dataDaFilter.value,
      data_a: dataAFilter.value,
    },
  }))

  serverItems.value = resultData.value?.data ?? []
  totalItems.value = resultData.value?.total ?? 0
  loading.value = false
}

const loadVettori = async () => {
  const { data: resultData } = await useApi<any>('/sp/ddt/vettori')

  vettori.value = resultData.value ?? []
}

const loadStats = async () => {
  const { data: resultData } = await useApi<any>(createUrl('/sp/ddt/stats', {
    query: {
      vettore: vettoreFilter.value,
      numero_ddt: numeroDdtFilter.value,
      provincia: provinciaFilter.value,
      regione: regioneFilter.value,
      con_costo: conCostoFilter.value,
      data_da: dataDaFilter.value,
      data_a: dataAFilter.value,
    },
  }))

  stats.value = resultData.value?.stats ?? {}
}

const onFiltroChange = () => {
  page.value = 1
  loadItems()
  loadStats()
}

const showDetail = (item: any) => {
  detailItem.value = item
  detailDialog.value = true
}

const dettaglioKeys = computed(() => {
  if (!detailItem.value?.costo_dettaglio)
    return []

  const det = detailItem.value.costo_dettaglio
  const labels: Record<string, string> = {
    listino: 'Listino',
    listino_anno: 'Anno listino',
    provincia: 'Provincia',
    comune: 'Comune',
    regione: 'Regione',
    hub: 'Hub',
    servizio: 'Servizio',
    fascia: 'Fascia',
    prezzo_unitario: 'Prezzo unitario',
    colli: 'Colli',
    peso_lordo_kg: 'Peso lordo (kg)',
    peso_tassato_kg: 'Peso tassato (kg)',
    peso_per_collo: 'Peso per collo (kg)',
    peso_da: 'Peso da (kg)',
    peso_a: 'Peso a (kg)',
    prezzo_quintale: 'Prezzo / 100kg',
    quota_peso: 'Quota peso',
    quota_inoltro: 'Quota inoltro',
    inoltro_escluso: 'Inoltro escluso',
    costo_totale: 'Costo totale',
  }

  return Object.keys(det).map(key => ({
    key,
    label: labels[key] ?? key,
    value: det[key],
  }))
})

const costiUnitari = computed(() => {
  const item = detailItem.value

  if (!item || item.costo_spedizione === null || item.costo_spedizione === undefined)
    return []

  const costo = Number(item.costo_spedizione)
  const det = item.costo_dettaglio ?? {}
  const out: { label: string; value: string }[] = []

  // Costo per collo e per kg della spedizione
  if (item.n_colli)
    out.push({ label: 'Per collo', value: formatEuro(costo / Number(item.n_colli)) })
  if (item.peso_lordo_kg)
    out.push({ label: 'Per kg', value: formatEuro(costo / Number(item.peso_lordo_kg)) })

  // Tariffe unitarie del listino usato
  if (det.prezzo_unitario !== undefined && det.prezzo_unitario !== null)
    out.push({ label: 'Tariffa unitaria listino', value: formatEuro(det.prezzo_unitario) })
  if (det.prezzo_quintale !== undefined && det.prezzo_quintale !== null)
    out.push({ label: 'Tariffa /100kg listino', value: formatEuro(det.prezzo_quintale) })

  return out
})

loadVettori()
loadStats()
</script>

<template>
  <div class="workspace-container w-100 d-flex flex-column pa-4 gap-3">
    <VCard
      variant="outlined"
      class="bg-surface border-thin rounded-lg"
    >
      <!-- Header -->
      <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
        <div class="d-flex align-center gap-2">
          <VIcon
            icon="tabler-file-invoice"
            size="24"
            color="primary"
          />
          <div>
            <div class="text-h6 font-weight-medium">
              DDT Spedizioni
            </div>
            <div class="text-caption text-medium-emphasis">
              {{ totalItems }} DDT registrati
            </div>
          </div>
        </div>
        <div class="d-flex align-center gap-2">
          <VChip
            color="success"
            variant="tonal"
            size="small"
          >
            Con costo: {{ stats.con_costo ?? 0 }}
          </VChip>
          <VChip
            color="warning"
            variant="tonal"
            size="small"
          >
            Senza costo: {{ stats.senza_costo ?? 0 }}
          </VChip>
          <VChip
            color="primary"
            variant="tonal"
            size="small"
          >
            Totale: {{ formatEuro(stats.costo_totale) }}
          </VChip>
        </div>
      </VCardText>
      <VDivider />

      <!-- Filtri -->
      <VCardText class="pa-3">
        <VRow class="mb-2">
          <!-- 👉 N° DDT -->
          <VCol
            cols="12"
            sm="2"
          >
            <AppTextField
              v-model="numeroDdtFilter"
              label="N° DDT"
              placeholder="Cerca DDT"
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-search"
              @keyup.enter="onFiltroChange"
              @click:clear="onFiltroChange"
            />
          </VCol>

          <!-- 👉 Vettore -->
          <VCol
            cols="12"
            sm="3"
          >
            <AppSelect
              v-model="vettoreFilter"
              :items="vettori"
              label="Vettore"
              placeholder="Tutti"
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-filter"
              @update:model-value="onFiltroChange"
              @click:clear="onFiltroChange"
            />
          </VCol>

          <!-- 👉 Provincia -->
          <VCol
            cols="12"
            sm="1"
          >
            <AppTextField
              v-model="provinciaFilter"
              label="Prov."
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-search"
              @keyup.enter="onFiltroChange"
              @click:clear="onFiltroChange"
            />
          </VCol>

          <!-- 👉 Regione -->
          <VCol
            cols="12"
            sm="3"
          >
            <AppTextField
              v-model="regioneFilter"
              label="Regione"
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-search"
              @keyup.enter="onFiltroChange"
              @click:clear="onFiltroChange"
            />
          </VCol>

          <!-- 👉 Costo -->
          <VCol
            cols="12"
            sm="3"
          >
            <AppSelect
              v-model="conCostoFilter"
              :items="opzioniCosto"
              label="Costo"
              placeholder="Tutti"
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-filter"
              @update:model-value="onFiltroChange"
              @click:clear="onFiltroChange"
            />
          </VCol>
        </VRow>
        <VRow>
          <!-- 👉 Data da -->
          <VCol
            cols="12"
            sm="2"
          >
            <AppTextField
              v-model="dataDaFilter"
              type="date"
              label="Data da"
              prepend-inner-icon="tabler-calendar"
              @update:model-value="onFiltroChange"
            />
          </VCol>

          <!-- 👉 Data a -->
          <VCol
            cols="12"
            sm="2"
          >
            <AppTextField
              v-model="dataAFilter"
              type="date"
              label="Data a"
              prepend-inner-icon="tabler-calendar"
              @update:model-value="onFiltroChange"
            />
          </VCol>
        </VRow>
      </VCardText>
      <VDivider />

      <!-- 👉 Datatable  -->
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        :headers="headers"
        :items="serverItems"
        :items-length="totalItems"
        :loading="loading"
        density="comfortable"
        hover
        @update:options="updateOptions"
      >
        <template #no-data>
          <div class="py-10 text-center">
            <VIcon
              icon="tabler-file-invoice"
              size="40"
              class="text-disabled mb-2"
            />
            <p class="text-body-1 text-disabled mb-0">
              Nessun DDT trovato
            </p>
          </div>
        </template>

        <template #item.numero_ddt="{ item }">
          <span class="font-weight-medium">{{ item.numero_ddt || '-' }}</span>
        </template>

        <template #item.data_ddt="{ item }">
          {{ item.data_ddt ? formatDate(item.data_ddt) : '-' }}
        </template>

        <template #item.vettore="{ item }">
          <span class="text-no-wrap">{{ item.vettore || '-' }}</span>
        </template>

        <template #item.destinazione_nome="{ item }">
          <div class="d-flex flex-column">
            <span class="text-body-2">{{ item.destinazione_nome || '-' }}</span>
            <small class="text-disabled">{{ item.destinazione_indirizzo }}</small>
          </div>
        </template>

        <template #item.destinazione_provincia="{ item }">
          <VChip
            v-if="item.destinazione_provincia"
            size="small"
            variant="tonal"
          >
            {{ item.destinazione_provincia }}
          </VChip>
          <span v-else>-</span>
        </template>

        <template #item.destinazione_regione="{ item }">
          {{ item.destinazione_regione || '-' }}
        </template>

        <template #item.n_colli="{ item }">
          {{ item.n_colli ?? '-' }}
        </template>

        <template #item.peso_lordo_kg="{ item }">
          {{ formatNumero(item.peso_lordo_kg) }}
        </template>

        <template #item.costo_spedizione="{ item }">
          <span
            v-if="item.costo_spedizione !== null"
            class="font-weight-bold text-success"
          >
            {{ formatEuro(item.costo_spedizione) }}
          </span>
          <VTooltip v-else>
            <template #activator="{ props }">
              <span
                v-bind="props"
                class="text-warning"
              >
                -
              </span>
            </template>
            <span>{{ item.costo_note || 'Costo non calcolato' }}</span>
          </VTooltip>
        </template>

        <template #item.costo_tipo_calcolo="{ item }">
          <VChip
            v-if="item.costo_tipo_calcolo"
            size="small"
            :color="tipoCalcoloColor(item.costo_tipo_calcolo)"
            variant="tonal"
          >
            {{ item.costo_tipo_calcolo }}
          </VChip>
          <span v-else>-</span>
        </template>

        <template #item.actions="{ item }">
          <IconBtn
            v-if="item.costo_dettaglio || item.costo_note"
            size="small"
            color="primary"
            @click="showDetail(item)"
          >
            <VIcon
              icon="tabler-info-circle"
              size="18"
            />
          </IconBtn>
        </template>
      </VDataTableServer>
    </VCard>

    <!-- Dialog dettaglio costo -->
    <VDialog
      v-model="detailDialog"
      max-width="720px"
    >
      <VCard
        v-if="detailItem"
        variant="outlined"
        class="bg-surface border-thin rounded-lg"
      >
        <!-- Header -->
        <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
          <div class="d-flex align-center gap-2">
            <VIcon
              icon="tabler-receipt"
              size="24"
              color="primary"
            />
            <div>
              <div class="text-h6 font-weight-medium">
                Dettaglio costo
              </div>
              <div class="text-caption text-medium-emphasis">
                DDT {{ detailItem.numero_ddt }} · {{ detailItem.data_ddt ? formatDate(detailItem.data_ddt) : '-' }}
              </div>
            </div>
          </div>
          <div class="d-flex align-center gap-2">
            <VChip
              v-if="detailItem.costo_spedizione !== null && detailItem.costo_spedizione !== undefined"
              color="success"
              variant="tonal"
            >
              {{ formatEuro(detailItem.costo_spedizione) }}
            </VChip>
            <VChip
              v-if="detailItem.costo_tipo_calcolo"
              size="small"
              :color="tipoCalcoloColor(detailItem.costo_tipo_calcolo)"
              variant="tonal"
            >
              {{ detailItem.costo_tipo_calcolo }}
            </VChip>
            <IconBtn
              size="small"
              @click="detailDialog = false"
            >
              <VIcon
                icon="tabler-x"
                size="18"
              />
            </IconBtn>
          </div>
        </VCardText>
        <VDivider />

        <VCardText class="pa-3">
          <!-- Dati spedizione -->
          <VRow class="mb-1">
            <VCol cols="6">
              <div class="text-caption text-medium-emphasis">
                Vettore
              </div>
              <div class="text-body-2 font-weight-medium">
                {{ detailItem.vettore || '-' }}
              </div>
            </VCol>
            <VCol cols="6">
              <div class="text-caption text-medium-emphasis">
                Destinazione
              </div>
              <div class="text-body-2 font-weight-medium">
                {{ detailItem.destinazione_nome || '-' }}
              </div>
              <div class="text-caption text-disabled">
                {{ detailItem.destinazione_indirizzo }}
              </div>
            </VCol>
            <VCol cols="6">
              <div class="text-caption text-medium-emphasis">
                Provincia / Regione
              </div>
              <div class="text-body-2 font-weight-medium">
                {{ detailItem.destinazione_provincia || '-' }} / {{ detailItem.destinazione_regione || '-' }}
              </div>
            </VCol>
            <VCol cols="6">
              <div class="text-caption text-medium-emphasis">
                Colli / Peso lordo
              </div>
              <div class="text-body-2 font-weight-medium">
                {{ detailItem.n_colli ?? '-' }} · {{ formatNumero(detailItem.peso_lordo_kg) }} kg
              </div>
            </VCol>
          </VRow>

          <!-- Costi unitari -->
          <template v-if="costiUnitari.length">
            <VDivider class="my-3" />
            <div class="mb-3">
              <div class="text-caption text-medium-emphasis mb-2 text-uppercase">
                Costi unitari
              </div>
              <div class="d-flex flex-wrap gap-2">
                <VChip
                  v-for="u in costiUnitari"
                  :key="u.label"
                  variant="outlined"
                  color="primary"
                  size="small"
                >
                  {{ u.label }}: <strong class="ms-1">{{ u.value }}</strong>
                </VChip>
              </div>
            </div>
          </template>

          <!-- Nota calcolo -->
          <template v-if="detailItem.costo_note">
            <VDivider class="my-3" />
            <div class="mb-3">
              <div class="text-caption text-medium-emphasis mb-2 text-uppercase">
                Nota
              </div>
              <p class="text-body-2 mb-0">
                {{ detailItem.costo_note }}
              </p>
            </div>
          </template>

          <!-- Dettaglio calcolo -->
          <template v-if="dettaglioKeys.length">
            <VDivider class="my-3" />
            <div class="text-caption text-medium-emphasis mb-2 text-uppercase">
              Dettaglio calcolo
            </div>
            <VTable density="compact">
              <tbody>
                <tr
                  v-for="row in dettaglioKeys"
                  :key="row.key"
                >
                  <td class="text-medium-emphasis">
                    {{ row.label }}
                  </td>
                  <td class="text-end">
                    <template v-if="['prezzo_unitario', 'prezzo_quintale', 'quota_peso', 'quota_inoltro', 'costo_totale'].includes(row.key)">
                      {{ formatEuro(row.value) }}
                    </template>
                    <template v-else>
                      {{ row.value }}
                    </template>
                  </td>
                </tr>
              </tbody>
            </VTable>
          </template>
        </VCardText>
      </VCard>
    </VDialog>
  </div>
</template>
