<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { useI18n } from 'vue-i18n'
import { VForm } from 'vuetify/components/VForm'
import { can } from '@layouts/plugins/casl'
import DefineAbilities from '@/plugins/casl/DefineAbilities'
import type { Preventivo } from '@/views/offices/technical/quote/type'
import type {Conformita} from "@/views/quality/conformita/type";

definePage({
  meta: {
    action: 'list',
    subject: 'Preventivi',
  },
})

const { t } = useI18n()
const dataCorrente = new Date()
const itemsPerPage = ref(100)
const loading = ref(true)
const refForm = ref<VForm>()
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const page = ref(1)
const serverItems = ref<any>([])
const isSnackbarScrollReverseVisible = ref(false)
const isDialogLoading = ref(false)
const message = ref('')
const color = ref('')
const editDialog = ref(false)
const copiaDialog = ref(false)
const isLoading = ref(false)
const deleteDialog = ref(false)
const clienteFilter = ref()
const numeroFilter = ref()
const annoFilter = ref()
const cavoFilter = ref()
const copiaPreventivo = ref<any>({})
const cliente = ref()
const groupBy = []

// eslint-disable-next-line @typescript-eslint/no-unused-vars
groupBy.push({
  key: 'numero',
  order: 'asc',
})

const defaultItem = ref<Preventivo>({
  id: null,
  user: null,
  numero: '',
  rdo: '',
  paramentro: '',
  cliente_id: '',
  cliente_obj: {
    id: '',
    ragione_sociale: '',
    codice_sap: null,
  },
  cu: null,
  data_rdo: '',
  data_preventivo: '',
  nota: '',
})

