<script setup lang="ts">
import { useRouter } from 'vue-router'
import { VForm } from 'vuetify/components/VForm'
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import moment from 'moment'
import { useI18n } from 'vue-i18n'
import type { Fai } from '@/views/quality/fai/type'
import { can } from '@layouts/plugins/casl'
import DefineAbilities from '@/plugins/casl/DefineAbilities'

definePage({
  meta: { action: 'list', subject: 'Qualita-Fai' },
})

const { t } = useI18n()
// eslint-disable-next-line @typescript-eslint/no-unused-vars
const router = useRouter()

// State Management
const itemsPerPage = ref(10)
const loading = ref(false)
const totalItems = ref(0)
const page = ref(1)
const sortBy = ref()
const orderBy = ref()
const serverItems = ref<Fai[]>([])
const proveTipoOptions = ref<any>([])

// Nuova referenza per catturare il file dall'input HTML/Vuetify
const fileSpecificaInput = ref<File | null>(null)

// Filters
const filters = ref({ ol: '', articolo: '' })

// Dialogs & UI States
const isFormValid = ref(false)
const isFormClodesValid = ref(false)
const isLoading = ref(false)

const dialogs = ref({
  edit: false,
  result: false,
  delete: false,
  info: false,
})

const snackbar = ref({ visible: false, message: '', color: '' })
const refForm = ref<VForm>()

// Options fissee
const selectedOptions = [
  { text: 'Positivo', value: 'POSITIVO' },
  { text: 'Negativo', value: 'NEGATIVO' },
  { text: 'Annullato', value: 'ANNULLATO' },
]

const fattibilitaOptions = [
  { text: 'Positivo', value: 'POSITIVO' },
  { text: 'Negativo', value: 'NEGATIVO' },
]

// Aggiornato defaultItem coerentemente con il backend
const defaultItem: any = {
  id: undefined, codice: '', data_inizio: moment().format('YYYY-MM-DD'),
  descrizione: '', esito_fattibilita: '', soggetto: '', articolo: '',
  specifica: '', specifica_id: '', ol: '', prove: [], esito: 'IN_CORSO', drive_id: '',
}

const editedItem = ref<any>({ ...defaultItem })
const editedIndex = ref(-1)

// Fetching con scompattamento compatto e sicuro
const loadItems = async () => {
  loading.value = true
  const { data: resultData } = await useApi<any>(createUrl('/qt/fai/list', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      ol: filters.value.ol,
      articolo: filters.value.articolo,
    },
  }))

  if (resultData.value) {
    const raw = resultData.value
    serverItems.value = Array.isArray(raw) ? raw : (raw.data || [])
    totalItems.value = raw.total || serverItems.value.length
  } else {
    serverItems.value = []
    totalItems.value = 0
  }
  loading.value = false
}

const updateOptions = (options: any) => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
  page.value = options.page
  itemsPerPage.value = options.itemsPerPage
  loadItems()
}

// Utility per mostrare notifiche
const showSnackbar = (msg: string, col = 'success') => {
  snackbar.value = { visible: true, message: msg, color: col }
}

// Gestione Dialoghi
const openDialog = (type: 'edit' | 'result' | 'delete' | 'info', item?: Fai) => {
  editedIndex.value = item ? serverItems.value.indexOf(item) : -1
  editedItem.value = item ? { ...item } : { ...defaultItem, prove: [] }
  fileSpecificaInput.value = null // Resetta il file caricato ad ogni apertura
  dialogs.value[type] = true
}

const closeDialogs = () => {
  dialogs.value = { edit: false, result: false, delete: false, info: false }
  isLoading.value = false
  fileSpecificaInput.value = null
  editedItem.value = { ...defaultItem }
}

