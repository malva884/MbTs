<script setup lang="ts">
import { VForm } from 'vuetify/components/VForm'
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import moment from 'moment'
import { useI18n } from 'vue-i18n'
import type { Conformita } from '@/views/quality/conformita/type'
import NonConforme from '@/components/dialogs/NonConforme.vue'
import {can} from "@layouts/plugins/casl";
import DefineAbilities from "@/plugins/casl/DefineAbilities";
import type {Fai} from "@/views/quality/fai/type";

definePage({
  meta: {
    action: 'list',
    subject: 'Qualita-Conformita',
  },
})

const { t } = useI18n()
const itemsPerPage = ref(10)
const loading = ref(true)
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const olFilter = ref('')
const materialeFilter = ref('')
const difettoFilter = ref('')
const macchinaFilter = ref('')
const page = ref(1)
const serverItems = ref<Conformita[]>([])
const resultFaiDialog = ref(false)
const isSnackbarScrollReverseVisible = ref(false)
const message = ref('')
const color = ref('')
const macchineOptions = []
const defettiOptions = []
const fibraTipoOptions = []
const nonConformitaVisibile = ref(false)
const NonConformeItem = ref({})
const deleteDialog = ref(false)
const isLoading = ref(false)
const viewDifeti = ref(false)

const defaultItem = ref<Conformita>({
  id: '',
  user: 0,
  data_creazione: '',
  data_chiusura: '',
  ol: '',
  numero_fai: '',
  descrizione: '',
  cod_cavo: '',
  cod_materiale: '',
  esito: 0,
  path_drive: '',
  risultato: 0,
  approvazione: ''
})

const editedItem = ref<Conformita>(defaultItem.value)
const editedIndex = ref(-1)

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

  const { data:resultData, error } = await useApi<any>(createUrl('/qt/conformita/list', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      ordine: olFilter.value,
      materiale: materialeFilter.value,
      difetto: difettoFilter.value,
      macchina: macchinaFilter.value,
    },
  }))

  if (resultData.value !== null) {
    serverItems.value = resultData.value.data
    totalItems.value = resultData.value.total
  } else {
    serverItems.value = []
    totalItems.value = 0
  }
  loading.value = false
}

// headers
const headers = [
  { title: t('Table.Ordine'), key: 'ol' },
  { title: t('Table.Materiale'), key: 'materiale' },
  { title: t('Table.Operatore'), key: 'full_name' },
  { title: t('Table.Data-Apertura'), key: 'data_apertura' },
  { title: t('Table.Bobina'), key: 'bobina' },
  { title: t('Table.Physical_l'), key: 'physical_l', sortable: false },
  { title: t('Table.Pptical_l'), key: 'optical_l' },
  { title: t('Table.Stage'), key: 'stage' },
  { title: t('Table.Linea'), key: 'macchina_nome', sortable: false },
  { title: t('Table.Difetto'), key: 'difetto_nome' },
  { title: t('Table.Chiuso'), key: 'stato' },
  { title: t('Table.Id-Conformita'), key: 'numero' },
  { title: 'ACTIONS', key: 'actions', sortable: false },
]

const openResultDialog = (item: Fai) => {
  resultFaiDialog.value = false
  editedIndex.value = serverItems.value.indexOf(item)

  editedItem.value = { ...item }
  resultFaiDialog.value = true
}

function formatDate(date: string): string {
  return moment(String(date)).format('MM/DD/YYYY')
}

const resolveStage = (stage: string) => {
  if (stage === 'BUF')
    return {color: 'primary', text: 'BUF'}
  else if (stage === 'SZ')
    return {color: 'success', text: 'SZ'}
  else if (stage === 'FC')
    return {color: 'error', text: 'FC'}
  else if (stage === 'PE')
    return {color: 'warning', text: 'PE'}
  else if (stage === 'COL')
    return {color: 'secondary', text: 'COL'}
  else
    return {color: 'light', text: 'SF'}
}

const openConformita = async (item?: any, chiudi?: boolean) => {
  NonConformeItem.value = []
  if (item.id)
    NonConformeItem.value = item
  NonConformeItem.value.disable = false
  if (chiudi === true)
    NonConformeItem.value.disable = true

  nonConformitaVisibile.value = true
}

function openDrivePage(path: string) {
  window.open(`https://drive.google.com/drive/u/0/folders/${path}`, '_blank')
}

