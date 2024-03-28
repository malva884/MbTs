<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { VForm } from 'vuetify/components/VForm'
import { can } from '@layouts/plugins/casl'
import InvoiceEditable from '@/views/quality/checker/report/colisForm.vue'
import DefineAbilities from '@/plugins/casl/DefineAbilities'
import type { Coils, ReprotChecker } from '@/views/quality/checker/type'
import type { Conformita } from '@/views/quality/conformita/type'
import AperturaNonConforme from '@/pages/quality/checker/reports/list/AperturaNonConforme.vue'
import NonConforme from "@/components/dialogs/NonConforme.vue";

definePage({
  meta: {
    action: 'list',
    subject: 'Qualita-Checker-Report',
  },
})

const loading = ref(false)
const view = ref(false)
const serverItems = ref<ReprotChecker[]>([])
const itemsPerPage = ref(10)
const page = ref(1)
const sortBy = ref()
const orderBy = ref()
const olFilter = ref()
const totalItems = ref(0)
const listCheckers = ref({})
const isFormValid = ref(false)
const editDialog = ref(false)
const deleteDialog = ref(false)
const isSnackbarScrollReverseVisible = ref(false)
const message = ref('')
const color = ref('')
const selectedChecker = ref('')
const refForm = ref<VForm>()
const isDialogVisible = ref(false)
const nonConformitaVisibile = ref(false)
const NonConformeItem = ref({})

const defaultItem = ref<ReprotChecker>({
  id: null,
  user: null,
  date_create: '',
  ol: '',
  num_fo: null,
  stage: '',
  note: '',
  not_conformity: false,
  coils: [
    {
      coil: '',
      coil_t: '',
      fo_try: null,
    },
  ],
})

const defaultConformita = ref<Conformita>({
  report_id: '',
  ol: '',
  materiale: '',
  num_fo: null,
  stage: '',
  bobina: '',
  note: '',
  macchina: null,
  difetto: null,
  fibre: '',
  soluzione: '',
  chiuso: false,
  diametro: null,
  tipologia_fibra: '',
  operator: '',
  physical_l: null,
  optical_l: null,
  tipologia_difetto: '',
})

const conformitaData = ref({})

