<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { useI18n } from 'vue-i18n'
import { VForm } from 'vuetify/components/VForm'
import { can } from '@layouts/plugins/casl'
import DefineAbilities from '@/plugins/casl/DefineAbilities'

definePage({
  meta: {
    action: 'list',
    subject: 'Cavi',
  },
})

const { t } = useI18n()
const itemsPerPage = ref(10)
const loading = ref(1)
const refForm = ref<VForm>()
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const clienteFilter = ref('')
const codiceFilter = ref('')
const statoFilter = ref(0)
const page = ref(1)
const serverItems = ref<any>([])
const isSnackbarScrollReverseVisible = ref(false)
const message = ref('')
const color = ref('')
const editDialog = ref(false)
const isLoading = ref(false)
const isFormValid = ref(false)

const defaultItem = ref<any>({
  id: null,
  ragione_sociale: '',
  codice_sap: '',
  email: '',
  telefono: '',
  provincia: '',
  citta: '',
  cap: '',
  indirizzo: '',
  disabled: 0,
})

function new_defaultItem() {
  defaultItem.value = {
    id: null,
    ragione_sociale: '',
    codice_sap: '',
    email: '',
    telefono: '',
    provincia: '',
    citta: '',
    cap: '',
    indirizzo: '',
    disabled: 0,
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

  const { data: resultData, error } = await useApi<any>(createUrl('/to/clienti/list', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      cliente: clienteFilter.value,
      codice: codiceFilter.value,
      stato: statoFilter.value,
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
  { title: t('Table.Ragione-Sociale'), key: 'ragione_sociale' },
  { title: t('Table.Email'), key: 'email' },
  { title: t('Table.Provincia'), key: 'provincia' },
  { title: t('Table.Città'), key: 'citta' },
  { title: t('Table.Indirizzo'), key: 'indirizzo' },
  { title: t('Table.Disattivo'), key: 'disabled' },
  { title: 'ACTIONS', key: 'actions', sortable: false },
]

const save = async () => {
  if (editedItem.value.ragione_sociale) {
    let path = '/to/clienti/stored/'
    if (editedItem.value.id)
      path = `/to/clienti/update/${editedItem.value.id}`

    isLoading.value = true

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
  editedItem.value.attivo = editedItem.value.attivo === '1'
  editedItem.value.report_gp = editedItem.value.report_gp === '1'
  editedItem.value.disabled = editedItem.value.disabled === '1'
  editDialog.value = true
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
          <!-- 👉 Cliente -->
          <VCol
            cols="12"
            sm="4"
          >
            <AppTextField
              v-model="clienteFilter"
              :label="$t('Label.Cliente')"
              clearable
              clear-icon="tabler-x"
              @focusout="loadItems"
            />
          </VCol>
          <!-- 👉 Codice Sap -->
          <VCol
            cols="12"
            sm="4"
          >
            <AppTextField
              v-model="codiceFilter"
              :label="$t('Label.Codice-Sap')"
              clearable
              clear-icon="tabler-x"
              @focusout="loadItems"
            />
          </VCol>
          <!-- 👉 Stato -->
          <VCol
            cols="12"
            sm="4"
          >
            <AppSelect
              v-model="statoFilter"
              :items="[{ title: 'Disattivo', value: 1 }, { title: 'Attivo', value: 0 }]"
              :label="$t('Label.Stato')"
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
            v-if="can(DefineAbilities.qt_non_conformita_create.action, DefineAbilities.qt_non_conformita_create.subject)"
            prepend-icon="tabler-plus"
            color="success"
            @click="newItem"
          >
            {{$t('Button.Nuovo-Cliente')}}
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
        <template #item.disabled="{ item }">
          <div
            v-if="item.disabled === '1'"
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
          />
        </template>
        <!-- Actions -->
        <template #item.actions="{ item }">
          <div class="d-flex gap-1">
            <IconBtn
              v-if="can(DefineAbilities.cavi_create.action, DefineAbilities.cavi_create.subject)"
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
  <VDialog
    v-model="editDialog"
    max-width="1400px"
  >
    <AppCardActions
      v-model:loading="isLoading"
      :title="editedItem.id ? `${$t('Label.Modifica')} Cliente` : `${$t('Label.Nuovo')} Cliente`"
      no-actions
    >
      <VCard>
        <VCardText>
          <VContainer>
            <VForm
              ref="refForm"
              v-model="isFormValid"
            >
              <VRow>

                <!-- 👉 Ragione Sociale -->
                <VCol cols="6">
                  <AppTextField
                    v-model="editedItem.ragione_sociale"
                    :rules="[requiredValidator]"
                    :label="$t('Label.Ragione-Sociale')"
                    :placeholder="$t('Label.Ragione-Sociale')"
                  />
                </VCol>

                <!-- 👉 Codice Spa -->
                <VCol cols="6">
                  <AppTextField
                    v-model="editedItem.codice_sap"
                    :label="$t('Label.Codice-Sap')"
                    :placeholder="$t('Label.Codice-Sap')"
                  />
                </VCol>

                <!-- 👉 Email -->
                <VCol cols="6">
                  <AppTextField
                    v-model="editedItem.email"
                    :label="$t('Label.Email')"
                    :placeholder="$t('Label.Email')"
                  />
                </VCol>

                <!-- 👉 Telefono -->
                <VCol cols="6">
                  <AppTextField
                    v-model="editedItem.telefono"
                    :label="$t('Label.Telefono')"
                    :placeholder="$t('Label.Telefono')"
                  />
                </VCol>

                <!-- 👉 Provincia -->
                <VCol cols="6">
                  <AppTextField
                    v-model="editedItem.provincia"
                    :label="$t('Label.Provincia')"
                    :placeholder="$t('Label.Provincia')"
                  />
                </VCol>

                <!-- 👉 Citta -->
                <VCol cols="6">
                  <AppTextField
                    v-model="editedItem.citta"
                    :label="$t('Label.Citta')"
                    :placeholder="$t('Label.Citta')"
                  />
                </VCol>

                <!-- 👉 Indirizzo -->
                <VCol cols="6">
                  <AppTextField
                    v-model="editedItem.indirizzo"
                    :label="$t('Label.Indirizzo')"
                    :placeholder="$t('Label.Indirizzo')"
                  />
                </VCol>

                <!-- 👉 Cap -->
                <VCol cols="6">
                  <AppTextField
                    v-model="editedItem.cap"
                    :label="$t('Label.Cap')"
                    :placeholder="$t('Label.Cap')"
                  />
                </VCol>

                <VCol
                  cols="12"
                  class="mt-8"
                >
                  <VSwitch
                    v-model="editedItem.disabled"
                    :label="$t('Label.Disattivo')"
                  />
                </VCol>
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
    </AppCardActions>
  </VDialog>
</template>