const loadMacchine = async () => {
  const resultData = await useApi<any>(createUrl('/macchine/get_list', {
    query: {
      attivo: true,
    },
  }))

  resultData.data.value.forEach((value: any) => {
    macchineOptions.push({ id: value.id, titolo: value.nome })
  })
}

const loadDifettie = async () => {
  const resultData = await useApi<any>(createUrl('/difetti/get_list', {
    query: {
      attivo: true,
    },
  }))

  resultData.data.value.forEach((value: any) => {
    defettiOptions.push({ id: value.id, titolo: value.difetto, categoria: value.categoria })
  })
  viewDifeti.value = true
}

const loadFibreTipo = async () => {
  const resultData = await useApi<any>(createUrl('/fibra_tipologia/get_list', {
    query: {
      attivo: true,
    },
  }))

  resultData.data.value.forEach((value: any) => {
    fibraTipoOptions.push({ id: value.id, titolo: value.nome })
  })
}

const saveConformita = async (conformita: object) => {

  if ((conformita.id && conformita.stato !== '1') || (conformita.id && conformita.stato === '1' && conformita.approvazione)) {
    const retuenData = await $api(`/qt/conformita/closed/${conformita.id}`, {
      method: 'POST',
      body: conformita,
    })

    message.value = retuenData.message
    color.value = retuenData.color
    console.log(message)
  }
  else if (conformita.id) {
    const retuenData = await $api(`/qt/conformita/edit/${conformita.id}`, {
      method: 'POST',
      body: conformita,
    })

    message.value = retuenData.message
    color.value = retuenData.color
  }
  else {
    const retuenData = await $api('/qt/conformita/store', {
      method: 'POST',
      body: conformita,
    })

    message.value = retuenData.message
    color.value = retuenData.color
  }
  await loadItems()
  isSnackbarScrollReverseVisible.value = true
}

const ButtonChiuso = (conformita: number) => {

  if (conformita == 1)
    return { color: 'error', text: t('Label.Aperto') }
  else if(conformita == 2)
    return { color: 'warning', text: t('Label.Da-Chiudere') }

  return { color: 'success', text: t('Label.Dettaglio') }
}

const deleteItem = (item: Conformita) => {
  editedIndex.value = serverItems.value.indexOf(item)
  editedItem.value = { ...item }
  deleteDialog.value = true
}

const closeDelete = () => {
  deleteDialog.value = false
  editedIndex.value = -1
  editedItem.value = { ...defaultItem.value }
}

const deleteItemConfirm = async () => {
  isLoading.value = true

  const retuenData = await $api(`/qt/conformita/delete/${editedItem.value.id}`, {
    method: 'DELETE',
  })

  await loadItems()

  closeDelete()
  isLoading.value = false
  message.value = retuenData.message
  color.value = retuenData.color
  isSnackbarScrollReverseVisible.value = true
}

onMounted(() => {
  loadMacchine()
  loadDifettie()
  loadFibreTipo()
})
</script>

