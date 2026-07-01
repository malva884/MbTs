<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { useI18n } from 'vue-i18n'
import { VForm } from 'vuetify/components/VForm'
import { onMounted, computed, ref } from 'vue'

definePage({
  meta: {
    action: 'list',
    subject: 'Services',
  },
})

const { t } = useI18n()
const loading = ref(true)
const refForm = ref<VForm>()
const isSnackbarScrollReverseVisible = ref(false)
const message = ref('')
const color = ref('')
const editDialog = ref(false)
const isLoading = ref(false)
const isFormValid = ref(false)

const activeTab = ref('types')

const iconOptions = [
  { title: 'Cloud', value: 'tabler-cloud' },
  { title: 'Email', value: 'tabler-mail' },
  { title: 'VPN', value: 'tabler-shield-lock' },
  { title: 'Server', value: 'tabler-server' },
  { title: 'Database', value: 'tabler-database' },
  { title: 'Rete', value: 'tabler-network' },
  { title: 'SAP', value: 'tabler-building-factory' },
  { title: 'Microsoft', value: 'tabler-window' },
  { title: 'Google', value: 'tabler-brand-google' },
  { title: 'Slack', value: 'tabler-brand-slack' },
  { title: 'Teams', value: 'tabler-video' },
  { title: 'Github', value: 'tabler-brand-github' },
  { title: 'Desktop', value: 'tabler-device-desktop' },
  { title: 'Mobile', value: 'tabler-device-mobile' },
  { title: 'Stampante', value: 'tabler-printer' },
  { title: 'Wifi', value: 'tabler-wifi' },
  { title: 'Telefono', value: 'tabler-phone' },
  { title: 'Chat', value: 'tabler-message' },
  { title: 'Calendario', value: 'tabler-calendar' },
  { title: 'File', value: 'tabler-file' },
  { title: 'Cartella', value: 'tabler-folder' },
  { title: 'Sicurezza', value: 'tabler-lock' },
  { title: 'Chiave', value: 'tabler-key' },
  { title: 'Utente', value: 'tabler-user' },
  { title: 'Gruppo', value: 'tabler-users-group' },
  { title: 'Globe', value: 'tabler-world' },
  { title: 'API', value: 'tabler-api' },
  { title: 'Code', value: 'tabler-code' },
  { title: 'Terminal', value: 'tabler-terminal-2' },
  { title: 'Backup', value: 'tabler-database-export' },
]

// Service Types
const serviceTypes = ref<any[]>([])
const editedType = ref<any>({
  id: '',
  name: '',
  description: '',
  icon: 'tabler-cloud',
  disabled: false,
})

// Services
const services = ref<any[]>([])
const editedService = ref<any>({
  id: '',
  service_type_id: '',
  name: '',
  description: '',
  provider: '',
  server_url: '',
  domain: '',
  disabled: false,
})

const fetchServiceTypes = async () => {
  loading.value = true
  try {
    const { data } = await useApi<any>(createUrl('/hr/services/types'))
    serviceTypes.value = data.value || []
  }
  catch (e) {
    console.error('Error fetching service types:', e)
    serviceTypes.value = []
  }
  finally {
    loading.value = false
  }
}

const fetchServices = async () => {
  loading.value = true
  try {
    const { data } = await useApi<any>(createUrl('/hr/services'))
    services.value = data.value || []
  }
  catch (e) {
    console.error('Error fetching services:', e)
    services.value = []
  }
  finally {
    loading.value = false
  }
}

// Service Types CRUD
const newType = () => {
  editedType.value = {
    id: '',
    name: '',
    description: '',
    icon: 'tabler-cloud',
    disabled: false,
  }
  editDialog.value = true
}

const editType = (item: any) => {
  editedType.value = { ...item }
  editDialog.value = true
}

const saveType = async () => {
  if (!editedType.value.name) return

  isLoading.value = true
  try {
    const path = editedType.value.id
      ? `/hr/services/types/${editedType.value.id}`
      : '/hr/services/types'

    const returnData = await $api(path, {
      method: 'POST',
      body: editedType.value,
    })

    message.value = 'Servizio salvato con successo'
    color.value = 'success'
    isSnackbarScrollReverseVisible.value = true
    editDialog.value = false
    await fetchServiceTypes()
  }
  catch (e) {
    message.value = 'Errore durante il salvataggio'
    color.value = 'error'
    isSnackbarScrollReverseVisible.value = true
  }
  finally {
    isLoading.value = false
  }
}

const deleteType = async (item: any) => {
  if (!confirm('Sei sicuro di voler eliminare questo tipo di servizio?'))
    return

  try {
    await $api(`/hr/services/types/${item.id}`, { method: 'DELETE' })
    message.value = 'Tipo di servizio eliminato'
    color.value = 'success'
    isSnackbarScrollReverseVisible.value = true
    await fetchServiceTypes()
  }
  catch (e) {
    message.value = 'Errore durante eliminazione'
    color.value = 'error'
    isSnackbarScrollReverseVisible.value = true
  }
}

