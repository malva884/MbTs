<script setup lang="ts">
import { ref, watch, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import moment from 'moment'
import DefineAbilities from '@/plugins/casl/DefineAbilities'
import { can } from '@layouts/plugins/casl'
import type { Commessa } from '@/views/workflow/commesse/type'

interface Emit {
  (e: 'update:isDialogVisible', value: boolean): void
  (e: 'update:commessaData', value: Commessa): void
}

interface Props {
  commessaData?: any
  isDialogVisible: boolean
}

const props = defineProps<Props>()
const emit = defineEmits<Emit>()
const { t } = useI18n()

const isDialogApprovedVisible = ref(false)
const documents = ref<any>([])
const documentId = ref('')
const closed = ref(false)
const view = ref(false)
const viewDocument = ref(false)
const loadingPage = ref(false)
const key = ref(1)
const file_google_id = ref<string | null>(null)
const is_approver = ref(false)
const i_approved = ref(true)
const info_approved = ref({})
const role_id = ref(null)

const checkboxContent = [
  {
    title: 'Firma tutti i documenti in Approvazione',
    subtitle: '',
    desc: '',
    value: 'all',
  },
]

const selectedCheckbox = ref([])

const check_approver = async () => {
  if (!props.commessaData?.id) return
  try {
    const { data: resultData } = await useApi<any>(createUrl('/workflow/is_approver', {
      query: {
        id: props.commessaData.id,
        model_name: 'WfOrder',
        date: props.commessaData.created_at,
        role: 'Approvatore',
      },
    }))

    is_approver.value = resultData.value.is_approver
    i_approved.value = resultData.value.i_approved
    info_approved.value = resultData.value.info_approved

    // eslint-disable-next-line vue/no-mutating-props
    props.commessaData.role_id = resultData.value.role_id
  } catch (e) {
    console.error(e)
  }
}

const getDocument = async (modelId: string) => {
  try {
    const { data: resultData } = await useApi<any>(createUrl(`/workflow/commesse/document/${modelId}`))
    documents.value = resultData.value || []
    view.value = true
  } catch (e) {
    console.error(e)
  }
}

const approvazione = async () => {
  loadingPage.value = true
  try {
    const retuenData = await $api<any>('/workflow/commesse/approval', {
      method: 'POST',
      body: props.commessaData,
    })

    props.commessaData.id = retuenData.obj.id
    props.commessaData.id_file_drive = retuenData.obj.id_file_drive
    props.commessaData.commessa = retuenData.obj.commessa
    props.commessaData.tipologia = retuenData.obj.tipologia
    props.commessaData.created_at = retuenData.obj.created_at
    props.commessaData.stato = retuenData.obj.stato
    props.commessaData.data_approvazione = retuenData.obj.data_approvazione

    if (selectedCheckbox.value[0] === undefined || props.commessaData.stato === 'Approved' || retuenData.obj === "0") {
      isDialogApprovedVisible.value = false
      removeKeyboardListener() // Pulisce l'ascoltatore tastiera
      emit('update:isDialogVisible', false)
      emit('update:commessaData', retuenData.obj)
    }

    openDocument(props.commessaData.id_file_drive)
    getDocument(props.commessaData.id)
    isDialogApprovedVisible.value = false
  } catch (e) {
    console.error(e)
  } finally {
    loadingPage.value = false
  }
}

const openDocument = async (id_file: string) => {
  viewDocument.value = false
  file_google_id.value = id_file
  key.value = key.value + 1
  viewDocument.value = true
}

function openDrivePage(id_folder: string) {
  if (id_folder) window.open(`https://drive.google.com/drive/u/0/folders/${id_folder}`, '_blank')
}

// 👉 Logica di chiusura centralizzata
const close = () => {
  isDialogApprovedVisible.value = false
  removeKeyboardListener() // Rimuove l'evento globale per non lasciare hook appesi
  emit('update:isDialogVisible', false)
  emit('update:commessaData', true)
}

// 👉 Gestore globale della tastiera (Intercetta l'ESC ovunque nella finestra)
const handleEscKey = (event: KeyboardEvent) => {
  if (event.key === 'Escape' || event.key === 'Esc') {
    close()
  }
}

const addKeyboardListener = () => {
  window.addEventListener('keydown', handleEscKey, true) // Usiamo 'true' per la fase di cattura prioritaria
}

const removeKeyboardListener = () => {
  window.removeEventListener('keydown', handleEscKey, true)
}

// Rimuove l'evento se il componente viene smontato improvvisamente
onUnmounted(() => {
  removeKeyboardListener()
})

const resolveStato = (stato: string) => {
  if (stato === 'In-Approval')
    return { color: 'warning', text: 'In Approvazione' }
  if (stato === 'Approved')
    return { color: 'success', text: 'Approvato' }
  return { color: 'secondary', text: stato || '--' }
}

const resolveType = (tipologia: number) => {
  if (tipologia == 1)
    return { color: 'primary', text: 'Commessa' }
  if (tipologia == 3)
    return { color: 'error', text: 'Revisione' }
  return { color: 'secondary', text: '--' }
}

function formatDate(date: string): string {
  if (!date) return '-'
  return moment(String(date)).format('DD/MM/YYYY')
}

const userOpenFile = async (id: string) => {
  await $api(`/workflow/commesse/userOpenFile/${id}`, {
    method: 'POST',
  })
}

// Attiva e disattiva l'ascoltatore globale in base alla visibilità reale del dialog
watch(() => props.isDialogVisible, (newVal) => {
  if (newVal) {
    view.value = false
    check_approver()
    closed.value = false
    openDocument(props.commessaData?.id_file_drive)
    getDocument(props.commessaData?.id)
    addKeyboardListener() // Attiva la cattura del tasto ESC
  } else {
    removeKeyboardListener() // Disattiva la cattura
  }
}, { immediate: true })
</script>

<template>
  <VDialog
    :model-value="props.isDialogVisible"
    fullscreen
    :scrim="false"
    transition="dialog-bottom-transition"
    @update:model-value="(val) => { if(!val) close() }"
  >
    <VCard class="elegant-commessa-dialog bg-background">
      <VToolbar color="surface" elevation="1" class="border-b px-2">
        <VBtn
          icon="tabler-x"
          variant="text"
          color="medium-emphasis"
          density="comfortable"
          @click="close"
        />

        <VToolbarTitle class="text-body-1 font-weight-bold d-flex align-center gap-2 ps-2">
          <VIcon icon="tabler-briefcase" color="primary" size="20" />
          <span> {{ `${$t('Label.Commessa')} - ${props.commessaData?.commessa || '---'}` }}</span>
        </VToolbarTitle>

        <VSpacer />

        <VBtn
          variant="tonal"
          color="secondary"
          density="comfortable"
          class="font-weight-bold text-caption px-4"
          @click="close"
        >
          {{ $t('Label.Chiudi') }}
        </VBtn>
      </VToolbar>

      <div class="workspace-container">
        <div class="document-viewer-panel">
          <div v-if="viewDocument" class="iframe-wrapper">
            <iframe
              :key="key"
              :src="`https://drive.google.com/file/d/${file_google_id}/preview`"
              allow="autoplay"
            />
          </div>
          <div v-else class="d-flex flex-column align-center justify-center h-100 pa-5 text-disabled">
            <VProgressCircular indeterminate color="primary" class="mb-2" size="28" width="3" />
            <span class="text-caption">Caricamento anteprima in corso...</span>
          </div>
        </div>

        <div class="metadata-sidebar border-l bg-surface pa-4">
          <div class="sidebar-scroller">

            <VCard variant="outlined" class="mb-4 form-section-card">
              <div class="py-2 px-3 bg-header d-flex align-center gap-2 border-b">
                <VIcon icon="tabler-info-circle" color="primary" size="16" />
                <span class="text-caption font-weight-bold text-high-emphasis">Dettagli Commessa</span>
              </div>

              <VCardText class="pa-3">
                <table class="w-100 metadata-table text-body-2">
                  <tbody>
                  <tr>
                    <td class="text-disabled font-weight-medium py-1">Codice:</td>
                    <td class="text-high-emphasis font-weight-bold text-end py-1">{{ props.commessaData?.commessa }}</td>
                  </tr>
                  <tr>
                    <td class="text-disabled font-weight-medium py-1">Tipologia:</td>
                    <td class="text-end py-1">
                      <VChip :color="resolveType(props.commessaData?.tipologia).color" size="x-small" class="font-weight-bold">
                        {{ resolveType(props.commessaData?.tipologia).text }}
                      </VChip>
                    </td>
                  </tr>
                  <tr>
                    <td class="text-disabled font-weight-medium py-1">Creazione:</td>
                    <td class="text-medium-emphasis text-end py-1">{{ formatDate(props.commessaData?.created_at) }}</td>
                  </tr>
                  <tr>
                    <td class="text-disabled font-weight-medium py-1">Stato:</td>
                    <td class="text-end py-1">
                      <VChip :color="resolveStato(props.commessaData?.stato).color" size="x-small" variant="flat" class="font-weight-bold">
                        {{ resolveStato(props.commessaData?.stato).text }}
                      </VChip>
                    </td>
                  </tr>
                  <tr>
                    <td class="text-disabled font-weight-medium py-1">Approvazione:</td>
                    <td class="text-medium-emphasis text-end py-1">{{ formatDate(props.commessaData?.data_approvazione) }}</td>
                  </tr>
                  </tbody>
                </table>
              </VCardText>
            </VCard>

            <VCard
              v-if="(props.commessaData?.stato !== 'Approved' && is_approver) && !i_approved"
              variant="outlined"
              class="mb-4 border-warning-light"
            >
              <VCardText class="pa-3 bg-warning-lightest">
                <CustomCheckboxes
                  v-model:selected-checkbox="selectedCheckbox"
                  :checkbox-content="checkboxContent"
                  :grid-column="{ sm: '12', cols: '12' }"
                  class="compact-checkboxes"
                />
              </VCardText>
            </VCard>

            <div class="d-flex flex-column gap-2 mb-4">
              <VBtn
                v-if="(props.commessaData?.stato !== 'Approved' && is_approver) && !i_approved"
                block
                color="success"
                prepend-icon="tabler-circle-check"
                variant="flat"
                class="font-weight-bold"
                @click="isDialogApprovedVisible = true"
              >
                {{ $t('Button.Approva') }}
              </VBtn>

              <VBtn
                v-if="props.commessaData?.id"
                block
                color="primary"
                variant="tonal"
                prepend-icon="tabler-brand-google-drive"
                class="font-weight-bold"
                @click="openDrivePage(props.commessaData.folder_drive)"
              >
                {{ $t('Button.Google-Drive') }}
              </VBtn>
            </div>

            <VCard v-if="view && documents.length" variant="outlined" class="form-section-card">
              <div class="py-2 px-3 bg-header d-flex align-center gap-2 border-b">
                <VIcon icon="tabler-paperclip" color="primary" size="16" />
                <span class="text-caption font-weight-bold text-high-emphasis">{{ $t('Label.File') }} Associati</span>
              </div>

              <VCardText class="pa-1">
                <VList
                  v-model:selected="documentId"
                  nav
                  density="compact"
                  class="bg-transparent elegant-sidebar-list"
                >
                  <VListItem
                    v-for="item in documents"
                    :key="item.id"
                    :value="item.id"
                    color="primary"
                    class="rounded-lg mb-1"
                    @click="openDocument(item.id_file_drive)"
                  >
                    <template #prepend>
                      <VIcon
                        icon="tabler-file-text"
                        size="18"
                        class="me-2"
                        :class="item.tipologia == 55 ? 'text-warning font-weight-bold' : 'text-disabled'"
                      />
                    </template>

                    <VListItemTitle
                      class="text-caption font-weight-medium text-wrap"
                      :class="item.tipologia == 55 ? 'text-warning font-weight-bold custom-highlight-text' : ''"
                    >
                      {{ item.nome_file }}
                    </VListItemTitle>

                    <template #append>
                      <VBtn
                        icon="tabler-writing"
                        variant="text"
                        size="x-small"
                        color="secondary"
                        @click.stop="userOpenFile(item.id)"
                      />
                    </template>
                  </VListItem>
                </VList>
              </VCardText>
            </VCard>

          </div>
        </div>
      </div>
    </VCard>
  </VDialog>

  <VDialog
    v-model="isDialogApprovedVisible"
    persistent
    width="320"
  >
    <VCard class="rounded-xl">
      <VCardItem class="pt-4 px-4 text-center">
        <VIcon icon="tabler-shield-check" color="success" size="36" class="mb-2" />
        <VCardTitle class="text-body-1 font-weight-bold">Approvazione Giroposta</VCardTitle>
      </VCardItem>

      <VCardText class="text-center text-body-2 text-medium-emphasis px-4 pb-4">
        Vuoi procedere con l'approvazione formale di questa commessa?
      </VCardText>

      <VDivider />

      <div class="d-flex align-center justify-end gap-2 pa-3 bg-header">
        <VBtn
          color="secondary"
          variant="text"
          size="small"
          class="font-weight-bold"
          @click="isDialogApprovedVisible = false"
        >
          Annulla
        </VBtn>
        <VBtn
          color="success"
          variant="flat"
          size="small"
          class="font-weight-bold px-4"
          @click="approvazione"
        >
          Sì, Approva
        </VBtn>
      </div>
    </VCard>
  </VDialog>

  <LoadingStandBy v-model="loadingPage" />
</template>

<style scoped lang="scss">
.elegant-commessa-dialog {
  overflow: hidden;
  height: 100vh;
  display: flex;
  flex-direction: column;

  .gap-2 { gap: 8px; }
  .gap-3 { gap: 12px; }

  .border-b { border-bottom: 1px solid rgba(var(--v-border-color), 0.08) !important; }
  .border-l { border-left: 1px solid rgba(var(--v-border-color), 0.08) !important; }
  .bg-header { background-color: rgba(var(--v-theme-on-surface), 0.015); }

  .workspace-container {
    display: flex;
    flex: 1;
    overflow: hidden;
    height: calc(100vh - 52px);
  }

  .document-viewer-panel {
    flex: 1;
    background-color: #1e1e1e;
    position: relative;
    height: 100%;

    .iframe-wrapper {
      width: 100%;
      height: 100%;

      iframe {
        display: block;
        background: #1e1e1e;
        border: none;
        height: 100%;
        width: 100%;
      }
    }
  }

  .metadata-sidebar {
    width: 390px;
    height: 100%;
    display: flex;
    flex-direction: column;
    overflow: hidden;

    .sidebar-scroller {
      flex: 1;
      overflow-y: auto;
      padding-right: 2px;
    }
  }

  .form-section-card {
    border-radius: 8px;
    background-color: rgb(var(--v-theme-surface));
  }

  .metadata-table {
    border-collapse: collapse;
    tr {
      border-bottom: 1px dashed rgba(var(--v-border-color), 0.04);
      &:last-child {
        border-bottom: none;
      }
    }
  }

  .bg-warning-lightest {
    background-color: rgba(var(--v-theme-warning), 0.04) !important;
  }
  .border-warning-light {
    border-color: rgba(var(--v-theme-warning), 0.2) !important;
    border-radius: 8px;
  }

  .elegant-sidebar-list {
    .v-list-item--selected {
      background-color: rgba(var(--v-theme-primary), 0.08) !important;
      color: rgb(var(--v-theme-primary)) !important;
    }
  }

  .custom-highlight-text {
    color: #ffb100 !important;
    text-shadow: 0 0 1px rgba(0,0,0,0.1);
  }
}

:deep(.compact-checkboxes) {
  .v-selection-control {
    min-height: 32px !important;
  }
  .v-label {
    font-size: 0.8rem !important;
    font-weight: 600;
  }
}
</style>
