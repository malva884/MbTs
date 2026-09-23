<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { useI18n } from 'vue-i18n'

definePage({
  meta: {
    action: 'admin',
    subject: 'Ehs-Eventi',
  },
})

const { t } = useI18n()

const currentTab = ref('sedi')
const itemsPerPage = ref(10)
const loading = ref(false)
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const page = ref(1)
const serverItems = ref<any>([])
const nomeFilter = ref('')
const disattivoFilter = ref()
const isSnackbarVisible = ref(false)
const message = ref('')
const color = ref('')

// Dialog
const isDialogVisible = ref(false)
const editingItem = ref<any>(null)
const formNome = ref('')
const formDisattivo = ref(false)
const saving = ref(false)

const tabs = computed(() => [
  { key: 'sedi', title: t('Ehs.Impianti'), icon: 'tabler-building-factory' },
  { key: 'cause', title: t('Ehs.Cause'), icon: 'tabler-alert-circle' },
  { key: 'lesioni', title: t('Ehs.Tipi-Lesione'), icon: 'tabler-bandage' },
  { key: 'anatomiche', title: t('Ehs.Sedi-Anatomiche'), icon: 'tabler-body-scan' },
  { key: 'eventi', title: t('Ehs.Tipi-Evento'), icon: 'tabler-leaf' },
])

const nameColumn = computed(() => {
  switch (currentTab.value) {
    case 'sedi': return 'site'
    case 'cause': return 'causa'
    case 'lesioni': return 'injurie'
    case 'anatomiche': return 'anatomical'
    case 'eventi': return 'event'
    default: return 'site'
  }
})

const headers = computed(() => [
  { title: t('Label.Nome'), key: nameColumn.value },
  { title: t('Label.Stato'), key: 'disattivo' },
  { title: t('Table.Azioni'), key: 'actions', sortable: false },
])

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

  const { data: resultData } = await useApi<any>(createUrl(`/ehs/lookup/${currentTab.value}/list`, {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      nome: nomeFilter.value,
      disattivo: disattivoFilter.value,
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

watch(currentTab, () => {
  page.value = 1
  nomeFilter.value = ''
  disattivoFilter.value = undefined

  loadItems()
})

const openDialog = (item: any = null) => {
  editingItem.value = item
  formNome.value = item ? item[nameColumn.value] : ''
  formDisattivo.value = item ? !!item.disattivo : false
  isDialogVisible.value = true
}

const save = async () => {
  saving.value = true
  try {
    const url = editingItem.value
      ? `/ehs/lookup/${currentTab.value}/update/${editingItem.value.id}`
      : `/ehs/lookup/${currentTab.value}/store`

    const response = await $api(url, {
      method: 'POST',
      body: { nome: formNome.value, disattivo: formDisattivo.value },
    })

    message.value = response.message
    color.value = response.color || 'success'
    isSnackbarVisible.value = true
    isDialogVisible.value = false
    loadItems()
  }
  catch (error: any) {
    message.value = error.message || 'Errore'
    color.value = 'error'
    isSnackbarVisible.value = true
  }
  finally {
    saving.value = false
  }
}

const deactivate = async (id: string) => {
  try {
    const response = await $api(`/ehs/lookup/${currentTab.value}/destroy/${id}`, { method: 'DELETE' })

    message.value = response.message
    color.value = response.color || 'success'
    isSnackbarVisible.value = true
    loadItems()
  }
  catch (error: any) {
    message.value = error.message || 'Errore'
    color.value = 'error'
    isSnackbarVisible.value = true
  }
}

await loadItems()
</script>

<template>
  <div class="workspace-container w-100 d-flex flex-column pa-4 gap-3">
    <VSnackbar v-model="isSnackbarVisible" transition="scroll-y-reverse-transition" location="top center" :timeout="3000">
      {{ $t(message) }}
    </VSnackbar>

    <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
      <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
        <div class="d-flex align-center gap-2">
          <VIcon icon="tabler-settings" size="24" color="primary" />
          <div class="text-h6 font-weight-medium">{{ $t('Ehs.Gestione-Tabelle') }}</div>
        </div>
        <VBtn
          prepend-icon="tabler-plus"
          color="primary"
          variant="flat"
          density="comfortable"
          @click="openDialog"
        >
          {{ $t('Label.Nuovo') }}
        </VBtn>
      </VCardText>
      <VDivider />

      <VTabs v-model="currentTab" class="px-4">
        <VTab v-for="tab in tabs" :key="tab.key" :value="tab.key">
          <VIcon :icon="tab.icon" size="18" class="me-1" />
          {{ tab.title }}
        </VTab>
      </VTabs>
      <VDivider />

      <VCardText class="pa-3">
        <VRow class="mb-2">
          <VCol cols="12" sm="4">
            <AppTextField
              v-model="nomeFilter"
              :label="$t('Label.Nome')"
              :placeholder="$t('Label.Cerca')"
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-search"
              @keyup.enter="loadItems"
              @click:clear="loadItems"
            />
          </VCol>
          <VCol cols="12" sm="3">
            <AppSelect
              v-model="disattivoFilter"
              :label="$t('Label.Stato')"
              :placeholder="$t('Label.Tutti')"
              :items="[{ title: $t('Label.Attivo'), value: 0 }, { title: $t('Label.Disattivo'), value: 1 }]"
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
        <template #item.disattivo="{ item }">
          <VChip :color="item.disattivo ? 'error' : 'success'" size="small" variant="tonal">
            {{ item.disattivo ? $t('Label.Disattivo') : $t('Label.Attivo') }}
          </VChip>
        </template>
        <template #item.actions="{ item }">
          <div class="d-flex gap-1">
            <IconBtn color="warning" size="small" @click="openDialog(item)">
              <VIcon icon="tabler-edit" size="18" />
            </IconBtn>
            <IconBtn v-if="!item.disattivo" color="error" size="small" @click="deactivate(item.id)">
              <VIcon icon="tabler-circle-off" size="18" />
            </IconBtn>
          </div>
        </template>
      </VDataTableServer>
    </VCard>

    <!-- Dialog creazione/modifica -->
    <VDialog v-model="isDialogVisible" max-width="500">
      <VCard>
        <VCardTitle class="pa-4">
          {{ editingItem ? $t('Label.Modifica') : $t('Label.Nuovo') }}
        </VCardTitle>
        <VDivider />
        <VCardText class="pa-4">
          <AppTextField
            v-model="formNome"
            :label="$t('Label.Nome')"
            :rules="[v => !!v || $t('Validation.Required')]"
            autofocus
          />
          <VCheckbox
            v-model="formDisattivo"
            :label="$t('Label.Disattivo')"
            class="mt-2"
          />
        </VCardText>
        <VDivider />
        <VCardActions class="pa-4">
          <VSpacer />
          <VBtn variant="outlined" color="secondary" @click="isDialogVisible = false">
            {{ $t('Label.Annulla') }}
          </VBtn>
          <VBtn color="primary" :loading="saving" @click="save">
            {{ $t('Label.Salva') }}
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>
