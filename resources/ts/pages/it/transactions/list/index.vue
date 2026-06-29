<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { createUrl } from '@/@core/composable/createUrl'

const { t } = useI18n()

const serverItems = ref([])
const loading = ref(true)
const totalItems = ref(0)
const assetFilter = ref('')
const typeFilter = ref('')
const dateFromFilter = ref('')
const dateToFilter = ref('')
const page = ref(1)
const itemsPerPage = ref(25)
const sortBy = ref(['date'])
const orderBy = ref('desc')

const headers = computed(() => [
  { title: t('IT.Asset.SerialNumber'), key: 'asset.serial_number', sortable: false },
  { title: t('IT.Transaction.Type'), key: 'type', sortable: true },
  { title: t('IT.Transaction.FromLocation'), key: 'fromLocation.name', sortable: false },
  { title: t('IT.Transaction.ToLocation'), key: 'to_location.name', sortable: false },
  { title: t('IT.Transaction.Date'), key: 'date', sortable: true },
  { title: t('IT.Transaction.PerformedBy'), key: 'performed_by.full_name', sortable: false },
])

const typeOptions = [
  { title: 'In', value: 'In' },
  { title: 'Out', value: 'Out' },
  { title: 'Transfer', value: 'Transfer' },
  { title: 'Maintenance', value: 'Maintenance' },
  { title: 'Return', value: 'Return' },
  { title: 'Retire', value: 'Retire' },
]

const resolveTypeColor = (type: string) => {
  const colors: Record<string, string> = {
    In: 'success',
    Out: 'error',
    Transfer: 'info',
    Maintenance: 'warning',
    Return: 'primary',
    Retire: 'secondary',
  }
  return colors[type] || 'primary'
}

const fetchItems = async () => {
  loading.value = true
  try {
    const { data } = await useApi<any>(createUrl('/it/transactions', {
      query: {
        page: page.value,
        itemsPerPage: itemsPerPage.value,
        sortBy: sortBy.value,
        orderBy: orderBy.value,
        asset_id: assetFilter.value,
        type: typeFilter.value,
        date_from: dateFromFilter.value,
        date_to: dateToFilter.value,
      },
    }))
    serverItems.value = data.value?.data ?? []
    totalItems.value = data.value?.total ?? 0
    console.log(data.value?.data)
  } catch (error) {
    console.error('Error fetching transactions:', error)
  } finally {
    loading.value = false
  }
}

fetchItems()
</script>

<template>
  <VCard flat border>
    <VCardItem>
      <template #prepend>
        <VAvatar color="primary" variant="tonal" size="40">
          <VIcon icon="tabler-arrows-left-right" />
        </VAvatar>
      </template>
      <VCardTitle>{{ t('IT.Transactions') }}</VCardTitle>
    </VCardItem>

    <VDivider />

    <VCardText class="pa-4">
      <VRow>
        <VCol cols="12" sm="3">
          <VTextField
            v-model="assetFilter"
            :label="t('IT.Asset')"
            prepend-inner-icon="tabler-search"
            clearable
            @keyup.enter="fetchItems"
          />
        </VCol>
        <VCol cols="12" sm="3">
          <VSelect
            v-model="typeFilter"
            :label="t('IT.Transaction.Type')"
            :items="typeOptions"
            clearable
            @update:model-value="fetchItems"
          />
        </VCol>
        <VCol cols="12" sm="3">
          <VTextField
            v-model="dateFromFilter"
            :label="t('IT.Transaction.DateFrom')"
            type="date"
            clearable
            @update:model-value="fetchItems"
          />
        </VCol>
        <VCol cols="12" sm="3">
          <VTextField
            v-model="dateToFilter"
            :label="t('IT.Transaction.DateTo')"
            type="date"
            clearable
            @update:model-value="fetchItems"
          />
        </VCol>
      </VRow>
    </VCardText>

    <VDataTableServer
      v-model:items-per-page="itemsPerPage"
      v-model:page="page"
      v-model:sort-by="sortBy"
      v-model:sort-desc="orderBy"
      :headers="headers"
      :items="serverItems"
      :items-length="totalItems"
      :loading="loading"
      density="comfortable"
      hover
      @update:options="fetchItems"
    >
      <template #item.type="{ item }">
        <VChip :color="resolveTypeColor(item.type)" size="small">
          {{ item.type }}
        </VChip>
      </template>
      <template #item.date="{ item }">
        {{ item.date ? new Date(item.date).toLocaleDateString('it-IT') : 'N/A' }}
      </template>
    </VDataTableServer>
  </VCard>
</template>
