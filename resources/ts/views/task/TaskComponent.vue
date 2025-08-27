<script setup lang="ts">
import { VForm } from 'vuetify/components/VForm'
import TaskView from '@/views/task/TaskView.vue'
import type { Task } from '@/views/task/type'
import type { Area } from '@/views/task/aree/type'
import {useI18n} from "vue-i18n";

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
    v-model="props.isDialogVisible"
    persistent
    class="v-dialog-xl"
  >
    <!-- Dialog close btn -->
    <DialogCloseBtn @click="close" />

    <!-- Dialog Content -->
    <VCard title="Nuovo task">
      <VForm
        ref="refForm"
        @submit.prevent="storedTask"
      >
        <VCardText>
          <VRow>
            <VCol
              cols="12"
              sm="12"
              md="12"
            >
              <VAlert
                :color="props.areaData.colore"
                class="text-center"
              >
                Area: {{ props.areaData.area }}
              </VAlert>
            </VCol>
            <VCol
              cols="12"
              sm="6"
              md="6"
            >
              <AppTextField
                v-model="props.taskData.titolo"
                :label="$t('Label.Titolo')"
                :placeholder="$t('Label.Titolo')"
                :rules="[requiredValidator]"
              />
            </VCol>
            <VCol
              cols="12"
              sm="6"
              md="6"
            >
              <AppTextarea
                v-model="props.taskData.descrizione"
                :label="$t('Label.Descrizione')"
                :placeholder="$t('Label.Descrizione')"
                :rules="[requiredValidator]"
                rows="2"
              />
            </VCol>
            <VCol
              v-if="props.responsabileData"
              cols="12"
              sm="6"
              md="4"
            >
              <AppDateTimePicker
                v-model="props.taskData.created_at"
                :label="$t('Label.Data-Apertura')"
                :placeholder="$t('Label.Data-Apertura')"
              />
            </VCol>
            <VCol
              cols="12"
              sm="6"
              md="4"
            >
              <AppTextField
                v-model="props.taskData.giorni_dopo_scadenza"
                type="number"
                :label="$t('Label.Giorni-Dopo-La-Scadenza')"
                :placeholder="$t('Label.Giorni-Dopo-La-Scadenza')"
              />
            </VCol>
            <VCol
              cols="12"
              sm="6"
              md="4"
            >
              <AppDateTimePicker
                v-model="props.taskData.data_scadenza"
                :label="$t('Label.Data-Scadenza')"
                :placeholder="$t('Label.Data-Scadenza')"
                :rules="[requiredValidator]"
              />
            </VCol>
            <VCol
              v-if="props.responsabileData"
              cols="12"
              md="4"
            >
              <AppSelect
                v-model="props.taskData.stato"
                :items="[{ value: '1', text: 'Aperto' }, { value: '5', text: 'In Svolgimento' }]"
                item-title="text"
                item-value="value"
                :label="$t('Label.Stato')"
                :placeholder="$t('Label.Stato')"
                :rules="[requiredValidator]"
              />
            </VCol>
            <!-- 👉 Stato -->
            <VCol
              cols="12"
              md="4"
            >
              <AppSelect
                v-model="props.taskData.priorieta"
                :items="[{ value: '1', text: 'Basso' }, { value: '2', text: 'Normale' }, { value: '3', text: 'Alto' }, { value: '4', text: 'Critico' }]"
                item-title="text"
                item-value="value"
                :label="$t('Label.Priorita')"
                :placeholder="$t('Label.Priorita')"
                :rules="[requiredValidator]"
              />
            </VCol>
            <!-- 👉 Utenti Task -->
            <VCol
              v-if="props.responsabileData"
              cols="12"
              md="4"
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
              />
            </VCol>
          </VRow>
        </VCardText>

        <VCardText class="d-flex justify-end flex-wrap gap-3">
          <VBtn
            variant="tonal"
            color="secondary"
            @click="isDialogVisible = false"
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

  <LoadingStandBy v-model="loadingPage" />
</template>

<style scoped lang="scss">

</style>
