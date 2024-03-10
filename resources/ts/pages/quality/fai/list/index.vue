<script setup lang="ts">
import { VForm } from 'vuetify/components/VForm'
import { VDataTableServer } from 'vuetify/components/VDataTable'
import type { Fai } from '@/views/quality/fai/type'
import moment from 'moment'

definePage({
  meta: {
    action: 'read',
    subject: 'user',
  },
})


const itemsPerPage = ref(10)
let loading = true
const totalItems = ref(0)
const search = ref('')
const serverItems = ref<Fai[]>([])
const isFormValid = ref(false)
const isFormClodesValid = ref(false)
const isUserInfoEditDialogVisible = ref(false)
const editDialog = ref(false)
const resultFaiDialog = ref(false)
const deleteDialog = ref(false)
const isSnackbarScrollReverseVisible = ref(false)
const message = ref('')
const color = ref('')
const refForm = ref<VForm>()

const defaultItem = ref<Fai>({
  id: '',
  user: 0,
  data_creazione: '',
  data_chiusura: '',
  ol: '',
  numero_fai: '',
  descrizione: '',
  cod_cavo: '',
  cod_materiale: '',
  esito: 0,
  path_drive: '',
  risultato: 0,
})

const editedItem = ref<Fai>(defaultItem.value)
const editedIndex = ref(-1)

const loadItems = async (serverOptions: any) => {
  const sort = ref('')
  if (serverOptions.sortBy[0]?.order) {
    if (serverOptions.sortBy[0]?.order === 'asc')
      sort.value = `-${serverOptions.sortBy[0]?.key}`
    else
      sort.value = serverOptions.sortBy[0]?.key
  }

  loading = true

  const resultData = await useApi<Fai>(createUrl('/qt/fai/list', {
    query: {
      page: serverOptions.page,
      itemsPerPage: serverOptions.itemsPerPage,
      search: serverOptions.search,
      sort: sort.value,
    },
  }))

  if (resultData.data.value !== null) {
    serverItems.value = resultData.data.value.data
    totalItems.value = resultData.data.value.total
  } else {
    serverItems.value = []
    totalItems.value = 0
  }
  loading = false
}

// status options
const selectedOptions = [
  { text: 'Positivo', value: 1 },
  { text: 'Negativo', value: 2 },
]

// headers
const headers = [
  { title: 'Fai', key: 'numero_fai' },
  { title: 'Data Apertura', key: 'data_creazione' },
  { title: 'Data Chiusura', key: 'data_chiusura' },
  { title: 'Risultato Fai', key: 'risultato' },
  { title: 'Descrizione', key: 'descrizione', sortable: false },
  { title: 'Ordine Lavoro', key: 'ol' },
  { title: 'Cod. Cavo', key: 'cod_cavo', sortable: false },
  { title: 'Cod. Materiale', key: 'cod_materiale', sortable: false },
  { title: 'Esito', key: 'esito' },
  { title: 'ACTIONS', key: 'actions', sortable: false },
]

const resolveStatusVariant = (risultato: number) => {
  if (risultato == 1)
    return { color: 'success', text: 'Positivo' }
  else if (risultato == 2)
    return { color: 'error', text: 'Negativo' }
  else
    return { color: '', text: risultato }
}

