<script setup lang="ts">
import {VDataTableServer} from 'vuetify/labs/VDataTable'
import {useI18n} from 'vue-i18n'
import type {VForm} from 'vuetify/components/VForm'
import moment from 'moment/moment'
import {can} from '@layouts/plugins/casl'
import DefineAbilities from '@/plugins/casl/DefineAbilities'
import TipoReport from '@/pages/quality/prove/tipo/list/TipoReport.vue'

definePage({
  meta: {
    action: 'list',
    subject: 'Qualita-Prove-Tipo',
  },
})

const {t} = useI18n()
const itemsPerPage = ref(10)
const loading = ref(true)
const refForm = ref<VForm>()
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const materialeFilter = ref()
const olFilter = ref()
const tipologiaFilter = ref([])
const esitoFilter = ref([])
const standardFilter = ref([])
const specificaFilter = ref()
const dataFilter = ref()
const page = ref(1)
const serverItems = ref<any>([])
const isSnackbarScrollReverseVisible = ref(false)
const message = ref('')
const color = ref('')
const editDialog = ref(false)
const proveTipoOptions = ref<any>([])
const standatrdOptions = ref<any>([])


const defaultItem = ref<any>({
  id: '',
  nome: '',
  nome_gp: '',
  report_gp: 0,
  ativo: 0,
  lavorazione: 0,
})

function new_defaultItem() {
  defaultItem.value = {
    id: '',
    ol: '',
    matariale: '',
    descrizione: '',
    esito: null,
    standard: null,
    specifica: '',
    tipo: null,
    data_prova: null,
    cliente: '',
    note: '',
  }
}

