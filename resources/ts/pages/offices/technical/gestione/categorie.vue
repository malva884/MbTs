<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { useI18n } from 'vue-i18n'
import { VForm } from 'vuetify/components/VForm'
import { can } from '@layouts/plugins/casl'
import DefineAbilities from '@/plugins/casl/DefineAbilities'

definePage({
  meta: {
    action: 'list',
    subject: 'Cavi',
  },
})

const { t } = useI18n()
const itemsPerPage = ref(10)
const loading = ref(1)
const refForm = ref<VForm>()
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const categoriaFilter = ref('')
const normaFilter = ref('')
const moduloFilter = ref('')
const page = ref(1)
const serverItems = ref<any>([])
const isSnackbarScrollReverseVisible = ref(false)
const message = ref('')
const color = ref('')
const editDialog = ref(false)
const isLoading = ref(false)
const isFormValid = ref(false)

const defaultItem = ref<any>({
  id: '',
  categoria: '',
  legistrazione: '',
  nota: '',
  modulo: null,
})

function new_defaultItem() {
  defaultItem.value = {
    id: '',
    categoria: '',
    legistrazione: '',
    nota: '',
    modulo: null,
  }
}

const editedItem = ref<any>(defaultItem.value)
const editedIndex = ref(-1)
const selectedModuli = ref([{ categoria: 'Cavi', value: '1', colore: 'info' }])

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

  const { data: resultData, error } = await useApi<any>(createUrl('/to/categorie/list', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      categoria: categoriaFilter.value,
      norma: normaFilter.value,
      modulo: moduloFilter.value,
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
  { title: t('Table.Categoria'), key: 'categoria' },
  { title: t('Table.Norma'), key: 'legistrazione' },
  { title: t('Table.Modulo'), key: 'modulo' },
  { title: t('Table.Nota'), key: 'nota' },
  { title: 'ACTIONS', key: 'actions', sortable: false },
]

const save = async () => {
  if (editedItem.value.categoria) {
    let path = '/to/categorie/store/'
    if (editedItem.value.id)
      path = `/to/categorie/update/${editedItem.value.id}`

    isLoading.value = true

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
}

const newItem = () => {
  new_defaultItem()
  editedIndex.value = -1
  editedItem.value = { ...defaultItem.value }
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
  editedItem.value.attivo = editedItem.value.attivo === '1'
  editedItem.value.report_gp = editedItem.value.report_gp === '1'
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
          <!-- 👉 Categoria -->
          <VCol
            cols="12"
            sm="4"
          >
            <AppTextField
              v-model="categoriaFilter"
              :label="$t('Label.Categoria')"
              clearable
              clear-icon="tabler-x"
              @focusout="loadItems"
            />
          </VCol>
          <!-- 👉 Norma -->
          <VCol
            cols="12"
            sm="4"
          >
            <AppTextField
              v-model="normaFilter"
              :label="$t('Label.Norma')"
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
            v-if="can(DefineAbilities.qt_non_conformita_create.action, DefineAbilities.qt_non_conformita_create.subject)"
            prepend-icon="tabler-plus"
            color="success"
            @click="newItem"
          >
            Nuova Categoria
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
        <template #item.disabled="{ item }">
          <div
            v-if="item.disabled === '0'"
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
              v-if="can(DefineAbilities.qt_non_conformita_create.action, DefineAbilities.qt_non_conformita_create.subject)"
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
      :title="editedItem.id ? `${$t('Label.Modifica')} Categoria` : `${$t('Label.Nuova')} Categoria`"
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

                <!-- 👉 Categoria -->
                <VCol cols="12">
                  <AppTextField
                    v-model="editedItem.categoria"
                    :rules="[requiredValidator]"
                    :label="$t('Label.Categoria')"
                    :placeholder="$t('Label.Categoria')"
                  />
                </VCol>

                <!-- 👉 Norma -->
                <VCol cols="12">
                  <AppTextField
                    v-model="editedItem.legistrazione"
                    :label="$t('Label.Norma')"
                    :placeholder="$t('Label.Norma')"
                  />
                </VCol>

                <!-- 👉 Nota -->
                <VCol cols="12">
                  <AppTextField
                    v-model="editedItem.nota"
                    :label="$t('Label.Nota')"
                    :placeholder="$t('Label.Nota')"
                  />
                </VCol>

                <!-- 👉 Moduli -->
                <VCol cols="12">
                  <AppSelect
                    v-model="editedItem.modulo"
                    :label="$t('Label.Moduli')"
                    :placeholder="$t('Label.Moduli')"
                    :items="selectedModuli"
                    :item-title="item => item.categoria"
                    :item-value="item => item.value"
                    chips
                    multiple
                    eager
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
</template>
