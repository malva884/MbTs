<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { useI18n } from 'vue-i18n'
import type { Task } from '@/views/task/type'
import TaskView from '@/views/task/TaskView.vue'

interface Emit {
  (e: 'refresh', value: boolean): void
}

const emit = defineEmits<Emit>()
const { t } = useI18n()
const itemsPerPage = ref(6)
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const page = ref(1)
const serverItems = ref<any>([])
const search = ref('')
const q = ref('')
const loading = ref(false)
const TaskItemView = ref<Task>({})
const taskViewDialog = ref(false)

// headers
const headers = [
  { title: t('Table.Riferimento'), key: 'codice' },
  { title: t('Table.Titolo'), key: 'titolo' },
  { title: t('Table.Priorieta'), key: 'priorieta' },
]

const updateOptions = (options: any) => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
  page.value = options.page
  itemsPerPage.value = options.itemsPerPage
  q.value = options.search
  // eslint-disable-next-line @typescript-eslint/no-use-before-define
  loadItems()
}

const loadItems = async () => {
  loading.value = true

  const { data: resultData } = await useApi<any>(createUrl('task/dashboard/approvare', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      search: q.value,
      stato: 3,
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

const resolveStato = (stato: string) => {
  if (stato === '1')
    return { text: 'Aperto', color: 'secondary' }
  else if (stato === '2')
    return { text: 'Chiuso', color: 'success' }
  else if (stato === '3')
    return { text: 'Da Approvare', color: 'warning' }
  else if (stato === '4')
    return { text: 'Sospeso', color: 'error' }
  else if (stato === '5')
    return { text: 'In Svolgimento', color: 'primary' }
  else
    return { text: '--', color: 'bianco' }
}

const resolveProprieta = (proprieta: string) => {
  if (proprieta === '1')
    return { text: 'Basso', color: 'secondary' }
  else if (proprieta === '2')
    return { text: 'Normale', color: 'primary' }
  else if (proprieta === '3')
    return { text: 'Alto', color: 'error' }
  else if (proprieta === '4')
    return { text: 'Critico', color: 'critico' }
  else
    return { text: '--', color: 'bianco' }
}

const viewTask = async (item: Task) => {
  TaskItemView.value = { ...item }
  taskViewDialog.value = true
}

const refresh = async () => {
  await loadItems()
  emit('refresh', true)
}
</script>

<template>
  <VCard :title="$t('Label.Task-Da-Approvare')">
    <template #append>
      <div class="me-n2">
        <MoreBtn :menu-list="moreList" />
      </div>
    </template>

    <VDivider />
    <VDataTableServer
      v-model:items-per-page="itemsPerPage"
      :headers="headers"
      :items="serverItems"
      :items-length="totalItems"
      :loading="loading"
      :search="search"
      density="compact"
      @update:options="updateOptions"
    >
      <template #item.codice="{ item }">
        <div class="d-flex align-center">
          <div class="d-flex flex-column">
            <h6 class="text-base">
              <VBtn
                variant="text"
                @click="viewTask(item)"
              >
                {{ item.codice }}
              </VBtn>
            </h6>
          </div>
        </div>
      </template>

      <template #item.stato="{ item }">
        <VChip
          :color="resolveStato(item.stato).color"
          variant="outlined"
        >
          {{ resolveStato(item.stato).text }}
        </VChip>
      </template>
      <template #item.priorieta="{ item }">
        <VChip
          :color="resolveProprieta(item.priorieta).color"
          variant="elevated"
        >
          {{ resolveProprieta(item.priorieta).text }}
        </VChip>
      </template>
      <template #bottom />
    </VDataTableServer>
  </VCard>

  <TaskView
    v-model:isDialogVisible="taskViewDialog"
    :task-data="TaskItemView"
    @task-data="refresh"
  />
</template>

<style lang="scss" scoped>
.v-table {
  tbody {
    tr:not(:last-child) {
      td {
        border: none !important;
      }
    }
  }
}
</style>
