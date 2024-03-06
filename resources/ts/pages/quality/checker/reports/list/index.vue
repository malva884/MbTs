<script setup lang="ts">
import {VDataTable } from 'vuetify/labs/VDataTable'
import {VForm} from "vuetify/components/VForm";
import InvoiceEditable from '@/views/quality/checker/report/colisForm.vue'
import moment from "moment";


definePage({
  meta: {
    action: 'read',
    subject: 'user',
  },
})

export interface Coils {
  coil: string
  fo_try: number
}

export interface Data {
  id: string
  user: number
  date_create: string
  ol: string
  num_fo: number
  coils: Coils[]
  stage: string
  coil: string,
  fo_try: number,
  note: string,
}

const  itemsPerPage =  ref(10)
let loading =  true
const  totalItems =  ref(0)
const  search =  ref('')
const serverItems = ref<Data[]>([])


const data: Data[] = []
//const userList = ref<Data[]>([])


const isFormValid = ref(false)
const editDialog = ref(false)
const deleteDialog = ref(false)
const isSnackbarScrollReverseVisible = ref(false)
const message = ref('')
const color = ref('')
const refForm = ref<VForm>()

const defaultItem = ref<Data>({
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
      fo_try: 0,
    },
  ],
})

const editedItem = ref<Data>(defaultItem.value)
const editedIndex = ref(-1)

const updateOptions = (options: any) => {

}

const loadItems = async (serverOptions: any) => {
  alert('si')
  loading = true
  const resultData = await useApi<any>(createUrl('/qt/checker/report', {
    query: {
      page: serverOptions.page,
      itemsPerPage: serverOptions.itemsPerPage,
      search: serverOptions.search,
      serverOptions,
    },
  }))
  serverItems.value = resultData.data.value.data
  totalItems.value = resultData.data.value.total
  loading = false
}

// status options
const selectedOptions = [
  {text: 'BUF', value: 'BUF'},
  {text: 'SZ', value: 'SZ'},
  {text: 'FC', value: 'FC'},
  {text: 'PE', value: 'PE'},
  {text: 'COL', value: 'COL'},
  {text: 'SF', value: 'SF'},
]

// headers
const headers = [
  {title: 'Data', key: 'date_create'},
  {title: 'Ol', key: 'ol',sortable: true},
  {title: 'Numero Fo', key: 'num_fo'},
  {title: 'Numero Bobbina', key: 'coil'},
  {title: 'Fo Provate', key: 'fo_try'},
  {title: 'Stage', key: 'stage'},
  {title: 'ACTIONS', key: 'actions'},
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
  else
    return {color: 'info', text: 'Applied'}
}

const newItem = () => {
  editedIndex.value = -1
  editedItem.value = {...defaultItem.value}
  editDialog.value = true
}

// 👉 methods
const editItem = (item: Data) => {

  editedIndex.value = serverItems.value.indexOf(item)
  item.coils = [{coil: '', coil_t: item.coil, fo_try: item.fo_try}]
  editedItem.value = {...item}
  editDialog.value = true
}

const deleteItem = (item: Data) => {
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
  const retuenData = await $api('/qt/checker/report/store', {
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
      serverItems.value.push(...retuenData.objs)

    close()
    message.value = retuenData.message
    color.value = retuenData.color
    isSnackbarScrollReverseVisible.value = true
  }else{
    editDialog.value = false
    message.value = 'Messaggi.Errore-Salavataggio';
    color.value = 'error'
  }


}

const addProduct = (value: Coils) => { //console.log(editedItem.value)
  editedItem.value?.coils.push(value)

}

const removeProduct = (id: number) => {
  editedItem.value?.coils.splice(id, 1)
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
  <VCol cols="8">
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
            Nuova Riga
          </VBtn>
        </div>
      </VCardText>
      <!-- 👉 Datatable  -->
      <VDataTable
          v-model:items-per-page="itemsPerPage"
          :headers="headers"
          :items="serverItems"
          :items-length="totalItems"
          :loading="loading"
          :search="search"
          item-value="ol"
          :options="loadItems"
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
      </VDataTable>
    </VCard>
  </VCol>


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

              <VDivider/>

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
</template>
