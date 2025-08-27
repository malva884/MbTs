<script lang="ts" setup>
import { VForm } from 'vuetify/components/VForm'
import type { Task } from '@/views/task/type'
import TaskDettaglioView from '@/views/task/TaskDettaglioView.vue'
import type {ChatContact as TypeChatContact} from "@/views/task/note/type"
import {useNotStore} from "@/views/task/view/useNoteStore"
import TaskAttivita from "@/views/task/view/TaskAttivita.vue"
import type {ReprotChecker} from "@/views/quality/checker/type";


interface Emit {
  (e: 'update:isDialogVisible', value: boolean): void
  (e: 'task-data', value: object): void
}

interface Props {
  isDialogVisible: boolean
  taskData: Task
}

const userPermessi = ref<any>({
  responsabile: false,
  apriTask: false,
  chiudiTask: false,
  modificaTask: false,
  eliminaTask: false,
})

const props = defineProps<Props>()
const emit = defineEmits<Emit>()
const path = import.meta.env.VITE_BASE_URL_PORTALE
const store = useNotStore()
// Chat message
const loadingPage = ref(false)
const msg = ref('')
const taskTab = ref(null)
const subTaskDialog = ref(false)
const expiredTaskDialog = ref(false)
const TaskItem = ref<Task>(<Task>{})
const refForm = ref<VForm>()
const subTaskList = ref([])
const userTaskList = ref([])
const userTask = ref()
const dettaglioView = ref(false)
const dettaglio = ref<Task>(<Task>{})
const avanzamentoTaskDialog = ref(false)
const usersView = ref(false)
const fileDialog = ref(false)
const notaAvanzamento = ref()
const notaScadenza = ref()
const errors = ref({})
const nota = ref()
const usersTask = ref([])
const users = ref([])
const file = ref(null)
const data = ref({})
const fileName = computed(() => file.value?.name)
const fileExtension = computed(() => fileName.value?.substr(fileName.value?.lastIndexOf('.') + 1))
const fileMimeType = computed(() => file.value?.type)
const dialogTaskId = ref()
const change = ref(1)
const isAlert = ref(false)
const view = ref(false)

const defaultItem = ref<any>({
  id:  null,
  area_id:  null,
  padre: '',
  responsabile_id: '',
  utente_id: '',
  codice: '',
  stato: '',
  reparto_id: '',
  mansione_id: '',
  titolo: '',
  descrizione: '',
  data_chiusura: '',
  data_scadenza: '',
  data_scadenza_iniziale: '',
  giorni_dopo_scadenza: '',
  completamento: '',
  priorieta: '',
  numero: '',
  near_miss_id: '',
  path_drive: '',
  created_at: '',
  full_name: '',
})

function new_defaultItem() {
  defaultItem.value = {
    id:  null,
    area_id:  null,
    padre: '',
    responsabile_id: '',
    utente_id: '',
    codice: '',
    stato: '',
    reparto_id: '',
    mansione_id: '',
    titolo: '',
    descrizione: '',
    data_chiusura: '',
    data_scadenza: '',
    data_scadenza_iniziale: '',
    giorni_dopo_scadenza: '',
    completamento: '',
    priorieta: '',
    numero: '',
    near_miss_id: '',
    path_drive: '',
    created_at: '',
    full_name: '',
  }
}

const avanzamento = ref(1)
const avanzamentoLabels = { 0: '0 %', 25: '25 %', 50: '50 %', 75: '75 %', 100: '100 %' }

const tabs = [
  { icon: 'tabler-cloud', title: 'File' },
  { icon: 'tabler-activity', title: 'Attività' },
]



const userLoad = async () => {
  const { data: userData } = await useApi<any>(createUrl(`/task/user/${props.taskData.area_id}`))

  userPermessi.value.responsabile = userData.value.responsabile === '1'
  userPermessi.value.apriTask = userData.value.aprire_task === '1'
  userPermessi.value.chiudiTask = userData.value.chiudere_task === '1'
  userPermessi.value.modificaTask = userData.value.modificare_task === '1'
  userPermessi.value.eliminaTask = userData.value.eliminare_task === '1'

}