function new_defaultItem() {
  defaultItem.value = {
    id: null,
    user: null,
    numero: '',
    rdo: '',
    paramentro: '',
    cliente_id: '',
    cliente_obj: {
      id: '',
      ragione_sociale: '',
      codice_sap: null,
    },
    cu: null,
    data_rdo: '',
    data_preventivo: '',
    nota: '',
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

  const { data: resultData, error } = await useApi<any>(createUrl('/to/preventivi/list', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      numero: numeroFilter.value,
      cliente: clienteFilter.value,
      cavo: cavoFilter.value,
      anno: annoFilter.value,
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
  {
    title: t('Label.Codice'),
    align: 'start',
    sortable: false,
    key: 'codice',
  },

  { title: t('Label.Metri'), key: 'metri' },
  { title: t('Table.Cliente'), key: 'ragione_sociale' },
  { title: t('Label.Data-Creazione'), key: 'data_creazione_cavo' },
  { title: 'ACTIONS', key: 'actions', sortable: false },
]

const clientiOptions = ref([])
const { data: clientiData } = await useApi<any>(createUrl('/to/clienti/get_list/'))

const save = async () => {
  isLoading.value = true
  refForm.value?.validate().then(async ({ valid }) => {
    if (valid) {
      let path = 'to/preventivi/stored'
      // eslint-disable-next-line eqeqeq
      if (editedItem.value.id != undefined)
        path = `to/preventivi/update/${editedItem.value.id}`

      const retuenData = await $api(path, {
        method: 'POST',
        body: editedItem.value,
      })

      nextTick(() => {
        refForm.value?.reset()
        refForm.value?.resetValidation()
        loadItems()
      })
      editDialog.value = false
    }
  })
  isLoading.value = false
}

const newItem = () => {
  new_defaultItem()
  editedIndex.value = -1
  editedItem.value = { ...defaultItem.value }
  editedItem.value.data_rdo = ref(`${dataCorrente.getFullYear()}-${dataCorrente.getMonth()+1}-${dataCorrente.getUTCDate()}`)

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

const copy = (item: Preventivo) => {
  copiaPreventivo.value = { ...item }
  copiaPreventivo.value.numero = `${copiaPreventivo.value.numero} (Copy)`
  copiaDialog.value = true
}

const saveCopy = async () => {
  isDialogLoading.value = true
  refForm.value?.validate().then(async ({ valid }) => {
    if (valid) {
      const retuenData = await $api(`to/preventivi/duplicate/${copiaPreventivo.value.id}`, {
        method: 'POST',
        body: {
          numero: copiaPreventivo.value.numero,
          cliente: cliente.value,
        },
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
  isDialogLoading.value = false
}

const deleteItem = (item: Preventivo) => {
  editedIndex.value = serverItems.value.indexOf(item)
  editedItem.value = { ...item }
  deleteDialog.value = true
}

const closeDelete = () => {
  deleteDialog.value = false
  editedIndex.value = -1
  editedItem.value = { ...defaultItem.value }
}

const deleteItemConfirm = async () => {
  isLoading.value = true

  const retuenData = await $api(`/to/preventivi/delete/${editedItem.value.id}`, {
    method: 'DELETE',
  })

  await loadItems()

  closeDelete()
  isLoading.value = false
  message.value = retuenData.message
  color.value = retuenData.color
  isSnackbarScrollReverseVisible.value = true
}

onMounted(() => {
  clientiOptions.value = clientiData.value
})
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
            sm="3"
          >
            <AppTextField
              v-model="numeroFilter"
              :label="$t('Label.Numero')"
              :placeholder="$t('Label.Numero')"
              clearable
              clear-icon="tabler-x"
              @focusout="loadItems"
            />
          </VCol>

          <VCol
            cols="12"
            sm="3"
          >
            <AppTextField
              v-model="cavoFilter"
              :label="$t('Label.Cavo')"
              :placeholder="$t('Label.Cavo')"
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
              v-model="clienteFilter"
              :label="$t('Label.Clienti')"
              :placeholder="$t('Label.Clienti')"
              :items="clientiOptions"
              :item-title="item => item.ragione_sociale"
              :item-value="item => item.id"
              clearable
              clear-icon="tabler-x"
              @focusout="loadItems"
            />
          </VCol>

          <VCol
            cols="12"
            sm="2"
          >
            <AppTextField
              v-model="annoFilter"
              :label="$t('Label.Anno')"
              :placeholder="$t('Label.Anno')"
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
            v-if="can(DefineAbilities.preventivi_create.action, DefineAbilities.preventivi_create.subject)"
            prepend-icon="tabler-plus"
            color="success"
            @click="newItem"
          >
            {{ $t('Button.Nuovo-Preventivo') }}
          </VBtn>
        </div>
      </VCardText>
      <!-- 👉 Datatable  -->
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        :group-by="groupBy"
        :headers="headers"
        :items="serverItems"
        :items-length="totalItems"
        :loading="loading"
        item-value="numero"
        @update:options="updateOptions"
      >
        <template #group-header="{ item, columns, toggleGroup, isGroupOpen }">
          <tr>
            <td >
              <VBtn
                :icon="isGroupOpen(item) ? '$expand' : '$next'"
                size="small"
                variant="text"
                @click="toggleGroup(item)"
              />
              <RouterLink
                :to="{ name: 'offices-technical-quote-view-id', params: { id: item.items[0].raw.id } }"
                class="font-weight-medium text-link"
              >
                {{ item.value }}
              </RouterLink>
            </td>
            <td></td>
            <td></td>
            <td> {{ item.items[0].raw.ragione_sociale }}</td>
            <td> {{ formatDate(item.items[0].raw.data_preventivo) }}</td>
            <td>
              <div class="d-flex gap-1">
                <IconBtn
                  v-if="can(DefineAbilities.preventivi_edit.action, DefineAbilities.preventivi_edit.subject)"
                  color="warning"
                  @click="editItem(item.items[0].raw)"
                >
                  <VIcon icon="tabler-edit"/>
                </IconBtn>
                <IconBtn
                  v-if="can(DefineAbilities.preventivi_create.action, DefineAbilities.preventivi_create.subject)"
                  color="primary"
                  @click="copy(item.items[0].raw)"
                >
                  <VIcon icon="tabler-copy"/>
                </IconBtn>
                <IconBtn
                  v-if="can(DefineAbilities.preventivi_create.action, DefineAbilities.preventivi_create.subject)"
                  color="error"
                  @click="deleteItem(item.items[0].raw)"
                >
                  <VIcon icon="tabler-trash"/>
                </IconBtn>
              </div>
            </td>
          </tr>
        </template>
        <template #item.data_creazione_cavo="{ item }">
          {{ formatDate(item.data_creazione_cavo) }}
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
      :title="editedItem.id ? `${$t('Label.Modifica')} Preventivo` : `${$t('Label.Nuovo')} Preventivo`"
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
                <!-- 👉 Numero -->
                <VCol cols="4">
                  <AppTextField
                    v-model="editedItem.numero"
                    :label="$t('Label.Numero')"
                    :placeholder="$t('Label.Numero')"
                    :rules="[requiredValidator]"
                  />
                </VCol>
                <!-- 👉 CU -->
                <VCol cols="4">
                  <AppTextField
                    v-model="editedItem.cu"
                    type="number"
                    :label="$t('Label.Base-Cu')"
                    :placeholder="$t('Label.Base-Cu')"
                    :rules="[requiredValidator]"
                  />
                </VCol>
                <!-- 👉 Parametro -->
                <VCol cols="4">
                  <AppTextField
                    v-model="editedItem.parametro"
                    type="number"
                    :label="$t('Label.Parametro')"
                    :placeholder="$t('Label.Parametro')"
                    :rules="[requiredValidator]"
                  />
                </VCol>
                <!-- 👉 Cliente -->
                <VCol cols="12">
                  <AppSelect
                    v-model="editedItem.cliente_id"
                    :label="$t('Label.Cliente')"
                    :placeholder="$t('Label.Cliente')"
                    :items="clientiOptions"
                    :item-title="item => item.ragione_sociale"
                    :item-value="item => item.id"
                    :rules="[requiredValidator]"
                  />
                </VCol>

                <!-- 👉 Rdo -->
                <VCol cols="6">
                  <AppTextField
                    v-model="editedItem.rdo"
                    :label="$t('Label.Rdo')"
                    :placeholder="$t('Label.Rdo')"
                    :rules="[requiredValidator]"
                  />
                </VCol>

                <!-- 👉 Data Rdo -->
                <VCol cols="6">
                  <AppTextField
                    v-model="editedItem.data_rdo"
                    :label="$t('Label.Del')"
                    :placeholder="$t('Label.Del')"
                    :rules="[requiredValidator]"
                    clearable
                    clear-icon="tabler-x"
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
                <!-- 👉 Numero -->
                <VCol cols="6">
                  <AppTextField
                    v-model="copiaPreventivo.numero"
                    :label="$t('Label.Numero-Preventivo')"
                    :placeholder="$t('Label.Numero-Preventivo')"
                    :rules="[requiredValidator]"
                  />
                </VCol>
                <VCol
                  cols="12"
                  sm="6"
                >
                  <AppSelect
                    v-model="cliente"
                    :label="$t('Label.Clienti')"
                    :placeholder="$t('Label.Clienti')"
                    :items="clientiOptions"
                    :item-title="item => item.ragione_sociale"
                    :item-value="item => item.id"
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
    <AppCardActions
      v-model:loading="isLoading"
      title="Eliminazione Preventivo:"
      no-actions
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
            Si
          </VBtn>

          <VSpacer />
        </VCardActions>
      </VCard>
    </AppCardActions>
  </VDialog>

  <!-- Dialog -->
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
