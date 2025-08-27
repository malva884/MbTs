<script setup lang="ts">
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
  usersData: object
}

const props = defineProps<Props>()
const emit = defineEmits<Emit>()
const isFormValid = ref(false)
const refForm = ref<VForm>()
const taskTab = ref(null)
const users = ref({})
const viewUsers = ref(false)
const stato = ref()
const userstask = ref()

const tabs = [
  { icon: 'tabler-message-circle', title: 'Aggiornamenti' },
  { icon: 'tabler-activity', title: 'Attività' },
]

const getUsers = async () => {
  const { data: userTaskData } = await useApi<any>(createUrl(`/task/aree/get_users/${props.taskData.area_id}`, {
    query: {
      only: 'true',
    },
  }))

  users.value = userTaskData.value

  if (props.taskData.padre === null)
    viewUsers.value = true
}

const resolveStato = (stato: string) => {
  if (stato === '1')
    return { text: 'Aperto', color: 'secondary' }
  else if (stato === '2')
    return { text: 'Chiuso', color: 'success' }
  else if (stato === '3')
    return { text: 'Da Approvare', color: 'warning' }
  else if (stato === '4')
    return { text: 'Sospeso', color: 'warning' }
  else if (stato === '5')
    return { text: 'In Svolgimento', color: 'primary' }
  else
    return { text: '--', color: 'bianco' }
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

// 👉 drawer close
const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false)

  nextTick(() => {
    refForm.value?.reset()
    refForm.value?.resetValidation()
  })
}

const onSubmit = async () => {
  if ((props.taskData.padre && stato !== 3 ) || (stato !== 3 && userstask.value)) {
    // eslint-disable-next-line vue/no-mutating-props
    props.taskData.stato = stato.value
    await $api(`/task/approvazione/${props.taskData.id}`, {
      method: 'POST',
      body: {
        users: userstask.value,
        task: props.taskData,
      },
    })
    emit('taskData', props.taskData)
  }
}

function formatDate(date: string): string {
  return moment(String(date)).format('YYYY -MM-DD')
}

const handleDrawerModelValueUpdate = (val: boolean) => {
  emit('update:isDrawerOpen', val)
}

watch(props, () => {
  stato.value = props.taskData.stato
  getUsers()
})
</script>

