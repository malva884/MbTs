<script setup lang="ts">
import { useI18n } from 'vue-i18n'

definePage({
  meta: {
    action: 'create',
    subject: 'Ehs-Eventi',
  },
})

const { t } = useI18n()
const router = useRouter()

const loading = ref(false)
const isSnackbarVisible = ref(false)
const message = ref('')
const color = ref('')
const currentStep = ref(0)

// Dati form
const form = ref({
  tipo_scheda: 1,
  tipo_rilevazione: 1,
  matricola: '',
  nome: '',
  cognome: '',
  qualifica: null as number | null,
  data_evento: '',
  ora_lavorativa: '',
  testimoni: '',
  desc_dinamica: '',
  causa_id: null as string | null,
  azioni: '',
  reparto_id: null as string | null,
  sede_id: null as string | null,
  lesione_id: null as string | null,
  s_anatomica_id: null as string | null,
  tipo_evento_id: null as string | null,
  giorni_infortunio: null as number | null,
  analisi_causa: '',
  azioni_contenimento: '',
  responsabile_azione: '',
  data_chiusura: '',
})

const docFile = ref<File | null>(null)

// Dati di supporto
const { data: formData } = await useApi<any>('/ehs/events/form-data')

const tipoSchedaItems = [
  { title: t('Ehs.Infortunio'), value: 1 },
  { title: t('Ehs.Evento-Ambientale'), value: 2 },
]

const tipoRilevazioneItems = computed(() => {
  if (form.value.tipo_scheda === 1) {
    return [
      { title: t('Ehs.Infortunio'), value: 1 },
      { title: t('Ehs.Near-Miss-Inf'), value: 2 },
      { title: t('Ehs.First-Aid'), value: 5 },
    ]
  }

  return [
    { title: t('Ehs.Danno-Ambientale'), value: 3 },
    { title: t('Ehs.Near-Miss-Amb'), value: 4 },
  ]
})

// First AID: nasconde testimoni, analisi, azioni e allegato (come nel vecchio gestionale)
const isFirstAid = computed(() => form.value.tipo_rilevazione === 5)

const qualificaItems = [
  { title: t('Ehs.Operaio'), value: 1 },
  { title: t('Ehs.Impiegato'), value: 2 },
  { title: t('Ehs.Altro'), value: 3 },
]

const oraLavorativaItems = [
  { title: '1°', value: '1°', color: '#4CAF50' },
  { title: '2°', value: '2°', color: '#8BC34A' },
  { title: '3°', value: '3°', color: '#CDDC39' },
  { title: '4°', value: '4°', color: '#FFEB3B' },
  { title: '5°', value: '5°', color: '#FFC107' },
  { title: '6°', value: '6°', color: '#FF9800' },
  { title: '7°', value: '7°', color: '#FF5722' },
  { title: '8°', value: '8°', color: '#F44336' },
  { title: 'Extra time', value: 'Extra time', color: '#B71C1C' },
]

const tipoSchedaLabel = computed(() =>
  form.value.tipo_scheda === 1 ? t('Ehs.Infortunio') : t('Ehs.Evento-Ambientale'))

const dipendenteLabel = computed(() =>
  [form.value.cognome, form.value.nome].filter(Boolean).join(' ') || form.value.matricola || undefined)

const dataEventoLabel = computed(() =>
  form.value.data_evento ? new Date(form.value.data_evento).toLocaleString('it-IT') : undefined)

const steps = computed(() => {
  const base = [
    { title: t('Ehs.Tipo-Scheda'), icon: 'tabler-category', subtitle: tipoSchedaLabel.value },
    { title: t('Ehs.Dati-Dipendente'), icon: 'tabler-user', subtitle: dipendenteLabel.value },
    { title: t('Ehs.Dati-Evento'), icon: 'tabler-calendar-event', subtitle: dataEventoLabel.value },
  ]

  if (!isFirstAid.value) {
    base.push(
      { title: t('Ehs.Analisi-E-Azioni'), icon: 'tabler-analyze' },
      { title: t('Ehs.Allegato'), icon: 'tabler-paperclip', subtitle: docFile.value?.name },
    )
  }

  return base
})

