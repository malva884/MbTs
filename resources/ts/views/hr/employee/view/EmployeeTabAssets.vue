<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps<{
  id: string
}>()

const { t } = useI18n()

const employeeAssets = ref<any[]>([])
const loading = ref(false)

const fetchEmployeeAssets = async () => {
  loading.value = true
  try {
    const data = await $api(`/it/assignments/employee/${props.id}`)
    employeeAssets.value = data || []
  }
  catch (e) {
    console.error('Error fetching employee assets:', e)
    employeeAssets.value = []
  }
  finally {
    loading.value = false
  }
}

const getStatusColor = (status: string) => {
  const colors: Record<string, string> = {
    Active: 'success',
    Returned: 'error',
  }
  return colors[status] || 'secondary'
}

const getStatusLabel = (status: string) => {
  const labels: Record<string, string> = {
    Active: 'Attivo',
    Returned: 'Restituito',
  }
  return labels[status] || status
}

onMounted(() => {
  fetchEmployeeAssets()
})
</script>

<template>
  <div class="employee-assets">
    <div class="d-flex justify-space-between align-center mb-4">
      <h3 class="text-h6 font-weight-semibold">Asset IT Assegnati</h3>
    </div>

    <VProgressCircular
      v-if="loading"
      indeterminate
      color="primary"
      class="ma-4"
    />

    <div v-else-if="employeeAssets.length === 0" class="text-center pa-8 text-medium-emphasis">
      <VIcon icon="tabler-device-laptop" size="48" class="mb-2" />
      <p>Nessun asset IT assegnato a questo dipendente</p>
    </div>

    <div v-else class="d-flex flex-column gap-3">
      <VCard
        v-for="assignment in employeeAssets"
        :key="assignment.id"
        variant="outlined"
        class="rounded-lg"
      >
        <VCardText class="pa-4">
          <div class="d-flex justify-space-between align-start">
            <div class="d-flex flex-column gap-1">
              <div class="d-flex align-center gap-2">
                <VIcon icon="tabler-device-laptop" size="20" color="primary" />
                <span class="font-weight-medium text-h6">{{ assignment.asset?.brand }} {{ assignment.asset?.model }}</span>
              </div>
              
              <div class="d-flex flex-column gap-1 mt-2">
                <div class="d-flex align-center gap-2">
                  <VIcon icon="tabler-barcode" size="16" class="text-medium-emphasis" />
                  <span class="text-caption">Serial: {{ assignment.asset?.serial_number }}</span>
                </div>
                
                <div v-if="assignment.asset?.asset_tag" class="d-flex align-center gap-2">
                  <VIcon icon="tabler-tag" size="16" class="text-medium-emphasis" />
                  <span class="text-caption">Tag: {{ assignment.asset.asset_tag }}</span>
                </div>
                
                <div v-if="assignment.asset?.category" class="d-flex align-center gap-2">
                  <VIcon icon="tabler-category" size="16" class="text-medium-emphasis" />
                  <span class="text-caption">{{ assignment.asset.category.name }}</span>
                </div>
                
                <div v-if="assignment.asset?.location" class="d-flex align-center gap-2">
                  <VIcon icon="tabler-map-pin" size="16" class="text-medium-emphasis" />
                  <span class="text-caption">{{ assignment.asset.location.name }}</span>
                </div>
                
                <div class="d-flex align-center gap-2">
                  <VIcon icon="tabler-calendar" size="16" class="text-medium-emphasis" />
                  <span class="text-caption">Assegnato: {{ new Date(assignment.assigned_at).toLocaleDateString('it-IT') }}</span>
                </div>
                
                <div class="d-flex align-center gap-2">
                  <VIcon icon="tabler-users" size="16" class="text-medium-emphasis" />
                  <span class="text-caption">Quantità: {{ assignment.assigned_quantity }}</span>
                </div>
              </div>
            </div>

            <div class="d-flex flex-column align-end gap-2">
              <VChip
                :color="getStatusColor(assignment.status)"
                size="small"
                label
              >
                {{ getStatusLabel(assignment.status) }}
              </VChip>
              
              <div v-if="assignment.assigned_by_user" class="text-caption text-medium-emphasis">
                Assegnato da: {{ assignment.assigned_by_user.name }}
              </div>
            </div>
          </div>

          <VDivider v-if="assignment.notes" class="my-3" />
          
          <div v-if="assignment.notes" class="text-caption text-medium-emphasis">
            <VIcon icon="tabler-note" size="14" class="me-1" />
            {{ assignment.notes }}
          </div>
        </VCardText>
      </VCard>
    </div>
  </div>
</template>
