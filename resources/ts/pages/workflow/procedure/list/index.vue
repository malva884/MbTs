<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { useI18n } from 'vue-i18n'
import moment from 'moment/moment'
import { can } from '@layouts/plugins/casl'
import DefineAbilities from '@/plugins/casl/DefineAbilities'
import ProceduraView from '@/views/workflow/procedure/view/ProceduraView.vue'

definePage({
  meta: {
    action: 'list',
    subject: 'Wf-Procedure',
  },
})

const { t } = useI18n()
const itemsPerPage = ref(10)
const loading = ref(true)
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const proceduraFilter = ref('')
const tipologiaFilter = ref()
const viewFilter = ref(1)
const page = ref(1)
const serverItems = ref<any>([])
const isSnackbarScrollReverseVisible = ref(false)
const message = ref('')
const color = ref('')
const proceduraVisibile = ref(false)
const proceduraData = ref({})
const isApprover = ref(false)
const optionViewFilter = []
const checkFilter = ref(false)
const certificatiOption = ref<any>([])
const ufficiOption = ref<any>([])
const certificatoFilter = ref<any>([])
const ufficioFilter = ref<any>([])

const updateOptions = (options: any) => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
  page.value = options.page
  itemsPerPage.value = options.itemsPerPage

  // eslint-disable-next-line @typescript-eslint/no-use-before-define
  loadItems()
}

const loadItems = async () => {
  loading.value = true

  const { data: resultData } = await useApi<any>(createUrl('/workflow/procedure/', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      certificati: [certificatoFilter.value],
      uffici: [ufficioFilter.value],
    },
  }))

  if (resultData.value !== null) {
    serverItems.value = resultData.value.objs.data
    totalItems.value = resultData.value.objs.total
    isApprover.value = resultData.value.is_approver
  }
  else {
    serverItems.value = []
    totalItems.value = 0
    isApprover.value = false
  }

  if (isApprover.value)
    optionViewFilter.push({ id: 1, tipologia: 'Da Firmare' }, { id: 2, tipologia: 'Firmati' }, { id: 3, tipologia: 'Tutti' })
  else
    optionViewFilter.push({ id: 1, tipologia: 'Da Approvare' }, { id: 2, tipologia: 'Approvati' }, { id: 3, tipologia: 'Tutti' })

  loading.value = false
  checkFilter.value = true
}

// headers
const headers = [
  { title: t('Label.Processo'), key: 'categoria' },
  { title: t('Label.Descrizione'), key: 'descrizione' },
  { title: t('Label.Revisione'), key: 'revisione' },
  { title: t('Label.Anno'), key: 'revisione_anno' },
  { title: 'ACTIONS', key: 'actions', sortable: false },
]

const reload = () => {
  loadItems()
}

const loadCertificati = async () => {
  const resultData = await useApi<any>(createUrl('/workflow/certificazioni/get_list'))

  certificatiOption.value = resultData.data
}

const loadUffici = async () => {
  const resultData = await useApi<any>(createUrl('/workflow/office/get_list'))

  ufficiOption.value = resultData.data
}

loadCertificati()
loadUffici()

function openDrivePage(path: string) {
  window.open(`https://drive.google.com/drive/u/0/folders/${path}`, '_blank')
}

const resolveType = (tipologia: number) => {
  if (tipologia == 1)
    return { color: 'primary', text: 'Commessa' }
  else if (tipologia == 3)
    return { color: 'error', text: 'Revisione' }
  else
    return { color: '', text: '--' }
}

const resolveStato = (stato: string, statoUser: string) => {
  if (isApprover.value !== true && stato === 'In-Approval') { return { color: 'warning', text: 'In Approvazione' } }
  else if (isApprover.value !== true && stato === 'Approved') { return { color: 'success', text: 'Approvato' } }
  else if (viewFilter.value == 2 && isApprover.value === true) { return { color: 'success', text: 'Firmato' } }
  else if (viewFilter.value == 1 && isApprover.value === true) { return { color: 'warning', text: 'Da Firmare' } }
  else if (viewFilter.value == 3 && isApprover.value === true) {
    if (stato === 'In-Approval' && statoUser === '')
      return { color: 'warning', text: 'Da Firmare' }
    else if ( stato === 'Approved')
      return { color: 'success', text: 'Firmato' }
    else
      return { color: '', text: '--------' }
  }
  else { return { color: '', text: '--' } }
}

function formatDate(date: string): string {
  return moment(String(date)).format('MM/DD/YYYY')
}

