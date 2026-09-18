<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { useI18n } from 'vue-i18n'
import moment from 'moment/moment'

definePage({
  meta: {
    action: 'list',
    subject: 'Produzione-Magazzino',
  },
})

const { t } = useI18n()
const itemsPerPage = ref(10)
const loading = ref(true)
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const page = ref(1)
const serverItems = ref<any>([])
const isSnackbarScrollReverseVisible = ref(false)
const message = ref('')
const color = ref('')

const materialeFilter = ref('')
const documentoFilter = ref('')
const tipoFilter = ref()
const statoFilter = ref()
const dataDaFilter = ref('2026-09-14')
const dataAFilter = ref()

const isApprover = ref(false)
const roleId = ref(null)

const approveDialog = ref(false)
const rejectDialog = ref(false)
const isDialogLoading = ref(false)
const selectedItem = ref<any>({})
const comment = ref('')

const tipoOptions = [
  { title: '701 - Rettifica +', value: '701' },
  { title: '702 - Rettifica -', value: '702' },
  { title: '201 - Consumo CC', value: '201' },
  { title: '202 - Storno Consumo CC', value: '202' },
]

const statoOptions = [
  { title: 'Da Approvare', value: 'pending' },
  { title: 'In Approvazione', value: 'In-Approval' },
  { title: 'Approvato', value: 'Approved' },
  { title: 'Rifiutato', value: 'Rejected' },
]

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

  const { data: resultData } = await useApi<any>(createUrl('/pr/movimenti/list', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      materiale: materialeFilter.value,
      documento: documentoFilter.value,
      tipo_movimento: tipoFilter.value,
      stato: statoFilter.value,
      data_da: dataDaFilter.value,
      data_a: dataAFilter.value,
    },
  }))

  if (resultData.value !== null) {
    serverItems.value = resultData.value.objs.data
    totalItems.value = resultData.value.objs.total
    isApprover.value = resultData.value.is_approver
    roleId.value = resultData.value.role_id
  }
  else {
    serverItems.value = []
    totalItems.value = 0
    isApprover.value = false
  }
  loading.value = false
}

// headers
const headers = computed(() => [
  { title: t('Table.Data-Documento'), key: 'data_documento' },
  { title: t('Table.Documento'), key: 'documento_materiale' },
  { title: t('Table.Materiale'), key: 'materiale' },
  { title: t('Table.Descrizione'), key: 'descrizione', sortable: false },
  { title: t('Table.Tipo-Movimento'), key: 'tipo_movimento' },
  { title: t('Table.Quantita'), key: 'quantita' },
  { title: t('Table.Importo'), key: 'importo' },
  { title: t('Table.Utente'), key: 'user' },
  { title: t('Table.Stato'), key: 'stato' },
  { title: t('Table.Azioni'), key: 'actions', sortable: false },
])

const resolveStato = (item: any) => {
  if (item.stato === 'Approved')
    return { color: 'success', text: 'Approvato' }
  if (item.stato === 'Rejected')
    return { color: 'error', text: 'Rifiutato' }
  if (item.stato === 'In-Approval')
    return { color: 'warning', text: 'In Approvazione' }
  if (item.approval_action === 'Approved')
    return { color: 'success', text: 'Approvato da te' }
  if (item.approval_action === 'Rejected')
    return { color: 'error', text: 'Rifiutato da te' }

  return { color: 'warning', text: 'Da Approvare' }
}

const canProcess = (item: any) => {
  return isApprover.value
    && !item.approval_action
    && item.stato !== 'Approved'
    && item.stato !== 'Rejected'
}

const openApprove = (item: any) => {
  selectedItem.value = item
  comment.value = ''
  approveDialog.value = true
}

const openReject = (item: any) => {
  selectedItem.value = item
  comment.value = ''
  rejectDialog.value = true
}

const showMessage = (data: any) => {
  message.value = data.message
  color.value = data.color
  isSnackbarScrollReverseVisible.value = true
}

