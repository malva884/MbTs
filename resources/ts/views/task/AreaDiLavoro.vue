<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { VForm } from 'vuetify/components/VForm'
import TaskView from '@/views/task/TaskView.vue'
import TaskComponent from '@/views/task/TaskComponent.vue'
import type { Task } from '@/views/task/type'
import type { Area } from '@/views/task/aree/type'

interface Props {
  areaId: string
  responsabile: string
}

const props = defineProps<Props>()

// Composables
const { t } = useI18n()

// Stato Tabella e Paginazione
const itemsPerPage = ref(10)
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const page = ref(1)
const serverItems = ref<any[]>([])
const loading = ref(false)
const expanded = ref<string[]>([])

// Filtri di ricerca
const titoloTask = ref('')
const statoTask = ref('0')
const priorietaTask = ref(null)

// Dialogs e UI State
const taskVisibile = ref(false)
const taskViewDialog = ref(false)
const isSnackbarScrollReverseVisible = ref(false)
const loadingPage = ref(false)
const message = ref('')
const color = ref('')

// Stato per espansione sub task
const expandedTasks = ref<Record<string, any[]>>({})
const loadingSubTasks = ref<Record<string, boolean>>({})

// Dati Modelli
const TaskItem = ref<Task>({})
const TaskItemView = ref<Task>({})
const areaItem = ref<Area>({})
const refForm = ref<VForm>()

// Permessi Utente
const userPermessi = ref<any>({
  responsabile: false,
  apriTask: false,
  chiudiTask: false,
  modificaTask: false,
  eliminaTask: false,
})

// Caricamento Dati Area
const getArea = async () => {
  try {
    const { data: areaData } = await useApi<any>(createUrl(`/task/aree/view/${props.areaId}`))
    areaItem.value = areaData.value
    await userLoad()
  } catch (error) {
    console.error("Errore nel caricamento dell'area:", error)
  }
}

// Caricamento Permessi Utente
const userLoad = async () => {
  try {
    const { data: userData } = await useApi<any>(createUrl(`/task/user/${props.areaId}`))
    userPermessi.value.responsabile = userData.value.responsabile === '1'
    userPermessi.value.apriTask = userData.value.aprire_task === '1'
    userPermessi.value.chiudiTask = userData.value.chiudere_task === '1'
    userPermessi.value.modificaTask = userData.value.modificare_task === '1'
    userPermessi.value.eliminaTask = userData.value.eliminare_task === '1'
  } catch (error) {
    console.error("Errore nel caricamento dei permessi utente:", error)
  }
}

// Headers Tabella
const headers = [
  { title: '', key: 'expand', width: '50px', sortable: false },
  { title: t('Table.Riferimento'), key: 'codice', width: '130px' },
  { title: t('Table.Titolo'), key: 'titolo' },
  { title: t('Table.Priorieta'), key: 'priorieta', width: '150px' },
  { title: t('Table.Data-Scadenza'), key: 'data_scadenza', width: '150px' },
  { title: t('Table.Avanzamento'), key: 'completamento', width: '180px', sortable: false },
  { title: t('Table.Stato'), key: 'stato', width: '160px' },
]

// Cambio Opzioni Tabella (Server-side)
const updateOptions = (options: any) => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
  page.value = options.page
  itemsPerPage.value = options.itemsPerPage
  loadItems()
}

