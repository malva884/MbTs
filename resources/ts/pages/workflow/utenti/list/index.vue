<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { useI18n } from 'vue-i18n'
import { VForm } from 'vuetify/components/VForm'
import { can } from '@layouts/plugins/casl'
import DefineAbilities from '@/plugins/casl/DefineAbilities'

definePage({
  meta: {
    action: 'admin',
    subject: 'Workflow',
  },
})

const { t } = useI18n()
const itemsPerPage = ref(10)
const loading = ref(true)
const refForm = ref<VForm>()
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const userFilter = ref()
const ruoliFilter = ref()
const modelFilter = ref()
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
  user_id: '',
  role_id: '',
  model: '',
  approval_start_date: '',
  disabled: false,

})

function new_defaultItem() {
  defaultItem.value = {
    id: '',
    user_id: '',
    role_id: '',
    model: '',
    approval_start_date: '',
    disabled: false,
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

  const { data: resultData } = await useApi<any>(createUrl('/workflow/utenti/', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      ruoli: ruoliFilter.value,
      model: modelFilter.value,
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
  { title: t('Label.Utente'), key: 'full_name' },
  { title: t('Label.Ruolo'), key: 'role' },
  { title: t('Label.Model'), key: 'model' },
  { title: 'ACTIONS', key: 'actions', sortable: false },
])

const modelOptions = ref([])
const ruoliOptions = ref([])
const utentiOptions = ref([])

const ruoliGet = async () => {
  const model_t = ref()
  editedItem.value.role_id = null

  if (modelFilter.value)
    model_t.value = modelFilter.value
  else
    model_t.value = editedItem.value.model

  const resultData = await useApi<any>(createUrl('/workflow/getRoles', {
    query: {
      model: model_t.value,
    },
  }))

  const arr = []

  resultData.data.value.objs.forEach(value => {
    arr.push({ ruolo: value.role, id: value.id })
  })

  ruoliOptions.value = arr
}

const usersGet = async () => {
  const resultData = await useApi<any>(createUrl('getUsers'))
  const arr = []

  resultData.data.value.data.forEach(value => {

    arr.push({ utente: value.full_name, id: value.id })
  })

  utentiOptions.value = arr
}

const modelGet = async () => {
  const resultData = await useApi<any>(createUrl('/workflow/getModel'))
  const arr = []

  Object.entries(resultData.data.value.objs).forEach(
    ([key, value]) => arr.push({ model: value, id: key }),
  )

  modelOptions.value = arr
}

modelGet()
usersGet()

const modelSetFilter = () => {
  ruoliOptions.value = []
  if (modelFilter.value || editedItem.value.model) {
    ruoliGet()
    loadItems()
  }

}

const save = async () => {
  if (editedItem.value.user_id && editedItem.value.role_id && editedItem.value.model) {
    let path = '/workflow/utenti/store/'
    if (editedItem.value.id)
      path = `/workflow/utenti/update/${editedItem.value.id}`

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
  editedItem.value.disabled = editedItem.value.disabled === '1'
  editDialog.value = true
}

function resolveModel(id: string) {
  return modelOptions.value.find(obj => obj.id === id).model
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
          <!-- 👉 Utenti -->
          <VCol
            cols="12"
            sm="4"
          >
            <AppSelect
              v-model="userFilter"
              :label="$t('Label.Utenti')"
              :placeholder="$t('Label.Utenti')"
              :items="utentiOptions"
              item-title="utente"
              item-value="id"
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
            <AppSelect
              v-model="modelFilter"
              :label="$t('Label.Moduli')"
              :placeholder="$t('Label.Moduli')"
              :items="modelOptions"
              item-title="model"
              item-value="id"
              clearable
              clear-icon="tabler-x"
              @focusout="modelSetFilter"
            />
          </VCol>

          <!-- 👉 Ruoli -->
          <VCol
            cols="12"
            sm="4"
          >
            <AppSelect
              v-model="ruoliFilter"
              :label="$t('Label.Ruolo')"
              :placeholder="$t('Label.Ruolo')"
              :items="ruoliOptions"
              item-title="ruolo"
              item-value="id"
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
            v-if="can(DefineAbilities.macchinari_create.action, DefineAbilities.macchinari_create.subject)"
            prepend-icon="tabler-plus"
            color="success"
            @click="newItem"
          >
            Nuovo Permesso Utente
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
        <template #item.model="{ item }">
          {{ resolveModel(item.model) }}
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }">
          <div class="d-flex gap-1">
            <IconBtn
              v-if="can(DefineAbilities.macchinari_edit.action, DefineAbilities.macchinari_edit.subject)"
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
    persistent
  >
    <AppCardActions
      v-model:loading="isLoading"
      :title="editedItem.id ? `${$t('Label.Modifica')} Permesso Utente` : `${$t('Label.Nuovo')} Permesso Utente`"
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

                <!-- 👉 Utenti -->
                <VCol cols="12">
                  <AppSelect
                    v-model="editedItem.user_id"
                    :label="$t('Label.Utenti')"
                    :placeholder="$t('Label.Utenti')"
                    :rules="[requiredValidator]"
                    :items="utentiOptions"
                    item-title="utente"
                    item-value="id"
                  />
                </VCol>

                <!-- 👉 Modulo -->
                <VCol cols="12">
                  <AppSelect
                    v-model="editedItem.model"
                    :label="$t('Label.Modulo')"
                    :placeholder="$t('Label.Modulo')"
                    :rules="[requiredValidator]"
                    :items="modelOptions"
                    item-title="model"
                    item-value="id"
                    @focusout="modelSetFilter"
                  />
                </VCol>

                <!-- 👉 Ruoli -->
                <VCol cols="12">
                  <AppSelect
                    v-model="editedItem.role_id"
                    :label="$t('Label.Ruolo')"
                    :placeholder="$t('Label.Ruolo')"
                    :items="ruoliOptions"
                    :rules="[requiredValidator]"
                    item-title="ruolo"
                    item-value="id"
                  />
                </VCol>

                <VCol cols="12">
                  <AppDateTimePicker
                    v-model="editedItem.approval_start_date"
                    :label="$t('Label.Data-Inizio-Approvazione')"
                    :placeholder="$t('Label.Data-Inizio-Approvazione')"
                    clearable
                    clear-icon="tabler-x"
                  />
                </VCol>

                <VCol
                  cols="12"
                  class="mt-8"
                >
                  <VSwitch
                    v-model="editedItem.disabled"
                    :label="$t('Label.Disabilita')"
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
