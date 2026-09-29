<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { VForm } from 'vuetify/components/VForm'
import { can } from '@layouts/plugins/casl'
import DefineAbilities from '@/plugins/casl/DefineAbilities'

definePage({
  meta: {
    action: 'list',
    subject: 'Spedizioni-Listini',
  },
})

const { t } = useI18n()

// --- Lista listini ---
const serverItems = ref<any>([])
const itemsPerPage = ref(10)
const loading = ref(true)
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const page = ref(1)
const filterVettore = ref('')
const filterTipo = ref('')

const isSnackbarVisible = ref(false)
const message = ref('')
const color = ref('')

const uploadDialog = ref(false)
const editDialog = ref(false)
const detailDialog = ref(false)
const deleteDialog = ref(false)
const isDialogLoading = ref(false)

const uploadForm = ref({
  file: null as File[] | null,
  vettore: '',
  tipo: 'pallet',
  descrizione: '',
  anno: new Date().getFullYear(),
})

const editForm = ref<any>({
  id: null,
  descrizione: '',
  anno: null,
  attivo: true,
})

const listinoDaEliminare = ref<any>(null)
const listinoDettaglio = ref<any>(null)

// --- Voci dettaglio ---
const serverVoci = ref<any>([])
const itemsPerPageVoci = ref(50)
const loadingVoci = ref(false)
const totalItemsVoci = ref(0)
const pageVoci = ref(1)
const sortByVoci = ref()
const orderByVoci = ref()

const tipoOptions = [
  { title: 'Pallet (provincia x servizio x formato)', value: 'pallet' },
  { title: 'Peso (regione x fascia peso)', value: 'peso' },
]

const headers = computed(() => [
  { title: t('Table.Vettore'), key: 'vettore' },
  { title: t('Table.Tipo'), key: 'tipo' },
  { title: t('Table.Anno'), key: 'anno' },
  { title: t('Table.File'), key: 'file_name', sortable: false },
  { title: t('Table.Stato'), key: 'status' },
  { title: t('Table.Voci'), key: 'numero_voci', sortable: false },
  { title: t('Table.Attivo'), key: 'attivo' },
  { title: 'ACTIONS', key: 'actions', sortable: false },
])

const vociHeaders = computed(() => [
  { title: t('Table.Regione'), key: 'regione' },
  { title: t('Table.Provincia'), key: 'provincia' },
  { title: 'HUB', key: 'hub' },
  { title: t('Table.Servizio'), key: 'servizio' },
  { title: t('Table.Fascia'), key: 'fascia' },
  { title: t('Table.Peso-Da'), key: 'peso_da' },
  { title: t('Table.Peso-A'), key: 'peso_a' },
  { title: t('Table.Prezzo'), key: 'prezzo' },
  { title: t('Table.Tipo-Voce'), key: 'tipo_voce' },
])

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

  const { data: resultData } = await useApi<any>(createUrl('/sp/listini/', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      vettore: filterVettore.value,
      tipo: filterTipo.value,
    },
  }))

  serverItems.value = resultData.value.data
  totalItems.value = resultData.value.total
  loading.value = false
}

const updateOptionsVoci = (options: any) => {
  sortByVoci.value = options.sortBy[0]?.key
  orderByVoci.value = options.sortBy[0]?.order
  pageVoci.value = options.page
  itemsPerPageVoci.value = options.itemsPerPage

  // eslint-disable-next-line @typescript-eslint/no-use-before-define
  loadVoci()
}

const onFiltroChange = () => {
  page.value = 1
  loadItems()
}

const loadVoci = async () => {
  if (!listinoDettaglio.value)
    return

  loadingVoci.value = true

  const { data: resultData } = await useApi<any>(createUrl(`/sp/listini/${listinoDettaglio.value.id}/voci`, {
    query: {
      page: pageVoci.value,
      itemsPerPage: itemsPerPageVoci.value,
      sortBy: sortByVoci.value,
      orderBy: orderByVoci.value,
    },
  }))

  serverVoci.value = resultData.value.data
  totalItemsVoci.value = resultData.value.total
  loadingVoci.value = false
}

