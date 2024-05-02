<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { useI18n } from 'vue-i18n'
import { VForm } from 'vuetify/components/VForm'
import { can } from '@layouts/plugins/casl'
import DefineAbilities from '@/plugins/casl/DefineAbilities'
import moment from "moment/moment";
import type {ReprotChecker} from "@/views/quality/checker/type";
import type {Rule} from "@/plugins/casl/ability";

definePage({
  meta: {
    action: 'read',
    subject: 'Finanze-Fatturato',
  },
})

const { t } = useI18n()
const isDialogLoading = ref(false)
const deleteDialog = ref(false)
const itemsPerPage = ref(10)
const loading = ref(true)
const refForm = ref<VForm>()
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const macchinaeFilter = ref('')
const attivoFilter = ref('')
const lavorazioneFilter = ref('')
const page = ref(1)
const serverItems = ref<any>([])
const isSnackbarScrollReverseVisible = ref(false)
const message = ref('')
const color = ref('')
const editDialog = ref(false)
const isLoading = ref(false)
const isFormValid = ref(false)
const targetCc = ref('')
const targetOfc = ref('')
const targetKfkm = ref('')
const targetCkm = ref('')
const mesePrecedente = ref(false)
const file = ref(null)
const data = ref({})
const fileName = computed(() => file.value?.name)
const fileExtension = computed(() => fileName.value?.substr(fileName.value?.lastIndexOf('.') + 1))
const fileMimeType = computed(() => file.value?.type)
const editedItem = ref<any>()
const deletedItem = ref({})
const editedIndex = ref(-1)
const router = useRouter()
const check = ref(false)

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

  const { data: resultData, error } = await useApi<any>(createUrl('/fi/turnover/list', {
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
  { title: t('Table.Fatturato-Del'), key: 'created_at' },
  { title: t('Table.Totale-Fatturato'), key: 'totale_fatturato', sortable: false },
  { title: t('Table.Targhet-Cc'), key: 'target_cc', sortable: false },
  { title: t('Table.Targhet-Ofc'), key: 'target_ofc', sortable: false },
  { title: t('Table.Targhet-Ckm'), key: 'target_ckm', sortable: false },
  { title: t('Table.Targhet-Kfkm'), key: 'target_kfkm', sortable: false },
  { title: t('Table.Stato'), key: 'import', sortable: false },
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

  const retuenData = await $api('fi/turnover/import', {
    method: 'POST',
    body: {
      targhetCc: targetCc.value,
      targhetOfc: targetOfc.value,
      targhetKfkm: targetKfkm.value,
      targhetCkm: targetCkm.value,
      mese_precendente: mesePrecedente.value,
      file_upload: data.value,
    },
  })

  isDialogLoading.value = false
  check.value = retuenData.check
  if (check.value)
    router.push('/finance/fatturato/check')
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

const deleteItem = (item: ReprotChecker) => {
  editedIndex.value = serverItems.value.indexOf(item)
  editedItem.value = { ...item }
  deletedItem.value = { ...item }
  deleteDialog.value = true
}

const deleteItemConfirm = async () => {
  isDialogLoading.value = true
  const retuenData = await $api(`/fi/turnover/delete/${deletedItem.value.id}`, {
    method: 'delete',
  })

  message.value = retuenData.message
  color.value = retuenData.color
  isSnackbarScrollReverseVisible.value = true

  await loadItems()
  deleteDialog.value = false
  isDialogLoading.value = false
}

const close = () => {
  isLoading.value = false
  editDialog.value = false
  refForm.value?.reset()
}

const loadTarghet = async () => {
  const { data: resultData, error } = await useApi<any>(createUrl('/fi/turnover/getTarghet'))

  targetCc.value = resultData.value.target_cc
  targetOfc.value = resultData.value.target_ofc
  targetKfkm.value = resultData.value.target_kfkm
  targetCkm.value = resultData.value.target_ckm
}

loadTarghet()
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
            sm="3"
          >
            <AppTextField
              v-model="macchinaeFilter"
              :label="$t('Label.Visitatore')"
              clearable
              clear-icon="tabler-x"
              @focusout="loadItems"
            />
          </VCol>

          <!-- 👉 Topologia Cavo -->
          <VCol
            cols="12"
            sm="3"
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
            sm="3"
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
            v-if="can(DefineAbilities.rp_finance_fatturato_create.action, DefineAbilities.rp_finance_fatturato_create.subject)"
            prepend-icon="tabler-plus"
            color="success"
            @click="newItem"
          >
            Importa Fattorato
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
                  :to="{ name: 'finance-fatturato-view-id', params: { id: item.id } }"
                  class="font-weight-medium text-link"
                >
                  {{ formatDate(item.created_at) }}
                </RouterLink>
              </h6>
            </div>
          </div>
        </template>

        <template #item.target_cc="{ item }">
          <p
            v-if="item.target_cc < item.value_cc"
            class="text-success">
            {{euro.format(item.target_cc)}}
          </p>
          <p v-else class="text-warning">
            {{euro.format(item.target_cc)}}
          </p>
        </template>

        <template #item.target_ofc="{ item }">
          <p v-if="item.target_ofc < item.value_ofc" class="text-success">
            {{euro.format(item.target_ofc)}}
          </p>
          <p v-else class="text-warning">
            {{euro.format(item.target_ofc)}}
          </p>
        </template>

        <template #item.target_kfkm="{ item }">
          <p v-if="item.target_kfkm < item.value_kfkm" class="text-success">
            {{item.target_kfkm}}
          </p>
          <p v-else class="text-warning">
            {{item.target_kfkm}}
          </p>
        </template>

        <template #item.target_ckm="{ item }">
          <p v-if="item.target_ckm < item.value_ckm" class="text-success">
            {{item.target_ckm}}
          </p>
          <p v-else class="text-warning">
            {{item.target_ckm}}
          </p>
        </template>

        <template #item.totale_fatturato="{ item }">
          <p class="text-success">
            {{euro.format(item.totale_fatturato)}}
          </p>

        </template>

        <template #item.import="{ item }">
          <div
            v-if="item.import === '1'"
            class="d-flex gap-1"
          >
            <VIcon
              color="success"
              icon="tabler-check"
            />
          </div>
          <div
            v-else
            class="d-flex gap-1"
          >
            <VIcon
              color="error"
              icon="tabler-circle-off"
            />
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
              @click=""
            >
              <VIcon icon="tabler-send" title="Send Notification"/>
            </IconBtn>

            <IconBtn
              v-if="can(DefineAbilities.rp_finance_fatturato_deleted.action, DefineAbilities.rp_finance_fatturato_deleted.subject)"
              color="error"
              @click="deleteItem(item)"
            >
              <VIcon icon="tabler-trash" />
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
      :title="$t('Label.Importazione-Fatturato')"
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
                <!-- 👉 Targhet Cc -->
                <VCol cols="3">
                  <AppTextField
                    v-model="targetCc"
                    :rules="[requiredValidator]"
                    :label="$t('Label.Taghet-Cc')"
                    :placeholder="$t('Label.Taghet-Cc')"
                    type="number"
                  />
                </VCol>

                <!-- 👉 Nome Gp -->
                <VCol cols="3">
                  <AppTextField
                    v-model="targetOfc"
                    :label="$t('Label.Taghet-Ofc')"
                    :placeholder="$t('Label.Taghet-Ofc')"
                    :rules="[requiredValidator]"
                    type="number"
                  />
                </VCol>

                <!-- 👉 kfkm -->
                <VCol cols="3">
                  <AppTextField
                    v-model="targetKfkm"
                    :label="$t('Label.Targhet-Kfkm')"
                    :placeholder="$t('Label.Targhet-Kfkm')"
                    :rules="[requiredValidator]"
                    type="number"
                  />
                </VCol>

                <!-- 👉 ckm -->
                <VCol cols="3">
                  <AppTextField
                    v-model="targetCkm"
                    :label="$t('Label.Targhet-Ckm')"
                    :placeholder="$t('Label.Targhet-Ckm')"
                    :rules="[requiredValidator]"
                    type="number"
                  />
                </VCol>

                <VCol cols="12" class="mt-8">
                  <VSwitch
                    v-model="mesePrecedente"
                    :label="$t('Label.Mese Precendente')"
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

  <!-- Dialog Loading -->
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
        <VSpacer />

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
          OK
        </VBtn>

        <VSpacer />
      </VCardActions>
    </VCard>
  </VDialog>
</template>
