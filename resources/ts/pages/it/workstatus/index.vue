<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { can } from '@layouts/plugins/casl'
import DefineAbilities from '@/plugins/casl/DefineAbilities'
import moment from 'moment/moment'

definePage({
  meta: {
    action: 'list',
    subject: 'It-Work-Progress',
  },
})

const itemsPerPage = ref(15)
const page = ref(1)
const sortBy = ref('created_at')
const orderBy = ref('desc')
const totalItems = ref(0)
const serverItems = ref<any[]>([])
const loading = ref(true)
const deleteDialog = ref(false)
const deletedItem = ref<any>({})
const isDialogLoading = ref(false)
const isSnackbarVisible = ref(false)
const message = ref('')
const color = ref('')

const headers = computed(() => [
  { title: 'Data', key: 'created_at' },
  { title: 'Stato', key: 'status', sortable: false },
  { title: 'Corr. costo M.P.', key: 'total_final_mp', sortable: false },
  { title: 'Costo M.P.', key: 'total_raw_material', sortable: false },
  { title: 'Corr. costo tot.', key: 'total_final', sortable: false },
  { title: 'Costo tot.', key: 'raw_material_machine_manpower_cost', sortable: false },
  { title: 'ACTIONS', key: 'actions', sortable: false },
])

const euro = new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' })

const resolveStatus = (status: number) => {
  if (status === 2)
    return { color: 'success', text: 'Completato' }
  else if (status === 1)
    return { color: 'primary', text: 'In Lavorazione' }
  else if (status === 3)
    return { color: 'error', text: 'Errore' }
  else
    return { color: 'secondary', text: 'Import' }
}

const formatDate = (date: string) => moment(String(date)).format('DD/MM/YYYY HH:mm')

const loadItems = async () => {
  loading.value = true

  const { data: resultData } = await useApi<any>(createUrl('/ws/head/list', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
    },
  }))

  if (resultData.value) {
    serverItems.value = resultData.value.data
    totalItems.value = resultData.value.total
  }
  else {
    serverItems.value = []
    totalItems.value = 0
  }
  loading.value = false
}

const updateOptions = (options: any) => {
  sortBy.value = options.sortBy[0]?.key || 'created_at'
  orderBy.value = options.sortBy[0]?.order || 'desc'
  page.value = options.page
  itemsPerPage.value = options.itemsPerPage
  loadItems()
}

const deleteItem = (item: any) => {
  deletedItem.value = { ...item }
  deleteDialog.value = true
}

const deleteItemConfirm = async () => {
  isDialogLoading.value = true

  const retuenData = await $api(`/ws/head/delete/${deletedItem.value.id}`, {
    method: 'delete',
  })

  message.value = retuenData.message
  color.value = retuenData.color
  isSnackbarVisible.value = true

  await loadItems()
  deleteDialog.value = false
  isDialogLoading.value = false
}
</script>

