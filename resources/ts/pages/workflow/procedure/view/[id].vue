<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import moment from 'moment'
import { can } from '@layouts/plugins/casl'
import DefineAbilities from '@/plugins/casl/DefineAbilities'
import ProceduraView from '@/views/workflow/procedure/view/ProceduraView.vue'

definePage({
  meta: {
    action: 'list',
    subject: 'Wf-Procedure',
  },
})

interface ProvaTipo {
  files_upload: []
}

const newItem = ref<ProvaTipo>({ files_upload: [] })
const { t } = useI18n()
const route = useRoute('workflow-procedure-view-id')
const loading = ref(true)
const isDialogLoading = ref(false)
const proceduraPadre = ref<any>({})
const serverItemsModuli = ref<any>([])
const serverItemsIstruzioni = ref<any>([])
const ufficiData = ref<any>([])
const certificatiData = ref<any>([])
const itemsPerPage = ref(-1)
const isSnackbarScrollReverseVisible = ref(false)
const color = ref('')
const message = ref('')
const proceduraVisibile = ref(false)
const proceduraData = ref({})
const isApprover = ref(false)
const certificatiOption = ref<any>([])
const ufficiOption = ref<any>([])
const certificatoFilter = ref<any>([])
const ufficioFilter = ref<any>([])

// Headers puliti (senza pulsanti invasivi di view)
const headers = computed(() => [
  { title: t('Table.Titolo'), key: 'procedura', width: '160px', sortable: false },
  { title: t('Table.Descrizione'), key: 'descrizione', sortable: false },
  { title: t('Table.Revisione'), key: 'revisione', width: '70px', sortable: false },
  { title: t('Table.Anno'), key: 'revisione_anno', width: '70px', sortable: false },
  { title: t('Table.Stato'), key: 'approval_action', width: '120px', sortable: false },
  { title: t('Table.Data-Creazione'), key: 'created_at', width: '110px', sortable: false },
  { title: 'ACTIONS', key: 'actions', width: '100px', align: 'center', sortable: false },
])

