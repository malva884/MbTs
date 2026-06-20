<script setup lang="ts">
import { VForm } from 'vuetify/components/VForm'
import TaskView from '@/views/task/TaskView.vue'
import type { Task } from '@/views/task/type'
import type { Area } from '@/views/task/aree/type'
import {useI18n} from "vue-i18n";
import {CustomInputContent} from "@core/types";

interface Emit {
  (e: 'update:isDialogVisible', value: boolean): void
  (e: 'update:taskData', value: object): void
}

interface Props {
  isDialogVisible: boolean
  taskData: Task
  areaData: Area
  responsabileData: boolean
}

const props = defineProps<Props>()
const emit = defineEmits<Emit>()
const {t} = useI18n()
const refForm = ref<VForm>()
const loading = ref(false)
const loadingPage = ref(false)
const usersOptions = ref<any>([])

const typeTask: CustomInputContent[] = [
  {
    title: t('Label.Task-Standard'),
    subtitle: '',
    desc: t('Label.Task-Standard'),
    value: '1',
    color: 'error'
  },
  {
    title: t('Label.Task-Cross'),
    subtitle: '',
    value: '2',
    desc: t('Label.Task-Cross'),
  },
]

const tipoTask = ref('1')

const getUsers = async () => {
  const { data: usersData } = await useApi<any>(createUrl(`/task/aree/get_users/${props.taskData.area_id}`, {
    query: {
      only: true,
    },
  }))

  usersOptions.value = usersData.value
}



const storedTask = async () => {
  refForm.value?.validate().then(async ({ valid }) => {
    if (valid) {
      loadingPage.value = true
      await $api('/task/store', {
        method: 'POST',
        body: props.taskData,
      })

      nextTick(() => {
        refForm.value?.reset()
        refForm.value?.resetValidation()
      })
      props.taskData.data_scadenza = ''
      props.taskData.created_at = ''
      props.taskData = false
      loadingPage.value = false
      emit('update:isDialogVisible', false)
    }
  })
}
const close = () => {
  emit('update:isDialogVisible', false)
}

watch(props, () => {
  getUsers()
})
</script>

