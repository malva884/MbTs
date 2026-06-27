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
const categoriaFilter = ref('')
const normaFilter = ref('')
const moduloFilter = ref('')
const page = ref(1)
const serverItems = ref<any>([])
const snackbar = ref({ show: false, color: '', message: '' })
const editDialog = ref(false)
const isLoading = ref(false)
const isFormValid = ref(false)

const defaultItem = ref<any>({
  id: '',
  categoria: '',
  legistrazione: '',
  nota: '',
  modulo: null,
})

function new_defaultItem() {
  defaultItem.value = {
    id: '',
    categoria: '',
    legistrazione: '',
    nota: '',
    modulo: null,
  }
}

const editedItem = ref<any>(defaultItem.value)
const editedIndex = ref(-1)
const selectedModuli = ref([{ categoria: 'Cavi', value: '1', colore: 'info' }])

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

  const { data: resultData, error } = await useApi<any>(createUrl('/to/categorie/list', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      categoria: categoriaFilter.value,
      norma: normaFilter.value,
      modulo: moduloFilter.value,
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
const headers = computed(() => [
  { title: t('Table.Categoria'), key: 'categoria' },
  { title: t('Table.Norma'), key: 'legistrazione' },
  { title: t('Table.Modulo'), key: 'modulo' },
  { title: t('Table.Nota'), key: 'nota' },
  { title: 'ACTIONS', key: 'actions', sortable: false },
])

const save = async () => {
  if (editedItem.value.categoria) {
    let path = '/to/categorie/store/'
    if (editedItem.value.id)
      path = `/to/categorie/update/${editedItem.value.id}`

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
          <VIcon icon="tabler-category" size="24" color="primary" />
          <div>
            <div class="text-h6 font-weight-medium">Categorie</div>
            <div class="text-caption text-medium-emphasis">{{ totalItems }} categorie in anagrafica</div>
          </div>
        </div>
        <VBtn
          v-if="can(DefineAbilities.qt_non_conformita_create.action, DefineAbilities.qt_non_conformita_create.subject)"
          prepend-icon="tabler-plus"
          color="primary"
          variant="flat"
          density="comfortable"
          class="px-3"
          @click="newItem"
        >
          Nuova Categoria
        </VBtn>
      </VCardText>
      <VDivider />
      <VCardText class="pa-3">
        <VRow class="mb-2">
          <VCol cols="12" sm="4">
            <AppTextField
              v-model="categoriaFilter"
              label="Categoria"
              placeholder="Categoria"
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-search"
              @keyup.enter="loadItems"
              @click:clear="loadItems"
            />
          </VCol>
          <VCol cols="12" sm="4">
            <AppTextField
              v-model="normaFilter"
              label="Norma"
              placeholder="Norma"
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-search"
              @keyup.enter="loadItems"
              @click:clear="loadItems"
            />
          </VCol>
          <VCol cols="12" sm="4">
            <AppTextField
              v-model="moduloFilter"
              label="Modulo"
              placeholder="Modulo"
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-search"
              @keyup.enter="loadItems"
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
            <VIcon icon="tabler-category" size="40" class="text-disabled mb-2" />
            <p class="text-body-1 text-disabled mb-0">Nessuna categoria trovata</p>
          </div>
        </template>
        <template #item.actions="{ item }">
          <div class="d-flex gap-1 justify-center">
            <IconBtn
              v-if="can(DefineAbilities.qt_non_conformita_create.action, DefineAbilities.qt_non_conformita_create.subject)"
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

  <!-- Dialog Modifica Categoria -->
  <VDialog v-model="editDialog" max-width="700">
    <DialogCloseBtn @click="close" />
    <VCard>
      <VCardItem class="py-3">
        <template #prepend>
          <VAvatar :color="editedItem.id ? 'primary' : 'success'" variant="tonal" size="38">
            <VIcon :icon="editedItem.id ? 'tabler-edit' : 'tabler-plus'" size="20" />
          </VAvatar>
        </template>
        <VCardTitle>{{ editedItem.id ? 'Modifica Categoria' : 'Nuova Categoria' }}</VCardTitle>
      </VCardItem>
      <VDivider />
      <VCardText class="pt-4">
        <VForm ref="refForm" v-model="isFormValid">
          <VRow>
            <VCol cols="12">
              <AppTextField
                v-model="editedItem.categoria"
                :rules="[requiredValidator]"
                label="Categoria"
                placeholder="Nome categoria"
              />
            </VCol>
            <VCol cols="12">
              <AppTextField
                v-model="editedItem.legistrazione"
                label="Norma"
                placeholder="Norma di riferimento"
              />
            </VCol>
            <VCol cols="12">
              <AppTextField
                v-model="editedItem.nota"
                label="Nota"
                placeholder="Note aggiuntive"
              />
            </VCol>
            <VCol cols="12">
              <AppSelect
                v-model="editedItem.modulo"
                label="Moduli"
                placeholder="Seleziona moduli"
                :items="selectedModuli"
                :item-title="item => item.categoria"
                :item-value="item => item.value"
                chips
                multiple
                eager
              />
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
