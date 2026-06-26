<script lang="ts" setup>
import { ref, computed, watch, nextTick } from 'vue'
import { VForm } from 'vuetify/components/VForm'
import type { Task } from '@/views/task/type'
import TaskDettaglioView from '@/views/task/TaskDettaglioView.vue'
import { useNotStore } from "@/views/task/view/useNoteStore"
import TaskAttivita from "@/views/task/view/TaskAttivita.vue"
import SubTaskDialog from '@/views/task/SubTaskDialog.vue'

interface Emit {
  (e: 'update:isDialogVisible', value: boolean): void
  (e: 'task-data', value: object): void
}

interface Props {
  isDialogVisible: boolean
  taskData: Task
}

const props = defineProps<Props>()
const emit = defineEmits<Emit>()

const path = import.meta.env.VITE_BASE_URL_PORTALE
const store = useNotStore()

const localIsDialogVisible = computed({
  get: () => props.isDialogVisible,
  set: (val) => emit('update:isDialogVisible', val)
})

const userPermessi = ref({
  responsabile: false,
  apriTask: false,
  chiudiTask: false,
  modificaTask: false,
  eliminaTask: false,
})

const loadingPage = ref(false)
const subTaskDialog = ref(false)
const expiredTaskDialog = ref(false)
const avanzamentoTaskDialog = ref(false)
const dettaglioView = ref(false)
const usersView = ref(false)
const fileDialog = ref(false)
const isAlert = ref(false)

const TaskItem = ref<Partial<Task>>({})
const refForm = ref<InstanceType<typeof VForm> | null>(null)
const subTaskList = ref<any[]>([])
const taskAttivitaRef = ref<{ refresh: () => Promise<void> } | null>(null)

// --- UTENTI ---
const userTaskList = ref<any[]>([])
const userTask = ref<any[]>([])
const users = ref<any[]>([])

const dettaglio = ref<Partial<Task>>({})
const notaAvanzamento = ref('')
const notaScadenza = ref('')
const nota = ref('')
const errors = ref<Record<string, string>>({ nota: '' })

const file = ref<File | null>(null)
const data = ref<any>(null)
const fileName = computed(() => file.value?.name ?? '')
const fileExtension = computed(() => fileName.value.includes('.') ? fileName.value.split('.').pop() : '')
const fileMimeType = computed(() => file.value?.type ?? '')
const dialogTaskId = ref<string | null>(null)

const avanzamento = ref(1)
const avanzamentoLabels = { 0: '0%', 25: '25%', 50: '50%', 75: '75%', 100: '100%' }

const defaultItem = ref<Partial<Task>>({
  id: null, area_id: null, padre: '', responsabile_id: '', utente_id: '', codice: '', stato: '',
  reparto_id: '', mansione_id: '', titolo: '', descrizione: '', data_chiusura: '', data_scadenza: '',
  data_scadenza_iniziale: '', giorni_dopo_scadenza: '', completamento: '', priorieta: '', numero: '',
  near_miss_id: '', path_drive: '', created_at: '', full_name: '', richiedente: '',
})

function new_defaultItem() {
  defaultItem.value = {
    id: null, area_id: null, padre: '', responsabile_id: '', utente_id: '', codice: '', stato: '',
    reparto_id: '', mansione_id: '', titolo: '', descrizione: '', data_chiusura: '', data_scadenza: '',
    data_scadenza_iniziale: '', giorni_dopo_scadenza: '', completamento: '', priorieta: '', numero: '',
    near_miss_id: '', path_drive: '', created_at: '', full_name: '', richiedente: '',
  }
}

const userLoad = async () => {
  try {
    const { data: userData } = await useApi<any>(createUrl(`/task/user/${props.taskData.area_id}`))
    if (userData.value) {
      userPermessi.value.responsabile = userData.value.responsabile === '1'
      userPermessi.value.apriTask = userData.value.aprire_task === '1'
      userPermessi.value.chiudiTask = userData.value.chiudere_task === '1'
      userPermessi.value.modificaTask = userData.value.modificare_task === '1'
      userPermessi.value.eliminaTask = userData.value.eliminare_task === '1'
    }
  } catch (err) {
    console.error(err)
  }
}

const subTaskLoad = async () => {
  const { data: subTaskData } = await useApi<any>(createUrl(`/task/sub_task_list/${props.taskData.id}`))
  subTaskList.value = subTaskData.value ?? []
}

