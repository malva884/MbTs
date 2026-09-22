<script setup lang="ts">
import { VDataTable, VDataTableServer } from 'vuetify/labs/VDataTable'
import { useI18n } from 'vue-i18n'

definePage({
  meta: {
    action: 'read',
    subject: 'Finanze-Spedito',
  },
})

// eslint-disable-next-line @typescript-eslint/no-unused-vars
const route = useRoute('finance-viaggio-view-id')

const { t } = useI18n()
const itemsPerPage = ref(10)
const loading = ref(true)
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const materialeFilter = ref('')
const clientiFilter = ref([])
const dataFilter = ref('')
const tipologiaCavoFilter = ref('')
const page = ref(1)
const serverItems = ref<any>([])
const isSnackbarScrollReverseVisible = ref(false)
const message = ref('')
const color = ref('')
const selectedHeaders = ref<string[]>([])
const clientiOptions = ref([])
const reportData = ref<any>({ ottico: null, rame: null, clienti: [] })
const detailDialog = ref(false)
const detailLoading = ref(false)
const detailItems = ref<any[]>([])
const detailClient = ref<any>(null)
const detailMaterialeFilter = ref('')
const currentTab = ref(0)

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

  // eslint-disable-next-line no-template-curly-in-string
  const { data: resultData, error } = await useApi<any>(createUrl(`/fi/goods_transit/rows/list/${route.params.id}`, {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      materiale: materialeFilter.value,
      clienti: JSON.stringify(clientiFilter.value),
      data: dataFilter.value,
      lavorazione: tipologiaCavoFilter.value,
    },
  }))

  if (resultData.value !== null) {
    serverItems.value = resultData.value.data
    totalItems.value = resultData.value.total
  }
  else {
    serverItems.value = []
    totalItems.value = 0
  }
  loading.value = false

  // eslint-disable-next-line @typescript-eslint/no-use-before-define
  loadReport()
}

// headers
const headers = computed(() => [
  { title: t('Table.Data'), key: 'date_row' },
  { title: t('Table.Cod-Cliente'), key: 'code_client' },
  { title: t('Table.Cliente'), key: 'client' },
  { title: t('Table.Item'), key: 'item' },
  { title: t('Table.Materiale'), key: 'material' },
  { title: t('Table.Descrizione'), key: 'description' },
  { title: t('Table.Tipo-Cavo'), key: 'type' },
  { title: t('Table.Commessa'), key: 'commessa' },
  { title: t('Table.Codice-Cliente'), key: 'code_recipient' },
  { title: t('Table.Cliente'), key: 'recipient' },
  { title: t('Table.Unit'), key: 'unit' },
  { title: t('Table.Totale-Spedito'), key: 'qty_value' },
  { title: t('Table.Valore-Costo'), key: 'cost_value' },
  { title: t('Table.Fibre'), key: 'fiber_counter' },
  { title: t('Table.Quantita-Spedito'), key: 'delivered_qty' },
  { title: t('Table.Quantita-Fkm'), key: 'qty_fkm' },
  { title: t('Table.Prezzo-Km'), key: 'price_km' },
  { title: t('Table.Costo-Al-Km'), key: 'cost_km' },
  { title: t('Table.Prezzo-Standard'), key: 'std_price' },
  { title: t('Table.Ordine'), key: 'order' },
  { title: t('Table.Profitto-Netto'), key: 'net_profit' },
  { title: t('Table.Profitto-Percentuale'), key: 'profit_perc' },
  { title: t('Table.Cambio'), key: 'exchange_rate' },
  { title: t('Table.Cap'), key: 'postal_code' },
  { title: t('Table.Citta'), key: 'city' },
  { title: t('Table.Docuemnto'), key: 'document' },
  { title: t('Table.Distanza'), key: 'km_distance' },
])

selectedHeaders.value = headers.value.map(header => header.key)

const visibleHeaders = computed(() =>
  headers.value.filter(header => selectedHeaders.value.includes(header.key)),
)

