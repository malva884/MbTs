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
const itemsPerPage = ref(20)
const loading = ref(true)
const refForm = ref<VForm>()
const refCostoForm = ref<VForm>()
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const materialeFilter = ref()
const attivoFilter = ref()
const descrizioneFilter = ref()

const page = ref(1)
const serverItems = ref<any>([])
const snackbar = ref({ show: false, color: '', message: '' })
const editDialog = ref(false)
const costoDialog = ref(false)
const isLoading = ref(false)
const isFormValid = ref(false)
const isCostoValid = ref(false)

const defaultItem = ref<any>({
  id: '',
  materiale: '',
  costo: null,
  descrizione: '',
  diametro: 0,
  ativo: 0,
  updated_at: '',
})

function new_defaultItem() {
  defaultItem.value = {
    id: '',
    materiale: '',
    costo: null,
    descrizione: '',
    diametro: 0,
    ativo: 0,
	updated_at: '',
  }
}

const editedItem = ref<any>(defaultItem.value)
const editedIndex = ref(-1)
const costoItem = ref<any>({ id: '', materiale: '', costo: null })

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

  const { data: resultData, error } = await useApi<any>(createUrl('/to/materiali/list', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      materiale: materialeFilter.value,
      attivo: attivoFilter.value,
	  descrizione: descrizioneFilter.value,
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
  { title: t('Table.Materiale'), key: 'materiale' },
  { title: t('Table.Descrizione'), key: 'descrizione' },
  { title: t('Table.Costo'), key: 'costo' },
  { title: t('Table.Diametro'), key: 'diametro' },
  { title: t('Table.Data-Ultima-Modifica'), key: 'updated_at' }, 
  { title: t('Table.Disattivo'), key: 'disabled' },
  { title: 'ACTIONS', key: 'actions', sortable: false },
])