const usersTaskLoad = async () => {
  const { data: usersTaskData } = await useApi<any>(createUrl(`/task/users_task_list/${props.taskData.id}`))
  userTaskList.value = usersTaskData.value ?? []
}

const resolveIcon = (stato: string) => {
  if (stato === '1') return { icon: 'tabler-circle', color: 'secondary', text: 'Da Fare' }
  if (stato === '2') return { icon: 'tabler-circle-check-filled', color: 'success', text: 'Chiuso' }
  if (stato === '3') return { icon: 'tabler-circle-x-filled', color: 'error', text: 'Bloccato' }
  if (stato === '4') return { icon: 'tabler-circle-minus-filled', color: 'warning', text: 'Sospeso' }
  if (stato === '5') return { icon: 'tabler-circle-dot-filled', color: 'primary', text: 'In Corso' }
  return { icon: 'tabler-circle', color: 'secondary', text: 'N/D' }
}

// CORRETTO: Usiamo direttamente i colori a contrasto pieno (HEX) così non dipendiamo dal motore di calcolo contrasti di Vuetify
const resolvePriorieta = (proprieta: string) => {
  if (proprieta === '1') return { text: 'Basso', color: 'secondary', themeColor: 'secondary', icon: 'tabler-arrow-down', textColor: '#FFFFFF' }
  if (proprieta === '2') return { text: 'Normale', color: 'info', themeColor: 'info', icon: 'tabler-arrow-right', textColor: '#FFFFFF' }
  if (proprieta === '3') return { text: 'Alto', color: 'warning', themeColor: 'warning', icon: 'tabler-arrow-up', textColor: '#0F0F0F' } // Nero pieno, super leggibile
  if (proprieta === '4') return { text: 'Critico', color: 'error', themeColor: 'error', icon: 'tabler-alert-triangle', textColor: '#FFFFFF' }
  return { text: '--', color: 'secondary', themeColor: 'secondary', icon: 'tabler-minus', textColor: '#FFFFFF' }
}

const newSubTask = () => {
  TaskItem.value = {
    priorieta: '1', area_id: props.taskData.area_id, responsabile_id: props.taskData.responsabile_id,
    padre: props.taskData.id, numero: props.taskData.numero, codice: props.taskData.codice,
    path_drive: props.taskData.path_drive, stato: '1'
  }
  subTaskDialog.value = true
}

const handleSubTaskUpdate = async (updatedSubTask: Task) => {
  if (TaskItem.value.padre === null && TaskItem.value.stato === '2' && subTaskList.value.length > 0) {
    const tmp = subTaskList.value.find(item => item.stato !== 2)
    if (tmp && tmp.id !== undefined) {
      isAlert.value = true
      return
    }
  }

  loadingPage.value = true

  const isCurrentTask = TaskItem.value.id === props.taskData.id

  const updatedTask = {
    ...props.taskData,
    stato: isCurrentTask ? (TaskItem.value.stato ?? props.taskData.stato) : props.taskData.stato,
    priorieta: isCurrentTask ? (TaskItem.value.priorieta ?? props.taskData.priorieta) : props.taskData.priorieta,
    titolo: isCurrentTask ? (TaskItem.value.titolo ?? props.taskData.titolo) : props.taskData.titolo,
    descrizione: isCurrentTask ? (TaskItem.value.descrizione ?? props.taskData.descrizione) : props.taskData.descrizione,
    richiedente: isCurrentTask ? (TaskItem.value.richiedente ?? props.taskData.richiedente) : props.taskData.richiedente,
    completamento: isCurrentTask ? (TaskItem.value.completamento ?? props.taskData.completamento) : props.taskData.completamento,
    data_scadenza: isCurrentTask ? (TaskItem.value.data_scadenza ?? props.taskData.data_scadenza) : props.taskData.data_scadenza,
  }

  if (isCurrentTask) {
    Object.assign(props.taskData, updatedTask)
  }

  emit('task-data', { ...updatedTask })

  await subTaskLoad()
  new_defaultItem()
  TaskItem.value = { ...defaultItem.value }
  loadingPage.value = false
}

const close = async () => {
  emit('task-data', props.taskData)
  emit('update:isDialogVisible', false)
}

const closeEdit = () => { TaskItem.value = {}; subTaskDialog.value = false }
const closeAvanzamento = () => { avanzamentoTaskDialog.value = false }
const editTask = (task: Task) => { TaskItem.value = { ...task }; subTaskDialog.value = true }
const apriDettaglio = (item: Task) => { dettaglio.value = { ...item }; dettaglioView.value = true }

