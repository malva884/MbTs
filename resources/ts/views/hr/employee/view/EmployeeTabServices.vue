<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps<{
  id: string
}>()

const { t } = useI18n()

const employeeServices = ref<any[]>([])
const loading = ref(false)
const serviceDialog = ref(false)
const editServiceId = ref<string | null>(null)
const selectedService = ref<string | null>(null)
const serviceForm = ref({
  username: '',
  email: '',
  phone: '',
  status: 'Active',
  activated_at: '',
  expires_at: '',
  notes: '',
})

const services = ref<any[]>([])

const fetchEmployeeServices = async () => {
  loading.value = true
  try {
    const { data: data } = await useApi<any>(createUrl(`/hr/services/employee/${props.id}`))

    //const data = await $api(`/hr/services/employee/${props.id}`)
    employeeServices.value = data.value || []
  }
  catch (e) {
    console.error('Error fetching employee services:', e)
    employeeServices.value = []
  }
  finally {
    loading.value = false
  }
}

const fetchServices = async () => {
  try {
    const { data } = await useApi<any>(createUrl('/hr/services'))
    services.value = data.value || []
  }
  catch (e) {
    console.error('Error fetching services:', e)
    services.value = []
  }
}

const openAddServiceDialog = async () => {
  editServiceId.value = null
  selectedService.value = null
  serviceForm.value = {
    username: '',
    email: '',
    phone: '',
    status: 'Active',
    activated_at: '',
    expires_at: '',
    notes: '',
  }
  await fetchServices()
  serviceDialog.value = true
}

const openEditServiceDialog = (service: any) => {
  editServiceId.value = service.id
  selectedService.value = service.service_id
  serviceForm.value = {
    username: service.username || '',
    email: service.email || '',
    phone: service.phone || '',
    status: service.status || 'Active',
    activated_at: service.activated_at || '',
    expires_at: service.expires_at || '',
    notes: service.notes || '',
  }
  serviceDialog.value = true
}

const saveEmployeeService = async () => {
  if (!selectedService.value) return

  try {
    if (editServiceId.value) {
      await $api(`/hr/services/employee/${editServiceId.value}`, {
        method: 'POST',
        body: {
          ...serviceForm.value,
        },
      })
    }
    else {
      await $api('/hr/services/employee', {
        method: 'POST',
        body: {
          employee_id: props.id,
          service_id: selectedService.value,
          ...serviceForm.value,
        },
      })
    }
    serviceDialog.value = false
    await fetchEmployeeServices()
  }
  catch (e) {
    console.error('Error saving employee service:', e)
    alert('Error: ' + JSON.stringify(e))
  }
}

const deleteEmployeeService = async (serviceId: string) => {
  if (!confirm('Are you sure you want to remove this service?')) return

  try {
    await $api(`/hr/services/employee/${serviceId}`, {
      method: 'DELETE',
    })
    await fetchEmployeeServices()
  }
  catch (e) {
    console.error('Error deleting employee service:', e)
    alert('Error: ' + JSON.stringify(e))
  }
}

const getStatusColor = (status: string) => {
  const colors: Record<string, string> = {
    Active: 'success',
    Suspended: 'warning',
    Revoked: 'error',
  }
  return colors[status] || 'secondary'
}

const getStatusLabel = (status: string) => {
  const labels: Record<string, string> = {
    Active: 'Attivo',
    Suspended: 'Sospeso',
    Revoked: 'Revocato',
  }
  return labels[status] || status
}

const getServicesByType = (typeName: string) => {
  return employeeServices.value.filter(s => s.service?.service_type?.name === typeName)
}

onMounted(() => {
  fetchEmployeeServices()
})
</script>

