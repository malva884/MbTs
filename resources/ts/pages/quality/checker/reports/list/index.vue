<script setup lang="ts">
import {VDataTable} from 'vuetify/labs/VDataTable'
import {VForm} from "vuetify/components/VForm";
import InvoiceEditable from '@/views/quality/checker/report/colisForm.vue'


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

const data: Data[] = []


const fetchData = async () => { alert('si')
  const resultData = await useApi<any>(createUrl('/qt/checker/report'))

  data.push(...resultData.data.value.data)

}

await fetchData()

const isFormValid = ref(false)
const editDialog = ref(false)
const deleteDialog = ref(false)
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
const userList = ref<Data[]>([])

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
  {title: 'Ol', key: 'ol'},
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

// 👉 methods
const editItem = (item: Data) => {
  editedIndex.value = userList.value.indexOf(item)
  item.coils = [{coil:'', coil_t: item.coil, fo_try: item.fo_try}]
  editedItem.value = {...item}
  editDialog.value = true
}

const deleteItem = (item: Data) => {
  editedIndex.value = userList.value.indexOf(item)
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


const onSubmit = () => {
  alert('ok')

}

const save = async () => {
  const retuenData = await $api('/qt/checker/report/store', {
    method: 'POST',
    body: editedItem.value,
  })

 nextTick(() => {
    refForm.value?.reset()
    refForm.value?.resetValidation()
  })
  // refetch Data
  fetchData()

  editDialog.value = false
  editedIndex.value = -3
  editedItem.value = {...defaultItem.value}

}

const addProduct = (value: Coils) => { //console.log(editedItem.value)
  editedItem.value?.coils.push(value)
}

const removeProduct = (id: number) => {
  editedItem.value?.coils.splice(id, 1)
}

const deleteItemConfirm = () => {
  userList.value.splice(editedIndex.value, 1)
  closeDelete()
}

function formatDate(date: Date): string {
  const day = date.getDate().toString().padStart(2, '0');
  const month = (date.getMonth() + 1).toString().padStart(2, '0');
  const year = date.getFullYear();
  return `${day}-${month}-${year}`;
}

onMounted(() => {

  userList.value = JSON.parse(JSON.stringify(data))
})
</script>

<template>
  <VCol cols="8">
    <VCardText class="d-flex flex-wrap py-4 gap-4">
      <div class="app-user-search-filter d-flex align-center flex-wrap gap-4">
        <!-- 👉 Add user button -->
        <VBtn
            prepend-icon="tabler-plus"
            @click="editItem(editedItem)"
        >
          Nuovo Riga
        </VBtn>
      </div>
    </VCardText>
    <!-- 👉 Datatable  -->
    <VDataTable
        :headers="headers"
        :items="userList"
        :items-per-page="50"
    >

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
              @submit.prevent="onSubmit"
          >
            <VRow>
              <!-- ol -->
              <VCol
                  cols="12"
                  sm="6"
                  md="4"
              >
                <VTextField
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
                <VTextField
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
        <VSpacer/>

        <VBtn
            type="reset"
            color="error"
            variant="outlined"
            @click=""
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
