<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { useI18n } from 'vue-i18n'
import { can } from '@layouts/plugins/casl'
import DefineAbilities from '@/plugins/casl/DefineAbilities'

definePage({
  meta: {
    action: 'list',
    subject: 'Ehs-Eventi',
  },
})

const { t } = useI18n()
const itemsPerPage = ref(10)
const loading = ref(true)
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const page = ref(1)
const serverItems = ref<any>([])
const isSnackbarVisible = ref(false)
const message = ref('')
const color = ref('')
const isImporting = ref(false)

// Filtri
const tipoSchedaFilter = ref()
const tipoRilevazioneFilter = ref()
const dataDaFilter = ref('')
const dataAFilter = ref('')
const matricolaFilter = ref('')
const dipendenteFilter = ref('')

const tipoSchedaItems = [
  { title: t('Ehs.Infortunio'), value: 1 },
  { title: t('Ehs.Evento-Ambientale'), value: 2 },
]

const tipoRilevazioneItems = [
  { title: t('Ehs.Infortunio'), value: 1 },
  { title: t('Ehs.Near-Miss-Inf'), value: 2 },
  { title: t('Ehs.Danno-Ambientale'), value: 3 },
  { title: t('Ehs.Near-Miss-Amb'), value: 4 },
  { title: t('Ehs.First-Aid'), value: 5 },
]

const importOldPortal = async () => {
  isImporting.value = true
  try {
    const response = await $api('/ehs/events/import', { method: 'POST' })

    message.value = response.message
    color.value = response.color || 'success'
    isSnackbarVisible.value = true

    // eslint-disable-next-line @typescript-eslint/no-use-before-define
    loadItems()
  }
  catch (error: any) {
    message.value = error.message || 'Errore durante l\'importazione'
    color.value = 'error'
    isSnackbarVisible.value = true
  }
  finally {
    isImporting.value = false
  }
}

const updateOptions = (options: any) => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
  page.value = options.page
  itemsPerPage.value = options.itemsPerPage

  // eslint-disable-next-line @typescript-eslint/no-use-before-define
  loadItems()
}

