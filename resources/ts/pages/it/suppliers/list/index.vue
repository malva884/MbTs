<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { can } from '@layouts/plugins/casl'

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
const isEditDialogVisible = ref(false)
const editingItem = ref<any>(null)

const formData = ref({
  name: '',
  contact_person: '',
  email: '',
  phone: '',
  address: '',
  city: '',
  country: '',
  vat_number: '',
  notes: '',
  disabled: false,
})

const headers = computed(() => [
  { title: t('IT.Supplier.Name'), key: 'name', sortable: true },
  { title: t('IT.Supplier.Contact'), key: 'contact_person', sortable: true },
  { title: t('IT.Supplier.Email'), key: 'email', sortable: true },
  { title: t('IT.Supplier.Phone'), key: 'phone', sortable: true },
  { title: t('IT.Supplier.City'), key: 'city', sortable: true },
  { title: t('IT.Supplier.Country'), key: 'country', sortable: true },
  { title: t('IT.Supplier.VAT'), key: 'vat_number', sortable: true },
  { title: t('Label.Azioni'), key: 'actions', sortable: false },
])

const loadItems = async () => {
  loading.value = true
  try {
    const { data: resultData } = await useApi<any>(createUrl('/it/suppliers', {
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
  formData.value = {
    name: '',
    contact_person: '',
    email: '',
    phone: '',
    address: '',
    city: '',
    country: '',
    vat_number: '',
    notes: '',
    disabled: false,
  }
  isDialogVisible.value = true
}

const openEditDialog = (item: any) => {
  editingItem.value = item
  formData.value = {
    name: item.name,
    contact_person: item.contact_person || '',
    email: item.email || '',
    phone: item.phone || '',
    address: item.address || '',
    city: item.city || '',
    country: item.country || '',
    vat_number: item.vat_number || '',
    notes: item.notes || '',
    disabled: item.disabled || false,
  }
  isEditDialogVisible.value = true
}

const submit = async () => {
  loading.value = true
  try {
    await $api('/it/suppliers/store', {
      method: 'POST',
      body: formData.value,
    })
    isDialogVisible.value = false
    loadItems()
  } catch (e) {
    console.error(e)
    alert('Error: ' + JSON.stringify(e))
  } finally {
    loading.value = false
  }
}

const update = async () => {
  loading.value = true
  try {
    await $api(`/it/suppliers/update/${editingItem.value.id}`, {
      method: 'POST',
      body: formData.value,
    })
    isEditDialogVisible.value = false
    loadItems()
  } catch (e) {
    console.error(e)
    alert('Error: ' + JSON.stringify(e))
  } finally {
    loading.value = false
  }
}

const deleteItem = async (item: any) => {
  if (!confirm(t('Label.ConfermaEliminazione'))) return

  loading.value = true
  try {
    await $api(`/it/suppliers/${item.id}`, {
      method: 'DELETE',
    })
    loadItems()
  } catch (e) {
    console.error(e)
    alert('Error: ' + JSON.stringify(e))
  } finally {
    loading.value = false
  }
}

loadItems()
</script>

<template>
  <VCard flat border>
    <VCardItem>
      <template #prepend>
        <VAvatar color="primary" variant="tonal" size="40">
          <VIcon icon="tabler-building-factory-2" />
        </VAvatar>
      </template>
      <VCardTitle>{{ t('IT.Suppliers') }}</VCardTitle>
    </VCardItem>

    <VDivider />

    <VCardText class="pa-4">
      <VRow>
        <VCol cols="12" sm="6">
          <VTextField
            v-model="search"
            :label="t('Label.Cerca')"
            prepend-inner-icon="tabler-search"
            clearable
            @keyup.enter="loadItems"
          />
        </VCol>

        <VCol cols="12" sm="6" class="d-flex justify-end">
          <VBtn v-if="can('manage', 'IT-Suppliers')" color="primary" prepend-icon="tabler-plus" @click="openCreateDialog">
            {{ t('Label.Nuovo') }}
          </VBtn>
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
      <template #item.actions="{ item }">
        <VBtn icon="tabler-pencil" size="small" variant="text" @click="openEditDialog(item)" />
        <VBtn icon="tabler-trash" size="small" variant="text" color="error" @click="deleteItem(item)" />
      </template>
    </VDataTableServer>
  </VCard>

  <!-- Create Dialog -->
  <VDialog v-model="isDialogVisible" max-width="600px">
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between pa-4">
        <span>{{ t('IT.Supplier.New') }}</span>
        <VBtn icon="tabler-x" variant="text" @click="isDialogVisible = false" />
      </VCardTitle>

      <VCardText class="pa-4">
        <VRow>
          <VCol cols="12">
            <VTextField v-model="formData.name" :label="t('IT.Supplier.Name')" required />
          </VCol>

          <VCol cols="12" sm="6">
            <VTextField v-model="formData.contact_person" :label="t('IT.Supplier.Contact')" />
          </VCol>

          <VCol cols="12" sm="6">
            <VTextField v-model="formData.email" :label="t('IT.Supplier.Email')" type="email" />
          </VCol>

          <VCol cols="12" sm="6">
            <VTextField v-model="formData.phone" :label="t('IT.Supplier.Phone')" />
          </VCol>

          <VCol cols="12" sm="6">
            <VTextField v-model="formData.vat_number" :label="t('IT.Supplier.VAT')" />
          </VCol>

          <VCol cols="12">
            <VTextField v-model="formData.address" :label="t('IT.Supplier.Address')" />
          </VCol>

          <VCol cols="12" sm="6">
            <VTextField v-model="formData.city" :label="t('IT.Supplier.City')" />
          </VCol>

          <VCol cols="12" sm="6">
            <VTextField v-model="formData.country" :label="t('IT.Supplier.Country')" />
          </VCol>

          <VCol cols="12">
            <VTextarea v-model="formData.notes" :label="t('IT.Assignment.Notes')" rows="2" />
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

  <!-- Edit Dialog -->
  <VDialog v-model="isEditDialogVisible" max-width="600px">
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between pa-4">
        <span>{{ t('IT.Supplier.Edit') }}</span>
        <VBtn icon="tabler-x" variant="text" @click="isEditDialogVisible = false" />
      </VCardTitle>

      <VCardText class="pa-4">
        <VRow>
          <VCol cols="12">
            <VTextField v-model="formData.name" :label="t('IT.Supplier.Name')" required />
          </VCol>

          <VCol cols="12" sm="6">
            <VTextField v-model="formData.contact_person" :label="t('IT.Supplier.Contact')" />
          </VCol>

          <VCol cols="12" sm="6">
            <VTextField v-model="formData.email" :label="t('IT.Supplier.Email')" type="email" />
          </VCol>

          <VCol cols="12" sm="6">
            <VTextField v-model="formData.phone" :label="t('IT.Supplier.Phone')" />
          </VCol>

          <VCol cols="12" sm="6">
            <VTextField v-model="formData.vat_number" :label="t('IT.Supplier.VAT')" />
          </VCol>

          <VCol cols="12">
            <VTextField v-model="formData.address" :label="t('IT.Supplier.Address')" />
          </VCol>

          <VCol cols="12" sm="6">
            <VTextField v-model="formData.city" :label="t('IT.Supplier.City')" />
          </VCol>

          <VCol cols="12" sm="6">
            <VTextField v-model="formData.country" :label="t('IT.Supplier.Country')" />
          </VCol>

          <VCol cols="12">
            <VTextarea v-model="formData.notes" :label="t('IT.Assignment.Notes')" rows="2" />
          </VCol>

          <VCol cols="12">
            <VSwitch v-model="formData.disabled" :label="t('Label.Disabled')" />
          </VCol>
        </VRow>
      </VCardText>

      <VCardActions class="pa-4">
        <VSpacer />
        <VBtn variant="text" @click="isEditDialogVisible = false">
          {{ t('Label.Chiudi') }}
        </VBtn>
        <VBtn color="primary" :loading="loading" @click="update">
          {{ t('Label.Salva') }}
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>