const loadItem = async () => {
  loading.value = true
  try {
    const { data: resultData } = await useApi<any>(createUrl(`/workflow/procedure/view/${route.params.id}`))
    proceduraPadre.value = resultData.value.obj || {}
    ufficiData.value = resultData.value.uffici || []
    certificatiData.value = resultData.value.certificati || []
    isApprover.value = resultData.value.is_approver || false
    await loadAllegati()
    if (isApprover.value && proceduraPadre.value.approval_action === null)
      openView(proceduraPadre.value)
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

const loadAllegati = async () => {
  loading.value = true
  try {
    const { data: resultData } = await useApi<any>(createUrl(`/workflow/procedure/allegati/${route.params.id}`, {
      query: {
        id: route.params.id,
        certificati: certificatoFilter.value,
        uffici: ufficioFilter.value,
      },
    }))
    serverItemsModuli.value = resultData.value.moduli || []
    serverItemsIstruzioni.value = resultData.value.istruzioni || []
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

const resolveStato = (stato: string) => {
  if (stato === 'In-Approval')
    return { color: 'warning', text: isApprover.value ? 'In Presa Visione' : 'In Approvazione' }
  if (stato === 'Approved')
    return { color: 'success', text: 'Visionato' }
  return { color: 'secondary', text: stato || '---' }
}

const openView = (proceduraOpen: object) => {
  proceduraData.value = proceduraOpen
  proceduraVisibile.value = true
}

function formatDate(date: string): string {
  return date ? moment(String(date)).format('YYYY-MM-DD') : '---'
}

function openDrivePage(path: string) {
  if (path) window.open(`https://drive.google.com/drive/u/0/folders/${path}`, '_blank')
}

const reload = () => { loadItem() }

const loadCertificati = async () => {
  const resultData = await useApi<any>(createUrl('/workflow/certificazioni/get_list'))
  certificatiOption.value = resultData.data.value || []
}

const loadUffici = async () => {
  const resultData = await useApi<any>(createUrl('/workflow/office/get_list'))
  ufficiOption.value = resultData.data.value || []
}

onMounted(() => {
  loadCertificati()
  loadUffici()
  loadItem()
})
</script>

<template>
  <div class="pa-4 standard-view">
    <VSnackbar
      v-model="isSnackbarScrollReverseVisible"
      transition="scroll-y-reverse-transition"
      location="top center"
      :color="color"
    >
      {{ $t(message) }}
    </VSnackbar>

    <div class="d-flex align-center justify-end gap-3 mb-5 filter-container-minimal">
      <div class="d-flex align-center gap-1-5 text-caption font-weight-bold text-disabled text-uppercase tracking-wider me-2">
        <VIcon icon="tabler-adjustments-horizontal" size="16" />
        Filtri
      </div>

      <div class="filter-select-wrapper">
        <AppSelect
          v-model="certificatoFilter"
          :items="certificatiOption"
          item-title="certificazione"
          item-value="id"
          placeholder="Tutti i Certificati"
          multiple
          clearable
          chips
          max-chips="1"
          density="compact"
          hide-details
          variant="solo-filled"
          flat
          prepend-inner-icon="tabler-certificate"
          class="elegant-select"
          @update:model-value="loadAllegati"
        />
      </div>

      <div class="filter-select-wrapper">
        <AppSelect
          v-model="ufficioFilter"
          :items="ufficiOption"
          item-title="ufficio"
          item-value="id"
          placeholder="Tutti gli Uffici"
          multiple
          clearable
          chips
          max-chips="1"
          density="compact"
          hide-details
          variant="solo-filled"
          flat
          prepend-inner-icon="tabler-building-fortress"
          class="elegant-select"
          @update:model-value="loadAllegati"
          :menu-props="{ contentClass: 'elegant-select-menu' }"
        />
      </div>
    </div>

    <VCard variant="outlined" class="mb-4 main-doc-card">
      <VCardItem class="py-3 px-4 bg-header">
        <VCardTitle class="text-body-1 font-weight-bold d-flex align-center gap-2">
          <VIcon icon="tabler-file-text" color="primary" size="22" />
          {{ $t('Label.Procedura') }}
        </VCardTitle>

        <template #append>
          <div class="d-flex gap-2">
            <VBtn
              v-if="can(DefineAbilities.wf_procedura_create.action, DefineAbilities.wf_procedura_create.subject)"
              prepend-icon="tabler-file-plus"
              variant="flat"
              color="success"
              density="comfortable"
              class="font-weight-bold"
              :to="{ name: 'workflow-procedure-add-id', params: { id: proceduraPadre.id } }"
            >
              Nuovo Documento
            </VBtn>
            <VBtn
              v-if="can(DefineAbilities.wf_procedura_create.action, DefineAbilities.wf_procedura_create.subject)"
              icon="tabler-message-up"
              variant="tonal"
              color="warning"
              density="comfortable"
            />
            <VBtn
              color="primary"
              icon="tabler-brand-google-drive"
              variant="tonal"
              density="comfortable"
              @click="openDrivePage(proceduraPadre.folder_drive)"
            />
          </div>
        </template>
      </VCardItem>
      <VDivider />

      <VCardText class="pa-4">
        <VRow>
          <VCol cols="12" sm="6" md="3">
            <div class="text-caption text-disabled text-uppercase font-weight-bold mb-1">Processo</div>
            <div class="text-body-1 font-weight-medium text-high-emphasis">{{ proceduraPadre.processo || '---' }}</div>
          </VCol>

          <VCol cols="12" sm="6" md="3">
            <div class="text-caption text-disabled text-uppercase font-weight-bold mb-1">Procedura</div>
            <div class="elegant-link-wrapper" @click="openView(proceduraPadre)">
              <span class="elegant-link-text">{{ proceduraPadre.procedura || '---' }}</span>
              <VIcon icon="tabler-arrow-up-right" size="16" class="elegant-link-icon text-primary" />
              <VTooltip activator="parent" location="top" open-delay="100">
                Clicca per visualizzare l'anteprima del documento
              </VTooltip>
            </div>
          </VCol>

          <VCol cols="12" sm="6" md="3">
            <div class="text-caption text-disabled text-uppercase font-weight-bold mb-1">Stato / Revisione</div>
            <div class="d-flex align-center gap-2">
              <VChip :color="resolveStato(proceduraPadre.stato).color" size="small" class="font-weight-bold">
                {{ resolveStato(proceduraPadre.stato).text }}
              </VChip>
              <span class="text-body-2 text-medium-emphasis">Rev. {{ proceduraPadre.revisione ?? 0 }} ({{ proceduraPadre.revisione_anno || '---' }})</span>
            </div>
          </VCol>

          <VCol cols="12" sm="6" md="3">
            <div class="text-caption text-disabled text-uppercase font-weight-bold mb-1">Data Creazione</div>
            <div class="text-body-1 font-weight-medium text-high-emphasis">{{ formatDate(proceduraPadre.created_at) }}</div>
          </VCol>

          <VCol cols="12" class="pt-2">
            <div class="text-caption text-disabled text-uppercase font-weight-bold mb-1">Descrizione</div>
            <div class="text-body-2 text-high-emphasis bg-desc pa-2 rounded">{{ proceduraPadre.description || proceduraPadre.descrizione || 'Nessuna descrizione presente.' }}</div>
          </VCol>
        </VRow>

        <div class="d-flex flex-wrap align-center gap-4 mt-4 pt-3 border-t">
          <div class="d-flex align-center flex-wrap gap-1">
            <span class="text-caption font-weight-bold text-medium-emphasis me-1">Uffici associati:</span>
            <VChip v-for="d in ufficiData" :key="d.id" size="small" color="warning" variant="tonal">{{ d.ufficio }}</VChip>
            <span v-if="!ufficiData.length" class="text-caption text-disabled">Nessuno</span>
          </div>
          <VDivider vertical class="hidden-xs-only mx-2" />
          <div class="d-flex align-center flex-wrap gap-1">
            <span class="text-caption font-weight-bold text-medium-emphasis me-1">Certificazioni:</span>
            <VChip v-for="d in certificatiData" :key="d.id" size="small" color="success" variant="tonal">{{ d.certificazione }}</VChip>
            <span v-if="!certificatiData.length" class="text-caption text-disabled">Nessuna</span>
          </div>
        </div>
      </VCardText>
    </VCard>

    <VRow>
      <VCol cols="12" lg="6">
        <VCard variant="outlined">
          <div class="py-2.5 px-4 bg-header d-flex align-center gap-2 border-b">
            <VIcon icon="tabler-clipboard-list" color="warning" size="20" />
            <span class="text-subtitle-2 font-weight-bold text-high-emphasis">{{ $t('Label.Istruzioni') }}</span>
          </div>

          <VDataTableServer
            :headers="headers"
            :items="serverItemsIstruzioni"
            :loading="loading"
            density="compact"
            class="table-layout-fixed elegant-table"
          >
            <template #item.procedura="{ item }">
              <div class="d-flex align-center style-title-cell row-interactive-link" @click="openView(item)">
                <VIcon icon="tabler-file-search" size="16" class="me-2 link-indicator-icon text-primary" />
                <span class="font-weight-bold text-truncate text-primary text-link-label">
                  {{ item.procedura }}
                </span>
                <VTooltip activator="parent" location="top" open-delay="100">
                  Clicca per visualizzare il documento
                </VTooltip>
              </div>
            </template>

            <template #item.descrizione="{ item }">
              <div class="text-truncate style-desc-cell text-medium-emphasis">
                <span>{{ item.descrizione || '---' }}</span>
                <VTooltip v-if="item.descrizione" activator="parent" location="top" open-delay="150">
                  {{ item.descrizione }}
                </VTooltip>
              </div>
            </template>

            <template #item.approval_action="{ item }">
              <VChip :color="resolveStato(item.approval_action === 'Approved' ? item.approval_action : item.stato).color" size="small" class="font-weight-bold">
                {{ resolveStato(item.approval_action === 'Approved' ? item.approval_action : item.stato).text }}
              </VChip>
            </template>

            <template #item.created_at="{ item }">
              <span class="text-body-2">{{ formatDate(item.created_at) }}</span>
            </template>

            <template #item.actions="{ item }">
              <div class="d-flex justify-center gap-1">
                <VBtn v-if="!item.id_log_drive && can(DefineAbilities.wf_procedura_create.action, DefineAbilities.wf_procedura_create.subject)" icon="tabler-edit" variant="text" color="error" size="small" :to="{ name: 'workflow-procedure-edit-id', params: { id: item.id } }" />
                <VBtn v-if="can(DefineAbilities.wf_procedura_create.action, DefineAbilities.wf_procedura_create.subject)" icon="tabler-message-up" variant="text" color="warning" size="small" />
                <VBtn icon="tabler-brand-google-drive" variant="text" color="primary" size="small" @click.stop="openDrivePage(item.folder_drive)" />
              </div>
            </template>
          </VDataTableServer>
        </VCard>
      </VCol>

      <VCol cols="12" lg="6">
        <VCard variant="outlined">
          <div class="py-2.5 px-4 bg-header d-flex align-center gap-2 border-b">
            <VIcon icon="tabler-folders" color="success" size="20" />
            <span class="text-subtitle-2 font-weight-bold text-high-emphasis">{{ $t('Label.Moduli') }}</span>
          </div>

          <VDataTableServer
            v-model:items-per-page="itemsPerPage"
            :headers="headers"
            :items="serverItemsModuli"
            :loading="loading"
            density="compact"
            class="table-layout-fixed elegant-table"
          >
            <template #item.procedura="{ item }">
              <div class="d-flex align-center style-title-cell row-interactive-link" @click="openView(item)">
                <VIcon icon="tabler-file-search" size="16" class="me-2 link-indicator-icon text-primary" />
                <span class="font-weight-bold text-truncate text-primary text-link-label">
                  {{ item.procedura }}
                </span>
                <VTooltip activator="parent" location="top" open-delay="100">
                  Clicca per visualizzare il documento
                </VTooltip>
              </div>
            </template>

            <template #item.descrizione="{ item }">
              <div class="text-truncate style-desc-cell text-medium-emphasis">
                <span>{{ item.descrizione || '---' }}</span>
                <VTooltip v-if="item.descrizione" activator="parent" location="top" open-delay="150">
                  {{ item.descrizione }}
                </VTooltip>
              </div>
            </template>

            <template #item.approval_action="{ item }">
              <VChip :color="resolveStato(item.approval_action === 'Approved' ? item.approval_action : item.stato).color" size="small" class="font-weight-bold">
                {{ resolveStato(item.approval_action === 'Approved' ? item.approval_action : item.stato).text }}
              </VChip>
            </template>

            <template #item.created_at="{ item }">
              <span class="text-body-2">{{ formatDate(item.created_at) }}</span>
            </template>

            <template #item.actions="{ item }">
              <div class="d-flex justify-center gap-1">
                <VBtn v-if="!item.id_log_drive && can(DefineAbilities.wf_procedura_create.action, DefineAbilities.wf_procedura_create.subject)" icon="tabler-edit" variant="text" color="error" size="small" :to="{ name: 'workflow-procedure-edit-id', params: { id: item.id } }" />
                <VBtn v-if="can(DefineAbilities.wf_procedura_create.action, DefineAbilities.wf_procedura_create.subject)" icon="tabler-message-up" variant="text" color="warning" size="small" />
                <VBtn icon="tabler-brand-google-drive" variant="text" color="primary" size="small" @click.stop="openDrivePage(item.folder_drive)" />
              </div>
            </template>
          </VDataTableServer>
        </VCard>
      </VCol>
    </VRow>

    <VDialog v-model="isDialogLoading" persistent width="280">
      <VCard color="primary" dark class="pa-4 text-center">
        <div class="text-body-2 font-weight-bold text-white mb-2">Caricamento in corso...</div>
        <VProgressLinear indeterminate color="white" height="3" class="mb-0" />
      </VCard>
    </VDialog>

    <ProceduraView
      v-model:isDialogVisible="proceduraVisibile"
      :procedura-data="proceduraData"
      :padre-data="proceduraPadre"
      :ufffci-data="ufficiData"
      :certificati-data="certificatiData"
      @update:procedura-data="reload"
    />
  </div>
</template>

<style scoped lang="scss">
.standard-view {
  .gap-1 { gap: 4px; }
  .gap-1-5 { gap: 6px; } // Modificato qui (sostituito il punto con il trattino)
  .gap-2 { gap: 8px; }
  .gap-3 { gap: 12px; }
  .gap-4 { gap: 16px; }

  .border-t { border-top: 1px solid rgba(var(--v-border-color), 0.08) !important; }
  .border-b { border-bottom: 1px solid rgba(var(--v-border-color), 0.08) !important; }

  .bg-header { background-color: rgba(var(--v-theme-on-surface), 0.015); }
  .bg-desc { background-color: rgba(var(--v-theme-on-surface), 0.02); }

  // Layout Contenitore Filtri Sottili ed Eleganti
  .filter-container-minimal {
    padding: 2px 4px;

    .tracking-wider {
      letter-spacing: 0.05em;
    }
  }

  .filter-select-wrapper {
    width: 210px; // Dimensione controllata ed elegante
    transition: width 0.2s ease;

    &:focus-within {
      width: 240px; // Si espande leggermente e morbidamente durante l'uso
    }
  }

  // Personalizzazione dell'input Vuetify per renderlo leggero
  :deep(.elegant-select) {
    .v-field {
      border-radius: 8px !important;
      background-color: rgba(var(--v-theme-on-surface), 0.03) !important;
      font-size: 0.825rem !important;
      font-weight: 500;
      transition: background-color 0.2s;

      &:hover {
        background-color: rgba(var(--v-theme-on-surface), 0.05) !important;
      }

      &.v-field--focused {
        background-color: rgb(var(--v-theme-surface)) !important;
        box-shadow: 0 0 0 1px rgb(var(--v-theme-primary)) !important;
      }
    }

    .v-field__input {
      padding-top: 6px !important;
      padding-bottom: 6px !important;
      min-height: 32px !important;
    }

    .v-chip {
      height: 20px !important;
      font-size: 0.75rem !important;
      margin: 2px !important;
    }

    .v-field__clearable, .v-field__append-inner {
      font-size: 0.8rem;
      .v-icon { size: 14px; }
    }

    .v-field__prepend-inner {
      padding-top: 6px !important;
      opacity: 0.6;
      .v-icon { font-size: 16px !important; }
    }
  }

  .main-doc-card {
    border-left: 4px solid rgb(var(--v-theme-primary)) !important;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }

  // Wrapper Elegante Link del Documento Padre
  .elegant-link-wrapper {
    display: inline-flex;
    align-items: center;
    cursor: pointer;
    position: relative;
    padding: 2px 0;

    .elegant-link-text {
      color: rgb(var(--v-theme-primary));
      font-weight: 600;
      font-size: 1.05rem;
      border-bottom: 1px dashed rgba(var(--v-theme-primary), 0.4);
      transition: all 0.2s ease-in-out;
    }

    .elegant-link-icon {
      opacity: 0;
      transform: translate(-3px, 3px);
      transition: all 0.2s ease-in-out;
      margin-left: 4px;
    }

    &:hover {
      .elegant-link-text {
        color: rgba(var(--v-theme-primary), 0.85);
        border-bottom: 1px solid rgb(var(--v-theme-primary));
      }
      .elegant-link-icon {
        opacity: 1;
        transform: translate(0, 0);
      }
    }
  }

  // Righe interattive ed eleganti per i link delle tabelle
  .row-interactive-link {
    cursor: pointer;
    width: 100%;
    display: inline-flex;
    align-items: center;

    .link-indicator-icon {
      opacity: 0.4;
      transform: scale(0.9);
      transition: all 0.2s ease;
    }

    .text-link-label {
      transition: color 0.2s ease;
    }

    &:hover {
      .link-indicator-icon {
        opacity: 1;
        transform: scale(1.1);
      }
      .text-link-label {
        color: rgba(var(--v-theme-primary), 0.75) !important;
        text-decoration: underline;
      }
    }
  }

  // Struttura tabelle
  .elegant-table {
    :deep(tr:hover) {
      background-color: rgba(var(--v-theme-primary), 0.015) !important;
    }
  }

  .table-layout-fixed {
    :deep(table) {
      table-layout: fixed !important;
      width: 100% !important;
    }
  }

  .style-title-cell { max-width: 180px; }
  .style-desc-cell { max-width: 220px; }
}
</style>
