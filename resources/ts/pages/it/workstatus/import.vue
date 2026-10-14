<script setup lang="ts">
import { VForm } from 'vuetify/components/VForm'
import moment from 'moment/moment'

definePage({
  meta: {
    action: 'create',
    subject: 'It-Work-Progress',
  },
})

const router = useRouter()

const refForm = ref<VForm>()
const isFormValid = ref(false)

const machineDates = ref<{ title: string, value: string }[]>([])
const materialDates = ref<{ title: string, value: string }[]>([])
const dataMacchinari = ref<string>('')
const dataMatariali = ref<string>('')
const file = ref<File | null>(null)

const isDialogLoading = ref(false)
const progressMessage = ref('Caricamento file...')
const progressPercentage = ref(0)
const isSnackbarVisible = ref(false)
const message = ref('')
const color = ref('')

let pollTimer: ReturnType<typeof setInterval> | null = null

const toOption = (d: any) => {
  const value = String(d.date_import ?? d).substring(0, 10)

  return { title: moment(value).format('DD/MM/YYYY'), value }
}

const loadDates = async () => {
  const { data } = await useApi<any>('/ws/cost/dates')

  if (data.value) {
    machineDates.value = (data.value.machines || []).map(toOption)
    materialDates.value = (data.value.materials || []).map(toOption)
  }
}

const uploadFile = (event: any) => {
  file.value = event.target.files?.[0] ?? null
}

const pollProgress = (headId: string) => {
  pollTimer = setInterval(async () => {
    const data = await $api(`/ws/import/progress/${headId}`)

    if (data.progress) {
      progressMessage.value = data.progress.message || 'Importazione in corso...'
      progressPercentage.value = data.progress.percentage || 0
    }

    if (data.progress?.status === 'done' || data.head_status === 1) {
      if (pollTimer) clearInterval(pollTimer)
      isDialogLoading.value = false
      message.value = 'Importazione completata'
      color.value = 'success'
      isSnackbarVisible.value = true
      router.push({ name: 'it-workstatus' })
    }
    else if (data.progress?.status === 'error' || data.head_status === 3) {
      if (pollTimer) clearInterval(pollTimer)
      isDialogLoading.value = false
      message.value = data.import_error || data.progress?.message || 'Errore durante l\'importazione'
      color.value = 'error'
      isSnackbarVisible.value = true
    }
  }, 3000)
}

const save = async () => {
  if (!dataMacchinari.value || !dataMatariali.value || !file.value)
    return

  isDialogLoading.value = true
  progressMessage.value = 'Caricamento file...'
  progressPercentage.value = 0

  try {
    const formData = new FormData()

    formData.append('data_macchinari', dataMacchinari.value)
    formData.append('data_matariali', dataMatariali.value)
    formData.append('file', file.value)

    const retuenData = await $api('/ws/head/load', {
      method: 'POST',
      body: formData,
    })

    if (retuenData.head_id)
      pollProgress(retuenData.head_id)
    else
      isDialogLoading.value = false
  }
  catch (e: any) {
    isDialogLoading.value = false
    message.value = e?.data?.message || 'Errore caricamento file'
    color.value = 'error'
    isSnackbarVisible.value = true
  }
}

onUnmounted(() => {
  if (pollTimer) clearInterval(pollTimer)
})

loadDates()
</script>

<template>
  <div class="workspace-container w-100 d-flex flex-column pa-4 gap-3">
    <VSnackbar v-model="isSnackbarVisible" transition="scroll-y-reverse-transition" location="top center" :timeout="4000">
      {{ message }}
    </VSnackbar>

    <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
      <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
        <div class="d-flex align-center gap-2">
          <VIcon icon="tabler-upload" size="24" color="primary" />
          <div>
            <div class="text-h6 font-weight-medium">Nuovo Work Progress</div>
            <div class="text-caption text-medium-emphasis">Importa il file Stato Lavori selezionando i listini di riferimento</div>
          </div>
        </div>
        <VBtn
          prepend-icon="tabler-arrow-left"
          variant="outlined"
          color="secondary"
          density="comfortable"
          :to="{ name: 'it-workstatus' }"
        >
          Torna alla lista
        </VBtn>
      </VCardText>
      <VDivider />

      <VCardText class="pa-6">
        <VForm ref="refForm" v-model="isFormValid" @submit.prevent="save">
          <VRow>
            <VCol cols="12" md="4">
              <AppSelect
                v-model="dataMacchinari"
                :items="machineDates"
                label="Costo Macchinari e Manodopera del"
                placeholder="Seleziona data listino"
                :rules="[requiredValidator]"
                clearable
              />
            </VCol>
            <VCol cols="12" md="4">
              <AppSelect
                v-model="dataMatariali"
                :items="materialDates"
                label="Costo Materiali del"
                placeholder="Seleziona data listino"
                :rules="[requiredValidator]"
                clearable
              />
            </VCol>
            <VCol cols="12" md="4">
              <VFileInput
                accept=".xlsx,.xls,.csv"
                label="File Stato Lavori"
                :rules="[requiredValidator]"
                @change="uploadFile"
              />
            </VCol>
          </VRow>

          <VRow>
            <VCol cols="12" class="d-flex align-center justify-space-between">
              <RouterLink :to="{ name: 'it-workstatus-costs' }" class="text-link">
                Importa / aggiorna i listini costi
              </RouterLink>
              <VBtn
                type="submit"
                color="primary"
                prepend-icon="tabler-upload"
                :disabled="!dataMacchinari || !dataMatariali || !file"
              >
                Import
              </VBtn>
            </VCol>
          </VRow>
        </VForm>
      </VCardText>
    </VCard>
  </div>

  <!-- Dialog progress import -->
  <VDialog v-model="isDialogLoading" width="400" persistent>
    <VCard color="primary" width="400">
      <VCardText class="pt-3">
        <span class="ml-4 mb-3">{{ progressMessage }}</span>
        <VProgressLinear
          :model-value="progressPercentage"
          :indeterminate="progressPercentage === 0"
          :size="40"
          color="warning"
          class="mt-3"
        />
      </VCardText>
    </VCard>
  </VDialog>
</template>
