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
const page = ref(1)
const itemsPerPage = ref(25)
const sortBy = ref()
const orderBy = ref()

const isDialogVisible = ref(false)
const dialogLoading = ref(false)
const name = ref('')
const type = ref('')
const description = ref('')
const parent_id = ref('')
const locations = ref([])

const headers = computed(() => [
  { title: t('IT.Location.Name'), key: 'name', sortable: true },
  { title: t('IT.Location.Type'), key: 'type', sortable: true },
  { title: t('IT.Location.Description'), key: 'description', sortable: false },
  { title: t('IT.Location.Parent'), key: 'parent.name', sortable: false },
  { title: t('Label.Azioni'), key: 'actions', sortable: false },
])

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
    const { data: resultData, error } = await useApi<any>(createUrl('/it/locations', {
      query: {
        page: page.value,
        itemsPerPage: itemsPerPage.value,
        sortBy: sortBy.value,
        orderBy: orderBy.value,
        search: search.value,
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
  } catch (error) {
    console.error('Error fetching locations:', error)
    loading.value = false
  }
}

const fetchLocations = async () => {
  try {
    const { data } = await useApi<any>(createUrl('/it/locations', {
      query: { itemsPerPage: 1000 },
    }))
    if (data.value && data.value.data && Array.isArray(data.value.data)) {
      locations.value = data.value.data
    } else {
      locations.value = []
    }
  } catch (error) {
    console.error('Error fetching locations:', error)
    locations.value = []
  }
}

const deleteItem = async (id: string) => {
  if (confirm(t('Label.ConfirmDelete'))) {
    try {
      await useApi.delete(`/it/locations/${id}`)
      loadItems()
    } catch (error) {
      console.error('Error deleting location:', error)
    }
  }
}

const openDialog = () => {
  name.value = ''
  type.value = ''
  description.value = ''
  parent_id.value = ''
  isDialogVisible.value = true
}

const createLocation = async () => {
  dialogLoading.value = true
  try {
    const returnData = await $api('/it/locations/store', {
      method: 'POST',
      body: {
        name: name.value,
        type: type.value,
        description: description.value,
        parent_id: parent_id.value || null,
      },
    })
    console.log('Location created:', returnData)
    isDialogVisible.value = false
    loadItems()
  } catch (error) {
    console.error('Error creating location:', error)
    alert('Error creating location: ' + JSON.stringify(error))
  } finally {
    dialogLoading.value = false
  }
}

loadItems()
fetchLocations()
</script>

<template>
  <VCard flat border>
    <VCardItem>
      <template #prepend>
        <VAvatar color="primary" variant="tonal" size="40">
          <VIcon icon="tabler-map-pin" />
        </VAvatar>
      </template>
      <VCardTitle>{{ t('IT.Locations') }}</VCardTitle>
      <template #append>
        <VBtn
          prepend-icon="tabler-plus"
          color="primary"
          variant="flat"
          density="comfortable"
          @click="openDialog"
        >
          {{ t('Label.Add') }}
        </VBtn>
      </template>
    </VCardItem>

    <VDivider />

    <VCardText class="pa-4">
      <VRow>
        <VCol cols="12" sm="4">
          <VTextField
            v-model="search"
            :label="t('Label.Search')"
            prepend-inner-icon="tabler-search"
            clearable
            @keyup.enter="loadItems"
            @click:clear="loadItems"
          />
        </VCol>
      </VRow>
    </VCardText>

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
          <VIcon icon="tabler-map-pin" size="40" class="text-disabled mb-2" />
          <p class="text-body-1 text-disabled mb-0">Nessuna location trovata</p>
        </div>
      </template>
      <template #item.name="{ item }">
        <span class="font-weight-medium">{{ item.name }}</span>
      </template>
      <template #item.type="{ item }">
        <VChip size="small" variant="flat">{{ item.type }}</VChip>
      </template>
      <template #item.description="{ item }">
        <span class="text-caption">{{ item.description || '-' }}</span>
      </template>
      <template #item.parent.name="{ item }">
        <span class="text-caption">{{ item.parent?.name || '-' }}</span>
      </template>
      <template #item.actions="{ item }">
        <VBtn icon size="small" color="primary" @click="$router.push(`/it/locations/${item.id}`)">
          <VIcon icon="tabler-pencil" />
        </VBtn>
        <VBtn icon size="small" color="error" @click="deleteItem(item.id)">
          <VIcon icon="tabler-trash" />
        </VBtn>
      </template>
    </VDataTableServer>

    <!-- Create Dialog -->
    <VDialog v-model="isDialogVisible" max-width="600">
      <VCard>
        <VCardTitle>{{ t('IT.Location.Create') }}</VCardTitle>

        <VCardText>
          <VForm @submit.prevent="createLocation">
            <VRow>
              <VCol cols="12" md="6">
                <VTextField
                  v-model="name"
                  :label="t('IT.Location.Name')"
                  required
                />
              </VCol>

              <VCol cols="12" md="6">
                <VSelect
                  v-model="type"
                  :label="t('IT.Location.Type')"
                  :items="['Office', 'Warehouse', 'Data Center']"
                  required
                />
              </VCol>

              <VCol cols="12">
                <VSelect
                  v-model="parent_id"
                  :label="t('IT.Location.Parent')"
                  :items="locations"
                  item-title="name"
                  item-value="id"
                  clearable
                />
              </VCol>

              <VCol cols="12">
                <VTextarea
                  v-model="description"
                  :label="t('IT.Location.Description')"
                  rows="3"
                />
              </VCol>
            </VRow>

            <VRow>
              <VCol cols="12">
                <VBtn
                  type="submit"
                  color="primary"
                  :loading="dialogLoading"
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
  </VCard>
</template>