<template>
  <VDialog
    class="v-dialog-xxl"
    persistent=""
    :model-value="props.isDrawerOpen"
    @update:model-value="handleDrawerModelValueUpdate"
  >
    <!-- 👉 Dialog close btn -->
    <DialogCloseBtn @click="closeNavigationDrawer" />

    <VCard class="pa-sm-1 pa-1">
      <!-- 👉 Title -->
      <VCardItem class="text-center ">
        <VCardTitle class="text-h5 text-success mb-1">
          {{ props.taskData.codice }}
        </VCardTitle>
      </VCardItem>

      <VRow>
        <VCol cols="6">
          <VCardText class="mt-1">
            <p class="text-xs">
              Dettaglio
            </p>

            <VList class="card-list text-medium-emphasis">
              <VListItem>
                <VListItemTitle>
                  <span class="font-weight-medium me-1">Riferimento:</span>
                  <span class="font-weight-bold me-1">{{ props.taskData.codice }}</span>
                </VListItemTitle>
              </VListItem>
              <VListItem>
                <VListItemTitle>
                  <span class="font-weight-medium me-1">Aperto da:</span>
                  <span class="font-weight-bold me-1">{{ props.taskData.full_name }}</span>
                </VListItemTitle>
              </VListItem>
              <VListItem>
                <VListItemTitle>
                  <span class="font-weight-medium me-1">Titolo:</span>
                  <span class="font-weight-bold me-1">{{ props.taskData.titolo }}</span>
                </VListItemTitle>
              </VListItem>
              <VListItem>
                <VListItemTitle>
                  <span class="font-weight-medium me-1">Descrizione:</span>
                  <span class="font-weight-bold me-1" v-html="props.taskData.descrizione" />
                </VListItemTitle>
              </VListItem>
            </VList>
          </VCardText>

          <VCardText class="mt-1">
            <p class="text-xs">
              Info
            </p>

            <VList class="card-list text-medium-emphasis">
              <VListItem>
                <VListItemTitle>
                  <span class="font-weight-medium me-1">Stato:</span>
                  <span>
                    <VChip
                      :color="resolveStato(props.taskData.stato).color"
                      variant="elevated"
                    >
                      {{ resolveStato(props.taskData.stato).text }}
                    </VChip>
                  </span>
                </VListItemTitle>
              </VListItem>
              <VListItem>
                <VListItemTitle>
                  <span class="font-weight-medium me-1">Priorità:</span>
                  <span>
                    <VChip
                      :color="resolvePriorieta(props.taskData.priorieta).color"
                      variant="elevated"
                    >
                      {{ resolvePriorieta(props.taskData.priorieta).text }}
                    </VChip>
                  </span>
                </VListItemTitle>
              </VListItem>
              <VListItem>
                <VListItemTitle>
                  <span class="font-weight-medium me-1">Aperto:</span>
                  <span class="font-weight-bold me-1">{{ formatDate(props.taskData.created_at) }}</span>
                </VListItemTitle>
              </VListItem>
              <VListItem>
                <VListItemTitle>
                  <span class="font-weight-medium me-1">Scadenza:</span>
                  <span class="font-weight-bold me-1">{{ props.taskData?.data_scadenza ? formatDate(props.taskData.data_scadenza) : '' }}</span>
                </VListItemTitle>
              </VListItem>
              <VListItem>
                <VListItemTitle>
                  <span class="font-weight-medium me-1">Chiuso:</span>
                  <span class="font-weight-bold me-1">{{ props.taskData?.data_chiusura ? formatDate(props.taskData.data_chiusura) : '' }}</span>
                </VListItemTitle>
              </VListItem>
              <VListItem v-if="!props.taskData.padre">
                <VListItemTitle>
                  <span class="font-weight-medium me-1">Completamento:</span>
                  <span>
                    <VProgressLinear
                      v-model="props.taskData.completamento"
                      height="20"
                      :color="resolveavanzamento(props.taskData.completamento)"
                    >
                      <span>{{ props.taskData.completamento }}%</span>
                    </VProgressLinear>
                  </span>
                </VListItemTitle>
              </VListItem>
            </VList>
          </VCardText>
        </VCol>
        <VCol
          v-if="props.taskData.stato !== '3'"
          cols="6"
          class="mb-0"
        >
          <VTabs v-model="taskTab" class="mb-0">
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
              <TaskNote :task-id="props.taskData.id" />
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
        </VCol>
        <VCol
          v-else
          cols="6"
        >
          <VRow>
            <VCol
              v-if="props.taskData.id"
              cols="12"
              md="12"
            >
              <AppSelect
                v-model="stato"
                :items="[{ value: '1', text: 'Aperto' }, { value: '5', text: 'In Svolgimento' }, { value: '4', text: 'Sospeso' }, { value: '2', text: 'Chiuso' }, { value: '3', text: 'In Approvazione' }]"
                item-title="text"
                item-value="value"
                :label="$t('Label.Stato')"
                :placeholder="$t('Label.Stato')"
                :rules="[requiredValidator]"
              />
            </VCol>
            <VCol
              v-if="viewUsers"
              cols="12"
              md="12"
            >
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
              >
              </AppAutocomplete>
            </VCol>
            <VCardText class="d-flex justify-end flex-wrap gap-3">
              <VRow>
                <VCol
                  cols="12"
                  sm="12"
                >
                  <VBtn
                    color="success"
                    size="large"
                    @click="onSubmit"
                    block
                  >
                    {{ $t('Button.Salva') }}
                  </VBtn>
                </VCol>
              </VRow>
            </VCardText>
          </VRow>
        </VCol>
      </VRow>
    </VCard>
  </VDialog>
</template>

<style lang="scss">
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