const subTaskLoad = async () => {
  const { data: subTaskData } = await useApi<any>(createUrl(`/task/sub_task_list/${props.taskData.id}`))
  subTaskList.value = subTaskData.value
  change.value = change.value + 1
}

const usersTaskLoad = async () => {
  const { data: usersTaskData } = await useApi<any>(createUrl(`/task/users_task_list/${props.taskData.id}`))
  userTaskList.value = usersTaskData.value
}

const resolveIcon = (stato: string, tipo: string) => {
  if (tipo === 'task' && stato === '2')
    return { icon: 'tabler-checks', color: 'secondary' }
  else if (stato === '1')
    return { icon: 'tabler-hourglass-empty', color: 'secondary' }
  else if (stato === '2')
    return { icon: 'tabler-check', color: 'success' }
  else if (stato === '3')
    return { icon: 'tabler-player-pause', color: 'cord' }
  else if (stato === '4')
    return { icon: 'tabler-hand-stop', color: 'warning' }
  else if (stato === '5')
    return { icon: 'tabler-progress', color: 'primary' }
  else
    return { icon: '--', color: 'bianco' }
}

const resolvePriorieta = (proprieta: string) => {
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

const newSubTask = () => {
  TaskItem.value.priorieta = '1'
  TaskItem.value.area_id = props.taskData.area_id
  TaskItem.value.responsabile_id = props.taskData.responsabile_id
  TaskItem.value.padre = props.taskData.id
  TaskItem.value.numero = props.taskData.numero
  TaskItem.value.codice = props.taskData.codice
  TaskItem.value.path_drive = props.taskData.path_drive
  TaskItem.value.stato = '1'
  subTaskDialog.value = true
}

const storedSubTask = () => {
  refForm.value?.validate().then(async ({ valid }) => {
    if (valid) {
      console.log(TaskItem.value)
      if(TaskItem.value.padre === null && TaskItem.value.stato === '2' && subTaskList.value.length > 0){
        const tmp = subTaskList.value.find(item => item.stato !== 2)
        if(tmp.id !== undefined) {
          isAlert.value = true
          return ''
        }
      }
      loadingPage.value = true
      let path = '/task/store_sub_task'
      if (TaskItem.value.id)
        path = `/task/update_sub_task/${TaskItem.value.id}`
      await $api(path, {
        method: 'POST',
        body: TaskItem.value,
      })

      nextTick(() => {
        refForm.value?.reset()
        refForm.value?.resetValidation()
      })

      if(TaskItem.value.padre === null){
        props.taskData.stato = TaskItem.value.stato
        props.taskData.priorieta = TaskItem.value.priorieta
        props.taskData.titolo = TaskItem.value.titolo
        props.taskData.descrizione = TaskItem.value.descrizione
      }

      await subTaskLoad()
      new_defaultItem()
      TaskItem.value = {...defaultItem}
      subTaskDialog.value = false
      loadingPage.value = false
    }
  })
}



const close = async () => {
  emit('task-data', props.taskData)
  emit('update:isDialogVisible', false)
}

const closeEdit = () => {
  TaskItem.value = []
  subTaskDialog.value = false
}

const closeAvanzamento = () => {
  avanzamentoTaskDialog.value = false
}

const editTask = (task: Task) => {
  TaskItem.value = { ...task }
  subTaskDialog.value = true
}

const apriDettaglio = (item: Task) => {
  dettaglio.value = { ...item }
  dettaglioView.value = true
}

const color = computed(() => {
  if (avanzamento.value === 0)
    return 'error'
  if (avanzamento.value === 25)
    return 'error'
  if (avanzamento.value === 50)
    return 'warning'
  if (avanzamento.value === 75)
    return 'primary'
  if (avanzamento.value === 100)
    return 'success'

  return 'error'
})

const editAvanzamento = () => {
  if (props.taskData.completamento != null)
    avanzamento.value = Number(props.taskData.completamento)
  avanzamentoTaskDialog.value = true
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

const saveAvanzamento = async () => {
  if (notaAvanzamento.value !== undefined) {
    await $api(`/task/${props.taskData.id}/avanzamento`, {
      method: 'POST',
      body: {
        avanzamento: avanzamento.value,
        nota: notaAvanzamento.value,
      },
    })
    // eslint-disable-next-line vue/no-mutating-props
    props.taskData.completamento = avanzamento.value
    avanzamentoTaskDialog.value = false
  }
  else {
    errors.value.nota = 'Campo Obbligatorio'
  }
}

function openDrivePage(path: string) {
  window.open(`https://drive.google.com/drive/u/0/folders/${path}`, '_blank')
}

const usersDialog = async () => {
  usersTaskLoad()
  const { data: userTaskData } = await useApi<any>(createUrl(`/task/aree/get_users/${props.taskData.area_id}`, {
    query: {
      only: 'true',
      //escludiGiaMenbri: props.taskData.id,
    },
  }))

  users.value = userTaskData.value
  usersTask.value = []

  usersView.value = true
}

const getUserTask = async () => {
  const userTmp = []
  const { data: userTaskArea } = await useApi<any>(createUrl(`/task/aree/get_users/${props.taskData.area_id}`, {
    query: {
      only: 'true',
      //escludiGiaMenbri: props.taskData.id,
    },
  }))

  const { data: userTaskData } = await useApi<any>(createUrl(`/task/users_task_list/${props.taskData.id}`, {
    query: {
      only: 'true',
      //escludiGiaMenbri: props.taskData.id,
    },
  }))


  userTaskData.value.forEach((value: string) => {
    const tmp = userTaskArea.value.find(item => item.id === value)
    console.log('utente')
    console.log(tmp)
    userTmp.push({id: tmp.id, name: tmp.full_name, avatar: tmp.avatar})
  })

  userTask.value = userTmp
}

const addUsersTask = async () => {
  loadingPage.value = true
  await $api(`/task/${props.taskData.id}/setUsers`, {
    method: 'POST',
    body: {
      users: userTaskList.value,
      area_id: props.taskData.area_id,
    },
  })
  usersView.value = false
  loadingPage.value = false
}

const closeAddUsersTask = () => {
  userTaskList.value = []
  usersView.value = false
}

const dialogFile = (taskId: string) => {

  dialogTaskId.value = taskId
  fileDialog.value = true
}

const closefileTask = () => {
  dialogTaskId.value = null
  fileDialog.value = false
}
const uploadFileTask = async () => {
  refForm.value?.validate().then(async ({ valid }) => {
    if (valid) {
      loadingPage.value = true
      const retuenData = await $api(`/task/uploadFile/${dialogTaskId.value}`, {
        method: 'POST',
        body: {
          file_upload: data.value,
          nota: nota.value,
        },
      })
      data.value = null
      nota.value = null
      fileDialog.value = false
      loadingPage.value = false
    }
  })
}

const closeDetails = (task: Task) => {
  subTaskLoad()
  props.taskData.stato = task.stato
  dettaglioView.value = false
}


const uploadFile = (event: any) => {
  file.value = event.target.files[0]

  const reader = new FileReader()

  reader.readAsDataURL(file.value)
  reader.onload = async () => {
    const encodedFile = reader.result.split(',')[1]

    data.value = {
      file: encodedFile,
      fileName: fileName.value,
      fileExtension: fileExtension.value,
      fileMimeType: fileMimeType.value,
    }
  }
}

const storeExpiredTask = () => {
  refForm.value?.validate().then(async ({ valid }) => {
    if (valid) {
      loadingPage.value = true
      const retuenData = await $api(`/task/notaScadenza/${props.taskData.id}`, {
        method: 'POST',
        body: {
          nota: notaScadenza.value,
        },
      })
      expiredTaskDialog.value = false
      loadingPage.value = false
    }
  })
}

watch(props, () => {
  const oggi: Date = new Date()
  const scadenza: Date = new Date(props.taskData.data_scadenza)
  if(oggi.getTime() > scadenza.getTime())
    expiredTaskDialog.value = true
  userLoad()
  getUserTask()
  subTaskLoad()
  usersTaskLoad()
  if(props.isDialogVisible === false)
    expiredTaskDialog.value = false
})

</script>

<template>
  <VDialog
    v-model="props.isDialogVisible"
    fullscreen=""
    :scrim="false"
    transition="dialog-bottom-transition"
  >
    <!-- Dialog Content -->
    <VCard>
      <!-- Toolbar -->
      <div>
        <VToolbar :color="resolvePriorieta(props.taskData.priorieta).color">
          <VBtn
            icon
            variant="plain"
            @click="close"
          >
            <VIcon
              color="white"
              icon="tabler-x"
            />
          </VBtn>

          <VToolbarTitle>{{ props.taskData.codice }}</VToolbarTitle>

          <VSpacer />

          <VToolbarItems>
            <VBtn
              variant="text"
              @click="close"
            >
              {{ $t('Label.Chiudi') }}
            </VBtn>
          </VToolbarItems>
        </VToolbar>
      </div>

      <VRow class="mt-4">
        <VCol cols="8">
            <!-- List -->
            <VList lines="two">
              <VList
                nav
                :lines="false"
              >
                <VListItem value="1">
                  <template #prepend>
                    <VIcon
                      :icon="resolveIcon(props.taskData.stato, 'task').icon"
                      :color="resolveIcon(props.taskData.stato).color"
                    />
                  </template>

                  <VListItemTitle @click="apriDettaglio(props.taskData)">
                    {{ props.taskData.titolo }}
                    &nbsp;
                    <VChip
                      :color="resolvePriorieta(props.taskData.priorieta).color "
                      variant="elevated"
                    >
                      {{ resolvePriorieta(props.taskData.priorieta).text }}
                    </VChip>
                    &nbsp;
                    <div class="v-avatar-group demo-avatar-group">
                      <VAvatar v-for="user in userTask"
                        :size="25">
                        <VImg :src="path + user.avatar" />
                        <VTooltip
                          activator="parent"
                          location="top"
                        >
                          {{ user.name }}
                        </VTooltip>
                      </VAvatar>
                    </div>
                  </VListItemTitle>

                  <template #append>
                    <VIcon
                      v-if="userPermessi.apriTask"
                      icon="tabler-file-plus"
                      color="info"
                      @click="newSubTask"
                    />
                    <VIcon
                      v-if=userPermessi.responsabile
                      icon="tabler-user-plus"
                      color="primary"
                      @click="usersDialog"
                    />
                    <VIcon
                      icon="tabler-upload"
                      color="warning"
                      @click="dialogFile(props.taskData.id)"
                    />
                    <VIcon
                      icon="tabler-brand-google-drive"
                      color="primary"
                      @click="openDrivePage(props.taskData.path_drive)"
                    />
                    <VIcon
                      v-if="userPermessi.modificaTask || userPermessi.chiudiTask"
                      icon="tabler-edit"
                      color="info"
                      @click="editTask(props.taskData)"
                    />
                    <VIcon
                      v-if="userPermessi.eliminaTask"
                      icon="tabler-trash"
                      color="error"
                      @click="dialogFile"
                    />
                  </template>
                </VListItem>

                <VListItem
                  v-for="item in subTaskList"
                  :key="item.id"
                  class="ml-4"
                  :value="item.id"
                >
                  <template #prepend>
                    <VIcon
                      :icon="resolveIcon(item.stato, 'sub-task').icon"
                      :color="resolveIcon(item.stato).color"
                    />
                  </template>

                  <template #append>
                    <VIcon
                      icon="tabler-upload"
                      color="warning"
                      @click="dialogFile(item.id)"
                    />
                    <VIcon
                      icon="tabler-brand-google-drive"
                      color="primary"
                      @click="openDrivePage(item.path_drive)"
                    />
                    <VIcon
                      v-if="userPermessi.modificaTask || userPermessi.chiudiTask"
                      icon="tabler-edit"
                      color="info"
                      @click="editTask(item)"
                    />
                    <VIcon
                      v-if="userPermessi.eliminaTask"
                      icon="tabler-trash"
                      color="error"
                      @click="test"
                    />
                  </template>

                  <VListItemTitle @click="apriDettaglio(item)">
                    {{ item.titolo }}
                    &nbsp;
                    <VChip
                      :color="resolvePriorieta(item.priorieta).color "
                      variant="elevated"
                    >
                      {{ resolvePriorieta(item.priorieta).text }}
                    </VChip>
                  </VListItemTitle>
                </VListItem>
              </VList>
            </VList>

        </VCol>
        <VCol cols="4" v-if="props.taskData.stato !== '3'">
          <VCol cols="12">
            <VCard>
              <VCardTitle>
                {{ $t('Label.Avanzamento-Task') }}
              </VCardTitle>
              <VProgressLinear
                v-model="props.taskData.completamento"
                height="20"
                :color="resolveavanzamento(props.taskData.completamento)"
              >
                <span>{{ Math.ceil(props.taskData.completamento) }}%</span>
              </VProgressLinear>
              <VCardActions>
                <VSpacer />
                <VBtn
                  class="mt-3"
                  type="submit"
                  color="info"
                  variant="elevated"
                  @click="editAvanzamento"
                >
                  {{ $t('Label.Modifica') }}
                </VBtn>
              </VCardActions>
            </VCard>
          </VCol>

          <VCol cols="12">
            <VCard>
              <!-- List -->
              <VList lines="two">
                <VTabs v-model="taskTab">
                  <VTab
                    v-for="tab in tabs"
                    :key="tab.icon"
                  >
                    <VIcon
                      :size="15"
                      :icon="tab.icon"
                    />
                    <span class="h-10">{{ tab.title }}</span>
                  </VTab>
                </VTabs>

                <VWindow
                  v-model="taskTab"
                  class="mt-1 disable-tab-transition"
                  :touch="false"
                >
                  <VWindowItem>

                  </VWindowItem>

                  <VWindowItem>
                    <VCol
                      cols="12"
                      md="12"
                    >
                      <TaskAttivita :task-id="props.taskData.id" />
                    </VCol>
                  </VWindowItem>
                </VWindow>
              </VList>
            </VCard>
          </VCol>
        </VCol>
      </VRow>
    </VCard>
  </VDialog>

  <VDialog
    v-model="subTaskDialog"
    persistent
    class="v-dialog-xl"
  >
    <!-- Dialog close btn -->
    <DialogCloseBtn @click="closeEdit" />

    <!-- Dialog Content -->
    <VCard :title="TaskItem.id ? $t('Label.Modifica-Sub-Task') : $t('Label.Nuovo-Sub-Task')">
      <VForm
        ref="refForm"
        @submit.prevent="storedSubTask"
      >
        <VCardText>
          <VRow>
            <VCol
              cols="12"
              sm="12"
              md="12"
            />
            <VCol
              cols="12"
              sm="6"
              md="6"
            >
              <AppTextField
                v-model="TaskItem.titolo"
                :label="$t('Label.Titolo')"
                :placeholder="$t('Label.Titolo')"
                :rules="[requiredValidator]"
                :readonly="!userPermessi.modificaTask"
              />
            </VCol>
            <!-- 👉 priorita -->
            <VCol
              cols="12"
              md="4"
            >
              <AppSelect
                v-model="TaskItem.priorieta"
                :items="[{ value: '1', text: 'Basso' }, { value: '2', text: 'Normale' }, { value: '3', text: 'Alto' }, { value: '4', text: 'Critico' }]"
                item-title="text"
                item-value="value"
                :label="$t('Label.Priorita')"
                :placeholder="$t('Label.Priorita')"
                :rules="[requiredValidator]"
                :readonly="!userPermessi.modificaTask"
              />
            </VCol>
            <VCol
              cols="12"
              sm="6"
              md="6"
            >
              <TiptapEditor
                v-model="TaskItem.descrizione"
                :placeholder="$t('Label.Descrizione')"
                :label="$t('Label.Descrizione')"
                :class="'border rounded basic-editor ' + (!userPermessi.modificaTask ? 'v-rating--readonly' : '' )"
                :rules="[requiredValidator]"
              />
            </VCol>
            <VCol
              v-if="TaskItem.id"
              cols="12"
              md="4"
            >
              <AppSelect
                v-model="TaskItem.stato"
                :items="[{ value: '1', text: 'Aperto' }, { value: '5', text: 'In Svolgimento' }, { value: '4', text: 'Sospeso' }, { value: '2', text: 'Chiuso' }]"
                item-title="text"
                item-value="value"
                :label="$t('Label.Stato')"
                :placeholder="$t('Label.Stato')"
                :rules="[requiredValidator]"
                :readonly="!userPermessi.chiudiTask"
              />
            </VCol>
          </VRow>
        </VCardText>

        <VCardText class="d-flex justify-end flex-wrap gap-3">
          <VBtn
            variant="tonal"
            color="secondary"
            @click="closeEdit"
          >
            Close
          </VBtn>
          <VBtn
            type="submit"
            @click="refForm?.validate()"
          >
            Save
          </VBtn>
        </VCardText>
      </VForm>
    </VCard>
  </VDialog>

  <!-- 👉 Avanzamento Task -->
  <VDialog
    v-model="avanzamentoTaskDialog"
    persistent
    class="v-dialog-xl"
  >
    <!-- Dialog close btn -->
    <DialogCloseBtn @click="closeAvanzamento" />

    <!-- Dialog Content -->
    <VCard :title="$t('Label.Avanzamento-Task')">
      <VCardText>
        <VSlider
          v-model="avanzamento"
          :ticks="avanzamentoLabels"
          :color="color"
          :max="100"
          step="25"
          show-ticks="always"
          tick-size="4"
        />
      </VCardText>
      <VCol cols="12">
        <VRow no-gutters>
          <VCol
            cols="12"
            md="1"
          />
          <VCol
            cols="12"
            md="10"
          >
            <AppTextField
              v-model="notaAvanzamento"
              :label="`${$t('Label.Nota')} *`"
              :error-messages="errors.nota"
              autocomplete="off"
            />
          </VCol>
        </VRow>
      </VCol>

      <!-- 👉 submit and reset button -->
      <VCol cols="12">
        <VRow no-gutters>
          <VCol
            cols="12"
            md="3"
          />
          <VCol
            cols="12"
            md="9"
          >
            <VBtn
              type="submit"
              color="success"
              class="me-4"
              @click="saveAvanzamento"
            >
              {{ $t('Label.Salva') }}
            </VBtn>
          </VCol>
        </VRow>
      </VCol>
    </VCard>
  </VDialog>

  <!-- 👉 Users Task -->
  <VDialog
    v-model="usersView"
    persistent
    class="v-dialog-xl"
  >
    <!-- Dialog close btn -->
    <DialogCloseBtn @click="closeAddUsersTask" />
    <VCard :title="$t('Label.Aggiungi-Utente')">
      <VForm
        ref="refForm"
        @submit.prevent="addUsersTask"
      >
        <VCardText>
          <VRow>
            <VCol
              cols="12"
              sm="12"
              md="12"
            />
            <VCol
              cols="12"
              md="12"
            >
              <AppSelect
                v-model="userTaskList"
                :items="users"
                :rules="[requiredValidator]"
                item-title="full_name"
                item-value="id"
                :placeholder="$t('Label.Users')"
                multiple
                clearable
                clear-icon="tabler-x"
              >
                <template #selection="{ item }">
                  <VChip>
                    <span>{{ item.title }}</span>
                  </VChip>
                </template>
              </AppSelect>
            </VCol>
          </VRow>
        </VCardText>

        <VCardText class="d-flex justify-end flex-wrap gap-3">
          <VBtn
            variant="tonal"
            color="secondary"
            @click="closeAddUsersTask"
          >
            Close
          </VBtn>
          <VBtn
            type="submit"
            @click="refForm?.validate()"
          >
            Save
          </VBtn>
        </VCardText>
      </VForm>
    </VCard>
  </VDialog>

  <!-- 👉 Files Task -->
  <VDialog
    v-model="fileDialog"
    persistent
    class="v-dialog-xl"
  >
    <!-- Dialog close btn -->
    <DialogCloseBtn @click="closefileTask" />
    <VCard :title="$t('Label.Upload-File')">
      <VForm
        ref="refForm"
        @submit.prevent="uploadFileTask"
      >
        <VCardText>
          <VRow>
            <VCol
              cols="12"
              sm="12"
              md="12"
            />
            <VCol
              cols="12"
              md="6"
            >
              <VFileInput
                accept="image/*,application/pdf,application/vnd.ms-excel"
                label="File input"
                :rules="[requiredValidator]"
                @change="uploadFile"
              />
            </VCol>
            <VCol
              cols="12"
              md="11"
              class="ml-9"
            >
              <AppTextField
                v-model="nota"
                :label="`${$t('Label.Nota')} *`"
                :rules="[requiredValidator]"
                autocomplete="off"
              />
            </VCol>
          </VRow>
        </VCardText>

        <VCardText class="d-flex justify-end flex-wrap gap-3">
          <VBtn
            variant="tonal"
            color="secondary"
            @click="closeAddUsersTask"
          >
            Close
          </VBtn>
          <VBtn
            type="submit"
            @click="refForm?.validate()"
          >
            Save
          </VBtn>
        </VCardText>
      </VForm>
    </VCard>
  </VDialog>

  <!-- 👉 Expired Task -->
  <VDialog
    v-model="expiredTaskDialog"
    persistent
    class="v-dialog-xl"
  >
    <!-- Dialog close btn -->
    <DialogCloseBtn v-if="userPermessi.responsabile" @click="expiredTaskDialog = !expiredTaskDialog" />

    <VCard :title="$t('Label.Commento-Task-Scaduto')">
      <VForm
        ref="refForm"
        @submit.prevent="storeExpiredTask"
      >
        <VCardText>
          <VRow>
            <VCardText class="text-warning mb-0">
              <h3 class="text-warning mb-0">Inserire un commetto per gestire il task.</h3>
            </VCardText>
            <VCol
              cols="12"
              sm="12"
              md="12"
            />
            <VCol
              cols="12"
              md="12"
            >
              <AppTextField
                v-model="notaScadenza"
                :rules="[requiredValidator]"
                :label="$t('Label.Commento-Task-Scaduto')"
                :placeholder="$t('Label.Commento-Task-Scaduto')"
              />
            </VCol>
          </VRow>
        </VCardText>

        <VCardText class="d-flex justify-end flex-wrap gap-3">
          <VBtn
            color="error"
            @click="close"
          >
            Esci
          </VBtn>
          <VBtn
            type="submit"
            @click="refForm?.validate()"
          >
            Save
          </VBtn>
        </VCardText>
      </VForm>
    </VCard>
  </VDialog>

  <VSnackbar
    v-model="isAlert"
    location="center"
    color="warning"
  >
    Attenzione Ci Sono Sub Task Ancora in Lavorazinoe.
  </VSnackbar>

  <!-- 👉 View Task -->
  <TaskDettaglioView
    v-model:isDrawerOpen="dettaglioView"
    :task-data="dettaglio"
    :users-data="users"
    @task-data ="closeDetails"
  />

  <LoadingStandBy v-model="loadingPage" />
</template>

<style lang="scss">
.dialog-bottom-transition-enter-active,
.dialog-bottom-transition-leave-active {
  transition: transform 0.2s ease-in-out;
}

.v-list-item.v-list-item--active:not(.v-list-group__header) {
  background-color: #00ff91 !important;
  color: #000000 !important;
}

.demo-avatar-group {
  &.v-avatar-group {
    display: inline;

    .v-avatar {
      &:last-child {
        border: none;

      }
    }
  }
}
</style>
