<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import moment from 'moment'
import { useI18n } from 'vue-i18n'
import type { RpRegisterLog } from '@/views/reception/type'
import NuovoVisitatoreDrawer from '@/views/reception/list/NuovoVisitatoreDrawer.vue'
import {a} from "unplugin-vue-router/dist/options-8dbadba3";

definePage({
  meta: {
    action: 'list',
    subject: 'Qualita-Fai',
  },
})

const { t } = useI18n()
const itemsPerPage = ref(10)
let loading = true
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const visitatoreFilter = ref('')
const aziendaFilter = ref('')
const dataFilter = ref('')
const page = ref(1)
const serverItems = ref<any>([])
const editDialog = ref(false)
const isSnackbarScrollReverseVisible = ref(false)
const message = ref('')
const color = ref('')
const isNuovoVisitatoreDrawerVisible = ref(false)


const updateOptions = (options: any) => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
  page.value = options.page
  itemsPerPage.value = options.itemsPerPage

  // eslint-disable-next-line @typescript-eslint/no-use-before-define
  loadItems()
}

const loadItems = async () => {
  loading = true

  const { data: resultData, error } = await useApi<any>(createUrl('/reception/register/list', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      visitatore: visitatoreFilter.value,
      azienda: aziendaFilter.value,
      data: dataFilter.value,
    },
  }))

  if (resultData.value !== null) {
    serverItems.value = resultData.value.data
    totalItems.value = resultData.value.total
  }
  else {
    serverItems.value = []
    totalItems.value = 0
  }
  loading = false
}

const newItem = () => {
  editDialog.value = true
}

// status options
const selectedOptions = [
  { text: 'Attivo', value: 1 },
  { text: 'Disattivo', value: 2 },
]

// headers
const headers = [
  { title: t('Label.Data'), key: 'data_prevista' },
  { title: t('Label.Visitatore'), key: 'nome' },
  { title: t('Label.Email'), key: 'email' },
  { title: t('Label.Azienda'), key: 'azienda' },
  { title: t('Label.Utente'), key: 'full_name' },
  { title: t('Label.Notifica'), key: 'notifica_inviata' },
  { title: 'ACTIONS', key: 'actions', sortable: false },
]

const guestsOptions = ref([])

const userOptions = async () => {
  const resultData = await useApi<any>(createUrl('/users/getUsers'))
  const arr = []

  resultData.data.value.data.forEach(value => {
    arr.push({ full_name: value.full_name, id: value.email })
  })
  guestsOptions.value = arr
}

userOptions()

function formatDate(date: string): string {
  return moment(String(date)).format('MM/DD/YYYY H:m')
}

const nuovoVisitatore = async (visitatoreData: RpRegisterLog) => {

  const retuenData = await $api('/reception/register/store', {
    method: 'POST',
    body: visitatoreData,
  })

  message.value = retuenData.message
  color.value = retuenData.color
  isSnackbarScrollReverseVisible.value = true

  // refetch User
  await loadItems()
}

const send = async (id: number) => {

  // eslint-disable-next-line no-template-curly-in-string
  const retuenData = await $api(`/reception/register/send/${id}`, {
    method: 'POST',
  })

  message.value = retuenData.message
  color.value = retuenData.color
  isSnackbarScrollReverseVisible.value = true

  // refetch List
  await loadItems()
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
          <!-- 👉 Visitatore -->
          <VCol
            cols="12"
            sm="4"
          >
            <AppTextField
              v-model="visitatoreFilter"
              :label="$t('Label.Visitatore')"
              clearable
              clear-icon="tabler-x"
              @focusout="loadItems"
            />
          </VCol>

          <!-- 👉 Azienda -->
          <VCol
            cols="12"
            sm="4"
          >
            <AppTextField
              v-model="aziendaFilter"
              :label="$t('Label.Azienda')"
              clearable
              clear-icon="tabler-x"
              @focusout="loadItems"
            />
          </VCol>

          <!-- 👉 Data -->
          <VCol
            cols="12"
            sm="4"
          >
            <AppDateTimePicker
              v-model="dataFilter"
              :label="$t('Label.Data')"
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
            prepend-icon="tabler-plus"
            color="success"
            @click="isNuovoVisitatoreDrawerVisible = true"
          >
            Nuovo Visitatore
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
        <template #item.data_prevista="{ item }">
          <div class="d-flex gap-1">
            {{ formatDate(item.data_prevista) }}
          </div>
        </template>

        <template #item.notifica_inviata="{ item }">
          <div
            v-if="item.notifica_inviata"
            class="d-flex gap-1"
          >
            Si
          </div>
          <div
            v-else
            class="d-flex gap-1"
          >
            No
          </div>
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }">
          <div class="d-flex gap-1">
            <IconBtn
              color="primary"
              @click=""
            >
              <VIcon icon="tabler-info-circle" />
            </IconBtn>

            <IconBtn
              color="warning"
              @click="send(item.id)"
            >
              <VIcon icon="tabler-send" />
            </IconBtn>
          </div>
        </template>
      </VDataTableServer>
    </VCard>
  </VCol>

  <!-- 👉 Add New User -->
  <NuovoVisitatoreDrawer
    v-model:isDrawerOpen="isNuovoVisitatoreDrawerVisible"
    @visitatore-data="nuovoVisitatore"
  />
</template>
