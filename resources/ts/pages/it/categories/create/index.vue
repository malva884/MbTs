<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'

const { t } = useI18n()
const router = useRouter()

const loading = ref(false)
const name = ref('')
const description = ref('')
const parent_id = ref('')
const require_label = ref(false)

const categories = ref([])

const fetchCategories = async () => {
  try {
    const { data } = await useApi.get('/it/categories', {
      params: { itemsPerPage: 1000 },
    })
    categories.value = data.data
  } catch (error) {
    console.error('Error fetching categories:', error)
  }
}

const createCategory = async () => {
  loading.value = true
  try {
    await useApi.post('/it/categories/store', {
      name: name.value,
      description: description.value,
      parent_id: parent_id.value || null,
      require_label: require_label.value,
    })
    router.push('/it/categories/list')
  } catch (error) {
    console.error('Error creating category:', error)
  } finally {
    loading.value = false
  }
}

fetchCategories()
</script>

<template>
  <VCard>
    <VCardTitle>{{ t('IT.Category.Create') }}</VCardTitle>

    <VCardText>
      <VForm @submit.prevent="createCategory">
        <VRow>
          <VCol cols="12" md="6">
            <VTextField
              v-model="name"
              :label="t('IT.Category.Name')"
              required
            />
          </VCol>

          <VCol cols="12" md="6">
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
              :loading="loading"
              block
            >
              {{ t('Label.Save') }}
            </VBtn>
          </VCol>
        </VRow>
      </VForm>
    </VCardText>
  </VCard>
</template>
