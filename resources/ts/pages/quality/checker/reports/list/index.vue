<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { VForm } from 'vuetify/components/VForm'
import { useI18n } from 'vue-i18n'
import { can } from '@layouts/plugins/casl'
import InvoiceEditable from '@/views/quality/checker/report/colisForm.vue'
import DefineAbilities from '@/plugins/casl/DefineAbilities'
import type { Coils, ReprotChecker } from '@/views/quality/checker/type'
import NonConforme from '@/components/dialogs/NonConforme.vue'
import StageReport from '@/pages/quality/checker/StageReport.vue'
import type {Conformita} from "@/views/quality/conformita/type";

definePage({
  meta: {
    action: 'list',
    subject: 'Qualita-Checker-Report',
  },
})

const dataCorrente = new Date()
const { t } = useI18n()
const loading = ref(false)
const isDialogLoading = ref(false)
const view = ref(false)
const serverItems = ref<ReprotChecker[]>([])
const itemsPerPage = ref(10)
const page = ref(1)
const sortBy = ref()
const orderBy = ref()
const olFilter = ref()
const totalItems = ref(0)
const listCheckers = ref({})
const dataFilter = ref(`${dataCorrente.getFullYear()}-${dataCorrente.getMonth()+1}-${dataCorrente.getUTCDate()}`)
const isFormValid = ref(false)
const editDialog = ref(false)
const deleteDialog = ref(false)
const isSnackbarScrollReverseVisible = ref(false)
const message = ref('')
const color = ref('')
const selectedChecker = ref('')
const refForm = ref<VForm>()
const nonConformitaVisibile = ref(false)
const NonConformeItem = ref<Conformita>({})
const olError = ref('')

const defaultItem = ref<ReprotChecker>({
  id: null,
  user: null,
  date_create: '',
  ol: '',
  num_fo: null,
  stage: '',
  note: '',
  lavorazione: 5420,
  not_conformity: false,
  coils: [
    {
      coil: '',
      coil_t: '',
      fo_try: null,
    },
  ],
})

const conformitaItem = ref<Conformita>({
  id: undefined,
  report_id: null,
  user: null,
  ol: '',
  materiale: '',
  num_fo: null,
  stage: null,
  bobina: '',
  note: '',
  macchina: null,
  difetto: null,
  fibre: '',
  soluzione: '',
  stato: null,
  diametro: null,
  tipologia_fibra: null,
  operator: '',
  physical_l: null,
  optical_l: null,
  tipologia_difetto: null,
  disable: false,
  approvazione: '',
  motivazione: '',
  file_upload: {},
})

const editedItem = ref<ReprotChecker>(defaultItem.value)
const deletedItem = ref({})
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

    const { data: resultData } = await useApi<any>(createUrl('/qt/checker/report', {
      query: {
        page: page.value,
        itemsPerPage: itemsPerPage.value,
        sortBy: sortBy.value,
        orderBy: orderBy.value,
        checker: selectedChecker.value,
        data: dataFilter.value,
        ordine: olFilter.value,
        lavorazione: 5420,
      },

    }))

  serverItems.value = resultData.value.data
  totalItems.value = resultData.value.total
  loading.value = false
}

const loadChecker = async () => {
  const resultData = await useApi<any>(createUrl('/users/get_users_permission', {
    query: {
      permission: 'qt.checker.report.create',
    },
  }))

  listCheckers.value = resultData.data.value.data
  view.value = true
}

loadChecker()

const macchineOptions = []
const defettiOptions = []
const fibraTipoOptions = []

const loadMacchine = async () => {
  const resultData = await useApi<any>(createUrl('/macchine/get_list', {
    query: {
      attivo: true,
    },
  }))

  resultData.data.value.forEach((value: any) => {
    macchineOptions.push({ id: value.id, titolo: value.nome })
  })
}

const loadDifettie = async () => {
  const resultData = await useApi<any>(createUrl('/difetti/get_list', {
    query: {
      attivo: true,
    },
  }))

  resultData.data.value.forEach((value: any) => {
    defettiOptions.push({ id: value.id, titolo: value.difetto, categoria: value.categoria })
  })
}

