<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'

const { t } = useI18n()
const router = useRouter()

const loading = ref(false)
const name = ref('')
const code = ref('')
const description = ref('')
const location_id = ref('')
const status = ref('Active')

const locations = ref([])

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

const createMachine = async () => {
  if (!name.value) {
    alert('Inserisci il nome della macchina.')
    return
  }
  if (!code.value) {
    alert('Inserisci il codice della macchina.')
    return
  }

  loading.value = true
  try {
    await $api('/it/machines/store', {
      method: 'POST',
      body: {
        name: name.value,
        code: code.value,
        description: description.value,
        location_id: location_id.value || null,
        status: status.value,
      },
    })

    router.push({ name: 'it-machines-list' })
  } catch (error: any) {
    const messages = error?.data?.errors
      ? Object.values(error.data.errors).flat().join('\n')
      : (error?.data?.message || error?.message)

    console.error('Error creating machine:', error)
    alert(messages || 'Errore durante il salvataggio.')
  } finally {
    loading.value = false
  }
}

fetchLocations()
</script>

<template>
  <div class="workspace-container w-100 d-flex flex-column pa-4 gap-3">
    <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
      <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
        <div class="d-flex align-center gap-2">
          <VIcon icon="tabler-device-desktop-plus" size="24" color="primary" />
          <div>
            <div class="text-h6 font-weight-medium">{{ t('IT.Machine.Create') }}</div>
            <div class="text-caption text-medium-emphasis">Nuova macchina di produzione</div>
          </div>
        </div>
      </VCardText>
      <VDivider />
      <VCardText class="pa-3">
        <VForm @submit.prevent="createMachine">
          <VRow>
            <VCol cols="12" md="6">
              <VTextField
                v-model="name"
                :label="t('IT.Machine.Name')"
                required
              />
            </VCol>

            <VCol cols="12" md="6">
              <VTextField
                v-model="code"
                :label="t('IT.Machine.Code')"
                required
              />
            </VCol>

            <VCol cols="12" md="6">
              <VSelect
                v-model="location_id"
                :label="t('IT.Locations')"
                :items="locations"
                item-title="name"
                item-value="id"
                clearable
                clear-icon="tabler-x"
              />
            </VCol>

            <VCol cols="12" md="6">
              <VSelect
                v-model="status"
                :label="t('IT.Machine.Status')"
                :items="[
                  { title: t('IT.Machine.Status.Active'), value: 'Active' },
                  { title: t('IT.Machine.Status.Inactive'), value: 'Inactive' },
                  { title: t('IT.Machine.Status.Maintenance'), value: 'Maintenance' },
                ]"
                required
              />
            </VCol>

            <VCol cols="12">
              <VTextarea
                v-model="description"
                :label="t('IT.Machine.Description')"
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
  </div>
</template>
