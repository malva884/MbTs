<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { useI18n } from 'vue-i18n'
import type { Task } from '@/views/task/type'
import TaskView from "@/views/task/TaskView.vue"

interface Props {
  areaId?: string
  responsabile?: string
}

const props = defineProps<Props>()
const { t } = useI18n()

// Stato Tabella e Paginazione
const itemsPerPage = ref(10)
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const page = ref(1)
const serverItems = ref<any[]>([])
const loading = ref(false)

// UI States e Filtri
const isSnackbarScrollReverseVisible = ref(false)
const message = ref('')
const color = ref('')
const search = ref('')
const q = ref('')
const usersOptions = ref<any>([])
const TaskItemView = ref<Task>({})
const taskViewDialog = ref(false)
const loadingPage = ref(false)

// Headers Tabella
const headers = [
  { title: t('Table.Riferimento'), key: 'codice', width: '130px' },
  { title: t('Table.Titolo'), key: 'titolo' },
  { title: t('Table.Priorieta'), key: 'priorieta', width: '150px' },
  { title: t('Table.Data-Scadenza'), key: 'data_scadenza', width: '150px' },
  { title: t('Table.Avanzamento'), key: 'completamento', width: '180px', sortable: false },
  { title: t('Table.Stato'), key: 'stato', width: '160px' },
]

// Cambio Opzioni Paginazione / Ordinamento Server-side
const updateOptions = (options: any) => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
  page.value = options.page
  itemsPerPage.value = options.itemsPerPage
  q.value = options.search
  loadItems()
}

// Caricamento Elementi dal Server
const loadItems = async () => {
  loading.value = true
  try {
    const { data: resultData } = await useApi<any>(createUrl('/task/mylist', {
      query: {
        page: page.value,
        itemsPerPage: itemsPerPage.value,
        sortBy: sortBy.value,
        orderBy: orderBy.value,
        search: q.value,
      },
    }))

    if (resultData.value !== null) {
      serverItems.value = resultData.value.data ?? []
      totalItems.value = resultData.value.total ?? 0
    } else {
      serverItems.value = []
      totalItems.value = 0
    }
  } catch (error) {
    console.error("Errore nel caricamento dei task personali:", error)
    serverItems.value = []
    totalItems.value = 0
  } finally {
    loading.value = false
  }
}

// Resolver grafici uniformati
const resolveStato = (stato: string) => {
  const stati: Record<string, { text: string, color: string }> = {
    '1': { text: 'Aperto', color: 'secondary' },
    '2': { text: 'Chiuso', color: 'success' },
    '3': { text: 'Da Approvare', color: 'warning' },
    '4': { text: 'Sospeso', color: 'error' },
    '5': { text: 'In Svolgimento', color: 'primary' }
  }
  return stati[stato] || { text: '--', color: 'white' }
}

const resolveProprieta = (proprieta: string) => {
  const priorita: Record<string, { text: string, color: string }> = {
    '1': { text: 'Basso', color: 'secondary' },
    '2': { text: 'Normale', color: 'primary' },
    '3': { text: 'Alto', color: 'warning' },
    '4': { text: 'Critico', color: 'error' }
  }
  return priorita[proprieta] || { text: '--', color: 'white' }
}

const resolveavanzamento = (avanzamento: string) => {
  const av = Number(avanzamento)
  if (av === 100) return 'success'
  if (av >= 75) return 'primary'
  if (av >= 50) return 'warning'
  if (av >= 1) return 'error'
  return 'secondary'
}

// Chiamate API Utenti
const getUsers = async () => {
  if (!props.areaId) return
  try {
    const { data: usersData } = await useApi<any>(createUrl(`/task/aree/get_users/${props.areaId}`, {
      query: { only: true },
    }))
    usersOptions.value = usersData.value
  } catch (error) {
    console.error("Errore nel caricamento degli utenti:", error)
  }
}

const viewTask = async (item: Task) => {
  TaskItemView.value = { ...item }
  taskViewDialog.value = true
}

// AGGIORNATO: Gestisce l'aggiornamento reattivo in tempo reale senza ricaricare l'intera lista
const handleTaskUpdate = (updatedTask: any) => {
  if (!updatedTask || !updatedTask.id) return

  const oldTask = serverItems.value.find(task => task.id === updatedTask.id)
  const scadenzaChanged = oldTask && oldTask.data_scadenza !== updatedTask.data_scadenza

  serverItems.value = serverItems.value.map(task =>
    task.id === updatedTask.id ? { ...task, ...updatedTask } : task,
  )

  if (TaskItemView.value.id === updatedTask.id) {
    TaskItemView.value = { ...TaskItemView.value, ...updatedTask }
  }

  if (scadenzaChanged)
    loadItems()
}

onMounted(() => {
  getUsers()
  loadItems()
})

watch(props, () => {
  getUsers()
  loadItems()
})
</script>

