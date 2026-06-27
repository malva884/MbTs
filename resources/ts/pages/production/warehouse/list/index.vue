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

  const resultData = await $api('pr/magazzino/import', {
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

const newItem = () => {

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
  <VCol cols="12">
    <VCard
      title="Filters"
      class="mb-6"
    >
      <VCardText>
        <VRow>
          <!-- 👉 Periodo -->
          <VCol
            cols="12"
            sm="4"
          >
            <AppTextField
              v-model="periodoFilter"
              :label="$t('Label.Periodo')"
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
            v-if="can(DefineAbilities.product_magazzino_import.action, DefineAbilities.product_magazzino_import.subject)"
            prepend-icon="tabler-table-import"
            color="success"
            @click="newItem"
          >
            {{ $t('Button.Importa-Magazino')}}
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
        <template #item.titolo="{ item }">
          <div class="d-flex align-center">
            <div class="d-flex flex-column">
              <h6 class="text-base">
                <RouterLink
                  :to="{ name: 'production-warehouse-view-id', params: { id: item.id } }"
                  class="font-weight-medium text-link"
                >
                  {{ item.titolo }}
                </RouterLink>
              </h6>
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
              color="error"
              @click="reload(item)"
            >
              <VIcon icon="tabler-refresh"/>
            </IconBtn>
          </div>
          <div class="d-flex gap-1">
            <IconBtn
              v-if="can(DefineAbilities.product_magazzino_deleted.action, DefineAbilities.product_magazzino_deleted.subject)"
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

  <!-- 👉 Delete Dialog  -->
  <VDialog
    v-model="deleteDialog"
    max-width="500px"
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
          @click="deleteDialog = false"
        >
          Cancel
        </VBtn>

        <VBtn
          color="success"
          variant="elevated"
          @click="deleteItemConfirm"
        >
          OK
        </VBtn>

        <VSpacer/>
      </VCardActions>
    </VCard>
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
