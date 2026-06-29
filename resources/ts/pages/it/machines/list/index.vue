<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { createUrl } from '@/@core/composable/createUrl'

const { t } = useI18n()

const serverItems = ref([])
const loading = ref(true)
const totalItems = ref(0)
const search = ref('')
const locationFilter = ref('')
const statusFilter = ref('')
const page = ref(1)
const itemsPerPage = ref(25)
const sortBy = ref()
const orderBy = ref()

const showCreateDialog = ref(false)
const createLoading = ref(false)
const createName = ref('')
const createCode = ref('')
const createDescription = ref('')
const createLocationId = ref('')
const createStatus = ref('Active')

const showEditDialog = ref(false)
const editLoading = ref(false)
const editId = ref('')
const editName = ref('')
const editCode = ref('')
const editDescription = ref('')
const editLocationId = ref('')
const editStatus = ref('Active')

const locations = ref([])

const headers = computed(() => [
  { title: t('IT.Machine.Name'), key: 'name', sortable: true },
  { title: t('IT.Machine.Code'), key: 'code', sortable: true },
  { title: t('IT.Locations'), key: 'location.name', sortable: false },
  { title: t('IT.Machine.Status'), key: 'status', sortable: true },
  { title: t('Label.Azioni'), key: 'actions', sortable: false },
])

const statusOptions = [
  { title: t('IT.Machine.Status.Active'), value: 'Active' },
  { title: t('IT.Machine.Status.Inactive'), value: 'Inactive' },
  { title: t('IT.Machine.Status.Maintenance'), value: 'Maintenance' },
]

const resolveStatusColor = (status: string) => {
  const colors: Record<string, string> = {
    Active: 'success',
    Inactive: 'secondary',
    Maintenance: 'warning',
  }
  return colors[status] || 'primary'
}

const fetchLocations = async () => {
  try {
    const { data } = await useApi<any>('/it/locations')
    if (data.value && data.value.data && Array.isArray(data.value.data)) {
      locations.value = data.value.data
    } else if (data.value && Array.isArray(data.value)) {
      locations.value = data.value
    } else {
      locations.value = []
    }
  } catch (error) {
    console.error('Error fetching locations:', error)
    locations.value = []
  }
}