const color = computed(() => {
  if (avanzamento.value === 0 || avanzamento.value === 25) return 'error'
  if (avanzamento.value === 50) return 'warning'
  if (avanzamento.value === 75) return 'primary'
  if (avanzamento.value === 100) return 'success'
  return 'error'
})

const editAvanzamento = () => {
  if (props.taskData.completamento != null) {
    avanzamento.value = Number(props.taskData.completamento)
  }
  avanzamentoTaskDialog.value = true
}

const resolveavanzamento = (avanzamentoVal: string) => {
  const av = Number(avanzamentoVal)
  if (av === 100) return 'success'
  if (av <= 99 && av >= 75) return 'primary'
  if (av <= 75 && av >= 50) return 'warning'
  if (av <= 49 && av >= 1) return 'error'
  return 'error'
}

const saveAvanzamento = async () => {
  if (notaAvanzamento.value && notaAvanzamento.value.trim() !== '') {
    await $api(`/task/${props.taskData.id}/avanzamento`, {
      method: 'POST',
      body: { avanzamento: avanzamento.value, nota: notaAvanzamento.value },
    })

    const updatedTask = {
      ...props.taskData,
      completamento: String(avanzamento.value),
      stato: avanzamento.value === 100 ? '2' : props.taskData.stato
    }

    Object.assign(props.taskData, updatedTask)
    emit('task-data', { ...updatedTask })

    avanzamentoTaskDialog.value = false
    notaAvanzamento.value = ''
    errors.value.nota = ''
  } else {
    errors.value.nota = 'Campo Obbligatorio'
  }
}

function openDrivePage(pathDrive: string) {
  if (!pathDrive) return
  window.open(`https://drive.google.com/drive/u/0/folders/${pathDrive}`, '_blank')
}

const usersDialog = async () => {
  await usersTaskLoad()
  const { data: userTaskArea } = await useApi<any>(createUrl(`/task/aree/get_users/${props.taskData.area_id}`, {
    query: { only: 'true' },
  }))
  users.value = userTaskArea.value ?? []
  usersView.value = true
}

const getUserTask = async () => {
  const userTmp: any[] = []
  const { data: userTaskArea } = await useApi<any>(createUrl(`/task/aree/get_users/${props.taskData.area_id}`, {
    query: { only: 'true' },
  }))
  const { data: userTaskData = { value: null } } = await useApi<any>(createUrl(`/task/users_task_list/${props.taskData.id}`, {
    query: { only: 'true' },
  }))

  if (userTaskData.value && userTaskArea.value) {
    userTaskData.value.forEach((value: any) => {
      const tmp = userTaskArea.value.find((item: any) => item.id === value)
      if (tmp) {
        userTmp.push({ id: tmp.id, name: tmp.full_name, avatar: tmp.avatar })
      }
    })
  }
  userTask.value = userTmp
}

const addUsersTask = async () => {
  loadingPage.value = true
  await $api(`/task/${props.taskData.id}/setUsers`, {
    method: 'POST',
    body: { users: userTaskList.value, area_id: props.taskData.area_id },
  })
  await getUserTask()
  usersView.value = false
  loadingPage.value = false
}

const closeAddUsersTask = () => { usersView.value = false }
const dialogFile = (taskId: string) => { dialogTaskId.value = taskId; fileDialog.value = true }
const closefileTask = () => { dialogTaskId.value = null; fileDialog.value = false }

const uploadFileTask = async () => {
  refForm.value?.validate().then(async ({ valid }) => {
    if (valid) {
      loadingPage.value = true
      await $api(`/task/uploadFile/${dialogTaskId.value}`, {
        method: 'POST',
        body: { file_upload: data.value, nota: nota.value },
      })
      data.value = null; nota.value = null; fileDialog.value = false; loadingPage.value = false
    }
  })
}

const closeDetails = (task: Task) => {
  subTaskLoad()
  const updatedTask = { ...props.taskData, stato: task.stato }
  emit('task-data', updatedTask)
  dettaglioView.value = false
}

const uploadFile = (event: any) => {
  const targetFile = event.target.files[0]
  if (!targetFile) return
  file.value = targetFile
  const reader = new FileReader()
  reader.readAsDataURL(targetFile)
  reader.onload = async () => {
    const encodedFile = (reader.result as string).split(',')[1]
    data.value = { file: encodedFile, fileName: fileName.value, fileExtension: fileExtension.value, fileMimeType: fileMimeType.value }
  }
}