// Se passa a First AID mentre è oltre lo step 3, torna indietro
watch(isFirstAid, val => {
  if (val && currentStep.value > 2)
    currentStep.value = 2
})

const isStepValid = computed(() => {
  if (currentStep.value === 0)
    return !!form.value.tipo_scheda && !!form.value.tipo_rilevazione && !!form.value.qualifica
  if (currentStep.value === 1)
    return !!form.value.matricola && !!form.value.nome && !!form.value.cognome
  if (currentStep.value === 2) {
    if (!form.value.data_evento || !form.value.reparto_id || !form.value.sede_id || !form.value.causa_id)
      return false
    if (form.value.tipo_scheda === 1)
      return !!form.value.lesione_id && !!form.value.s_anatomica_id

    return !!form.value.tipo_evento_id
  }

  return true
})

const nextStep = () => {
  if (currentStep.value < steps.value.length - 1)
    currentStep.value++
}

const prevStep = () => {
  if (currentStep.value > 0)
    currentStep.value--
}

// Quando cambia tipo_scheda, resetta i campi specifici
watch(() => form.value.tipo_scheda, val => {
  if (val === 1) {
    form.value.tipo_rilevazione = 1
    form.value.tipo_evento_id = null
  }
  else {
    form.value.tipo_rilevazione = 3
    form.value.lesione_id = null
    form.value.s_anatomica_id = null
    form.value.giorni_infortunio = null
  }
})

// Autocomplete dipendente per matricola
const employeeSearch = ref('')
const selectedEmployeeId = ref<string | null>(null)
const employeeItems = ref<any[]>([])
const searchingEmployee = ref(false)

const searchEmployees = async (val: string) => {
  if (!val || val.length < 2) {
    employeeItems.value = []

    return
  }
  searchingEmployee.value = true

  const { data } = await useApi<any>(createUrl('/hr/dipendenti/list', {
    query: { dipendente: val, itemsPerPage: 10 },
  }))

  if (data.value)
    employeeItems.value = data.value.data || []
  searchingEmployee.value = false
}

watch(employeeSearch, searchEmployees)

const onEmployeeSelected = (id: string | null) => {
  const emp = employeeItems.value.find(e => e.id === id)
  if (emp) {
    form.value.matricola = emp.matricola
    form.value.nome = emp.nome
    form.value.cognome = emp.cognome
  }
}

// Autocomplete su focusout della matricola (come nel vecchio gestionale)
const onMatricolaBlur = async () => {
  if (!form.value.matricola)
    return

  const { data } = await useApi<any>(createUrl('/hr/dipendenti/list', {
    query: { matricola: form.value.matricola, itemsPerPage: 5 },
  }))

  const emp = data.value?.data?.find((e: any) => e.matricola === form.value.matricola)
  if (emp) {
    form.value.nome = emp.nome
    form.value.cognome = emp.cognome
  }
  else {
    form.value.nome = ''
    form.value.cognome = ''
    message.value = 'Ehs.Dipendente-Non-Trovato'
    color.value = 'warning'
    isSnackbarVisible.value = true
  }
}

// Quick-add inline per le tabelle di supporto (pulsante "+" accanto alle select)
const quickAddDialog = ref(false)
const quickAddType = ref('')
const quickAddNome = ref('')
const quickAddSaving = ref(false)

const quickAddConfig: Record<string, { listKey: string; field: string; label: string; endpoint?: string; bodyKey?: string; refetchUrl?: string; itemKey?: string }> = {
  reparti: { listKey: 'reparti', field: 'reparto_id', label: 'Label.Reparto', endpoint: '/hr/gestione/reparti/store', bodyKey: 'reparto', refetchUrl: '/hr/reparti/getList', itemKey: 'reparto' },
  sedi: { listKey: 'sedi', field: 'sede_id', label: 'Ehs.Impianto' },
  lesioni: { listKey: 'lesioni', field: 'lesione_id', label: 'Ehs.Tipo-Lesione' },
  anatomiche: { listKey: 's_anatomiche', field: 's_anatomica_id', label: 'Ehs.Sede-Anatomica' },
  eventi: { listKey: 't_eventi', field: 'tipo_evento_id', label: 'Ehs.Tipo-Evento' },
  cause: { listKey: 'cause', field: 'causa_id', label: 'Ehs.Dinamica' },
}

