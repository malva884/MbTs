<script setup lang="ts">
import { ref, watch, nextTick } from 'vue'
import type { VForm } from 'vuetify/components/VForm'
import moment from 'moment'
import type { Task } from '@/views/task/type'
import TaskNote from '@/views/task/view/TaskNote.vue'
import TaskAttivita from '@/views/task/view/TaskAttivita.vue'

interface Emit {
  (e: 'update:isDrawerOpen', value: boolean): void
  (e: 'taskData', value: Task): void
}

interface Props {
  isDrawerOpen: boolean
  taskData: Task
  usersData?: object
}

const props = defineProps<Props>()
const emit = defineEmits<Emit>()

const refForm = ref<InstanceType<typeof VForm> | null>(null)
const taskTab = ref('Aggiornamenti')
const users = ref<any[]>([])
const viewUsers = ref(false)
const stato = ref<string>('')
const userstask = ref<any[]>([])

const tabs = [
  { icon: 'tabler-message-circle', title: 'Aggiornamenti' },
  { icon: 'tabler-activity', title: 'Attività' },
]

const getUsers = async () => {
  if (!props.taskData?.area_id) return

  try {
    const { data: userTaskData } = await useApi<any>(createUrl(`/task/aree/get_users/${props.taskData.area_id}`, {
      query: { only: 'true' },
    }))

    users.value = userTaskData.value ?? []

    if (props.taskData.padre === null) {
      viewUsers.value = true
    }
  } catch (err) {
    console.error("Errore durante il caricamento degli utenti:", err)
  }
}

const resolveStato = (statoVal: string) => {
  if (statoVal === '1') return { text: 'Aperto', color: 'secondary', icon: 'tabler-circle' }
  if (statoVal === '2') return { text: 'Chiuso', color: 'success', icon: 'tabler-circle-check' }
  if (statoVal === '3') return { text: 'Da Approvare', color: 'warning', icon: 'tabler-circle-dot' }
  if (statoVal === '4') return { text: 'Sospeso', color: 'amber', icon: 'tabler-circle-minus' }
  if (statoVal === '5') return { text: 'In Svolgimento', color: 'primary', icon: 'tabler-circle-play' }
  return { text: '--', color: 'grey', icon: 'tabler-help' }
}

const resolvePriorieta = (proprieta: string) => {
  if (proprieta === '1') return { text: 'Basso', color: 'secondary' }
  if (proprieta === '2') return { text: 'Normale', color: 'info' }
  if (proprieta === '3') return { text: 'Alto', color: 'warning' }
  if (proprieta === '4') return { text: 'Critico', color: 'error' }
  return { text: '--', color: 'grey' }
}

const resolveavanzamento = (avanzamento: string) => {
  const av = Number(avanzamento)
  if (av === 100) return 'success'
  if (av <= 99 && av >= 75) return 'primary'
  if (av <= 75 && av >= 50) return 'warning'
  if (av <= 49 && av >= 1) return 'error'
  return 'secondary'
}

const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false)

  nextTick(() => {
    refForm.value?.reset()
    refForm.value?.resetValidation()
  })
}

const onSubmit = async () => {
  if ((props.taskData.padre && stato.value !== '3') || (stato.value !== '3' && userstask.value?.length)) {

    const updatedTask = {
      ...props.taskData,
      stato: stato.value
    }

    await $api(`/task/approvazione/${props.taskData.id}`, {
      method: 'POST',
      body: {
        users: userstask.value,
        task: updatedTask,
      },
    })

    emit('taskData', updatedTask)
    closeNavigationDrawer()
  }
}

function formatDate(date: string): string {
  if (!date) return '--'
  return moment(String(date)).format('DD/MM/YYYY')
}

const handleDrawerModelValueUpdate = (val: boolean) => {
  emit('update:isDrawerOpen', val)
}

watch(() => props.taskData, (newTask) => {
  if (newTask) {
    stato.value = newTask.stato
    getUsers()
  }
}, { immediate: true })
</script>

