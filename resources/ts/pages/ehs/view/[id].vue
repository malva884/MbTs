<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { can } from '@layouts/plugins/casl'
import DefineAbilities from '@/plugins/casl/DefineAbilities'

definePage({
  meta: {
    action: 'list',
    subject: 'Ehs-Eventi',
  },
})

const { t } = useI18n()
const route = useRoute()
const id = route.params.id as string

const loading = ref(true)
const event = ref<any>(null)
const isSnackbarVisible = ref(false)
const message = ref('')
const color = ref('')
const uploading = ref(false)
const docFile = ref<File | null>(null)

const loadEvent = async () => {
  loading.value = true

  const { data } = await useApi<any>(`/ehs/events/view/${id}`)
  if (data.value)
    event.value = data.value
  loading.value = false
}

await loadEvent()

const qualificaLabel = (q: number | null) => {
  switch (q) {
    case 1: return t('Ehs.Operaio')
    case 2: return t('Ehs.Impiegato')
    case 3: return t('Ehs.Altro')
    default: return '-'
  }
}

const tipoRilevazioneLabel = (tipo: number) => {
  switch (tipo) {
    case 1: return { color: 'error', text: t('Ehs.Infortunio') }
    case 2: return { color: 'warning', text: t('Ehs.Near-Miss-Inf') }
    case 3: return { color: 'error', text: t('Ehs.Danno-Ambientale') }
    case 4: return { color: 'warning', text: t('Ehs.Near-Miss-Amb') }
    case 5: return { color: 'info', text: t('Ehs.First-Aid') }
    default: return { color: 'secondary', text: '-' }
  }
}

const formatDate = (date: string | null) => {
  if (!date)
    return '-'

  return new Date(date).toLocaleDateString('it-IT')
}

const formatDateTime = (date: string | null) => {
  if (!date)
    return '-'

  return new Date(date).toLocaleString('it-IT')
}

// First AID: nasconde testimoni, analisi e allegati (come nel vecchio gestionale)
const isFirstAid = computed(() => event.value?.tipo_rilevazione === 5)

const nomeCompleto = computed(() =>
  event.value?.employee?.nome_completo
  || `${event.value?.nome || ''} ${event.value?.cognome || ''}`.trim()
  || '-')

const initials = computed(() => {
  if (nomeCompleto.value === '-')
    return '?'

  return nomeCompleto.value.split(' ').map((w: string) => w[0]).join('').substring(0, 2).toUpperCase()
})

const oraColor = (ora: string | null) => {
  const map: Record<string, string> = {
    '1°': '#4CAF50',
    '2°': '#8BC34A',
    '3°': '#CDDC39',
    '4°': '#FFEB3B',
    '5°': '#FFC107',
    '6°': '#FF9800',
    '7°': '#FF5722',
    '8°': '#F44336',
    'Extra time': '#B71C1C',
  }

  return map[ora ?? ''] || 'grey'
}

const uploadFile = async () => {
  if (!docFile.value)
    return
  uploading.value = true
  try {
    const fd = new FormData()

    fd.append('id', id)
    fd.append('doc', docFile.value)

    const response = await $api('/ehs/events/add-file', {
      method: 'POST',
      body: fd,
    })

    message.value = response.message
    color.value = response.color || 'success'
    isSnackbarVisible.value = true
    docFile.value = null
    loadEvent()
  }
  catch (error: any) {
    message.value = error.message || 'Errore upload'
    color.value = 'error'
    isSnackbarVisible.value = true
  }
  finally {
    uploading.value = false
  }
}

const exportPdf = () => {
  window.open(`/api/ehs/events/export/${id}`, '_blank')
}
</script>

