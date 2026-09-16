<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { useI18n } from 'vue-i18n'
import { VForm } from 'vuetify/components/VForm'
import { can } from '@layouts/plugins/casl'
import DefineAbilities from '@/plugins/casl/DefineAbilities'
import moment from "moment/moment";
import type {ReprotChecker} from "@/views/quality/checker/type";

definePage({
  meta: {
    action: 'list',
    subject: 'Produzione-Magazzino',
  },
})

const { t } = useI18n()
const itemsPerPage = ref(10)
const loading = ref(true)
const refForm = ref<VForm>()
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const page = ref(1)
const periodoFilter = ref('')
const serverItems = ref<any>([])
const isSnackbarScrollReverseVisible = ref(false)
const message = ref('')
const color = ref('')
const editDialog = ref(false)
const isLoading = ref(false)
const isFormValid = ref(false)
const file = ref(null)
const data = ref({})
const importMode = ref<'monthly' | 'weekly'>('monthly')
const fileName = computed(() => file.value?.name)
const fileExtension = computed(() => fileName.value?.substr(fileName.value?.lastIndexOf('.') + 1))
const fileMimeType = computed(() => file.value?.type)
const isDialogLoading = ref(false)
const deleteDialog = ref(false)
const deletedItem = ref({})
const editedItem = ref<any>()
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

  const { data: resultData } = await useApi<any>(createUrl('/pr/magazzino/list', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      periodo: periodoFilter.value,
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
  loading.value = false
}

// headers
const headers = computed(() => [
  { title: t('Table.Magazzino-Del'), key: 'titolo' },
  { title: t('Table.Totale'), key: 'totale', sortable: false },
  { title: t('Table.Magazzino'), key: 'magazzino', sortable: false },
  { title: t('Table.Ofc.Fkm'), key: 'fkm_ofc', sortable: false },
  { title: t('Table.Ofc.Ckm'), key: 'ckm_ofc', sortable: false },
  { title: t('Table.Cc.Ckm'), key: 'ckm_cc', sortable: false },
  { title: 'ACTIONS', key: 'actions', sortable: false },
])

const save = async () => {
  isDialogLoading.value = true

  const endpoint = importMode.value === 'weekly'
    ? 'pr/magazzino/import-week'
    : 'pr/magazzino/import'

  const resultData = await $api(endpoint, {
    method: 'POST',
    body: {
      file_upload: data.value,
    },
  })

  message.value = resultData.message
  color.value = resultData.color
  isSnackbarScrollReverseVisible.value = true
  loadItems()
  isDialogLoading.value = false
  editDialog.value = false
}

const reload = async (item: any) => {
  isDialogLoading.value = true
  const resultData = await $api(`pr/magazzino/reload/${item.id}`, {
    method: 'POST',
  })

  isDialogLoading.value = false
  loadItems()
}

const uploadFile = (event: any) => {
  file.value = event.target.files[0]

  const reader = new FileReader()

  reader.readAsDataURL(file.value)
  reader.onload = async () => {
    const encodedFile = reader.result.split(',')[1]

    data.value = {
      file: encodedFile,
      fileName: fileName.value,
      fileExtension: fileExtension.value,
      fileMimeType: fileMimeType.value,
    }
  }
}

const newItem = (mode: 'monthly' | 'weekly' = 'monthly') => {
  importMode.value = mode
  editDialog.value = true
}

const close = () => {
  isLoading.value = false
  editDialog.value = false
  refForm.value?.reset()
}

const deleteItem = (item: ReprotChecker) => {
  editedIndex.value = serverItems.value.indexOf(item)
  editedItem.value = {...item}
  deletedItem.value = {...item}
  deleteDialog.value = true
}

const deleteItemConfirm = async () => {
  isDialogLoading.value = true
  const retuenData = await $api(`/pr/magazzino/delete/${deletedItem.value.id}`, {
    method: 'delete',
  })

  message.value = retuenData.message
  color.value = retuenData.color
  isSnackbarScrollReverseVisible.value = true

  await loadItems()
  deleteDialog.value = false
  isDialogLoading.value = false
}

function formatDate(date: string): string {
  return moment(String(date)).format('YYYY - MMMM')
}

let euro = new Intl.NumberFormat('it-IT', {
  style: 'currency',
  currency: 'EUR',
})

const formatNum = (numero: number, decimal: boolean) => {
  let num = 3
  if (!decimal) {
    numero = Math.trunc(numero)
    num = 0
  }

  return new Intl.NumberFormat('it-IT', { minimumFractionDigits: num, maximumFractionDigits: 3 }).format(numero)
}
</script>

<template>
  <div class="workspace-container w-100 d-flex flex-column pa-4 gap-3">
    <VSnackbar
      v-model="isSnackbarScrollReverseVisible"
      transition="scroll-y-reverse-transition"
      location="top center"
      :color="color"
      :timeout="3000"
    >
      {{ $t(message) }}
    </VSnackbar>

    <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
      <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
        <div class="d-flex align-center gap-2">
          <VIcon icon="tabler-building-warehouse" size="24" color="primary" />
          <div>
            <div class="text-h6 font-weight-medium">{{ $t('Label.Magazzino-Produzione') }}</div>
            <div class="text-caption text-medium-emphasis">{{ totalItems }} {{ $t('Label.Importazioni-Registrati') }}</div>
          </div>
        </div>
        <div class="d-flex align-center gap-2">
          <VBtn
            v-if="can(DefineAbilities.product_magazzino_import.action, DefineAbilities.product_magazzino_import.subject)"
            prepend-icon="tabler-table-import"
            color="success"
            variant="flat"
            density="comfortable"
            class="px-3"
            @click="newItem('monthly')"
          >
            {{ $t('Button.Importa-Magazino')}}
          </VBtn>
          <VBtn
            v-if="can(DefineAbilities.product_magazzino_import.action, DefineAbilities.product_magazzino_import.subject)"
            prepend-icon="tabler-table-import"
            color="info"
            variant="outlined"
            density="comfortable"
            class="px-3"
            @click="newItem('weekly')"
          >
            {{ $t('Button.Importa-Magazino-Settimanale') }}
          </VBtn>
        </div>
      </VCardText>
      <VDivider />
      <VCardText class="pa-3">
        <VRow class="mb-2">
          <!-- 👉 Periodo -->
          <VCol cols="12" sm="4">
            <AppTextField
              v-model="periodoFilter"
              :label="$t('Label.Periodo')"
              :placeholder="$t('Label.Periodo')"
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-search"
              @keyup.enter="loadItems"
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
            <VIcon icon="tabler-building-warehouse" size="40" class="text-disabled mb-2" />
            <p class="text-body-1 text-disabled mb-0">{{ $t('Label.Nessun-Magazzino-Trovato') }}</p>
          </div>
        </template>
        <template #item.titolo="{ item }">
          <div class="d-flex align-center">
            <div class="d-flex flex-column">
              <RouterLink
                :to="{ name: 'production-warehouse-view-id', params: { id: item.id } }"
                class="font-weight-medium text-primary text-decoration-none"
              >
                {{ item.titolo }}
              </RouterLink>
            </div>
          </div>
        </template>
        <template #item.totale="{ item }">
          <p class="text-success">
            {{euro.format(parseFloat(item.totale) + parseFloat(item.corso_lavori))}}
          </p>
        </template>
        <template #item.magazzino="{ item }">
          <p class="text-success">
            {{euro.format(parseFloat(item.totale))}}
          </p>
        </template>
        <template #item.fkm_ofc="{ item }">
          <p class="text-success">
            {{formatNum(item.fkm_ofc)}}
          </p>
        </template>

        <template #item.ckm_ofc="{ item }">
          <p class="text-success">
            {{formatNum(item.ckm_ofc, true)}}
          </p>
        </template>

        <template #item.ckm_cc="{ item }">
          <p class="text-success">
            {{formatNum(item.ckm_cc, true)}}
          </p>
        </template>
        <!-- Actions -->
        <template #item.actions="{ item }">
          <div class="d-flex gap-1">
            <IconBtn
              v-if="can(DefineAbilities.product_magazzino_deleted.action, DefineAbilities.product_magazzino_deleted.subject)"
              color="primary"
              size="small"
              @click="reload(item)"
            >
              <VIcon icon="tabler-refresh" size="18"/>
            </IconBtn>
            <IconBtn
              v-if="can(DefineAbilities.product_magazzino_deleted.action, DefineAbilities.product_magazzino_deleted.subject)"
              color="error"
              size="small"
              @click="deleteItem(item)"
            >
              <VIcon icon="tabler-trash" size="18"/>
            </IconBtn>
          </div>
        </template>
      </VDataTableServer>
    </VCard>
  </div>

  <!-- 👉 Edit Dialog  -->
  <VDialog
    v-model="editDialog"
    max-width="700px"
    persistent
  >
    <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
      <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
        <div class="d-flex align-center gap-2">
          <VAvatar color="primary" variant="tonal" size="38">
            <VIcon icon="tabler-table-import" size="20" />
          </VAvatar>
          <div>
            <div class="text-h6 font-weight-medium">{{ importMode === 'weekly' ? $t('Label.Nuova-Importazione-Settimanale') : $t('Label.Nuova-Importazione') }}</div>
            <div class="text-caption text-medium-emphasis">{{ $t('Label.Carica-File-Magazzino') }}</div>
          </div>
        </div>
        <DialogCloseBtn @click="editDialog = !editDialog" />
      </VCardText>
      <VDivider />

      <VCardText class="pa-4">
        <VForm
          ref="refForm"
          v-model="isFormValid"
        >
          <VRow>
            <!-- 👉 Upload -->
            <VCol cols="12">
              <VFileInput
                accept=".xlsx, .xls,"
                :label="$t('Label.File')"
                :rules="[requiredValidator]"
                prepend-inner-icon="tabler-upload"
                @change="uploadFile"
              />
            </VCol>
          </VRow>
        </VForm>
      </VCardText>
      <VDivider />

      <VCardActions class="pa-4 justify-end">
        <VBtn
          type="reset"
          color="error"
          variant="outlined"
          density="comfortable"
          class="px-3"
          @click="close"
        >
          {{ $t('Label.Annulla') }}
        </VBtn>

        <VBtn
          type="submit"
          color="primary"
          variant="elevated"
          density="comfortable"
          class="px-3"
          @click="save"
        >
          {{ $t('Label.Salva') }}
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <!-- 👉 Delete Dialog  -->
  <VDialog
    v-model="deleteDialog"
    max-width="500px"
    persistent
  >
    <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
      <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
        <div class="d-flex align-center gap-2">
          <VAvatar color="error" variant="tonal" size="38">
            <VIcon icon="tabler-alert-triangle" size="20" />
          </VAvatar>
          <div>
            <div class="text-h6 font-weight-medium">{{ $t('Label.Conferma-Eliminazione') }}</div>
            <div class="text-caption text-medium-emphasis">{{ $t('Label.Sicuro-Eliminare-Magazzino') }}</div>
          </div>
        </div>
        <DialogCloseBtn @click="deleteDialog = !deleteDialog" />
      </VCardText>
      <VDivider />

      <VCardActions class="pa-4 justify-end">
        <VBtn
          color="error"
          variant="outlined"
          density="comfortable"
          class="px-3"
          @click="deleteDialog = false"
        >
          {{ $t('Label.Annulla') }}
        </VBtn>

        <VBtn
          color="error"
          variant="elevated"
          density="comfortable"
          class="px-3"
          @click="deleteItemConfirm"
        >
          {{ $t('Label.Elimina') }}
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <!-- Dialog Loading -->
  <LoadingStandBy v-model="isDialogLoading" />
</template>