<template>
  <div class="employee-services">
    <div class="d-flex justify-space-between align-center mb-4">
      <h3 class="text-h6 font-weight-semibold">Servizi IT</h3>
      <VBtn
        color="primary"
        variant="tonal"
        @click="openAddServiceDialog"
      >
        <VIcon start icon="tabler-plus" />
        Aggiungi Servizio
      </VBtn>
    </div>

    <VProgressCircular
      v-if="loading"
      indeterminate
      color="primary"
      class="ma-4"
    />

    <div v-else-if="employeeServices.length === 0" class="text-center pa-8 text-medium-emphasis">
      <VIcon icon="tabler-cloud" size="48" class="mb-2" />
      <p>Nessun servizio IT assegnato a questo dipendente</p>
    </div>

    <div v-else class="d-flex flex-column gap-3">
      <VCard
        v-for="service in employeeServices"
        :key="service.id"
        variant="outlined"
        class="rounded-lg"
      >
        <VCardText class="pa-4">
          <div class="d-flex justify-space-between align-start">
            <div class="d-flex flex-column gap-1">
              <div class="d-flex align-center gap-2">
                <VIcon :icon="service.service?.service_type?.icon || 'tabler-cloud'" size="20" color="primary" />
                <span class="font-weight-medium text-h6">{{ service.service?.name }}</span>
              </div>
              
              <div class="d-flex flex-column gap-1 mt-2">
                <div v-if="service.username" class="d-flex align-center gap-2">
                  <VIcon icon="tabler-user" size="16" class="text-medium-emphasis" />
                  <span class="text-caption">Username: {{ service.username }}</span>
                </div>
                
                <div v-if="service.email" class="d-flex align-center gap-2">
                  <VIcon icon="tabler-mail" size="16" class="text-medium-emphasis" />
                  <span class="text-caption">{{ service.email }}</span>
                </div>
                
                <div v-if="service.phone" class="d-flex align-center gap-2">
                  <VIcon icon="tabler-phone" size="16" class="text-medium-emphasis" />
                  <span class="text-caption">{{ service.phone }}</span>
                </div>
                
                <div v-if="service.activated_at" class="d-flex align-center gap-2">
                  <VIcon icon="tabler-calendar" size="16" class="text-medium-emphasis" />
                  <span class="text-caption">Attivato: {{ new Date(service.activated_at).toLocaleDateString('it-IT') }}</span>
                </div>
                
                <div v-if="service.expires_at" class="d-flex align-center gap-2">
                  <VIcon icon="tabler-calendar-off" size="16" class="text-medium-emphasis" />
                  <span class="text-caption">Scadenza: {{ new Date(service.expires_at).toLocaleDateString('it-IT') }}</span>
                </div>
              </div>
            </div>

            <div class="d-flex flex-column align-end gap-2">
              <VChip
                :color="getStatusColor(service.status)"
                size="small"
                label
              >
                {{ getStatusLabel(service.status) }}
              </VChip>
              
              <div class="d-flex gap-1">
                <VBtn
                  icon="tabler-edit"
                  size="small"
                  variant="text"
                  color="primary"
                  @click="openEditServiceDialog(service)"
                />
                <VBtn
                  icon="tabler-trash"
                  size="small"
                  variant="text"
                  color="error"
                  @click="deleteEmployeeService(service.id)"
                />
              </div>
            </div>
          </div>

          <VDivider v-if="service.notes" class="my-3" />
          
          <div v-if="service.notes" class="text-caption text-medium-emphasis">
            <VIcon icon="tabler-note" size="14" class="me-1" />
            {{ service.notes }}
          </div>
        </VCardText>
      </VCard>
    </div>

    <!-- Add/Edit Service Dialog -->
    <VDialog v-model="serviceDialog" max-width="500px">
      <VCard>
        <VCardTitle class="d-flex align-center justify-space-between pa-4">
          <span>{{ editServiceId ? 'Modifica Servizio' : 'Aggiungi Servizio' }}</span>
          <VBtn icon="tabler-x" variant="text" @click="serviceDialog = false" />
        </VCardTitle>

        <VDivider />

        <VCardText class="pa-4">
          <VSelect
            v-model="selectedService"
            :items="services"
            item-title="name"
            item-value="id"
            label="Servizio"
            placeholder="Seleziona un servizio"
            :disabled="!!editServiceId"
            :readonly="!!editServiceId"
            required
          />

          <VTextField
            v-model="serviceForm.username"
            label="Username"
            placeholder="Username del servizio"
            class="mt-4"
          />

          <VTextField
            v-model="serviceForm.email"
            label="Email"
            placeholder="Email del servizio"
            class="mt-4"
          />

          <VTextField
            v-model="serviceForm.phone"
            label="Telefono"
            placeholder="Telefono del servizio"
            class="mt-4"
          />

          <VSelect
            v-model="serviceForm.status"
            :items="[
              { title: 'Attivo', value: 'Active' },
              { title: 'Sospeso', value: 'Suspended' },
              { title: 'Revocato', value: 'Revoked' },
            ]"
            label="Stato"
            placeholder="Seleziona lo stato"
            required
            class="mt-4"
          />

          <VTextField
            v-model="serviceForm.activated_at"
            type="date"
            label="Data Attivazione"
            class="mt-4"
          />

          <VTextField
            v-model="serviceForm.expires_at"
            type="date"
            label="Data Scadenza"
            class="mt-4"
          />

          <VTextarea
            v-model="serviceForm.notes"
            label="Note"
            placeholder="Note aggiuntive"
            rows="3"
            class="mt-4"
          />
        </VCardText>

        <VCardActions class="pa-4">
          <VSpacer />
          <VBtn variant="text" @click="serviceDialog = false">
            Annulla
          </VBtn>
          <VBtn color="primary" @click="saveEmployeeService">
            Salva
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>
