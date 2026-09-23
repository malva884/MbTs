<script setup lang="ts">
import { useI18n } from 'vue-i18n'

definePage({
  meta: {
    action: 'edit',
    subject: 'Ehs-Eventi',
  },
})

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const id = route.params.id as string

const loading = ref(false)
const loadingData = ref(true)
const isSnackbarVisible = ref(false)
const message = ref('')
const color = ref('')

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

const { data: formData } = await useApi<any>('/ehs/events/form-data')

// Carica i dati dell'evento
const loadEvent = async () => {
  loadingData.value = true

  const { data, error } = await useApi<any>(`/ehs/events/view/${id}`)
  if (data.value) {
    const e = data.value

    form.value = {
      tipo_scheda: e.tipo_scheda,
      tipo_rilevazione: e.tipo_rilevazione,
      matricola: e.matricola || '',
      nome: e.nome || '',
      cognome: e.cognome || '',
      qualifica: e.qualifica,
      data_evento: e.data_evento ? e.data_evento.substring(0, 16) : '',
      ora_lavorativa: e.ora_lavorativa || '',
      testimoni: e.testimoni || '',
      desc_dinamica: e.desc_dinamica || '',
      causa_id: e.causa_id,
      azioni: e.azioni || '',
      reparto_id: e.reparto_id,
      sede_id: e.sede_id,
      lesione_id: e.lesione_id,
      s_anatomica_id: e.s_anatomica_id,
      tipo_evento_id: e.tipo_evento_id,
      giorni_infortunio: e.giorni_infortunio,
      analisi_causa: e.analisi_causa || '',
      azioni_contenimento: e.azioni_contenimento || '',
      responsabile_azione: e.responsabile_azione || '',
      data_chiusura: e.data_chiusura ? e.data_chiusura.substring(0, 10) : '',
    }

    // Se il reparto è disattivo (importato dal vecchio gestionale) non è in formData:
    // lo aggiungo alla lista per mostrare il nome invece dell'id
    if (e.reparto_id && e.reparto && formData.value?.reparti
      && !formData.value.reparti.some((r: any) => r.id === e.reparto_id))
      formData.value.reparti.push({ id: e.reparto.id, reparto: e.reparto.reparto })
  }
  else if (error.value) {
    message.value = 'Errore caricamento evento'
    color.value = 'error'
    isSnackbarVisible.value = true
  }
  loadingData.value = false
}

