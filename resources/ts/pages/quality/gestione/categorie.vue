<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { useI18n } from 'vue-i18n'
import { VForm } from 'vuetify/components/VForm'
import { can } from '@layouts/plugins/casl'
import DefineAbilities from '@/plugins/casl/DefineAbilities'

definePage({
  meta: {
    action: 'list',
    subject: 'Qualita-Fai',
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
const attivoFilter = ref('')
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
  descrizione: '',
  valore: 0,
  disabled: 0,
  moduli: [],
  id_drive: '',
})

function new_defaultItem() {
  defaultItem.value = {
    id: '',
    categoria: '',
    descrizione: '',
    valore: 0,
    disabled: 0,
    moduli: [],
    id_drive: '',
  }
}

const editedItem = ref<any>(defaultItem.value)
const editedIndex = ref(-1)
const selectedModuli = ref([{ categoria: 'Prove Di tipo', value: '1', colore: 'info' }, { categoria: 'Standard', value: '2', colore: 'primary' }, { categoria: 'Specifica', value: '3', colore: 'warning' }, { categoria: 'Prove Di Cpr', value: '4', colore: 'success' }])

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

  const { data: resultData, error } = await useApi<any>(createUrl('/qt/categorie', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      categoria: categoriaFilter.value,
      attivo: attivoFilter.value,
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
  { title: t('Label.Categoria'), key: 'categoria' },
  { title: t('Label.Descrizione'), key: 'descrizione', sortable: false },
  { title: t('Label.Moduli'), key: 'moduli', sortable: false },
  { title: t('Table.Stato'), key: 'disabled' },
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

const guestsOptions = ref([])

const userOptions = async () => {
  const resultData = await useApi<any>(createUrl('/users/getUsers'))
  const arr = []

  resultData.data.value.data.forEach(value => {
    arr.push({ full_name: value.full_name, id: value.email })
  })
  guestsOptions.value = arr
}

userOptions()

const save = async () => {
  if (editedItem.value.categoria) {
    let path = '/qt/categorie/store/'
    if (editedItem.value.id)
      path = `/qt/categorie/update/${editedItem.value.id}`

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

          <!-- 👉 Moduli -->
          <VCol
            cols="12"
            sm="4"
          >
            <VCheckbox
              v-for="color in colorCheckbox"
              :key="color"
              v-model="selectedModuli"
              :label="color"
              :color="color.toLowerCase()"
              :value="color"
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

        <template #item.moduli="{ item }">
          <div
            v-for="modulo in item.moduli"
            class="d-flex gap-1"
          >
            {{modulo}}
          </div>
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
      :title="editedItem.id ? `${$t('Label.Modifica')} Macchina` : `${$t('Label.Nuova')} Macchina`"
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

                <!-- 👉 Descrizione -->
                <VCol cols="12">
                  <AppTextField
                    v-model="editedItem.descrizone"
                    :label="$t('Label.Descrizione')"
                    :placeholder="$t('Label.Descrizione')"
                  />
                </VCol>

                <!-- 👉 Valore -->
                <VCol cols="12">
                  <AppTextField
                    v-model="editedItem.valore"
                    :label="$t('Label.Valore')"
                    :placeholder="$t('Label.Valore')"
                  />
                </VCol>

                <!-- 👉 Moduli -->
                <VCol cols="12">
                  <AppSelect
                    v-model="editedItem.moduli"
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

                <!-- 👉 id Drive -->
                <VCol cols="12">
                  <AppTextField
                    v-model="editedItem.id_drive"
                    :label="$t('Label.Id Cartella Google Drive')"
                    :placeholder="$t('Label.Id Cartella Google Drive')"
                  />
                </VCol>

                <VCol
                  cols="12"
                  class="mt-8"
                >
                  <VSwitch
                    v-model="editedItem.attivo"
                    :label="$t('Label.Categoria Disabilitata')"
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