const approva = async () => {
  isDialogLoading.value = true

  const returnData = await $api<any>('/pr/movimenti/approval', {
    method: 'POST',
    body: {
      id: selectedItem.value.id,
      comment: comment.value,
    },
  })

  showMessage(returnData)
  isDialogLoading.value = false
  approveDialog.value = false
  await loadItems()
}

const rifiuta = async () => {
  isDialogLoading.value = true

  const returnData = await $api<any>('/pr/movimenti/reject', {
    method: 'POST',
    body: {
      id: selectedItem.value.id,
      comment: comment.value,
    },
  })

  showMessage(returnData)
  isDialogLoading.value = false
  rejectDialog.value = false
  await loadItems()
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
    '23910839': 'Ricca Asia',
    '23920682': 'Roberta Cossetti',
  }
  return users[user] || user
}

function formatDate(date: string): string {
  if (!date) return '-'
  return moment(String(date)).format('DD/MM/YYYY')
}

const euro = new Intl.NumberFormat('it-IT', {
  style: 'currency',
  currency: 'EUR',
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
      {{ message }}
    </VSnackbar>

    <VCard
      variant="outlined"
      class="bg-surface border-thin rounded-lg"
    >
      <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
        <div class="d-flex align-center gap-2">
          <VIcon
            icon="tabler-clipboard-check"
            size="24"
            color="primary"
          />
          <div>
            <div class="text-h6 font-weight-medium">
              Approvazione Movimenti
            </div>
            <div class="text-caption text-medium-emphasis">
              {{ totalItems }} movimenti
            </div>
          </div>
        </div>
        <div class="d-flex align-center gap-2">
          <VChip
            v-if="isApprover"
            color="success"
            prepend-icon="tabler-circle-check"
          >
            Approvatore
          </VChip>
        </div>
      </VCardText>
      <VDivider />
      <VCardText class="pa-3">
        <VRow class="mb-2">
          <!-- 👉 Materiale -->
          <VCol
            cols="12"
            sm="2"
          >
            <AppTextField
              v-model="materialeFilter"
              :label="$t('Table.Materiale')"
              :placeholder="$t('Table.Materiale')"
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-search"
              @keyup.enter="loadItems"
              @click:clear="loadItems"
            />
          </VCol>

          <!-- 👉 Documento -->
          <VCol
            cols="12"
            sm="2"
          >
            <AppTextField
              v-model="documentoFilter"
              :label="$t('Table.Documento')"
              :placeholder="$t('Table.Documento')"
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-search"
              @keyup.enter="loadItems"
              @click:clear="loadItems"
            />
          </VCol>

          <!-- 👉 Tipo Movimento -->
          <VCol
            cols="12"
            sm="2"
          >
            <AppSelect
              v-model="tipoFilter"
              :label="$t('Table.Tipo-Movimento')"
              :placeholder="$t('Label.Tutti')"
              :items="tipoOptions"
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-filter"
              @update:model-value="loadItems"
              @click:clear="loadItems"
            />
          </VCol>

          <!-- 👉 Stato -->
          <VCol
            cols="12"
            sm="2"
          >
            <AppSelect
              v-model="statoFilter"
              :label="$t('Table.Stato')"
              :placeholder="$t('Label.Tutti')"
              :items="statoOptions"
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-filter"
              @update:model-value="loadItems"
              @click:clear="loadItems"
            />
          </VCol>

          <!-- 👉 Data Da -->
          <VCol
            cols="12"
            sm="2"
          >
            <AppDateTimePicker
              v-model="dataDaFilter"
              :label="$t('Label.Data-Da')"
              :placeholder="$t('Label.Data-Da')"
              clearable
              @update:model-value="loadItems"
            />
          </VCol>

          <!-- 👉 Data A -->
          <VCol
            cols="12"
            sm="2"
          >
            <AppDateTimePicker
              v-model="dataAFilter"
              :label="$t('Label.Data-A')"
              :placeholder="$t('Label.Data-A')"
              clearable
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
        density="comfortable"
        hover
        @update:options="updateOptions"
      >
        <template #no-data>
          <div class="py-10 text-center">
            <VIcon
              icon="tabler-clipboard-off"
              size="40"
              class="text-disabled mb-2"
            />
            <p class="text-body-1 text-disabled mb-0">
              Nessun movimento trovato
            </p>
          </div>
        </template>

        <template #item.data_documento="{ item }">
          {{ formatDate(item.data_documento) }}
        </template>

        <template #item.quantita="{ item }">
          {{ item.quantita }} {{ item.um }}
        </template>

        <template #item.importo="{ item }">
          {{ euro.format(item.importo) }}
        </template>

        <template #item.user="{ item }">
          {{ resolveUser(item.user) }}
        </template>

        <template #item.stato="{ item }">
          <VChip
            :color="resolveStato(item).color"
            size="small"
          >
            {{ resolveStato(item).text }}
          </VChip>
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }">
          <div
            v-if="canProcess(item)"
            class="d-flex gap-1"
          >
            <IconBtn
              color="success"
              size="small"
              @click="openApprove(item)"
            >
              <VIcon
                icon="tabler-check"
                size="18"
              />
              <VTooltip
                activator="parent"
                location="top"
              >
                Approva
              </VTooltip>
            </IconBtn>
            <IconBtn
              color="error"
              size="small"
              @click="openReject(item)"
            >
              <VIcon
                icon="tabler-x"
                size="18"
              />
              <VTooltip
                activator="parent"
                location="top"
              >
                Rifiuta
              </VTooltip>
            </IconBtn>
          </div>
        </template>
      </VDataTableServer>
    </VCard>
  </div>

  <!-- 👉 Approve Dialog  -->
  <VDialog
    v-model="approveDialog"
    persistent
    max-width="500px"
  >
    <VCard title="Approva Movimento">
      <VCardText class="text-body-2">
        Vuoi approvare il movimento <strong>{{ selectedItem.tipo_movimento }}</strong> del materiale
        <strong>{{ selectedItem.materiale }}</strong> (Doc. {{ selectedItem.documento_materiale }})?
      </VCardText>
      <VCardText class="pt-0">
        <AppTextarea
          v-model="comment"
          :label="$t('Label.Nota')"
          :placeholder="$t('Label.Nota')"
          rows="2"
        />
      </VCardText>
      <VCardActions>
        <VSpacer />
        <VBtn
          color="error"
          variant="outlined"
          @click="approveDialog = false"
        >
          {{ $t('Button.Annulla') }}
        </VBtn>
        <VBtn
          color="success"
          variant="elevated"
          @click="approva"
        >
          Approva
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <!-- 👉 Reject Dialog  -->
  <VDialog
    v-model="rejectDialog"
    persistent
    max-width="500px"
  >
    <VCard title="Rifiuta Movimento">
      <VCardText class="text-body-2">
        Vuoi rifiutare il movimento <strong>{{ selectedItem.tipo_movimento }}</strong> del materiale
        <strong>{{ selectedItem.materiale }}</strong> (Doc. {{ selectedItem.documento_materiale }})?
        Verrà inviata una mail di notifica.
      </VCardText>
      <VCardText class="pt-0">
        <AppTextarea
          v-model="comment"
          :label="$t('Label.Motivazione')"
          :placeholder="$t('Label.Motivazione')"
          rows="2"
        />
      </VCardText>
      <VCardActions>
        <VSpacer />
        <VBtn
          color="primary"
          variant="outlined"
          @click="rejectDialog = false"
        >
          {{ $t('Button.Annulla') }}
        </VBtn>
        <VBtn
          color="error"
          variant="elevated"
          @click="rifiuta"
        >
          Rifiuta
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <!-- Loading Dialog -->
  <VDialog
    v-model="isDialogLoading"
    width="300"
  >
    <VCard
      color="primary"
      width="300"
    >
      <VCardText class="pt-3">
        <span class="ml-4 mb-3">Please stand by</span>
        <VProgressLinear
          :size="40"
          color="warning"
          class="mt-3"
          indeterminate
        />
      </VCardText>
    </VCard>
  </VDialog>
</template>
