<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { useI18n } from 'vue-i18n'
import { VForm } from 'vuetify/components/VForm'
import { can } from '@layouts/plugins/casl'
import DefineAbilities from '@/plugins/casl/DefineAbilities'

definePage({
  meta: {
    action: 'list',
    subject: 'Hr-Richieste',
  },
})

const { t } = useI18n()
const itemsPerPage = ref(10)
const loading = ref(true)
const refForm = ref<VForm>()
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const centroFilter = ref()
const attivoFilter = ref()
const userFilter = ref()
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
  user_id: null,
  livello: null,
  centro_ci_costo: null,
  disattivo: false,
  notica: true,
})

function new_defaultItem() {
  defaultItem.value = {
    id: '',
    user_id: null,
    livello: null,
    centro_ci_costo: null,
    disattivo: false,
    notica: true,
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

  const { data: resultData, error } = await useApi<any>(createUrl('/hr/approvatori/list', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      centro: centroFilter.value,
      disattivo: attivoFilter.value,
      user: userFilter.value,
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
  { title: t('Table.Utente'), key: 'full_name' },
  { title: t('Table.Centro-Di-Costo'), key: 'centro_ci_costo' },
  { title: t('Table.Livello'), key: 'livello' },
  { title: t('Label.Notifica'), key: 'notifica' },
  { title: t('Label.Disattivo'), key: 'disattivo' },
  { title: 'ACTIONS', key: 'actions', sortable: false },
])

const usersOptions = ref([])
const centroOptions = ref([])

const userOptions = async () => {
  const resultData = await useApi<any>(createUrl('/users/getUsers'))
  const arr = []

  resultData.data.value.data.forEach( value => {
    arr.push({ full_name: value.full_name, id: value.id })
  })
  usersOptions.value = arr
}

userOptions()

const centroLoad = async () => {
  const resultData = await useApi<any>(createUrl('/hr/centro_di_costo/get_list'))

  const arr = []
  Object.keys(resultData.data.value).forEach(key => {
    arr.push({ text: resultData.data.value[key].centro_di_costo, value: resultData.data.value[key].valore })
  })

  centroOptions.value = arr
}

centroLoad()

const save = async () => {
  if (editedItem.value.user_id && editedItem.value.centro_ci_costo) {
    let path = '/hr/approvatori/store/'
    if (editedItem.value.id)
      path = `/hr/approvatori/update/${editedItem.value.id}`

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
  editedItem.value.disattivo = editedItem.value.disattivo === '1'
  editedItem.value.notifica = editedItem.value.notifica === '1'
  editedItem.value.user_id = Number.parseInt(editedItem.value.user_id)
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
          <!-- 👉 User -->
          <VCol
            cols="12"
            sm="4"
          >
            <AppTextField
              v-model="userFilter"
              :label="$t('Label.User')"
              :placeholder="$t('Label.User')"
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
              v-model="centroFilter"
              :label="$t('Label.Centro-Di-Costo')"
              :placeholder="$t('Label.Centro-Di-Costo')"
              :items="centroOptions"
              item-title="text"
              item-value="value"
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
              :items="[{ title: 'No', value: 1 }, { title: 'Si', value: 0 }]"
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
            v-if="can(DefineAbilities.hr_requests_admin.action, DefineAbilities.hr_requests_admin.subject)"
            prepend-icon="tabler-plus"
            color="success"
            @click="newItem"
          >
            Nuovo Approvatore
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
        <template #item.user="{ item }">
          <div
            v-if="item.user === null"
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

        <template #item.notifica="{ item }">
          <div
            v-if="item.notifica === '1'"
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
              v-if="can(DefineAbilities.hr_requests_admin.action, DefineAbilities.hr_requests_admin.subject)"
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
      :title="editedItem.id ? `${$t('Label.Modifica')} Approvatore` : `${$t('Label.Nuovo')} Approvatore`"
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
                <!-- 👉 User -->
                <VCol cols="12">
                  <AppSelect
                    v-model="editedItem.user_id"
                    :label="$t('Label.User')"
                    :placeholder="$t('Label.User')"
                    :items="usersOptions"
                    item-title="full_name"
                    item-value="id"
                    :readonly="!!editedItem.id"
                    chips
                    eager
                  />
                </VCol>

                <!-- 👉 Centro Di Costo -->
                <VCol cols="6">
                  <AppSelect
                    v-model="editedItem.centro_ci_costo"
                    :label="$t('Label.Centro-Di-Costo')"
                    :placeholder="$t('Label.Centro-Di-Costo')"
                    :items="centroOptions"
                    item-title="text"
                    item-value="value"
                    chips
                    eager
                  />
                </VCol>
                <!-- 👉 Livello -->
                <VCol cols="6">
                  <AppTextField
                    v-model="editedItem.livello"
                    type="number"
                    :label="$t('Label.Livello')"
                    :placeholder="$t('Label.Livello')"
                  />
                </VCol>

                <VCol
                  cols="12"
                  class="mt-8"
                >
                  <VSwitch
                    v-model="editedItem.notifica"
                    :label="$t('Label.Notifica-Attiva')"
                  />
                </VCol>

                <VCol
                  cols="12"
                  class="mt-8"
                >
                  <VSwitch
                    v-model="editedItem.disattivo"
                    :label="$t('Label.Approvatore-Disattivato')"
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