await loadEvent()

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

    const response = await $api(`/ehs/events/update/${id}`, {
      method: 'POST',
      body: formDataObj,
    })

    message.value = response.message
    color.value = response.color || 'success'
    isSnackbarVisible.value = true

    if (response.success) {
      setTimeout(() => {
        router.push({ name: 'ehs-view-id', params: { id } })
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
        <VIcon icon="tabler-edit" size="24" color="primary" />
        <div class="text-h6 font-weight-medium">
          {{ form.tipo_scheda === 1 ? $t('Ehs.Modifica-Infortunio') : $t('Ehs.Modifica-Evento') }}
        </div>
      </VCardText>
      <VDivider />

      <VCardText v-if="loadingData" class="pa-4 text-center">
        <VProgressCircular indeterminate color="primary" />
      </VCardText>

      <VCardText v-else class="pa-4">
        <VForm @submit.prevent="submit">
          <VRow>
            <VCol cols="12" sm="4">
              <AppSelect
                v-model="form.tipo_rilevazione"
                :label="$t('Ehs.Tipo-Rilevazione')"
                :items="tipoRilevazioneItems"
                :rules="[v => !!v || $t('Validation.Required')]"
              />
            </VCol>
            <VCol cols="12" sm="4">
              <AppSelect
                v-model="form.qualifica"
                :label="$t('Ehs.Qualifica')"
                :items="qualificaItems"
                clearable
              />
            </VCol>
          </VRow>

          <VDivider class="my-4" />
          <div class="text-subtitle-1 font-weight-medium mb-3">{{ $t('Ehs.Dati-Dipendente') }}</div>
          <VRow>
            <VCol cols="12" sm="2">
              <AppTextField
                v-model="form.matricola"
                :label="$t('Label.Matricola')"
                :rules="[v => !!v || $t('Validation.Required')]"
                @blur="onMatricolaBlur"
              />
            </VCol>
            <VCol cols="12" sm="3">
              <AppTextField
                v-model="form.nome"
                :label="$t('Label.Nome')"
                :rules="[v => !!v || $t('Validation.Required')]"
              />
            </VCol>
            <VCol cols="12" sm="3">
              <AppTextField
                v-model="form.cognome"
                :label="$t('Label.Cognome')"
                :rules="[v => !!v || $t('Validation.Required')]"
              />
            </VCol>
          </VRow>

          <VDivider class="my-4" />
          <div class="text-subtitle-1 font-weight-medium mb-3">{{ $t('Ehs.Dati-Evento') }}</div>
          <VRow>
            <VCol cols="12" sm="3">
              <AppTextField
                v-model="form.data_evento"
                :label="$t('Ehs.Data-Evento')"
                type="datetime-local"
                :rules="[v => !!v || $t('Validation.Required')]"
              />
            </VCol>
            <VCol cols="12" sm="3">
              <AppSelect
                v-model="form.ora_lavorativa"
                :label="$t('Ehs.Ora-Lavorativa')"
                :items="oraLavorativaItems"
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
          </VRow>

          <VRow>
            <VCol cols="12">
              <AppTextarea v-model="form.desc_dinamica" :label="$t('Ehs.Descrizione-Dinamica')" rows="3" />
            </VCol>
            <VCol v-if="!isFirstAid" cols="12">
              <AppTextarea v-model="form.testimoni" :label="$t('Ehs.Testimoni')" rows="2" />
            </VCol>
          </VRow>

          <template v-if="form.tipo_scheda === 1">
            <VDivider class="my-4" />
            <div class="text-subtitle-1 font-weight-medium mb-3">{{ $t('Ehs.Dati-Infortunio') }}</div>
            <VRow>
              <VCol cols="12" sm="4">
                <div class="d-flex align-start gap-1">
                  <AppSelect
                    v-model="form.lesione_id"
                    :label="$t('Ehs.Tipo-Lesione')"
                    :items="formData?.lesioni || []"
                    item-title="injurie"
                    item-value="id"
                    :rules="[v => !!v || $t('Validation.Required')]"
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
              <VCol cols="12" sm="4">
                <AppTextField
                  v-model="form.giorni_infortunio"
                  :label="$t('Ehs.Giorni-Infortunio')"
                  type="number"
                  min="0"
                />
              </VCol>
            </VRow>
          </template>

          <template v-else>
            <VDivider class="my-4" />
            <div class="text-subtitle-1 font-weight-medium mb-3">{{ $t('Ehs.Dati-Evento-Ambientale') }}</div>
            <VRow>
              <VCol cols="12" sm="4">
                <div class="d-flex align-start gap-1">
                  <AppSelect
                    v-model="form.tipo_evento_id"
                    :label="$t('Ehs.Tipo-Evento')"
                    :items="formData?.t_eventi || []"
                    item-title="event"
                    item-value="id"
                    :rules="[v => !!v || $t('Validation.Required')]"
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
          </template>

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

          <template v-if="!isFirstAid">
            <VDivider class="my-4" />
            <div class="text-subtitle-1 font-weight-medium mb-3">{{ $t('Ehs.Analisi-E-Azioni') }}</div>
            <VRow>
              <VCol cols="12" sm="6">
                <AppTextField v-model="form.responsabile_azione" :label="$t('Ehs.Responsabile-Azione')" />
              </VCol>
              <VCol cols="12">
                <AppTextarea v-model="form.analisi_causa" :label="$t('Ehs.Analisi-Causa')" rows="3" />
              </VCol>
              <VCol cols="12">
                <AppTextarea v-model="form.azioni_contenimento" :label="$t('Ehs.Azioni-Contenimento')" rows="3" />
              </VCol>
              <VCol cols="12">
                <AppTextarea v-model="form.azioni" :label="$t('Ehs.Azioni-Correttive')" rows="3" />
              </VCol>
              <VCol cols="12" sm="4">
                <AppTextField v-model="form.data_chiusura" :label="$t('Ehs.Data-Chiusura')" type="date" />
              </VCol>
            </VRow>

            <VDivider class="my-4" />
            <div class="text-subtitle-1 font-weight-medium mb-3">{{ $t('Ehs.Allegato') }}</div>
            <VRow>
              <VCol cols="12" sm="6">
                <VFileInput
                  v-model="docFile"
                  :label="$t('Ehs.Documento')"
                  prepend-icon="tabler-paperclip"
                  clearable
                  show-size
                />
              </VCol>
            </VRow>
          </template>

          <VDivider class="my-4" />
          <div class="d-flex justify-end gap-3">
            <VBtn variant="outlined" color="secondary" :to="{ name: 'ehs-view-id', params: { id } }">
              {{ $t('Label.Annulla') }}
            </VBtn>
            <VBtn type="submit" color="primary" :loading="loading">
              {{ $t('Label.Salva') }}
            </VBtn>
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
