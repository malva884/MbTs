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
const isEditDialogVisible = ref(false)
const editDialogLoading = ref(false)
const editId = ref('')
const name = ref('')
const description = ref('')
const parent_id = ref('')
const require_label = ref(false)
const categories = ref([])

const headers = computed(() => [
  { title: t('IT.Category.Name'), key: 'name', sortable: true },
  { title: t('IT.Category.Description'), key: 'description', sortable: false },
  { title: t('IT.Category.Parent'), key: 'parent.name', sortable: false },
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
    const { data: resultData, error } = await useApi<any>(createUrl('/it/categories', {
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
    console.error('Error fetching categories:', error)
    loading.value = false
  }
}

const fetchCategories = async () => {
  try {
    const { data } = await useApi<any>(createUrl('/it/categories', {
      query: { itemsPerPage: 1000 },
    }))
    if (data.value && data.value.data && Array.isArray(data.value.data)) {
      categories.value = data.value.data
    } else {
      categories.value = []
    }
  } catch (error) {
    console.error('Error fetching categories:', error)
    categories.value = []
  }
}

const deleteItem = async (id: string) => {
  if (confirm(t('Label.ConfirmDelete'))) {
    try {
      await useApi.delete(`/it/categories/${id}`)
      loadItems()
    } catch (error) {
      console.error('Error deleting category:', error)
    }
  }
}

const openDialog = () => {
  name.value = ''
  description.value = ''
  parent_id.value = ''
  require_label.value = false
  isDialogVisible.value = true
}

const openEditDialog = async (item: any) => {
  editId.value = item.id
  name.value = item.name
  description.value = item.description
  parent_id.value = item.parent_id
  require_label.value = item.require_label
  isEditDialogVisible.value = true
}

const createCategory = async () => {
  dialogLoading.value = true
  try {
    const returnData = await $api('/it/categories/store', {
      method: 'POST',
      body: {
        name: name.value,
        description: description.value,
        parent_id: parent_id.value || null,
        require_label: require_label.value,
      },
    })
    console.log('Category created:', returnData)
    isDialogVisible.value = false
    loadItems()
  } catch (error) {
    console.error('Error creating category:', error)
    alert('Error creating category: ' + JSON.stringify(error))
  } finally {
    dialogLoading.value = false
  }
}

const updateCategory = async () => {
  editDialogLoading.value = true
  try {
    const returnData = await $api(`/it/categories/update/${editId.value}`, {
      method: 'POST',
      body: {
        name: name.value,
        description: description.value,
        parent_id: parent_id.value || null,
        require_label: require_label.value,
      },
    })
    console.log('Category updated:', returnData)
    isEditDialogVisible.value = false
    loadItems()
  } catch (error) {
    console.error('Error updating category:', error)
    alert('Error updating category: ' + JSON.stringify(error))
  } finally {
    editDialogLoading.value = false
  }
}

loadItems()
fetchCategories()
</script>

<template>
  <VCard flat border>
    <VCardItem>
      <template #prepend>
        <VAvatar color="primary" variant="tonal" size="40">
          <VIcon icon="tabler-category" />
        </VAvatar>
      </template>
      <VCardTitle>{{ t('IT.Categories') }}</VCardTitle>
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
          <VIcon icon="tabler-category" size="40" class="text-disabled mb-2" />
          <p class="text-body-1 text-disabled mb-0">Nessuna categoria trovata</p>
        </div>
      </template>
      <template #item.name="{ item }">
        <span class="font-weight-medium">{{ item.name }}</span>
      </template>
      <template #item.description="{ item }">
        <span class="text-caption">{{ item.description || '-' }}</span>
      </template>
      <template #item.parent.name="{ item }">
        <span class="text-caption">{{ item.parent?.name || '-' }}</span>
      </template>
      <template #item.actions="{ item }">
        <VBtn icon size="small" color="primary" @click="openEditDialog(item)">
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
        <VCardTitle>{{ t('IT.Category.Create') }}</VCardTitle>

        <VCardText>
          <VForm @submit.prevent="createCategory">
            <VRow>
              <VCol cols="12">
                <VTextField
                  v-model="name"
                  :label="t('IT.Category.Name')"
                  required
                />
              </VCol>

              <VCol cols="12">
                <VSelect
                  v-model="parent_id"
                  :label="t('IT.Category.Parent')"
                  :items="categories"
                  item-title="name"
                  item-value="id"
                  clearable
                />
              </VCol>

              <VCol cols="12">
                <VTextarea
                  v-model="description"
                  :label="t('IT.Category.Description')"
                  rows="3"
                />
              </VCol>

              <VCol cols="12">
                <VSwitch
                  v-model="require_label"
                  :label="t('IT.Category.RequireLabel')"
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

    <!-- Edit Dialog -->
    <VDialog v-model="isEditDialogVisible" max-width="600">
      <VCard>
        <VCardTitle>{{ t('IT.Category.Edit') }}</VCardTitle>

        <VCardText>
          <VForm @submit.prevent="updateCategory">
            <VRow>
              <VCol cols="12">
                <VTextField
                  v-model="name"
                  :label="t('IT.Category.Name')"
                  required
                />
              </VCol>

              <VCol cols="12">
                <VSelect
                  v-model="parent_id"
                  :label="t('IT.Category.Parent')"
                  :items="categories"
                  item-title="name"
                  item-value="id"
                  clearable
                />
              </VCol>

              <VCol cols="12">
                <VTextarea
                  v-model="description"
                  :label="t('IT.Category.Description')"
                  rows="3"
                />
              </VCol>

              <VCol cols="12">
                <VSwitch
                  v-model="require_label"
                  :label="t('IT.Category.RequireLabel')"
                />
              </VCol>
            </VRow>

            <VRow>
              <VCol cols="12">
                <VBtn
                  type="submit"
                  color="primary"
                  :loading="editDialogLoading"
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