const storeExpiredTask = () => {
  refForm.value?.validate().then(async ({ valid }) => {
    if (valid) {
      loadingPage.value = true
      const objTask = await $api(`/task/notaScadenza/${props.taskData.id}`, { method: 'POST', body: { nota: notaScadenza.value } })

      const o = objTask.obj
      /*const giorni = Number(props.taskData.giorni_dopo_scadenza)
      const newDate = new Date()
      newDate.setDate(newDate.getDate() + giorni)
      const newDateStr = o.data_scadenza*/

      const updatedTask = {
        ...props.taskData,
        data_scadenza: o.data_scadenza,
      }

      Object.assign(props.taskData, updatedTask)
      console.log(props.taskData)
      emit('task-data', { ...updatedTask })

      await taskAttivitaRef.value?.refresh()
      notaScadenza.value = ''
      expiredTaskDialog.value = false
      loadingPage.value = false
    }
  })
}

const eliminaTaskHandler = (taskId: string) => { console.log(taskId) }

watch(() => props.taskData, (newTaskData) => {
  if (!newTaskData || !props.isDialogVisible) return

  userLoad(); getUserTask(); subTaskLoad(); usersTaskLoad()

  if (newTaskData.stato === '2') {
    expiredTaskDialog.value = false
    return
  }

  const oggi = new Date()
  const scadenza = new Date(newTaskData.data_scadenza)

  if (oggi.getTime() > scadenza.getTime()) {
    expiredTaskDialog.value = true
  }
}, { immediate: true })

watch(() => props.isDialogVisible, (isVisible) => { if (!isVisible) { expiredTaskDialog.value = false } })
</script>

