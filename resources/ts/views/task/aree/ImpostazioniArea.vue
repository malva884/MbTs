<script setup lang="ts">
import { useI18n } from 'vue-i18n'

interface Props {
  areaId: string
  responsabile: boolean
}

const props = defineProps<Props>()
const isSnackbarScrollReverseVisible = ref(false)
const message = ref('')
const color = ref('')
const selectedCheckbox = ref([])
const isLoading = ref(false)
const view = ref(false)

const loadItem = async () => {
  const { data: taskData } = await useApi<any>(createUrl(`/task/aree/view/${props.areaId}`))

  const tmp = []
  if (taskData.value.approvazione_task === '1')
    tmp.push('new-task')
  if (taskData.value.approvazione_sub_task === '1')
    tmp.push('new-sub-task')
  if (taskData.value.notifiche === '1')
    tmp.push('notifiche')
  if (taskData.value.tipologia === '1')
    tmp.push('Privato')
  if (taskData.value.nascosta === '1')
    tmp.push('Disattiva')

  selectedCheckbox.value = tmp
  view.value = true
}

loadItem()

const checkboxContent: any[] = [
  {
    title: 'Approvazione Nuovo Task',
    subtitle: '',
    desc: 'Un nuovo task deve essere approvato da un responsabile prima di ....',
    value: 'new-task',
  },
  {
    title: 'Approvazione Nuovo Sub Task',
    subtitle: '',
    desc: 'Un nuovo Sub Task deve essere approvato da un responsabile prima di ....',
    value: 'new-sub-task',
  },
  {
    title: 'Notifiche Email Attive',
    subtitle: '',
    desc: 'Notifica Tutti Gli Utenti Associati Al Task Gli Aggiornamenti Con Una Email.',
    value: 'notifiche',
  },
  {
    title: 'Area Privata',
    subtitle: '',
    desc: 'E Area Personale.',
    value: 'Privato',
  },
  {
    title: 'Area Disattivata',
    subtitle: '',
    desc: 'Nascondi L\'area Di Lavoro.',
    value: 'Disattiva',
  },
]

const save = async () => {
  isLoading.value = true

  const retuenData = await $api(`/task/impostazioni/update/${props.areaId}`, {
    method: 'POST',
    body: {
      setting: selectedCheckbox.value,
    },
  })

  message.value = retuenData.message
  color.value = retuenData.color
  isSnackbarScrollReverseVisible.value = true

  isLoading.value = false
}

watch(props, () => {
  loadItem()
})
</script>

<template>
  <VCardText class="d-flex flex-wrap py-4 gap-4 ">
    <VSnackbar
      v-model="isSnackbarScrollReverseVisible"
      transition="scroll-y-reverse-transition"
      location="top central"
      :color="color"
    >
      {{ $t(message) }}
    </VSnackbar>
  </VCardText>
  <VRow>
    <VCol cols="12">
      <VCard>
        <CustomCheckboxes
          v-if="view"
          v-model:selected-checkbox="selectedCheckbox"
          :checkbox-content="checkboxContent"
          :grid-column="{ sm: '12', cols: '12' }"
        />
      </VCard>
    </VCol>
  </VRow>
  <VRow>
    <VCol
      cols="12"
      sm="12"
    >
      <VBtn
        block
        @click="save"
      >
        {{ $t('Button.Save') }}
      </VBtn>
    </VCol>
  </VRow>
</template>

<style scoped lang="scss">

</style>