// Services CRUD
const newService = () => {
  editedService.value = {
    id: '',
    service_type_id: '',
    name: '',
    description: '',
    provider: '',
    server_url: '',
    domain: '',
    disabled: false,
  }
  editDialog.value = true
}

const editService = (item: any) => {
  editedService.value = { ...item }
  editDialog.value = true
}

const saveService = async () => {
  if (!editedService.value.name || !editedService.value.service_type_id) return

  isLoading.value = true
  try {
    const path = editedService.value.id
      ? `/hr/services/${editedService.value.id}`
      : '/hr/services'

    const returnData = await $api(path, {
      method: 'POST',
      body: editedService.value,
    })

    message.value = 'Servizio salvato con successo'
    color.value = 'success'
    isSnackbarScrollReverseVisible.value = true
    editDialog.value = false
    await fetchServices()
  }
  catch (e) {
    message.value = 'Errore durante il salvataggio'
    color.value = 'error'
    isSnackbarScrollReverseVisible.value = true
  }
  finally {
    isLoading.value = false
  }
}

const deleteService = async (item: any) => {
  if (!confirm('Sei sicuro di voler eliminare questo servizio?'))
    return

  try {
    await $api(`/hr/services/${item.id}`, { method: 'DELETE' })
    message.value = 'Servizio eliminato'
    color.value = 'success'
    isSnackbarScrollReverseVisible.value = true
    await fetchServices()
  }
  catch (e) {
    message.value = 'Errore durante eliminazione'
    color.value = 'error'
    isSnackbarScrollReverseVisible.value = true
  }
}

const save = () => {
  if (activeTab.value === 'types')
    saveType()
  else
    saveService()
}

const close = () => {
  isLoading.value = false
  editDialog.value = false
  refForm.value?.reset()
}

const getTypeName = (typeId: string) => {
  const type = serviceTypes.value.find((t: any) => t.id === typeId)
  return type ? type.name : 'N/A'
}

const typeHeaders = computed(() => [
  { title: 'Nome', key: 'name' },
  { title: 'Descrizione', key: 'description' },
  { title: 'Icona', key: 'icon' },
  { title: 'Disattivo', key: 'disabled' },
  { title: 'ACTIONS', key: 'actions', sortable: false },
])

const serviceHeaders = computed(() => [
  { title: 'Nome', key: 'name' },
  { title: 'Tipo', key: 'service_type_id' },
  { title: 'Provider', key: 'provider' },
  { title: 'Dominio', key: 'domain' },
  { title: 'Disattivo', key: 'disabled' },
  { title: 'ACTIONS', key: 'actions', sortable: false },
])

onMounted(() => {
  fetchServiceTypes()
  fetchServices()
})
</script>

