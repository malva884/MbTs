<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { VForm } from 'vuetify/components/VForm'
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { can } from '@layouts/plugins/casl'
import DefineAbilities from '@/plugins/casl/DefineAbilities'
import ViewCertificato from "@/views/quality/fornitore/view/ViewCertificato.vue";
import EditFornitore from "@/views/quality/fornitore/editFornitore.vue";

interface Props {
  fornitoreId: string
  keyTab: string
}

const props = defineProps<Props>()
const { t } = useI18n()
const itemsPerPage = ref(10)
const loading = ref(false)
const loadingPage = ref(false)
const refForm = ref<VForm>()
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const certificatoFilter = ref('')
const page = ref(1)
const serverItems = ref<any>([])
const isSnackbarScrollReverseVisible = ref(false)
const message = ref('')
const color = ref('')
const editDialog = ref(false)
const certificateDialog = ref(false)
const file = ref(null)
const data = ref({})
const fileName = computed(() => file.value?.name)
const fileExtension = computed(() => fileName.value?.substr(fileName.value?.lastIndexOf('.') + 1))
const fileMimeType = computed(() => file.value?.type)
const certifications = ref({})
const itemView = ref({})

const defaultItem = ref<any>({
  id: '',
  fornitore_id: '',
  certificato_id: null,
  questionario_id: '',
  livello: null,
  valutazione: 0,
  scadenza: '',
  data_acquisizione: '',
  file_id: '',
  approvato: 0,
  file: '',
  avviso: [],
})

function new_defaultItem() {
  defaultItem.value = {
    id: '',
    fornitore_id: '',
    certificato_id: null,
    questionario_id: '',
    livello: null,
    valutazione: 0,
    scadenza: '',
    data_acquisizione: '',
    file_id: '',
    approvato: 0,
    file: '',
    avviso: [],
  }
}

const editedItem = ref<any>(defaultItem.value)
const editedIndex = ref(-1)