const upload = async () => {
  if (!uploadForm.value.file || uploadForm.value.file.length === 0)
    return

  isDialogLoading.value = true

  const formData = new FormData()

  formData.append('file', uploadForm.value.file[0])
  formData.append('vettore', uploadForm.value.vettore)
  formData.append('tipo', uploadForm.value.tipo)
  formData.append('descrizione', uploadForm.value.descrizione ?? '')
  formData.append('anno', String(uploadForm.value.anno ?? ''))

  try {
    const resultData = await $api<any>('sp/listini/upload', {
      method: 'POST',
      body: formData,
    })

    message.value = resultData.message
    color.value = resultData.color
    isSnackbarVisible.value = true
    uploadDialog.value = false
    uploadForm.value = { file: null, vettore: '', tipo: 'pallet', descrizione: '', anno: new Date().getFullYear() }
    await loadItems()
  }
  catch (e) {
    message.value = 'Messaggi.Errore-Upload'
    color.value = 'error'
    isSnackbarVisible.value = true
  }

  isDialogLoading.value = false
}

const openEdit = (item: any) => {
  editForm.value = {
    id: item.id,
    descrizione: item.descrizione,
    anno: item.anno,
    attivo: !!item.attivo,
  }
  editDialog.value = true
}

const saveEdit = async () => {
  isDialogLoading.value = true

  const resultData = await $api<any>(`sp/listini/update/${editForm.value.id}`, {
    method: 'POST',
    body: {
      descrizione: editForm.value.descrizione,
      anno: editForm.value.anno,
      attivo: editForm.value.attivo,
    },
  })

  message.value = resultData.message
  color.value = resultData.color
  isSnackbarVisible.value = true
  editDialog.value = false
  await loadItems()
  isDialogLoading.value = false
}

const openDetail = (item: any) => {
  listinoDettaglio.value = item
  pageVoci.value = 1
  detailDialog.value = true
  loadVoci()
}

const confirmDelete = (item: any) => {
  listinoDaEliminare.value = item
  deleteDialog.value = true
}

const deleteItem = async () => {
  isDialogLoading.value = true

  const resultData = await $api<any>(`sp/listini/delete/${listinoDaEliminare.value.id}`, {
    method: 'DELETE',
  })

  message.value = resultData.message
  color.value = resultData.color
  isSnackbarVisible.value = true
  deleteDialog.value = false
  await loadItems()
  isDialogLoading.value = false
}

const statusColor = (status: string) => {
  if (status === 'processed')
    return 'success'
  if (status === 'error')
    return 'error'

  return 'warning'
}

loadItems()
</script>