<template>
  <VDialog
    class="v-dialog-xxl"
    persistent
    :model-value="props.isDrawerOpen"
    @update:model-value="handleDrawerModelValueUpdate"
  >
    <DialogCloseBtn @click="closeNavigationDrawer" />

    <VCard class="pa-4 rounded-xl">
      <VCardItem class="border-bottom pb-4 mb-4">
        <div class="d-flex align-center justify-space-between flex-wrap gap-2">
          <div>
            <div class="d-flex align-center gap-2 mb-1">
              <VIcon icon="tabler-ticket" class="text-success" size="24" />
              <VCardTitle class="text-h4 font-weight-bold text-success pa-0">
                {{ props.taskData?.codice }}
              </VCardTitle>
            </div>
            <span class="text-subtitle-1 text-medium-emphasis font-weight-semibold">
              {{ props.taskData?.titolo }}
            </span>
          </div>

          <div class="d-flex align-center gap-2">
            <VChip
              :color="resolveStato(props.taskData?.stato).color"
              variant="tonal"
              class="font-weight-bold px-3"
              size="large"
            >
              <VIcon :icon="resolveStato(props.taskData?.stato).icon" start size="18" />
              {{ resolveStato(props.taskData?.stato).text }}
            </VChip>
          </div>
        </div>
      </VCardItem>

      <VForm ref="refForm" @submit.prevent="onSubmit">
        <VRow>
          <VCol cols="12" md="6" class="border-end-md">
            <VCard variant="flat" border class="mb-4 pa-4 bg-var-theme-background">
              <div class="d-flex align-center gap-2 mb-3">
                <VIcon icon="tabler-file-text" size="20" class="text-primary" />
                <h4 class="text-h6 font-weight-bold mb-0">Dettaglio Task</h4>
              </div>

              <VRow class="text-body-1">
                <VCol cols="4" class="text-medium-emphasis font-weight-medium py-1">Aperto da:</VCol>
                <VCol cols="8" class="font-weight-bold py-1">{{ props.taskData?.full_name || '--' }}</VCol>

                <VCol cols="12" class="mt-2">
                  <span class="text-medium-emphasis font-weight-medium d-block mb-1">Descrizione:</span>
                  <div class="pa-3 bg-surface rounded border text-body-2 html-description" v-html="props.taskData?.descrizione || '<i>Nessuna descrizione fornita.</i>'" />
                </VCol>
              </VRow>
            </VCard>

            <VCard variant="flat" border class="pa-4 bg-var-theme-background">
              <div class="d-flex align-center gap-2 mb-3">
                <VIcon icon="tabler-info-circle" size="20" class="text-primary" />
                <h4 class="text-h6 font-weight-bold mb-0">Informazioni Temporali</h4>
              </div>

              <VRow class="text-body-1">
                <VCol cols="6" class="py-2">
                  <div class="d-flex align-center gap-2 mb-1">
                    <VIcon icon="tabler-calendar-plus" size="16" class="text-disabled" />
                    <span class="text-xs text-medium-emphasis font-weight-medium">Data Apertura</span>
                  </div>
                  <span class="font-weight-bold ps-6">{{ formatDate(props.taskData?.created_at) }}</span>
                </VCol>

                <VCol cols="6" class="py-2">
                  <div class="d-flex align-center gap-2 mb-1">
                    <VIcon icon="tabler-calendar-time" size="16" class="text-warning" />
                    <span class="text-xs text-medium-emphasis font-weight-medium">Scadenza</span>
                  </div>
                  <span class="font-weight-bold text-warning ps-6">{{ props.taskData?.data_scadenza ? formatDate(props.taskData.data_scadenza) : '--' }}</span>
                </VCol>

                <VCol cols="6" class="py-2">
                  <div class="d-flex align-center gap-2 mb-1">
                    <VIcon icon="tabler-calendar-check" size="16" class="text-success" />
                    <span class="text-xs text-medium-emphasis font-weight-medium">Data Chiusura</span>
                  </div>
                  <span class="font-weight-bold text-success ps-6">{{ props.taskData?.data_chiusura ? formatDate(props.taskData.data_chiusura) : '--' }}</span>
                </VCol>

                <VCol cols="6" class="py-2">
                  <div class="d-flex align-center gap-2 mb-1">
                    <VIcon icon="tabler-alert-triangle" size="16" class="text-disabled" />
                    <span class="text-xs text-medium-emphasis font-weight-medium">Priorità</span>
                  </div>
                  <div class="ps-6 mt-1">
                    <VChip :color="resolvePriorieta(props.taskData?.priorieta).color" size="small" variant="elevated" class="font-weight-bold">
                      {{ resolvePriorieta(props.taskData?.priorieta).text }}
                    </VChip>
                  </div>
                </VCol>

                <VCol v-if="!props.taskData?.padre" cols="12" class="pt-4">
                  <div class="d-flex align-center justify-space-between mb-2">
                    <span class="text-body-2 font-weight-medium text-medium-emphasis">Avanzamento Globale</span>
                    <span class="text-body-2 font-weight-bold text-primary">{{ props.taskData?.completamento || 0 }}%</span>
                  </div>
                  <VProgressLinear
                    :model-value="props.taskData?.completamento"
                    height="12"
                    rounded
                    striped
                    :color="resolveavanzamento(props.taskData?.completamento)"
                    class="rounded-pill"
                  />
                </VCol>
              </VRow>
            </VCard>
          </VCol>

          <VCol cols="12" md="6" class="ps-md-4">
            <div v-if="props.taskData?.stato !== '3'">
              <VTabs v-model="taskTab" color="primary" grow class="v-tabs-pill border-bottom mb-4">
                <VTab v-for="tab in tabs" :key="tab.icon" :value="tab.title" class="font-weight-bold">
                  <VIcon :size="18" :icon="tab.icon" class="me-2" />
                  {{ tab.title }}
                </VTab>
              </VTabs>

              <VWindow v-model="taskTab" class="mt-2 disable-tab-transition" :touch="false">
                <VWindowItem value="Aggiornamenti">
                  <VCard variant="flat" border class="pa-2 tab-scrollable-container">
                    <TaskNote :task-id="props.taskData?.id" />
                  </VCard>
                </VWindowItem>

                <VWindowItem value="Attività">
                  <VCard variant="flat" border class="pa-2 tab-scrollable-container">
                    <TaskAttivita :task-id="props.taskData?.id" />
                  </VCard>
                </VWindowItem>
              </VWindow>
            </div>

            <div v-else class="h-full d-flex flex-column justify-center pa-4">
              <VCard variant="flat" border class="pa-4 border-warning bg-light-warning rounded-xl">
                <div class="d-flex align-center gap-2 mb-4">
                  <VIcon icon="tabler-shield-check" size="24" class="text-warning" />
                  <h3 class="text-h5 font-weight-bold mb-0 text-warning">Approvazione Richiesta</h3>
                </div>

                <p class="text-body-2 mb-4 text-medium-emphasis">
                  Questo task si trova in stato di approvazione. Seleziona il nuovo stato e assegna gli utenti responsabili per procedere.
                </p>

                <VRow>
                  <VCol cols="12">
                    <AppSelect
                      v-model="stato"
                      :items="[
                        { value: '1', text: 'Aperto' },
                        { value: '5', text: 'In Svolgimento' },
                        { value: '4', text: 'Sospeso' },
                        { value: '2', text: 'Chiuso' },
                        { value: '3', text: 'In Approvazione' }
                      ]"
                      item-title="text"
                      item-value="value"
                      :label="$t('Label.Stato')"
                      :placeholder="$t('Label.Stato')"
                      :rules="[requiredValidator]"
                      prepend-inner-icon="tabler-settings"
                    />
                  </VCol>

                  <VCol v-if="viewUsers" cols="12">
                    <AppAutocomplete
                      v-model="userstask"
                      :items="users"
                      :rules="[requiredValidator]"
                      item-title="full_name"
                      item-value="id"
                      :label="$t('Label.Utenti')"
                      :placeholder="$t('Label.Utenti')"
                      multiple
                      clearable
                      clear-icon="tabler-x"
                      prepend-inner-icon="tabler-users"
                    />
                  </VCol>

                  <VCol cols="12" class="mt-2">
                    <VBtn
                      color="success"
                      size="large"
                      type="submit"
                      block
                      prepend-icon="tabler-device-floppy"
                      class="font-weight-bold"
                    >
                      {{ $t('Button.Salva') }}
                    </VBtn>
                  </VCol>
                </VRow>
              </VCard>
            </div>
          </VCol>
        </VRow>
      </VForm>
    </VCard>
  </VDialog>
</template>

<style lang="scss">
.border-bottom {
  border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}
.border-end-md {
  @media (min-width: 960px) {
    border-inline-end: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  }
}
.html-description {
  min-height: 80px;
  max-height: 250px;
  overflow-y: auto;
  line-height: 1.6;
  p {
    margin-bottom: 0.5rem;
  }
}

/* Nuova classe per isolare lo scroll delle attività/note */
.tab-scrollable-container {
  height: 520px;       /* Altezza fissa o max-height a tua scelta */
  overflow-y: auto;    /* Attiva lo scroll verticale solo qui all'interno */
  overflow-x: hidden;  /* Evita fastidiosi scroll orizzontali */
}

.permission-table {
  td {
    border-block-end: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    padding-block: 0.5rem;

    .v-checkbox {
      min-inline-size: 4.75rem;
    }

    &:not(:first-child) {
      padding-inline: 0.5rem;
    }

    .v-label {
      white-space: nowrap;
    }
  }
}
</style>
