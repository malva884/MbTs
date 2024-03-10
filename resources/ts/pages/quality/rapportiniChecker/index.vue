<script setup lang="ts">
import { VDataTable } from 'vuetify/labs/VDataTable'
import { VForm } from 'vuetify/components/VForm'
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
  startDate: string
  ol: string
  num_fo: number
  coils: Coils[]
  stage: string
}

const data: Data[] = [
  {
    id: 95,
    user: 1,
    startDate: 'asd',
    ol: 'ol',
    num_fo: 12,
    stage: 'BUF',
    coils: [
      {
        coil: '1',
        fo_try: 12,
      },
      {
        coil: '1',
        fo_try: 12,
      },
    ],
  }
]

const isFormValid = ref(false)
const editDialog = ref(false)
const deleteDialog = ref(false)

const defaultItem = ref<Data>({
  id: '1',
  user: 0,
  startDate: '',
  ol: '',
  num_fo: 0,
  coils: [
    {
      coil: '',
      fo_try: 0,
    },
  ],
  stage: '',
})

const editedItem = ref<Data>(defaultItem.value)
const editedIndex = ref(-1)
const userList = ref<Data[]>([])

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
  { title: 'Data', key: 'startDate' },
  { title: 'Ol', key: 'ol' },
  { title: 'Numero Fo', key: 'num_fo' },
  { title: 'Numero Bobbina', key: 'coil' },
  { title: 'Fo Provate', key: 'fo_try' },
  { title: 'Stage', key: 'stage' },
  { title: 'ACTIONS', key: 'actions' },
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
  else
    return { color: 'info', text: 'Applied' }
}

// 👉 methods
const editItem = (item: Data) => {

  editedIndex.value = userList.value.indexOf(item)
  editedItem.value = { ...item }
  editDialog.value = true
}

const deleteItem = (item: Data) => {
  editedIndex.value = userList.value.indexOf(item)
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

const save = () => {
  if (editedIndex.value > -1)
    Object.assign(userList.value[editedIndex.value], editedItem.value)

  else
    userList.value.push(editedItem.value)

  close()
}

const addProduct = (value: Coils) => {
  editedItem.value?.coils.push(value)
}

const removeProduct = (id: number) => {
  editedItem.value?.coils.splice(id, 1)
}

const deleteItemConfirm = () => {
  userList.value.splice(editedIndex.value, 1)
  closeDelete()
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
          @click="editItem"
        >
          Add New
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
        <span class="headline">{{ editedItem.id ? 'Modifica' : 'Nuovo' }} Test</span>
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
                md="3"
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
                md="2"
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
                md="9"
              >
                <InvoiceEditable
                  :data="editedItem"
                  @push="addProduct"
                  @remove="removeProduct"
                />
              </VCol>

              <!-- fo try -->
              <VCol
                cols="12"
                sm="6"
                md="4"
              >
                <VBtn
                  color="success"
                  variant="elevated"
                  @click="addRow(editedItem.coil,editedItem.fo_try)"
                >
                  Save
                </VBtn>
              </VCol>

              <VDivider />

              <VCardText class="mx-sm-4">
                <p class="font-weight-medium text-sm text-high-emphasis mb-2">
                  Note:
                </p>
                <AppTextarea
                  v-model="note"
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
          @click="closeNavigationDrawer"
        >
          Cancel
        </VBtn>


        <VBtn
          type="submit"
          color="success"
          variant="elevated"
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