const loadFibreTipo = async () => {
  const resultData = await useApi<any>(createUrl('/fibra_tipologia/get_list', {
    query: {
      attivo: true,
    },
  }))

  resultData.data.value.forEach((value: any) => {
    fibraTipoOptions.push({ id: value.id, titolo: value.nome })
  })
}

// status options
const selectedOptions = [
  { text: 'BUF', value: 'BUF' },
  { text: 'SZ', value: 'SZ' },
  { text: 'FC', value: 'FC' },
  { text: 'PE', value: 'PE' },
  { text: 'COL', value: 'COL' },
  { text: 'SF', value: 'SF' },
]

// headers
const headers = [
  { title: t('Table.Data'), key: 'date_create' },
  { title: t('Table.Ol'), key: 'ol' },
  { title: t('Table.Numero-Fo'), key: 'num_fo' },
  { title: t('Table.Numero-Bobina'), key: 'coil', sortable: false },
  { title: t('Table.Fo-Testate'), key: 'fo_try', sortable: false },
  { title: t('Table.Stage'), key: 'stage' },
  { title: t('Table.Chilometri'), key: 'km', sortable: false },
  { title: t('Table.Non-Conforme'), key: 'not_conformity', sortable: false },
  { title: t('Table.Actions'), key: 'actions', sortable: false },
]

const resolveStatusVariant = (stage: string) => {
  if (stage === 'BUF')
    return { color: 'buf', text: 'BUF' }
  else if (stage === 'SZ')
    return { color: 'sz', text: 'SZ' }
  else if (stage === 'FC')
    return { color: 'fc', text: 'FC' }
  else if (stage === 'PE')
    return { color: 'pe', text: 'PE' }
  else if (stage === 'COL')
    return { color: 'col', text: 'COL' }
  else if (stage === 'GUA')
    return { color: 'gua', text: 'GUA' }
  else if (stage === 'ISOL')
    return { color: 'isol', text: 'ISOL' }
  else if (stage === 'CORD')
    return { color: 'cord', text: 'CORD' }
  else if (stage === 'ARMA')
    return { color: 'arma', text: 'ARMA' }
  else if (stage === 'NASTR')
    return { color: 'nastr', text: 'NASTR' }
  else
    return { color: 'sf', text: 'SF' }
}

function new_defaultItem() {
  defaultItem.value = {
    id: null,
    user: null,
    date_create: '',
    ol: '',
    num_fo: null,
    stage: '',
    note: '',
    lavorazione: 5420,
    coils: [
      {
        coil: '',
        coil_t: '',
        fo_try: null,
        km: null,
      },
    ],
  }
}

const newItem = () => {
  new_defaultItem()
  editedIndex.value = -1
  editedItem.value = { ...defaultItem.value }
  editDialog.value = true
}

// 👉 methods
const editItem = (item: ReprotChecker) => {
  editedIndex.value = serverItems.value.indexOf(item)
  item.coils = [{ coil: '', coil_t: item.coil, fo_try: item.fo_try, km: item.km  }]
  editedItem.value = { ...item }
  editDialog.value = true
}

const deleteItem = (item: ReprotChecker) => {
  editedIndex.value = serverItems.value.indexOf(item)
  editedItem.value = { ...item }
  deletedItem.value = { ...item }
  deleteDialog.value = true
}

const close = () => {
  editDialog.value = false
  editedIndex.value = -1
  editedItem.value = { ...defaultItem.value }
}

const closeDelete = () => {
  deleteDialog.value = false
  editedIndex.value = -1
  editedItem.value = { ...defaultItem.value }
}