// eslint-disable-next-line camelcase
function new_defaultItem() {
  defaultItem.value = {
    id: '',
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

const openInfoFaiDialog = (item: Fai) => {
  editedIndex.value = serverItems.value.indexOf(item)

  editedItem.value = { ...item }
  isUserInfoEditDialogVisible.value = true
}

const newItem = () => {
  new_defaultItem()
  editedIndex.value = -1
  editedItem.value = { ...defaultItem.value }
  editDialog.value = true
}

// 👉 methods
const editItem = (item: Fai) => {
  editedIndex.value = serverItems.value.indexOf(item)

  editedItem.value = { ...item }
  editDialog.value = true
}

const deleteItem = (item: Fai) => {
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
  const retuenData = await $api('/qt/fai/store', {
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
      serverItems.value.push(retuenData.obj)

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

const deleteItemConfirm = () => {
  serverItems.value.splice(editedIndex.value, 1)
  closeDelete()
}

const getMateriale = async (ol: string) => {
  const resultData = await useApi<any>(createUrl(`/gp/getMateriale/${ol}`))

  editedItem.value.cod_materiale = resultData.data.value.Prodotto
}

const openResultDialog = (item: Fai) => {
  resultFaiDialog.value = false
  editedIndex.value = serverItems.value.indexOf(item)

  editedItem.value = { ...item }
  resultFaiDialog.value = true
}

const closeFaiItem = async () => {
  const retuenData = await $api(`/qt/fai/closed/${editedItem.value.id}`, {
    method: 'POST',
    body: {
      rusultato: editedItem.value.risultato,
    },
  })

  if (retuenData.success === true) {
    nextTick(() => {
      refForm.value?.reset()
      refForm.value?.resetValidation()
    })

    if (editedIndex.value > -1)
      Object.assign(serverItems.value[editedIndex.value], editedItem.value)

    resultFaiDialog.value = false
    message.value = retuenData.message
    color.value = retuenData.color
  } else {
    resultFaiDialog.value = false
    message.value = 'Messaggi.Errore-Salavataggio'
    color.value = 'error'
  }
}

const onReset = () => {
  resultFaiDialog.value = false
}

function formatDate(date: string): string {
  return moment(String(date)).format('MM/DD/YYYY')
}

function openDrivePage(path: string) {
  window.open(`https://drive.google.com/drive/u/0/folders/${path}`, '_blank')
}
</script>

<template>
  <VCol cols="12">
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
            prepend-icon="tabler-plus"
            color="success"
            @click="newItem"
          >
            Apri Fai
          </VBtn>
        </div>
      </VCardText>
      <!-- 👉 Datatable  -->
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        :headers="headers"
        :items="serverItems"
        :items-length="totalItems"
        :loading="loading"
        :search="search"
        @update:options="loadItems"
      >

        <!-- User -->
        <template #item.numero_fai="{ item }">
          <div class="d-flex align-center">
            <div class="d-flex flex-column">
              <span class="text-sm text-medium-emphasis text-success" @click="openInfoFaiDialog(item)">{{ item.numero_fai }}</span>
            </div>
          </div>
        </template>

        <!-- date -->
        <template #item.data_creazione="{ item }">
          <div class="d-flex gap-1">
            {{ formatDate(item.data_creazione) }}
          </div>
        </template>

        <!-- date -->
        <template #item.data_chiusura="{ item }">
          <div
            v-if="item.data_chiusura"
            class="d-flex gap-1"
          >
            {{ formatDate(item.data_chiusura) }}
          </div>
          <div
            v-else
            class="d-flex gap-1"
          >
            <VBtn
              prepend-icon="tabler-square-rounded-x"
              @click="openResultDialog(item)"
            >
              Chiudi
            </VBtn>
          </div>
        </template>

        <!-- risultato -->
        <template #item.risultato="{ item }">
          <div class="d-flex gap-1" v-if="item.risultato">
            <VChip
              :color="resolveStatusVariant(item.risultato).color"
              size="small"
            >
              {{ resolveStatusVariant(item.risultato).text }}
            </VChip>
          </div>
        </template>

        <!-- descrizione -->
        <template #item.descrizione="{ item }">
          <div class="d-flex gap-1" v-if="item.descrizione">
            {{ item.descrizione.substr(0, 50) }} ...
          </div>
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }">
          <div class="d-flex gap-1">
            <IconBtn
              color="primary"
              @click="openDrivePage(item.path_drive)"
            >
              <VIcon icon="tabler-brand-google-drive"/>
            </IconBtn>
            <IconBtn
              color="warning"
              @click="editItem(item)"
            >
              <VIcon icon="tabler-edit"/>
            </IconBtn>
            <IconBtn
              color="error"
              @click="deleteItem(item.raw)"
            >
              <VIcon icon="tabler-trash"/>
            </IconBtn>
          </div>
        </template>
      </VDataTableServer>
    </VCard>
  </VCol>

  <!-- 👉 Edit Dialog  -->
  <VDialog
    v-model="editDialog"
    max-width="1400px"
  >
    <VCard>
      <VCardTitle>
        <span class="headline">{{ editedItem.id ? 'Modifica' : 'Nuovo' }} Fai</span>
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
                md="2"
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

              <!-- cod_materiale -->
              <VCol
                cols="12"
                sm="6"
                md="3"
              >
                <AppTextField
                  v-model="editedItem.cod_materiale"
                  :rules="[requiredValidator]"
                  label="Codice Materiale"
                  type="string"
                />
              </VCol>

              <!-- cod_cavo -->
              <VCol
                cols="12"
                sm="6"
                md="3"
              >
                <AppTextField
                  v-model="editedItem.cod_cavo"
                  label="Codice Cavo"
                  type="string"
                />
              </VCol>

              <!-- descrizione -->
              <VCol
                cols="12"
                sm="6"
                md="4"
              >
                <AppTextarea
                  v-model="editedItem.descrizione"
                  label="Descrizione"
                  placeholder=""
                  :rows="2"
                />
              </VCol>
            </VRow>
          </VForm>
        </VContainer>
      </VCardText>

      <VCardActions>
        <VSpacer/>

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

  <!-- 👉 Closed Fai Dialog  -->
  <VDialog
    :width="$vuetify.display.smAndDown ? 'auto' : 600"
    :model-value="resultFaiDialog"
  >
    <!-- 👉 dialog close btn -->
    <DialogCloseBtn @click="onReset"/>

    <VCard class="pa-sm-8 pa-5">
      <!-- 👉 Title -->
      <VCardItem class="text-center">
        <VCardTitle class="text-h5">
          Chiudi Fai
        </VCardTitle>
      </VCardItem>

      <VCardText class="mt-1">
        <!-- 👉 Form -->
        <VForm
          ref="refForm"
          v-model="isFormClodesValid"
        >
          <VAlert
            type="info"
            title="Fai Numero:"
            class="mb-6"
          >
            {{ editedItem.numero_fai }}
          </VAlert>

          <!-- 👉 Role name -->
          <div class="d-flex align-end gap-3 mb-3">
            <AppSelect
              v-model="editedItem.risultato"
              :rules="[requiredValidator]"
              :items="selectedOptions"
              item-title="text"
              item-value="value"
              label="Risultato"
            />

            <VBtn type="submit" @click="closeFaiItem">
              Salva
            </VBtn>
          </div>
        </VForm>
      </VCardText>
    </VCard>
  </VDialog>

  <!-- 👉 Edit user info dialog -->
  <InfoFaiDialog
    v-model:isDrawerOpen="isUserInfoEditDialogVisible"
    :fai-data="editedItem"
  />
</template>

<style>
.v-table > .v-table__wrapper > table > tbody > tr > td, .v-table > .v-table__wrapper > table > thead > tr > td, .v-table > .v-table__wrapper > table > tfoot > tr > td {
  font-size: 15px !important;
}
</style>
