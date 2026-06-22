<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { useI18n } from 'vue-i18n'
import { VForm } from 'vuetify/components/VForm'
import { can } from '@layouts/plugins/casl'
import DefineAbilities from '@/plugins/casl/DefineAbilities'
import EditFornitore from "@/views/quality/fornitore/editFornitore.vue";

definePage({
  meta: {
    action: 'list',
    subject: 'Qt-Supplier',
  },
})

const { t } = useI18n()
const itemsPerPage = ref(10)
const loading = ref(false)
const refForm = ref<VForm>()
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const ragioneSocialeFilter = ref('')
const codiceSapFilter = ref('')
const categoriaFilter = ref()
const page = ref(1)
const serverItems = ref<any>([])
const isSnackbarScrollReverseVisible = ref(false)
const message = ref('')
const color = ref('')
const editDialog = ref(false)
const isLoading = ref(false)

const defaultItem = ref<any>({
  id: '',
  email: '',
  codiceSap: '',
  ragioneSociale: '',
  nazione: '',
  indirizzo: '',
  cap: '',
  citta: '',
  servizio: null,
  disattivo: 0,
  qualificato: 0,
  prezzo: null,
  critico: '',
  categoria: null,
  folderID: '',
})

function new_defaultItem() {
  defaultItem.value = {
    id: '',
    email: '',
    codiceSap: '',
    ragioneSociale: '',
    nazione: '',
    indirizzo: '',
    cap: '',
    citta: '',
    servizio: null,
    disattivo: 0,
    qualificato: 0,
    prezzo: null,
    critico: '',
    categoria: null,
    folderID: '',
  }
}