const editedItem = ref<any>(defaultItem.value)
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

  const {data: resultData, error} = await useApi<any>(createUrl('/qt/prove_tipo/list', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      materiale: materialeFilter.value,
      esito: esitoFilter.value,
      tipologia: tipologiaFilter.value,
      standard: standardFilter.value,
      specifica: specificaFilter.value,
      ol: olFilter.value,
      data: dataFilter.value,
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
  {title: t('Label.Ol'), key: 'ol'},
  {title: t('Label.Materiale'), key: 'materiale', sortable: false},
  {title: t('Table.Esito'), key: 'esito'},
  {title: t('Label.Standard'), key: 'standard', sortable: false},
  {title: t('Label.Specifica'), key: 'specifica'},
  {title: t('Label.Tipologia'), key: 'categoria'},
  {title: t('Label.Data'), key: 'data_prova'},
  {title: 'ACTIONS', key: 'actions', sortable: false},
]

const resolveStatusVariant = (risultato: string) => {
  if (risultato === 'POSITIVO')
    return {color: 'success', text: 'POSITIVO'}
  else if (risultato === 'NEGATIVO')
    return {color: 'error', text: 'NEGATIVO'}
  else
    return {color: '', text: risultato}
}

function formatDate(date: string): string {
  return moment(String(date)).format('DD/MM/YYYY')
}

function openDrivePage(path: string) {
  window.open(`https://drive.google.com/drive/u/0/folders/${path}`, '_blank')
}

const editItem = (item: object) => {
  editedIndex.value = serverItems.value.indexOf(item)

  editedItem.value = {...item}
  editedItem.value.attivo = editedItem.value.attivo === '1'
  editedItem.value.report_gp = editedItem.value.report_gp === '1'
  editDialog.value = true
}

const getTipoProve = async () => {
  const { data: resultData, error } = await useApi<any>(createUrl('/qt/categorie/get_categorie', {
    query: {
      modulo: 1, // tipo_prove
    },
  }))

  proveTipoOptions.value = resultData.value
}

const getStandardProve = async () => {
  const { data: resultData, error } = await useApi<any>(createUrl('/qt/categorie/get_categorie', {
    query: {
      modulo: 2, // Standard
    },
  }))

  if (resultData.value !== null)
    standatrdOptions.value = resultData.value
}

onMounted(() => {
  getTipoProve()
  getStandardProve()
})
</script>

<template>
  <VCol cols="12">
    <VRow>
      <VCol cols="10">
        <VCard
          title="Filters"
          class="mb-6"
        >
          <VCardText>
            <VRow>
              <!-- 👉 Materiale -->
              <VCol
                cols="12"
                sm="3"
              >
                <AppTextField
                  v-model="materialeFilter"
                  :label="$t('Label.Materiale')"
                  :placeholder="$t('Label.Materiale')"
                  clearable
                  clear-icon="tabler-x"
                  @focusout="loadItems"
                />
              </VCol>

              <!-- 👉 Ordine -->
              <VCol
                cols="12"
                sm="3"
              >
                <AppTextField
                  v-model="olFilter"
                  :label="$t('Label.Ol')"
                  :placeholder="$t('Label.Ol')"
                  clearable
                  clear-icon="tabler-x"
                  @focusout="loadItems"
                />
              </VCol>

              <!-- 👉 Tipologia -->
              <VCol
                cols="12"
                sm="3"
              >
                <AppSelect
                  v-model="tipologiaFilter"
                  :label="$t('Label.Tipologia')"
                  :placeholder="$t('Label.Tipologia')"
                  :items="proveTipoOptions"
                  :item-title="item => item.categoria"
                  :item-value="item => item.id"
                  clearable
                  clear-icon="tabler-x"
                  @focusout="loadItems"
                />
              </VCol>

              <!-- 👉 Standard -->
              <VCol
                cols="12"
                sm="3"
              >
                <AppSelect
                  v-model="standardFilter"
                  :label="$t('Label.Standard')"
                  :placeholder="$t('Label.Standard')"
                  :items="standatrdOptions"
                  :item-title="item => item.categoria"
                  :item-value="item => item.categoria"
                  clearable
                  clear-icon="tabler-x"
                  @focusout="loadItems"
                />
              </VCol>

              <!-- 👉 Specifica -->
              <VCol
                cols="12"
                sm="3"
              >
                <AppTextField
                  v-model="specificaFilter"
                  :label="$t('Label.Specifica')"
                  :placeholder="$t('Label.Specifica')"
                  clearable
                  clear-icon="tabler-x"
                  @focusout="loadItems"
                />
              </VCol>

              <!-- 👉 Esito -->
              <VCol
                cols="12"
                sm="3"
              >
                <AppSelect
                  v-model="esitoFilter"
                  :label="$t('Label.Esito')"
                  :placeholder="$t('Label.Esito')"
                  :items="[{ title: 'Positivo', value: 'Positivo' }, { title: 'Negativo', value: 'Negativo' }]"
                  clearable
                  clear-icon="tabler-x"
                  @focusout="loadItems"
                />
              </VCol>

              <!-- 👉 Data -->
              <VCol
                cols="12"
                sm="3"
              >
                <AppDateTimePicker
                  v-model="dataFilter"
                  :label="$t('Label.Data')"
                  :placeholder="$t('Label.Data')"
                  :config="{ mode: 'range' }"
                  clearable
                  clear-icon="tabler-x"
                  @focusout="loadItems"
                />
              </VCol>
            </VRow>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="2">
        <VCard class="mb-3">
          <!-- 👉Stage Report -->
          <TipoReport
            :ol-filter="olFilter"
            :tipologia-filter="tipologiaFilter"
            :materiale-filter="materialeFilter"
            :esito-filter="esitoFilter"
            :standard-filter="standardFilter"
            :specifica-filter="specificaFilter"
            :date-filter="dataFilter"
          />
        </VCard>
      </VCol>
    </VRow>
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
            v-if="can(DefineAbilities.qt_prove_tipo_create.action, DefineAbilities.qt_prove_tipo_create.subject)"
            prepend-icon="tabler-plus"
            color="success"
            :to="{ name: 'quality-prove-tipo-add-add' }"
          >
            {{$t('Button.Nuova-Prova')}}
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
        <!-- Ol -->
        <template #item.ol="{ item }">
          <div class="d-flex align-center">
            <div
              v-if="can(DefineAbilities.qt_prove_tipo_read.action, DefineAbilities.qt_prove_tipo_read.subject)"
              class="d-flex flex-column">
              <h6 class="text-base">
                <RouterLink
                  :to="{ name: 'quality-prove-tipo-view-id', params: { id: item.id } }"
                  class="font-weight-medium text-link"
                >
                  {{ item.ol }}
                </RouterLink>
                <span v-if="item.note">⚠️</span>
              </h6>
            </div>
            <div
              v-else
              class="d-flex flex-column">
              <h6 class="text-base">
                {{ item.ol }}
                <span v-if="item.note">⚠️</span>
              </h6>
            </div>
          </div>
        </template>

        <!-- risultato -->
        <template #item.esito="{ item }">
          <div class="d-flex gap-1" v-if="item.esito">
            <VChip
              :color="resolveStatusVariant(item.esito).color"
              size="small"
            >
              {{ resolveStatusVariant(item.esito).text }}
            </VChip>
          </div>
        </template>

        <!-- date -->
        <template #item.data_prova="{ item }">
          <div class="d-flex gap-1">
            {{ formatDate(item.data_prova) }}
          </div>
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }">
          <div class="d-flex gap-1">
            <IconBtn
              v-if="can(DefineAbilities.qt_prove_tipo_create.action, DefineAbilities.qt_prove_tipo_create.subject)"
              color="primary"
              @click="openDrivePage(item.path_drive)"
            >
              <VIcon icon="tabler-brand-google-drive"/>
            </IconBtn>
            <IconBtn
              v-if="can(DefineAbilities.qt_checker_fai_edit.action, DefineAbilities.qt_checker_fai_edit.subject)"
              color="warning"
              @click="editItem(item)"
            >
              <VIcon icon="tabler-edit"/>
            </IconBtn>
            <IconBtn
              v-if="can(DefineAbilities.qt_prove_tipo_deleted.action, DefineAbilities.qt_prove_tipo_deleted.subject)"
              color="error"
              @click="deleteItem(item)"
            >
              <VIcon icon="tabler-trash"/>
            </IconBtn>
          </div>
        </template>
      </VDataTableServer>
    </VCard>
  </VCol>
</template>


