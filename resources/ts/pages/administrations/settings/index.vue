<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { useI18n } from 'vue-i18n'
import DefineAbilities from '@/plugins/casl/DefineAbilities'
import { VForm } from 'vuetify/components/VForm'
import { ability } from '@/plugins/casl/ability'

definePage({
  meta: {
    action: 'admin',
    subject: 'Settings',
  },
})

const { t } = useI18n()

const searchQuery = ref('')
const selectedGroup = ref()
const selectedType = ref()

const itemsPerPage = ref(10)
const loading = ref(true)
const page = ref(1)
const sortBy = ref()
const orderBy = ref()
const settings = ref<any>([])
const totalSettings = ref(0)
const isSnackbarVisible = ref(false)
const snackbarMessage = ref('')
const snackbarColor = ref('')

const editDialog = ref(false)
const isFormValid = ref(false)
const refForm = ref<VForm>()
const editedItem = ref<any>({
  key: '',
  value: '',
  type: 'string',
  group: 'general',
  description: '',
})
const editedIndex = ref(-1)

const headers = computed(() => [
  { title: t('Table.Key'), key: 'key' },
  { title: t('Table.Value'), key: 'value' },
  { title: t('Table.Type'), key: 'type' },
  { title: t('Table.Group'), key: 'group' },
  { title: t('Table.Description'), key: 'description' },
  { title: t('Table.Actions'), key: 'actions', sortable: false },
])

const groups = [
  { title: 'Google', value: 'google' },
  { title: 'System', value: 'system' },
  { title: 'Mail', value: 'mail' },
  { title: 'General', value: 'general' },
]

const types = [
  { title: 'String', value: 'string' },
  { title: 'Boolean', value: 'boolean' },
  { title: 'Integer', value: 'integer' },
  { title: 'Float', value: 'float' },
  { title: 'JSON', value: 'json' },
]

const updateOptions = (options: any) => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
  page.value = options.page
  itemsPerPage.value = options.itemsPerPage
  loadItems()
}

const loadItems = async () => {
  loading.value = true
  try {
    const { data: settingsData } = await useApi<any>(createUrl('/settings/metadata', {
      query: {
        group: selectedGroup.value,
        itemsPerPage: itemsPerPage.value,
        page: page.value,
        sortBy: sortBy.value,
        orderBy: orderBy.value,
      },
    }))

    if (settingsData.value) {
      settings.value = settingsData.value.data
      totalSettings.value = settingsData.value.data.length
    }
    else {
      settings.value = []
      totalSettings.value = 0
    }
  }
  catch (error) {
    console.error('Error loading settings:', error)
    settings.value = []
    totalSettings.value = 0
  }
  loading.value = false
}

const newItem = () => {
  editedIndex.value = -1
  editedItem.value = {
    key: '',
    value: '',
    type: 'string',
    group: selectedGroup.value || 'general',
    description: '',
  }
  editDialog.value = true
}

const editItem = (item: any) => {
  editedIndex.value = settings.value.indexOf(item)
  editedItem.value = { ...item }
  editDialog.value = true
}

const save = async () => {
  const { valid } = await refForm.value?.validate()

  if (!valid)
    return

  try {
    if (editedIndex.value === -1) {
      await $api('/settings', {
        method: 'POST',
        body: editedItem.value,
      })
      snackbarMessage.value = 'Setting creato con successo'
    }
    else {
      await $api(`/settings/${editedItem.value.key}`, {
        method: 'PUT',
        body: editedItem.value,
      })
      snackbarMessage.value = 'Setting aggiornato con successo'
    }

    snackbarColor.value = 'success'
    isSnackbarVisible.value = true
    editDialog.value = false
    loadItems()
  }
  catch (error: any) {
    console.error('Error saving setting:', error)
    snackbarMessage.value = error.response?.data?.message || 'Errore durante il salvataggio'
    snackbarColor.value = 'error'
    isSnackbarVisible.value = true
  }
}

const deleteItem = async (item: any) => {
  if (!confirm(`Sei sicuro di voler eliminare il setting "${item.key}"?`))
    return

  try {
    await $api(`/settings/${item.key}`, {
      method: 'DELETE',
    })
    snackbarMessage.value = 'Setting eliminato con successo'
    snackbarColor.value = 'success'
    isSnackbarVisible.value = true
    loadItems()
  }
  catch (error: any) {
    console.error('Error deleting setting:', error)
    snackbarMessage.value = error.response?.data?.message || 'Errore durante l\'eliminazione'
    snackbarColor.value = 'error'
    isSnackbarVisible.value = true
  }
}

const clearCache = async () => {
  try {
    await $api('/settings/clear-cache', {
      method: 'POST',
    })
    snackbarMessage.value = 'Cache pulita con successo'
    snackbarColor.value = 'success'
    isSnackbarVisible.value = true
  }
  catch (error: any) {
    console.error('Error clearing cache:', error)
    snackbarMessage.value = error.response?.data?.message || 'Errore durante la pulizia della cache'
    snackbarColor.value = 'error'
    isSnackbarVisible.value = true
  }
}

const closeDialog = () => {
  editDialog.value = false
  refForm.value?.reset()
  refForm.value?.resetValidation()
}