const { data: certificationNotSupplierData } = await useApi<any>(createUrl(`/qt/certification/notSupplier/${props.fornitoreId}`))
const { data: certificationData } = await useApi<any>(createUrl('/qt/certification/list/'))

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

  const { data: resultData, error } = await useApi<any>(createUrl(`/qt/certification/supplier/${props.fornitoreId}`, {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      certificato: certificatoFilter.value,
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
  { title: t('Table.Certificazione'), key: 'titolo' },
  { title: t('Table.Livello'), key: 'livello' },
  { title: t('Table.Valutazione'), key: 'valutazione' },
  { title: t('Table.Approvato'), key: 'approvato' },
  { title: t('Table.Scadenza'), key: 'scadenza' },
  { title: 'ACTIONS', key: 'actions', sortable: false },
]

const save = async () => {
  refForm.value?.validate().then(async ({ valid }) => {
    if (valid) {
      loadingPage.value = true
      let path = '/qt/certification/stored/supplier/'
      if (editedItem.value.id)
        path = `/qt/certification/update/supplier/${editedItem.value.id}`

      const retuenData = await $api(path, {
        method: 'POST',
        body: editedItem.value,
      })

      nextTick(() => {
        refForm.value?.reset()
        refForm.value?.resetValidation()
      })

      message.value = retuenData.message
      color.value = retuenData.color
      isSnackbarScrollReverseVisible.value = true

      editDialog.value = false
      await loadItems()
      loadingPage.value = false
    }
  })
}

const newItem = () => {
  certifications.value = certificationNotSupplierData.value
  new_defaultItem()
  editedIndex.value = -1
  editedItem.value = { ...defaultItem.value }
  editedItem.value.fornitore_id = props.fornitoreId
  editDialog.value = true
}

const updateItem = (item: object) => {
  certifications.value = certificationData.value
  editedIndex.value = serverItems.value.indexOf(item)

  editedItem.value = { ...item }
  editedItem.value.approvato = (editedItem.value.approvato === '1' ? 1 : 0)
  editedItem.value.file = ''
  editDialog.value = true
}

const isDialogCertificate = (item: object) => {
  itemView.value = { ...item }
  certificateDialog.value = true
}

const uploadFile = (event: any) => {
  file.value = event.target.files[0]

  const reader = new FileReader()

  reader.readAsDataURL(file.value)
  reader.onload = async () => {
    const encodedFile = reader.result.split(',')[1]

    editedItem.value.file = {
      file: encodedFile,
      fileName: fileName.value,
      fileExtension: fileExtension.value,
      fileMimeType: fileMimeType.value,
    }
  }
}

const refresh = () => {
  loadItems()
}

watch(props, () => {
  loadItems()
})
</script>

<template>
  <VCard class="mb-6">
    <VCardItem class="pb-4">
      <VCardTitle>Filters</VCardTitle>
    </VCardItem>

    <VCardText>
      <VRow>
        <!-- 👉 Select Certification -->
        <VCol
          cols="12"
          sm="4"
        >
          <AppTextField
            v-model="certificatoFilter"
            :placeholder="t('Label.Certificazione')"
            clearable
            clear-icon="tabler-x"
          />
        </VCol>
      </VRow>
    </VCardText>

    <VDivider />

    <VCardText class="d-flex flex-wrap gap-4">
      <div class="me-3 d-flex gap-3" />
      <VSpacer />

      <div class="app-user-search-filter d-flex align-center flex-wrap gap-4">
        <!-- 👉 Export button -->
        <VBtn
          variant="tonal"
          color="secondary"
          prepend-icon="tabler-upload"
        >
          Export
        </VBtn>

        <!-- 👉 Add Certification button -->
        <VBtn
          prepend-icon="tabler-plus"
          @click="newItem"
        >
          {{ t('Button.Nuova-Certificazione') }}
        </VBtn>
      </div>
    </VCardText>

    <VDivider />

    <VDataTableServer
      v-model:items-per-page="itemsPerPage"
      :headers="headers"
      :items="serverItems"
      :items-length="totalItems"
      :loading="loading"
      @update:options="updateOptions"
    >
      <template #item.approvato="{ item }">
        <div
          v-if="item.approvato === null"
          class="d-flex gap-1"
        >
          <VIcon
            color="primary"
            icon="tabler-player-pause"
          />
        </div>
        <div
          v-else-if="item.approvato === '1'"
          class="d-flex gap-1"
        >
          <VIcon
            color="success"
            icon="tabler-check"
          />
        </div>
        <div
          v-else-if="item.approvato === '0'"
          class="d-flex gap-1"
        >
          <VIcon
            color="error"
            icon="tabler-alert-hexagon"
          />
        </div>
      </template>
      <!-- Actions -->
      <template #item.actions="{ item }">
        <div class="d-flex gap-1">
          <IconBtn
            v-if="can(DefineAbilities.qt_supplier_edit.action, DefineAbilities.qt_supplier_edit.subject)"
            color="warning"
            @click="updateItem(item)"
          >
            <VIcon icon="tabler-edit" />
          </IconBtn>
          <IconBtn
            v-if="can(DefineAbilities.qt_supplier_edit.action, DefineAbilities.qt_supplier_edit.subject)"
            color="success"
            @click="isDialogCertificate(item)"
          >
            <VIcon icon="tabler-eye" />
          </IconBtn>
        </div>
      </template>
    </VDataTableServer>
    <!-- SECTION -->
  </VCard>

  <VDialog
    v-model="editDialog"
    max-width="900"
    persistent=""
  >
    <!-- Dialog close btn -->
    <DialogCloseBtn @click="editDialog = !editDialog" />

    <!-- Dialog Content -->
    <VCard :title="t('Label.Certificazione')">
      <VCardText>
        <VContainer>
          <VForm
            ref="refForm"
            @submit.prevent="save"
          >
            <VRow>
              <!-- 👉 Certificazione -->
              <VCol cols="12">
                <AppSelect
                  v-model="editedItem.certificato_id"
                  :items="certifications"
                  item-title="text"
                  item-value="value"
                  :label="t('Label.Certificazione')"
                  :placeholder="t('Label.Certificazione')"
                  :rules="[requiredValidator]"
                  :readonly="!!editedItem.id"
                />
              </vcol>

              <VCol cols="6">
                <AppSelect
                  v-model="editedItem.livello"
                  :items="['A', 'B', 'E']"
                  :label="t('Label.Livello')"
                  :placeholder="t('Label.Livello')"
                  :rules="[requiredValidator]"
                />
              </vcol>

              <VCol cols="6">
                <AppTextField
                  v-model="editedItem.valutazione"
                  type="number"
                  :label="t('Label.Valutazione')"
                  :placeholder="t('Label.Valutazione')"
                  :rules="[requiredValidator]"
                />
              </vcol>

              <VCol cols="12">
                <VFileInput
                  accept="application/pdf"
                  :label="t('Label.Upload-Certificato')"
                  @change="uploadFile"
                />
              </VCol>

              <VCol cols="6">
                <AppDateTimePicker
                  v-model="editedItem.scadenza"
                  :label="t('Label.Scadenza')"
                  :placeholder="t('Label.Scadenza')"
                  :rules="[requiredValidator]"
                />
              </vcol>

              <VCol cols="6">
                <AppSelect
                  v-model="editedItem.approvato"
                  :items="[{ value: 1, text: t('Label.Approvato') }, { value: 0, text: t('Label.Non-Approvato') }]"
                  item-title="text"
                  item-value="value"
                  :label="t('Label.Approvazione')"
                  :placeholder="t('Label.Approvazione')"
                />
              </vcol>
            </VRow>
            <VRow class="mt-8">
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
                  @click="refForm?.validate()"
                >
                  Save
                </VBtn>
              </VCardActions>
            </VRow>
          </VForm>
        </VContainer>
      </VCardText>
    </VCard>
  </VDialog>

  <ViewCertificato
    v-model:isDrawerOpen="certificateDialog"
    :item-data="itemView"
    @item-data="refresh"
  />
  <LoadingStandBy v-model="loadingPage"></LoadingStandBy>
</template>

<style lang="scss">
.billing-address-table {
  tr {
    td:first-child {
      inline-size: 148px;
    }
  }
}
</style>