<template>
  <div class="workspace-container w-100 d-flex flex-column pa-4 gap-3">
    <VSnackbar v-model="isSnackbarVisible" transition="scroll-y-reverse-transition" location="top center" :timeout="3000">
      {{ $t(message) }}
    </VSnackbar>

    <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
      <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
        <div class="d-flex align-center gap-2">
          <VIcon icon="tabler-progress" size="24" color="primary" />
          <div>
            <div class="text-h6 font-weight-medium">Work Progress</div>
            <div class="text-caption text-medium-emphasis">{{ totalItems }} stati lavori importati</div>
          </div>
        </div>
        <div class="d-flex align-center gap-2">
          <VBtn
            v-if="can(DefineAbilities.it_workprogress_import.action, DefineAbilities.it_workprogress_import.subject)"
            prepend-icon="tabler-table-import"
            variant="outlined"
            color="secondary"
            density="comfortable"
            :to="{ name: 'it-workstatus-costs' }"
          >
            {{ $t('Costi') }}
          </VBtn>
          <VBtn
            v-if="can(DefineAbilities.it_workprogress_create.action, DefineAbilities.it_workprogress_create.subject)"
            prepend-icon="tabler-plus"
            color="primary"
            variant="flat"
            density="comfortable"
            class="px-3"
            :to="{ name: 'it-workstatus-import' }"
          >
            New Work Progress
          </VBtn>
        </div>
      </VCardText>
      <VDivider />

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
            <VIcon icon="tabler-progress" size="40" class="text-disabled mb-2" />
            <p class="text-body-1 text-disabled mb-0">Nessuno stato lavori trovato</p>
          </div>
        </template>

        <template #item.created_at="{ item }">
          <RouterLink
            :to="{ name: 'it-workstatus-detail-id', params: { id: item.id } }"
            class="font-weight-medium text-link"
          >
            {{ formatDate(item.created_at) }}
          </RouterLink>
        </template>

        <template #item.status="{ item }">
          <VChip :color="resolveStatus(item.status).color" size="small">
            {{ resolveStatus(item.status).text }}
          </VChip>
        </template>

        <template #item.total_final_mp="{ item }">
          {{ euro.format(item.total_final_mp) }}
        </template>

        <template #item.total_raw_material="{ item }">
          {{ euro.format(item.total_raw_material) }}
        </template>

        <template #item.total_final="{ item }">
          {{ euro.format(item.total_final) }}
        </template>

        <template #item.raw_material_machine_manpower_cost="{ item }">
          {{ euro.format(item.raw_material_machine_manpower_cost) }}
        </template>

        <template #item.actions="{ item }">
          <div class="d-flex gap-1">
            <IconBtn
              color="primary"
              :to="{ name: 'it-workstatus-detail-id', params: { id: item.id } }"
            >
              <VIcon icon="tabler-info-circle" />
              <VTooltip activator="parent" location="top">Dettaglio</VTooltip>
            </IconBtn>

            <IconBtn
              v-if="can(DefineAbilities.it_workprogress_edit.action, DefineAbilities.it_workprogress_edit.subject)"
              color="warning"
              :to="{ name: 'it-workstatus-check-id', params: { id: item.id } }"
            >
              <VIcon icon="tabler-package" />
              <VTooltip activator="parent" location="top">Prelievi Mancanti</VTooltip>
            </IconBtn>

            <IconBtn
              color="info"
              :to="{ name: 'it-workstatus-summary-id', params: { id: item.id } }"
            >
              <VIcon icon="tabler-list-details" />
              <VTooltip activator="parent" location="top">Riepilogo</VTooltip>
            </IconBtn>

            <IconBtn
              v-if="can(DefineAbilities.it_workprogress_deleted.action, DefineAbilities.it_workprogress_deleted.subject)"
              color="error"
              @click="deleteItem(item)"
            >
              <VIcon icon="tabler-trash" />
              <VTooltip activator="parent" location="top">Elimina</VTooltip>
            </IconBtn>
          </div>
        </template>
      </VDataTableServer>
    </VCard>
  </div>

  <!-- Dialog Loading -->
  <VDialog v-model="isDialogLoading" width="300">
    <VCard color="primary" width="300">
      <VCardText class="pt-3">
        <span class="ml-4 mb-3">Please stand by</span>
        <VProgressLinear :size="40" color="warning" class="mt-3" indeterminate />
      </VCardText>
    </VCard>
  </VDialog>

  <!-- Delete Dialog -->
  <VDialog v-model="deleteDialog" max-width="500px">
    <VCard>
      <VCardTitle>Sei sicuro di voler eliminare?</VCardTitle>
      <VCardActions>
        <VSpacer />
        <VBtn color="error" variant="outlined" @click="deleteDialog = false">Cancel</VBtn>
        <VBtn color="success" variant="elevated" @click="deleteItemConfirm">OK</VBtn>
        <VSpacer />
      </VCardActions>
    </VCard>
  </VDialog>
</template>