<template>
  <div class="workspace-container w-100 d-flex flex-column pa-4 gap-3">
    <VSnackbar
      v-model="isSnackbarVisible"
      transition="scroll-y-reverse-transition"
      location="top central"
      :color="color"
    >
      {{ $t(message) }}
    </VSnackbar>

    <VCard
      variant="outlined"
      class="bg-surface border-thin rounded-lg"
    >
      <!-- Header -->
      <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
        <div class="d-flex align-center gap-2">
          <VIcon
            icon="tabler-file-analytics"
            size="24"
            color="primary"
          />
          <div>
            <div class="text-h6 font-weight-medium">
              {{ $t('Label.Listini-Spedizioni') }}
            </div>
            <div class="text-caption text-medium-emphasis">
              {{ totalItems }} {{ $t('Label.Listini-Caricati') }}
            </div>
          </div>
        </div>
        <VBtn
          v-if="can(DefineAbilities.sp_listini_create.action, DefineAbilities.sp_listini_create.subject)"
          prepend-icon="tabler-upload"
          color="success"
          @click="uploadDialog = true"
        >
          {{ $t('Label.Carica-Listino') }}
        </VBtn>
      </VCardText>
      <VDivider />

      <!-- Filtri -->
      <VCardText class="pa-3">
        <VRow>
          <!-- 👉 Vettore -->
          <VCol
            cols="12"
            sm="4"
          >
            <AppTextField
              v-model="filterVettore"
              :label="$t('Table.Vettore')"
              :placeholder="$t('Label.Cerca-Vettore')"
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-search"
              @keyup.enter="onFiltroChange"
              @click:clear="onFiltroChange"
            />
          </VCol>

          <!-- 👉 Tipo -->
          <VCol
            cols="12"
            sm="4"
          >
            <AppSelect
              v-model="filterTipo"
              :items="tipoOptions"
              :label="$t('Table.Tipo')"
              :placeholder="$t('Label.Tutti')"
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-filter"
              @update:model-value="onFiltroChange"
              @click:clear="onFiltroChange"
            />
          </VCol>
        </VRow>
      </VCardText>
      <VDivider />

      <!-- 👉 Datatable -->
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
              icon="tabler-file-analytics"
              size="40"
              class="text-disabled mb-2"
            />
            <p class="text-body-1 text-disabled mb-0">
              {{ $t('Messaggi.Nessun-Listino') }}
            </p>
          </div>
        </template>

        <template #item.vettore="{ item }">
          <RouterLink
            to=""
            class="font-weight-medium text-link"
            @click="openDetail(item)"
          >
            {{ item.vettore }}
          </RouterLink>
        </template>

        <template #item.status="{ item }">
          <VChip
            :color="statusColor(item.status)"
            size="small"
          >
            {{ item.status }}
          </VChip>
          <VTooltip
            v-if="item.status === 'error' && item.error_message"
            activator="parent"
          >
            {{ item.error_message }}
          </VTooltip>
        </template>

        <template #item.attivo="{ item }">
          <VIcon
            :icon="item.attivo ? 'tabler-check' : 'tabler-x'"
            :color="item.attivo ? 'success' : 'error'"
          />
        </template>

        <template #item.created_at="{ item }">
          {{ formatDate(item.created_at) }}
        </template>

        <template #item.actions="{ item }">
          <div class="d-flex gap-1">
            <IconBtn
              size="small"
              color="primary"
              @click="openDetail(item)"
            >
              <VIcon
                icon="tabler-eye"
                size="18"
              />
            </IconBtn>
            <IconBtn
              v-if="can(DefineAbilities.sp_listini_edit.action, DefineAbilities.sp_listini_edit.subject)"
              size="small"
              color="success"
              @click="openEdit(item)"
            >
              <VIcon
                icon="tabler-pencil"
                size="18"
              />
            </IconBtn>
            <IconBtn
              v-if="can(DefineAbilities.sp_listini_deleted.action, DefineAbilities.sp_listini_deleted.subject)"
              size="small"
              color="error"
              @click="confirmDelete(item)"
            >
              <VIcon
                icon="tabler-trash"
                size="18"
              />
            </IconBtn>
          </div>
        </template>
      </VDataTableServer>
    </VCard>

    <!-- 👉 Upload Dialog -->
    <VDialog
      v-model="uploadDialog"
      max-width="600px"
    >
      <VCard
        variant="outlined"
        class="bg-surface border-thin rounded-lg"
      >
        <VCardText class="d-flex align-center justify-space-between py-3 gap-3">
          <div class="d-flex align-center gap-2">
            <VIcon
              icon="tabler-upload"
              size="24"
              color="primary"
            />
            <div class="text-h6 font-weight-medium">
              {{ $t('Label.Carica-Listino') }}
            </div>
          </div>
          <IconBtn
            size="small"
            @click="uploadDialog = false"
          >
            <VIcon
              icon="tabler-x"
              size="18"
            />
          </IconBtn>
        </VCardText>
        <VDivider />
        <VCardText class="pa-3">
          <VForm>
            <VRow>
              <VCol cols="12">
                <VFileInput
                  v-model="uploadForm.file"
                  :label="$t('Label.File-PDF')"
                  accept="application/pdf"
                  prepend-icon="tabler-file-pdf"
                />
              </VCol>
              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="uploadForm.vettore"
                  :label="$t('Table.Vettore')"
                  placeholder="PALLETWAYS, SUSA, ..."
                />
              </VCol>
              <VCol
                cols="12"
                md="6"
              >
                <VSelect
                  v-model="uploadForm.tipo"
                  :items="tipoOptions"
                  :label="$t('Table.Tipo')"
                />
              </VCol>
              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="uploadForm.descrizione"
                  :label="$t('Table.Descrizione')"
                />
              </VCol>
              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="uploadForm.anno"
                  :label="$t('Table.Anno')"
                  type="number"
                />
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
        <VDivider />
        <VCardActions class="pa-3">
          <VSpacer />
          <VBtn
            color="error"
            variant="outlined"
            @click="uploadDialog = false"
          >
            {{ $t('Button.Cancella') }}
          </VBtn>
          <VBtn
            color="success"
            variant="elevated"
            :disabled="!uploadForm.file || !uploadForm.vettore"
            @click="upload"
          >
            {{ $t('Button.Upload') }}
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- 👉 Edit Dialog -->
    <VDialog
      v-model="editDialog"
      max-width="500px"
    >
      <VCard
        variant="outlined"
        class="bg-surface border-thin rounded-lg"
      >
        <VCardText class="d-flex align-center justify-space-between py-3 gap-3">
          <div class="d-flex align-center gap-2">
            <VIcon
              icon="tabler-pencil"
              size="24"
              color="primary"
            />
            <div class="text-h6 font-weight-medium">
              {{ $t('Label.Modifica-Listino') }}
            </div>
          </div>
          <IconBtn
            size="small"
            @click="editDialog = false"
          >
            <VIcon
              icon="tabler-x"
              size="18"
            />
          </IconBtn>
        </VCardText>
        <VDivider />
        <VCardText class="pa-3">
          <VRow>
            <VCol cols="12">
              <VTextField
                v-model="editForm.descrizione"
                :label="$t('Table.Descrizione')"
              />
            </VCol>
            <VCol
              cols="12"
              md="6"
            >
              <VTextField
                v-model="editForm.anno"
                :label="$t('Table.Anno')"
                type="number"
              />
            </VCol>
            <VCol
              cols="12"
              md="6"
            >
              <VCheckbox
                v-model="editForm.attivo"
                :label="$t('Table.Attivo')"
              />
            </VCol>
          </VRow>
        </VCardText>
        <VDivider />
        <VCardActions class="pa-3">
          <VSpacer />
          <VBtn
            color="error"
            variant="outlined"
            @click="editDialog = false"
          >
            {{ $t('Button.Cancella') }}
          </VBtn>
          <VBtn
            color="success"
            variant="elevated"
            @click="saveEdit"
          >
            {{ $t('Button.Save') }}
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- 👉 Detail Dialog (voci) -->
    <VDialog
      v-model="detailDialog"
      max-width="1200px"
    >
      <VCard
        variant="outlined"
        class="bg-surface border-thin rounded-lg"
      >
        <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
          <div class="d-flex align-center gap-2">
            <VIcon
              icon="tabler-list-details"
              size="24"
              color="primary"
            />
            <div>
              <div class="text-h6 font-weight-medium">
                {{ $t('Label.Voci-Listino') }}
              </div>
              <div class="text-caption text-medium-emphasis">
                {{ listinoDettaglio?.vettore ?? '' }}
                <template v-if="listinoDettaglio?.descrizione">
                  · {{ listinoDettaglio.descrizione }}
                </template>
              </div>
            </div>
          </div>
          <div class="d-flex align-center gap-2">
            <VChip
              v-if="listinoDettaglio"
              size="small"
              :color="listinoDettaglio.attivo ? 'success' : 'error'"
              variant="tonal"
            >
              {{ listinoDettaglio.attivo ? $t('Table.Attivo') : $t('Table.Disattivo') }}
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
          <VDataTableServer
            v-model:items-per-page="itemsPerPageVoci"
            :headers="vociHeaders"
            :items="serverVoci"
            :items-length="totalItemsVoci"
            :loading="loadingVoci"
            density="comfortable"
            hover
            @update:options="updateOptionsVoci"
          >
            <template #no-data>
              <div class="py-8 text-center">
                <VIcon
                  icon="tabler-list-details"
                  size="36"
                  class="text-disabled mb-2"
                />
                <p class="text-body-1 text-disabled mb-0">
                  {{ $t('Messaggi.Nessuna-Voce') }}
                </p>
              </div>
            </template>
            <template #item.tipo_voce="{ item }">
              <VChip
                :color="item.tipo_voce === 'inoltro' ? 'info' : 'default'"
                size="small"
              >
                {{ item.tipo_voce }}
              </VChip>
            </template>
          </VDataTableServer>
        </VCardText>
      </VCard>
    </VDialog>

    <!-- 👉 Delete Dialog -->
    <VDialog
      v-model="deleteDialog"
      max-width="450px"
    >
      <VCard
        variant="outlined"
        class="bg-surface border-thin rounded-lg"
      >
        <VCardText class="d-flex align-center justify-space-between py-3 gap-3">
          <div class="d-flex align-center gap-2">
            <VIcon
              icon="tabler-trash"
              size="24"
              color="error"
            />
            <div class="text-h6 font-weight-medium">
              {{ $t('Label.Elimina-Listino') }}
            </div>
          </div>
          <IconBtn
            size="small"
            @click="deleteDialog = false"
          >
            <VIcon
              icon="tabler-x"
              size="18"
            />
          </IconBtn>
        </VCardText>
        <VDivider />
        <VCardText class="pa-3">
          {{ $t('Messaggi.Conferma-Eliminazione-Listino') }} <strong>{{ listinoDaEliminare?.vettore }}</strong>?
        </VCardText>
        <VDivider />
        <VCardActions class="pa-3">
          <VSpacer />
          <VBtn
            color="primary"
            variant="outlined"
            @click="deleteDialog = false"
          >
            {{ $t('Button.Cancella') }}
          </VBtn>
          <VBtn
            color="error"
            variant="elevated"
            @click="deleteItem"
          >
            {{ $t('Button.Delete') }}
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
  </div>
</template>
