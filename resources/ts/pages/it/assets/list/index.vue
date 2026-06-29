<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { can } from '@layouts/plugins/casl'
import BulkAdd from '../bulk-add/index.vue'

const { t } = useI18n()

const serverItems = ref([])
const loading = ref(true)
const totalItems = ref(0)
const search = ref('')
const categoryFilter = ref('')
const locationFilter = ref('')
const statusFilter = ref('')
const page = ref(1)
const itemsPerPage = ref(25)
const sortBy = ref()
const orderBy = ref()
const grouped = ref(true)

const categories = ref([])
const locations = ref([])

const headers = computed(() => [
  { title: t('IT.Asset.Brand'), key: 'brand', sortable: true },
  { title: t('IT.Asset.Model'), key: 'model', sortable: true },
  { title: t('IT.Asset.Quantity'), key: 'total_quantity', sortable: true },
  { title: t('IT.Asset.Stock'), key: 'total_stock', sortable: true },
  { title: t('IT.Asset.Status.Available'), key: 'available_count', sortable: false },
  { title: t('IT.Asset.MinStock'), key: 'min_stock', sortable: true },
  { title: t('Label.Azioni'), key: 'actions', sortable: false },
])

const statusOptions = [
  { title: t('IT.Asset.Status.Available'), value: 'Available' },
  { title: t('IT.Asset.Status.Assigned'), value: 'Assigned' },
  { title: t('IT.Asset.Status.InRepair'), value: 'In Repair' },
  { title: t('IT.Asset.Status.Retired'), value: 'Retired' },
  { title: t('IT.Asset.Status.Lost'), value: 'Lost' },
]

const resolveStatusColor = (status: string) => {
  const colors: Record<string, string> = {
    Available: 'success',
    Assigned: 'primary',
    'In Repair': 'warning',
    Retired: 'secondary',
    Lost: 'error',
  }
  return colors[status] || 'secondary'
}

const fetchCategories = async () => {
  try {
    const { data } = await useApi<any>('/it/categories')
    if (data.value && data.value.data && Array.isArray(data.value.data)) {
      categories.value = data.value.data
    } else if (data.value && Array.isArray(data.value)) {
      categories.value = data.value
    } else {
      categories.value = []
    }
  } catch (error) {
    console.error('Error fetching categories:', error)
    categories.value = []
  }
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
    const { data: resultData, error } = await useApi<any>(createUrl('/it/assets', {
      query: {
        page: page.value,
        itemsPerPage: itemsPerPage.value,
        sortBy: sortBy.value,
        orderBy: orderBy.value,
        search: search.value,
        category_id: categoryFilter.value,
        location_id: locationFilter.value,
        status: statusFilter.value,
        grouped: grouped.value,
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

fetchCategories()
fetchLocations()
loadItems()
</script>

<template>
  <div class="workspace-container w-100 d-flex flex-column pa-4 gap-3">
    <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
      <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
        <div class="d-flex align-center gap-2">
          <VIcon icon="tabler-device-laptop" size="24" color="primary" />
          <div>
            <div class="text-h6 font-weight-medium">{{ t('IT.Assets') }}</div>
            <div class="text-caption text-medium-emphasis">{{ totalItems }} asset registrati</div>
          </div>
        </div>
        <div class="d-flex align-center gap-2">
          <BulkAdd v-if="can('manage', 'IT-Assets')" @success="loadItems" />
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

          <VCol cols="12" sm="3">
            <VSelect
              v-model="categoryFilter"
              :label="t('IT.Categories')"
              :items="categories"
              item-title="name"
              item-value="id"
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-filter"
              @update:model-value="loadItems"
              @click:clear="loadItems"
            />
          </VCol>

          <VCol cols="12" sm="3">
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

          <VCol cols="12" sm="2">
            <VSelect
              v-model="statusFilter"
              :label="t('IT.Asset.Status')"
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
      <template #item.available_count="{ item }">
        <VChip :color="(item.available_count || 0) <= (item.min_stock || 0) ? 'error' : 'success'" size="small" variant="flat">
          {{ item.available_count || 0 }}
        </VChip>
      </template>

      <template #item.min_stock="{ item }">
        <span :class="(item.available_count || 0) <= (item.min_stock || 0) && (item.min_stock || 0) > 0 ? 'text-error font-weight-bold' : ''">
          {{ item.min_stock || 0 }}
        </span>
      </template>

      <template #item.actions="{ item }">
        <VBtn icon="tabler-eye" size="small" variant="text" :to="{ name: 'it-assets-group', query: { brand: item.brand, model: item.model } }" />
      </template>
    </VDataTableServer>
    </VCard>
  </div>
</template>