// Actions API modificata per supportare multipart/form-data per l'upload della specifica
const save = async () => {
  if (!editedItem.value.ol || !editedItem.value.articolo)
    return
  isLoading.value = true

  const isEdit = !!editedItem.value.id

  // Creiamo il FormData
  const formData = new FormData()

  if (isEdit) {
    formData.append('_method', 'PUT')
  }

  // Campi standard
  formData.append('data_inizio', editedItem.value.data_inizio)
  formData.append('descrizione', editedItem.value.descrizione)
  formData.append('esito_fattibilita', editedItem.value.esito_fattibilita || '')
  formData.append('soggetto', editedItem.value.soggetto)
  formData.append('articolo', editedItem.value.articolo)
  formData.append('ol', editedItem.value.ol)
  formData.append('esito', editedItem.value.esito)

  // Array delle prove
  if (editedItem.value.prove && editedItem.value.prove.length) {
    editedItem.value.prove.forEach((id: string) => {
      formData.append('prove[]', id)
    })
  }

  /**
   * CORREZIONE VFILEINPUT VUETIFY:
   * Estraiamo il file reale sia se Vuetify lo passa come singolo oggetto,
   * sia se lo avvolge in un array (comportamento standard di VFileInput).
   */
  let fileDaCaricare: File | null = null

  if (Array.isArray(fileSpecificaInput.value) && fileSpecificaInput.value.length > 0) {
    fileDaCaricare = fileSpecificaInput.value[0]
  } else if (fileSpecificaInput.value instanceof File) {
    fileDaCaricare = fileSpecificaInput.value
  }

  // Se abbiamo trovato un file valido, lo appendiamo al FormData
  if (fileDaCaricare) {
    formData.append('file_specifica', fileDaCaricare)
  }

  const url = isEdit ? `/qt/fai/${editedItem.value.id}` : '/qt/fai/store'

  const returnData = await $api(url, {
    method: 'POST',
    body: formData,
  })

  if (returnData) {
    nextTick(() => { refForm.value?.reset(); refForm.value?.resetValidation() })
    await loadItems()
    closeDialogs()
    showSnackbar('Messaggi.Salvataggio-Completato')
  } else {
    isLoading.value = false
    showSnackbar('Messaggi.Errore-Salvataggio', 'error')
  }
}

const deleteItemConfirm = async () => {
  isLoading.value = true
  await $api(`/fais/${editedItem.value.id}`, { method: 'DELETE' })
  await loadItems()
  closeDialogs()
  showSnackbar('Messaggi.Eliminazione-Completata')
}

const closeFaiItem = async () => {
  if (!editedItem.value.esito) return
  const returnData = await $api(`/fais/${editedItem.value.id}`, {
    method: 'PUT',
    body: { esito: editedItem.value.esito },
  })

  if (returnData) {
    await loadItems()
    dialogs.value.result = false
    showSnackbar('Messaggi.Fai-Chiuso-Successo')
  }
}

// Chiamate asincrone di supporto
const getMateriale = async (ol: string) => {
  const resultData = await useApi<any>(createUrl(`/gp/getMateriale/${ol}`))
  if (resultData.data.value) editedItem.value.articolo = resultData.data.value.Prodotto
}

const getTipoProve = async () => {
  const { data: resultData } = await useApi<any>(createUrl('/qt/categorie/get_categorie', { query: { modulo: 1 } }))
  proveTipoOptions.value = resultData.value
}

// Badge & Mapping dei colori dello Status
const resolveStatus = (esito: string) => {
  const map: Record<string, { color: string; text: string }> = {
    POSITIVO: { color: 'success', text: 'Positivo' },
    NEGATIVO: { color: 'error', text: 'Negativo' },
    ANNULLATO: { color: 'secondary', text: 'Annullato' },
  }

  return map[esito] || { color: 'warning', text: 'In Corso' }
}

function openDrivePage(path: string) {
  window.open(`https://drive.google.com/drive/u/0/folders/${path}`, '_blank')
}

const visualizzaDettaglioFai = (item: Fai) => {
  router.push({
    name: 'quality-fai-view-id',
    params: { id: item.id }
  })
}

const headers = computed(() => [
  { title: t('Label.Fai'), key: 'codice' },
  { title: t('Label.Data Inizio'), key: 'data_inizio' },
  { title: t('Label.Risultato Fai'), key: 'esito' },
  { title: t('Label.Cliente/Fornitore'), key: 'soggetto' },
  { title: t('Label.Articolo'), key: 'articolo' },
  { title: t('Label.Numero Ordine'), key: 'ol' },
  { title: 'ACTIONS', key: 'actions', sortable: false, width: '140px' },
])

