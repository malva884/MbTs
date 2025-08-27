<script setup lang="ts">
import {VDataTableServer} from 'vuetify/labs/VDataTable'
import {useI18n} from 'vue-i18n'
import {VForm} from 'vuetify/components/VForm'
import TaskView from '@/views/task/TaskView.vue'
import TaskComponent from '@/views/task/TaskComponent.vue'
import type { Task } from '@/views/task/type'
import type { Area } from '@/views/task/aree/type'

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
const loadingPage = ref(false)
const message = ref('')
const color = ref('')
const priorietaTask = ref()
const statoTask = ref()
const titoloTask = ref()
const q = ref('')
const loading = ref(false)
const taskVisibile = ref(false)
const TaskItem = ref<Task>({})
const TaskItemView = ref<Task>({})
const areaItem = ref<Area>({})
const isFormValid = ref(false)
const refForm = ref<VForm>()
const taskViewDialog = ref(false)

const userPermessi = ref<any>({
  responsabile: false,
  apriTask: false,
  chiudiTask: false,
  modificaTask: false,
  eliminaTask: false,
})

const getArea = async () => {
  const { data: areaData } = await useApi<any>(createUrl(`/task/aree/view/${props.areaId}`))

  areaItem.value = areaData.value

  await userLoad()
}

getArea()

const userLoad = async () => {
  const { data: userData } = await useApi<any>(createUrl(`/task/user/${props.areaId}`))

  userPermessi.value.responsabile = userData.value.responsabile === '1'
  userPermessi.value.apriTask = userData.value.aprire_task === '1'
  userPermessi.value.chiudiTask = userData.value.chiudere_task === '1'
  userPermessi.value.modificaTask = userData.value.modificare_task === '1'
  userPermessi.value.eliminaTask = userData.value.eliminare_task === '1'
}

// headers
const headers = [
  { title: t('Table.Riferimento'), key: 'codice' },
  { title: t('Table.Titolo'), key: 'titolo' },
  { title: t('Table.Priorieta'), key: 'priorieta' },
  { title: t('Table.Data-Scadenza'), key: 'data_scadenza' },
  { title: t('Table.Avanzamento'), key: 'completamento' },
  { title: t('Table.Stato'), key: 'stato' },
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
    serverItems.value = resultData.value.data
    totalItems.value = resultData.value.total
  } else {
    serverItems.value = []
    totalItems.value = 0
  }
  loading.value = false
  console.log(serverItems.value)
}

const resolveStato = (stato: string) => {
  if (stato === '1')
    return {text: 'Aperto', color: 'secondary'}
  else if (stato === '2')
    return {text: 'Chiuso', color: 'success'}
  else if (stato === '3')
    return {text: 'Da Approvare', color: 'warning'}
  else if (stato === '4')
    return {text: 'Sospeso', color: 'error'}
  else if (stato === '5')
    return {text: 'In Svolgimento', color: 'primary'}
  else
    return {text: '--', color: 'bianco'}
}

const resolveProprieta = (proprieta: string) => {
  if (proprieta === '1')
    return {text: 'Basso', color: 'secondary'}
  else if (proprieta === '2')
    return {text: 'Normale', color: 'primary'}
  else if (proprieta === '3')
    return {text: 'Alto', color: 'error'}
  else if (proprieta === '4')
    return {text: 'Critico', color: 'critico'}
  else
    return {text: '--', color: 'bianco'}
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



const newItem = () => {
  TaskItem.value.area_id = props.areaId
  TaskItem.value.responsabile_id = areaItem.value.responsabile_id
  TaskItem.value.responsabile = userPermessi.value.responsabile
  TaskItem.value.giorni_dopo_scadenza = 15
  TaskItem.value.stato = '1'
  taskVisibile.value = true
}

const viewTask = async (item: Task) => {
  TaskItemView.value = {...item}
  taskViewDialog.value = true
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
              cols="4"
              md="4"
            >
              <VBtn
                v-if="userPermessi.apriTask"
                prepend-icon="tabler-plus"
                color="primary"
                @click="newItem"
              >
                {{ $t('Label.Nuovo-Task') }}
              </VBtn>
            </VCol>
          </VRow>
          <VRow>
            <VCol
              cols="4"
              md="4"
            >
              <AppTextField
                v-model="titoloTask"
                placeholder="Titolo"
                single-line
                hide-details
                dense
                outlined
                density="compact"
                clearable
                clear-icon="tabler-x"
                @focusout="loadItems"
              />
            </VCol>
            <VCol
              md="4"
            >
              <AppSelect
                v-model="statoTask"
                :items="[{ value: '0', text: 'Tutti' }, { value: '1', text: 'Aperto' }, { value: '2', text: 'Chiuso' }, { value: '4', text: 'Sospeso' }, { value: '5', text: 'In Svolgimento' }]"
                item-title="text"
                item-value="value"
                placeholder="Stato"
                outlined
                density="compact"
                clearable
                clear-icon="tabler-x"
                @focusout="loadItems"
              />
            </VCol>
            <VCol
              md="4"
            >
              <AppSelect
                v-model="priorietaTask"
                :items="[{ value: '1', text: 'Basso' }, { value: '2', text: 'Normale' }, { value: '3', text: 'Alto' }, { value: '4', text: 'Critico' }]"
                item-title="text"
                item-value="value"
                placeholder="Priorietà"
                outlined
                density="compact"
                clearable
                clear-icon="tabler-x"
                @focusout="loadItems"
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
        </VDataTableServer>
      </VCard>
    </VCol>
  </VRow>

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
  />

  <LoadingStandBy v-model="loadingPage" />
</template>

<style scoped lang="scss">
a div {cursor:hand;}
</style>
