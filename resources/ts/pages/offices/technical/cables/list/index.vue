<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { useI18n } from 'vue-i18n'
import { VForm } from 'vuetify/components/VForm'
import { can } from '@layouts/plugins/casl'
import DefineAbilities from '@/plugins/casl/DefineAbilities'
import type { Cavo } from '@/views/offices/technical/cables/type'

definePage({
  meta: {
    action: 'list',
    subject: 'Cavi',
  },
})

const { t } = useI18n()
const itemsPerPage = ref(15)
const loading = ref(true)
const refForm = ref<VForm>()
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const page = ref(1)
const serverItems = ref<any>([])
const isSnackbarScrollReverseVisible = ref(false)
const message = ref('')
const color = ref('')
const editDialog = ref(false)
const copiaDialog = ref(false)
const isLoading = ref(false)
const categoriaFilter = ref()
const normaFilter = ref()
const cavoFilter = ref()
const copiaCavo = ref<any>({})
const deletedItem = ref({})
const deleteDialog = ref(false)

const defaultItem = ref<any>({
  id: '',
  user: null,
  codice: '',
  categoria: '',
  categoria_id: null,
  descrizione: '',
  norma: '',
})

function new_defaultItem() {
  defaultItem.value = {
    id: '',
    user: null,
    codice: '',
    categoria: '',
    categoria_id: null,
    descrizione: '',
    norma: '',
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

  const { data: resultData, error } = await useApi<any>(createUrl('/to/cavi/list', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      cavo: cavoFilter.value,
      categoria: categoriaFilter.value,
      norma: normaFilter.value,
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
  { title: t('Label.Cavo'), key: 'codice' },
  { title: t('Table.Descrizione'), key: 'descrizione' },
  { title: t('Table.Categoria'), key: 'categoria' },
  { title: t('Table.Norma'), key: 'norma' },
  { title: t('Label.Data-Creazione'), key: 'created_at' },
  { title: 'ACTIONS', key: 'actions', sortable: false },
]

const categorieOptions = ref([])

const catOptions = async () => {
  const { data: resultData } = await useApi<any>(createUrl('/to/categorie/get_list', {
    query: {
      modulo: 1,
    },
  }))

  const arr = []

  resultData.value.forEach(value => {
    arr.push({ full_name: value.categoria, id: value.id })
  })

  categorieOptions.value = arr
}

catOptions()

const save = async () => {
  isLoading.value = true
  refForm.value?.validate().then(async ({ valid }) => {
    if (valid) {
      let path = 'to/cavi/stored'
      if (editedItem.value.id !== undefined)
        path = `to/cavi/update/${editedItem.value.id}`

      const retuenData = await $api(path, {
        method: 'POST',
        body: editedItem.value,
      })

      nextTick(() => {
        refForm.value?.reset()
        refForm.value?.resetValidation()
      })
      editDialog.value = false
      window.location.href = `/offices/technical/cables/view/${retuenData.obj.id}`
    }
  })
  isLoading.value = false
}

const newItem = () => {
  new_defaultItem()
  editedIndex.value = -1
  editedItem.value = { ...defaultItem.value }
  editDialog.value = true
}

const close = () => {
  isLoading.value = false
  editDialog.value = false
  editedIndex.value = -1
  refForm.value?.reset()
}

const editItem = (item: object) => {
  editedIndex.value = serverItems.value.indexOf(item)

  editedItem.value = { ...item }
  editDialog.value = true
}

const copy = (item: Cavo) => {
  copiaCavo.value = { ...item }
  copiaCavo.value.codice = `${item.codice} (Copy)`
  copiaDialog.value = true
}

const saveCopy = async () => {
  isLoading.value = true
  refForm.value?.validate().then(async ({ valid }) => {
    if (valid) {
      const retuenData = await $api(`to/cavi/duplicate/${copiaCavo.value.id}`, {
        method: 'POST',
        body: copiaCavo.value,
      })

      nextTick(() => {
        refForm.value?.reset()
        refForm.value?.resetValidation()
        loadItems()

        copiaDialog.value = false
        message.value = retuenData.message
        color.value = retuenData.color
        isSnackbarScrollReverseVisible.value = true
      })
    }
  })
  isLoading.value = false
}

const deleteItem = (item: Cavo) => {
  deletedItem.value = { ...item }
  deleteDialog.value = true
}

const deleteItemConfirm = async () => {
  loading.value = true

  const { data: retuenData } = await $api(`/to/cavi/delete/${deletedItem.value.id}`, {
    method: 'delete',
    body: deletedItem.value,
  })

  loadItems()
  loading.value = false
  deleteDialog.value = false
}
</script>

<template>
  <VCol cols="12">
    <VCard
      title="Filters"
      class="mb-6"
    >
      <VCardText>
        <VRow>
          <VCol
            cols="12"
            sm="4"
          >
            <AppTextField
              v-model="cavoFilter"
              :label="$t('Label.Codice')"
              :placeholder="$t('Label.Codice')"
              clearable
              clear-icon="tabler-x"
              @focusout="loadItems"
            />
          </VCol>

          <VCol
            cols="12"
            sm="4"
          >
            <AppTextField
              v-model="normaFilter"
              :label="$t('Label.Norma')"
              :placeholder="$t('Label.Norma')"
              clearable
              clear-icon="tabler-x"
              @focusout="loadItems"
            />
          </VCol>

          <VCol
            cols="12"
            sm="4"
          >
            <AppSelect
              v-model="categoriaFilter"
              :label="$t('Label.Categoria')"
              :placeholder="$t('Label.Categoria')"
              :items="categorieOptions"
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
            v-if="can(DefineAbilities.cavi_create.action, DefineAbilities.cavi_create.subject)"
            prepend-icon="tabler-plus"
            color="success"
            @click="newItem"
          >
            {{ $t('Button.Nuovo-Cavo') }}
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
        @update:options="updateOptions"
      >
        <template #item.codice="{ item }">
          <div class="d-flex align-center">
            <div class="d-flex flex-column">
              <h6 class="text-base">
                <RouterLink
                  :to="{ name: 'offices-technical-cables-view-id', params: { id: item.id } }"
                  class="font-weight-medium text-link"
                >
                  {{ item.codice }}
                </RouterLink>
              </h6>
            </div>
          </div>
        </template>

        <template #item.created_at="{ item }">
          {{ formatDate(item.created_at) }}
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }">
          <div class="d-flex gap-1">
            <IconBtn
              v-if="can(DefineAbilities.cavi_edit.action, DefineAbilities.cavi_edit.subject)"
              color="warning"
              @click="editItem(item)"
            >
              <VIcon icon="tabler-edit" />
            </IconBtn>
            <IconBtn
              v-if="can(DefineAbilities.cavi_create.action, DefineAbilities.cavi_create.subject)"
              color="primary"
              @click="copy(item)"
            >
              <VIcon icon="tabler-copy" />
            </IconBtn>
            <IconBtn
              v-if="can(DefineAbilities.cavideleted.action, DefineAbilities.cavideleted.subject)"
              color="error"
              @click="deleteItem(item)"
            >
              <VIcon icon="tabler-trash" />
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
    <AppCardActions
      v-model:loading="isLoading"
      :title="editedItem.id ? `${$t('Label.Modifica')} Cavo` : `${$t('Label.Nuovo')} Cavo`"
      no-actions
    >
      <VCard>
        <VCardText>
          <VContainer>
            <VForm
              ref="refForm"
              @submit.prevent="save"
            >
              <VRow>
                <!-- 👉 Categoria -->
                <VCol cols="4">
                  <AppSelect
                    v-model="editedItem.categoria_id"
                    :label="$t('Label.Categoria')"
                    :placeholder="$t('Label.Categoria')"
                    :items="categorieOptions"
                    item-title="full_name"
                    item-value="id"
                    :rules="[requiredValidator]"
                  />
                </VCol>

                <!-- 👉 Codice -->
                <VCol cols="4">
                  <AppTextField
                    v-model="editedItem.codice"
                    :label="$t('Label.Codice-Cavo')"
                    :placeholder="$t('Label.Codice-Cavo')"
                    :rules="[requiredValidator]"
                  />
                </VCol>

                <!-- 👉 Norma -->
                <VCol cols="4">
                  <AppTextField
                    v-model="editedItem.norma"
                    :label="$t('Label.Norma')"
                    :placeholder="$t('Label.Norma')"
                  />
                </VCol>

                <!-- 👉 Descrizione -->
                <VCol cols="6">
                  <AppTextField
                    v-model="editedItem.descrizione"
                    :label="$t('Label.Descrizione')"
                    :placeholder="$t('Label.Descrizione')"
                  />
                </VCol>

                <!-- 👉 Nota -->
                <VCol cols="6">
                  <AppTextField
                    v-model="editedItem.nota"
                    :label="$t('Label.Nota')"
                    :placeholder="$t('Label.Nota')"
                  />
                </VCol>
              </VRow>
              <VCardActions class="mt-6">
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
                  @click="refForm?.validate()"
                >
                  Submit
                </VBtn>
              </VCardActions>
            </VForm>
          </VContainer>
        </VCardText>
      </VCard>
    </AppCardActions>
  </VDialog>

  <!-- 👉 Copia Dialog  -->
  <VDialog
    v-model="copiaDialog"
    max-width="1400px"
  >
    <AppCardActions
      v-model:loading="isLoading"
      :title="$t('Label.Duplica-Cavo')"
      no-actions
    >
      <VCard>
        <VCardText>
          <VContainer>
            <VForm
              ref="refForm"
              @submit.prevent="saveCopy"
            >
              <VRow>
                <!-- 👉 Codice -->
                <VCol cols="6">
                  <AppTextField
                    v-model="copiaCavo.codice"
                    :label="$t('Label.Codice-Cavo')"
                    :placeholder="$t('Label.Codice-Cavo')"
                    :rules="[requiredValidator]"
                  />
                </VCol>
              </VRow>
              <VCardActions class="mt-6">
                <VSpacer />

                <VBtn
                  type="reset"
                  color="error"
                  variant="outlined"
                  @click="copiaDialog = false"
                >
                  Cancel
                </VBtn>

                <VBtn
                  type="submit"
                  @click="refForm?.validate()"
                >
                  Submit
                </VBtn>
              </VCardActions>
            </VForm>
          </VContainer>
        </VCardText>
      </VCard>
    </AppCardActions>
  </VDialog>

  <!-- 👉 Delete Dialog  -->
  <VDialog
    v-model="deleteDialog"
    max-width="500px"
  >
    <VCard>
      <VCardTitle>
        {{ $t('Messaggi.Eliminazione-Item') }}
      </VCardTitle>

      <VCardActions>
        <VSpacer />

        <VBtn
          color="error"
          variant="outlined"
          @click="deleteDialog = false"
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
</template>
