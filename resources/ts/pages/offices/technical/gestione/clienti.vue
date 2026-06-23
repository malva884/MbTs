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
const loading = ref(true)
const refForm = ref<VForm>()
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const clienteFilter = ref('')
const codiceFilter = ref('')
const statoFilter = ref(0)
const page = ref(1)
const serverItems = ref<any>([])
const snackbar = ref({ show: false, color: '', message: '' })
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
    snackbar.value = { show: true, color: retuenData.color, message: retuenData.message }

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
  <div class="workspace-container w-100 d-flex flex-column pa-4 gap-3">
    <VSnackbar v-model="snackbar.show" :color="snackbar.color" location="top center" :timeout="3000">
      {{ $t(snackbar.message) }}
    </VSnackbar>

    <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
      <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
        <div class="d-flex align-center gap-2">
          <VIcon icon="tabler-users" size="24" color="primary" />
          <div>
            <div class="text-h6 font-weight-medium">Clienti</div>
            <div class="text-caption text-medium-emphasis">{{ totalItems }} clienti in anagrafica</div>
          </div>
        </div>
        <VBtn
          v-if="can(DefineAbilities.cavi_create.action, DefineAbilities.cavi_create.subject)"
          prepend-icon="tabler-plus"
          color="primary"
          variant="flat"
          density="comfortable"
          class="px-3"
          @click="newItem"
        >
          Nuovo Cliente
        </VBtn>
      </VCardText>
      <VDivider />
      <VCardText class="pa-3">
        <VRow class="mb-2">
          <VCol cols="12" sm="4">
            <AppTextField
              v-model="clienteFilter"
              label="Cliente"
              placeholder="Cliente"
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-search"
              @keyup.enter="loadItems"
              @click:clear="loadItems"
            />
          </VCol>
          <VCol cols="12" sm="4">
            <AppTextField
              v-model="codiceFilter"
              label="Codice SAP"
              placeholder="Codice SAP"
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-search"
              @keyup.enter="loadItems"
              @click:clear="loadItems"
            />
          </VCol>
          <VCol cols="12" sm="4">
            <AppSelect
              v-model="statoFilter"
              label="Stato"
              placeholder="Tutti"
              :items="[{ title: 'Attivo', value: 0 }, { title: 'Disattivo', value: 1 }]"
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-filter"
              @update:model-value="loadItems"
              @click:clear="loadItems"
            />
          </VCol>
        </VRow>
      </VCardText>
      <VDivider />
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        :headers="headers"
        :items="serverItems"
        :items-length="totalItems"
        :loading="loading"
        density="comfortable"
        hover
        @update:options="updateOptions"
      >
        <template #no-data>
          <div class="py-10 text-center">
            <VIcon icon="tabler-users" size="40" class="text-disabled mb-2" />
            <p class="text-body-1 text-disabled mb-0">Nessun cliente trovato</p>
          </div>
        </template>
        <template #item.disabled="{ item }">
          <VChip
            v-if="item.disabled === '1'"
            size="small"
            color="success"
            variant="tonal"
          >
            Attivo
          </VChip>
          <VChip
            v-else
            size="small"
            color="error"
            variant="tonal"
          >
            Disattivo
          </VChip>
        </template>
        <template #item.actions="{ item }">
          <div class="d-flex gap-1 justify-center">
            <IconBtn
              v-if="can(DefineAbilities.cavi_create.action, DefineAbilities.cavi_create.subject)"
              color="primary"
              size="small"
              @click="editItem(item)"
            >
              <VIcon icon="tabler-edit" size="18" />
            </IconBtn>
          </div>
        </template>
      </VDataTableServer>
    </VCard>
  </div>

  <!-- Dialog Modifica Cliente -->
  <VDialog v-model="editDialog" max-width="900">
    <DialogCloseBtn @click="close" />
    <VCard>
      <VCardItem class="py-3">
        <template #prepend>
          <VAvatar :color="editedItem.id ? 'primary' : 'success'" variant="tonal" size="38">
            <VIcon :icon="editedItem.id ? 'tabler-edit' : 'tabler-plus'" size="20" />
          </VAvatar>
        </template>
        <VCardTitle>{{ editedItem.id ? 'Modifica Cliente' : 'Nuovo Cliente' }}</VCardTitle>
      </VCardItem>
      <VDivider />
      <VCardText class="pt-4">
        <VForm ref="refForm" v-model="isFormValid">
          <VRow>
            <VCol cols="6">
              <AppTextField
                v-model="editedItem.ragione_sociale"
                :rules="[requiredValidator]"
                label="Ragione Sociale"
                placeholder="Ragione sociale"
              />
            </VCol>
            <VCol cols="6">
              <AppTextField
                v-model="editedItem.codice_sap"
                label="Codice SAP"
                placeholder="Codice SAP"
              />
            </VCol>
            <VCol cols="6">
              <AppTextField
                v-model="editedItem.email"
                label="Email"
                placeholder="Email"
              />
            </VCol>
            <VCol cols="6">
              <AppTextField
                v-model="editedItem.telefono"
                label="Telefono"
                placeholder="Telefono"
              />
            </VCol>
            <VCol cols="4">
              <AppTextField
                v-model="editedItem.provincia"
                label="Provincia"
                placeholder="Provincia"
              />
            </VCol>
            <VCol cols="4">
              <AppTextField
                v-model="editedItem.citta"
                label="Città"
                placeholder="Città"
              />
            </VCol>
            <VCol cols="4">
              <AppTextField
                v-model="editedItem.cap"
                label="CAP"
                placeholder="CAP"
              />
            </VCol>
            <VCol cols="12">
              <AppTextField
                v-model="editedItem.indirizzo"
                label="Indirizzo"
                placeholder="Indirizzo"
              />
            </VCol>
            <VCol cols="12">
              <VSwitch v-model="editedItem.disabled" label="Disattivo" color="error" />
            </VCol>
          </VRow>
        </VForm>
      </VCardText>
      <VDivider />
      <VCardActions class="justify-end">
        <VBtn color="error" variant="outlined" @click="close">
          Annulla
        </VBtn>
        <VBtn color="primary" variant="elevated" :loading="isLoading" @click="save">
          Salva
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