const clienti = async () => {
  const { data: clientiResult } = await useApi<any>(createUrl('/fi/get_clienti'))
  const arr = []

  clientiResult.value.forEach(value => {
    arr.push({ val: value.cliente, id: value.codice_cliente })
  })
  clientiOptions.value = arr
}

clienti()

const loadReport = async () => {
  const { data: reportResult } = await useApi<any>(createUrl(`/fi/goods_transit/rows/report/${route.params.id}`, {
    query: {
      materiale: materialeFilter.value,
      clienti: JSON.stringify(clientiFilter.value),
      data: dataFilter.value,
      lavorazione: tipologiaCavoFilter.value,
    },
  }))

  if (reportResult.value !== null)
    reportData.value = reportResult.value
}

loadReport()

const loadDetailItems = async () => {
  detailLoading.value = true
  detailItems.value = []

  const { data: detailResult } = await useApi<any>(createUrl(`/fi/goods_transit/rows/list/${route.params.id}`, {
    query: {
      page: 1,
      itemsPerPage: 1000,
      clienti: JSON.stringify([detailClient.value.code_client]),
      materiale: detailMaterialeFilter.value || materialeFilter.value,
      data: dataFilter.value,
      lavorazione: tipologiaCavoFilter.value,
    },
  }))

  if (detailResult.value !== null)
    detailItems.value = detailResult.value.data

  detailLoading.value = false
}

const openClientDetail = (cliente: any) => {
  detailClient.value = cliente
  detailMaterialeFilter.value = ''
  detailDialog.value = true
  loadDetailItems()
}

const clientiOttico = computed(() =>
  (reportData.value.clienti ?? []).filter((c: any) => Number(c.ottico) > 0),
)

const clientiRame = computed(() =>
  (reportData.value.clienti ?? []).filter((c: any) => Number(c.rame) > 0),
)

const clientiOtticoHeaders = computed(() => [
  { title: t('Table.Cliente'), key: 'client' },
  { title: t('Table.Ottico'), key: 'ottico' },
])

const clientiRameHeaders = computed(() => [
  { title: t('Table.Cliente'), key: 'client' },
  { title: t('Table.Rame'), key: 'rame' },
])

const detailTotal = computed(() =>
  detailItems.value.reduce((sum: number, item: any) => sum + Number(item.qty_value || 0), 0),
)

const detailHeaders = computed(() => [
  { title: t('Table.Data'), key: 'date_row' },
  { title: t('Table.Materiale'), key: 'material' },
  { title: t('Table.Descrizione'), key: 'description' },
  { title: t('Table.Unit'), key: 'unit' },
  { title: t('Table.Totale-Spedito'), key: 'qty_value' },
  { title: t('Table.Quantita-Spedito'), key: 'delivered_qty' },
  { title: t('Table.Distanza'), key: 'km_distance' },
])

const euro = new Intl.NumberFormat('it-IT', {
  style: 'currency',
  currency: 'EUR',
})

const numberFormat = new Intl.NumberFormat('it-IT', {
  maximumFractionDigits: 3,
})

const exportUrl = computed(() => {
  const params = new URLSearchParams()

  if (materialeFilter.value)
    params.append('materiale', materialeFilter.value)
  if (dataFilter.value)
    params.append('data', dataFilter.value)
  if (tipologiaCavoFilter.value)
    params.append('lavorazione', tipologiaCavoFilter.value)
  if (clientiFilter.value.length)
    params.append('clienti', JSON.stringify(clientiFilter.value))

  return `/api/export/goods_transit/excel/${route.params.id}?${params.toString()}`
})
</script>

