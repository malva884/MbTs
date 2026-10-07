<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { useI18n } from 'vue-i18n'
import moment from 'moment/moment'
import { can } from '@layouts/plugins/casl'
import DefineAbilities from '@/plugins/casl/DefineAbilities'
import VariazioneView from '@/views/workflow/variazioni/view/VariazioneView.vue'

definePage({
  meta: {
    action: 'list',
    subject: 'Wf-Variazioni',
  },
})

const { t } = useI18n()
const router = useRouter()
const itemsPerPage = ref(10)
const loading = ref(true)
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const olFilter = ref('')
const statoFilter = ref()
const viewFilter = ref(3)
const visualizFilter = ref()
const page = ref(1)
const serverItems = ref<any>([])
const variazioneVisibile = ref(false)
const variazioneData = ref({})
const isApprover = ref(false)
const isViewer = ref(false)
const optionViewFilter = ref<any[]>([])
const checkFilter = ref(false)
const isSnackbarVisible = ref(false)
const message = ref('')
const color = ref('')

const updateOptions = (options: any) => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
  page.value = options.page
  itemsPerPage.value = options.itemsPerPage

  loadItems()
}

const loadItems = async () => {
  loading.value = true

  const { data: resultData } = await useApi<any>(createUrl('/workflow/variazioni/', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      ol: olFilter.value,
      stato: statoFilter.value,
      view: viewFilter.value,
      visualiz: visualizFilter.value,
    },
  }))

  if (resultData.value !== null) {
    serverItems.value = resultData.value.objs.data
    totalItems.value = resultData.value.objs.total
    isApprover.value = resultData.value.is_approver
    isViewer.value = !!resultData.value.is_viewer
  }
  else {
    serverItems.value = []
    totalItems.value = 0
    isApprover.value = false
    isViewer.value = false
  }

  optionViewFilter.value = isApprover.value
    ? [{ id: 1, tipologia: 'Da Firmare' }, { id: 2, tipologia: 'Firmati' }, { id: 3, tipologia: 'Tutti' }]
    : [{ id: 1, tipologia: 'In Approvazione' }, { id: 2, tipologia: 'Firmati da me' }, { id: 3, tipologia: 'Tutti' }]

  loading.value = false
  checkFilter.value = true
}

// headers: la colonna 'Visualizzata' è riservata a chi ha il ruolo Visualizzatore
const headers = computed(() => [
  { title: 'OL', key: 'ol' },
  { title: t('Label.Revisione'), key: 'revisione' },
  { title: t('Label.Del'), key: 'created_at' },
  { title: t('Label.Stato'), key: 'stato' },
  ...(isViewer.value ? [{ title: t('Label.Visualizzata'), key: 'viewed', sortable: false }] : []),
  { title: 'ACTIONS', key: 'actions', sortable: false },
])

const reload = () => {
  loadItems()
}

function openDrivePage(path: string) {
  if (path)
    window.open(`https://drive.google.com/drive/u/0/folders/${path}`, '_blank')
}

const resolveStato = (stato: string, statoUser: string | null) => {
  if (isApprover.value !== true) {
    if (stato === 'In-Approval')
      return { color: 'warning', text: 'In Approvazione' }
    if (stato === 'Approved')
      return { color: 'success', text: 'Approvata' }
    if (stato === 'End')
      return { color: 'secondary', text: 'Conclusa' }
  }
  else if (viewFilter.value == 2) {
    return { color: 'success', text: 'Firmata' }
  }
  else if (viewFilter.value == 1) {
    return { color: 'warning', text: 'Da Firmare' }
  }
  else if (viewFilter.value == 3) {
    if (stato === 'In-Approval' && !statoUser)
      return { color: 'warning', text: 'Da Firmare' }
    else if (stato === 'Approved' || stato === 'End')
      return { color: 'success', text: 'Firmata' }
  }

  return { color: '', text: '--' }
}

function formatDate(date: string): string {
  if (!date)
    return '-'

  return moment(String(date)).format('DD/MM/YYYY')
}

const openView = (variazione: object) => {
  variazioneData.value = variazione
  variazioneVisibile.value = true
}

const goCreate = () => {
  router.push({ name: 'workflow-variazioni-create' })
}
</script>