<template>
  <VCol cols="12">
    <VCard
      title="Filters"
      class="mb-6"
    >
      <VCardText>
        <VRow>
          <!-- 👉 Ordine -->
          <VCol
            cols="12"
            sm="2"
          >
            <AppTextField
              v-model="olFilter"
              :label="$t('Label.Numero Ordine')"
              clearable
              clear-icon="tabler-x"
              @focusout="loadItems"
            />
          </VCol>

          <!-- 👉 Materiale -->
          <VCol
            cols="12"
            sm="2"
          >
            <AppTextField
              v-model="materialeFilter"
              :label="$t('Label.Codice Materiale')"
              clearable
              clear-icon="tabler-x"
              @focusout="loadItems"
            />
          </VCol>

          <!-- 👉 Difetti -->
          <VCol
            v-if="viewDifeti"
            cols="12"
            sm="2"
          >
            <AppSelect
              v-model="difettoFilter"
              :items="defettiOptions"
              :menu-props="{ transition: 'scroll-y-transition' }"
              :label="$t('Label.Difetto')"
              :item-title="item => item.titolo"
              :item-value="item => item.id"
              clearable
              clear-icon="tabler-x"
              @focusout="loadItems"
            />
          </VCol>

          <!-- 👉 Difetti -->
          <VCol
            v-if="viewDifeti"
            cols="12"
            sm="2"
          >
            <AppSelect
              v-model="macchinaFilter"
              :items="macchineOptions"
              :menu-props="{ transition: 'scroll-y-transition' }"
              :label="$t('Label.Linea')"
              :item-title="item => item.titolo"
              :item-value="item => item.id"
              clearable
              clear-icon="tabler-x"
              @focusout="loadItems"
            />
          </VCol>
        </VRow>
      </VCardText>
    </VCard>
    <VCard :title="$t('Label.Lista-Non-Conformita')">
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
            prepend-icon="tabler-plus"
            color="success"
            @click="openConformita"
          >
            {{$t('Label.Apri-Non-Conformita')}}
          </VBtn>
        </div>
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
        <!-- date -->
        <template #item.data_apertura="{ item }">
          <div class="d-flex gap-1">
            {{ formatDate(item.data_apertura) }}
          </div>
        </template>

        <!-- date -->
        <template #item.data_chiusura="{ item }">
          <div
            v-if="item.data_chiusura"
            class="d-flex gap-1"
          >
            {{ formatDate(item.data_chiusura) }}
          </div>
          <div
            v-else
            class="d-flex gap-1"
          >
            <VBtn
              prepend-icon="tabler-square-rounded-x"
              @click="openResultDialog(item)"
            >
              Chiudi
            </VBtn>
          </div>
        </template>

        <!-- stage -->
        <template #item.stage="{ item }">
          <VChip
            :color="resolveStage(item.stage).color"
            size="small"
          >
            {{ resolveStage(item.stage).text }}
          </VChip>
        </template>

        <!-- descrizione -->
        <template #item.soluzione="{ item }">
          <div class="d-flex gap-1" v-if="item.soluzione">
            {{ item.soluzione.substr(0, 50) }} ...
          </div>
        </template>

        <!-- chiuso -->
        <template #item.stato="{ item }">
          <VBtn :color="ButtonChiuso(item.stato).color" @click="openConformita(item, true)">
            {{ ButtonChiuso(item.stato).text }}
          </VBtn>
        </template>

        <template #item.numero="{ item }">
          <div class="d-flex gap-1">
            {{ item.anno + item.numero }}
          </div>
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }">
          <div class="d-flex gap-1">
            <IconBtn
              color="primary"
              @click="openDrivePage(item.google_drive_id)"
            >
              <VIcon icon="tabler-brand-google-drive"/>
            </IconBtn>
            <IconBtn
              v-if="item.stato === '1' && can(DefineAbilities.qt_non_conformita_edit.action, DefineAbilities.qt_non_conformita_edit.subject)"
              color="warning"
              @click="openConformita(item)"
            >
              <VIcon icon="tabler-edit"/>
            </IconBtn>
            <IconBtn
              v-if="item.stato === '1' && can(DefineAbilities.qt_non_conformita_deleted.action, DefineAbilities.qt_non_conformita_deleted.subject)"
              color="error"
              @click="deleteItem(item)"
            >
              <VIcon icon="tabler-trash"/>
            </IconBtn>
          </div>
        </template>
      </VDataTableServer>
    </VCard>
    <NonConforme
      v-model:isDialogVisible="nonConformitaVisibile"
      :conformita-data="NonConformeItem"
      :macchine-options="macchineOptions"
      :defetti-options="defettiOptions"
      :fibra-tipo-options="fibraTipoOptions"
      @conformita-data="saveConformita"
    />
  </VCol>

  <!-- 👉 Delete Dialog  -->
  <VDialog
    v-model="deleteDialog"
    max-width="500px"
  >
    <AppCardActions
      v-model:loading="isLoading"
      title="Eliminazione Non Conformità:"
      no-actions
    >
      <VCard>
        <VCardTitle>
          Sei sicuro di voler eliminare?
        </VCardTitle>

        <VCardActions>
          <VSpacer/>

          <VBtn
            color="error"
            variant="outlined"
            @click="closeDelete"
          >
            Cancel
          </VBtn>

          <VBtn
            color="success"
            variant="elevated"
            @click="deleteItemConfirm"
          >
            Si
          </VBtn>

          <VSpacer/>
        </VCardActions>
      </VCard>
    </AppCardActions>S
  </VDialog>
</template>

<style>
.v-table > .v-table__wrapper > table > tbody > tr > td, .v-table > .v-table__wrapper > table > thead > tr > td, .v-table > .v-table__wrapper > table > tfoot > tr > td {
  font-size: 15px !important;
}
</style>
