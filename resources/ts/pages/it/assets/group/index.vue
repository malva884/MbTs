<script setup lang="ts">
import { VDataTable } from 'vuetify/labs/VDataTable'
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import moment from 'moment'

const route = useRoute()
const router = useRouter()
const { t } = useI18n()

const assets = ref<any[]>([])
const loading = ref(true)
const groupInfo = ref<any>(null)
const minStock = ref(0)
const savingMinStock = ref(false)
const suppliers = ref<any[]>([])
const suppliersLoading = ref(false)

const supplierHeaders = computed(() => [
  { title: t('IT.Supplier.Name'), key: 'name', sortable: true },
  { title: t('IT.Categories'), key: 'categories', sortable: false },
  { title: t('IT.Supplier.Site'), key: 'product_link', sortable: false },
  { title: t('IT.Supplier.Price'), key: 'unit_cost', sortable: true },
])

const isLowStock = computed(() => groupInfo.value && (groupInfo.value.available_count || 0) <= (minStock.value || 0) && (minStock.value || 0) > 0)

const resolveStatusColor = (status: string) => {
  const colors: Record<string, string> = {
    'Available': 'success',
    'Assigned': 'primary',
    'In Repair': 'warning',
    'Retired': 'secondary',
    'Lost': 'error',
  }

  return colors[status] || 'secondary'
}

const formatDate = (date: string) => {
  if (!date)
    return '--'

  return moment(date).format('DD/MM/YYYY')
}

const formatDomain = (url: string) => {
  if (!url)
    return ''
  try {
    return new URL(url).hostname.replace(/^www\./, '')
  }
  catch {
    return url
  }
}

const formatCurrency = (value: number | null | undefined) => {
  if (value === null || value === undefined || value === '')
    return '--'
  const num = typeof value === 'string' ? Number.parseFloat(value) : value
  if (Number.isNaN(num))
    return '--'

  return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(num)
}

const availableCount = computed(() => assets.value.filter(a => a.status === 'Available').length)
const assignedCount = computed(() => assets.value.filter(a => a.status === 'Assigned').length)

const groupTitle = computed(() => {
  const brand = (route.query.brand || '').toString().trim()
  const model = (route.query.model || '').toString().trim()
  if (!brand && !model)
    return ''
  if (model.toLowerCase().startsWith(brand.toLowerCase()) || brand.toLowerCase().startsWith(model.toLowerCase()))
    return model || brand

  return `${brand} ${model}`.trim()
})

const groupCategories = computed(() => {
  const names = assets.value.map(a => a.category?.name).filter(Boolean)

  return [...new Set(names)]
})

const groupLocations = computed(() => {
  const names = assets.value.map(a => a.location?.name).filter(Boolean)

  return [...new Set(names)]
})

const headers = computed(() => [
  { title: t('IT.Asset.SerialNumber'), key: 'serial_number', sortable: true },
  { title: t('IT.Locations'), key: 'location.name', sortable: false },
  { title: t('IT.Asset.Status'), key: 'status', sortable: true },
  { title: t('IT.Asset.PurchaseDate'), key: 'purchase_date', sortable: true },
  { title: t('IT.Asset.WarrantyExpiry'), key: 'warranty_expiry', sortable: true },
  { title: t('Label.Azioni'), key: 'actions', sortable: false },
])

const loadAssets = async () => {
  loading.value = true
  try {
    const { data } = await useApi<any>(createUrl('/it/assets', {
      query: {
        brand: route.query.brand,
        model: route.query.model,
        itemsPerPage: 1000,
      },
    }))

    if (data.value && data.value.data)
      assets.value = data.value.data
    else
      assets.value = []

    if (route.query.brand || route.query.model) {
      const { data: groupData } = await useApi<any>(createUrl('/it/assets', {
        query: {
          grouped: 'true',
          brand: route.query.brand,
          model: route.query.model,
        },
      }))

      if (groupData.value && groupData.value.data && groupData.value.data.length > 0) {
        groupInfo.value = groupData.value.data[0]
        minStock.value = groupInfo.value.min_stock || 0
      }
    }
  }
  catch (e) {
    console.error(e)
  }
  finally {
    loading.value = false
  }
}