const quickAddLabel = computed(() => {
  if (quickAddType.value === 'reparti')
    return form.value.tipo_scheda === 1 ? t('Ehs.Mansione') : t('Label.Reparto')

  const cfg = quickAddConfig[quickAddType.value]

  return cfg ? t(cfg.label) : ''
})

const openQuickAdd = (type: string) => {
  quickAddType.value = type
  quickAddNome.value = ''
  quickAddDialog.value = true
}

const saveQuickAdd = async () => {
  if (!quickAddNome.value)
    return
  quickAddSaving.value = true
  try {
    const cfg = quickAddConfig[quickAddType.value]

    const response = await $api(cfg.endpoint || `/ehs/lookup/${quickAddType.value}/store`, {
      method: 'POST',
      body: { [cfg.bodyKey || 'nome']: quickAddNome.value },
    })

    if (response.success) {
      if (response.obj) {
        if (formData.value?.[cfg.listKey])
          formData.value[cfg.listKey].push(response.obj)
        ;(form.value as any)[cfg.field] = response.obj.id
      }
      else if (cfg.refetchUrl) {
        // Endpoint che non ritorna l'oggetto: ricarica la lista e seleziona per nome
        const { data } = await useApi<any>(cfg.refetchUrl)

        if (data.value && formData.value) {
          formData.value[cfg.listKey] = data.value

          const found = data.value.find((i: any) =>
            String(i[cfg.itemKey || 'nome']).toLowerCase() === quickAddNome.value.toLowerCase())

          if (found)
            (form.value as any)[cfg.field] = found.id
        }
      }
    }

    message.value = response.message
    color.value = response.color || 'success'
    isSnackbarVisible.value = true
    quickAddDialog.value = false
  }
  catch (error: any) {
    message.value = error.message || 'Errore'
    color.value = 'error'
    isSnackbarVisible.value = true
  }
  finally {
    quickAddSaving.value = false
  }
}