<template>
  <VDialog
    v-model="localIsDialogVisible"
    fullscreen
    transition="dialog-bottom-transition"
  >
    <VCard class="bg-background rounded-0 v-dialog-fullscreen-wrapper">

      <v-toolbar
        color="surface"
        elevation="1"
        class="flex-shrink-0"
        :style="`border-bottom: 4px solid rgb(var(--v-theme-${resolvePriorieta(props.taskData.priorieta).themeColor})) !important;`"
      >
        <div class="d-flex align-center justify-space-between w-100 px-4">
          <div class="d-flex align-center gap-2">
            <VChip size="small" color="primary" variant="flat">{{ props.taskData.codice }}</VChip>

            <div
              class="d-flex align-center gap-x-1 cursor-pointer hover-header-title"
              @click="apriDettaglio(props.taskData)"
            >
              <span class="text-h6 font-weight-bold">
                {{ props.taskData.titolo }}
              </span>
              <VIcon icon="tabler-info-circle" size="18" color="primary" class="mb-0.5" />

              <VTooltip activator="parent" location="bottom" start>
                Visualizza e modifica i dettagli completi del task
              </VTooltip>
            </div>
          </div>
          <div>
            <VBtn v-if="userPermessi.modificaTask" icon="tabler-edit" variant="text" color="secondary" class="mr-2" @click="editTask(props.taskData)" />
            <VBtn color="secondary" variant="flat" @click="close">Chiudi</VBtn>
          </div>
        </div>
      </v-toolbar>

      <div class="scrollable-content-area pa-4">
        <v-row class="ma-0 h-100 align-stretch">

          <v-col cols="12" md="8" class="pa-2 d-flex flex-column custom-column-height">

            <VCard v-if="props.taskData.descrizione" class="mb-4 flex-shrink-0" variant="outlined">
              <VCardItem class="py-2 bg-var-theme-background">
                <div class="text-caption font-weight-bold text-uppercase opacity-60">Descrizione dell'attività principale</div>
              </VCardItem>
              <VCardText class="pt-3 text-body-1 text-wrap" v-html="props.taskData.descrizione"></VCardText>
            </VCard>

            <VCard variant="flat" class="border flex-grow-1 d-flex flex-column overflow-hidden">
              <VCardItem class="border-b bg-surface flex-shrink-0">
                <div class="d-flex align-center justify-space-between flex-wrap gap-2 w-100">
                  <div class="d-flex align-center gap-2">
                    <VIcon icon="tabler-list-check" color="primary" />
                    <span class="font-weight-bold">Sotto-attività ({{ subTaskList.length }})</span>
                  </div>

                  <div class="d-flex align-center gap-2">
                    <VBtn v-if="props.taskData.path_drive" size="small" variant="tonal" color="success" @click="openDrivePage(props.taskData.path_drive)">
                      <VIcon icon="tabler-brand-google-drive" class="mr-1" /> Drive
                    </VBtn>
                    <VBtn size="small" variant="tonal" color="secondary" @click="dialogFile(props.taskData.id)">
                      <VIcon icon="tabler-paperclip" class="mr-1" /> Allegato
                    </VBtn>
                    <VBtn v-if="userPermessi.responsabile" size="small" variant="tonal" color="secondary" @click="usersDialog">
                      <VIcon icon="tabler-user-plus" class="mr-1" /> Gestisci Team
                    </VBtn>
                    <VBtn v-if="userPermessi.apriTask" size="small" color="primary" @click="newSubTask">
                      <VIcon icon="tabler-plus" class="mr-1" /> Nuova Attività
                    </VBtn>
                  </div>
                </div>
              </VCardItem>

              <VCardText class="pa-0 flex-grow-1 overflow-y-auto">
                <div v-if="subTaskList.length === 0" class="text-center py-8 text-disabled">
                  Nessuna sotto-attività presente.
                </div>
                <v-list v-else lines="two" class="py-0">
                  <div
                    v-for="item in subTaskList"
                    :key="item.id"
                    class="border-b"
                    :style="`border-left: 4px solid rgb(var(--v-theme-${resolveIcon(item.stato).color})) !important;`"
                  >
                    <v-list-item class="py-2">
                      <template #prepend>
                        <VIcon :icon="resolveIcon(item.stato).icon" :color="resolveIcon(item.stato).color" class="mr-2" />
                      </template>

                      <v-list-item-title
                        class="text-body-2 font-weight-medium text-wrap cursor-pointer dynamic-title-wrap"
                        @click="apriDettaglio(item)"
                      >
                        {{ item.titolo }}
                      </v-list-item-title>

                      <v-list-item-subtitle v-if="item.data_scadenza" class="mt-1">
                        <span class="text-caption text-disabled">Scadenza: {{ item.data_scadenza }}</span>
                      </v-list-item-subtitle>

                      <template #append>
                        <div class="d-flex align-center gap-3">
                          <VChip size="x-small" :color="resolvePriorieta(item.priorieta).themeColor" variant="tonal" class="text-uppercase font-weight-bold">
                            {{ resolvePriorieta(item.priorieta).text }}
                          </VChip>

                          <div class="d-flex align-center">
                            <VBtn icon="tabler-paperclip" size="small" variant="text" color="secondary" @click.stop="dialogFile(item.id)" />
                            <VBtn v-if="userPermessi.modificaTask" icon="tabler-edit" size="small" variant="text" color="secondary" @click.stop="editTask(item)" />
                            <VBtn v-if="userPermessi.eliminaTask" icon="tabler-trash" size="small" variant="text" color="error" @click.stop="eliminaTaskHandler(item.id)" />
                          </div>
                        </div>
                      </template>
                    </v-list-item>
                  </div>
                </v-list>
              </VCardText>
            </VCard>
          </v-col>

          <v-col cols="12" md="4" class="pa-2 d-flex flex-column custom-column-height">

            <VCard class="mb-3 border flex-shrink-0" variant="flat" @click="editAvanzamento">
              <VCardText class="d-flex align-center justify-space-between py-3">
                <div class="w-100 mr-4">
                  <div class="text-caption font-weight-bold text-uppercase opacity-60 mb-1">Avanzamento Globale</div>
                  <VProgressLinear
                    :model-value="Number(props.taskData.completamento)"
                    height="8"
                    :color="resolveavanzamento(props.taskData.completamento)"
                    rounded
                    striped
                    class="progress-fluid-transition"
                  />
                </div>
                <div class="text-right">
                  <span class="text-h5 font-weight-black">{{ Math.ceil(Number(props.taskData.completamento)) }}%</span>
                </div>
              </VCardText>
            </VCard>

            <VCard class="mb-3 border flex-shrink-0" variant="flat">
              <VCardItem class="border-b py-1 bg-surface">
                <div class="text-caption font-weight-bold text-uppercase opacity-70">Team Operativo Assegnato</div>
              </VCardItem>
              <VCardText class="pa-1">
                <v-list density="compact" bg-color="transparent" class="py-0">
                  <v-list-item v-for="user in userTask" :key="user.id" class="px-2 py-0" min-height="32">
                    <template #prepend>
                      <v-avatar size="24" class="mr-2 border">
                        <v-img v-if="user.avatar" :src="path + user.avatar" />
                        <VIcon v-else icon="tabler-user" size="14" />
                      </v-avatar>
                    </template>
                    <v-list-item-title class="text-caption font-weight-semibold">{{ user.name }}</v-list-item-title>
                  </v-list-item>
                  <div v-if="userTask.length === 0" class="text-caption text-disabled text-center py-2">
                    Nessun utente assegnato.
                  </div>
                </v-list>
              </VCardText>
            </VCard>

            <VCard class="mb-3 border flex-shrink-0" variant="flat">
              <VCardText class="pa-3">
                <VRow no-gutters class="align-center">

                  <VCol cols="5" class="border-e pr-3">
                    <div class="text-caption font-weight-bold text-uppercase opacity-50 mb-1">Stato Task</div>
                    <div class="d-flex align-center gap-2">
                      <VIcon
                        :icon="resolveIcon(props.taskData.stato).icon"
                        :color="resolveIcon(props.taskData.stato).color"
                        size="18"
                      />
                      <span class="text-body-2 font-weight-bold">
                        {{ resolveIcon(props.taskData.stato).text }}
                      </span>
                    </div>
                  </VCol>

                  <VCol cols="7" class="pl-3">
                    <div class="text-caption font-weight-bold text-uppercase opacity-50 mb-1">Livello Priorità</div>

                    <div
                      class="d-flex align-center justify-center gap-2 px-3 py-2 rounded font-weight-black text-uppercase tracking-wide priorita-badge-base"
                      :class="{
                        'pulse-critico-anim': props.taskData.priorieta === '4',
                        'elevation-3': props.taskData.priorieta === '4' || props.taskData.priorieta === '3'
                      }"
                      :style="{
                        backgroundColor: `rgb(var(--v-theme-${resolvePriorieta(props.taskData.priorieta).themeColor}))`,
                        color: `${resolvePriorieta(props.taskData.priorieta).textColor} !important`,
                        transform: (props.taskData.priorieta === '4' || props.taskData.priorieta === '3') ? 'scale(1.05)' : 'scale(1)'
                      }"
                    >
                      <VIcon
                        :icon="resolvePriorieta(props.taskData.priorieta).icon"
                        size="20"
                        :style="`color: ${resolvePriorieta(props.taskData.priorieta).textColor} !important; font-weight: 900;`"
                      />
                      <span
                        class="text-body-1 font-weight-black"
                        :style="`color: ${resolvePriorieta(props.taskData.priorieta).textColor} !important;`"
                      >
                        {{ resolvePriorieta(props.taskData.priorieta).text }}
                      </span>
                    </div>
                  </VCol>

                </VRow>
              </VCardText>
            </VCard>

            <VCard class="border flex-grow-1 d-flex flex-column overflow-hidden" variant="flat">
              <VCardItem class="border-b py-2 bg-surface flex-shrink-0">
                <div class="d-flex align-center gap-2">
                  <VIcon icon="tabler-history" size="18" color="secondary" />
                  <span class="text-caption font-weight-bold text-uppercase opacity-70">Registro delle Attività</span>
                </div>
              </VCardItem>

              <VCardText class="pa-2 flex-grow-1 overflow-y-auto style-scrollbar registro-dinamico-scroll">
                <TaskAttivita ref="taskAttivitaRef" :task-id="props.taskData.id" />
              </VCardText>
            </VCard>

          </v-col>
        </v-row>
      </div>

    </VCard>
  </VDialog>

  <VDialog v-model="usersView" persistent class="v-dialog-xl">
    <VCard class="bg-background rounded-sm overflow-hidden">

      <v-toolbar color="surface" elevation="1" class="flex-shrink-0">
        <div class="d-flex align-center justify-space-between w-100 px-4">
          <div class="d-flex align-center gap-2">
            <VIcon icon="tabler-user-plus" color="primary" />
            <span class="text-h6 font-weight-bold">
              {{ $t('Label.Aggiungi-Utente') }}
            </span>
          </div>
          <DialogCloseBtn @click="closeAddUsersTask" class="position-static ma-0" />
        </div>
      </v-toolbar>

      <VForm ref="refForm" @submit.prevent="addUsersTask" class="d-flex flex-column">
        <VCardText class="pa-4 bg-background">
          <VRow>
            <VCol cols="12">
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
                variant="outlined"
              >
                <template #selection="{ item }">
                  <VChip size="small" class="ma-1" color="primary" variant="tonal">
                    <span>{{ item.title }}</span>
                  </VChip>
                </template>
              </AppSelect>
            </VCol>
          </VRow>
        </VCardText>

        <VCardText class="d-flex justify-end flex-wrap gap-3 border-t bg-surface py-3 px-4">
          <VBtn variant="tonal" color="secondary" @click="closeAddUsersTask">Chiudi</VBtn>
          <VBtn type="submit" color="primary">Salva</VBtn>
        </VCardText>
      </VForm>

    </VCard>
  </VDialog>

  <SubTaskDialog
    v-model:isDialogVisible="subTaskDialog"
    :subTaskData="TaskItem"
    :userPermessi="userPermessi"
    @subTaskData="handleSubTaskUpdate"
  />

  <VDialog v-model="avanzamentoTaskDialog" persistent class="v-dialog-xl">
    <VCard class="bg-background rounded-sm overflow-hidden">

      <v-toolbar
        color="surface"
        elevation="1"
        class="flex-shrink-0"
        :style="`border-bottom: 4px solid rgb(var(--v-theme-${color})) !important;`"
      >
        <div class="d-flex align-center justify-space-between w-100 px-4">
          <div class="d-flex align-center gap-2">
            <VIcon icon="tabler-adjustments-horizontal" :color="color" />
            <span class="text-h6 font-weight-bold">
              {{ $t('Label.Avanzamento-Task') }}
            </span>
          </div>
          <DialogCloseBtn @click="closeAvanzamento" class="position-static ma-0" />
        </div>
      </v-toolbar>

      <div class="d-flex flex-column">
        <VCardText class="pa-6 bg-background">
          <div class="mb-8 px-2">
            <div class="text-caption font-weight-bold text-uppercase opacity-60 mb-4">
              Seleziona la percentuale di completamento
            </div>
            <VSlider
              v-model="avanzamento"
              :ticks="avanzamentoLabels"
              :color="color"
              :max="100"
              step="25"
              show-ticks="always"
              tick-size="4"
              hide-details
            />
          </div>

          <VRow>
            <VCol cols="12">
              <AppTextField
                v-model="notaAvanzamento"
                :label="`${$t('Label.Nota')} *`"
                :error-messages="errors.nota"
                placeholder="Descrivi brevemente l'aggiornamento..."
                autocomplete="off"
              />
            </VCol>
          </VRow>
        </VCardText>

        <VCardText class="d-flex justify-end flex-wrap gap-3 border-t bg-surface py-3 px-4">
          <VBtn variant="tonal" color="secondary" @click="closeAvanzamento">
            Annulla
          </VBtn>
          <VBtn color="success" @click="saveAvanzamento">
            {{ $t('Label.Salva') }}
          </VBtn>
        </VCardText>
      </div>

    </VCard>
  </VDialog>

  <VDialog v-model="fileDialog" persistent class="v-dialog-xl">
    <VCard class="bg-background rounded-sm overflow-hidden">

      <v-toolbar color="surface" elevation="1" class="flex-shrink-0">
        <div class="d-flex align-center justify-space-between w-100 px-4">
          <div class="d-flex align-center gap-2">
            <VIcon icon="tabler-paperclip" color="primary" />
            <span class="text-h6 font-weight-bold">
              {{ $t('Label.Upload-File') }}
            </span>
          </div>
          <DialogCloseBtn @click="closefileTask" class="position-static ma-0" />
        </div>
      </v-toolbar>

      <VForm ref="refForm" @submit.prevent="uploadFileTask" class="d-flex flex-column">
        <VCardText class="pa-4 bg-background">
          <VRow>
            <VCol cols="12">
              <VFileInput
                accept="image/*,application/pdf,application/vnd.ms-excel"
                label="Seleziona file"
                :rules="[requiredValidator]"
                variant="outlined"
                density="comfortable"
                prepend-icon=""
                prepend-inner-icon="tabler-file-upload"
                @change="uploadFile"
              />
            </VCol>

            <VCol cols="12">
              <AppTextField
                v-model="nota"
                :label="`${$t('Label.Nota')} *`"
                :rules="[requiredValidator]"
                placeholder="Inserisci una descrizione o una nota per l'allegato..."
                autocomplete="off"
              />
            </VCol>
          </VRow>
        </VCardText>

        <VCardText class="d-flex justify-end flex-wrap gap-3 border-t bg-surface py-3 px-4">
          <VBtn variant="tonal" color="secondary" @click="closefileTask">Annulla</VBtn>
          <VBtn type="submit" color="primary">Salva</VBtn>
        </VCardText>
      </VForm>

    </VCard>
  </VDialog>

  <VDialog v-model="expiredTaskDialog" persistent class="v-dialog-xl">
    <VCard class="bg-background rounded-sm overflow-hidden">

      <v-toolbar
        color="surface"
        elevation="1"
        class="flex-shrink-0"
        style="border-bottom: 4px solid rgb(var(--v-theme-error)) !important;"
      >
        <div class="d-flex align-center justify-space-between w-100 px-4">
          <div class="d-flex align-center gap-2">
            <VIcon icon="tabler-clock-exclamation" color="error" />
            <span class="text-h6 font-weight-bold text-error">
              {{ $t('Label.Commento-Task-Scaduto') }}
            </span>
          </div>
          <DialogCloseBtn
            v-if="userPermessi.responsabile"
            @click="expiredTaskDialog = !expiredTaskDialog"
            class="position-static ma-0"
          />
        </div>
      </v-toolbar>

      <VForm ref="refForm" @submit.prevent="storeExpiredTask" class="d-flex flex-column">
        <VCardText class="pa-6 bg-background">

          <div class="d-flex align-start gap-4 mb-6 pa-4 rounded border-error border-opacity-25 bg-error-lighten-5" style="border: 1px solid rgba(var(--v-theme-error), 0.3);">
            <VIcon icon="tabler-alert-triangle" color="error" size="28" class="mt-1" />
            <div>
              <h4 class="text-h6 font-weight-bold text-error mb-1">Attenzione: Tempo Scaduto</h4>
              <p class="text-body-2 mb-0 opacity-90">
                L'attività ha superato la data di scadenza prevista. Per garantire la tracciabilità del processo, è <strong>obbligatorio</strong> inserire una nota che spieghi il motivo del ritardo.
              </p>
            </div>
          </div>

          <VRow>
            <VCol cols="12">
              <AppTextField
                v-model="notaScadenza"
                :rules="[requiredValidator]"
                :label="$t('Label.Commento-Task-Scaduto')"
                placeholder="Scrivi qui il motivo del ritardo..."
                variant="outlined"
                persistent-placeholder
                autocomplete="off"
              />
            </VCol>
          </VRow>
        </VCardText>

        <VCardText class="d-flex justify-end flex-wrap gap-3 border-t bg-surface py-3 px-4">
          <VBtn color="error" variant="tonal" @click="close">
            Esci
          </VBtn>
          <VBtn type="submit" color="primary">
            Salva Nota
          </VBtn>
        </VCardText>
      </VForm>

    </VCard>
  </VDialog>

  <VSnackbar v-model="isAlert" location="center" color="warning" class="font-weight-bold">
    Attenzione: ci sono ancora Sotto-task aperti!
  </VSnackbar>

  <TaskDettaglioView v-model:isDrawerOpen="dettaglioView" :task-data="dettaglio" :users-data="users" @task-data="closeDetails" />
  <LoadingStandBy v-model="loadingPage" />
</template>

<style lang="scss">
.hover-header-title {
  transition: all 0.2s ease-in-out;
  padding: 4px 8px;
  border-radius: 6px;

  &:hover {
    background-color: rgba(var(--v-theme-primary), 0.08);
    span { color: rgb(var(--v-theme-primary)); }
  }
}

.scrollable-content-area {
  height: calc(100vh - 64px);
  overflow: hidden !important;
}

.custom-column-height {
  height: 100% !important;
  max-height: 100% !important;
  overflow: hidden !important;
}

.registro-dinamico-scroll {
  height: 0 !important;
}

@keyframes pulse-danger {
  0% { box-shadow: 0 0 0 0 rgba(var(--v-theme-error), 0.7); }
  70% { box-shadow: 0 0 0 10px rgba(var(--v-theme-error), 0); }
  100% { box-shadow: 0 0 0 0 rgba(var(--v-theme-error), 0); }
}

.pulse-critico-anim {
  animation: pulse-danger 2s infinite !important;
  border: 2px solid #ffffff !important;
}

.date-picker-z-index-fix {
  z-index: 99999 !important;
}

.priorita-badge-base {
  transition: all 0.3s ease-in-out;
}
</style>
