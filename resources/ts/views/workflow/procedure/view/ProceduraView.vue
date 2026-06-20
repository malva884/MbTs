<script setup lang="ts">
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import moment from 'moment'
import DefineAbilities from '@/plugins/casl/DefineAbilities'
import { can } from '@layouts/plugins/casl'
import type { Procedura } from '@/views/workflow/procedure/type'

interface Emit {
  (e: 'update:isDialogVisible', value: boolean): void
  (e: 'update:proceduraData', value: Procedura): void
  (e: 'padreData', value: Procedura): void
  (e: 'ufffciData', value: object): void
  (e: 'certificatiData', value: object): void
}

interface Props {
  proceduraData?: any
  padreData?: any
  isDialogVisible: boolean
  ufffciData: any
  certificatiData: any
}

const props = withDefaults(defineProps<Props>(), {
  proceduraData: () => ({}),
  padreData: () => ({}),
})

const emit = defineEmits<Emit>()
const { t } = useI18n()

// Stato Locale per evitare la mutazione diretta delle Props
const localProceduraData = ref<any>({})

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

const check_approver = async () => {
  if (!localProceduraData.value?.id) return

  const { data: resultData } = await useApi<any>(createUrl('/workflow/is_approver', {
    query: {
      id: localProceduraData.value.id,
      model_name: 'WfProcedure',
      date: localProceduraData.value.created_at,
      role: 'Approvatore',
    },
  }))

  is_approver.value = resultData.value.is_approver
  i_approved.value = resultData.value.i_approved
  info_approved.value = resultData.value.info_approved
  localProceduraData.value.role_id = resultData.value.role_id
}

const getDocument = async (modelId: string) => {
  const { data: resultData } = await useApi<any>(createUrl(`/workflow/procedure/documents/${modelId}`))
  documents.value = resultData.value || []
  view.value = true
}

