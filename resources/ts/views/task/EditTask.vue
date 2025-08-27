<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import type { Task } from '@/views/task/type'
import type { Area } from '@/views/task/aree/type'

interface Emit {
  (e: 'update:isDialogVisible', value: boolean): void
  (e: 'taskData', value: Task): void
}

interface Props {
  taskData: Task
  areaData: Area
  usersOptions: object
  repartiOptions?: object
  mansioniOptions?: object
  isDialogVisible: boolean
}

const props = withDefaults(defineProps<Props>(), {
  taskData: () => ({
    id: undefined,
    area_id: null,
    padre: null,
    responsabile_id: '',
    utente_id: '',
    codice: null,
    stato: '',
    reparto_id: '',
    mansione_id: '',
    titolo: '',
    descrizione: '',
    data_chiusura: '',
    data_scadenza: '',
    data_scadenza_iniziale: null,
    giorni_dopo_scadenza: 15,
    completamento: '',
    priorieta: '',
    numero: null,
    near_miss_id: null,
    path_drive: '',
    created_at: '',
  }),
})

const emit = defineEmits<Emit>()
const { t } = useI18n()
const taskData = ref<Task>(structuredClone(toRaw(props.taskData)))
const areaData = ref<Area>(structuredClone(toRaw(props.areaData)))

watch(props, () => {})
</script>
<template>
  <VDialog
    v-model="props.isDialogVisible"
    persistent
    class="v-dialog-xl"
  >
    <!-- Dialog close btn -->
    <DialogCloseBtn @click="props.isDialogVisible = !props.isDialogVisible" />

    <!-- Dialog Content -->
    <VCard title="Nuovo task">
      <VCardText>
        <VRow>
          <VCol
            cols="12"
            sm="12"
            md="12"
          >
            <VAlert color="success" class="text-center">
              Area: {{areaData.area}}
            </VAlert>
          </VCol>
          <VCol
            cols="12"
            sm="6"
            md="6"
          >
            <AppTextField
              v-model="taskData.titolo"
              :label="$t('Label.Titolo')"
              :placeholder="$t('Label.Titolo')"
            />
          </VCol>
          <VCol
            cols="12"
            sm="6"
            md="6"
          >
            <AppTextField
              v-model="taskData.descrizione"
              :label="$t('Label.Descrizione')"
              :placeholder="$t('Label.Descrizione')"
            />
          </VCol>
          <VCol
            cols="12"
            sm="6"
            md="4"
          >
            <AppDateTimePicker
              v-model="taskData.created_at"
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
              v-model="taskData.giorni_dopo_scadenza"
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
              v-model="taskData.data_scadenza"
              :label="$t('Label.Data-Scadenza')"
              :placeholder="$t('Label.Data-Scadenza')"
            />
          </VCol>
          <VCol
            cols="12"
            md="4"
          >
            <AppSelect
              v-model="taskData.stato"
              :items="[{ value: '1', text: 'Aperto' }, { value: '2', text: 'Chiuso' }, { value: '4', text: 'Sospeso' }, { value: '5', text: 'In Svolgimento' }]"
              item-title="text"
              item-value="value"
              :label="$t('Label.Stato')"
              :placeholder="$t('Label.Stato')"
            />
          </VCol>
          <!-- 👉 Stato -->
          <VCol
            cols="12"
            md="4"
          >
            <AppSelect
              v-model="taskData.priorieta"
              :items="[{ value: '1', text: 'Basso' }, { value: '2', text: 'Normale' }, { value: '3', text: 'Alto' }, { value: '4', text: 'Critico' }]"
              item-title="text"
              item-value="value"
              :label="$t('Label.Priorita')"
              :placeholder="$t('Label.Priorita')"
            />
          </VCol>
          <!-- 👉 Utenti Task -->
          <VCol
            cols="12"
            md="4"
          >
            <AppSelect
              v-model="taskData.utente_id"
              :items="props.usersOptions"
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
        <VBtn @click="isDialogVisible = false">
          Save
        </VBtn>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<style lang="scss">

</style>
