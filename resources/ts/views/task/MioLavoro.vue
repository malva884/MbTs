<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { useI18n } from 'vue-i18n'
import { VForm } from 'vuetify/components/VForm'
import type { Task } from '@/views/task/type'
import type { Area } from '@/views/task/aree/type'
import TaskView from "@/views/task/TaskView.vue";

interface Props {
  areaId: string
  responsabile: string
}

const props = defineProps<Props>()
const { t } = useI18n()
const itemsPerPage = ref(25)
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const page = ref(1)
const serverItems = ref<any>([])
const isSnackbarScrollReverseVisible = ref(false)
const message = ref('')
const color = ref('')
const search = ref('')
const q = ref('')
const loading = ref(false)
const usersOptions = ref<any>([])
const TaskItemView = ref<Task>({})
const taskViewDialog = ref(false)
const loadingPage = ref(false)

// headers
const headers = [
  { title: t('Table.Riferimento'), key: 'codice' },
  { title: t('Table.Titolo'), key: 'titolo' },
  { title: t('Table.Priorieta'), key: 'priorieta' },
  { title: t('Table.Data-Scadenza'), key: 'data_scadenza' },
  { title: t('Table.Avanzamento'), key: 'completamento' },
  { title: t('Table.Stato'), key: 'stato' },
  //{ title: 'ACTIONS', key: 'actions', sortable: false },
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

const resolveavanzamento = (avanzamento: string) => {
  const av = Number(avanzamento)
  if (av === 100)
    return 'success'
  else if (av <= 99 && av >= 75)
    return 'primary'
  else if (av <= 75 && av >= 50)
    return 'warning'
  else if (av <= 49 && av >= 1)
    return 'error'
  else
    return { text: '--', color: 'bianco' }
}

const getUsers = async () => {
  const { data: usersData } = await useApi<any>(createUrl(`/task/aree/get_users/${props.areaId}`, {
    query: {
      only: true,
    },
  }))

  usersOptions.value = usersData.value
}

getUsers()

const viewTask = async (item: Task) => {
  TaskItemView.value = { ...item }
  taskViewDialog.value = true
}

const refreshList = () => {
  loadItems()
}

watch(props, () => {
  getArea()
  loadItems()
})
</script>

<template>
  <VCard>
    <VSnackbar
      v-model="isSnackbarScrollReverseVisible"
      transition="scroll-y-reverse-transition"
      location="top central"
      :color="color"
    >
      {{ $t(message) }}
    </VSnackbar>
  </VCard>
  <VRow>
    <VCol cols="12">
      <VCard>
        <VCardText>
          <VRow>
            <VCol
              offset-md="4"
              md="4"
            >
              <AppTextField
                v-model="search"
                placeholder="Search ..."
                append-inner-icon="tabler-search"
                single-line
                hide-details
                dense
                outlined
                density="compact"
              />
            </VCol>
          </VRow>
        </VCardText>
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
            <a
              class="font-weight-medium text-link"
              style="cursor: pointer;"
              @click="viewTask(item)"
            >
              {{ item.codice }}
            </a>
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
          <template #item.completamento="{ item }">
            <VProgressLinear
              v-model="item.completamento"
              height="20"
              :color="resolveavanzamento(item.completamento)"
            >
              <span>{{ Math.ceil(item.completamento) }}%</span>
            </VProgressLinear>
          </template>
          <!-- Actions -->
          <template #item.actions="{ item }">
            <div class="d-flex gap-1">
              <IconBtn
                color="warning"
                @click="editItem(item)"
              >
                <VIcon icon="tabler-edit" />
              </IconBtn>
              <IconBtn
                color="error"
                @click="editItem(item)"
              >
                <VIcon icon="tabler-trash" />
              </IconBtn>
            </div>
          </template>
        </VDataTableServer>
      </VCard>
    </VCol>
  </VRow>

  <TaskView
    v-model:isDialogVisible="taskViewDialog"
    :task-data="TaskItemView"
    @update:item="refreshList"
  />
  <LoadingStandBy v-model="loadingPage"></LoadingStandBy>
</template>

<style scoped lang="scss">

</style>