// Caricamento Task dal Server
const loadItems = async () => {
  loading.value = true
  try {
    const { data: resultData } = await useApi<any>(createUrl(`/task/list/${props.areaId}`, {
      query: {
        page: page.value,
        itemsPerPage: itemsPerPage.value,
        sortBy: sortBy.value,
        orderBy: orderBy.value,
        stato: statoTask.value,
        titolo: titoloTask.value,
        priorieta: priorietaTask.value,
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
    console.error("Errore nel caricamento dei task:", error)
    serverItems.value = []
    totalItems.value = 0
  } finally {
    loading.value = false
  }
}

// Resolver grafici (Chip e Colori)
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

// Azioni
const newItem = () => {
  TaskItem.value = {}
  TaskItem.value.area_id = props.areaId
  TaskItem.value.responsabile_id = areaItem.value.responsabile_id
  TaskItem.value.responsabile = userPermessi.value.responsabile
  TaskItem.value.giorni_dopo_scadenza = 15
  TaskItem.value.stato = '1'
  taskVisibile.value = true
}

const viewTask = (item: Task) => {
  TaskItemView.value = { ...item }
  taskViewDialog.value = true
}

const handleTaskUpdate = (updatedTask: any) => {
  if (!updatedTask || !updatedTask.id) return

  const index = serverItems.value.findIndex(task => task.id === updatedTask.id)
  if (index !== -1) {
    serverItems.value[index] = {
      ...serverItems.value[index],
      ...updatedTask
    }
    if (TaskItemView.value.id === updatedTask.id) {
      TaskItemView.value = { ...TaskItemView.value, ...updatedTask }
    }
  }
}

// Funzione per caricare sub task
const toggleSubTasks = async (taskId: string) => {
  const index = expanded.value.indexOf(taskId)
  if (index > -1) {
    // Collassa se già espanso
    expanded.value.splice(index, 1)
  } else {
    // Espandi e carica sub task
    expanded.value.push(taskId)
    loadingSubTasks.value[taskId] = true
    try {
      const { data: subTaskData } = await useApi<any>(createUrl(`/task/sub_task_list/${taskId}`))
      expandedTasks.value[taskId] = subTaskData.value ?? []
    } catch (error) {
      console.error('Errore nel caricamento dei sub task:', error)
      expandedTasks.value[taskId] = []
    } finally {
      loadingSubTasks.value[taskId] = false
    }
  }
}

// Resolver per stato sub task
const resolveStatoSubTask = (stato: string) => {
  const stati: Record<string, { text: string, color: string, icon: string }> = {
    '1': { text: 'Da Fare', color: 'secondary', icon: 'tabler-circle' },
    '2': { text: 'Chiuso', color: 'success', icon: 'tabler-circle-check-filled' },
    '3': { text: 'Bloccato', color: 'error', icon: 'tabler-circle-x-filled' },
    '4': { text: 'Sospeso', color: 'warning', icon: 'tabler-circle-minus-filled' },
    '5': { text: 'In Corso', color: 'primary', icon: 'tabler-circle-dot-filled' }
  }
  return stati[stato] || { text: 'N/D', color: 'secondary', icon: 'tabler-circle' }
}

onMounted(() => {
  getArea()
  loadItems()
})

watch(() => props.areaId, () => {
  getArea()
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
          {{ areaItem.nome || 'Area di Lavoro' }}
        </h3>
        <span class="text-caption text-medium-emphasis d-none d-sm-inline">
          — Gestione attività team
        </span>
      </div>

      <VBtn
        v-if="userPermessi.apriTask"
        prepend-icon="tabler-plus"
        color="primary"
        variant="flat"
        density="comfortable"
        class="px-3"
        @click="newItem"
      >
        {{ $t('Label.Nuovo-Task') }}
      </VBtn>
    </div>

    <VCard variant="outlined" class="bg-surface border-thin rounded-lg d-flex flex-column flex-grow-1 h-0 overflow-hidden">

      <div class="filter-toolbar d-flex align-center justify-space-between flex-wrap gap-3 px-4 py-2.5 border-b border-thin flex-shrink-0">

        <div class="search-box-wrapper">
          <AppTextField
            v-model="titoloTask"
            placeholder="Cerca task per titolo..."
            prepend-inner-icon="tabler-search"
            single-line
            hide-details
            density="compact"
            clearable
            clear-icon="tabler-x"
            @keyup.enter="loadItems"
            @update:model-value="loadItems"
            @click:clear="setTimeout(() => { titoloTask = ''; loadItems() }, 50)"
          />
        </div>

        <div class="d-flex align-center gap-2 select-filters-wrapper">
          <div class="filter-select-item">
            <AppSelect
              v-model="statoTask"
              :items="[{ value: '0', text: 'Tutti gli Stati' }, { value: '1', text: 'Aperto' }, { value: '2', text: 'Chiuso' }, { value: '4', text: 'Sospeso' }, { value: '5', text: 'In Svolgimento' }]"
              item-title="text"
              item-value="value"
              placeholder="Stato"
              hide-details
              density="compact"
              clearable
              clear-icon="tabler-x"
              @update:model-value="loadItems"
              @click:clear="setTimeout(() => { statoTask = null; loadItems() }, 50)"

            />
          </div>

          <div class="filter-select-item">
            <AppSelect
              v-model="priorietaTask"
              :items="[{ value: null, text: 'Tutte le Priorità' }, { value: '1', text: 'Basso' }, { value: '2', text: 'Normale' }, { value: '3', text: 'Alto' }, { value: '4', text: 'Critico' }]"
              item-title="text"
              item-value="value"
              placeholder="Priorità"
              hide-details
              density="compact"
              clearable
              clear-icon="tabler-x"
              @update:model-value="loadItems"
              @click:clear="setTimeout(() => { priorietaTask = null; loadItems() }, 50)"
            />
          </div>
        </div>
      </div>

      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:expanded="expanded"
        :headers="headers"
        :items="serverItems"
        :items-length="totalItems"
        :loading="loading"
        density="comfortable"
        fixed-header
        class="task-custom-table flex-grow-1 h-0"
        @update:options="updateOptions"
      >
        <template #no-data>
          <div class="py-10 text-center">
            <VIcon icon="tabler-clipboard-text" size="40" class="text-disabled mb-2" />
            <p class="text-body-1 text-disabled mb-0">Nessun task disponibile per questa area</p>
          </div>
        </template>

        <template #item.expand="{ item }">
          <VBtn
            icon
            size="small"
            variant="text"
            color="secondary"
            @click.stop="toggleSubTasks(item.id)"
          >
            <VIcon
              :icon="expanded.includes(item.id) ? 'tabler-chevron-down' : 'tabler-chevron-right'"
              size="20"
            />
          </VBtn>
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
          <div class="d-flex align-center gap-2">
            <VChip
              size="small"
              :color="resolveProprieta(item.priorieta).color"
              variant="tonal"
              class="font-weight-semibold text-uppercase"
            >
              {{ resolveProprieta(item.priorieta).text }}
            </VChip>

            <VTooltip v-if="item.richiedente" location="top">
              <template #activator="{ props: tooltipProps }">
                <VIcon
                  v-bind="tooltipProps"
                  icon="tabler-user-share"
                  size="18"
                  class="cursor-pointer opacity-70 text-secondary"
                />
              </template>
              <span>{{ $t('Label.Richiedente') }}: {{ item.richiedente }}</span>
            </VTooltip>
          </div>
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

        <template #expanded-row="{ item }">
          <td :colspan="headers.length" class="pa-0">
            <div class="sub-tasks-container">
              <div v-if="loadingSubTasks[item.id]" class="text-center py-4">
                <VProgressCircular indeterminate color="primary" size="24" />
              </div>
              <div v-else-if="expandedTasks[item.id] && expandedTasks[item.id].length > 0" class="sub-tasks-list">
                <div
                  v-for="subTask in expandedTasks[item.id]"
                  :key="subTask.id"
                  class="sub-task-item d-flex align-center gap-3 pa-3 border-b"
                >
                  <VIcon
                    :icon="resolveStatoSubTask(subTask.stato).icon"
                    :color="resolveStatoSubTask(subTask.stato).color"
                    size="16"
                  />
                  <span class="text-body-2 font-weight-medium flex-grow-1">{{ subTask.titolo }}</span>
                  <VChip
                    size="x-small"
                    :color="resolveStatoSubTask(subTask.stato).color"
                    variant="tonal"
                    class="text-uppercase font-weight-bold"
                  >
                    {{ resolveStatoSubTask(subTask.stato).text }}
                  </VChip>
                  <VChip
                    size="x-small"
                    :color="resolveProprieta(subTask.priorieta).color"
                    variant="tonal"
                    class="text-uppercase font-weight-bold"
                  >
                    {{ resolveProprieta(subTask.priorieta).text }}
                  </VChip>
                </div>
              </div>
              <div v-else-if="expandedTasks[item.id] && expandedTasks[item.id].length === 0" class="text-center py-4 text-disabled">
                Nessun sub task presente
              </div>
            </div>
          </td>
        </template>
      </VDataTableServer>
    </VCard>

    <TaskComponent
      v-model:isDialogVisible="taskVisibile"
      :task-data="TaskItem"
      :area-data="areaItem"
      :responsabile-data="userPermessi.responsabile"
      @update:is-dialog-visible="loadItems"
    />

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
.gap-3 { gap: 12px; }
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

  .select-filters-wrapper {
    flex-wrap: wrap;
  }

  .filter-select-item {
    width: 180px;

    @media screen and (max-width: 600px) {
      width: 100%;
    }
  }
}

/* Gestione dello scroll interno esclusivo sulla tabella */
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

.sub-tasks-container {
  background-color: rgba(var(--v-theme-on-surface), 0.02);
}

.sub-tasks-list {
  max-height: 300px;
  overflow-y: auto;
}

.sub-task-item {
  background-color: rgb(var(--v-theme-surface));
  transition: background-color 0.2s ease;

  &:hover {
    background-color: rgba(var(--v-theme-on-surface), 0.03);
  }
}
</style>
