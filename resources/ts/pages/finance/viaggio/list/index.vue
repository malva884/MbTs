<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { useI18n } from 'vue-i18n'
import { VForm } from 'vuetify/components/VForm'
import { can } from '@layouts/plugins/casl'
import DefineAbilities from '@/plugins/casl/DefineAbilities'
import moment from "moment/moment";

definePage({
  meta: {
    action: 'read',
    subject: 'Finanze-Spedito',
  },
})

const { t, locale } = useI18n()
const itemsPerPage = ref(10)
const loading = ref(true)
const refForm = ref<VForm>()
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const page = ref(1)
const annoFilter = ref()
const meseFilter = ref()
const serverItems = ref<any>([])
const isSnackbarScrollReverseVisible = ref(false)
const message = ref('')
const color = ref('')
const editDialog = ref(false)
const isLoading = ref(false)
const isFormValid = ref(false)
const file = ref(null)
const data = ref({})
const fileName = computed(() => file.value?.name)
const fileExtension = computed(() => fileName.value?.substr(fileName.value?.lastIndexOf('.') + 1))
const fileMimeType = computed(() => file.value?.type)
const isDialogLoading = ref(false)

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

  const { data: resultData, error } = await useApi<any>(createUrl('/fi/goods_transit/list', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      anno: annoFilter.value,
      mese: meseFilter.value,
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
  { title: t('Table.Merce-In-Transito-Del'), key: 'created_at' },
  { title: t('Table.Totale'), key: 'totale', sortable: false },
  { title: t('Table.Rame'), key: 'value_cc', sortable: false },
  { title: t('Table.Ottico'), key: 'value_ofc', sortable: false },
  { title: t('Table.Ottico-Fkm'), key: 'value_fkm', sortable: false },
  { title: t('Table.Ottico-Ckm'), key: 'value_ckm', sortable: false },
  { title: t('Table.Rame-Ckm'), key: 'value_cc_ckm', sortable: false },
  { title: t('Table.Azioni'), key: 'actions', sortable: false },
])


const anni = computed(() => {
  const currentYear = new Date().getFullYear()
  const list = []

  for (let year = currentYear; year >= 2024; year--)
    list.push({ title: String(year), value: year })

  return list
})

const mesi = computed(() => {
  const formatter = new Intl.DateTimeFormat(locale.value, { month: 'long' })

  return Array.from({ length: 12 }, (_, i) => {
    const month = formatter.format(new Date(2000, i, 1))

    return { title: month.charAt(0).toUpperCase() + month.slice(1), value: i + 1 }
  })
})

