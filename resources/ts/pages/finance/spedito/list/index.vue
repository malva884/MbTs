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
const macchinaeFilter = ref('')
const attivoFilter = ref('')
const lavorazioneFilter = ref('')
const page = ref(1)
const serverItems = ref<any>([])
const isSnackbarScrollReverseVisible = ref(false)
const message = ref('')
const color = ref('')
const mesePrecedente = ref(false)
const editDialog = ref(false)
const isLoading = ref(false)
const isFormValid = ref(false)
const deleteDialog = ref(false)
const targetCc = ref('')
const targetOfc = ref('')
const targetFkm = ref('')
const targetCcCkm = ref('')
const targetOfcCkm = ref('')
const file = ref(null)
const data = ref({})
const fileName = computed(() => file.value?.name)
const fileExtension = computed(() => fileName.value?.substr(fileName.value?.lastIndexOf('.') + 1))
const fileMimeType = computed(() => file.value?.type)
const isDialogLoading = ref(false)
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

  const { data: resultData, error } = await useApi<any>(createUrl('/fi/list', {
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
  { title: t('Table.Spedito-Del'), key: 'created_at' },
  { title: t('Table.Totale-Spedito'), key: 'totale_spedito', sortable: false },
  { title: t('Table.Target-Cc'), key: 'target_cc', sortable: false },
  { title: t('Table.Target-Ofc'), key: 'target_ofc', sortable: false },
  { title: t('Table.Target-Ofc-Fkm'), key: 'target_fkm', sortable: false },
  { title: t('Table.Target-Ofc-Ckm'), key: 'target_ckm_ofc', sortable: false },
  { title: t('Table.Target-Cc-Ckm'), key: 'target_ckm_cc', sortable: false },
  { title: 'ACTIONS', key: 'actions', sortable: false },
]

const loadTarghet = async () => {
  const { data: resultData } = await useApi<any>(createUrl('/fi/getTarghet', {
    query: {
      mese_precendente: mesePrecedente.value,
    },
  }))

  targetCc.value = resultData.value.target_cc
  targetOfc.value = resultData.value.target_ofc
  targetFkm.value = resultData.value.target_fkm
  targetCcCkm.value = resultData.value.target_ckm_cc
  targetOfcCkm.value = resultData.value.target_ckm_ofc
}

loadTarghet()

const save = async () => {
  isDialogLoading.value = true

  await $api('fi/import', {
    method: 'POST',
    body: {
      targhetCc: targetCc.value,
      targhetOfc: targetOfc.value,
      targhetKfm: targetFkm.value,
      targhetOfcCkm: targetOfcCkm.value,
      targhetCcCkm: targetCcCkm.value,
      file_upload: data.value,
      mese_precendente: mesePrecedente.value,
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

const deleteItem = (item: ReprotChecker) => {
  editedIndex.value = serverItems.value.indexOf(item)
  editedItem.value = {...item}
  deletedItem.value = {...item}
  deleteDialog.value = true
}

const newItem = () => {

  editDialog.value = true
}

const close = () => {
  isLoading.value = false
  editDialog.value = false
  refForm.value?.reset()
}

const deleteItemConfirm = async () => {
  isDialogLoading.value = true
  const retuenData = await $api(`/fi/delete/${deletedItem.value.id}`, {
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
            v-if="can(DefineAbilities.fi_spedito_create.action, DefineAbilities.fi_spedito_create.subject)"
            prepend-icon="tabler-plus"
            color="success"
            @click="newItem"
          >
            Nuovo Spedito
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
        <template #item.created_at="{ item }">
          <div class="d-flex align-center">
            <div class="d-flex flex-column">
              <h6 class="text-base">
                <RouterLink
                  :to="{ name: 'finance-spedito-view-id', params: { id: item.id } }"
                  class="font-weight-medium text-link"
                >
                  {{ formatDate(item.created_at) }}
                </RouterLink>
              </h6>
            </div>
          </div>
        </template>

        <template #item.target_cc="{ item }">
          <p v-if="item.target_cc < item.value_cc" class="text-success">
            {{euro.format(item.target_cc)}} / <span class="text-info">  {{euro.format(item.value_cc)}} </span>
          </p>
          <p v-else class="text-warning">
            {{euro.format(item.target_cc)}} / <span class="text-info">  {{euro.format(item.value_cc)}} </span>
          </p>
        </template>

        <template #item.target_ofc="{ item }">
          <p v-if="item.target_ofc < item.value_ofc" class="text-success">
            {{euro.format(item.target_ofc)}} / <span class="text-info">  {{euro.format(item.target_ofc)}} </span>
          </p>
          <p v-else class="text-warning">
            {{euro.format(item.target_ofc)}} / <span class="text-info">  {{euro.format(item.value_ofc)}} </span>
          </p>
        </template>

        <template #item.target_fkm="{ item }">
          <p v-if="item.target_fkm < item.value_fkm_ofc" class="text-success">
            {{ item.target_fkm }} / <span class="text-info"> {{ item.value_fkm_ofc }} </span>
          </p>
          <p v-else class="text-warning">
            {{ item.target_fkm }} / <span class="text-info">{{ item.value_fkm_ofc }} </span>
          </p>
        </template>

        <template #item.target_ckm_ofc="{ item }">
          <p v-if="item.target_ckm_ofc < item.value_ckm_ofc" class="text-success">
            {{ item.target_ckm_ofc }} / <span class="text-info"> {{ item.value_ckm_ofc }} </span>
          </p>
          <p v-else class="text-warning">
            {{ item.target_ckm_ofc }} / <span class="text-info">{{ item.value_ckm_ofc }} </span>
          </p>
        </template>

        <template #item.target_ckm_cc="{ item }">
          <p v-if="item.target_ckm_cc < item.value_ckm_cc" class="text-success">
            {{ item.target_ckm_cc }} / <span class="text-info"> {{ item.value_ckm_cc }} </span>
          </p>
          <p v-else class="text-warning">
            {{ item.target_ckm_cc }} / <span class="text-info">{{ item.value_ckm_cc }} </span>
          </p>
        </template>

        <template #item.totale_spedito="{ item }">
          <p class="text-success">
            {{euro.format(item.totale_spedito)}}
          </p>
        </template>

        <template #item.actions="{ item }">
          <div class="d-flex gap-1">
            <IconBtn
              v-if="can(DefineAbilities.fi_spedito_deleted.action, DefineAbilities.fi_spedito_deleted.subject)"
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
      :title="$t('Label.Nuovo-Magaziono')"
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
                <!-- 👉 Target Cc -->
                <VCol cols="3">
                  <AppTextField
                    v-model="targetCc"
                    :rules="[requiredValidator]"
                    :label="$t('Label.Target-Cc')"
                    :placeholder="$t('Label.Target-Cc')"
                    type="number"
                  />
                </VCol>

                <!-- 👉 Target Ofc -->
                <VCol cols="3">
                  <AppTextField
                    v-model="targetOfc"
                    :label="$t('Label.Target-Ofc')"
                    :placeholder="$t('Label.Target-Ofc')"
                    :rules="[requiredValidator]"
                    type="number"
                  />
                </VCol>

                <!-- 👉 Target Fkm -->
                <VCol cols="2">
                  <AppTextField
                    v-model="targetFkm"
                    :label="$t('Label.Target-Fkm')"
                    :placeholder="$t('Label.Target-Fkm')"
                    :rules="[requiredValidator]"
                    type="number"
                  />
                </VCol>

                <!-- 👉 Target Ofc Ckm -->
                <VCol cols="2">
                  <AppTextField
                    v-model="targetOfcCkm"
                    :label="$t('Label.Target-Ofc-Ckm')"
                    :placeholder="$t('Label.Target-Ofc-Ckm')"
                    :rules="[requiredValidator]"
                    type="number"
                  />
                </VCol>

                <!-- 👉 Target Cc Ckm -->
                <VCol cols="2">
                  <AppTextField
                    v-model="targetCcCkm"
                    :label="$t('Label.Target-Cc-Ckm')"
                    :placeholder="$t('Label.Target-Cc-Ckm')"
                    :rules="[requiredValidator]"
                    type="number"
                  />
                </VCol>

                <VCol cols="12" class="mt-8">
                  <VSwitch
                    v-model="mesePrecedente"
                    :label="$t('Label.Mese Precedente')"
                    @change="loadTarghet"
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