const getTypeColor = (type: string) => {
  const colors: Record<string, string> = {
    string: 'primary',
    boolean: 'success',
    integer: 'info',
    float: 'warning',
    json: 'error',
  }
  return colors[type] || 'grey'
}

const getGroupColor = (group: string) => {
  const colors: Record<string, string> = {
    google: 'primary',
    system: 'warning',
    mail: 'info',
    general: 'grey',
  }
  return colors[group] || 'grey'
}

loadItems()
</script>

<template>
  <div>
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between py-4">
        <div class="d-flex align-center gap-2">
          <VIcon icon="tabler-settings" size="24" color="primary" />
          <span class="text-h5">Settings</span>
        </div>
      </VCardTitle>

      <VDivider />
      <VCardText class="d-flex flex-wrap py-4 gap-4">
        <div class="app-user-search-filter d-flex align-center flex-wrap gap-4">
          <AppSelect
            v-model="selectedGroup"
            :items="groups"
            label="Gruppo"
            placeholder="Tutti"
            clearable
            density="comfortable"
            style="max-inline-size: 200px"
            @update:model-value="loadItems"
          />

          <AppSelect
            v-model="selectedType"
            :items="types"
            label="Tipo"
            placeholder="Tutti"
            clearable
            density="comfortable"
            style="max-inline-size: 200px"
          />
        </div>

        <VSpacer />

        <div class="d-flex gap-2">
          <VBtn
            color="warning"
            variant="outlined"
            prepend-icon="tabler-refresh"
            @click="clearCache"
          >
            Pulisci Cache
          </VBtn>

          <VBtn
            color="primary"
            prepend-icon="tabler-plus"
            @click="newItem"
          >
            Nuovo Setting
          </VBtn>
        </div>
      </VCardText>

      <VDivider />

      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        :headers="headers"
        :items="settings"
        :items-length="totalSettings"
        :loading="loading"
        :sort-by="[{ key: 'group', order: 'asc' }, { key: 'key', order: 'asc' }]"
        class="text-no-wrap"
        @update:options="updateOptions"
      >
        <template #item.value="{ item }">
          <div class="text-truncate d-inline-block" style="max-width: 300px">
            <span v-if="item.type === 'boolean'">
              <VChip size="small" :color="item.value ? 'success' : 'error'">
                {{ item.value ? 'Sì' : 'No' }}
              </VChip>
            </span>
            <span v-else class="text-body-2">
              {{ item.value }}
            </span>
          </div>
        </template>

        <template #item.type="{ item }">
          <VChip size="small" :color="getTypeColor(item.type)">
            {{ item.type }}
          </VChip>
        </template>

        <template #item.group="{ item }">
          <VChip size="small" :color="getGroupColor(item.group)" variant="tonal">
            {{ item.group }}
          </VChip>
        </template>

        <template #item.actions="{ item }">
          <div class="d-flex gap-1">
            <VBtn
              icon="tabler-edit"
              size="small"
              color="primary"
              variant="text"
              @click="editItem(item)"
            />
            <VBtn
              icon="tabler-trash"
              size="small"
              color="error"
              variant="text"
              @click="deleteItem(item)"
            />
          </div>
        </template>
      </VDataTableServer>
    </VCard>

    <!-- Edit/Create Dialog -->
    <VDialog v-model="editDialog" max-width="600px">
      <VCard>
        <VCardTitle>
          {{ editedIndex === -1 ? 'Nuovo Setting' : 'Modifica Setting' }}
        </VCardTitle>

        <VDivider />

        <VCardText>
          <VForm ref="refForm" v-model="isFormValid">
            <VRow>
              <VCol cols="12">
                <VTextField
                  v-model="editedItem.key"
                  label="Key"
                  :disabled="editedIndex !== -1"
                  :rules="[v => !!v || 'Key obbligatoria']"
                  required
                />
              </VCol>

              <VCol cols="12">
                <VSelect
                  v-model="editedItem.type"
                  label="Type"
                  :items="types"
                  item-title="title"
                  item-value="value"
                  :rules="[v => !!v || 'Type obbligatorio']"
                  required
                />
              </VCol>

              <VCol cols="12">
                <VSelect
                  v-model="editedItem.group"
                  label="Group"
                  :items="groups"
                  item-title="title"
                  item-value="value"
                  :rules="[v => !!v || 'Group obbligatorio']"
                  required
                />
              </VCol>

              <VCol cols="12">
                <VTextField
                  v-model="editedItem.value"
                  label="Value"
                  :rules="[v => !!v || 'Value obbligatorio']"
                  required
                  :type="editedItem.type === 'boolean' ? 'text' : 'text'"
                />
              </VCol>

              <VCol cols="12">
                <VTextField
                  v-model="editedItem.description"
                  label="Description"
                />
              </VCol>
            </VRow>
          </VForm>
        </VCardText>

        <VDivider />

        <VCardActions class="justify-end">
          <VBtn variant="text" @click="closeDialog">
            Annulla
          </VBtn>
          <VBtn color="primary" @click="save">
            Salva
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Snackbar -->
    <VSnackbar v-model="isSnackbarVisible" :color="snackbarColor">
      {{ snackbarMessage }}
    </VSnackbar>
  </div>
</template>