const editedItem = ref<ReprotChecker>(defaultItem.value)
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

  const { data: resultData, error } = await useApi<any>(createUrl('/qt/checker/report', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      checker: selectedChecker.value,
      ordine: olFilter.value,
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
  { title: 'Data', key: 'date_create' },
  { title: 'Ol', key: 'ol' },
  { title: 'Numero Fo', key: 'num_fo' },
  { title: 'Numero Bobina', key: 'coil', sortable: false },
  { title: 'Fo Provate', key: 'fo_try', sortable: false },
  { title: 'Stage', key: 'stage' },
  { title: 'Non Conforme', key: 'not_conformity', sortable: false },
  { title: 'ACTIONS', key: 'actions', sortable: false },
]

const resolveStatusVariant = (stage: string) => {
  if (stage === 'BUF')
    return { color: 'primary', text: 'BUF' }
  else if (stage === 'SZ')
    return { color: 'success', text: 'SZ' }
  else if (stage === 'FC')
    return { color: 'error', text: 'FC' }
  else if (stage === 'PE')
    return { color: 'warning', text: 'PE' }
  else if (stage === 'COL')
    return { color: 'secondary', text: 'COL' }
  else
    return { color: 'light', text: 'SF' }
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
    coils: [
      {
        coil: '',
        coil_t: '',
        fo_try: null,
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
  item.coils = [{ coil: '', coil_t: item.coil, fo_try: item.fo_try }]
  editedItem.value = { ...item }
  editDialog.value = true
}

const deleteItem = (item: ReprotChecker) => {
  editedIndex.value = serverItems.value.indexOf(item)
  editedItem.value = { ...item }
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
  }
}

const addProduct = (value: Coils) => {
  editedItem.value?.coils.push(value)
}

const removeProduct = (id: number) => {
  if (editedItem.value?.coils.length > 1)
    editedItem.value?.coils.splice(id, 1)
}

const deleteItemConfirm = () => {
  serverItems.value.splice(editedIndex.value, 1)
  closeDelete()
}

const getMateriale = async (ol: string) => {
  const resultData = await useApi<any>(createUrl(`/gp/getMateriale/${ol}`))
  const materiale = resultData.data.value.Prodotto
  const descrizione = resultData.data.value.Descrizione

  if (materiale !== undefined) {
    const tmp = descrizione.split(' ', 2)

    editedItem.value.num_fo = tmp[1]
    let iniziali = materiale.substr(0, 2)
    if (iniziali === 'BU')
      iniziali = 'BUF'

    if (iniziali === 'CO')
      iniziali = 'COL'

    editedItem.value.stage = iniziali
  }
}

const openConformita = async (item: ReprotChecker) => {

  if (item.not_conformity == 0) {
    const materialeData = await useApi<any>(createUrl(`/gp/getMateriale/${item.ol}`))

    NonConformeItem.value = item
    NonConformeItem.value.bobina = item.coil
    NonConformeItem.value.report_id = item.id
    NonConformeItem.value.note = ''
    NonConformeItem.value.id = ''
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
    return { color: 'warning', text: 'Chiudi' }
  else if (conformita == 2)
    return { color: 'success', text: 'Chiuso' }

  return { color: 'primary', text: 'Apri' }
}

const saveConformita = async (conformita: object) => {
  console.log(conformita)

  if (conformita.id && conformita.chiuso === '1') {
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
                @focusout="loadItems"
              />
            </VCol>

            <VCol
              v-if="view"
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
      <VCard>
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
              Nuova Riga
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

          <!-- No Conforme -->
          <template #item.not_conformity="{ item }">
            <VBtn :color="notConformityButton(item.not_conformity).color" @click="openConformita(item)">
              {{ notConformityButton(item.not_conformity).text }}
            </VBtn>

          </template>

          <!-- Actions -->
          <template #item.actions="{ item }">
            <div class="d-flex gap-1">
              <IconBtn
                v-if="can(DefineAbilities.qt_checker_reprot_edit.action, DefineAbilities.qt_checker_reprot_edit.subject)"
                @click="editItem(item)"
              >
                <VIcon icon="tabler-edit" />
              </IconBtn>
              <IconBtn
                v-if="can(DefineAbilities.qt_checker_reprot_deleted.action, DefineAbilities.qt_checker_reprot_deleted.subject)"
                @click="deleteItem(item.raw)"
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
  </VRow>



  <!-- 👉 Edit Dialog  -->
  <VDialog
    v-model="editDialog"
    max-width="900px"
  >
    <VCard>
      <VCardTitle>
        <span class="headline">{{ editedItem.id ? 'Modifica' : 'Nuovo' }} Rapportino</span>
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
                  label="Ol"
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
                  label="Numero Fibre"
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
                  label="Stage"
                />
              </VCol>

              <VCol
                cols="12"
                md="11"
              >
                <InvoiceEditable
                  :data="editedItem"
                  @push="addProduct"
                  @remove="removeProduct"
                />
              </VCol>

              <VDivider />

              <VCardText class="mx-sm-4">
                <p class="font-weight-medium text-sm text-high-emphasis mb-2">
                  Note:
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

  <!-- Dialog -->
  <VDialog
    v-model="isDialogVisible"
    class="v-dialog-sm"
  >
    <!-- Dialog close btn -->
    <DialogCloseBtn @click="isDialogVisible = false" />

    <VCard title="Apertura Non Conformità">
      <VCardText>
        Sei sicuro di voler aprire una non conformità per questa bobina?
      </VCardText>

      <VCardText class="d-flex justify-end flex-wrap gap-3">
        <VBtn
          variant="tonal"
          color="secondary"
          @click="isDialogVisible = false"
        >
          No
        </VBtn>
        <VBtn
          color="success"
          @click="isDialogTwoShow = !isDialogTwoShow"
        >
          Si
        </VBtn>
      </VCardText>
    </VCard>
  </VDialog>

  <!-- Dialog 2 -->
  <VDialog
    v-model="isDialogTwoShow"
    class="v-dialog-sm"
  >
    <!-- Dialog close btn -->
    <DialogCloseBtn @click="isDialogTwoShow = false" />

    <VCard title="Dialog 2">
      <VCardText>I'm a nested dialog.</VCardText>
      <VCardText class="d-flex flex-wrap gap-3">
        <VSpacer />
        <VBtn @click="isDialogTwoShow = false">
          Close
        </VBtn>
      </VCardText>
    </VCard>
  </VDialog>
</template>