<template>
  <div class="workspace-container w-100 h-100 d-flex flex-column pa-4 overflow-hidden">
    <VSnackbar
      v-model="isSnackbarScrollReverseVisible"
      transition="scroll-y-reverse-transition"
      location="top center"
      :color="color"
    >
      {{ $t(message) }}
    </VSnackbar>

    <div class="d-flex align-center justify-space-between flex-wrap gap-x-4 gap-y-2 mb-3 flex-shrink-0">
      <div class="d-flex align-baseline gap-2">
        <h3 class="text-h5 font-weight-bold mb-0">
          {{ $t('Label.I-Miei-Task') || 'I Miei Task' }}
        </h3>
        <span class="text-caption text-medium-emphasis d-none d-sm-inline">
          — Riepilogo delle tue attività personali
        </span>
      </div>
    </div>

    <VCard variant="outlined" class="bg-surface border-thin rounded-lg d-flex flex-column flex-grow-1 h-0 overflow-hidden">

      <div class="filter-toolbar d-flex align-center justify-space-between flex-wrap gap-3 px-4 py-2.5 border-b border-thin flex-shrink-0">
        <div class="search-box-wrapper">
          <AppTextField
            v-model="search"
            placeholder="Cerca nei tuoi task..."
            prepend-inner-icon="tabler-search"
            single-line
            hide-details
            density="compact"
            clearable
            clear-icon="tabler-x"
            @keyup.enter="loadItems"
            @focusout="loadItems"
            @click:clear="setTimeout(() => { search = ''; loadItems() }, 50)"
          />
        </div>
      </div>

      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        :headers="headers"
        :items="serverItems"
        :items-length="totalItems"
        :loading="loading"
        :search="search"
        density="comfortable"
        fixed-header
        class="task-custom-table flex-grow-1 h-0"
        @update:options="updateOptions"
      >
        <template #no-data>
          <div class="py-10 text-center">
            <VIcon icon="tabler-clipboard-text" size="40" class="text-disabled mb-2" />
            <p class="text-body-1 text-disabled mb-0">Nessun task assegnato trovato</p>
          </div>
        </template>

        <template #item.codice="{ item }">
          <VChip
            size="small"
            color="primary"
            variant="flat"
            class="font-weight-bold cursor-pointer font-monospace"
            @click="viewTask(item)"
          >
            {{ item.codice }}
          </VChip>
        </template>

        <template #item.titolo="{ item }">
          <span
            class="text-body-1 font-weight-medium text-high-emphasis text-link cursor-pointer"
            @click="viewTask(item)"
          >
            {{ item.titolo }}
          </span>
        </template>

        <template #item.priorieta="{ item }">
          <VChip
            size="small"
            :color="resolveProprieta(item.priorieta).color"
            variant="tonal"
            class="font-weight-semibold text-uppercase"
          >
            {{ resolveProprieta(item.priorieta).text }}
          </VChip>
        </template>

        <template #item.completamento="{ item }">
          <div class="d-flex align-center w-100 style-progress-wrapper">
            <VProgressLinear
              :model-value="Number(item.completamento || 0)"
              height="16"
              :color="resolveavanzamento(item.completamento)"
              rounded
            >
              <span class="text-white font-weight-bold text-caption">
                {{ Math.ceil(Number(item.completamento || 0)) }}%
              </span>
            </VProgressLinear>
          </div>
        </template>

        <template #item.stato="{ item }">
          <VChip
            size="small"
            :color="resolveStato(item.stato).color"
            variant="flat"
          >
            {{ resolveStato(item.stato).text }}
          </VChip>
        </template>
      </VDataTableServer>
    </VCard>

    <TaskView
      v-model:isDialogVisible="taskViewDialog"
      :task-data="TaskItemView"
      @task-data="handleTaskUpdate"
    />

    <LoadingStandBy v-model="loadingPage" />
  </div>
</template>

<style scoped lang="scss">
.workspace-container {
  box-sizing: border-box;
}

.gap-x-4 { column-gap: 16px; }
.gap-y-2 { row-gap: 8px; }
.gap-2 { gap: 8px; }
.cursor-pointer { cursor: pointer; }
.font-monospace { font-family: monospace; }

.text-link {
  transition: color 0.2s ease;
  &:hover {
    color: rgb(var(--v-theme-primary)) !important;
    text-decoration: underline;
  }
}

.style-progress-wrapper {
  max-width: 140px;
}

.filter-toolbar {
  background-color: rgba(var(--v-theme-on-surface), 0.015);

  .search-box-wrapper {
    flex: 1;
    min-width: 260px;
    max-width: 380px;
  }
}

/* Gestione dello scroll interno alla sola tabella */
.task-custom-table {
  background: transparent !important;
  display: flex;
  flex-direction: column;
  overflow: hidden;

  :deep(.v-table__wrapper) {
    overflow-y: auto !important;
    flex-grow: 1;
  }

  :deep(thead) {
    position: sticky !important;
    top: 0 !important;
    z-index: 2 !important;

    tr {
      background-color: rgb(var(--v-theme-surface)) !important;
    }

    th {
      font-weight: 700 !important;
      text-transform: uppercase;
      font-size: 0.75rem !important;
      letter-spacing: 0.5px;
      color: rgba(var(--v-theme-on-surface), 0.7) !important;
      border-bottom: 2px solid rgba(var(--v-border-color), 0.15) !important;
      background-color: rgb(var(--v-theme-surface)) !important;
    }
  }

  :deep(tbody) {
    tr {
      &:hover {
        background-color: rgba(var(--v-theme-on-surface), 0.02) !important;
      }
    }
    td {
      height: 52px !important;
    }
  }

  :deep(.v-data-table-footer) {
    border-top: 1px solid rgba(var(--v-border-color), var(--v-border-opacity)) !important;
    background-color: rgba(var(--v-theme-on-surface), 0.01);
    flex-shrink: 0;
  }
}
</style>
