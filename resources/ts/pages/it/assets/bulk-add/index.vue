<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'

definePage({
  meta: {
    action: 'list',
    subject: 'Plant-Asset',
  },
})

const { t } = useI18n()
const router = useRouter()

const props = defineProps<{
  brand?: string
  model?: string
  category_id?: string
  location_id?: string
  mode?: 'bulk' | 'restock'
}>()

const emit = defineEmits(['success'])

const isDialogVisible = ref(false)
const loading = ref(false)
const serialNumbers = ref('')
const assetTags = ref('')
const autoGenerateTags = ref(true)
const parent_category_id = ref('')
const subcategory_id = ref('')
const location_id = ref(props.location_id || '')
const brand = ref(props.brand || '')
const model = ref(props.model || '')
const supplier_id = ref('')
const purchase_date = ref('')
const warranty_expiry = ref('')
const notes = ref('')

// Errori di validazione
const errors = ref({
  serialNumbers: '',
  category: '',
  brand: '',
  model: '',
  location: '',
  purchaseDate: '',
})

const categories = ref([])
const locations = ref([])
const suppliers = ref([])
const brands = ref([])

// Computed: categorie padre (senza parent_id)
const parentCategories = computed(() => {
  return categories.value.filter((cat: any) => !cat.parent_id)
})

// Computed: sottocategorie della categoria padre selezionata
const subcategories = computed(() => {
  if (!parent_category_id.value) return []
  return categories.value.filter((cat: any) => cat.parent_id === parent_category_id.value)
})

// Computed: categoria_id da inviare (sottocategoria se selezionata, altrimenti categoria padre)
const category_id = computed(() => {
  return subcategory_id.value || parent_category_id.value
})

// Computed: modalità quick add (quando props sono forniti o mode è restock)
const isQuickAdd = computed(() => {
  return props.mode === 'restock' || !!(props.brand && props.model && props.category_id)
})

// Imposta parent_category_id dal props quando disponibile
watch(() => props.category_id, (newVal) => {
  if (newVal) {
    parent_category_id.value = newVal
  }
}, { immediate: true })

watch(() => props.brand, (newVal) => {
  if (newVal) brand.value = newVal
}, { immediate: true })

watch(() => props.model, (newVal) => {
  if (newVal) model.value = newVal
}, { immediate: true })

watch(() => props.location_id, (newVal) => {
  if (newVal) location_id.value = newVal
}, { immediate: true })

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

const fetchNextAssetTag = async () => {
  // Usa solo categorie padre, non sottocategorie
  const categoryIdToUse = parent_category_id.value
  if (!categoryIdToUse) return null
  
  try {
    const { data } = await useApi<any>(createUrl('/it/assets/next-asset-tag', {
      query: { category_id: categoryIdToUse },
    }))
    return data.value?.next_tag || null
  } catch (error) {
    console.error('Error fetching next asset tag:', error)
    return null
  }
}

const generateAssetTags = async () => {
  if (!autoGenerateTags.value || !parent_category_id.value) return
  
  const serialArray = serialNumbers.value.split('\n').map(s => s.trim()).filter(s => s)
  if (serialArray.length === 0) {
    assetTags.value = ''
    return
  }
  
  const baseTag = await fetchNextAssetTag()
  if (!baseTag) return
  
  // Estrai prefix e numero base
  const match = baseTag.match(/^([A-Z]{3})(\d{4})$/)
  if (!match) return
  
  const prefix = match[1]
  const baseNumber = parseInt(match[2], 10)
  
  // Genera tag incrementali per ogni serial number
  const tags = serialArray.map((_, index) => {
    const nextNumber = baseNumber + index
    return prefix + String(nextNumber).padStart(4, '0')
  })
  
  assetTags.value = tags.join('\n')
}

