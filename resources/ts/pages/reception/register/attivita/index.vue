<script setup lang="ts">
import { VForm } from 'vuetify/components/VForm'
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import moment from 'moment'
import { useI18n } from "vue-i18n";

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
const olFilter = ref('')
const materialeFilter = ref('')
const page = ref(1)
const serverItems = ref<Fai[]>([])
const isFormValid = ref(false)
const isFormClodesValid = ref(false)
const isUserInfoEditDialogVisible = ref(false)
const editDialog = ref(false)
const resultFaiDialog = ref(false)
const deleteDialog = ref(false)
const isSnackbarScrollReverseVisible = ref(false)
const message = ref('')
const color = ref('')
const refForm = ref<VForm>()
const isLoading = ref(false)




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

  const { data:resultData, error } = await useApi<any>(createUrl('/reception/register/list', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      ordine: olFilter.value,
      materiale: materialeFilter.value,
    },
  }))

  if (resultData.value !== null) {
    serverItems.value = resultData.value.data
    totalItems.value = resultData.value.total
  } else {
    serverItems.value = []
    totalItems.value = 0
  }
  loading = false
}

// status options
const selectedOptions = [
  { text: 'Positivo', value: 1 },
  { text: 'Negativo', value: 2 },
]

// headers
const headers = [
  { title: t('Label.Data'), key: 'data_azione' },
  { title: t('Label.Visitatore'), key: 'nome' },
  { title: t('Label.Azienda'), key: 'azienda' },
  { title: t('Label.Azione'), key: 'azione' },
  { title: t('Label.Utente'), key: 'full_name' },
  //{ title: t('Label.Esito'), key: 'esito' },
  { title: 'ACTIONS', key: 'actions', sortable: false },
]

const resolveInvoiceStatusVariantAndIcon = (status: string) => {
  if (status === 'Entrata')
    return { variant: 'success', icon: 'tabler-door-enter' }
  if (status === 'Uscita')
    return { variant: 'warning', icon: 'tabler-door-exit' }


  return { variant: 'secondary', icon: 'tabler-x' }
}

function formatDate(date: string): string {
  return moment(String(date)).format('MM/DD/YYYY H:m')
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
          <!-- 👉 Ordine -->
          <VCol
            cols="12"
            sm="4"
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
            sm="4"
          >
            <AppTextField
              v-model="materialeFilter"
              :label="$t('Label.Codice Materiale')"
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
        <template #item.data_azione="{ item }">
          <div class="d-flex gap-1">
            {{ formatDate(item.data_azione) }}
          </div>
        </template>

        <template #item.azione="{ item }">
          <VAvatar
            :size="30"
            v-bind="item"
            :color="resolveInvoiceStatusVariantAndIcon(item.azione).variant"
            variant="tonal"
          >
            <VIcon
              :size="20"
              :icon="resolveInvoiceStatusVariantAndIcon(item.azione).icon"
            />
          </VAvatar>
        </template>

      </VDataTableServer>
    </VCard>
  </VCol>
</template>