const loadItems = async () => {
  loading.value = true
  try {
    const { data: resultData, error } = await useApi<any>(createUrl('/it/machines', {
      query: {
        page: page.value,
        itemsPerPage: itemsPerPage.value,
        sortBy: sortBy.value,
        orderBy: orderBy.value,
        search: search.value,
        location_id: locationFilter.value,
        status: statusFilter.value,
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
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

const updateOptions = (options: any) => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
  page.value = options.page
  itemsPerPage.value = options.itemsPerPage
  loadItems()
}

const openCreateDialog = () => {
  createName.value = ''
  createCode.value = ''
  createDescription.value = ''
  createLocationId.value = ''
  createStatus.value = 'Active'
  showCreateDialog.value = true
}

const createMachine = async () => {
  if (!createName.value) {
    alert('Inserisci il nome della macchina.')
    return
  }
  if (!createCode.value) {
    alert('Inserisci il codice della macchina.')
    return
  }

  createLoading.value = true
  try {
    await $api('/it/machines/store', {
      method: 'POST',
      body: {
        name: createName.value,
        code: createCode.value,
        description: createDescription.value,
        location_id: createLocationId.value || null,
        status: createStatus.value,
      },
    })

    showCreateDialog.value = false
    loadItems()
  } catch (error: any) {
    const messages = error?.data?.errors
      ? Object.values(error.data.errors).flat().join('\n')
      : (error?.data?.message || error?.message)

    console.error('Error creating machine:', error)
    alert(messages || 'Errore durante il salvataggio.')
  } finally {
    createLoading.value = false
  }
}

const openEditDialog = (item: any) => {
  editId.value = item.id
  editName.value = item.name
  editCode.value = item.code
  editDescription.value = item.description || ''
  editLocationId.value = item.location_id || ''
  editStatus.value = item.status
  showEditDialog.value = true
}

const updateMachine = async () => {
  if (!editName.value) {
    alert('Inserisci il nome della macchina.')
    return
  }
  if (!editCode.value) {
    alert('Inserisci il codice della macchina.')
    return
  }

  editLoading.value = true
  try {
    await $api(`/it/machines/update/${editId.value}`, {
      method: 'POST',
      body: {
        name: editName.value,
        code: editCode.value,
        description: editDescription.value,
        location_id: editLocationId.value || null,
        status: editStatus.value,
      },
    })

    showEditDialog.value = false
    loadItems()
  } catch (error: any) {
    const messages = error?.data?.errors
      ? Object.values(error.data.errors).flat().join('\n')
      : (error?.data?.message || error?.message)

    console.error('Error updating machine:', error)
    alert(messages || 'Errore durante il salvataggio.')
  } finally {
    editLoading.value = false
  }
}

const deleteMachine = async (item: any) => {
  if (!confirm(`Sei sicuro di voler eliminare la macchina "${item.name}"?`)) {
    return
  }

  try {
    await $api(`/it/machines/${item.id}`, {
      method: 'DELETE',
    })

    loadItems()
  } catch (error: any) {
    const messages = error?.data?.errors
      ? Object.values(error.data.errors).flat().join('\n')
      : (error?.data?.message || error?.message)

    console.error('Error deleting machine:', error)
    alert(messages || 'Errore durante l\'eliminazione.')
  }
}

fetchLocations()
loadItems()
</script>

<template>
  <div class="workspace-container w-100 d-flex flex-column pa-4 gap-3">
    <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
      <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
        <div class="d-flex align-center gap-2">
          <VIcon icon="tabler-device-desktop" size="24" color="primary" />
          <div>
            <div class="text-h6 font-weight-medium">{{ t('IT.Machines') }}</div>
            <div class="text-caption text-medium-emphasis">{{ totalItems }} macchine registrate</div>
          </div>
        </div>
        <div class="d-flex align-center gap-2">
          <VBtn
            prepend-icon="tabler-plus"
            color="primary"
            variant="flat"
            density="comfortable"
            class="px-3"
            @click="openCreateDialog"
          >
            {{ t('IT.Machine.Create') }}
          </VBtn>
        </div>
      </VCardText>
      <VDivider />
      <VCardText class="pa-3">
        <VRow class="mb-2">
          <VCol cols="12" sm="4">
            <VTextField
              v-model="search"
              :label="t('Label.Cerca')"
              prepend-inner-icon="tabler-search"
              clearable
              clear-icon="tabler-x"
              @keyup.enter="loadItems"
              @click:clear="loadItems"
            />
          </VCol>

          <VCol cols="12" sm="4">
            <VSelect
              v-model="locationFilter"
              :label="t('IT.Locations')"
              :items="locations"
              item-title="name"
              item-value="id"
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-filter"
              @update:model-value="loadItems"
              @click:clear="loadItems"
            />
          </VCol>

          <VCol cols="12" sm="4">
            <VSelect
              v-model="statusFilter"
              :label="t('IT.Machine.Status')"
              :items="statusOptions"
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
        <template #item.status="{ item }">
          <VChip :color="resolveStatusColor(item.status)" size="small">
            {{ item.status }}
          </VChip>
        </template>
        <template #item.actions="{ item }">
          <VBtn icon="tabler-edit" size="small" variant="text" color="primary" @click="openEditDialog(item)" />
          <VBtn icon="tabler-trash" size="small" variant="text" color="error" @click="deleteMachine(item)" />
        </template>
      </VDataTableServer>
    </VCard>

    <VDialog v-model="showCreateDialog" max-width="600">
      <VCard>
        <VCardText class="d-flex align-center justify-space-between py-3">
          <div class="d-flex align-center gap-2">
            <VIcon icon="tabler-device-desktop-plus" size="24" color="primary" />
            <div>
              <div class="text-h6 font-weight-medium">{{ t('IT.Machine.Create') }}</div>
              <div class="text-caption text-medium-emphasis">Nuova macchina di produzione</div>
            </div>
          </div>
          <VBtn icon="tabler-x" variant="text" @click="showCreateDialog = false" />
        </VCardText>
        <VDivider />
        <VCardText class="pa-3">
          <VForm @submit.prevent="createMachine">
            <VRow>
              <VCol cols="12">
                <VTextField
                  v-model="createName"
                  :label="t('IT.Machine.Name')"
                  required
                />
              </VCol>

              <VCol cols="12">
                <VTextField
                  v-model="createCode"
                  :label="t('IT.Machine.Code')"
                  required
                />
              </VCol>

              <VCol cols="12">
                <VSelect
                  v-model="createLocationId"
                  :label="t('IT.Locations')"
                  :items="locations"
                  item-title="name"
                  item-value="id"
                  clearable
                  clear-icon="tabler-x"
                />
              </VCol>

              <VCol cols="12">
                <VSelect
                  v-model="createStatus"
                  :label="t('IT.Machine.Status')"
                  :items="[
                    { title: t('IT.Machine.Status.Active'), value: 'Active' },
                    { title: t('IT.Machine.Status.Inactive'), value: 'Inactive' },
                    { title: t('IT.Machine.Status.Maintenance'), value: 'Maintenance' },
                  ]"
                  required
                />
              </VCol>

              <VCol cols="12">
                <VTextarea
                  v-model="createDescription"
                  :label="t('IT.Machine.Description')"
                  rows="3"
                />
              </VCol>
            </VRow>

            <VRow>
              <VCol cols="12">
                <VBtn
                  type="submit"
                  color="primary"
                  :loading="createLoading"
                  block
                >
                  {{ t('Label.Save') }}
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </VDialog>

    <VDialog v-model="showEditDialog" max-width="600">
      <VCard>
        <VCardText class="d-flex align-center justify-space-between py-3">
          <div class="d-flex align-center gap-2">
            <VIcon icon="tabler-device-desktop-edit" size="24" color="primary" />
            <div>
              <div class="text-h6 font-weight-medium">Modifica Macchina</div>
              <div class="text-caption text-medium-emphasis">Modifica macchina di produzione</div>
            </div>
          </div>
          <VBtn icon="tabler-x" variant="text" @click="showEditDialog = false" />
        </VCardText>
        <VDivider />
        <VCardText class="pa-3">
          <VForm @submit.prevent="updateMachine">
            <VRow>
              <VCol cols="12">
                <VTextField
                  v-model="editName"
                  :label="t('IT.Machine.Name')"
                  required
                />
              </VCol>

              <VCol cols="12">
                <VTextField
                  v-model="editCode"
                  :label="t('IT.Machine.Code')"
                  required
                />
              </VCol>

              <VCol cols="12">
                <VSelect
                  v-model="editLocationId"
                  :label="t('IT.Locations')"
                  :items="locations"
                  item-title="name"
                  item-value="id"
                  clearable
                  clear-icon="tabler-x"
                />
              </VCol>

              <VCol cols="12">
                <VSelect
                  v-model="editStatus"
                  :label="t('IT.Machine.Status')"
                  :items="[
                    { title: t('IT.Machine.Status.Active'), value: 'Active' },
                    { title: t('IT.Machine.Status.Inactive'), value: 'Inactive' },
                    { title: t('IT.Machine.Status.Maintenance'), value: 'Maintenance' },
                  ]"
                  required
                />
              </VCol>

              <VCol cols="12">
                <VTextarea
                  v-model="editDescription"
                  :label="t('IT.Machine.Description')"
                  rows="3"
                />
              </VCol>
            </VRow>

            <VRow>
              <VCol cols="12">
                <VBtn
                  type="submit"
                  color="primary"
                  :loading="editLoading"
                  block
                >
                  {{ t('Label.Save') }}
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </VDialog>
  </div>
</template>