const save = async () => {
  if ( (editedItem.value.ol && editedItem.value.ol.length === 8) && editedItem.value.stage && editedItem.value.num_fo && editedItem.value.coils[0]['km'] && editedItem.value.coils[0]['fo_try'] && editedItem.value.coils[0]['coil_t'] ){
    const retuenData = await $api('/qt/checker/report/store', {
      method: 'POST',
      body: editedItem.value,
    })

    if (retuenData.success === true) {
      nextTick(() => {
        refForm.value?.reset()
        refForm.value?.resetValidation()
      })

      if (editedIndex.value > -1)
        Object.assign(serverItems.value[editedIndex.value], editedItem.value)
      else
        serverItems.value.push(...retuenData.objs)

      close()
      message.value = retuenData.message
      color.value = retuenData.color
      isSnackbarScrollReverseVisible.value = true
    }
    else {
      editDialog.value = false
      message.value = 'Messaggi.Errore-Salavataggio'
      color.value = 'error'
      isSnackbarScrollReverseVisible.value = true
    }
  }

}

const addProduct = (value: Coils) => {
  editedItem.value?.coils.push(value)
}

const removeProduct = (id: number) => {
  if (editedItem.value?.coils.length > 1)
    editedItem.value?.coils.splice(id, 1)
}

const deleteItemConfirm = async () => {
  const retuenData = await $api(`/qt/checker/report/delete/${deletedItem.value.id}`, {
    method: 'delete',
  })

  message.value = retuenData.message
  color.value = retuenData.color
  isSnackbarScrollReverseVisible.value = true

  // serverItems.value.splice(editedIndex.value, 1)
  await loadItems()
  closeDelete()
}

const getMateriale = async (ol: string) => {
  if (ol.length === 8) {
    const resultData = await useApi<any>(createUrl(`/gp/getMateriale/${ol}`))
    const materiale = resultData.data.value.Prodotto

    if (resultData.data.value.Conversione)
      editedItem.value.num_fo = Math.floor(resultData.data.value.Conversione)

    if (materiale !== undefined) {
      let iniziali = materiale.substr(0, 2)
      if (iniziali === 'BU')
        iniziali = 'BUF'

      if (iniziali === 'CO')
        iniziali = 'COL'

      editedItem.value.stage = iniziali
      olError.value = ''
    }
  }else{
    olError.value = 'Ol Non valido!'
  }
}

const openConformita = async (item: ReprotChecker) => {
  if (item.not_conformity == 0) {
    const materialeData = await useApi<any>(createUrl(`/gp/getMateriale/${item.ol}`))

    NonConformeItem.value.ol = item.ol
    NonConformeItem.value.bobina = item.coil
    NonConformeItem.value.report_id = item.id
    NonConformeItem.value.num_fo = item.num_fo
    NonConformeItem.value.stage = item.stage
    NonConformeItem.value.note = ''
    NonConformeItem.value.chiuso = '0'
    NonConformeItem.value.materiale = materialeData.data.value.Prodotto
  }
  else {
    const resultData = await useApi<any>(createUrl(`/qt/conformita/${item.id}`))

    NonConformeItem.value = { ...resultData.data.value }
    NonConformeItem.value.disable = true
  }
  nonConformitaVisibile.value = true
}

const notConformityButton = (conformita: number) => {
  if (conformita == 1)
    return { color: 'error', text: t('Label.Chiudi') }
  else if (conformita == 2)
    return { color: 'warning', text: t('Label.Da-Chiudere') }
  else if (conformita == 3)
    return { color: 'primary', text: t('Label.Chiuso') }

  return { color: 'success', text: t('Label.Apri') }
}

const saveConformita = async (conformita: object) => {
  isDialogLoading.value = true
  if (conformita.id && conformita.esito !== '0') {
    const retuenData = await $api(`/qt/conformita/closed/${conformita.id}`, {
      method: 'POST',
      body: conformita,
    })

    message.value = retuenData.message
    color.value = retuenData.color
  }
  else {
    const retuenData = await $api('/qt/conformita/store', {
      method: 'POST',
      body: conformita,
    })

    await loadItems()
    message.value = retuenData.message
    color.value = retuenData.color
  }
  isSnackbarScrollReverseVisible.value = true
  isDialogLoading.value = false
}

onMounted(() => {
  loadMacchine()
  loadDifettie()
  loadFibreTipo()
})
</script>

