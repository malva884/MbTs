<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'

const { t } = useI18n()
const router = useRouter()

const loading = ref(false)
const name = ref('')
const type = ref('')
const description = ref('')
const parent_id = ref('')

const locations = ref([])

const fetchLocations = async () => {
  try {
    const { data } = await useApi.get('/it/locations', {
      params: { itemsPerPage: 1000 },
    })
    locations.value = data.data
  } catch (error) {
    console.error('Error fetching locations:', error)
  }
}

const createLocation = async () => {
  if (!name.value) {
    alert('Inserisci il nome della location.')
    return
  }
  if (!type.value) {
    alert('Seleziona il tipo di location.')
    return
  }

  loading.value = true
  try {
    const payload = {
      name: name.value,
      type: type.value,
      description: description.value,
      parent_id: parent_id.value || null,
    }
    console.log('Creating location with payload:', payload)

    await useApi.post('/it/locations/store', payload)
    router.push('/it/locations/list')
  } catch (error: any) {
    console.error('Error creating location:', error)
    const messages = error?.data?.errors
      ? Object.values(error.data.errors).flat().join('\n')
      : (error?.data?.message || error?.message)
    alert(messages || 'Errore durante il salvataggio.')
  } finally {
    loading.value = false
  }
}

fetchLocations()
</script>

<template>
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

          <VCol cols="12" md="6">
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