const editedItem = ref<any>(defaultItem.value)
const editedIndex = ref(-1)

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

  const { data: resultData, error } = await useApi<any>(createUrl('/qt/supplier/', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      ragioneSociale: ragioneSocialeFilter.value,
      cdSap: codiceSapFilter.value,
      categoria: categoriaFilter.value,
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
}

// headers
const headers = [
  { title: t('Table.Fornitore'), key: 'ragioneSociale' },
  { title: t('Table.Rating'), key: 'rating', sortable: false },
  { title: t('Table.Prezzo'), key: 'prezzo', sortable: false },
  { title: t('Table.Servizio'), key: 'servizio' },
  { title: t('Table.Critico'), key: 'critico' },
  { title: t('Table.Qualificato'), key: 'qualificato' },
  { title: t('Table.Categoria'), key: 'categoria' },
  { title: t('Table.Nazione'), key: 'nazione' },
  { title: 'ACTIONS', key: 'actions', sortable: false },
]

const save = async () => {

  let path = '/qt/supplier/stored/'
  if (editedItem.value.id)
    path = `/qt/supplier/update/${editedItem.value.id}`

  const retuenData = await $api(path, {
    method: 'POST',
    body: editedItem.value,
  })

  nextTick(() => {
    refForm.value?.reset()
    refForm.value?.resetValidation()
  })

  message.value = retuenData.message
  color.value = retuenData.color
  isSnackbarScrollReverseVisible.value = true

  isLoading.value = false
  editDialog.value = false
  await loadItems()
}

const newItem = () => {
  new_defaultItem()
  editedIndex.value = -1
  editedItem.value = { ...defaultItem.value }
  editedItem.value.critico = '0'
  editedItem.value.qualificato = '0'
  editDialog.value = true
}

const editDialogClose = (edit: object) => {
  console.log(edit)
  //editDialog.value = true
}

const editItem = (item: object) => {
  editedIndex.value = serverItems.value.indexOf(item)

  editedItem.value = { ...item }
  editDialog.value = true
}

const roundTo = function (num: number, places: number) {
  const factor = 10 ** places

  return Math.round(num * factor) / factor
}

const exportToExcel = () => {
  const url = `/api/export/supplier/excel?ragioneSociale=${ragioneSocialeFilter.value}&codiceSap=${codiceSapFilter.value}&categoria=${categoriaFilter.value}`
  window.open(url, '_blank')
}
</script>

<template>
  <VCol cols="12">
    <VSnackbar
      v-model="isSnackbarScrollReverseVisible"
      transition="scroll-y-reverse-transition"
      location="top center"
      :color="color"
    >
      {{ $t(message) }}
    </VSnackbar>

    <VCard class="elegant-card overflow-hidden">
      <div class="elegant-header d-flex align-center justify-space-between px-3 py-2 border-b">
        <div class="d-flex align-center gap-2">
          <VIcon icon="tabler-building-factory-2" size="16" class="text-secondary" />
          <span class="text-subtitle-2 font-weight-bold text-high-emphasis">Fornitori</span>
        </div>
        <div class="d-flex gap-2">
          <VBtn
            variant="tonal"
            color="secondary"
            density="comfortable"
            class="px-3 text-xs font-weight-bold"
            prepend-icon="tabler-file-export"
            @click="exportToExcel"
          >
            Export
          </VBtn>
          <VBtn
            v-if="can(DefineAbilities.qt_supplier_create.action, DefineAbilities.qt_supplier_create.subject)"
            prepend-icon="tabler-plus"
            color="primary"
            density="comfortable"
            class="px-3 text-xs font-weight-bold elevation-0 rounded-sm"
            @click="newItem"
          >
            {{ t('Button.Nuovo-Fornitore') }}
          </VBtn>
        </div>
      </div>

      <VCardText class="pa-5">
        <VRow class="align-center g-1">
          <VCol cols="12" sm="3" class="py-0">
            <AppTextField
              v-model="ragioneSocialeFilter"
              :label="$t('Label.Fornitore')"
              clearable
              clear-icon="tabler-x"
              density="compact"
              hide-details
              variant="filled"
              @update:model-value="loadItems"
            />
          </VCol>
          <VCol cols="12" sm="3" class="py-0">
            <AppTextField
              v-model="codiceSapFilter"
              :label="$t('Label.Codice-Sap')"
              clearable
              clear-icon="tabler-x"
              density="compact"
              hide-details
              variant="filled"
              @update:model-value="loadItems"
            />
          </VCol>
          <VCol cols="12" sm="3" class="py-0">
            <AppSelect
              v-model="categoriaFilter"
              :label="$t('Label.Categoria')"
              :placeholder="$t('Label.Categoria')"
              :items="[{ title: 'Ausiliari, connessioni', value: 'AUS' }, { value: 'BOB', title: 'Bobine legno e plastica' },
                       { value: 'CCU', title: 'Catodi di rame e vergella' }, { value: 'COL', title: 'Coloranti in granuli e polvere' }, { value: 'FIA', title: 'Filo acciaio ' },
                       { value: 'FIL', title: 'Filo di rame e multifilo rosso, stagnato, argentato' }, { value: 'FIO', title: 'Fibre ottiche monomodali e multimodali' }, { value: 'FIR', title: 'Filati di rinforzo aramidici e tessili' },
                       { value: 'JEL', title: 'Jelly e altri composti per FO' }, { value: 'LEG', title: 'Fili in lega per termocoppie e coax' }, { value: 'MEG', title: 'Mescole in gomma in strisce e granuli' },
                       { title: 'Prodotti per mescole PVC', value: 'MES' }, { title: 'Nastri e semilavorati in alluminio e leghe', value: 'NAA' }, { title: 'Nastro ferro e semilavorati in ferro e zincati', value: 'NAF' },
                       { title: 'Nastro rame', value: 'NAR' }, { title: 'Nastri in materiale sintetico e tessile', value: 'NAS' }, { title: 'Servizi', value: 'SER' },
                       { title: 'Semilavorati e varie', value: 'SLA' }, { title: 'Granuli termoplastici per isolamento e guaina', value: 'TER' }, { title: 'Tubetti in rame e altri profili', value: 'TUB' },
                       { title: 'Vernici ed inchiostri', value: 'VER' },
              ]"
              clearable
              clear-icon="tabler-x"
              density="compact"
              hide-details
              variant="filled"
              @update:model-value="loadItems"
            />
          </VCol>
        </VRow>
      </VCardText>

      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        :headers="headers"
        :items="serverItems"
        :items-length="totalItems"
        :loading="loading"
        density="compact"
        @update:options="updateOptions"
      >
        <template #item.ragioneSociale="{ item }">
          <div class="d-flex align-center">
            <div class="d-flex flex-column">
              <h6 class="text-base">
                <RouterLink
                  :to="{ name: 'quality-fornitori-view-id', params: { id: item.id } }"
                  class="font-weight-medium text-link text-primary"
                >
                  {{ item.ragioneSociale }}
                </RouterLink>
              </h6>
            </div>
          </div>
        </template>
		
		<template #item.rating="{ item }">
          <h4>{{roundTo(item.rating, 2)}}</h4>
        </template>

        <template #item.qualificato="{ item }">
          <div
            v-if="item.qualificato === '1'"
            class="d-flex gap-1"
          >
            <VIcon
              color="success"
              icon="tabler-check"
            />
          </div>
          <div
            v-else
            class="d-flex gap-1"
          >
            <VIcon
              color="error"
              icon="tabler-alert-triangle"
            />
          </div>
        </template>

        <template #item.critico="{ item }">
          <div
            v-if="item.critico === '1'"
            class="d-flex gap-1"
          >
            <VIcon
              color="warning"
              icon="tabler-check"
            />
          </div>
          <div
            v-else
            class="d-flex gap-1"
          />
        </template>
        <!-- Actions -->
        <template #item.actions="{ item }">
          <div class="d-flex gap-1">
            <IconBtn
              v-if="can(DefineAbilities.qt_supplier_edit.action, DefineAbilities.qt_supplier_edit.subject)"
              color="warning"
              @click="editItem(item)"
            >
              <VIcon icon="tabler-edit" />
            </IconBtn>
          </div>
        </template>
      </VDataTableServer>
    </VCard>
  </VCol>

  <!-- 👉 Edit Dialog  -->
  <EditFornitore
    v-model:isDrawerOpen="editDialog"
    :fornitore="editedItem"
    @fornitore="save"
  />
</template>

<style lang="scss">
.elegant-card {
  box-shadow: 0 10px 30px -10px rgba(0,0,0,0.15) !important;
  border: 1px solid rgba(var(--v-border-color), 0.05);
}

.g-1 {
  row-gap: 6px !important;
  column-gap: 6px !important;
}

.g-2 {
  row-gap: 10px !important;
  column-gap: 10px !important;
}

.border-b {
  border-bottom: 1px solid rgba(var(--v-border-color), 0.06) !important;
}

.text-xs { font-size: 0.72rem !important; }

:deep(.v-label) {
  font-size: 0.75rem !important;
  font-weight: 500 !important;
  letter-spacing: 0.2px;
  color: rgba(var(--v-theme-on-surface), 0.7);
  margin-bottom: 3px !important;
}
</style>