onMounted(getTipoProve)
</script>

<template>
  <VCol cols="12">
    <VSnackbar
      v-model="snackbar.visible"
      transition="scroll-y-reverse-transition"
      location="top center"
      :color="snackbar.color"
    >
      {{ $t(snackbar.message) }}
    </VSnackbar>

    <VCard :title="$t('Label.Lista-Fai')">

      <VCardText class="pb-2">
        <VRow class="align-center" dense>
          <VCol cols="12" sm="4" md="3">
            <AppTextField
              v-model="filters.ol"
              :label="$t('Label.Numero Ordine')"
              clearable
              density="compact"
              hide-details
              @focusout="loadItems"
            />
          </VCol>
          <VCol cols="12" sm="4" md="3">
            <AppTextField
              v-model="filters.articolo"
              :label="$t('Label.Articolo')"
              clearable
              density="compact"
              hide-details
              @focusout="loadItems"
            />
          </VCol>
          <VSpacer />
          <VCol cols="12" sm="auto" class="d-flex justify-end">
            <VBtn
              prepend-icon="tabler-plus"
              color="success"
              density="comfortable"
              @click="openDialog('edit')"
            >
              {{ $t('Label.Apri-Fai') }}
            </VBtn>
          </VCol>
        </VRow>
      </VCardText>

      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        :headers="headers"
        :items="serverItems"
        :items-length="totalItems"
        :loading="loading"
        density="compact"
        @update:options="updateOptions"
      >
        <template #item.codice="{ item }">
          <span
            class="text-sm font-weight-bold text-success cursor-pointer text-decoration-underline"
            title="Apri scheda di riepilogo e gestione prove"
            @click="visualizzaDettaglioFai(item)"
          >
            {{ item.codice }}
          </span>
        </template>

        <template #item.data_inizio="{ item }">
          {{ item.data_inizio ? moment(item.data_inizio).format('DD/MM/YYYY') : '-' }}
        </template>

        <template #item.esito="{ item }">
          <VChip
            :color="resolveStatus(item.esito).color"
            size="small"
            class="cursor-pointer"
            @click="item.esito === 'IN_CORSO' ? openDialog('result', item) : null"
          >
            {{ resolveStatus(item.esito).text }}
          </VChip>
        </template>

        <template #item.actions="{ item }">
          <div class="d-flex gap-1">
            <IconBtn
              :disabled="!item.drive_id"
              color="primary"
              size="small"
              @click="openDrivePage(item.drive_id)"
            >
              <VIcon icon="tabler-brand-google-drive" size="18" />
            </IconBtn>

            <IconBtn v-if="item.esito === 'IN_CORSO' && can(DefineAbilities.qt_checker_fai_edit.action, DefineAbilities.qt_checker_fai_edit.subject)" color="warning" size="small" @click="openDialog('edit', item)">
              <VIcon icon="tabler-edit" size="18" />
            </IconBtn>

            <IconBtn v-if="item.esito === 'IN_CORSO' && can(DefineAbilities.qt_checker_fai_deleted.action, DefineAbilities.qt_checker_fai_deleted.subject)" color="error" size="small" @click="openDialog('delete', item)">
              <VIcon icon="tabler-trash" size="18" />
            </IconBtn>
          </div>
        </template>
      </VDataTableServer>
    </VCard>
  </VCol>

  <VDialog v-model="dialogs.edit" max-width="900px" persistent>
    <AppCardActions v-model:loading="isLoading" :title="editedItem.id ? `${$t('Label.Modifica')} Fai` : `${$t('Label.Apertura')} Fai`" no-actions>
      <VCardText class="pt-4">
        <VForm ref="refForm" v-model="isFormValid">
          <VRow dense>
            <VCol cols="12" sm="6" md="3">
              <AppTextField v-model="editedItem.ol" :rules="[requiredValidator]" :maxlength="8" counter="8" :label="$t('Label.Numero Ordine (OL)')" @focusout="getMateriale(editedItem.ol)" />
            </VCol>
            <VCol cols="12" sm="6" md="3">
              <AppTextField v-model="editedItem.articolo" :rules="[requiredValidator]" :label="$t('Label.Articolo')" />
            </VCol>
            <VCol cols="12" sm="6" md="3">
              <AppTextField v-model="editedItem.soggetto" :rules="[requiredValidator]" :label="$t('Label.Cliente / Fornitore')" />
            </VCol>
            <VCol cols="12" sm="6" md="3">
              <AppDateTimePicker v-model="editedItem.data_inizio" :rules="[requiredValidator]" :label="$t('Label.Data Inizio Attività')" />
            </VCol>

            <!-- Sezione Specifica Tecnica Riallineata e Select Esame Fattibilità -->
            <VCol cols="12" sm="4" class="d-flex align-center">
              <VFileInput
                v-model="fileSpecificaInput"
                accept=".pdf,.doc,.docx,.jpg,.png"
                label="Carica Specifica Tecnica"
                density="compact"
                variant="outlined"
                prepend-icon="tabler-file-upload"
                class="mt-6"
                :persistent-hint="!!editedItem.specifica"
                :hint="editedItem.specifica ? `File attuale: ${editedItem.specifica}` : 'Nessun file caricato'"
              />
            </VCol>

            <VCol cols="12" sm="8">
              <AppSelect
                v-model="editedItem.esito_fattibilita"
                :items="fattibilitaOptions"
                item-title="text"
                item-value="value"
                clearable
                :label="$t('Label.Esito Esame Fattibilità')"
              />
            </VCol>
            <VCol cols="12">
              <AppSelect v-model="editedItem.prove" :items="proveTipoOptions" item-title="categoria" item-value="id" :label="$t('Label.Prove Richieste')" multiple chips clearable />
            </VCol>

            <VCol cols="12">
              <AppTextarea v-model="editedItem.descrizione" :rules="[requiredValidator]" :label="$t('Label.Descrizione')" :rows="3" />
            </VCol>
          </VRow>
        </VForm>
      </VCardText>
      <VCardActions class="pa-4">
        <VSpacer />
        <VBtn color="error" variant="outlined" @click="closeDialogs">Cancel</VBtn>
        <VBtn color="success" variant="elevated" @click="save">Save</VBtn>
      </VCardActions>
    </AppCardActions>
  </VDialog>

  <VDialog v-model="dialogs.result" max-width="450px">
    <VCard class="pa-4">
      <VCardTitle class="text-h5 text-center">{{ $t('Label.Chiudi-Fai') }}</VCardTitle>
      <VCardText>
        <VForm v-model="isFormClodesValid">
          <VAlert type="info" dense class="mb-4">{{ $t('Label.Fai-Numero') }}: <strong>{{ editedItem.codice }}</strong></VAlert>
          <AppSelect v-model="editedItem.esito" :rules="[requiredValidator]" :items="selectedOptions" item-title="text" item-value="value" :label="$t('Label.Risultato Finale')" />
        </VForm>
      </VCardText>
      <VCardActions>
        <VSpacer />
        <VBtn color="secondary" variant="outlined" @click="dialogs.result = false">Cancel</VBtn>
        <VBtn color="success" :disabled="!isFormClodesValid" @click="closeFaiItem">{{ $t('Label.Salva') }}</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <VDialog v-model="dialogs.delete" max-width="450px">
    <VCard class="pa-4">
      <VCardTitle class="text-h6 text-center">{{ $t('Messaggi.Conferma-Eliminazione-Fai') }}</VCardTitle>
      <VCardActions class="mt-4">
        <VSpacer />
        <VBtn color="error" variant="outlined" @click="closeDialogs">Cancel</VBtn>
        <VBtn color="success" variant="elevated" :loading="isLoading" @click="deleteItemConfirm">OK</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <InfoFaiDialog v-model:isDrawerOpen="dialogs.info" :fai-data="editedItem" />
</template>
