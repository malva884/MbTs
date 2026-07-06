<script setup lang="ts">
import { VDataTableServer, VDataTable } from 'vuetify/labs/VDataTable'
import moment from 'moment'
import { useI18n } from 'vue-i18n'

definePage({
  meta: {
    action: 'admin',
    subject: 'Produzione-Interscambio',
  },
})

const { t } = useI18n()
const itemsPerPage = ref(10)
const loading = ref(true)
const totalItems = ref(0)
const sortBy = ref('DataEsportazione')
const orderBy = ref('desc')
const olFilter = ref('')
const esportatoFilter = ref<string | null>(null)
const messaggioFilter = ref('')
const dataEsportazioneFilter = ref<string | null>(null)
const page = ref(1)
const serverItems = ref<any>([])
const isSnackbarScrollReverseVisible = ref(false)
const message = ref('')
const color = ref('')

const fabbDialog = ref(false)
const fabbLoading = ref(false)
const fabbItems = ref<any>([])
const fabbTotal = ref(0)
const fabbIdProduzione = ref('')

const updateDialog = ref(false)
const updateLoading = ref(false)
const selectedRow = ref<any>(null)
const fabbHeaders = computed(() => [
  { title: t('Label.IDProduzione'), key: 'IDProduzione' },
  { title: t('Label.Materiale'), key: 'cdProdotto' },
  { title: t('Label.Lotto'), key: 'cdLotto' },
  { title: t('Label.Quantita'), key: 'Qta' },
  { title: t('Label.Esportato'), key: 'Esportato' },
  { title: t('Label.DataEsportazione'), key: 'DataEsportazione' },
  { title: 'MSG', key: 'MSG' },
  { title: t('Label.Errore'), key: 'Errore' },
  { title: t('Label.Ordine'), key: 'Ordine' },
  { title: t('Label.Fase'), key: 'Fase' },
  { title: t('Label.CoeffImpegno'), key: 'CoeffImpegno' },
  { title: t('Label.QtaProdotta'), key: 'QtaProdotta' },
  { title: t('Label.Consumo'), key: 'Consumo' },
])

const updateOptions = (options: any) => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
  page.value = options.page
  itemsPerPage.value = options.itemsPerPage

  // eslint-disable-next-line @typescript-eslint/no-use-before-define
  loadItems()
}

const openFabbisogni = async (_event: any, row: { item: any }) => {
  fabbIdProduzione.value = row.item.IDProduzione
  fabbDialog.value = true
  fabbLoading.value = true
  fabbItems.value = []
  fabbTotal.value = 0

  const { data: resultData } = await useApi<any>(createUrl('/gp/fabbisogni', {
    query: {
      id_produzione: fabbIdProduzione.value,
      itemsPerPage: 100,
    },
  }))

  if (resultData.value !== null) {
    fabbItems.value = resultData.value.data
    fabbTotal.value = resultData.value.total
  }
  fabbLoading.value = false
}

