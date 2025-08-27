<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { VForm } from 'vuetify/components/VForm'
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { can } from '@layouts/plugins/casl'
import DefineAbilities from '@/plugins/casl/DefineAbilities'

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
const utenteFilter = ref('')
const emailFilter = ref('')
const page = ref(1)
const serverItems = ref<any>([])
const isSnackbarScrollReverseVisible = ref(false)
const message = ref('')
const color = ref('')
const editDialog = ref(false)

const defaultItem = ref<any>({
  id: '',
  supplier_id: '',
  titolo: '',
  testo: '',
  scadenza: '',
  visualizata: null,
  user_id: '',
})

function new_defaultItem() {
  defaultItem.value = {
    id: '',
    supplier_id: '',
    titolo: '',
    testo: '',
    scadenza: '',
    visualizata: null,
    user_id: '',
  }
}

const editedItem = ref<any>(defaultItem.value)
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

  const { data: resultData, error } = await useApi<any>(createUrl(`/qt/supplier/notice/${props.fornitoreId}`, {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      nome: utenteFilter.value,
      email: emailFilter.value,
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
  { title: t('Table.Avviso'), key: 'titolo' },
  { title: t('Table.Certificazione'), key: 'titolo_certificazione' },
  { title: t('Table.Conformita'), key: 'conformita_id' },
  { title: t('Table.Ritardo'), key: 'ritardo_id' },
  { title: t('Table.Stato'), key: 'disattivo' },
  { title: 'ACTIONS', key: 'actions', sortable: false },
]

const save = async () => {
  refForm.value?.validate().then(async ({ valid }) => {
    if (valid) {
      loadingPage.value = true

      const retuenData = await $api(`/qt/supplier/notice/${props.fornitoreId}/stored`, {
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
  new_defaultItem()
  editedIndex.value = -1
  editedItem.value = { ...defaultItem.value }
  editedItem.value.supplier_id = props.fornitoreId
  editDialog.value = true
}

const updateItem = (item: object) => {
  editedIndex.value = serverItems.value.indexOf(item)

  editedItem.value = { ...item }
  //editedItem.value.disattivo = (editedItem.value.disattivo === '1' ? 1 : 0)

  editDialog.value = true
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
        <!-- 👉 Select Utente -->
        <VCol
          cols="12"
          sm="4"
        >
          <AppTextField
            v-model="utenteFilter"
            :placeholder="t('Label.Utente')"
            clearable
            clear-icon="tabler-x"
            @focusout="loadItems"
          />
        </VCol>

        <!-- 👉 Select Email -->
        <VCol
          cols="12"
          sm="4"
        >
          <AppTextField
            v-model="emailFilter"
            :placeholder="t('Label.Email')"
            clearable
            clear-icon="tabler-x"
            @focusout="loadItems"
          />
        </VCol>
      </VRow>
    </VCardText>

    <VDivider />

    <VCardText class="d-flex flex-wrap gap-4">
      <div class="me-3 d-flex gap-3" />
      <VSpacer />

      <div class="app-user-search-filter d-flex align-center flex-wrap gap-4">
        <!-- 👉 Add Certification button -->
        <VBtn
          prepend-icon="tabler-plus"
          @click="newItem"
        >
          {{ t('Button.Nuovo-Avviso') }}
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
      <template #item.disattivo="{ item }">
        <div
          v-if="item.disattivo === '0'"
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
        />
      </template>
      <!-- Actions -->
      <template #item.actions="{ item }">
        <div class="d-flex gap-1">
          <IconBtn
            v-if="can(DefineAbilities.qt_supplier_edit.action, DefineAbilities.qt_supplier_edit.subject)"
            color="success"
            @click="updateItem(item)"
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
    <VCard :title="t('Label.Avviso')">
      <VCardText>
        <VContainer>
          <VForm
            ref="refForm"
            @submit.prevent="save"
          >
            <VRow>
              <VCol cols="12">
                <AppTextField
                  v-model="editedItem.titolo"
                  :label="t('Label.Titolo')"
                  :placeholder="t('Label.Titolo')"
                  :rules="[requiredValidator]"
                  :readonly="!!editedItem.id"
                />
              </vcol>

              <VCol cols="12">
                <TiptapEditor
                  v-model="editedItem.testo"
                  class="border rounded basic-editor"
                  :label="t('Label.Avviso')"
                  :placeholder="t('Label.Avviso')"
                  :rules="[requiredValidator]"
                  :readonly="!!editedItem.id"
                />
              </VCol>

              <VCol cols="12">
                <AppDateTimePicker
                  v-model="editedItem.scadenza"
                  :label="t('Label.Scadenza')"
                  :placeholder="t('Label.Scadenza')"
                  :readonly="!!editedItem.id"
                />
              </vcol>
            </VRow>
            <VRow class="mt-8" v-if="!editedItem.id">
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