<template>
  <div class="workspace-container w-100 d-flex flex-column pa-4 gap-3">
    <VSnackbar
      v-model="isSnackbarScrollReverseVisible"
      transition="scroll-y-reverse-transition"
      location="top center"
      :timeout="3000"
      :color="color"
    >
      {{ $t(message) }}
    </VSnackbar>

    <VTabs
      v-model="currentTab"
      class="v-tabs-pill"
    >
      <VTab>
        <VIcon
          icon="tabler-chart-pie"
          size="18"
          class="me-2"
        />
        <span class="font-weight-medium">{{ $t('Label.Riepilogo') }}</span>
      </VTab>
      <VTab>
        <VIcon
          icon="tabler-truck-delivery"
          size="18"
          class="me-2"
        />
        <span class="font-weight-medium">{{ $t('Merce In Viaggio') }}</span>
      </VTab>
    </VTabs>

    <VWindow
      v-model="currentTab"
      :touch="false"
    >
      <VWindowItem>
    <!-- 👉 Riepilogo -->
    <VCard
      variant="outlined"
      class="bg-surface border-thin rounded-lg"
    >
      <VCardText class="d-flex align-center py-3 gap-2">
        <VIcon
          icon="tabler-chart-pie"
          size="24"
          color="primary"
        />
        <div class="text-h6 font-weight-medium">
          {{ $t('Label.Riepilogo') }}
        </div>
      </VCardText>
      <VDivider />
      <VCardText class="pa-3">
        <VRow>
          <!-- 👉 Ottico -->
          <VCol
            cols="12"
            sm="6"
          >
            <div class="d-flex align-center gap-3">
              <VAvatar
                color="warning"
                variant="tonal"
                size="42"
              >
                <VIcon
                  icon="tabler-circle"
                  size="22"
                />
              </VAvatar>
              <div>
                <div class="text-caption text-medium-emphasis">
                  {{ $t('Table.Ottico') }}
                </div>
                <div class="text-h6 font-weight-medium">
                  {{ euro.format(reportData.ottico?.totale ?? 0) }}
                </div>
                <div class="text-caption text-medium-emphasis">
                  {{ numberFormat.format(reportData.ottico?.fkm ?? 0) }} Fkm · {{ numberFormat.format(reportData.ottico?.ckm ?? 0) }} Ckm
                </div>
              </div>
            </div>
          </VCol>

          <!-- 👉 Rame -->
          <VCol
            cols="12"
            sm="6"
          >
            <div class="d-flex align-center gap-3">
              <VAvatar
                color="success"
                variant="tonal"
                size="42"
              >
                <VIcon
                  icon="tabler-circle"
                  size="22"
                />
              </VAvatar>
              <div>
                <div class="text-caption text-medium-emphasis">
                  {{ $t('Table.Rame') }}
                </div>
                <div class="text-h6 font-weight-medium">
                  {{ euro.format(reportData.rame?.totale ?? 0) }}
                </div>
                <div class="text-caption text-medium-emphasis">
                  {{ numberFormat.format(reportData.rame?.ckm ?? 0) }} Ckm
                </div>
              </div>
            </div>
          </VCol>
        </VRow>
      </VCardText>
      <VDivider />
      <VCardText class="pa-3">
        <div class="text-caption text-medium-emphasis mb-2">
          {{ $t('Label.Totale-Per-Cliente') }}
        </div>
        <VRow>
          <!-- 👉 Clienti Ottico -->
          <VCol
            cols="12"
            sm="6"
          >
            <div class="d-flex align-center gap-2 mb-2">
              <VAvatar
                color="warning"
                variant="tonal"
                size="28"
              >
                <VIcon
                  icon="tabler-circle"
                  size="16"
                />
              </VAvatar>
              <div class="text-body-2 font-weight-medium">
                {{ $t('Table.Ottico') }}
              </div>
            </div>
            <VDataTable
              :headers="clientiOtticoHeaders"
              :items="clientiOttico"
              density="comfortable"
              hover
              items-per-page="30"
              @click:row="(event: any, row: any) => openClientDetail(row.item)"
            >
              <template #item.ottico="{ item }">
                <span class="font-weight-medium">{{ euro.format(item.ottico) }}</span>
              </template>
              <template #no-data>
                <div class="py-6 text-center text-disabled">
                  {{ $t('Label.Nessuna-Riga-Trovata') }}
                </div>
              </template>
            </VDataTable>
          </VCol>

          <!-- 👉 Clienti Rame -->
          <VCol
            cols="12"
            sm="6"
          >
            <div class="d-flex align-center gap-2 mb-2">
              <VAvatar
                color="success"
                variant="tonal"
                size="28"
              >
                <VIcon
                  icon="tabler-circle"
                  size="16"
                />
              </VAvatar>
              <div class="text-body-2 font-weight-medium">
                {{ $t('Table.Rame') }}
              </div>
            </div>
            <VDataTable
              :headers="clientiRameHeaders"
              :items="clientiRame"
              density="comfortable"
              hover
              items-per-page="30"
              @click:row="(event: any, row: any) => openClientDetail(row.item)"
            >
              <template #item.rame="{ item }">
                <span class="font-weight-medium">{{ euro.format(item.rame) }}</span>
              </template>
              <template #no-data>
                <div class="py-6 text-center text-disabled">
                  {{ $t('Label.Nessuna-Riga-Trovata') }}
                </div>
              </template>
            </VDataTable>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>
      </VWindowItem>

      <VWindowItem>
    <VCard
      variant="outlined"
      class="bg-surface border-thin rounded-lg"
    >
      <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
        <div class="d-flex align-center gap-2">
          <VIcon
            icon="tabler-truck-delivery"
            size="24"
            color="primary"
          />
          <div>
            <div class="text-h6 font-weight-medium">
              {{ $t('Merce In Viaggio') }}
            </div>
            <div class="text-caption text-medium-emphasis">
              {{ totalItems }} {{ $t('Label.Righe-Trovate') }}
            </div>
          </div>
        </div>
        <div class="d-flex align-center gap-2">
          <VBtn
            prepend-icon="tabler-screen-share"
            color="secondary"
            variant="tonal"
            density="comfortable"
            class="px-3"
            :href="exportUrl"
          >
            {{ $t('Label.Esporta') }}
          </VBtn>
          <VBtn
            prepend-icon="tabler-arrow-left"
            color="secondary"
            variant="outlined"
            density="comfortable"
            class="px-3"
            :to="{ name: 'finance-viaggio-list' }"
          >
            {{ $t('Label.Torna-Alla-Lista') }}
          </VBtn>
        </div>
      </VCardText>
      <VDivider />
      <VCardText class="pa-3">
        <VRow class="mb-2">
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
              prepend-inner-icon="tabler-search"
              @keyup.enter="loadItems"
              @click:clear="loadItems"
            />
          </VCol>

          <!-- 👉 Clienti -->
          <VCol
            cols="12"
            sm="3"
          >
            <AppCombobox
              v-model="clientiFilter"
              :label="$t('Label.Clienti')"
              :placeholder="$t('Label.Tutti')"
              :items="clientiOptions"
              item-title="val"
              item-value="id"
              chips
              multiple
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-filter"
              @update:model-value="loadItems"
              @click:clear="loadItems"
            />
          </VCol>

          <!-- 👉 Tipologia Cavo -->
          <VCol
            cols="12"
            sm="3"
          >
            <AppSelect
              v-model="tipologiaCavoFilter"
              :label="$t('Label.Tipologia-Cavo')"
              :placeholder="$t('Label.Tutti')"
              :items="[{ title: 'Rame', value: 5441 }, { title: 'Ottico', value: 5420 }]"
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-filter"
              @update:model-value="loadItems"
              @click:clear="loadItems"
            />
          </VCol>

          <!-- 👉 Data -->
          <VCol
            cols="12"
            sm="3"
          >
            <AppDateTimePicker
              v-model="dataFilter"
              :label="$t('Label.Data')"
              :placeholder="$t('Label.Data')"
              :config="{ mode: 'range' }"
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-calendar"
              @update:model-value="loadItems"
              @click:clear="loadItems"
            />
          </VCol>
        </VRow>
        <VRow>
          <!-- 👉 Colonne -->
          <VCol
            cols="12"
            sm="6"
          >
            <AppSelect
              v-model="selectedHeaders"
              :label="$t('Label.Colonne')"
              :items="headers"
              item-title="title"
              item-value="key"
              chips
              multiple
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-columns"
            />
          </VCol>
        </VRow>
      </VCardText>
      <VDivider />
      <!-- 👉 Datatable  -->
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        :headers="visibleHeaders"
        :items="serverItems"
        :items-length="totalItems"
        :loading="loading"
        density="comfortable"
        hover
        height="600"
        fixed-header
        @update:options="updateOptions"
      >
        <template #no-data>
          <div class="py-10 text-center">
            <VIcon
              icon="tabler-truck-delivery"
              size="40"
              class="text-disabled mb-2"
            />
            <p class="text-body-1 text-disabled mb-0">
              {{ $t('Label.Nessuna-Riga-Trovata') }}
            </p>
          </div>
        </template>

        <template #item.net_profit="{ item }">
          <p
            v-if="item.profit_perc > 0.00"
            class="text-success mb-0"
          >
            {{ euro.format(item.net_profit) }}
          </p>
          <p
            v-else
            class="text-warning mb-0"
          >
            {{ euro.format(item.net_profit) }}
          </p>
        </template>

        <template #item.profit_perc="{ item }">
          <p
            v-if="item.profit_perc > 0.00"
            class="text-success mb-0"
          >
            {{ item.profit_perc }} %
          </p>
          <p
            v-else
            class="text-warning mb-0"
          >
            {{ item.profit_perc }} %
          </p>
        </template>

        <template #item.km_distance="{ item }">
          {{ item.km_distance }} Km
        </template>
      </VDataTableServer>
    </VCard>
      </VWindowItem>
    </VWindow>

    <!-- 👉 Client Detail Dialog -->
    <VDialog
      v-model="detailDialog"
      max-width="1800px"
    >
      <VCard
        variant="outlined"
        class="bg-surface border-thin rounded-lg"
      >
        <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
          <div class="d-flex align-center gap-2">
            <VAvatar
              color="primary"
              variant="tonal"
              size="38"
            >
              <VIcon
                icon="tabler-user"
                size="20"
              />
            </VAvatar>
            <div>
              <div class="text-h6 font-weight-medium">
                {{ detailClient?.client }}
              </div>
              <div class="text-caption text-medium-emphasis">
                {{ detailClient?.code_client }} · {{ euro.format(detailClient?.totale ?? 0) }}
              </div>
            </div>
          </div>
          <DialogCloseBtn @click="detailDialog = false" />
        </VCardText>
        <VDivider />
        <VCardText class="pa-3 pb-0">
          <VRow>
            <!-- 👉 Materiale -->
            <VCol
              cols="12"
              sm="4"
            >
              <AppTextField
                v-model="detailMaterialeFilter"
                :label="$t('Label.Materiale')"
                :placeholder="$t('Label.Materiale')"
                clearable
                clear-icon="tabler-x"
                prepend-inner-icon="tabler-search"
                @keyup.enter="loadDetailItems"
                @click:clear="loadDetailItems"
              />
            </VCol>
            <VCol
              cols="12"
              sm="8"
              class="d-flex align-center justify-end"
            >
              <div class="text-body-2 text-medium-emphasis me-2">
                {{ $t('Table.Totale') }}:
              </div>
              <div class="text-h6 font-weight-medium">
                {{ euro.format(detailTotal) }}
              </div>
            </VCol>
          </VRow>
        </VCardText>
        <VCardText class="pa-0">
          <VDataTable
            :headers="detailHeaders"
            :items="detailItems"
            :loading="detailLoading"
            density="comfortable"
            hover
            items-per-page="10"
          >
            <template #item.qty_value="{ item }">
              {{ euro.format(item.qty_value) }}
            </template>
            <template #item.delivered_qty="{ item }">
              {{ numberFormat.format(item.delivered_qty) }}
            </template>
            <template #item.km_distance="{ item }">
              {{ item.km_distance }} Km
            </template>
            <template #no-data>
              <div class="py-6 text-center text-disabled">
                {{ $t('Label.Nessuna-Riga-Trovata') }}
              </div>
            </template>
          </VDataTable>
        </VCardText>
      </VCard>
    </VDialog>
  </div>
</template>