const submit = async () => {
  loading.value = true
  try {
    const formDataObj = new FormData()

    Object.entries(form.value).forEach(([key, val]) => {
      if (val !== null && val !== '')
        formDataObj.append(key, String(val))
    })
    if (docFile.value)
      formDataObj.append('doc', docFile.value)

    const response = await $api('/ehs/events/store', {
      method: 'POST',
      body: formDataObj,
    })

    message.value = response.message
    color.value = response.color || 'success'
    isSnackbarVisible.value = true

    if (response.success && response.obj?.id) {
      setTimeout(() => {
        router.push({ name: 'ehs-view-id', params: { id: response.obj.id } })
      }, 800)
    }
    else if (response.success) {
      setTimeout(() => {
        router.push({ name: 'ehs-list' })
      }, 800)
    }
  }
  catch (error: any) {
    message.value = error.message || 'Errore durante il salvataggio'
    color.value = 'error'
    isSnackbarVisible.value = true
  }
  finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="workspace-container w-100 d-flex flex-column pa-4 gap-3">
    <VSnackbar v-model="isSnackbarVisible" transition="scroll-y-reverse-transition" location="top center" :timeout="3000">
      {{ $t(message) }}
    </VSnackbar>

    <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
      <VCardText class="d-flex align-center gap-2 py-3">
        <VIcon icon="tabler-shield-plus" size="24" color="primary" />
        <div class="text-h6 font-weight-medium">
          {{ form.tipo_scheda === 1 ? $t('Ehs.Nuovo-Infortunio') : $t('Ehs.Nuovo-Evento') }}
        </div>
      </VCardText>
      <VDivider />

      <VCardText class="pa-4">
        <AppStepper
          v-model:current-step="currentStep"
          :items="steps"
          align="center"
          icon-size="42"
          class="mb-6"
        />

        <VForm @submit.prevent="submit">
          <VWindow v-model="currentStep" class="disable-tab-transition">
            <!-- STEP 1: Tipo scheda -->
            <VWindowItem>
              <VRow justify="center" class="mb-4">
                <VCol
                  v-for="opt in tipoSchedaItems"
                  :key="opt.value"
                  cols="12"
                  sm="5"
                  md="4"
                >
                  <VCard
                    :variant="form.tipo_scheda === opt.value ? 'elevated' : 'outlined'"
                    :color="form.tipo_scheda === opt.value ? 'primary' : undefined"
                    class="cursor-pointer text-center pa-6 h-100"
                    @click="form.tipo_scheda = opt.value"
                  >
                    <VIcon
                      :icon="opt.value === 1 ? 'tabler-bandage' : 'tabler-leaf'"
                      size="40"
                      class="mb-2"
                    />
                    <div class="text-h6">{{ opt.title }}</div>
                  </VCard>
                </VCol>
              </VRow>
              <VRow justify="center">
                <VCol cols="12" sm="5" md="4">
                  <AppSelect
                    v-model="form.tipo_rilevazione"
                    :label="$t('Ehs.Tipo-Rilevazione')"
                    :items="tipoRilevazioneItems"
                    :rules="[v => !!v || $t('Validation.Required')]"
                    density="compact"
                  />
                </VCol>
                <VCol cols="12" sm="5" md="4">
                  <AppSelect
                    v-model="form.qualifica"
                    :label="$t('Ehs.Qualifica')"
                    :items="qualificaItems"
                    :rules="[v => !!v || $t('Validation.Required')]"
                    density="compact"
                  />
                </VCol>
              </VRow>
            </VWindowItem>

            <!-- STEP 2: Dipendente -->
            <VWindowItem>
              <VRow justify="center">
                <VCol cols="12" md="8">
                  <AppAutocomplete
                    v-model="selectedEmployeeId"
                    v-model:search="employeeSearch"
                    :label="$t('Label.Cerca-Dipendente')"
                    :items="employeeItems"
                    item-title="nome_completo"
                    item-value="id"
                    :loading="searchingEmployee"
                    prepend-inner-icon="tabler-search"
                    density="compact"
                    clearable
                    @update:model-value="onEmployeeSelected"
                  />
                </VCol>
              </VRow>
              <VRow justify="center">
                <VCol cols="12" sm="4" md="3">
                  <AppTextField
                    v-model="form.matricola"
                    :label="$t('Label.Matricola')"
                    :rules="[v => !!v || $t('Validation.Required')]"
                    density="compact"
                    @blur="onMatricolaBlur"
                  />
                </VCol>
                <VCol cols="12" sm="4" md="3">
                  <AppTextField
                    v-model="form.nome"
                    :label="$t('Label.Nome')"
                    :rules="[v => !!v || $t('Validation.Required')]"
                    density="compact"
                  />
                </VCol>
                <VCol cols="12" sm="4" md="2">
                  <AppTextField
                    v-model="form.cognome"
                    :label="$t('Label.Cognome')"
                    :rules="[v => !!v || $t('Validation.Required')]"
                    density="compact"
                  />
                </VCol>
              </VRow>
            </VWindowItem>

            <!-- STEP 3: Evento -->
            <VWindowItem>
              <VRow>
                <VCol cols="12" sm="3">
                  <AppTextField
                    v-model="form.data_evento"
                    :label="$t('Ehs.Data-Evento')"
                    type="datetime-local"
                    density="compact"
                    :rules="[v => !!v || $t('Validation.Required')]"
                  />
                </VCol>
                <VCol cols="12" sm="3">
                  <AppSelect
                    v-model="form.ora_lavorativa"
                    :label="$t('Ehs.Ora-Lavorativa')"
                    :items="oraLavorativaItems"
                    density="compact"
                    clearable
                  >
                    <template #item="{ props: itemProps, item }">
                      <VListItem v-bind="itemProps">
                        <template #prepend>
                          <VIcon icon="tabler-circle-filled" :color="item.raw.color" size="14" />
                        </template>
                      </VListItem>
                    </template>
                    <template #selection="{ item }">
                      <div class="d-flex align-center gap-2">
                        <VIcon icon="tabler-circle-filled" :color="item.raw.color" size="14" />
                        <span>{{ item.title }}</span>
                      </div>
                    </template>
                  </AppSelect>
                </VCol>
                <VCol cols="12" sm="3">
                  <div class="d-flex align-start gap-1">
                    <AppSelect
                      v-model="form.reparto_id"
                      :label="form.tipo_scheda === 1 ? $t('Ehs.Mansione') : $t('Label.Reparto')"
                      :items="formData?.reparti || []"
                      item-title="reparto"
                      item-value="id"
                      :rules="[v => !!v || $t('Validation.Required')]"
                      density="compact"
                    />
                    <VBtn
                      icon="tabler-plus"
                      variant="tonal"
                      color="success"
                      size="small"
                      class="mt-5"
                      @click="openQuickAdd('reparti')"
                    />
                  </div>
                </VCol>
                <VCol cols="12" sm="3">
                  <div class="d-flex align-start gap-1">
                    <AppSelect
                      v-model="form.sede_id"
                      :label="$t('Ehs.Impianto')"
                      :items="formData?.sedi || []"
                      item-title="site"
                      item-value="id"
                      :rules="[v => !!v || $t('Validation.Required')]"
                      density="compact"
                    />
                    <VBtn
                      icon="tabler-plus"
                      variant="tonal"
                      color="success"
                      size="small"
                      class="mt-5"
                      @click="openQuickAdd('sedi')"
                    />
                  </div>
                </VCol>
                <VCol cols="12">
                  <AppTextarea v-model="form.desc_dinamica" :label="$t('Ehs.Descrizione-Dinamica')" rows="2" density="compact" />
                </VCol>
                <VCol v-if="!isFirstAid" cols="12">
                  <AppTextarea v-model="form.testimoni" :label="$t('Ehs.Testimoni')" rows="2" density="compact" />
                </VCol>
              </VRow>

              <!-- Campi specifici infortunio -->
              <VRow v-if="form.tipo_scheda === 1">
                <VCol cols="12" sm="4">
                  <div class="d-flex align-start gap-1">
                    <AppSelect
                      v-model="form.lesione_id"
                      :label="$t('Ehs.Tipo-Lesione')"
                      :items="formData?.lesioni || []"
                      item-title="injurie"
                      item-value="id"
                      :rules="[v => !!v || $t('Validation.Required')]"
                      density="compact"
                    />
                    <VBtn
                      icon="tabler-plus"
                      variant="tonal"
                      color="success"
                      size="small"
                      class="mt-5"
                      @click="openQuickAdd('lesioni')"
                    />
                  </div>
                </VCol>
                <VCol cols="12" sm="4">
                  <div class="d-flex align-start gap-1">
                    <AppSelect
                      v-model="form.s_anatomica_id"
                      :label="$t('Ehs.Sede-Anatomica')"
                      :items="formData?.s_anatomiche || []"
                      item-title="anatomical"
                      item-value="id"
                      :rules="[v => !!v || $t('Validation.Required')]"
                      density="compact"
                    />
                    <VBtn
                      icon="tabler-plus"
                      variant="tonal"
                      color="success"
                      size="small"
                      class="mt-5"
                      @click="openQuickAdd('anatomiche')"
                    />
                  </div>
                </VCol>
              </VRow>

              <!-- Campi specifici evento ambientale -->
              <VRow v-else>
                <VCol cols="12" sm="4">
                  <div class="d-flex align-start gap-1">
                    <AppSelect
                      v-model="form.tipo_evento_id"
                      :label="$t('Ehs.Tipo-Evento')"
                      :items="formData?.t_eventi || []"
                      item-title="event"
                      item-value="id"
                      :rules="[v => !!v || $t('Validation.Required')]"
                      density="compact"
                    />
                    <VBtn
                      icon="tabler-plus"
                      variant="tonal"
                      color="success"
                      size="small"
                      class="mt-5"
                      @click="openQuickAdd('eventi')"
                    />
                  </div>
                </VCol>
              </VRow>

              <!-- Dinamica (causa): sempre visibile come nel vecchio gestionale -->
              <VRow>
                <VCol cols="12" sm="4">
                  <div class="d-flex align-start gap-1">
                    <AppSelect
                      v-model="form.causa_id"
                      :label="$t('Ehs.Dinamica')"
                      :items="formData?.cause || []"
                      item-title="causa"
                      item-value="id"
                      :rules="[v => !!v || $t('Validation.Required')]"
                      density="compact"
                    />
                    <VBtn
                      icon="tabler-plus"
                      variant="tonal"
                      color="success"
                      size="small"
                      class="mt-5"
                      @click="openQuickAdd('cause')"
                    />
                  </div>
                </VCol>
              </VRow>
            </VWindowItem>

            <!-- STEP 4: Analisi e azioni -->
            <VWindowItem>
              <VRow>
                <VCol cols="12" sm="6">
                  <AppTextField v-model="form.responsabile_azione" :label="$t('Ehs.Responsabile-Azione')" density="compact" />
                </VCol>
                <VCol cols="12" sm="6">
                  <AppTextarea v-model="form.analisi_causa" :label="$t('Ehs.Analisi-Causa')" rows="3" density="compact" />
                </VCol>
                <VCol cols="12" sm="6">
                  <AppTextarea v-model="form.azioni_contenimento" :label="$t('Ehs.Azioni-Contenimento')" rows="3" density="compact" />
                </VCol>
                <VCol cols="12" sm="6">
                  <AppTextarea v-model="form.azioni" :label="$t('Ehs.Azioni-Correttive')" rows="3" density="compact" />
                </VCol>
              </VRow>
            </VWindowItem>

            <!-- STEP 5: Allegato -->
            <VWindowItem>
              <VRow justify="center">
                <VCol cols="12" md="6">
                  <VFileInput
                    v-model="docFile"
                    :label="$t('Ehs.Documento')"
                    prepend-icon="tabler-paperclip"
                    density="compact"
                    clearable
                    show-size
                  />
                  <VAlert
                    type="info"
                    variant="tonal"
                    density="compact"
                    icon="tabler-brand-google-drive"
                    class="mt-3"
                  >
                    {{ $t('Ehs.Allegato-Drive-Info') }}
                  </VAlert>
                </VCol>
              </VRow>
            </VWindowItem>
          </VWindow>

          <VDivider class="my-4" />
          <div class="d-flex justify-space-between gap-3">
            <VBtn
              variant="outlined"
              color="secondary"
              prepend-icon="tabler-arrow-left"
              :disabled="currentStep === 0"
              @click="prevStep"
            >
              {{ $t('Label.Indietro') }}
            </VBtn>
            <div class="d-flex gap-3">
              <VBtn variant="outlined" color="secondary" :to="{ name: 'ehs-list' }">
                {{ $t('Label.Annulla') }}
              </VBtn>
              <VBtn
                v-if="currentStep < steps.length - 1"
                color="primary"
                append-icon="tabler-arrow-right"
                :disabled="!isStepValid"
                @click="nextStep"
              >
                {{ $t('Label.Avanti') }}
              </VBtn>
              <VBtn
                v-else
                type="submit"
                color="primary"
                prepend-icon="tabler-device-floppy"
                :loading="loading"
              >
                {{ $t('Label.Salva') }}
              </VBtn>
            </div>
          </div>
        </VForm>
      </VCardText>
    </VCard>

    <!-- Dialog quick-add tabelle di supporto -->
    <VDialog v-model="quickAddDialog" max-width="420">
      <VCard>
        <VCardTitle class="d-flex align-center gap-2 pt-4 px-4">
          <VIcon icon="tabler-plus" color="success" />
          {{ $t('Label.Nuovo') }} {{ quickAddLabel }}
        </VCardTitle>
        <VCardText class="px-4">
          <AppTextField
            v-model="quickAddNome"
            :label="$t('Label.Nome')"
            autofocus
            @keyup.enter="saveQuickAdd"
          />
        </VCardText>
        <VCardActions class="px-4 pb-4">
          <VSpacer />
          <VBtn variant="outlined" color="secondary" @click="quickAddDialog = false">
            {{ $t('Label.Annulla') }}
          </VBtn>
          <VBtn color="primary" :loading="quickAddSaving" :disabled="!quickAddNome" @click="saveQuickAdd">
            {{ $t('Label.Salva') }}
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>