<template>
  <VRow>
    <VCol cols="10">
      <VCard
        title="Filters"
        class="mb-10"
      >
        <VCardText>
          <VRow>
            <!-- 👉 Select Status -->
            <VCol
              cols="12"
              sm="4"
            >
              <AppTextField
                v-model="olFilter"
                :label="$t('Label.Numero Ordine')"
                clearable
                clear-icon="tabler-x"
                @change="loadItems"
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
                @change="loadItems"
              />
            </VCol>

            <VCol
              v-if="view && can(DefineAbilities.qt_checker_reprot_admin.action, DefineAbilities.qt_checker_reprot_admin.subject)"
              cols="12"
              sm="4"
            >
              <AppSelect
                v-model="selectedChecker"
                :items="listCheckers"
                :menu-props="{ transition: 'scroll-y-transition' }"
                :label="$t('Label.Checker')"
                item-title="full_name"
                item-value="id"
                clearable
                clear-icon="tabler-x"
                @focusout="loadItems"
              />
            </VCol>
          </VRow>
        </VCardText>
      </VCard>

      <VCard :title="$t('Label.Lista-Checker-Rapportini')">
        <VCardText class="d-flex flex-wrap py-4 gap-4">
          <VSnackbar
            v-model="isSnackbarScrollReverseVisible"
            transition="scroll-y-reverse-transition"
            location="top central"
            :color="color"
          >
            {{ $t(message) }}
          </VSnackbar>
          <div class="app-user-search-filter d-flex align-center flex-wrap gap-4">
            <!-- 👉 Add user button -->
            <VBtn
              v-if="can(DefineAbilities.qt_checker_reprot_create.action, DefineAbilities.qt_checker_reprot_create.subject)"
              prepend-icon="tabler-plus"
              @click="newItem"
            >
              {{ $t('Label.Nuova Riga') }}
            </VBtn>
          </div>
        </VCardText>

        <VCardText class="d-flex flex-wrap py-4 gap-4">
          <div class="me-3 d-flex gap-3" />
          <VSpacer />

          <div class="app-user-search-filter d-flex align-center flex-wrap gap-4">
            <!-- 👉 Export button -->

            <VBtn
              variant="tonal"
              color="secondary"
              prepend-icon="tabler-screen-share"
              target="_blank"
              :href="`/api/export/checker_report/excel?checker=${selectedChecker}&ol=${olFilter}&periodo=${dataFilter}&lavorazione=5420`"
            >
              Export
            </VBtn>
          </div>
        </VCardText>
        <!-- 👉 Datatable  -->
        <VDataTableServer
          v-model:items-per-page="itemsPerPage"
          v-model:page="page"
          :items="serverItems"
          :items-length="totalItems"
          :headers="headers"
          class="text-no-wrap"
          :loading="loading"
          @update:options="updateOptions"
        >
          <!-- date -->
          <template #item.date_create="{ item }">
            {{ formatDate(item.date_create) }}
          </template>

          <!-- stage -->
          <template #item.stage="{ item }">
            <VChip
              :color="resolveStatusVariant(item.stage).color"
              size="small"
            >
              {{ resolveStatusVariant(item.stage).text }}
            </VChip>
          </template>

          <!-- km -->
          <template #item.km="{ item }" >
           <span></span>
           {{(item.km == 0 ? 0:item.km)}} Km
          </template>

          <!-- No Conforme -->
          <template #item.not_conformity="{ item }">
            <VBtn
              v-if="can(DefineAbilities.qt_non_conformita_create.action, DefineAbilities.qt_non_conformita_create.subject)"
              :color="notConformityButton(item.not_conformity).color"
              @click="openConformita(item)"
            >
              {{ notConformityButton(item.not_conformity).text }}
            </VBtn>
          </template>

          <!-- Actions -->
          <template #item.actions="{ item }">
            <div class="d-flex gap-1">
              <IconBtn
                v-if="item.not_conformity == 0 && can(DefineAbilities.qt_checker_reprot_edit.action, DefineAbilities.qt_checker_reprot_edit.subject)"
                @click="editItem(item)"
              >
                <VIcon icon="tabler-edit" />
              </IconBtn>
              <IconBtn
                v-if="item.not_conformity == 0 && can(DefineAbilities.qt_checker_reprot_deleted.action, DefineAbilities.qt_checker_reprot_deleted.subject)"
                @click="deleteItem(item)"
              >
                <VIcon icon="tabler-trash" />
              </IconBtn>
            </div>
          </template>
        </VDataTableServer>
      </VCard>

      <NonConforme
        v-model:isDialogVisible="nonConformitaVisibile"
        :conformita-data="NonConformeItem"
        :macchine-options="macchineOptions"
        :defetti-options="defettiOptions"
        :fibra-tipo-options="fibraTipoOptions"
        @conformita-data="saveConformita"
      />
    </VCol>
    <VCol cols="2">
      <VCard>
        <!-- 👉Stage Report -->
        <StageReport
          :user="selectedChecker"
          :date-filter="dataFilter"
          :ol-filter="olFilter"
          :lavorazione="5420"
        />
      </VCard>
    </VCol>
  </VRow>

  <!-- 👉 Edit Dialog  -->
  <VDialog
    v-model="editDialog"
    max-width="900px"
  >
    <VCard>
      <VCardTitle>
        <span class="headline">{{ editedItem.id ? $t('Label.Modifica') : $t('Label.Nuovo') }} {{ $t('Label.Rapportino') }}</span>
      </VCardTitle>

      <VCardText>
        <VContainer>
          <VForm
            ref="refForm"
            v-model="isFormValid"
          >
            <VRow>
              <!-- ol -->
              <VCol
                cols="12"
                sm="6"
                md="4"
              >
                <AppTextField
                  v-model="editedItem.ol"
                  :rules="[requiredValidator]"
                  :maxlength="8"
                  :counter="8"
                  :label=" $t('Label.Ol')"
                  :error-messages="olError"
                  required
                  @focusout="getMateriale(editedItem.ol)"
                />
              </VCol>

              <!-- num fo -->
              <VCol
                cols="12"
                sm="6"
                md="3"
              >
                <AppTextField
                  v-model="editedItem.num_fo"
                  :rules="[requiredValidator]"
                  :label="$t('Label.Numero Fibre')"
                  type="number"
                />
              </VCol>

              <!-- stage -->
              <VCol
                cols="12"
                sm="6"
                md="4"
              >
                <AppSelect
                  v-model="editedItem.stage"
                  :rules="[requiredValidator]"
                  :items="selectedOptions"
                  item-title="text"
                  item-value="value"
                  :label="$t('Label.Stage')"
                />
              </VCol>

              <VCol
                cols="12"
                md="11"
              >
                <InvoiceEditable
                  :data="editedItem"
                  :tipo="5420"
                  @push="addProduct"
                  @remove="removeProduct"
                />
              </VCol>

              <VDivider />

              <VCardText class="mx-sm-4">
                <p class="font-weight-medium text-sm text-high-emphasis mb-2">
                  {{ $t('Label.Note') }}
                </p>
                <AppTextarea
                  v-model="editedItem.note"
                  placeholder="Write note here..."
                  :rows="2"
                />
              </VCardText>
            </VRow>
          </VForm>
        </VContainer>
      </VCardText>

      <VCardActions>
        <VSpacer />

        <VBtn
          type="reset"
          color="error"
          variant="outlined"
          @click="close"
        >
          Cancel
        </VBtn>

        <VBtn
          type="submit"
          color="success"
          variant="elevated"
          @click="save"
        >
          Save
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <!-- 👉 Delete Dialog  -->
  <VDialog
    v-model="deleteDialog"
    max-width="500px"
  >
    <VCard>
      <VCardTitle>
        Sei sicuro di voler eliminare?
      </VCardTitle>

      <VCardActions>
        <VSpacer />

        <VBtn
          color="error"
          variant="outlined"
          @click="closeDelete"
        >
          Cancel
        </VBtn>

        <VBtn
          color="success"
          variant="elevated"
          @click="deleteItemConfirm"
        >
          OK
        </VBtn>

        <VSpacer />
      </VCardActions>
    </VCard>
  </VDialog>

  <!-- Dialog Loading -->
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
