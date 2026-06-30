<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'

const { t } = useI18n()
const router = useRouter()

const emit = defineEmits(['success'])

const isDialogVisible = ref(false)
const loading = ref(false)
const serialNumbers = ref('')
const assetTags = ref('')
const category_id = ref('')
const location_id = ref('')
const brand = ref('')
const model = ref('')
const supplier_id = ref('')
const purchase_date = ref('')
const warranty_expiry = ref('')
const notes = ref('')

const categories = ref([])
const locations = ref([])
const suppliers = ref([])
const brands = ref([])

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

const fetchSuppliers = async () => {
  try {
    const { data } = await useApi<any>('/it/suppliers')
    if (data.value && data.value.data && Array.isArray(data.value.data)) {
      suppliers.value = data.value.data
    } else if (data.value && Array.isArray(data.value)) {
      suppliers.value = data.value
    } else {
      suppliers.value = []
    }
  } catch (error) {
    console.error('Error fetching suppliers:', error)
    suppliers.value = []
  }
}

const fetchBrands = async () => {
  try {
    const { data } = await useApi<any>('/it/assets/brands')
    if (data.value && Array.isArray(data.value)) {
      brands.value = data.value
    } else {
      brands.value = []
    }
  } catch (error) {
    console.error('Error fetching brands:', error)
    brands.value = []
  }
}

const submit = async () => {
  loading.value = true
  try {
    const serialArray = serialNumbers.value.split('\n').map(s => s.trim()).filter(s => s)
    const tagArray = assetTags.value.split('\n').map(s => s.trim()).filter(s => s)

    if (serialArray.length === 0) {
      alert(t('IT.BulkAdd.SerialNumbers') + ' required')
      loading.value = false
      return
    }

    if (!category_id.value) {
      alert(t('IT.Categories') + ' required')
      loading.value = false
      return
    }

    if (!brand.value) {
      alert(t('IT.Asset.Brand') + ' required')
      loading.value = false
      return
    }

    if (!model.value) {
      alert(t('IT.Asset.Model') + ' required')
      loading.value = false
      return
    }

    const { data } = await $api('/it/assets/bulk_store', {
      method: 'POST',
      body: {
        category_id: category_id.value,
        location_id: location_id.value,
        brand: brand.value,
        model: model.value,
        supplier_id: supplier_id.value,
        purchase_date: purchase_date.value,
        warranty_expiry: warranty_expiry.value,
        serial_numbers: serialArray,
        asset_tags: tagArray.length > 0 ? tagArray : undefined,
        notes: notes.value,
      },
    })


    isDialogVisible.value = false
    resetForm()
    emit('success')
  } catch (e) {
    console.error('Bulk add error:', e)
    alert('Error: ' + JSON.stringify(e))
  } finally {
    loading.value = false
  }
}

const resetForm = () => {
  serialNumbers.value = ''
  assetTags.value = ''
  category_id.value = ''
  location_id.value = ''
  brand.value = ''
  model.value = ''
  supplier_id.value = ''
  purchase_date.value = ''
  warranty_expiry.value = ''
  notes.value = ''
}

fetchCategories()
fetchLocations()
fetchSuppliers()
fetchBrands()
</script>

<template>
  <VDialog v-model="isDialogVisible" max-width="800px">
    <template #activator="{ props }">
      <VBtn v-bind="props" color="primary" prepend-icon="tabler-plus">
        {{ t('IT.BulkAdd') }}
      </VBtn>
    </template>

    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between pa-4">
        <span>{{ t('IT.BulkAdd') }}</span>
        <VBtn icon="tabler-x" variant="text" @click="isDialogVisible = false" />
      </VCardTitle>

      <VCardText class="pa-4">
        <VRow>
          <VCol cols="12">
            <VSelect
              v-model="category_id"
              :label="t('IT.Categories')"
              :items="categories"
              item-title="name"
              item-value="id"
              required
            />
          </VCol>

          <VCol cols="12" sm="6">
            <VSelect
              v-model="location_id"
              :label="t('IT.Locations')"
              :items="locations"
              item-title="name"
              item-value="id"
            />
          </VCol>

          <VCol cols="12" sm="6">
            <VAutocomplete
              v-model="brand"
              :label="t('IT.Asset.Brand')"
              :items="brands"
              clearable
              clear-icon="tabler-x"
              required
            />
          </VCol>

          <VCol cols="12" sm="6">
            <VTextField v-model="model" :label="t('IT.Asset.Model')" required />
          </VCol>

          <VCol cols="12" sm="6">
            <VSelect
              v-model="supplier_id"
              :label="t('IT.Asset.Supplier')"
              :items="suppliers"
              item-title="name"
              item-value="id"
              clearable
              clear-icon="tabler-x"
            />
          </VCol>

          <VCol cols="12" sm="6">
            <VTextField v-model="purchase_date" type="date" :label="t('IT.Asset.PurchaseDate')" />
          </VCol>


          <VCol cols="12" sm="6">
            <VTextField v-model="warranty_expiry" type="date" :label="t('IT.Asset.WarrantyExpiry')" />
          </VCol>

          <VCol cols="12">
            <VTextarea
              v-model="serialNumbers"
              :label="t('IT.BulkAdd.SerialNumbers')"
              :placeholder="t('IT.BulkAdd.OnePerLine')"
              rows="5"
              required
            />
          </VCol>

          <VCol cols="12">
            <VTextarea
              v-model="assetTags"
              :label="t('IT.BulkAdd.AssetTags')"
              :placeholder="t('IT.BulkAdd.OnePerLine')"
              rows="3"
            />
          </VCol>

          <VCol cols="12">
            <VTextarea v-model="notes" :label="t('IT.Assignment.Notes')" rows="2" />
          </VCol>
        </VRow>
      </VCardText>

      <VCardActions class="pa-4">
        <VSpacer />
        <VBtn variant="text" @click="isDialogVisible = false">
          {{ t('Label.Chiudi') }}
        </VBtn>
        <VBtn color="primary" :loading="loading" @click="submit">
          {{ t('Label.Salva') }}
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