const save = async () => {
  const validation = await refForm.value?.validate()

  if (validation && !validation.valid)
    return

  isDialogLoading.value = true

  const resultData = await $api('fi/goods_transit/import', {
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

const newItem = () => {

  editDialog.value = true
}

const close = () => {
  isLoading.value = false
  editDialog.value = false
  file.value = null
  data.value = {}
  refForm.value?.reset()
}

function formatDate(date: string): string {
  return moment(String(date)).format('YYYY - MMMM')
}

let euro = new Intl.NumberFormat('it-IT', {
  style: 'currency',
  currency: 'EUR',
})
</script>

<template>
  <div class="workspace-container w-100 d-flex flex-column pa-4 gap-3">
    <VSnackbar
      v-model="isSnackbarScrollReverseVisible"
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
            icon="tabler-truck-delivery"
            size="24"
            color="primary"
          />
          <div>
            <div class="text-h6 font-weight-medium">
              {{ $t('Merce In Viaggio') }}
            </div>
            <div class="text-caption text-medium-emphasis">
              {{ totalItems }} {{ $t('Label.Importazioni-Registrati') }}
            </div>
          </div>
        </div>
        <div class="d-flex align-center gap-2">
          <VBtn
            v-if="can(DefineAbilities.qt_checker_fai_creaate.action, DefineAbilities.qt_checker_fai_creaate.subject)"
            prepend-icon="tabler-plus"
            color="primary"
            variant="flat"
            density="comfortable"
            class="px-3"
            @click="newItem"
          >
            Import Merce in Viaggio
          </VBtn>
        </div>
      </VCardText>
      <VDivider />
      <VCardText class="pa-3">
        <VRow class="mb-2">
          <!-- 👉 Anno -->
          <VCol
            cols="12"
            sm="3"
          >
            <AppSelect
              v-model="annoFilter"
              :label="$t('Label.Anno')"
              :placeholder="$t('Label.Tutti')"
              :items="anni"
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-filter"
              @update:model-value="loadItems"
              @click:clear="loadItems"
            />
          </VCol>

          <!-- 👉 Mese -->
          <VCol
            cols="12"
            sm="3"
          >
            <AppSelect
              v-model="meseFilter"
              :label="$t('Label.Mese')"
              :placeholder="$t('Label.Tutti')"
              :items="mesi"
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
              icon="tabler-truck-delivery"
              size="40"
              class="text-disabled mb-2"
            />
            <p class="text-body-1 text-disabled mb-0">
              {{ $t('Label.Nessuna-Importazione-Trovata') }}
            </p>
          </div>
        </template>

        <template #item.lavorazione="{ item }">
          <VChip
            :color="resolveLavorazione(item.lavorazione).color"
            size="small"
          >
            {{ resolveLavorazione(item.lavorazione).text }}
          </VChip>
        </template>

        <template #item.created_at="{ item }">
          <div class="d-flex align-center">
            <div class="d-flex flex-column">
              <h6 class="text-base">
                <RouterLink
                  :to="{ name: 'finance-viaggio-view-id', params: { id: item.id } }"
                  class="font-weight-medium text-link"
                >
                  {{ formatDate(item.created_at) }}
                </RouterLink>
              </h6>
            </div>
          </div>
        </template>
        <template #item.totale="{ item }">
          <p class="text-warning">
            {{euro.format(item.totale)}}
          </p>
        </template>

        <template #item.value_cc="{ item }">
          <p class="text-warning">
            {{euro.format(item.value_cc)}}
          </p>
        </template>

        <template #item.value_ofc="{ item }">
          <p class="text-warning">
            {{euro.format(item.value_ofc)}}
          </p>
        </template>

        <template #item.value_fkm="{ item }">
          <p class="text-warning">
            {{item.value_fkm}}
          </p>
        </template>

        <template #item.value_ofc_ckm="{ item }">
          <p class="text-warning">
            {{item.value_ofc_ckm}}
          </p>
        </template>

        <template #item.value_ckm="{ item }">
          <p class="text-warning">
            {{item.value_ckm}}
          </p>
        </template>

        <template #item.value_cc_ckm="{ item }">
          <p class="text-warning">
            {{item.value_cc_ckm}}
          </p>
        </template>
        <!-- Actions -->
        <template #item.actions="{ item }">
          <div class="d-flex gap-1">
            <IconBtn
              v-if="can(DefineAbilities.macchinari_edit.action, DefineAbilities.macchinari_edit.subject)"
              color="warning"
              @click="editItem(item)"
            >
              <VIcon icon="tabler-edit" />
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
    <VCard
      variant="outlined"
      class="bg-surface border-thin rounded-lg"
    >
      <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
        <div class="d-flex align-center gap-2">
          <VAvatar
            color="primary"
            variant="tonal"
            size="38"
          >
            <VIcon
              icon="tabler-table-import"
              size="20"
            />
          </VAvatar>
          <div>
            <div class="text-h6 font-weight-medium">
              {{ $t('Label.Nuova-Importazione') }}
            </div>
            <div class="text-caption text-medium-emphasis">
              {{ $t('Label.Carica-File-Excel') }}
            </div>
          </div>
        </div>
        <DialogCloseBtn @click="close" />
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

  <!-- 👉 Loading Dialog -->
  <LoadingStandBy v-model="isDialogLoading" />
</template>
