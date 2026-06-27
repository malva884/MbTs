<script setup lang="ts">
import {VDataTableServer} from 'vuetify/labs/VDataTable'
import {useI18n} from 'vue-i18n'
import { VForm } from 'vuetify/components/VForm'
import {can} from '@layouts/plugins/casl'
import DefineAbilities from '@/plugins/casl/DefineAbilities'
import type {Cavo, StrutturaCavo} from '@/views/offices/technical/cables/type'

definePage({
  meta: {
    action: 'list',
    subject: 'Cavi',
  },
})

const {t} = useI18n()
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

const stats = computed(() => {
  const items = serverItems.value || []
  const categorie = new Set(items.map((i: any) => i.categoria_id).filter(Boolean)).size
  const norme = new Set(items.map((i: any) => i.norma).filter(Boolean)).size
  return { categorie, norme }
})

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

  const {data: resultData, error} = await useApi<any>(createUrl('/to/cavi/list', {
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
  } else {
    serverItems.value = []
    totalItems.value = 0
  }
  loading.value = false
}

// headers
const headers = computed(() => [
  { title: t('Label.Cavo'), key: 'codice' },
  { title: t('Table.Descrizione'), key: 'descrizione' },
  { title: t('Table.Categoria'), key: 'categoria' },
  { title: t('Table.Norma'), key: 'norma' },
  { title: t('Label.Data-Creazione'), key: 'created_at' },
  { title: 'ACTIONS', key: 'actions', sortable: false },
])

const categorieOptions = ref([])

const catOptions = async () => {
  const {data: resultData} = await useApi<any>(createUrl('/to/categorie/get_list', {
    query: {
      modulo: 1,
    },
  }))

  const arr = []

  resultData.value.forEach(value => {
    arr.push({full_name: value.categoria, id: value.id})
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
  <div class="workspace-container w-100 h-100 d-flex flex-column pa-4 overflow-hidden">
    <VSnackbar
      v-model="isSnackbarScrollReverseVisible"
      transition="scroll-y-reverse-transition"
      location="top center"
      :color="color"
    >
      {{ $t(message) }}
    </VSnackbar>

    <!-- Statistiche -->
    <VRow class="mb-3 flex-shrink-0">
      <VCol cols="12" sm="4">
        <VCard class="pa-4 d-flex align-center gap-3" color="primary" variant="tonal">
          <VIcon icon="tabler-cable" size="32" />
          <div>
            <div class="text-h5 font-weight-bold">{{ totalItems }}</div>
            <div class="text-caption">Cavi Totali</div>
          </div>
        </VCard>
      </VCol>
      <VCol cols="12" sm="4">
        <VCard class="pa-4 d-flex align-center gap-3" color="success" variant="tonal">
          <VIcon icon="tabler-category" size="32" />
          <div>
            <div class="text-h5 font-weight-bold">{{ stats.categorie }}</div>
            <div class="text-caption">Categorie</div>
          </div>
        </VCard>
      </VCol>
      <VCol cols="12" sm="4">
        <VCard class="pa-4 d-flex align-center gap-3" color="info" variant="tonal">
          <VIcon icon="tabler-certificate" size="32" />
          <div>
            <div class="text-h5 font-weight-bold">{{ stats.norme }}</div>
            <div class="text-caption">Norme</div>
          </div>
        </VCard>
      </VCol>
    </VRow>

    <!-- Header -->
    <div class="d-flex align-center justify-space-between flex-wrap gap-x-4 gap-y-2 mb-3 flex-shrink-0">
      <div class="d-flex align-baseline gap-2">
        <h3 class="text-h5 font-weight-bold mb-0">Cavi</h3>
        <span class="text-caption text-medium-emphasis d-none d-sm-inline">— Gestione anagrafica cavi</span>
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
        {{ $t('Button.Nuovo-Cavo') }}
      </VBtn>
    </div>

    <!-- Card con tabella -->
    <VCard variant="outlined" class="bg-surface border-thin rounded-lg d-flex flex-column flex-grow-1 overflow-hidden">

      <!-- Toolbar filtri -->
      <div class="filter-toolbar px-4 py-3 border-b border-thin flex-shrink-0">
        <VRow dense>
          <VCol cols="12" sm="4">
            <AppTextField
              v-model="cavoFilter"
              :label="$t('Label.Codice')"
              :placeholder="$t('Label.Codice')"
              prepend-inner-icon="tabler-search"
              hide-details
              density="compact"
              clearable
              clear-icon="tabler-x"
              @keyup.enter="loadItems"
              @focusout="loadItems"
              @click:clear="setTimeout(() => { cavoFilter = ''; loadItems() }, 50)"
            />
          </VCol>
          <VCol cols="12" sm="4">
            <AppTextField
              v-model="normaFilter"
              :label="$t('Label.Norma')"
              :placeholder="$t('Label.Norma')"
              hide-details
              density="compact"
              clearable
              clear-icon="tabler-x"
              @keyup.enter="loadItems"
              @focusout="loadItems"
              @click:clear="setTimeout(() => { normaFilter = ''; loadItems() }, 50)"
            />
          </VCol>
          <VCol cols="12" sm="4">
            <AppSelect
              v-model="categoriaFilter"
              :label="$t('Label.Categoria')"
              :placeholder="$t('Label.Categoria')"
              :items="categorieOptions"
              item-title="full_name"
              item-value="id"
              hide-details
              density="compact"
              clearable
              clear-icon="tabler-x"
              @update:model-value="loadItems"
              @click:clear="setTimeout(() => { categoriaFilter = null; loadItems() }, 50)"
            />
          </VCol>
        </VRow>
      </div>

      <!-- Tabella -->
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        :headers="headers"
        :items="serverItems"
        :items-length="totalItems"
        :loading="loading"
        item-value="id"
        density="comfortable"
        class="flex-grow-1"
        style="min-height: 400px;"
        @update:options="updateOptions"
      >
        <template #no-data>
          <div class="py-10 text-center">
            <VIcon icon="tabler-cable" size="40" class="text-disabled mb-2" />
            <p class="text-body-1 text-disabled mb-0">Nessun cavo trovato</p>
          </div>
        </template>

        <template #item.codice="{ item }">
          <VChip
            size="small"
            color="primary"
            variant="flat"
            class="font-weight-bold cursor-pointer font-monospace"
          >
            <RouterLink
              :to="{ name: 'offices-technical-cables-view-id', params: { id: item.id } }"
              class="text-white text-decoration-none"
            >
              {{ item.codice }}
            </RouterLink>
          </VChip>
        </template>

        <template #item.categoria="{ item }">
          <VChip v-if="item.categoria" size="small" color="secondary" variant="tonal">
            {{ item.categoria }}
          </VChip>
          <span v-else class="text-disabled">-</span>
        </template>

        <template #item.norma="{ item }">
          <span class="text-body-2 text-medium-emphasis">{{ item.norma || '-' }}</span>
        </template>

        <template #item.created_at="{ item }">
          <span class="text-body-2 text-medium-emphasis">{{ formatDate(item.created_at) }}</span>
        </template>

        <template #item.actions="{ item }">
          <div class="d-flex gap-1 justify-center">
            <IconBtn
              v-if="can(DefineAbilities.cavi_edit.action, DefineAbilities.cavi_edit.subject)"
              color="warning"
              size="small"
              @click="editItem(item)"
            >
              <VIcon icon="tabler-edit" size="18" />
            </IconBtn>
            <IconBtn
              v-if="can(DefineAbilities.cavi_create.action, DefineAbilities.cavi_create.subject)"
              color="primary"
              size="small"
              @click="copy(item)"
            >
              <VIcon icon="tabler-copy" size="18" />
            </IconBtn>
            <IconBtn
              v-if="can(DefineAbilities.cavideleted.action, DefineAbilities.cavideleted.subject)"
              color="error"
              size="small"
              @click="deleteItem(item)"
            >
              <VIcon icon="tabler-trash" size="18" />
            </IconBtn>
          </div>
        </template>
      </VDataTableServer>
    </VCard>
  </div>

  <!-- Edit Dialog -->
  <VDialog v-model="editDialog" persistent max-width="800">
    <VCard>
      <VCardItem class="py-3">
        <template #prepend>
          <VAvatar :color="editedItem.id ? 'primary' : 'success'" variant="tonal" size="38">
            <VIcon :icon="editedItem.id ? 'tabler-edit' : 'tabler-plus'" size="20" />
          </VAvatar>
        </template>
        <VCardTitle>{{ editedItem.id ? $t('Label.Modifica') + ' Cavo' : $t('Label.Nuovo') + ' Cavo' }}</VCardTitle>
        <VCardSubtitle>{{ editedItem.id ? editedItem.codice : 'Nuovo cavo anagrafica' }}</VCardSubtitle>
      </VCardItem>
      <VDivider />
      <VForm ref="refForm" @submit.prevent="save">
        <VCardText class="pb-2 pt-4">
          <div class="dialog-section-label text-primary">
            <VIcon icon="tabler-fingerprint" size="16" />
            <span>Identificazione</span>
          </div>
          <VRow class="mt-2">
            <VCol cols="12" sm="6">
              <AppSelect
                v-model="editedItem.categoria_id"
                :label="$t('Label.Categoria')"
                :items="categorieOptions"
                item-title="full_name"
                item-value="id"
                :rules="[requiredValidator]"
              />
            </VCol>
            <VCol cols="12" sm="6">
              <AppTextField
                v-model="editedItem.codice"
                :label="$t('Label.Codice-Cavo')"
                :rules="[requiredValidator]"
              />
            </VCol>
            <VCol cols="12" sm="6">
              <AppTextField
                v-model="editedItem.norma"
                :label="$t('Label.Norma')"
              />
            </VCol>
            <VCol cols="12" sm="6">
              <AppTextField
                v-model="editedItem.descrizione"
                :label="$t('Label.Descrizione')"
              />
            </VCol>
            <VCol cols="12">
              <AppTextField
                v-model="editedItem.nota"
                :label="$t('Label.Nota')"
              />
            </VCol>
          </VRow>
        </VCardText>
        <VDivider />
        <VCardText class="d-flex justify-end gap-3 py-3">
          <VBtn variant="tonal" color="secondary" prepend-icon="tabler-x" @click="close">Annulla</VBtn>
          <VBtn type="submit" color="primary" prepend-icon="tabler-device-floppy" :loading="isLoading" @click="refForm?.validate()">Salva</VBtn>
        </VCardText>
      </VForm>
    </VCard>
  </VDialog>

  <!-- Copia Dialog -->
  <VDialog v-model="copiaDialog" max-width="500">
    <VCard>
      <VCardItem class="py-3">
        <template #prepend>
          <VAvatar color="info" variant="tonal" size="38">
            <VIcon icon="tabler-copy" size="20" />
          </VAvatar>
        </template>
        <VCardTitle>Duplica Cavo</VCardTitle>
        <VCardSubtitle>{{ copiaCavo.codice }}</VCardSubtitle>
      </VCardItem>
      <VDivider />
      <VForm ref="refForm" @submit.prevent="saveCopy">
        <VCardText class="pt-4 pb-2">
          <VRow>
            <VCol cols="12">
              <AppTextField
                v-model="copiaCavo.codice"
                :label="$t('Label.Codice-Cavo')"
                :rules="[requiredValidator]"
              />
            </VCol>
          </VRow>
        </VCardText>
        <VDivider />
        <VCardText class="d-flex justify-end gap-3 py-3">
          <VBtn variant="tonal" color="secondary" prepend-icon="tabler-x" @click="copiaDialog = false">Annulla</VBtn>
          <VBtn type="submit" color="info" prepend-icon="tabler-copy" :loading="isLoading" @click="refForm?.validate()">Duplica</VBtn>
        </VCardText>
      </VForm>
    </VCard>
  </VDialog>

  <!-- Delete Dialog -->
  <VDialog v-model="deleteDialog" max-width="440">
    <VCard>
      <VCardItem class="py-4">
        <template #prepend>
          <VAvatar color="error" variant="tonal" size="38">
            <VIcon icon="tabler-trash" size="20" />
          </VAvatar>
        </template>
        <VCardTitle>Eliminazione Cavo</VCardTitle>
        <VCardSubtitle>Questa azione non è reversibile</VCardSubtitle>
      </VCardItem>
      <VDivider />
      <VCardText class="py-4">
        <p class="text-body-2">Sei sicuro di voler eliminare il cavo <strong>{{ deletedItem.codice }}</strong>?</p>
      </VCardText>
      <VDivider />
      <VCardText class="d-flex justify-end gap-3 py-3">
        <VBtn variant="tonal" color="secondary" @click="deleteDialog = false">Annulla</VBtn>
        <VBtn color="error" prepend-icon="tabler-trash" :loading="loading" @click="deleteItemConfirm">Elimina</VBtn>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<style lang="scss" scoped>
.filter-toolbar {
  background: rgba(var(--v-theme-on-surface), 0.02);
}

.dialog-section-label {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}
</style>
