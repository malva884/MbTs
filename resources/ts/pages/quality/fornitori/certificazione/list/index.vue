<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { useI18n } from 'vue-i18n'
import { VForm } from 'vuetify/components/VForm'
import { can } from '@layouts/plugins/casl'
import DefineAbilities from '@/plugins/casl/DefineAbilities'

definePage({
  meta: {
    action: 'list',
    subject: 'Qt-Supplier',
  },
})

const { t } = useI18n()
const itemsPerPage = ref(10)
const loading = ref(false)
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
const isLoading = ref(false)

const defaultItem = ref<any>({
  id: '',
  titolo: '',
  descrizione: '',
  disattivo: 0,
})

function new_defaultItem() {
  defaultItem.value = {
    id: '',
    titolo: '',
    descrizione: '',
    disattivo: 0,
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

  const { data: resultData, error } = await useApi<any>(createUrl('/qt/certification/', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      cartificato: certificatoFilter.value,
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
  { title: t('Table.Disattivo'), key: 'disattivo' },
  { title: 'ACTIONS', key: 'actions', sortable: false },
]

const save = async () => {
  refForm.value?.validate().then(async ({ valid }) => {
    if (valid) {
      isLoading.value = true
      let path = '/qt/certification/stored/'
      if (editedItem.value.id)
        path = `/qt/certification/update/${editedItem.value.id}`

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

      isLoading.value = false
      editDialog.value = false
      await loadItems()
    }
  })
}

const newItem = () => {
  new_defaultItem()
  editedIndex.value = -1
  editedItem.value = { ...defaultItem.value }
  editedItem.value.disattivo = '0'
  editDialog.value = true
}

const close = () => {
  isLoading.value = false
  editDialog.value = false
  editedIndex.value = -1
  refForm.value?.reset()
}

const editItem = (item: object) => {
  editedIndex.value = serverItems.value.indexOf(item)

  editedItem.value = { ...item }
  editedItem.value.disattivo = editedItem.value.disattivo === '1'

  editDialog.value = true
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
          <!-- 👉 Certificato -->
          <VCol
            cols="12"
            sm="4"
          >
            <AppTextField
              v-model="certificatoFilter"
              :label="$t('Label.Certificazione')"
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
            v-if="can(DefineAbilities.qt_supplier_create.action, DefineAbilities.qt_supplier_create.subject)"
            prepend-icon="tabler-plus"
            color="success"
            @click="newItem"
          >
            {{ t('Button.Nuova-Certificazione') }}
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
                  :to="{ name: 'quality-fornitori-view-id', params: { id: item.id } }"
                  class="font-weight-medium text-link text-primary"
                >
                  {{ item.titolo }}
                </RouterLink>
              </h6>
            </div>
          </div>
        </template>

        <template #item.disattivo="{ item }">
          <div
            v-if="item.disattivo === '1'"
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
    persistent=""
  >
    <AppCardActions
      v-model:loading="isLoading"
      :title="editedItem.id ? `${$t('Label.Modifica-Certificazione')} ` : `${$t('Label.Nuova-Certificazione')}`"
      no-actions
    >
      <VCard>
        <VCardText>
          <VContainer>
            <VForm
              ref="refForm"
              @submit.prevent="save"
            >
              <VRow>
                <!-- 👉 Titolo -->
                <VCol cols="12">
                  <AppTextField
                    v-model="editedItem.titolo"
                    :rules="[requiredValidator]"
                    :label="$t('Label.Certificazione')"
                    :placeholder="$t('Label.Certificazione')"
                    :readonly="!!editedItem.id"
                  />
                </vcol>

                <!-- 👉 Descrizione -->
                <VCol cols="12">
                  <AppTextField
                    v-model="editedItem.descrizione"
                    :label="$t('Label.Descrizione')"
                    :placeholder="$t('Label.Descrizione')"
                  />
                </VCol>

                <VCol
                  cols="12"
                  class="mt-8"
                >
                  <VSwitch
                    v-model="editedItem.disattivo"
                    :label="$t('Label.Certificazione-Disattiva')"
                  />
                </VCol>
              </VRow>
              <VRow>
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
    </AppCardActions>
  </VDialog>
</template>