const loadItems = async () => {
  loading.value = true

  const { data: resultData } = await useApi<any>(createUrl('/ehs/events/list', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      tipo_scheda: tipoSchedaFilter.value,
      tipo_rilevazione: tipoRilevazioneFilter.value,
      data_da: dataDaFilter.value,
      data_a: dataAFilter.value,
      matricola: matricolaFilter.value,
      dipendente: dipendenteFilter.value,
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
}

const headers = computed(() => [
  { title: t('Ehs.Data-Evento'), key: 'data_evento' },
  { title: t('Ehs.Tipo'), key: 'tipo_rilevazione' },
  { title: t('Label.Dipendente'), key: 'dipendente' },
  { title: t('Label.Matricola'), key: 'matricola' },
  { title: t('Label.Reparto'), key: 'reparto.reparto' },
  { title: t('Ehs.Impianto'), key: 'impianto.site' },
  { title: t('Table.Azioni'), key: 'actions', sortable: false },
])

const resolveTipoRilevazione = (tipo: number) => {
  switch (tipo) {
    case 1: return { color: 'error', text: t('Ehs.Infortunio') }
    case 2: return { color: 'warning', text: t('Ehs.Near-Miss-Inf') }
    case 3: return { color: 'error', text: t('Ehs.Danno-Ambientale') }
    case 4: return { color: 'warning', text: t('Ehs.Near-Miss-Amb') }
    case 5: return { color: 'info', text: t('Ehs.First-Aid') }
    default: return { color: 'secondary', text: '-' }
  }
}

const formatDate = (date: string) => {
  if (!date)
    return '-'

  return new Date(date).toLocaleDateString('it-IT')
}

const deleteItem = async (id: string) => {
  try {
    const response = await $api(`/ehs/events/destroy/${id}`, { method: 'DELETE' })

    message.value = response.message
    color.value = response.color || 'success'
    isSnackbarVisible.value = true
    loadItems()
  }
  catch (error: any) {
    message.value = error.message || 'Errore'
    color.value = 'error'
    isSnackbarVisible.value = true
  }
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
          <VIcon icon="tabler-shield-exclamation" size="24" color="primary" />
          <div>
            <div class="text-h6 font-weight-medium">{{ $t('Ehs.Infortuni-E-Ambiente') }}</div>
            <div class="text-caption text-medium-emphasis">{{ totalItems }} {{ $t('Ehs.Eventi-Registrati') }}</div>
          </div>
        </div>
        <div class="d-flex align-center gap-2">
          <VBtn
            v-if="can(DefineAbilities.ehs_event_admin.action, DefineAbilities.ehs_event_admin.subject)"
            prepend-icon="tabler-database-import"
            color="secondary"
            variant="outlined"
            density="comfortable"
            class="px-3 me-1"
            :loading="isImporting"
            @click="importOldPortal"
          >
            {{ $t('Label.Sincronizza-Vecchio-Gestionale') }}
          </VBtn>
          <VBtn
            v-if="can(DefineAbilities.ehs_event_create.action, DefineAbilities.ehs_event_create.subject)"
            prepend-icon="tabler-plus"
            color="primary"
            variant="flat"
            density="comfortable"
            class="px-3"
            :to="{ name: 'ehs-new' }"
          >
            {{ $t('Ehs.Nuovo-Evento') }}
          </VBtn>
        </div>
      </VCardText>
      <VDivider />
      <VCardText class="pa-3">
        <VRow class="mb-2">
          <!-- Tipo scheda -->
          <VCol cols="12" sm="2">
            <AppSelect
              v-model="tipoSchedaFilter"
              :label="$t('Ehs.Tipo-Scheda')"
              :placeholder="$t('Label.Tutti')"
              :items="tipoSchedaItems"
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-filter"
              @update:model-value="loadItems"
              @click:clear="loadItems"
            />
          </VCol>

          <!-- Tipo rilevazione -->
          <VCol cols="12" sm="2">
            <AppSelect
              v-model="tipoRilevazioneFilter"
              :label="$t('Ehs.Tipo-Rilevazione')"
              :placeholder="$t('Label.Tutti')"
              :items="tipoRilevazioneItems"
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-filter"
              @update:model-value="loadItems"
              @click:clear="loadItems"
            />
          </VCol>

          <!-- Data da -->
          <VCol cols="12" sm="2">
            <AppTextField
              v-model="dataDaFilter"
              :label="$t('Ehs.Data-Da')"
              type="date"
              clearable
              clear-icon="tabler-x"
              @update:model-value="loadItems"
              @click:clear="loadItems"
            />
          </VCol>

          <!-- Data a -->
          <VCol cols="12" sm="2">
            <AppTextField
              v-model="dataAFilter"
              :label="$t('Ehs.Data-A')"
              type="date"
              clearable
              clear-icon="tabler-x"
              @update:model-value="loadItems"
              @click:clear="loadItems"
            />
          </VCol>

          <!-- Matricola -->
          <VCol cols="12" sm="2">
            <AppTextField
              v-model="matricolaFilter"
              :label="$t('Label.Matricola')"
              :placeholder="$t('Label.Matricola')"
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-search"
              @keyup.enter="loadItems"
              @click:clear="loadItems"
            />
          </VCol>

          <!-- Dipendente -->
          <VCol cols="12" sm="2">
            <AppTextField
              v-model="dipendenteFilter"
              :label="$t('Label.Dipendente')"
              :placeholder="$t('Label.Cerca-Dipendente')"
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-search"
              @keyup.enter="loadItems"
              @click:clear="loadItems"
            />
          </VCol>
        </VRow>
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
            <VIcon icon="tabler-shield-off" size="40" class="text-disabled mb-2" />
            <p class="text-body-1 text-disabled mb-0">{{ $t('Ehs.Nessun-Evento-Trovato') }}</p>
          </div>
        </template>

        <template #item.data_evento="{ item }">
          {{ formatDate(item.data_evento) }}
        </template>

        <template #item.tipo_rilevazione="{ item }">
          <VChip
            :color="resolveTipoRilevazione(item.tipo_rilevazione).color"
            size="small"
            variant="tonal"
          >
            {{ resolveTipoRilevazione(item.tipo_rilevazione).text }}
          </VChip>
        </template>

        <template #item.dipendente="{ item }">
          <span v-if="item.employee">{{ item.employee.nome }} {{ item.employee.cognome }}</span>
          <span v-else>{{ item.nome }} {{ item.cognome }}</span>
        </template>

        <template #item.actions="{ item }">
          <div class="d-flex gap-1">
            <IconBtn
              color="primary"
              size="small"
              :to="{ name: 'ehs-view-id', params: { id: item.id } }"
            >
              <VIcon icon="tabler-eye" size="18" />
            </IconBtn>
            <IconBtn
              v-if="can(DefineAbilities.ehs_event_edit.action, DefineAbilities.ehs_event_edit.subject)"
              color="warning"
              size="small"
              :to="{ name: 'ehs-edit-id', params: { id: item.id } }"
            >
              <VIcon icon="tabler-edit" size="18" />
            </IconBtn>
            <IconBtn
              v-if="can(DefineAbilities.ehs_event_deleted.action, DefineAbilities.ehs_event_deleted.subject)"
              color="error"
              size="small"
              @click="deleteItem(item.id)"
            >
              <VIcon icon="tabler-trash" size="18" />
            </IconBtn>
          </div>
        </template>
      </VDataTableServer>
    </VCard>
  </div>
</template>
