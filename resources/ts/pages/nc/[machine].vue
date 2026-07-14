<script setup lang="ts">
import { useI18n } from 'vue-i18n'

interface Defect {
  id: string
  difetto: string
  categoria?: string
}

interface Machine {
  id: string
  nome: string
  name_gp?: string
}

definePage({
  meta: {
    public: true,
    layout: 'blank',
  },
})

const route = useRoute()
const { t } = useI18n()
const machineParam = computed(() => route.params.machine as string)

const machine = ref<Machine | null>(null)
const defects = ref<Defect[]>([])
const loading = ref(true)
const saving = ref(false)
const snackbar = ref(false)
const message = ref('')
const color = ref('success')
const refForm = ref()

const form = ref({
  ol: '',
  bobina: '',
  materiale: '',
  stage: '',
  difetto: '',
  note: '',
  operator: '',
})

const machineData = ref({
  velocita_linea: null as number | null,
  metri_prodotti: null as number | null,
})

const stageLocked = ref(false)

const stageOptions = [
  { title: 'BUF', value: 'BUF' },
  { title: 'SZ', value: 'SZ' },
  { title: 'FC', value: 'FC' },
  { title: 'PE', value: 'PE' },
  { title: 'COL', value: 'COL' },
  { title: 'SF', value: 'SF' },
  { title: 'ARMA', value: 'ARMA' },
  { title: 'CORD', value: 'CORD' },
  { title: 'GUA', value: 'GUA' },
  { title: 'ISOL', value: 'ISOL' },
]

const loadMachine = async () => {
  loading.value = true
  try {
    const data = await $api(`/public/nc/machine/${encodeURIComponent(machineParam.value)}`)
    if (data.success)
      machine.value = data.data
    else
      showError('Macchina non trovata')
  }
  catch (err) {
    showError('Errore caricamento macchina')
  }
  finally {
    loading.value = false
  }
}

const updateStageFromMaterial = (materiale: string) => {
  if (!materiale) {
    stageLocked.value = false

    return
  }

  const iniziali = materiale.substring(0, 2).toUpperCase()

  if (['F5', 'F6', 'S4'].includes(iniziali)) {
    form.value.stage = ''
    stageLocked.value = false
  }
  else {
    stageLocked.value = true
    if (iniziali === 'BU')
      form.value.stage = 'BUF'
    else if (iniziali === 'CO')
      form.value.stage = 'COL'
    else
      form.value.stage = iniziali
  }
}

const loadMachineData = async () => {
  try {
    const data = await $api(`/public/nc/machine/${encodeURIComponent(machineParam.value)}/data`)
    if (data.success) {
      if (!form.value.ol)
        form.value.ol = data.data.ol || ''
      if (!form.value.materiale) {
        form.value.materiale = data.data.prodotto || ''
        updateStageFromMaterial(form.value.materiale)
      }
      if (!form.value.operator)
        form.value.operator = data.data.operatore || ''
      machineData.value.velocita_linea = data.data.velocita_linea ?? null
      machineData.value.metri_prodotti = data.data.metri_prodotti ?? null
    }
  }
  catch (err) {
    console.error('Errore caricamento dati 4.0:', err)
  }
}

const loadDefects = async () => {
  try {
    const data = await $api('/public/nc/defects')
    if (data.success)
      defects.value = data.data
  }
  catch (err) {
    showError('Errore caricamento difetti')
  }
}

const showError = (msg: string) => {
  message.value = msg
  color.value = 'error'
  snackbar.value = true
}

const showSuccess = (msg: string) => {
  message.value = msg
  color.value = 'success'
  snackbar.value = true
}

const submit = async () => {
  const { valid } = await refForm.value?.validate()
  if (!valid)
    return

  saving.value = true
  try {
    const data = await $api('/public/nc/store', {
      method: 'POST',
      body: {
        ...form.value,
        macchina: machine.value?.id ?? machineParam.value,
      },
    })

    if (data.success) {
      showSuccess(t(data.message) || 'Non conformità aperta')
      form.value = {
        ol: '',
        bobina: '',
        materiale: '',
        stage: '',
        difetto: '',
        note: '',
        operator: '',
      }
      refForm.value?.resetValidation()
    }
    else {
      showError(data.message || 'Errore salvataggio')
    }
  }
  catch (err) {
    showError('Errore salvataggio')
  }
  finally {
    saving.value = false
  }
}

let refreshInterval: ReturnType<typeof setInterval> | null = null

onMounted(async () => {
  await loadMachine()
  loadDefects()
  loadMachineData()
  refreshInterval = setInterval(loadMachineData, 300000)
})

onUnmounted(() => {
  if (refreshInterval)
    clearInterval(refreshInterval)
})
</script>

<template>
  <VContainer class="fill-height">
    <VRow
      align="center"
      justify="center"
    >
      <VCol
        cols="12"
        md="8"
        lg="6"
      >
        <VCard>
          <VCardTitle class="text-h5 py-4">
            {{ t('Non Conformità') }} - {{ machine?.nome || machineParam }}
            <VProgressCircular
              v-if="loading"
              indeterminate
              size="20"
              class="ms-2"
            />
          </VCardTitle>
          <VDivider />
          <VCardText>
            <VForm ref="refForm">
              <VRow>
                <VCol cols="12">
                  <VTextField
                    v-model="form.ol"
                    :label="t('Ordine di Lavoro')"
                    :rules="[v => !!v || t('Campo obbligatorio')]"
                    required
                  />
                </VCol>
                <VCol cols="12">
                  <VTextField
                    v-model="form.bobina"
                    :label="t('Bobina')"
                    :rules="[v => !!v || t('Campo obbligatorio')]"
                    required
                  />
                </VCol>
                <VCol cols="12">
                  <VTextField
                    v-model="form.materiale"
                    :label="t('Materiale')"
                    @update:model-value="updateStageFromMaterial"
                  />
                </VCol>
                <VCol cols="12">
                  <VSelect
                    v-model="form.stage"
                    :items="stageOptions"
                    :label="t('Stage')"
                    :readonly="stageLocked"
                    clearable
                  />
                </VCol>
                <VCol cols="12" md="6">
                  <VTextField
                    :model-value="machineData.velocita_linea"
                    :label="t('Velocità Linea')"
                    readonly
                  />
                </VCol>
                <VCol cols="12" md="6">
                  <VTextField
                    :model-value="machineData.metri_prodotti"
                    :label="t('Metri Prodotti')"
                    readonly
                  />
                </VCol>
                <VCol cols="12">
                  <VSelect
                    v-model="form.difetto"
                    :items="defects"
                    item-title="difetto"
                    item-value="id"
                    :label="t('Difetto')"
                    :rules="[v => !!v || t('Campo obbligatorio')]"
                    required
                  />
                </VCol>
                <VCol cols="12">
                  <VTextarea
                    v-model="form.note"
                    :label="t('Note')"
                    rows="3"
                    :rules="[v => !!v || t('Campo obbligatorio')]"
                    required
                  />
                </VCol>
                <VCol cols="12">
                  <VTextField
                    v-model="form.operator"
                    :label="t('Operatore')"
                  />
                </VCol>
              </VRow>
            </VForm>
          </VCardText>
          <VDivider />
          <VCardActions class="pa-4">
            <VSpacer />
            <VBtn
              color="primary"
              :loading="saving"
              @click="submit"
            >
              {{ t('Salva') }}
            </VBtn>
          </VCardActions>
        </VCard>
      </VCol>
    </VRow>
    <VSnackbar
      v-model="snackbar"
      :color="color"
      location="top"
    >
      {{ message }}
    </VSnackbar>
  </VContainer>
</template>