const loadItems = async () => {
  loading.value = true

  const { data:resultData, error } = await useApi<any>(createUrl('/gp/produzione', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      ordine: olFilter.value,
      esportato: esportatoFilter.value,
      messaggio: messaggioFilter.value,
      dataEsportazione: dataEsportazioneFilter.value,
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

// headers
const headers = computed(() => [
  { title: 'ID', key: 'Id', sortable: true },
  { title: t('Label.IDProduzione'), key: 'IDProduzione', sortable: true },
  { title: t('Label.Ordine'), key: 'Ordine', sortable: true },
  { title: t('Label.Fase'), key: 'Fase', sortable: true },
  { title: t('Label.DataMov'), key: 'DataMov', sortable: true },
  { title: t('Label.Risorsa'), key: 'Risorsa', sortable: true },
  { title: t('Label.Quantita'), key: 'quantita', sortable: true },
  { title: t('Label.TempoTotale'), key: 'TempoTotale', sortable: true },
  { title: t('Label.TempoLavorato'), key: 'TempoLavorato', sortable: true },
  { title: t('Label.TempoFermi'), key: 'TempoFermi', sortable: true },
  { title: t('Label.TempoOperatore'), key: 'TempoOperatore', sortable: true },
  { title: t('Label.Operatore'), key: 'OperatorCode', sortable: true },
  { title: t('Label.UMTime'), key: 'UMTime', sortable: true },
  { title: t('Label.Esportato'), key: 'Esportato', sortable: true },
  { title: t('Label.DataEsportazione'), key: 'DataEsportazione', sortable: true },
  { title: 'MSG', key: 'MSG', sortable: true },
  { title: t('Label.Errore'), key: 'Errore', sortable: true },
  { title: 'Azioni', key: 'actions', sortable: false },
])

function formatDate(date: string): string {
  return moment(String(date)).format('MM/DD/YYYY H:m:s')
}

const formatNum = (numero: number, decimal: boolean) => {
  let num = 3
  if (!decimal) {
    numero = Math.trunc(numero)
    num = 0
  }

  return new Intl.NumberFormat('it-IT', { minimumFractionDigits: num, maximumFractionDigits: 3 }).format(numero)
}

const formatTime = (val: any) => {
  if (val === null || val === undefined) return '-'
  const num = Number(val)
  if (isNaN(num)) return val
  return new Intl.NumberFormat('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num)
}

const copyToClipboard = async (text: string) => {
  if (!text) return
  try {
    await navigator.clipboard.writeText(text)
    message.value = 'Label.Copiato'
    isSnackbarScrollReverseVisible.value = true
  }
  catch (e) {
    console.error('Failed to copy:', e)
  }
}

const openUpdateDialog = (row: any) => {
  selectedRow.value = row
  updateDialog.value = true
}

const performUpdate = async (updateType: string) => {
  updateLoading.value = true
  try {
    const resultData = await $api('/gp/produzione/update', {
      method: 'POST',
      body: {
        id: selectedRow.value.IDProduzione,
        update_type: updateType,
      },
    })

    if (resultData) {
      message.value = resultData.message || 'Update completato'
      color.value = 'success'
      isSnackbarScrollReverseVisible.value = true
      updateDialog.value = false
      loadItems()
    }
  }
  catch (e: any) {
    console.error('Errore update:', e)
    message.value = e.data?.message || 'Errore durante update'
    color.value = 'error'
    isSnackbarScrollReverseVisible.value = true
  }
  finally {
    updateLoading.value = false
  }
}

</script>

<template>
  <div class="workspace-container w-100 d-flex flex-column pa-4 gap-3">
    <VSnackbar v-model="isSnackbarScrollReverseVisible" transition="scroll-y-reverse-transition" location="top center" :timeout="3000">
      {{ $t(message) }}
    </VSnackbar>

    <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
      <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
        <div class="d-flex align-center gap-2">
          <VIcon icon="tabler-building-factory-2" size="24" color="primary" />
          <div>
            <div class="text-h6 font-weight-medium">{{ $t('Label.Lista-Produzione') }}</div>
            <div class="text-caption text-medium-emphasis">{{ totalItems }} record</div>
          </div>
        </div>
      </VCardText>
      <VDivider />
      <VCardText class="pa-3">
        <VRow class="mb-2">
          <!-- 👉 Ordine -->
          <VCol cols="12" sm="3">
            <AppTextField
              v-model="olFilter"
              :label="$t('Label.Numero Ordine')"
              :placeholder="$t('Label.Numero Ordine')"
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-search"
              @keyup.enter="loadItems"
              @click:clear="loadItems"
            />
          </VCol>

          <!-- 👉 Esportato -->
          <VCol cols="12" sm="3">
            <AppSelect
              v-model="esportatoFilter"
              :label="$t('Label.Esportato')"
              :placeholder="$t('Label.Esportato')"
              clearable
              :items="[{ title: 'Tutti', value: null }, { title: 'Sì', value: '1' }, { title: 'No', value: '0' }]"
              @update:model-value="loadItems"
            />
          </VCol>

          <!-- 👉 Messaggio -->
          <VCol cols="12" sm="3">
            <AppTextField
              v-model="messaggioFilter"
              label="Messaggio"
              placeholder="Messaggio"
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-search"
              @keyup.enter="loadItems"
              @click:clear="loadItems"
            />
          </VCol>

          <!-- 👉 Data Esportazione -->
          <VCol cols="12" sm="3">
            <AppDateTimePicker
              v-model="dataEsportazioneFilter"
              :label="$t('Label.DataEsportazione')"
              :placeholder="$t('Label.DataEsportazione')"
              clearable
              config-mode="date"
              @update:model-value="loadItems"
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
        :items-per-page-options="[10, 25, 50, 100]"
        density="comfortable"
        hover
        fixed-header
        height="60vh"
        @update:options="updateOptions"
        @click:row="openFabbisogni"
      >
        <template #no-data>
          <div class="py-10 text-center">
            <VIcon icon="tabler-database-off" size="40" class="text-disabled mb-2" />
            <p class="text-body-1 text-disabled mb-0">Nessun record trovato</p>
          </div>
        </template>

        <!-- Quantità -->
        <template #item.quantita="{ item }">
          <div v-if="item.UM == 'km' && item.quantita > 100" class="d-flex gap-1 text-warning" >
            {{ formatNum(item.quantita, true) }}
          </div>
          <div v-else class="d-flex gap-1 text-success" >
            {{ formatNum(item.quantita, true) }}
          </div>
        </template>

        <!-- Numero Fibre -->
        <template #item.Conversione12="{ item }">
          <div class="d-flex gap-1">
            {{ formatNum(item.Conversione12,false) }}
          </div>
        </template>

        <!-- date -->
        <template #item.DataOraInizio="{ item }">
          <div class="d-flex gap-1">
            {{ formatDate(item.DataOraInizio) }}
          </div>
        </template>

        <!-- date -->
        <template #item.DataOraFine="{ item }">
          <div class="d-flex gap-1">
            {{ formatDate(item.DataOraFine) }}
          </div>
        </template>

        <!-- TempoTotale -->
        <template #item.TempoTotale="{ item }">
          {{ formatTime(item.TempoTotale) }}
        </template>

        <!-- TempoLavorato -->
        <template #item.TempoLavorato="{ item }">
          {{ formatTime(item.TempoLavorato) }}
        </template>

        <!-- TempoFermi -->
        <template #item.TempoFermi="{ item }">
          {{ formatTime(item.TempoFermi) }}
        </template>

        <!-- TempoOperatore -->
        <template #item.TempoOperatore="{ item }">
          {{ formatTime(item.TempoOperatore) }}
        </template>

        <!-- descrizione -->
        <template #item.DescrizioneProdotto="{ item }">
          <div class="d-flex gap-1" >
            {{ item.DescrizioneProdotto }}
          </div>
        </template>

        <!-- MSG click-to-copy -->
        <template #item.MSG="{ item }">
          <div class="d-flex align-center gap-1">
            <span>{{ item.MSG || '-' }}</span>
            <VIcon
              v-if="item.MSG"
              icon="tabler-copy"
              size="16"
              color="primary"
              class="cursor-pointer flex-shrink-0"
              @click.stop="copyToClipboard(item.MSG)"
            >
              <VTooltip text="Copia messaggio" activator="parent" location="top" />
            </VIcon>
          </div>
        </template>

        <!-- IDProduzione click-to-copy -->
        <template #item.IDProduzione="{ item }">
          <div class="d-flex align-center gap-1">
            <span>{{ item.IDProduzione }}</span>
            <VIcon
              v-if="item.IDProduzione"
              icon="tabler-copy"
              size="16"
              color="primary"
              class="cursor-pointer flex-shrink-0"
              @click.stop="copyToClipboard(item.IDProduzione)"
            >
              <VTooltip text="Copia" activator="parent" location="top" />
            </VIcon>
          </div>
        </template>

        <!-- Ordine click-to-copy -->
        <template #item.Ordine="{ item }">
          <div class="d-flex align-center gap-1">
            <span>{{ item.Ordine }}</span>
            <VIcon
              v-if="item.Ordine"
              icon="tabler-copy"
              size="16"
              color="primary"
              class="cursor-pointer flex-shrink-0"
              @click.stop="copyToClipboard(item.Ordine)"
            >
              <VTooltip text="Copia" activator="parent" location="top" />
            </VIcon>
          </div>
        </template>

        <!-- Azioni -->
        <template #item.actions="{ item }">
          <VBtn
            size="small"
            color="primary"
            variant="text"
            @click.stop="openUpdateDialog(item)"
          >
            <VIcon icon="tabler-device-floppy" start />
            Aggiorna
            <VTooltip text="Aggiorna" activator="parent" location="top" />
          </VBtn>
        </template>
      </VDataTableServer>
    </VCard>

    <!-- 👉 Dialog Fabbisogni -->
    <VDialog v-model="fabbDialog" max-width="1200">
      <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
        <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
          <div class="d-flex align-center gap-2">
            <VIcon icon="tabler-package-export" size="24" color="primary" />
            <div>
              <div class="text-h6 font-weight-medium">Fabbisogni - ID Produzione: {{ fabbIdProduzione }}</div>
              <div class="text-caption text-medium-emphasis">{{ fabbTotal }} record</div>
            </div>
          </div>
          <VBtn icon="tabler-x" variant="text" density="comfortable" @click="fabbDialog = false" />
        </VCardText>
        <VDivider />
        <VDataTable
          :headers="fabbHeaders"
          :items="fabbItems"
          :loading="fabbLoading"
          density="comfortable"
          hover
          height="400"
          fixed-header
        >
          <template #no-data>
            <div class="py-10 text-center">
              <VIcon icon="tabler-database-off" size="40" class="text-disabled mb-2" />
              <p class="text-body-1 text-disabled mb-0">Nessun fabbisogno trovato</p>
            </div>
          </template>
        </VDataTable>
      </VCard>
    </VDialog>

    <!-- 👉 Dialog Update -->
    <VDialog v-model="updateDialog" max-width="500">
      <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
        <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
          <div class="d-flex align-center gap-2">
            <VIcon icon="tabler-refresh" size="24" color="primary" />
            <div>
              <div class="text-h6 font-weight-medium">Aggiorna Produzione</div>
              <div class="text-caption text-medium-emphasis">ID Produzione: {{ selectedRow?.IDProduzione }}</div>
            </div>
          </div>
          <VBtn icon="tabler-x" variant="text" density="comfortable" @click="updateDialog = false" />
        </VCardText>
        <VDivider />
        <VCardText class="pa-4">
          <div class="text-body-1 mb-4">Cosa vuoi aggiornare?</div>
          <VRow>
            <VCol cols="12">
              <VBtn
                block
                color="primary"
                :loading="updateLoading"
                @click="performUpdate('fabbisogni')"
              >
                Aggiorna solo Fabbisogni
              </VBtn>
            </VCol>
            <VCol cols="12">
              <VBtn
                block
                color="success"
                :loading="updateLoading"
                @click="performUpdate('avanzamento_fabbisogni')"
              >
                Aggiorna Avanzamento e Fabbisogni
              </VBtn>
            </VCol>
          </VRow>
        </VCardText>
      </VCard>
    </VDialog>
  </div>
</template>
