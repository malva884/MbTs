<script setup lang="ts">
import { ref, watch, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import moment from 'moment'
import type { Variazione } from '@/views/workflow/variazioni/type'
import { can } from '@layouts/plugins/casl'
import DefineAbilities from '@/plugins/casl/DefineAbilities'

interface Emit {
  (e: 'update:isDialogVisible', value: boolean): void
  (e: 'update:variazioneData', value: Variazione): void
}

interface Props {
  variazioneData?: any
  isDialogVisible: boolean
}

const props = defineProps<Props>()
const emit = defineEmits<Emit>()
const { t } = useI18n()

const isDialogApprovedVisible = ref(false)
const documents = ref<any>([])
const documentId = ref('')
const view = ref(false)
const viewDocument = ref(false)
const loadingPage = ref(false)
const key = ref(1)
const file_google_id = ref<string | null>(null)
const is_approver = ref(false)
const i_approved = ref(true)
const info_approved = ref<any>({})
const role_id = ref(null)
const viewed = ref(false)
const approvals = ref<any[]>([])
const viewers = ref<any[]>([])
const detail = ref<any>({})

const check_approver = async () => {
  if (!props.variazioneData?.id)
    return
  try {
    const { data: resultData } = await useApi<any>(createUrl('/workflow/is_approver', {
      query: {
        id: props.variazioneData.id,
        model_name: 'WfVariations',
        date: props.variazioneData.created_at,
        role: 'Approvatore',
      },
    }))

    is_approver.value = resultData.value.is_approver
    i_approved.value = resultData.value.i_approved
    info_approved.value = resultData.value.info_approved

    // eslint-disable-next-line vue/no-mutating-props
    props.variazioneData.role_id = resultData.value.role_id
  }
  catch (e) {
    console.error(e)
  }
}

const getDetail = async (id: string) => {
  try {
    const { data: resultData } = await useApi<any>(createUrl(`/workflow/variazioni/view/${id}`))

    if (resultData.value?.obj) {
      detail.value = resultData.value.obj
      approvals.value = resultData.value.obj.approvals || []
      viewers.value = resultData.value.obj.viewers || []
      viewed.value = !!props.variazioneData?.viewed
    }
  }
  catch (e) {
    console.error(e)
  }
}

const getDocument = async (modelId: string) => {
  try {
    const { data: resultData } = await useApi<any>(createUrl(`/workflow/variazioni/document/${modelId}`))

    documents.value = resultData.value || []
    view.value = true
  }
  catch (e) {
    console.error(e)
  }
}

const approvazione = async () => {
  loadingPage.value = true
  try {
    const retuenData = await $api<any>('/workflow/variazioni/approval', {
      method: 'POST',
      body: props.variazioneData,
    })

    if (retuenData.obj && retuenData.obj !== '0') {
      props.variazioneData.id = retuenData.obj.id
      props.variazioneData.id_file_drive = retuenData.obj.id_file_drive
      props.variazioneData.ol = retuenData.obj.ol
      props.variazioneData.stato = retuenData.obj.stato
      props.variazioneData.data_approvazione = retuenData.obj.data_approvazione
    }

    isDialogApprovedVisible.value = false
    removeKeyboardListener()
    emit('update:isDialogVisible', false)
    emit('update:variazioneData', retuenData.obj)
  }
  catch (e) {
    console.error(e)
  }
  finally {
    loadingPage.value = false
  }
}

const toggleViewed = async () => {
  try {
    await $api<any>('/workflow/variazioni/viewed', {
      method: 'POST',
      body: { id: props.variazioneData.id, viewed: viewed.value },
    })
  }
  catch (e) {
    console.error(e)
  }
}

const chiudiVariazione = async () => {
  loadingPage.value = true
  try {
    const retuenData = await $api<any>(`/workflow/variazioni/end/${props.variazioneData.id}`)

    if (retuenData?.id_log_drive)
      openDocument(retuenData.id_log_drive)

    await getDocument(props.variazioneData.id)
    emit('update:variazioneData', props.variazioneData)
  }
  catch (e) {
    console.error(e)
  }
  finally {
    loadingPage.value = false
  }
}

const rigeneraLog = async () => {
  loadingPage.value = true
  try {
    const retuenData = await $api<any>(`/workflow/variazioni/log/${props.variazioneData.id}`)

    if (retuenData?.id_log_drive)
      openDocument(retuenData.id_log_drive)

    await getDocument(props.variazioneData.id)
  }
  catch (e) {
    console.error(e)
  }
  finally {
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
  if (id_folder)
    window.open(`https://drive.google.com/drive/u/0/folders/${id_folder}`, '_blank')
}

// 👉 Logica di chiusura centralizzata
const close = () => {
  isDialogApprovedVisible.value = false
  removeKeyboardListener()
  emit('update:isDialogVisible', false)
  emit('update:variazioneData', true)
}

// 👉 Gestore globale della tastiera (Intercetta l'ESC ovunque nella finestra)
const handleEscKey = (event: KeyboardEvent) => {
  if (event.key === 'Escape' || event.key === 'Esc')
    close()
}

const addKeyboardListener = () => {
  window.addEventListener('keydown', handleEscKey, true)
}

const removeKeyboardListener = () => {
  window.removeEventListener('keydown', handleEscKey, true)
}

onUnmounted(() => {
  removeKeyboardListener()
})

const resolveStato = (stato: string) => {
  if (stato === 'In-Approval')
    return { color: 'warning', text: 'In Approvazione' }
  if (stato === 'Approved')
    return { color: 'success', text: 'Approvata' }
  if (stato === 'End')
    return { color: 'secondary', text: 'Conclusa' }

  return { color: 'secondary', text: stato || '--' }
}

function formatDate(date: string): string {
  if (!date)
    return '-'

  return moment(String(date)).format('DD/MM/YYYY')
}

function formatDateTime(date: string): string {
  if (!date)
    return '-'

  return moment(String(date)).format('DD/MM/YYYY HH:mm')
}

const userOpenFile = async (id: string) => {
  await $api(`/workflow/variazioni/userOpenFile/${id}`, {
    method: 'POST',
  })
}

// Attiva e disattiva l'ascoltatore globale in base alla visibilità reale del dialog
watch(() => props.isDialogVisible, newVal => {
  if (newVal) {
    view.value = false
    file_google_id.value = null
    viewDocument.value = false
    check_approver()
    openDocument(props.variazioneData?.id_file_drive)
    getDocument(props.variazioneData?.id)
    getDetail(props.variazioneData?.id)
    addKeyboardListener()
  }
  else {
    removeKeyboardListener()
  }
}, { immediate: true })
</script>

<template>
  <VDialog
    :model-value="props.isDialogVisible"
    fullscreen
    :scrim="false"
    transition="dialog-bottom-transition"
    @update:model-value="(val) => { if (!val) close() }"
  >
    <VCard class="elegant-variazione-dialog bg-background">
      <VToolbar
        color="surface"
        elevation="1"
        class="border-b px-2"
      >
        <VBtn
          icon="tabler-x"
          variant="text"
          color="medium-emphasis"
          density="comfortable"
          @click="close"
        />

        <VToolbarTitle class="text-body-1 font-weight-bold d-flex align-center gap-2 ps-2">
          <VIcon
            icon="tabler-exchange"
            color="primary"
            size="20"
          />
          <span>{{ `${$t('Label.Variazione')} - OL ${props.variazioneData?.ol || '---'}` }}</span>
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
          <div
            v-if="viewDocument && file_google_id"
            class="iframe-wrapper"
          >
            <iframe
              :key="key"
              :src="`https://drive.google.com/file/d/${file_google_id}/preview`"
              allow="autoplay"
            />
          </div>
          <div
            v-else-if="!file_google_id"
            class="h-100 overflow-y-auto pa-6"
          >
            <div class="text-caption text-medium-emphasis mb-3 text-center">
              Anteprima file non disponibile — documento ricostruito:
            </div>

            <div class="variazione-doc mx-auto">
              <div class="doc-header">
                <div class="doc-col">
                  <h5>Metallurgica</h5>
                  <h5>Bresciana S.p.a</h5>
                  <h5>Ufficio Commerciale</h5>
                </div>
                <div class="doc-col doc-col-title">
                  <h2>Comunicazione di variazione</h2>
                  <h2>ordine di produzione</h2>
                </div>
                <div class="doc-col doc-col-right">
                  <h5>Mod. Var-Com</h5>
                  <h5>Rev. {{ props.variazioneData?.revisione }}</h5>
                  <h5>Foglio 1</h5>
                </div>
              </div>

              <div class="doc-body">
                <p class="doc-rif">
                  Rif.: Ordine di produzione n.
                  <span class="doc-ol">{{ props.variazioneData?.ol }}</span>
                </p>
                <p>Oggetto della variazione:</p>
                <hr />
                <div
                  class="doc-text"
                  v-html="detail?.testo || '&mdash;'"
                />
              </div>

              <div class="doc-footer">
                <p>Il contratto &egrave; stato rilasciato</p>
                <p>secondo le modalit&agrave; della Proc.</p>
                <p>3Cq-012 - Per U.C.:</p>
                <p><span class="doc-underline">&nbsp;Nicoletta Piccinelli&nbsp;</span> Data: <span class="doc-underline">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></p>
              </div>

              <div
                v-if="detail?.folder_drive"
                class="text-center mt-6"
              >
                <VBtn
                  variant="tonal"
                  size="small"
                  color="primary"
                  class="text-none"
                  prepend-icon="tabler-folder-open"
                  @click="openDrivePage(detail.folder_drive)"
                >
                  Apri cartella su Drive
                </VBtn>
              </div>
            </div>
          </div>
          <div
            v-else
            class="d-flex flex-column align-center justify-center h-100 pa-5 text-disabled"
          >
            <VProgressCircular
              indeterminate
              color="primary"
              class="mb-2"
              size="28"
              width="3"
            />
            <span class="text-caption">Caricamento anteprima in corso...</span>
          </div>
        </div>

        <div class="metadata-sidebar border-l bg-surface pa-4">
          <div class="sidebar-scroller">
            <VCard
              variant="outlined"
              class="mb-4 form-section-card"
            >
              <div class="py-2 px-3 bg-header d-flex align-center gap-2 border-b">
                <VIcon
                  icon="tabler-info-circle"
                  color="primary"
                  size="16"
                />
                <span class="text-caption font-weight-bold text-high-emphasis">{{ $t('Label.Dettagli') }}</span>
              </div>

              <VCardText class="pa-3">
                <table class="w-100 metadata-table text-body-2">
                  <tbody>
                    <tr>
                      <td class="text-disabled font-weight-medium py-1">OL:</td>
                      <td class="text-high-emphasis font-weight-bold text-end py-1">
                        {{ props.variazioneData?.ol }}
                      </td>
                    </tr>
                    <tr>
                      <td class="text-disabled font-weight-medium py-1">{{ $t('Label.Revisione') }}:</td>
                      <td class="text-high-emphasis font-weight-bold text-end py-1">
                        {{ props.variazioneData?.revisione }}
                      </td>
                    </tr>
                    <tr>
                      <td class="text-disabled font-weight-medium py-1">{{ $t('Label.Categoria') }}:</td>
                      <td class="text-medium-emphasis text-end py-1">
                        {{ detail?.categoria || '-' }}
                      </td>
                    </tr>
                    <tr>
                      <td class="text-disabled font-weight-medium py-1">{{ $t('Label.Creato-Da') }}:</td>
                      <td class="text-medium-emphasis text-end py-1">
                        {{ detail?.creator_name || '-' }}
                      </td>
                    </tr>
                    <tr>
                      <td class="text-disabled font-weight-medium py-1">{{ $t('Label.Creazione') }}:</td>
                      <td class="text-medium-emphasis text-end py-1">
                        {{ formatDate(props.variazioneData?.created_at) }}
                      </td>
                    </tr>
                    <tr>
                      <td class="text-disabled font-weight-medium py-1">{{ $t('Label.Stato') }}:</td>
                      <td class="text-end py-1">
                        <VChip
                          :color="resolveStato(props.variazioneData?.stato).color"
                          size="x-small"
                          variant="flat"
                          class="font-weight-bold"
                        >
                          {{ resolveStato(props.variazioneData?.stato).text }}
                        </VChip>
                      </td>
                    </tr>
                    <tr>
                      <td class="text-disabled font-weight-medium py-1">{{ $t('Label.Approvazione') }}:</td>
                      <td class="text-medium-emphasis text-end py-1">
                        {{ formatDate(props.variazioneData?.data_approvazione) }}
                      </td>
                    </tr>
                    <tr>
                      <td class="text-disabled font-weight-medium py-1">{{ $t('Label.Chiusura') }}:</td>
                      <td class="text-medium-emphasis text-end py-1">
                        {{ formatDate(props.variazioneData?.end_date) }}
                      </td>
                    </tr>
                    <tr v-if="detail.is_viewer">
                      <td class="text-disabled font-weight-medium py-1">{{ $t('Label.Visualizzata') }}:</td>
                      <td class="text-end py-1">
                        <VCheckbox
                          v-model="viewed"
                          density="compact"
                          hide-details
                          class="d-inline-flex"
                          @update:model-value="toggleViewed"
                        />
                      </td>
                    </tr>
                  </tbody>
                </table>
              </VCardText>
            </VCard>

            <div class="d-flex flex-column gap-2 mb-4">
              <VBtn
                v-if="(props.variazioneData?.stato === 'In-Approval' && is_approver) && !i_approved"
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
                v-if="props.variazioneData?.stato === 'Approved'"
                block
                color="warning"
                prepend-icon="tabler-file-check"
                variant="flat"
                class="font-weight-bold"
                @click="chiudiVariazione"
              >
                {{ $t('Button.Chiudi-Variazione') }}
              </VBtn>

              <VBtn
                v-if="props.variazioneData?.id && can(DefineAbilities.wf_variazioni_create.action, DefineAbilities.wf_variazioni_create.subject)"
                block
                color="secondary"
                variant="tonal"
                prepend-icon="tabler-file-description"
                class="font-weight-bold"
                @click="rigeneraLog"
              >
                {{ $t('Button.Rigenera-Log') }}
              </VBtn>

              <VBtn
                v-if="props.variazioneData?.folder_drive"
                block
                color="primary"
                variant="tonal"
                prepend-icon="tabler-brand-google-drive"
                class="font-weight-bold"
                @click="openDrivePage(props.variazioneData.folder_drive)"
              >
                {{ $t('Button.Google-Drive') }}
              </VBtn>
            </div>

            <!-- Approvatori -->
            <VCard
              v-if="approvals.length"
              variant="outlined"
              class="mb-4 form-section-card"
            >
              <div class="py-2 px-3 bg-header d-flex align-center gap-2 border-b">
                <VIcon
                  icon="tabler-writing-sign"
                  color="primary"
                  size="16"
                />
                <span class="text-caption font-weight-bold text-high-emphasis">{{ $t('Label.Approvatori') }}</span>
              </div>

              <VCardText class="pa-2">
                <VList
                  nav
                  density="compact"
                  class="bg-transparent"
                >
                  <VListItem
                    v-for="item in approvals"
                    :key="item.id || item.user_id"
                  >
                    <template #prepend>
                      <VIcon
                        icon="tabler-circle-check"
                        size="18"
                        color="success"
                        class="me-2"
                      />
                    </template>
                    <VListItemTitle class="text-caption font-weight-medium">
                      {{ item.full_name }}
                    </VListItemTitle>
                    <template #append>
                      <span class="text-caption text-disabled">{{ formatDateTime(item.created_at) }}</span>
                    </template>
                  </VListItem>
                </VList>
              </VCardText>
            </VCard>

            <!-- Visualizzatori -->
            <VCard
              v-if="viewers.length"
              variant="outlined"
              class="mb-4 form-section-card"
            >
              <div class="py-2 px-3 bg-header d-flex align-center gap-2 border-b">
                <VIcon
                  icon="tabler-eye"
                  color="primary"
                  size="16"
                />
                <span class="text-caption font-weight-bold text-high-emphasis">{{ $t('Label.Visualizzatori') }}</span>
              </div>

              <VCardText class="pa-2">
                <VList
                  nav
                  density="compact"
                  class="bg-transparent"
                >
                  <VListItem
                    v-for="item in viewers"
                    :key="item.user_id"
                  >
                    <template #prepend>
                      <VIcon
                        :icon="item.viewed ? 'tabler-eye-check' : 'tabler-eye-off'"
                        size="18"
                        :color="item.viewed ? 'success' : 'disabled'"
                        class="me-2"
                      />
                    </template>
                    <VListItemTitle class="text-caption font-weight-medium">
                      {{ item.full_name }}
                    </VListItemTitle>
                    <template #append>
                      <span class="text-caption text-disabled">{{ formatDateTime(item.data_view) }}</span>
                    </template>
                  </VListItem>
                </VList>
              </VCardText>
            </VCard>

            <!-- File associati -->
            <VCard
              v-if="view && documents.length"
              variant="outlined"
              class="form-section-card"
            >
              <div class="py-2 px-3 bg-header d-flex align-center gap-2 border-b">
                <VIcon
                  icon="tabler-paperclip"
                  color="primary"
                  size="16"
                />
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
                        :class="item.tipologia == 100 ? 'text-success font-weight-bold' : 'text-disabled'"
                      />
                    </template>

                    <VListItemTitle
                      class="text-caption font-weight-medium text-wrap"
                      :class="item.tipologia == 100 ? 'text-success font-weight-bold' : ''"
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
        <VIcon
          icon="tabler-shield-check"
          color="success"
          size="36"
          class="mb-2"
        />
        <VCardTitle class="text-body-1 font-weight-bold">
          {{ $t('Label.Approvazione-Variazione') }}
        </VCardTitle>
      </VCardItem>

      <VCardText class="text-center text-body-2 text-medium-emphasis px-4 pb-4">
        {{ $t('Label.Approvazione-Variazione-Testo') }}
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
          {{ $t('Label.Annulla') }}
        </VBtn>
        <VBtn
          color="success"
          variant="flat"
          size="small"
          class="font-weight-bold px-4"
          @click="approvazione"
        >
          {{ $t('Button.Approva') }}
        </VBtn>
      </div>
    </VCard>
  </VDialog>

  <LoadingStandBy v-model="loadingPage" />
</template>

<style scoped lang="scss">
.elegant-variazione-dialog {
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

    .variazione-doc {
      max-width: 800px;
      background: #fff;
      color: #555;
      font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
      font-size: 15px;
      line-height: 24px;
      padding: 30px 40px;
      border: 1px solid #eee;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.35);

      .doc-header {
        display: flex;
        gap: 16px;

        .doc-col { flex: 1; }
        .doc-col-title h2 {
          text-align: center;
          font-size: 20px;
          margin: 0;
          color: #333;
        }
        .doc-col-right { text-align: right; }
        h5 { margin: 0; font-size: 14px; }
      }

      .doc-body {
        margin-block-start: 40px;
        margin-inline-start: 5%;

        .doc-rif { margin-block-end: 20px; }
        .doc-ol {
          text-decoration: underline;
          font-size: 20px;
          margin-inline-start: 150px;
          padding: 0 12px;
        }
        hr { border: none; border-top: 1px solid #ddd; }

        .doc-text {
          :deep(p) { margin-block-end: 0.6em; }
          :deep(ul), :deep(ol) { padding-inline-start: 1.4rem; }
        }
      }

      .doc-footer {
        margin-block-start: 60px;
        text-align: right;

        p { font-size: 12px; line-height: 1.6; margin: 0; }
        .doc-underline { text-decoration: underline; }
      }
    }

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

  .elegant-sidebar-list {
    .v-list-item--selected {
      background-color: rgba(var(--v-theme-primary), 0.08) !important;
      color: rgb(var(--v-theme-primary)) !important;
    }
  }
}
</style>