<template>
  <div class="workspace-container w-100 d-flex flex-column pa-4 gap-3">
    <VSnackbar
      v-model="isSnackbarVisible"
      transition="scroll-y-reverse-transition"
      location="top center"
      :timeout="3000"
      :color="color"
    >
      {{ $t(message) }}
    </VSnackbar>

    <VCard
      variant="outlined"
      class="bg-surface border-thin rounded-lg"
    >
      <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
        <div class="d-flex align-center gap-2">
          <VIcon
            icon="tabler-file-description"
            size="24"
            color="primary"
          />
          <div>
            <div class="text-h6 font-weight-medium">
              {{ $t('Label.Variazioni') }}
            </div>
            <div class="text-caption text-medium-emphasis">
              {{ totalItems }} {{ $t('Label.Variazioni') }}
            </div>
          </div>
        </div>
        <div class="d-flex align-center gap-2">
          <VBtn
            v-if="can(DefineAbilities.wf_variazioni_create.action, DefineAbilities.wf_variazioni_create.subject)"
            prepend-icon="tabler-plus"
            color="primary"
            variant="flat"
            density="comfortable"
            class="px-3"
            @click="goCreate"
          >
            {{ $t('Label.Nuova-Variazione') }}
          </VBtn>
        </div>
      </VCardText>
      <VDivider />
      <VCardText class="pa-3">
        <VRow class="mb-2">
          <!-- 👉 OL -->
          <VCol
            cols="12"
            sm="3"
          >
            <AppTextField
              v-model="olFilter"
              label="OL"
              placeholder="OL"
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-search"
              @keyup.enter="loadItems"
              @click:clear="loadItems"
            />
          </VCol>

          <!-- 👉 Stato -->
          <VCol
            cols="12"
            sm="3"
          >
            <AppSelect
              v-model="statoFilter"
              :label="$t('Label.Stato')"
              :items="[
                { id: 'In-Approval', tipologia: 'In Approvazione' },
                { id: 'Approved', tipologia: 'Approvata' },
                { id: 'End', tipologia: 'Conclusa' },
              ]"
              item-title="tipologia"
              item-value="id"
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-filter"
              @update:model-value="loadItems"
              @click:clear="loadItems"
            />
          </VCol>

          <!-- 👉 Firma -->
          <VCol
            v-if="checkFilter"
            cols="12"
            sm="3"
          >
            <AppSelect
              v-model="viewFilter"
              :label="$t('Label.Visualizza')"
              :items="optionViewFilter"
              item-title="tipologia"
              item-value="id"
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-filter"
              @update:model-value="loadItems"
              @click:clear="loadItems"
            />
          </VCol>

          <!-- 👉 Visualizzate: riservato ai Visualizzatori -->
          <VCol
            v-if="isViewer"
            cols="12"
            sm="3"
          >
            <AppSelect
              v-model="visualizFilter"
              :label="$t('Label.Visualizzata')"
              :items="[
                { id: 1, tipologia: 'Visualizzate' },
                { id: 0, tipologia: 'Da Visualizzare' },
              ]"
              item-title="tipologia"
              item-value="id"
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-filter"
              @update:model-value="loadItems"
              @click:clear="loadItems"
            />
          </VCol>
        </VRow>
      </VCardText>
      <VDivider />
      <!-- 👉 Datatable  -->
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        :headers="headers"
        :items="serverItems"
        :items-length="totalItems"
        :loading="loading"
        density="comfortable"
        hover
        @update:options="updateOptions"
      >
        <template #no-data>
          <div class="py-10 text-center">
            <VIcon
              icon="tabler-file-off"
              size="40"
              class="text-disabled mb-2"
            />
            <p class="text-body-1 text-disabled mb-0">
              {{ $t('Messaggi.Variazione-Non-Trovata') }}
            </p>
          </div>
        </template>

        <template #item.ol="{ item }">
          <RouterLink
            to=""
            class="font-weight-medium text-primary text-decoration-none"
            @click="openView(item)"
          >
            {{ item.ol }}
          </RouterLink>
        </template>

        <template #item.stato="{ item }">
          <VChip
            v-if="item.stato"
            :color="resolveStato(item.stato, item.approval_action).color"
            size="small"
          >
            {{ resolveStato(item.stato, item.approval_action).text }}
          </VChip>
        </template>

        <template #item.created_at="{ item }">
          {{ formatDate(item.created_at) }}
        </template>

        <template #item.viewed="{ item }">
          <VIcon
            :icon="item.viewed ? 'tabler-eye-check' : 'tabler-eye-off'"
            :color="item.viewed ? 'success' : 'disabled'"
            size="20"
          />
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }">
          <div class="d-flex gap-1">
            <IconBtn
              color="primary"
              size="small"
              @click="openView(item)"
            >
              <VIcon
                icon="tabler-eye"
                size="18"
              />
            </IconBtn>
            <IconBtn
              v-if="item.folder_drive && can(DefineAbilities.wf_variazioni_list.action, DefineAbilities.wf_variazioni_list.subject)"
              color="success"
              size="small"
              @click="openDrivePage(item.folder_drive)"
            >
              <VIcon
                icon="tabler-brand-google-drive"
                size="18"
              />
            </IconBtn>
          </div>
        </template>
      </VDataTableServer>
    </VCard>

    <VariazioneView
      v-model:isDialogVisible="variazioneVisibile"
      :variazione-data="variazioneData"
      @update:variazione-data="reload"
    />
  </div>
</template>