<template>
  <VDialog
    :model-value="props.isDialogVisible"
    persistent
    max-width="680px"
    class="v-dialog-elegant"
    @update:model-value="close"
  >
    <DialogCloseBtn size="small" class="elegant-close-btn" @click="close" />

    <VCard class="elegant-card overflow-hidden">

      <div class="elegant-header d-flex align-center justify-space-between px-4 py-2.5 border-b">
        <div class="d-flex align-center gap-2">
          <VIcon icon="tabler-clipboard-plus" size="18" class="text-secondary" />
          <span class="text-subtitle-2 font-weight-bold text-high-emphasis">Nuovo task</span>

          <VChip
            :color="props.areaData.colore"
            variant="tonal"
            size="x-small"
            class="font-weight-bold text-uppercase px-2 rounded-sm"
          >
            {{ props.areaData.area }}
          </VChip>
        </div>
      </div>

      <VForm
        ref="refForm"
        @submit.prevent="storedTask"
      >
        <VCardText class="px-4 py-3">
          <VRow class="match-height g-2">

            <!--VCol
              cols="12"
              class="py-1"
            >
              <span class="text-xs font-weight-semibold text-disabled d-block mb-1">TIPO DI TASK</span>
              <CustomRadios
                v-model:selected-radio="tipoTask"
                :radio-content="typeTask"
                :grid-column="{ sm: '6', cols: '12' }"
                class="compact-radios"
              />
            </VCol -->

            <VCol
              cols="12"
              sm="6"
              class="py-0.5"
            >
              <AppTextField
                v-model="props.taskData.titolo"
                :label="$t('Label.Titolo')"
                :placeholder="$t('Label.Titolo')"
                :rules="[requiredValidator]"
                hide-details="auto"
                density="compact"
                variant="filled"
              />
            </VCol>

            <VCol
              cols="12"
              sm="6"
              class="py-0.5"
            >
              <AppTextField
                v-model="props.taskData.richiedente"
                :label="$t('Label.Richiesto-Da')"
                :placeholder="$t('Label.Richiesto-Da')"
                hide-details="auto"
                density="compact"
                variant="filled"
              />
            </VCol>

            <VCol
              cols="12"
              class="py-0.5"
            >
              <AppTextarea
                v-model="props.taskData.descrizione"
                :label="$t('Label.Descrizione')"
                :placeholder="$t('Label.Descrizione')"
                :rules="[requiredValidator]"
                rows="1"
                auto-grow
                hide-details="auto"
                density="compact"
                variant="filled"
              />
            </VCol>

            <VCol
              v-if="props.responsabileData"
              cols="12"
              sm="4"
              class="py-0.5"
            >
              <AppDateTimePicker
                v-model="props.taskData.created_at"
                :label="$t('Label.Data-Apertura')"
                :placeholder="$t('Label.Data-Apertura')"
                hide-details="auto"
                density="compact"
                variant="filled"
              />
            </VCol>

            <VCol
              cols="12"
              sm="6"
              :md="props.responsabileData ? 4 : 6"
              class="py-0.5"
            >
              <AppTextField
                v-model="props.taskData.giorni_dopo_scadenza"
                type="number"
                :label="$t('Label.Giorni-Dopo-La-Scadenza')"
                :placeholder="$t('Label.Giorni-Dopo-La-Scadenza')"
                hide-details="auto"
                density="compact"
                variant="filled"
              />
            </VCol>

            <VCol
              cols="12"
              sm="6"
              :md="props.responsabileData ? 4 : 6"
              class="py-0.5"
            >
              <AppDateTimePicker
                v-model="props.taskData.data_scadenza"
                :label="$t('Label.Data-Scadenza')"
                :placeholder="$t('Label.Data-Scadenza')"
                :rules="[requiredValidator]"
                hide-details="auto"
                density="compact"
                variant="filled"
              />
            </VCol>

            <VCol
              v-if="props.responsabileData"
              cols="12"
              md="4"
              class="py-0.5"
            >
              <AppSelect
                v-model="props.taskData.stato"
                :items="[{ value: '1', text: 'Aperto' }, { value: '5', text: 'In Svolgimento' }]"
                item-title="text"
                item-value="value"
                :label="$t('Label.Stato')"
                :placeholder="$t('Label.Stato')"
                :rules="[requiredValidator]"
                hide-details="auto"
                density="compact"
                variant="filled"
              />
            </VCol>

            <VCol
              cols="12"
              sm="6"
              :md="props.responsabileData ? 4 : 6"
              class="py-0.5"
            >
              <AppSelect
                v-model="props.taskData.priorieta"
                :items="[{ value: '1', text: 'Basso' }, { value: '2', text: 'Normale' }, { value: '3', text: 'Alto' }, { value: '4', text: 'Critico' }]"
                item-title="text"
                item-value="value"
                :label="$t('Label.Priorita')"
                :placeholder="$t('Label.Priorita')"
                :rules="[requiredValidator]"
                hide-details="auto"
                density="compact"
                variant="filled"
              />
            </VCol>

            <VCol
              v-if="props.responsabileData"
              cols="12"
              md="4"
              class="py-0.5"
            >
              <AppSelect
                v-model="props.taskData.users"
                :items="usersOptions"
                :item-title="item => item.full_name"
                :item-value="item => item.id"
                :label="$t('Label.Utenti-Task')"
                :placeholder="$t('Label..Utenti-Task')"
                chips
                multiple
                eager
                hide-details="auto"
                density="compact"
                variant="filled"
              />
            </VCol>
          </VRow>
        </VCardText>

        <div class="d-flex justify-end gap-2 px-4 py-2.5 border-t elegant-footer">
          <VBtn
            variant="text"
            color="secondary"
            density="comfortable"
            class="px-4 text-xs font-weight-bold"
            @click="isDialogVisible = false"
          >
            Close
          </VBtn>
          <VBtn
            type="submit"
            color="primary"
            density="comfortable"
            class="px-4 text-xs font-weight-bold elevation-0 rounded-sm"
            @click="refForm?.validate()"
          >
            Save
          </VBtn>
        </div>
      </VForm>
    </VCard>
  </VDialog>

  <LoadingStandBy v-model="loadingPage" />
</template>

<style scoped lang="scss">
.elegant-card {
  box-shadow: 0 10px 30px -10px rgba(0,0,0,0.15) !important;
  border: 1px solid rgba(var(--v-border-color), 0.05);
}

.elegant-close-btn {
  top: 0.65rem !important;
  right: 0.65rem !important;
  opacity: 0.6;
  transition: opacity 0.2s;
  &:hover { opacity: 1; }
}

.g-2 {
  row-gap: 10px !important;
  column-gap: 10px !important;
}

.py-0\.5 {
  padding-top: 2px !important;
  padding-bottom: 2px !important;
}

.border-b {
  border-bottom: 1px solid rgba(var(--v-border-color), 0.06) !important;
}

.border-t {
  border-top: 1px solid rgba(var(--v-border-color), 0.06) !important;
}

.elegant-footer {
  background-color: rgba(var(--v-theme-on-surface), 0.008);
}

.text-xs { font-size: 0.72rem !important; }

/* Micro-ottimizzazione font ed eleganza etichette vuetify */
:deep(.v-label) {
  font-size: 0.75rem !important;
  font-weight: 500 !important;
  letter-spacing: 0.2px;
  color: rgba(var(--v-theme-on-surface), 0.7);
  margin-bottom: 3px !important;
}

/* Rende i CustomRadios super compatti se generano box grandi */
.compact-radios :deep(.v-radio) {
  padding: 4px 8px !important;
}
</style>
