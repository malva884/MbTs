<script setup lang="ts">
import { VForm } from 'vuetify/components/VForm'
import type { Task } from '@/views/task/type'

interface Emit {
  (e: 'update:isDialogVisible', value: boolean): void
  (e: 'subTaskData', value: Task): void
}

interface Props {
  isDialogVisible: boolean
  subTaskData: Task
  userPermessi: any
}

const props = defineProps<Props>()
const emit = defineEmits<Emit>()
const refForm = ref<VForm>()
const loadingPage = ref(false)

const storedSubTask = () => {
  refForm.value?.validate().then(async ({ valid }) => {
    if (!valid) return

    loadingPage.value = true
    let urlPath = '/task/store_sub_task'
    if (props.subTaskData.id) {
      urlPath = `/task/update_sub_task/${props.subTaskData.id}`
    }

    await $api(urlPath, { method: 'POST', body: props.subTaskData })

    nextTick(() => {
      refForm.value?.reset()
      refForm.value?.resetValidation()
    })

    emit('subTaskData', { ...props.subTaskData })
    emit('update:isDialogVisible', false)
    loadingPage.value = false
  })
}

const close = () => {
  emit('update:isDialogVisible', false)
}
</script>

<template>
  <VDialog
    v-model="props.isDialogVisible"
    persistent
    class="v-dialog-xl"
  >
    <VCard class="bg-background rounded-sm overflow-hidden">
      <v-toolbar
        color="surface"
        elevation="1"
        class="flex-shrink-0"
      >
        <div class="d-flex align-center justify-space-between w-100 px-4">
          <div class="d-flex align-center gap-2">
            <VIcon icon="tabler-file-plus" size="20" />
            <span class="text-subtitle-1 font-weight-bold">{{ props.subTaskData.id ? 'Modifica Sub Task' : 'Nuovo Sub Task' }}</span>
          </div>
          <VBtn icon variant="text" @click="close">
            <VIcon icon="tabler-x" />
          </VBtn>
        </div>
      </v-toolbar>

      <VForm ref="refForm" @submit.prevent="storedSubTask">
        <VCardText class="pa-4">
          <VRow>
            <VCol cols="12" sm="6" md="6">
              <AppTextField v-model="props.subTaskData.titolo" :label="$t('Label.Titolo')" :rules="[requiredValidator]" :readonly="!userPermessi.modificaTask" />
            </VCol>

            <VCol cols="12" md="4" lg="6">
              <AppSelect
                v-model="props.subTaskData.priorieta"
                :items="[{ value: '1', text: 'Basso' }, { value: '2', text: 'Normale' }, { value: '3', text: 'Alto' }, { value: '4', text: 'Critico' }]"
                item-title="text"
                item-value="value"
                :label="$t('Label.Priorita')"
                :rules="[requiredValidator]"
                :readonly="!userPermessi.modificaTask"
              />
            </VCol>

            <VCol cols="12" sm="6" md="6">
              <AppTextField v-model="props.subTaskData.richiedente" :label="$t('Label.Richiesto-Da')" :readonly="!userPermessi.modificaTask" />
            </VCol>

            <VCol cols="12" sm="6" md="6">
              <AppDateTimePicker v-model="props.subTaskData.data_scadenza" :label="$t('Label.Data-Scadenza')" :rules="[requiredValidator]" :readonly="!userPermessi.modificaTask" :config="{ inline: true }" />
            </VCol>

            <VCol cols="12">
              <TiptapEditor v-model="props.subTaskData.descrizione" :label="$t('Label.Descrizione')" :class="'border rounded basic-editor ' + (!userPermessi.modificaTask ? 'v-rating--readonly' : '')" :rules="[requiredValidator]" />
            </VCol>

            <VCol v-if="props.subTaskData.id" cols="12" md="6">
              <AppSelect
                v-model="props.subTaskData.stato"
                :items="[{ value: '1', text: 'Aperto' }, { value: '5', text: 'In Svolgimento' }, { value: '4', text: 'Sospeso' }, { value: '2', text: 'Chiuso' }]"
                item-title="text"
                item-value="value"
                :label="$t('Label.Stato')"
                :rules="[requiredValidator]"
                :readonly="!userPermessi.chiudiTask"
              />
            </VCol>
          </VRow>
        </VCardText>

        <VCardText class="d-flex justify-end flex-wrap gap-3 border-t bg-surface py-3 px-4">
          <VBtn variant="tonal" color="secondary" @click="close">Chiudi</VBtn>
          <VBtn type="submit" color="primary">Salva</VBtn>
        </VCardText>
      </VForm>
    </VCard>
  </VDialog>

  <LoadingStandBy v-model="loadingPage" />
</template>
