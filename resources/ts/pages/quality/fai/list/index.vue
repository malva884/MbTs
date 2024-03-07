<script setup lang="ts">
import {VForm} from "vuetify/components/VForm";
import {VDataTableServer} from "vuetify/components/VDataTable"
import InvoiceEditable from '@/views/quality/checker/report/colisForm.vue'
import moment from "moment";


definePage({
  meta: {
    action: 'read',
    subject: 'user',
  },
})

export interface Fai {
  id: string
  user: number
  data_creazione: string
  data_chiusura: string
  ol: string
  numero_fai: string
  descrizione: string
  cod_cavo: string,
  cod_materiale: string,
  esito: number,
  path_drive: string,
}

const itemsPerPage = ref(10)
let loading = true
const totalItems = ref(0)
const search = ref('')
const serverItems = ref<Fai[]>([])
const isFormValid = ref(false)
const editDialog = ref(false)
const deleteDialog = ref(false)
const isSnackbarScrollReverseVisible = ref(false)
const message = ref('')
const color = ref('')
const refForm = ref<VForm>()

const defaultItem = ref<Data>({
  id: 0,
  user: 0,
  data_creazione: '',
  data_chiusura: '',
  ol: '',
  numero_fai: '',
  descrizione: '',
  cod_cavo: '',
  cod_materiale: '',
  esito: '',
  path_drive: '',
})

const editedItem = ref<Data>(defaultItem.value)
const editedIndex = ref(-1)


const loadItems = async (serverOptions: any) => {
  const sort = ref('');
  if (serverOptions.sortBy[0]?.order) {
    if (serverOptions.sortBy[0]?.order === 'asc')
      sort.value = "-" + serverOptions.sortBy[0]?.key
    else
      sort.value = serverOptions.sortBy[0]?.key
  }


  loading = true
  const resultData = await useApi<Fai>(createUrl('/qt/fai/list', {
    query: {
      page: serverOptions.page,
      itemsPerPage: serverOptions.itemsPerPage,
      search: serverOptions.search,
      sort: sort.value
      //serverOptions,
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
  {text: 'Positivo', value: 1},
  {text: 'Negativo', value: 2},
]

// headers
const headers = [
  {title: 'Fai', key: 'numero_fai'},
  {title: 'Data Apertura', key: 'data_creazione'},
  {title: 'Data Chiusura', key: 'data_chiusura'},
  {title: 'Risultato Fai', key: 'risultato'},
  {title: 'Descrizione', key: 'descrizione'},
  {title: 'Ordine Lavoro', key: 'ol'},
  {title: 'Cod. Cavo', key: 'cod_cavo', sortable: false},
  {title: 'Cod. Materiale', key: 'cod_materiale', sortable: false},
  {title: 'Esito', key: 'esito'},
  {title: 'ACTIONS', key: 'actions', sortable: false},
]

const resolveStatusVariant = (stage: string) => {
  if (stage === 'BUF')
    return {color: 'primary', text: 'BUF'}
  else if (stage === 'SZ')
    return {color: 'success', text: 'SZ'}
  else if (stage === 'FC')
    return {color: 'error', text: 'FC'}
  else if (stage === 'PE')
    return {color: 'warning', text: 'PE'}
  else if (stage === 'COL')
    return {color: 'secondary', text: 'COL'}
  else
    return {color: 'light', text: 'SF'}
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
  console.log(defaultItem.value.coils)
  editedItem.value = {...defaultItem.value}
  editDialog.value = true
}

// 👉 methods
const editItem = (item: Fai) => {

  editedIndex.value = serverItems.value.indexOf(item)

  editedItem.value = {...item}
  editDialog.value = true
}

const deleteItem = (item: Fai) => {
  editedIndex.value = serverItems.value.indexOf(item)
  editedItem.value = {...item}
  deleteDialog.value = true
}


const close = () => {
  editDialog.value = false
  editedIndex.value = -1
  editedItem.value = {...defaultItem.value}
}

const closeDelete = () => {
  deleteDialog.value = false
  editedIndex.value = -1
  editedItem.value = {...defaultItem.value}
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

    if (editedIndex.value > -1) {
      console.log(editedItem)
      Object.assign(serverItems.value[editedIndex.value], editedItem.value)
    } else
      serverItems.value.push(retuenData.obj)

    close()
    message.value = retuenData.message
    color.value = retuenData.color
    isSnackbarScrollReverseVisible.value = true
  } else {
    editDialog.value = false
    message.value = 'Messaggi.Errore-Salavataggio';
    color.value = 'error'
  }


}


const deleteItemConfirm = () => {
  serverItems.value.splice(editedIndex.value, 1)
  closeDelete()
}


function formatDate(date: string): string {
  return moment(String(date)).format('MM/DD/YYYY')
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
              @click="newItem()"
          >
            Nuovo Fai
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

        <!-- date -->
        <template #item.data_creazione="{ item }">
          {{ formatDate(item.data_creazione) }}
        </template>

        <!-- date -->
        <template #item.data_chiusura="{ item }">
          {{ formatDate(item.data_chiusura) }}
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }">
          <div class="d-flex gap-1">
            <IconBtn @click="editItem(item)">
              <VIcon icon="tabler-edit"/>
            </IconBtn>
            <IconBtn @click="deleteItem(item.raw)">
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
                    :rules="[requiredValidator]"
                    label="Codice Cavo"
                    type="string"
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

              <!-- descrizione -->
              <VCol
                  cols="12"
                  sm="6"
                  md="4"
              >
                <AppTextarea
                    v-model="editedItem.descrizione"
                    label="Descrizione"
                    placeholder="Write note here..."
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
        <VSpacer/>

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

        <VSpacer/>
      </VCardActions>
    </VCard>
  </VDialog>

  <!-- Dialog -->
  <VDialog
      v-model="isDialogVisible"
      class="v-dialog-sm"
  >
    <!-- Dialog close btn -->
    <DialogCloseBtn @click="isDialogVisible = false"/>

    <VCard title="Apertura Non Conformità">
      <VCardText>
        Sei sicuro di voler aprire una non conformità per questa bobbina?
      </VCardText>

      <VCardText class="d-flex justify-end flex-wrap gap-3">
        <VBtn
            variant="tonal"
            color="secondary"
            @click="isDialogVisible = false"
        >
          No
        </VBtn>
        <VBtn @click="isDialogTwoShow = !isDialogTwoShow" color="success">
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
    <DialogCloseBtn @click="isDialogTwoShow = false"/>

    <VCard title="Dialog 2">
      <VCardText>I'm a nested dialog.</VCardText>
      <VCardText class="d-flex flex-wrap gap-3">
        <VSpacer/>
        <VBtn @click="isDialogTwoShow = false">
          Close
        </VBtn>
      </VCardText>
    </VCard>
  </VDialog>
</template>
