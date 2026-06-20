<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { useI18n } from 'vue-i18n'
import type { VForm } from 'vuetify/components/VForm'
import moment from 'moment/moment'
import { can } from '@layouts/plugins/casl'
import DefineAbilities from '@/plugins/casl/DefineAbilities'
import CommessaView from '@/views/workflow/commesse/view/CommessaView.vue'

definePage({
  meta: {
    action: 'list',
    subject: 'Wf-Commesse',
  },
})

const { t } = useI18n()
const itemsPerPage = ref(10)
const loading = ref(true)
const refForm = ref<VForm>()
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const commessaFilter = ref('')
const tipologiaFilter = ref()
const viewFilter = ref(1)
const page = ref(1)
const serverItems = ref<any>([])
const isSnackbarScrollReverseVisible = ref(false)
const message = ref('')
const color = ref('')
const commessaVisibile = ref(false)
const commessaData = ref({})
const isApprover = ref(false)
const optionViewFilter = []
const checkFilter = ref(false)

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

  const { data: resultData } = await useApi<any>(createUrl('/workflow/commesse/', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      commessa: commessaFilter.value,
      tipologia: tipologiaFilter.value,
      view: viewFilter.value,
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
  { title: t('Label.Commessa'), key: 'commessa' },
  { title: t('Label.Tipologia'), key: 'tipologia' },
  { title: t('Label.Revisione'), key: 'revisione' },
  { title: t('Label.Del'), key: 'created_at' },
  { title: t('Label.Stato'), key: 'stato' },
  { title: 'ACTIONS', key: 'actions', sortable: false },
]

const reload = () => {
  loadItems()
}

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

const openView = (commessa: object) => {
  commessaData.value = commessa
  commessaVisibile.value = true
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
          <!-- 👉 Commessa -->
          <VCol
            cols="12"
            sm="4"
          >
            <AppTextField
              v-model="commessaFilter"
              :label="$t('Label.Commessa')"
              clearable
              clear-icon="tabler-x"
              @focusout="loadItems"
            />
          </VCol>

          <!-- 👉 Tipologia -->
          <VCol
            cols="12"
            sm="4"
          >
            <AppSelect
              v-model="tipologiaFilter"
              :label="$t('Label.Tipologia')"
              :placeholder="$t('Tipologia.Moduli')"
              :items="[{ id: null, tipologia: 'Tutti' }, { id: 1, tipologia: 'Commessi' }, { id: 3, tipologia: 'Revisioni' }]"

              item-title="tipologia"
              item-value="id"
              clearable
              clear-icon="tabler-x"
              @focusout="loadItems"
            />
          </VCol>

          <!-- 👉 Firma -->
          <VCol
            v-if="checkFilter"
            cols="12"
            sm="4"
          >
            <AppSelect
              v-model="viewFilter"
              :label="$t('Label.Visualizza')"
              :placeholder="$t('Tipologia.Visualizza')"
              :items="optionViewFilter"
              item-title="tipologia"
              item-value="id"
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
        <template #item.commessa="{ item }">
          <div class="d-flex align-center">
            <div class="d-flex align-center">
              <div class="d-flex flex-column">
                <h6 class="text-base">
                  <RouterLink
                    to=""
                    class="font-weight-medium text-link"
                    @click="openView(item)"
                  >
                    {{ item.commessa }}
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
              v-if="can(DefineAbilities.wf_commessse_list.action, DefineAbilities.wf_commessse_list.subject)"
              color="primary"
              @click="openDrivePage(item.folder_drive)"
            >
              <VIcon icon="tabler-brand-google-drive" />
            </IconBtn>
          </div>
        </template>
      </VDataTableServer>
    </VCard>
  </VCol>

  <CommessaView
    v-model:isDialogVisible="commessaVisibile"
    :commessa-data="commessaData"
    @update:commessa-data="reload"
  />
</template>
