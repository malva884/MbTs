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

const { t } = useI18n()
const itemsPerPage = ref(10)
const loading = ref(true)
const refForm = ref<VForm>()
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const page = ref(1)
const macchinaeFilter = ref('')
const attivoFilter = ref('')
const lavorazioneFilter = ref('')
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
      macchina: macchinaeFilter.value,
      attivo: attivoFilter.value,
      lavorazione: lavorazioneFilter.value,
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
const headers = [
  { title: t('Table.Merce-In-Trsnsito-Del'), key: 'created_at' },
  { title: t('Table.Totale'), key: 'totale', sortable: false },
  { title: t('Table.Rame'), key: 'value_cc', sortable: false },
  { title: t('Table.Ottico'), key: 'value_ofc', sortable: false },
  { title: t('Table.Ottico-Fkm'), key: 'value_fkm', sortable: false },
  { title: t('Table.Ottico-Ckm'), key: 'value_ofc_ckm', sortable: false },
  { title: t('Table.Rame-Ckm'), key: 'value_ckm', sortable: false },
  { title: 'ACTIONS', key: 'actions', sortable: false },
]


const resolveLavorazione = (lavorazione: string) => {
  if (lavorazione === '2')
    return {color: 'warning', text: 'Ottico'}
  else if (lavorazione === '1')
    return {color: 'success', text: 'Rame'}
  else
    return {color: 'primary', text: 'Ottivo/Rame'}
}

const save = async () => {
  isDialogLoading.value = true

  await $api('fi/goods_transit/import', {
    method: 'POST',
    body: {
      file_upload: data.value,
    },
  })
  loadItems()
  isDialogLoading.value = false
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
              v-model="macchinaeFilter"
              :label="$t('Label.Visitatore')"
              clearable
              clear-icon="tabler-x"
              @focusout="loadItems"
            />
          </VCol>

          <!-- 👉 Lavorazione -->
          <VCol
            cols="12"
            sm="4"
          >
            <AppSelect
              v-model="lavorazioneFilter"
              :label="$t('Label.Lavorazione')"
              :placeholder="$t('Label.Lavorazione')"
              :items="[{ title: 'Rame', value: 1 }, { title: 'Ottico', value: 2 }, { title: 'Entrambi', value: 3 }]"
              clearable
              clear-icon="tabler-x"
              @focusout="loadItems"
            />
          </VCol>
          <!-- 👉 Attivo -->
          <VCol
            cols="12"
            sm="4"
          >
            <AppSelect
              v-model="attivoFilter"
              :label="$t('Label.Attive')"
              :placeholder="$t('Label.Attive')"
              :items="[{ title: 'Si', value: 1 }, { title: 'No', value: 0 }]"
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
            v-if="can(DefineAbilities.qt_checker_fai_creaate.action, DefineAbilities.qt_checker_fai_creaate.subject)"
            prepend-icon="tabler-plus"
            color="success"
            @click="newItem"
          >
            Import Merce in Viaggio
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
  </VCol>

  <!-- 👉 Edit Dialog  -->
  <VDialog
    v-model="editDialog"
    max-width="1400px"
  >
    <AppCardActions
      v-model:loading="isLoading"
      :title="$t('Label.Nuova-Importazione')"
      no-actions
    >
      <VCard>
        <VCardText>
          <VContainer>
            <VForm
              ref="refForm"
              v-model="isFormValid"
            >
              <VRow>
                <!-- 👉 Upload -->
                <VCol
                  cols="12"
                  md="12"
                >
                  <VFileInput
                    accept=".xlsx, .xls,"
                    :label="$t('Label.File')"
                    :rules="[requiredValidator]"
                    @change="uploadFile"
                  />
                </VCol>
              </VRow>
            </VForm>
          </VContainer>
        </VCardText>

        <VCardActions>
          <VSpacer />

          <VBtn
            type="reset"
            color="error"
            variant="outlined"
            @click="close"
          >
            Cancel
          </VBtn>

          <VBtn
            type="submit"
            color="success"
            variant="elevated"
            @click="save"
          >
            Save
          </VBtn>
        </VCardActions>
      </VCard>
    </AppCardActions>
  </VDialog>

  <!-- Dialog -->
  <VDialog
    v-model="isDialogLoading"
    width="300"
  >
    <VCard
      color="primary"
      width="300"
    >
      <VCardText class="pt-3">
        <span class="ml-4 mb-3">Please stand by</span>
        <VProgressLinear
          :size="40"
          color="warning"
          class="mt-3"
          indeterminate
        />
      </VCardText>
    </VCard>
  </VDialog>
</template>