<template>
  <div class="workspace-container w-100 d-flex flex-column pa-4 gap-3">
    <VSnackbar v-model="isSnackbarVisible" transition="scroll-y-reverse-transition" location="top center" :timeout="3000">
      {{ $t(message) }}
    </VSnackbar>

    <!-- Header -->
    <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
      <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
        <div class="d-flex align-center gap-3">
          <IconBtn variant="tonal" color="secondary" :to="{ name: 'ehs-list' }">
            <VIcon icon="tabler-arrow-left" />
          </IconBtn>
          <div>
            <div class="d-flex align-center flex-wrap gap-2">
              <span class="text-h6 font-weight-medium">
                {{ event?.tipo_scheda === 1 ? $t('Ehs.Scheda-Infortunio') : $t('Ehs.Scheda-Evento') }}
              </span>
              <VChip
                v-if="event"
                :color="tipoRilevazioneLabel(event.tipo_rilevazione).color"
                size="small"
                variant="tonal"
              >
                {{ tipoRilevazioneLabel(event.tipo_rilevazione).text }}
              </VChip>
              <VChip
                v-if="event"
                :color="event.data_chiusura ? 'success' : 'warning'"
                size="small"
                variant="tonal"
              >
                {{ event.data_chiusura ? $t('Label.Chiuso') : $t('Label.Aperto') }}
              </VChip>
            </div>
            <div v-if="event" class="text-caption text-medium-emphasis">
              {{ formatDateTime(event.data_evento) }}
              <template v-if="event.user">
                · {{ event.user.firstname }} {{ event.user.lastname }}
              </template>
            </div>
          </div>
        </div>
        <div class="d-flex gap-2">
          <VBtn
            prepend-icon="tabler-file-type-pdf"
            color="secondary"
            variant="outlined"
            density="comfortable"
            @click="exportPdf"
          >
            {{ $t('Ehs.Export-Pdf') }}
          </VBtn>
          <VBtn
            v-if="can(DefineAbilities.ehs_event_edit.action, DefineAbilities.ehs_event_edit.subject)"
            prepend-icon="tabler-edit"
            color="primary"
            variant="flat"
            density="comfortable"
            :to="{ name: 'ehs-edit-id', params: { id } }"
          >
            {{ $t('Label.Modifica') }}
          </VBtn>
        </div>
      </VCardText>
    </VCard>

    <div v-if="loading" class="text-center pa-8">
      <VProgressCircular indeterminate color="primary" />
    </div>

    <VRow v-else-if="event">
      <!-- Sidebar: dipendente + allegati -->
      <VCol cols="12" md="4" class="d-flex flex-column gap-3 order-md-last">
        <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
          <VCardText class="pa-4">
            <div class="d-flex align-center gap-2 mb-4">
              <VIcon icon="tabler-user" size="20" color="info" />
              <span class="text-subtitle-1 font-weight-medium">{{ $t('Ehs.Dati-Dipendente') }}</span>
            </div>
            <div class="d-flex align-center gap-3 mb-4">
              <VAvatar size="48" color="primary" variant="tonal">
                <span class="text-h6">{{ initials }}</span>
              </VAvatar>
              <div>
                <div class="text-body-1 font-weight-medium">{{ nomeCompleto }}</div>
                <div class="text-caption text-medium-emphasis">
                  {{ $t('Label.Matricola') }}: {{ event.matricola || '-' }}
                </div>
              </div>
            </div>
            <div class="d-flex align-center gap-3 mb-3">
              <VAvatar size="36" variant="tonal" color="info">
                <VIcon icon="tabler-id-badge" size="18" />
              </VAvatar>
              <div>
                <div class="text-caption text-medium-emphasis">{{ $t('Ehs.Qualifica') }}</div>
                <div class="text-body-1 font-weight-medium">{{ qualificaLabel(event.qualifica) }}</div>
              </div>
            </div>
            <div class="d-flex align-center gap-3">
              <VAvatar size="36" variant="tonal" color="info">
                <VIcon icon="tabler-user-edit" size="18" />
              </VAvatar>
              <div>
                <div class="text-caption text-medium-emphasis">{{ $t('Ehs.Inserito-Da') }}</div>
                <div class="text-body-1 font-weight-medium">
                  {{ event.user ? `${event.user.firstname} ${event.user.lastname}` : '-' }}
                </div>
              </div>
            </div>
          </VCardText>
        </VCard>

        <!-- Allegati -->
        <VCard v-if="!isFirstAid" variant="outlined" class="bg-surface border-thin rounded-lg">
          <VCardText class="pa-4">
            <div class="d-flex align-center gap-2 mb-4">
              <VIcon icon="tabler-paperclip" size="20" color="secondary" />
              <span class="text-subtitle-1 font-weight-medium">{{ $t('Ehs.Allegati') }}</span>
            </div>
            <VFileInput
              v-model="docFile"
              :label="$t('Ehs.Carica-Documento')"
              prepend-icon="tabler-paperclip"
              clearable
              show-size
              density="compact"
            />
            <div class="d-flex flex-wrap gap-2 mt-3">
              <VBtn
                color="primary"
                variant="tonal"
                size="small"
                :loading="uploading"
                :disabled="!docFile"
                @click="uploadFile"
              >
                {{ $t('Label.Carica') }}
              </VBtn>
              <VBtn
                v-if="event.path_drive"
                color="success"
                variant="outlined"
                size="small"
                prepend-icon="tabler-brand-google-drive"
                :href="`https://drive.google.com/drive/u/0/folders/${event.path_drive}`"
                target="_blank"
                rel="noopener noreferrer"
              >
                {{ $t('Ehs.Apri-Drive') }}
              </VBtn>
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <!-- Colonna principale -->
      <VCol cols="12" md="8" class="d-flex flex-column gap-3">
        <!-- Dati evento -->
        <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
          <VCardText class="pa-4">
            <div class="d-flex align-center gap-2 mb-4">
              <VIcon icon="tabler-calendar-event" size="20" color="primary" />
              <span class="text-subtitle-1 font-weight-medium">{{ $t('Ehs.Dati-Evento') }}</span>
            </div>
            <VRow>
              <VCol cols="12" sm="6" md="4">
                <div class="d-flex align-center gap-3">
                  <VAvatar size="36" variant="tonal" color="primary">
                    <VIcon icon="tabler-calendar-time" size="18" />
                  </VAvatar>
                  <div>
                    <div class="text-caption text-medium-emphasis">{{ $t('Ehs.Data-Evento') }}</div>
                    <div class="text-body-1 font-weight-medium">{{ formatDateTime(event.data_evento) }}</div>
                  </div>
                </div>
              </VCol>
              <VCol cols="12" sm="6" md="4">
                <div class="d-flex align-center gap-3">
                  <VAvatar size="36" variant="tonal" color="primary">
                    <VIcon icon="tabler-clock" size="18" />
                  </VAvatar>
                  <div>
                    <div class="text-caption text-medium-emphasis">{{ $t('Ehs.Ora-Lavorativa') }}</div>
                    <div class="text-body-1 font-weight-medium d-flex align-center gap-2">
                      <VIcon icon="tabler-circle-filled" :color="oraColor(event.ora_lavorativa)" size="12" />
                      {{ event.ora_lavorativa || '-' }}
                    </div>
                  </div>
                </div>
              </VCol>
              <VCol cols="12" sm="6" md="4">
                <div class="d-flex align-center gap-3">
                  <VAvatar size="36" variant="tonal" color="primary">
                    <VIcon icon="tabler-briefcase" size="18" />
                  </VAvatar>
                  <div>
                    <div class="text-caption text-medium-emphasis">
                      {{ event.tipo_scheda === 1 ? $t('Ehs.Mansione') : $t('Label.Reparto') }}
                    </div>
                    <div class="text-body-1 font-weight-medium">{{ event.reparto?.reparto || '-' }}</div>
                  </div>
                </div>
              </VCol>
              <VCol cols="12" sm="6" md="4">
                <div class="d-flex align-center gap-3">
                  <VAvatar size="36" variant="tonal" color="primary">
                    <VIcon icon="tabler-building-factory-2" size="18" />
                  </VAvatar>
                  <div>
                    <div class="text-caption text-medium-emphasis">{{ $t('Ehs.Impianto') }}</div>
                    <div class="text-body-1 font-weight-medium">{{ event.impianto?.site || '-' }}</div>
                  </div>
                </div>
              </VCol>
              <VCol cols="12" sm="6" md="4">
                <div class="d-flex align-center gap-3">
                  <VAvatar size="36" variant="tonal" color="primary">
                    <VIcon icon="tabler-bolt" size="18" />
                  </VAvatar>
                  <div>
                    <div class="text-caption text-medium-emphasis">{{ $t('Ehs.Dinamica') }}</div>
                    <div class="text-body-1 font-weight-medium">{{ event.causa?.causa || '-' }}</div>
                  </div>
                </div>
              </VCol>
            </VRow>

            <div class="mt-4">
              <div class="text-caption text-medium-emphasis mb-1">{{ $t('Ehs.Descrizione-Dinamica') }}</div>
              <div class="text-body-2 pa-3 rounded-lg border-thin" style="white-space: pre-wrap">{{ event.desc_dinamica || '-' }}</div>
            </div>
            <div v-if="!isFirstAid" class="mt-3">
              <div class="text-caption text-medium-emphasis mb-1">{{ $t('Ehs.Testimoni') }}</div>
              <div class="text-body-2 pa-3 rounded-lg border-thin" style="white-space: pre-wrap">{{ event.testimoni || '-' }}</div>
            </div>
          </VCardText>
        </VCard>

        <!-- Dati infortunio -->
        <VCard v-if="event.tipo_scheda === 1" variant="outlined" class="bg-surface border-thin rounded-lg">
          <VCardText class="pa-4">
            <div class="d-flex align-center gap-2 mb-4">
              <VIcon icon="tabler-bandage" size="20" color="error" />
              <span class="text-subtitle-1 font-weight-medium">{{ $t('Ehs.Dati-Infortunio') }}</span>
            </div>
            <VRow>
              <VCol cols="12" sm="4">
                <div class="text-caption text-medium-emphasis">{{ $t('Ehs.Tipo-Lesione') }}</div>
                <div class="text-body-1 font-weight-medium">{{ event.lesione?.injurie || '-' }}</div>
              </VCol>
              <VCol cols="12" sm="4">
                <div class="text-caption text-medium-emphasis">{{ $t('Ehs.Sede-Anatomica') }}</div>
                <div class="text-body-1 font-weight-medium">{{ event.anatomica?.anatomical || '-' }}</div>
              </VCol>
              <VCol cols="12" sm="4">
                <div class="text-caption text-medium-emphasis">{{ $t('Ehs.Giorni-Infortunio') }}</div>
                <div class="text-body-1 font-weight-medium">{{ event.giorni_infortunio ?? '-' }}</div>
              </VCol>
            </VRow>
          </VCardText>
        </VCard>

        <!-- Dati evento ambientale -->
        <VCard v-else variant="outlined" class="bg-surface border-thin rounded-lg">
          <VCardText class="pa-4">
            <div class="d-flex align-center gap-2 mb-4">
              <VIcon icon="tabler-leaf" size="20" color="success" />
              <span class="text-subtitle-1 font-weight-medium">{{ $t('Ehs.Dati-Evento-Ambientale') }}</span>
            </div>
            <div class="text-caption text-medium-emphasis">{{ $t('Ehs.Tipo-Evento') }}</div>
            <div class="text-body-1 font-weight-medium">{{ event.evento?.event || '-' }}</div>
          </VCardText>
        </VCard>

        <!-- Analisi e azioni -->
        <VCard v-if="!isFirstAid" variant="outlined" class="bg-surface border-thin rounded-lg">
          <VCardText class="pa-4">
            <div class="d-flex align-center gap-2 mb-4">
              <VIcon icon="tabler-clipboard-check" size="20" color="warning" />
              <span class="text-subtitle-1 font-weight-medium">{{ $t('Ehs.Analisi-E-Azioni') }}</span>
            </div>
            <VRow>
              <VCol cols="12" sm="6">
                <div class="text-caption text-medium-emphasis">{{ $t('Ehs.Responsabile-Azione') }}</div>
                <div class="text-body-1 font-weight-medium">{{ event.responsabile_azione || '-' }}</div>
              </VCol>
              <VCol cols="12" sm="6">
                <div class="text-caption text-medium-emphasis">{{ $t('Ehs.Data-Chiusura') }}</div>
                <div class="text-body-1 font-weight-medium">{{ formatDate(event.data_chiusura) }}</div>
              </VCol>
              <VCol cols="12">
                <div class="text-caption text-medium-emphasis mb-1">{{ $t('Ehs.Analisi-Causa') }}</div>
                <div class="text-body-2 pa-3 rounded-lg border-thin" style="white-space: pre-wrap">{{ event.analisi_causa || '-' }}</div>
              </VCol>
              <VCol cols="12" sm="6">
                <div class="text-caption text-medium-emphasis mb-1">{{ $t('Ehs.Azioni-Contenimento') }}</div>
                <div class="text-body-2 pa-3 rounded-lg border-thin" style="white-space: pre-wrap">{{ event.azioni_contenimento || '-' }}</div>
              </VCol>
              <VCol cols="12" sm="6">
                <div class="text-caption text-medium-emphasis mb-1">{{ $t('Ehs.Azioni-Correttive') }}</div>
                <div class="text-body-2 pa-3 rounded-lg border-thin" style="white-space: pre-wrap">{{ event.azioni || '-' }}</div>
              </VCol>
            </VRow>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>