const approvazione = async () => {
  loadingPage.value = true
  try {
    const returnData = await $api<any>('/workflow/procedure/approval', {
      method: 'POST',
      body: localProceduraData.value,
    })

    localProceduraData.value = {
      ...localProceduraData.value,
      id: returnData.obj.id,
      id_file_drive: returnData.obj.id_file_drive,
      procedura: returnData.obj.procedura,
      descrizione: returnData.obj.descrizione,
      created_at: returnData.obj.created_at,
      stato: returnData.obj.stato,
    }

    if (localProceduraData.value.stato === 'Approved' || returnData.obj === '0') {
      isDialogApprovedVisible.value = false // Sostituito con .value corretto
      emit('update:isDialogVisible', false)
      emit('update:proceduraData', returnData.obj)
      return
    }

    await openDocument(localProceduraData.value.id_file_drive)
    await getDocument(localProceduraData.value.id)
    isDialogApprovedVisible.value = false
  } catch (error) {
    console.error(error)
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

const close = () => {
  emit('update:isDialogVisible', false)
  emit('update:proceduraData', localProceduraData.value)
}

const resolveStato = (stato: string) => {
  if (stato === 'In-Approval')
    return { color: 'warning', text: 'In Presa Visone' }
  if (stato === 'Approved')
    return { color: 'success', text: 'Visionato' }
  return { color: 'secondary', text: stato || '--' }
}

function formatDate(date: string): string {
  if (!date) return '-'
  return moment(String(date)).format('YYYY-MM-DD')
}

watch(() => props.isDialogVisible, (newVal) => {
  if (newVal) {
    localProceduraData.value = JSON.parse(JSON.stringify(props.proceduraData || {}))
    view.value = false
    closed.value = false
    check_approver()

    if (localProceduraData.value.id !== undefined) {
      openDocument(localProceduraData.value.id_file_drive)
      getDocument(localProceduraData.value.id)
    }
  }
}, { immediate: true })
</script>

<template>
  <VDialog
    :model-value="props.isDialogVisible"
    fullscreen
    :scrim="true"
    transition="dialog-bottom-transition"
    class="custom-fullscreen-dialog"
  >
    <VCard class="d-flex flex-column h-100 opaque-view-card">
      <VToolbar color="primary" flat density="comfortable">
        <VBtn icon variant="plain" @click="close">
          <VIcon color="white" icon="tabler-x" />
        </VBtn>

        <VToolbarTitle class="text-body-1 font-weight-bold">
          {{ `${$t('Label.Documento')}: ${localProceduraData.procedura || ''}` }}
        </VToolbarTitle>

        <VSpacer />

        <VToolbarItems>
          <VBtn variant="text" class="font-weight-bold" @click="close">
            {{ $t('Label.Chiudi') }}
          </VBtn>
        </VToolbarItems>
      </VToolbar>

      <div class="flex-grow-1 overflow-hidden">
        <VRow no-gutters class="h-100">
          <VCol cols="12" md="9" class="h-100 d-flex flex-column border-e bg-black positional-fix">
            <div v-if="viewDocument" class="iframe-container flex-grow-1">
              <iframe
                :key="key"
                :src="`https://drive.google.com/file/d/${file_google_id}/preview#zoom=100`"
                allow="autoplay"
                class="w-100 h-100"
              />
            </div>
            <div v-else class="d-flex flex-column align-center justify-center flex-grow-1 text-white gap-2">
              <VProgressCircular indeterminate color="primary" />
              <span class="text-caption">Caricamento documento...</span>
            </div>
          </VCol>

          <VCol cols="12" md="3" class="h-100 overflow-y-auto pa-4 d-flex flex-column gap-4 sidebar-fix">

            <VCard variant="outlined" class="bg-surface">
              <div class="py-2 px-4 bg-header border-b text-caption font-weight-bold text-uppercase disabled-text">
                Dettagli Registro
              </div>
              <VCardText class="pa-3 text-body-2">
                <div class="mb-2">
                  <span class="text-caption text-disabled block text-uppercase font-weight-medium">Processo</span>
                  <span class="font-weight-medium text-high-emphasis">{{ props.padreData?.processo || '---' }}</span>
                </div>
                <div class="mb-2">
                  <span class="text-caption text-disabled block text-uppercase font-weight-medium">Procedura</span>
                  <span class="font-weight-medium text-high-emphasis">{{ props.padreData?.procedura || '---' }}</span>
                </div>
                <div class="mb-2">
                  <span class="text-caption text-disabled block text-uppercase font-weight-medium">Descrizione Padre</span>
                  <div class="bg-light pa-2 rounded mt-0.5 text-caption text-medium-emphasis">
                    {{ props.padreData?.descrizione || 'Nessuna descrizione.' }}
                  </div>
                </div>
                <div v-if="localProceduraData.id !== props.padreData?.id" class="mb-2 pt-2 border-top-dashed">
                  <span class="text-caption text-disabled block text-uppercase font-weight-medium">Documento Allegato</span>
                  <span class="font-weight-bold text-primary">{{ localProceduraData.procedura || '---' }}</span>
                </div>
                <div class="mb-2">
                  <span class="text-caption text-disabled block text-uppercase font-weight-medium">Descrizione Allegato</span>
                  <div class="bg-light pa-2 rounded mt-0.5 text-caption text-medium-emphasis">
                    {{ localProceduraData.descrizione || 'Nessuna descrizione.' }}
                  </div>
                </div>
                <div class="d-flex justify-space-between gap-2 border-top-dashed pt-2 mt-2">
                  <div>
                    <span class="text-caption text-disabled block text-uppercase font-weight-medium">Creazione</span>
                    <span class="text-high-emphasis">{{ formatDate(localProceduraData.created_at) }}</span>
                  </div>
                  <div>
                    <span class="text-caption text-disabled block text-uppercase font-weight-medium text-end">Stato</span>
                    <VChip :color="resolveStato(localProceduraData.stato).color" size="x-small" class="font-weight-bold mt-0.5">
                      {{ resolveStato(localProceduraData.stato).text }}
                    </VChip>
                  </div>
                </div>
              </VCardText>
            </VCard>

            <VCard variant="outlined" class="bg-surface">
              <VCardText class="pa-3 d-flex flex-column gap-3">
                <div>
                  <div class="text-caption text-disabled text-uppercase font-weight-medium mb-1">Uffici</div>
                  <div class="d-flex flex-wrap gap-1">
                    <VChip v-for="data in props.ufffciData" :key="data.id" size="x-small" color="warning" variant="tonal">
                      {{ data.ufficio }}
                    </VChip>
                    <span v-if="!props.ufffciData?.length" class="text-caption text-disabled">Nessuno</span>
                  </div>
                </div>
                <div>
                  <div class="text-caption text-disabled text-uppercase font-weight-medium mb-1">Certificati</div>
                  <div class="d-flex flex-wrap gap-1">
                    <VChip v-for="data in props.certificatiData" :key="data.id" size="x-small" color="success" variant="tonal">
                      {{ data.certificazione }}
                    </VChip>
                    <span v-if="!props.certificatiData?.length" class="text-caption text-disabled">Nessuno</span>
                  </div>
                </div>
              </VCardText>
            </VCard>

            <VCard variant="outlined" class="bg-surface">
              <VCardText class="pa-3 d-flex flex-column gap-2">
                <VBtn
                  v-if="(localProceduraData.stato !== 'Approved' && is_approver) && !i_approved"
                  block
                  color="success"
                  prepend-icon="tabler-circle-check"
                  size="small"
                  class="font-weight-bold"
                  @click="isDialogApprovedVisible = true"
                >
                  {{ $t('Button.Presa-Visione') }}
                </VBtn>

                <VBtn
                  v-if="localProceduraData?.id"
                  block
                  variant="tonal"
                  color="primary"
                  prepend-icon="tabler-brand-google-drive"
                  size="small"
                  @click="openDrivePage(localProceduraData.folder_drive)"
                >
                  {{ $t('Button.Google-Drive') }}
                </VBtn>
              </VCardText>
            </VCard>

            <VCard v-if="view" variant="outlined" class="flex-grow-1 d-flex flex-column overflow-hidden bg-surface">
              <div class="py-2 px-4 bg-header border-b text-caption font-weight-bold text-uppercase disabled-text">
                {{ $t('Label.File') }}
              </div>
              <div class="overflow-y-auto flex-grow-1">
                <VList v-model:selected="documentId" nav density="compact" class="bg-surface">
                  <VListItem
                    v-for="item in documents"
                    :key="item.id"
                    :value="item.id"
                    :active="file_google_id === item.id_file_drive"
                    color="primary"
                    @click="openDocument(item.id_file_drive)"
                  >
                    <template #prepend>
                      <VIcon icon="tabler-file" size="18" class="me-2" />
                    </template>
                    <VListItemTitle class="text-caption font-weight-medium text-truncate">
                      {{ item.nome_file }}
                    </VListItemTitle>
                  </VListItem>
                </VList>
              </div>
            </VCard>

          </VCol>
        </VRow>
      </div>
    </VCard>
  </VDialog>

  <VDialog
    v-model="isDialogApprovedVisible"
    persistent
    max-width="400"
    :z-index="2100"
  >
    <VCard title="Presa Visone Documento!">
      <VCardText class="text-body-2 pt-2">
        Vuoi confermare la presa visione del documento corrente? L'operazione non può essere annullata.
      </VCardText>
      <VCardText class="d-flex justify-end gap-2 pt-0">
        <VBtn color="secondary" variant="tonal" density="comfortable" @click="isDialogApprovedVisible = false">
          {{ $t('Button.Esci') }}
        </VBtn>
        <VBtn color="success" density="comfortable" class="font-weight-bold" @click="approvazione">
          {{ $t('Button.Presa-Visione') }}
        </VBtn>
      </VCardText>
    </VCard>
  </VDialog>

  <LoadingStandBy v-model="loadingPage" />
</template>

<style scoped lang="scss">
:deep(.custom-fullscreen-dialog) {
  z-index: 2000 !important;
  background-color: rgb(var(--v-theme-surface)) !important;
}

.opaque-view-card {
  background-color: rgb(var(--v-theme-background)) !important;
  position: relative;
  z-index: 1;
}

.positional-fix {
  position: relative;
  z-index: 2;
  background-color: #000000 !important;
  box-shadow: inset -1px 0 0 rgba(var(--v-border-color), 0.12);
}

.sidebar-fix {
  position: relative;
  z-index: 2;
  background-color: rgb(var(--v-theme-background)) !important;
}

.bg-header {
  background-color: rgba(var(--v-theme-on-surface), 0.02);
}

.bg-light {
  background-color: rgba(var(--v-theme-on-surface), 0.03);
}

.border-top-dashed {
  border-top: 1px dashed rgba(var(--v-border-color), 0.15) !important;
}

.gap-1 { gap: 4px; }
.gap-2 { gap: 8px; }
.gap-3 { gap: 12px; }
.gap-4 { gap: 16px; }
.block { display: block; }
.w-100 { width: 100%; }
.h-100 { height: 100%; }

.iframe-container {
  position: relative;
  width: 100%;
  height: 100%;
  z-index: 3;

  iframe {
    border: none;
    background: #1e1e1e;
    display: block;
    width: 100%;
    height: 100%;
  }
}
</style>