<template>
  <div class="workspace-container w-100 d-flex flex-column pa-4 gap-3">
    <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
      <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
        <div class="d-flex align-center gap-2">
          <VAvatar color="primary" variant="tonal" size="38">
            <VIcon icon="tabler-cloud" size="20" />
          </VAvatar>
          <div>
            <div class="text-h6 font-weight-medium">Gestione Servizi IT</div>
            <div class="text-caption text-medium-emphasis">Gestisci tipi di servizio e servizi assegnabili ai dipendenti</div>
          </div>
        </div>
        <div class="d-flex align-center gap-2">
          <VBtn
            prepend-icon="tabler-plus"
            color="primary"
            variant="flat"
            density="comfortable"
            class="px-3"
            @click="activeTab === 'types' ? newType() : newService()"
          >
            {{ activeTab === 'types' ? 'Nuovo Tipo' : 'Nuovo Servizio' }}
          </VBtn>
        </div>
      </VCardText>
      <VDivider />

      <VTabs v-model="activeTab">
        <VTab value="types">
          <VIcon start icon="tabler-category" />
          Tipi Servizio
        </VTab>
        <VTab value="services">
          <VIcon start icon="tabler-cloud" />
          Servizi
        </VTab>
      </VTabs>
      <VDivider />

      <VWindow v-model="activeTab">
        <!-- Service Types -->
        <VWindowItem value="types">
          <VDataTableServer
            :headers="typeHeaders"
            :items="serviceTypes"
            :items-length="serviceTypes.length"
            :loading="loading"
          >
            <template #item.icon="{ item }">
              <VIcon :icon="item.icon || 'tabler-cloud'" size="20" />
            </template>

            <template #item.disabled="{ item }">
              <VIcon v-if="item.disabled" color="error" icon="tabler-x" />
              <VIcon v-else color="success" icon="tabler-check" />
            </template>

            <template #item.actions="{ item }">
              <div class="d-flex gap-1">
                <IconBtn color="warning" @click="editType(item)">
                  <VIcon icon="tabler-edit" />
                </IconBtn>
                <IconBtn color="error" @click="deleteType(item)">
                  <VIcon icon="tabler-trash" />
                </IconBtn>
              </div>
            </template>
          </VDataTableServer>
        </VWindowItem>

        <!-- Services -->
        <VWindowItem value="services">
          <VDataTableServer
            :headers="serviceHeaders"
            :items="services"
            :items-length="services.length"
            :loading="loading"
          >
            <template #item.service_type_id="{ item }">
              {{ getTypeName(item.service_type_id) }}
            </template>

            <template #item.disabled="{ item }">
              <VIcon v-if="item.disabled" color="error" icon="tabler-x" />
              <VIcon v-else color="success" icon="tabler-check" />
            </template>

            <template #item.actions="{ item }">
              <div class="d-flex gap-1">
                <IconBtn color="warning" @click="editService(item)">
                  <VIcon icon="tabler-edit" />
                </IconBtn>
                <IconBtn color="error" @click="deleteService(item)">
                  <VIcon icon="tabler-trash" />
                </IconBtn>
              </div>
            </template>
          </VDataTableServer>
        </VWindowItem>
      </VWindow>
    </VCard>

    <VSnackbar
      v-model="isSnackbarScrollReverseVisible"
      transition="scroll-y-reverse-transition"
      location="top central"
      :color="color"
    >
      {{ message }}
    </VSnackbar>
  </div>

  <!-- Edit Dialog -->
  <VDialog
    v-model="editDialog"
    max-width="600px"
  >
    <AppCardActions
      v-model:loading="isLoading"
      :title="activeTab === 'types'
        ? (editedType.id ? 'Modifica Tipo Servizio' : 'Nuovo Tipo Servizio')
        : (editedService.id ? 'Modifica Servizio' : 'Nuovo Servizio')"
      no-actions
    >
      <VCard>
        <VCardText>
          <VContainer>
            <VForm
              ref="refForm"
              v-model="isFormValid"
            >
              <!-- Type Form -->
              <VRow v-if="activeTab === 'types'">
                <VCol cols="12">
                  <AppTextField
                    v-model="editedType.name"
                    :rules="[requiredValidator]"
                    label="Nome"
                    placeholder="es. Email, VPN, SAP"
                  />
                </VCol>
                <VCol cols="12">
                  <AppTextField
                    v-model="editedType.description"
                    label="Descrizione"
                    placeholder="Descrizione del tipo di servizio"
                  />
                </VCol>
                <VCol cols="12">
                  <AppSelect
                    v-model="editedType.icon"
                    label="Icona"
                    placeholder="Seleziona un'icona"
                    :items="iconOptions"
                  >
                    <template #selection="{ item }">
                      <div class="d-flex align-center gap-2">
                        <VIcon :icon="item.value" size="20" />
                        <span>{{ item.title }}</span>
                      </div>
                    </template>
                    <template #item="{ item, props }">
                      <VListItem v-bind="props">
                        <template #prepend>
                          <VIcon :icon="item.value" size="20" />
                        </template>
                      </VListItem>
                    </template>
                  </AppSelect>
                </VCol>
                <VCol cols="12">
                  <VSwitch
                    v-model="editedType.disabled"
                    label="Disattivo"
                  />
                </VCol>
              </VRow>

              <!-- Service Form -->
              <VRow v-else>
                <VCol cols="12">
                  <AppSelect
                    v-model="editedService.service_type_id"
                    :rules="[requiredValidator]"
                    label="Tipo Servizio"
                    placeholder="Seleziona un tipo"
                    :items="serviceTypes"
                    item-title="name"
                    item-value="id"
                  />
                </VCol>
                <VCol cols="12">
                  <AppTextField
                    v-model="editedService.name"
                    :rules="[requiredValidator]"
                    label="Nome Servizio"
                    placeholder="es. Gmail Aziendale, VPN Cisco"
                  />
                </VCol>
                <VCol cols="12">
                  <AppTextField
                    v-model="editedService.description"
                    label="Descrizione"
                    placeholder="Descrizione del servizio"
                  />
                </VCol>
                <VCol cols="12" sm="6">
                  <AppTextField
                    v-model="editedService.provider"
                    label="Provider"
                    placeholder="es. Google, Cisco, SAP"
                  />
                </VCol>
                <VCol cols="12" sm="6">
                  <AppTextField
                    v-model="editedService.domain"
                    label="Dominio"
                    placeholder="es. azienda.com"
                  />
                </VCol>
                <VCol cols="12">
                  <AppTextField
                    v-model="editedService.server_url"
                    label="Server URL"
                    placeholder="https://server.example.com"
                  />
                </VCol>
                <VCol cols="12">
                  <VSwitch
                    v-model="editedService.disabled"
                    label="Disattivo"
                  />
                </VCol>
              </VRow>
            </VForm>
          </VContainer>
        </VCardText>

        <VCardActions>
          <VSpacer />
          <VBtn
            color="error"
            variant="outlined"
            @click="close"
          >
            Annulla
          </VBtn>
          <VBtn
            color="success"
            variant="elevated"
            @click="save"
          >
            Salva
          </VBtn>
        </VCardActions>
      </VCard>
    </AppCardActions>
  </VDialog>
</template>