const submit = async () => {
  // Reset errori
  errors.value = {
    serialNumbers: '',
    category: '',
    brand: '',
    model: '',
    location: '',
    purchaseDate: '',
  }

  const serialArray = serialNumbers.value.split('\n').map(s => s.trim()).filter(s => s)
  const tagArray = assetTags.value.split('\n').map(s => s.trim()).filter(s => s)

  let hasError = false

  if (serialArray.length === 0) {
    errors.value.serialNumbers = t('IT.BulkAdd.SerialNumbers') + ' required'
    hasError = true
  }

  if (!category_id.value) {
    errors.value.category = t('IT.Category.Parent') + ' required'
    hasError = true
  }

  if (!brand.value) {
    errors.value.brand = t('IT.Asset.Brand') + ' required'
    hasError = true
  }

  if (!model.value) {
    errors.value.model = t('IT.Asset.Model') + ' required'
    hasError = true
  }

  if (!location_id.value) {
    errors.value.location = t('IT.Locations') + ' required'
    hasError = true
  }

  if (!purchase_date.value) {
    errors.value.purchaseDate = t('IT.Asset.PurchaseDate') + ' required'
    hasError = true
  } else {
    // Validazione formato data YYYY-MM-DD
    const dateRegex = /^\d{4}-\d{2}-\d{2}$/
    if (!dateRegex.test(purchase_date.value)) {
      errors.value.purchaseDate = 'Formato data non valido. Usa YYYY-MM-DD'
      hasError = true
    } else {
      // Verifica che sia una data valida
      const date = new Date(purchase_date.value)
      if (isNaN(date.getTime())) {
        errors.value.purchaseDate = 'Data non valida'
        hasError = true
      }
    }
  }

  if (hasError) {
    return
  }

  loading.value = true
  try {

    const { data } = await $api('/it/assets/bulk_store', {
      method: 'POST',
      body: {
        category_id: category_id.value,
        location_id: location_id.value,
        brand: brand.value,
        model: model.value,
        supplier_id: supplier_id.value || null,
        purchase_date: purchase_date.value,
        warranty_expiry: warranty_expiry.value || null,
        serial_numbers: serialArray,
        asset_tags: tagArray.length > 0 ? tagArray : undefined,
        notes: notes.value || null,
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
  parent_category_id.value = ''
  subcategory_id.value = ''
  location_id.value = ''
  brand.value = ''
  model.value = ''
  supplier_id.value = ''
  purchase_date.value = ''
  warranty_expiry.value = ''
  notes.value = ''
}

// Reset sottocategoria quando cambia categoria padre
const handleParentCategoryChange = () => {
  subcategory_id.value = ''
}

// Watch per generare automaticamente i tag quando cambia categoria padre o serial numbers
watch([parent_category_id, serialNumbers, autoGenerateTags], async () => {
  if (autoGenerateTags.value) {
    await generateAssetTags()
  }
})

fetchCategories()
fetchLocations()
fetchSuppliers()
fetchBrands()
</script>

<template>
  <VDialog v-model="isDialogVisible" max-width="800px">
    <template #activator="{ props }">
      <VBtn v-bind="props" color="primary" prepend-icon="tabler-plus">
        {{ isQuickAdd ? t('IT.Asset.Restock') : t('IT.BulkAdd') }}
      </VBtn>
    </template>

    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between pa-4">
        <span>{{ t('IT.BulkAdd') }}</span>
        <VBtn icon="tabler-x" variant="text" @click="isDialogVisible = false" />
      </VCardTitle>

      <VCardText class="pa-4">
        <VRow>
          <!-- Info gruppo in modalità quick add -->
          <VCol v-if="isQuickAdd" cols="12">
            <VAlert type="info" variant="tonal" density="compact">
              <div class="text-body-2">
                <div><strong>{{ t('IT.Asset.Brand') }}:</strong> {{ brand || '-' }}</div>
                <div><strong>{{ t('IT.Asset.Model') }}:</strong> {{ model || '-' }}</div>
                <div><strong>{{ t('IT.Categories') }}:</strong> {{ parentCategories.find(c => c.id === parent_category_id)?.name || subcategories.find(c => c.id === subcategory_id)?.name || '-' }}</div>
                <div><strong>{{ t('IT.Locations') }}:</strong> {{ locations.find(l => l.id === location_id)?.name || '-' }}</div>
              </div>
            </VAlert>
          </VCol>

          <!-- Campi categoria (solo se non quick add) -->
          <template v-if="!isQuickAdd">
            <VCol cols="12" sm="6">
              <VSelect
                v-model="parent_category_id"
                :label="t('IT.Category.Parent')"
                :items="parentCategories"
                item-title="name"
                item-value="id"
                required
                :error="!!errors.category"
                :error-messages="errors.category"
                @update:model-value="handleParentCategoryChange"
              />
            </VCol>

            <VCol cols="12" sm="6">
              <VSelect
                v-model="subcategory_id"
                :label="t('IT.Category.Subcategory')"
                :items="subcategories"
                item-title="name"
                item-value="id"
                clearable
                :disabled="!parent_category_id"
              />
            </VCol>
          </template>

          <!-- Campo ubicazione (solo se non quick add) -->
          <VCol v-if="!isQuickAdd" cols="12" sm="6">
            <VSelect
              v-model="location_id"
              :label="t('IT.Locations')"
              :items="locations"
              item-title="name"
              item-value="id"
              required
              :error="!!errors.location"
              :error-messages="errors.location"
            />
          </VCol>

          <!-- Campi brand/model (solo se non quick add) -->
          <template v-if="!isQuickAdd">
            <VCol cols="12" sm="6">
              <VTextField
                v-model="brand"
                :label="t('IT.Asset.Brand')"
                list="brands-list"
                clearable
                clear-icon="tabler-x"
                required
                :error="!!errors.brand"
                :error-messages="errors.brand"
              />
              <datalist id="brands-list">
                <option v-for="b in brands" :key="b" :value="b" />
              </datalist>
            </VCol>

            <VCol cols="12" sm="6">
              <VTextField
                v-model="model"
                :label="t('IT.Asset.Model')"
                required
                :error="!!errors.model"
                :error-messages="errors.model"
              />
            </VCol>
          </template>

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
            <VTextField
              v-model="purchase_date"
              type="date"
              :label="t('IT.Asset.PurchaseDate')"
              required
              :error="!!errors.purchaseDate"
              :error-messages="errors.purchaseDate"
            />
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
              :error="!!errors.serialNumbers"
              :error-messages="errors.serialNumbers"
            />
          </VCol>

          <VCol cols="12">
            <VTextarea
              v-model="assetTags"
              :label="t('IT.BulkAdd.AssetTags')"
              :placeholder="t('IT.BulkAdd.OnePerLine')"
              rows="3"
              :readonly="autoGenerateTags"
            />
          </VCol>

          <VCol cols="12">
            <VCheckbox
              v-model="autoGenerateTags"
              label="Genera automaticamente codici asset (prime 3 lettere categoria + numero incrementale)"
              @update:model-value="generateAssetTags"
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