const loadSuppliers = async () => {
  suppliersLoading.value = true
  try {
    const { data } = await useApi<any>(createUrl('/it/asset-groups/suppliers', {
      query: {
        brand: route.query.brand,
        model: route.query.model,
      },
    }))

    suppliers.value = data.value || []
  }
  catch (e) {
    console.error(e)
    suppliers.value = []
  }
  finally {
    suppliersLoading.value = false
  }
}

onMounted(() => {
  loadAssets()
  loadSuppliers()
})

const saveMinStock = async () => {
  savingMinStock.value = true
  try {
    await $api('/it/asset-groups/update_or_create', {
      method: 'POST',
      body: {
        brand: route.query.brand,
        model: route.query.model,
        min_stock: minStock.value,
      },
    })
    if (groupInfo.value)
      groupInfo.value.min_stock = minStock.value
  }
  catch (e) {
    console.error(e)
  }
  finally {
    savingMinStock.value = false
  }
}
</script>

<template>
  <VCard
    flat
    border
  >
    <VCardItem>
      <template #prepend>
        <VAvatar
          color="primary"
          variant="tonal"
          size="40"
        >
          <VIcon icon="tabler-device-laptop" />
        </VAvatar>
      </template>
      <VCardTitle>
        {{ groupTitle }}
        <VBtn
          icon="tabler-arrow-left"
          size="small"
          variant="text"
          class="ms-2"
          @click="router.push({ name: 'it-assets-list' })"
        />
      </VCardTitle>
    </VCardItem>

    <VDivider />

    <VCardText class="pa-4">
      <VRow>
        <VCol
          cols="12"
          lg="8"
        >
          <VCard
            flat
            border
          >
            <VCardItem>
              <VCardTitle>{{ t('IT.Group.Info') }}</VCardTitle>
            </VCardItem>
            <VDivider />
            <VCardText class="pa-4">
              <VRow>
                <VCol
                  cols="12"
                  sm="6"
                >
                  <VTextField
                    :model-value="route.query.brand || '--'"
                    :label="t('IT.Asset.Brand')"
                    readonly
                    density="comfortable"
                  />
                </VCol>
                <VCol
                  cols="12"
                  sm="6"
                >
                  <VTextField
                    :model-value="route.query.model || '--'"
                    :label="t('IT.Asset.Model')"
                    readonly
                    density="comfortable"
                  />
                </VCol>
                <VCol
                  cols="12"
                  sm="6"
                >
                  <VTextField
                    :model-value="groupCategories.join(', ') || '--'"
                    :label="t('IT.Categories')"
                    readonly
                    density="comfortable"
                  />
                </VCol>
                <VCol
                  cols="12"
                  sm="6"
                >
                  <VTextField
                    :model-value="groupLocations.join(', ') || '--'"
                    :label="t('IT.Locations')"
                    readonly
                    density="comfortable"
                  />
                </VCol>
              </VRow>
            </VCardText>
          </VCard>

          <VCard
            flat
            border
            class="mt-4"
          >
            <VCardItem>
              <VCardTitle>{{ t('IT.Inventory') }}</VCardTitle>
            </VCardItem>
            <VDivider />
            <VDataTable
              :headers="headers"
              :items="assets"
              :loading="loading"
              :items-per-page="-1"
              density="comfortable"
              hover
            >
              <template #item.location.name="{ item }">
                {{ item.location?.name || '--' }}
              </template>

              <template #item.status="{ item }">
                <VChip
                  :color="resolveStatusColor(item.status)"
                  size="small"
                  variant="flat"
                >
                  {{ t(`IT.Asset.Status.${item.status.replace(' ', '')}`) }}
                </VChip>
              </template>

              <template #item.purchase_date="{ item }">
                {{ formatDate(item.purchase_date) }}
              </template>

              <template #item.warranty_expiry="{ item }">
                {{ formatDate(item.warranty_expiry) }}
              </template>

              <template #item.actions="{ item }">
                <VBtn
                  icon="tabler-eye"
                  size="small"
                  variant="text"
                  :to="{ name: 'it-assets-view-id', params: { id: item.id } }"
                />
              </template>
            </VDataTable>
          </VCard>
        </VCol>

        <VCol
          cols="12"
          lg="4"
        >
          <VCard
            flat
            border
          >
            <VCardItem>
              <VCardTitle>{{ t('IT.Info') }}</VCardTitle>
            </VCardItem>
            <VDivider />
            <VCardText class="pa-4">
              <VRow>
                <VCol
                  cols="12"
                  sm="6"
                >
                  <VCard
                    variant="tonal"
                    color="primary"
                  >
                    <VCardText class="text-center">
                      <div class="text-h4">
                        {{ groupInfo?.total_quantity ?? 0 }}
                      </div>
                      <div class="text-caption">
                        {{ t('IT.Asset.Quantity') }}
                      </div>
                    </VCardText>
                  </VCard>
                </VCol>
                <VCol
                  cols="12"
                  sm="6"
                >
                  <VCard
                    variant="tonal"
                    color="success"
                  >
                    <VCardText class="text-center">
                      <div class="text-h4">
                        {{ groupInfo?.total_stock ?? 0 }}
                      </div>
                      <div class="text-caption">
                        {{ t('IT.Asset.Stock') }}
                      </div>
                    </VCardText>
                  </VCard>
                </VCol>
                <VCol
                  cols="12"
                  sm="6"
                >
                  <VCard
                    variant="tonal"
                    color="info"
                  >
                    <VCardText class="text-center">
                      <div class="text-h4">
                        {{ availableCount }}
                      </div>
                      <div class="text-caption">
                        {{ t('IT.Asset.Status.Available') }}
                      </div>
                    </VCardText>
                  </VCard>
                </VCol>
                <VCol
                  cols="12"
                  sm="6"
                >
                  <VCard
                    variant="tonal"
                    color="warning"
                  >
                    <VCardText class="text-center">
                      <div class="text-h4">
                        {{ assignedCount }}
                      </div>
                      <div class="text-caption">
                        {{ t('IT.Asset.Status.Assigned') }}
                      </div>
                    </VCardText>
                  </VCard>
                </VCol>
              </VRow>

              <VDivider class="my-4" />

              <VTextField
                v-model="minStock"
                :label="t('IT.Asset.MinStock')"
                type="number"
                min="0"
                density="comfortable"
                hide-details
              />
              <VBtn
                color="primary"
                size="small"
                class="mt-3"
                :loading="savingMinStock"
                @click="saveMinStock"
              >
                {{ t('Label.Salva') }}
              </VBtn>
              <VAlert
                v-if="isLowStock"
                type="error"
                variant="tonal"
                density="compact"
                class="mt-3"
              >
                {{ t('IT.Asset.LowStockWarning') }}
              </VAlert>
            </VCardText>
          </VCard>

          <VCard
            flat
            border
            class="mt-4"
          >
            <VCardItem>
              <VCardTitle>{{ t('IT.Suppliers') }}</VCardTitle>
            </VCardItem>
            <VDivider />
            <VDataTable
              :headers="supplierHeaders"
              :items="suppliers"
              :loading="suppliersLoading"
              :items-per-page="-1"
              density="compact"
              hover
            >
              <template #item.product_link="{ item }">
                <a
                  v-if="item.product_link"
                  :href="item.product_link"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="text-primary"
                >
                  {{ formatDomain(item.product_link) }}
                </a>
                <span
                  v-else
                  class="text-disabled"
                >--</span>
              </template>

              <template #item.unit_cost="{ item }">
                {{ formatCurrency(item.unit_cost) }}
              </template>

              <template #no-data>
                <div class="text-center text-disabled pa-4">
                  {{ t('IT.Suppliers.NoData') }}
                </div>
              </template>
            </VDataTable>
          </VCard>
        </VCol>
      </VRow>
    </VCardText>
  </VCard>
</template>