const stampa = async () => {

  const resultData = await $api('/export/procedure/export', {
    method: 'POST',
  })

  const blob = resultData
  const link = document.createElement('a')

  link.href = URL.createObjectURL(blob)
  link.download = 'Procedure.xlsx'
  link.click()
  URL.revokeObjectURL(link.href)
}

const openView = (procedura: object) => {
  proceduraData.value = procedura
  proceduraVisibile.value = true
}

</script>

<template>
  <VCol cols="12">
    <VCard
      title="Filters"
      class="mb-6"
    >
      <VCardText>
        <VRow>
          <VCol
            cols="12"
            sm="4"
          >
            <AppSelect
              v-model="certificatoFilter"
              :items="certificatiOption.value"
              :item-title="item => item.certificazione"
              :item-value="item => item.id"
              :label="$t('Label.Certificati')"
              :placeholder="$t('Label.Certificati')"
              multiple
              clearable
              clear-icon="tabler-x"
              @focusout="loadItems"
            />
          </VCol>

          <VCol
            cols="12"
            sm="4"
          >
            <AppSelect
              v-model="ufficioFilter"
              :items="ufficiOption.value"
              :item-title="item => item.ufficio"
              :item-value="item => item.id"
              :label="$t('Label.Uffici')"
              :placeholder="$t('Label.Uffici')"
              multiple
              clearable
              clear-icon="tabler-x"
              @focusout="loadItems"
            />
          </VCol>

        </VRow>
      </VCardText>
    </VCard>
    <VCard>
      <VCardText class="d-flex flex-wrap py-4 gap-4">
        <VSnackbar
          v-model="isSnackbarScrollReverseVisible"
          transition="scroll-y-reverse-transition"
          location="top central"
          :color="color"
        >
          {{ $t(message) }}
        </VSnackbar>
        <div class="app-user-search-filter d-flex align-center flex-wrap gap-4">
          <!-- 👉 Add user button -->
          <VBtn
            v-if="can(DefineAbilities.wf_procedura_create.action, DefineAbilities.wf_procedura_create.subject)"
            prepend-icon="tabler-plus"
            color="success"
            :to="{ name: 'workflow-procedure-add-add' }"
          >
            Nuova Procedura
          </VBtn>
        </div>
        <VCol cols="2" class="d-flex ">
          <div class="app-user-search-filter d-flex align-center">
            <!-- 👉 Export button -->
            <VBtn
              v-if="can(DefineAbilities.wf_procedura_create.action, DefineAbilities.wf_procedura_create.subject)"
              variant="tonal"
              color="secondary"
              prepend-icon="tabler-screen-share"
              @click="stampa"
            >
              Export
            </VBtn>
          </div>
        </VCol>
      </VCardText>
      <!-- 👉 Datatable  -->
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        :headers="headers"
        :items="serverItems"
        :items-length="totalItems"
        :loading="loading"
        @update:options="updateOptions"
      >
        <template #item.categoria="{ item }">
          <div class="d-flex align-center">
            <div class="d-flex align-center">
              <div class="d-flex flex-column">
                <h6 class="text-base">
                  <RouterLink
                    :to="{ name: 'workflow-procedure-view-id', params: { id: item.id } }"
                    class="font-weight-medium text-link"
                  >
                    {{ item.categoria }}
                  </RouterLink>
                </h6>
              </div>
            </div>
          </div>
        </template>

        <template #item.tipologia="{ item }">
          <div
            v-if="item.tipologia"
            class="d-flex gap-1"
          >
            <VChip
              :color="resolveType(item.tipologia).color"
              size="small"
            >
              {{ resolveType(item.tipologia).text }}
            </VChip>
          </div>
        </template>

        <template #item.stato="{ item }">
          <div
            v-if="item.stato"
            class="d-flex gap-1"
          >
            <VChip
              v-if="isApprover"
              :color="resolveStato(item.approval_action).color"
              size="small"
            >
              {{ resolveStato(item.approval_action).text }}
            </VChip>
            <VChip
              v-else
              :color="resolveStato(item.stato).color"
              size="small"
            >
              {{ resolveStato(item.stato).text }}
            </VChip>
          </div>
        </template>

        <template #item.created_at="{ item }">
          <div class="d-flex gap-1">
            {{ formatDate(item.created_at) }}
          </div>
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }">
          <div class="d-flex gap-1">
            <IconBtn
              color="primary"
              @click="openDrivePage(item.folder_drive_padre)"
            >
              <VIcon icon="tabler-brand-google-drive" />
            </IconBtn>
          </div>
        </template>
      </VDataTableServer>
    </VCard>
  </VCol>

  <ProceduraView
    v-model:isDialogVisible="proceduraVisibile"
    :procedura-data="proceduraData"
    @update:procedura-data="reload"
  />
</template>