const save = async () => {
  if (editedItem.value.materiale && editedItem.value.descrizione && editedItem.value.costo) {
    let path = '/to/materiali/stored'
    if (editedItem.value.id)
      path = `/to/materiali/update/${editedItem.value.id}`

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

const openCostoDialog = (item: any) => {
  costoItem.value = { id: item.id, materiale: item.materiale, costo: item.costo }
  costoDialog.value = true
}

const saveCosto = async () => {
  if (costoItem.value.id && costoItem.value.costo !== null) {
    isLoading.value = true

    const retuenData = await $api(`/to/materiali/update/${costoItem.value.id}`, {
      method: 'POST',
      body: { costo: costoItem.value.costo },
    })

    snackbar.value = { show: true, color: retuenData.color, message: retuenData.message }
    isLoading.value = false
    costoDialog.value = false
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

const closeCostoDialog = () => {
  isLoading.value = false
  costoDialog.value = false
  costoItem.value = { id: '', materiale: '', costo: null }
  refCostoForm.value?.reset()
}

const editItem = (item: object) => {
  editedIndex.value = serverItems.value.indexOf(item)

  editedItem.value = { ...item }
  editedItem.value.disabled = editedItem.value.disabled === '1'
  editDialog.value = true
}

const euro = new Intl.NumberFormat('it-IT', {
  style: 'currency',
  currency: 'EUR',
})

const formatDate = (dateStr: string) => {
  if (!dateStr) return '-'
  const date = new Date(dateStr)
  return new Intl.DateTimeFormat('it-IT', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date)
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
          <VIcon icon="tabler-package" size="24" color="primary" />
          <div>
            <div class="text-h6 font-weight-medium">Materiali</div>
            <div class="text-caption text-medium-emphasis">{{ totalItems }} materiali in anagrafica</div>
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
          Nuovo Materiale
        </VBtn>
      </VCardText>
      <VDivider />
      <VCardText class="pa-3">
        <VRow class="mb-2">
          <VCol cols="12" sm="4">
            <AppTextField
              v-model="materialeFilter"
              label="Materiale"
              placeholder="Materiale"
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-search"
              @keyup.enter="loadItems"
              @click:clear="loadItems"
            />
          </VCol>
          <VCol cols="12" sm="4">
            <AppTextField
              v-model="descrizioneFilter"
              label="Descrizione"
              placeholder="Descrizione"
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-search"
              @keyup.enter="loadItems"
              @click:clear="loadItems"
            />
          </VCol>
          <VCol cols="12" sm="4">
            <AppSelect
              v-model="attivoFilter"
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
            <VIcon icon="tabler-package" size="40" class="text-disabled mb-2" />
            <p class="text-body-1 text-disabled mb-0">Nessun materiale trovato</p>
          </div>
        </template>
        <template #item.materiale="{ item }">
          <VChip size="small" color="warning" variant="tonal" class="font-weight-medium">
            {{ item.materiale }}
          </VChip>
        </template>
        <template #item.costo="{ item }">
          <div class="d-flex align-center gap-2">
            <span class="text-success font-weight-medium">{{ euro.format(item.costo) }}</span>
            <IconBtn
              v-if="can(DefineAbilities.cavi_create.action, DefineAbilities.cavi_create.subject)"
              color="primary"
              size="x-small"
              @click="openCostoDialog(item)"
            >
              <VIcon icon="tabler-edit" size="14" />
            </IconBtn>
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
        <template #item.updated_at="{ item }">
          <span class="text-caption text-medium-emphasis">{{ formatDate(item.updated_at) }}</span>
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

  <!-- Dialog Modifica Materiale -->
  <VDialog v-model="editDialog" max-width="800">
    <DialogCloseBtn @click="close" />
    <VCard>
      <VCardItem class="py-3">
        <template #prepend>
          <VAvatar :color="editedItem.id ? 'primary' : 'success'" variant="tonal" size="38">
            <VIcon :icon="editedItem.id ? 'tabler-edit' : 'tabler-plus'" size="20" />
          </VAvatar>
        </template>
        <VCardTitle>{{ editedItem.id ? 'Modifica Materiale' : 'Nuovo Materiale' }}</VCardTitle>
      </VCardItem>
      <VDivider />
      <VCardText class="pt-4">
        <VForm ref="refForm" v-model="isFormValid">
          <VRow>
            <VCol cols="12">
              <AppTextField
                v-model="editedItem.materiale"
                :rules="[requiredValidator]"
                label="Materiale"
                placeholder="Codice materiale"
              />
            </VCol>
            <VCol cols="12">
              <AppTextField
                v-model="editedItem.descrizione"
                :rules="[requiredValidator]"
                label="Descrizione"
                placeholder="Descrizione materiale"
              />
            </VCol>
            <VCol cols="6">
              <AppTextField
                v-model="editedItem.costo"
                :rules="[requiredValidator]"
                type="number"
                label="Costo"
                placeholder="0.00"
                prefix="€"
              />
            </VCol>
            <VCol cols="6">
              <AppTextField
                v-model="editedItem.diametro"
                label="Diametro"
                placeholder="0.00"
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

  <!-- Dialog Modifica Veloce Costo -->
  <VDialog v-model="costoDialog" max-width="400">
    <DialogCloseBtn @click="closeCostoDialog" />
    <VCard>
      <VCardItem class="py-3">
        <template #prepend>
          <VAvatar color="primary" variant="tonal" size="38">
            <VIcon icon="tabler-currency-euro" size="20" />
          </VAvatar>
        </template>
        <VCardTitle>Modifica Costo</VCardTitle>
        <VCardSubtitle>{{ costoItem.materiale }}</VCardSubtitle>
      </VCardItem>
      <VDivider />
      <VCardText class="pt-4">
        <VForm ref="refCostoForm" v-model="isCostoValid">
          <AppTextField
            v-model="costoItem.costo"
            :rules="[requiredValidator]"
            type="number"
            label="Nuovo Costo"
            placeholder="0.00"
            prefix="€"
            autofocus
            @keyup.enter="saveCosto"
          />
        </VForm>
      </VCardText>
      <VDivider />
      <VCardActions class="justify-end">
        <VBtn color="error" variant="outlined" @click="closeCostoDialog">
          Annulla
        </VBtn>
        <VBtn color="primary" variant="elevated" :loading="isLoading" @click="saveCosto">
          Salva
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
