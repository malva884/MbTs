<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { useI18n } from 'vue-i18n'
import { VForm } from 'vuetify/components/VForm'
import moment from 'moment/moment'
import { can } from '@layouts/plugins/casl'
import DefineAbilities from '@/plugins/casl/DefineAbilities'

definePage({
  meta: {
    action: 'list',
    subject: 'Plant-Asset',
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
const utenteFilter = ref('')
const tipologiaFilter = ref()
const serialeFilter = ref('')
const serverItems = ref<any>([])
const tipologieList = ref({})
const registratiFilter = ref()
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
const viewTipologie = ref(false)

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

  const { data: resultData } = await useApi<any>(createUrl('/pl/asset/list', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      utente: utenteFilter.value,
      numero_seriale: serialeFilter.value,
      tipologia: tipologiaFilter.value,
      registrati: registratiFilter.value,
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
  { title: t('Table.Seriale'), key: 'numero_seriale' },
  { title: t('Table.Utente'), key: 'utente' },
  { title: t('Table.Tipo-Asset'), key: 'tipo_asset', sortable: true },
  { title: t('Table.Marca'), key: 'marca', sortable: true },
  { title: t('Table.Stato'), key: 'stato', sortable: true },
  { title: t('Table.Codice-Asset'), key: 'codice_asset', sortable: true },
  { title: 'ACTIONS', key: 'actions', sortable: false },
])

const loadTipologie = async () =>{
  const { data: tipologieData } = await useApi<any>(createUrl('/pl/typology/get_list', {
    query: {
      attivo: 1,
    },
  }))

  tipologieList.value = tipologieData.value
  viewTipologie.value = true
}

loadTipologie()

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
  await $api('pl/asset/import', {
    method: 'POST',
    body: {
      file_upload: data.value,
    },
  })
  loadItems()
  editDialog.value = false
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

const remoteConnection = (alias: string) => {
  window.location.href = `anydesk:${alias}`
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
          <!-- 👉 Utente -->
          <VCol
            cols="12"
            sm="4"
          >
            <AppTextField
              v-model="utenteFilter"
              :label="$t('Label.Utente')"
              :placeholder="$t('Label.Utente')"
              clearable
              clear-icon="tabler-x"
              @focusout="loadItems"
            />
          </VCol>

          <!-- 👉 Lavorazione -->
          <VCol
            cols="12"
            sm="4"
            v-if="viewTipologie"
          >
            <AppSelect
              v-model="tipologiaFilter"
              :label="$t('Label.Tipologia-Asset')"
              :placeholder="$t('Label.Tipologia-Asset')"
              :items="tipologieList"
              :item-title="item => item.tipologia"
              :item-value="item => item.tipologia"
              clearable
              clear-icon="tabler-x"
              @focusout="loadItems"
            />
          </VCol>
          <!-- 👉 Registrati -->
          <VCol
            cols="12"
            sm="4"
          >
            <AppSelect
              v-model="registratiFilter"
              :label="$t('Label.Asset-Registrsti')"
              :placeholder="$t('Label.Asset-Registrsti')"
              :items="[{ title: 'Tutti', value: null }, { title: 'Si', value: 1 }, { title: 'No', value: 0 }]"
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
            <AppTextField
              v-model="serialeFilter"
              :label="$t('Label.Numero-Seriale')"
              :placeholder="$t('Label.Numero-Seriale')"
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
            v-if="can(DefineAbilities.asset_create.action, DefineAbilities.asset_create.subject)"
            prepend-icon="tabler-plus"
            color="primary"
            @click="newItem"
          >
            {{ $t('Label.Import-Asset') }}
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
        <!-- User -->
        <template #item.numero_seriale="{ item }">
          <div class="d-flex align-center">
            <div class="d-flex flex-column">
              <h6 class="text-base">
                <RouterLink
                  :to="{ name: 'plant-asset-view-id', params: { id: item.id } }"
                  class="font-weight-medium text-link"
                >
                  {{ item.numero_seriale }}
                </RouterLink>
              </h6>
            </div>
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
        <!-- Actions -->
        <template #item.actions="{ item }">
          <div class="d-flex gap-1">
            <IconBtn
              v-if="item.anydesk_alias != null && can(DefineAbilities.assistance_list.action, DefineAbilities.assistance_list.subject)"
              color="primary"
              @click="remoteConnection(item.anydesk_alias)"
            >
              <VIcon icon="tabler-device-remote" title="Avvia Connessione Remota" />
            </IconBtn>
            <IconBtn
              v-if="can(DefineAbilities.assistance_edit.action, DefineAbilities.assistance_edit.subject)"
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
