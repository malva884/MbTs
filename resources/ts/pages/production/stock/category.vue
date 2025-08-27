<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { useI18n } from 'vue-i18n'
import { VForm } from 'vuetify/components/VForm'
import { can } from '@layouts/plugins/casl'
import DefineAbilities from '@/plugins/casl/DefineAbilities'

definePage({
  meta: {
    action: 'admin',
    subject: 'Produzione-Business-Intelligence',
  },
})

const { t } = useI18n()
const refForm = ref<VForm>()
const itemsPerPage = ref(10)
let loading = true
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const page = ref(1)
const serverItems = ref<any>([])
const isSnackbarScrollReverseVisible = ref(false)
const message = ref('')
const color = ref('')
const editDialog = ref(false)
const isLoading = ref(false)
const isFormValid = ref(false)
const legendaFilter = ref()
const notificheFilter = ref()

const defaultItem = ref<any>({
  id: null,
  condizioni: '',
  un: '',
  quantita: 0,
  legenda: '',
  utenti_notifica: '',
  tag: '',
  notifica: true,
})

function new_defaultItem() {
  defaultItem.value = {
    id: null,
    condizioni: '',
    un: '',
    quantita: 0,
    legenda: '',
    utenti_notifica: '',
    tag: '',
    notifica: true,
  }
}

const editedItem = ref<any>(defaultItem.value)
const editedIndex = ref(-1)

const headers = [
  { title: t('Label.Legenda'), key: 'legenda' },
  { title: t('Label.Query'), key: 'condizioni' },
  { title: t('Label.Quantita'), key: 'quantita' },
  { title: t('Label.Um'), key: 'un' },
  { title: t('Label.Notifica'), key: 'notifica' },
  { title: 'ACTIONS', key: 'actions', sortable: false },
]

const tag_notifica = [
  { id: 'pr_stock', titolo: 'Prodotto Finito Ottico' },
  { id: 'pr_stock', titolo: 'Semi Lavorato Ottico' },
  { id: 'pr_stock', titolo: 'Prodotto Finito Rame' },
]

const um = [
  { id: 'KG', titolo: 'Kilogrammo' },
  { id: 'KM', titolo: 'Chilometro' },
  { id: 'L', titolo: 'Litri' },
  { id: 'M', titolo: 'Metri' },
  { id: 'M2', titolo: 'Metri Quadrati' },
  { id: 'ML', titolo: 'Millilitro' },
  { id: 'NO', titolo: 'Numero' },
]

const updateOptions = (options: any) => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
  page.value = options.page
  itemsPerPage.value = options.itemsPerPage

  // eslint-disable-next-line @typescript-eslint/no-use-before-define
  loadItems()
}

const loadItems = async () => {
  loading = true

  const { data: resultData } = await useApi<any>(createUrl('/pr/stock/category', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      legenda: legendaFilter.value,
      notifica: notificheFilter.value,
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
  loading = false
}

const save = async () => {
  let path = ''
  if (editedItem.value.id === null)
    path = '/pr/stock/store/'
  else
    path = `/pr/stock/update/${editedItem.value.id}`

  isLoading.value = true

  const retuenData = await $api(path, {
    method: 'POST',
    body: editedItem.value,
  })

  message.value = retuenData.message
  color.value = retuenData.color
  isSnackbarScrollReverseVisible.value = true

  isLoading.value = false
  editDialog.value = false
  await loadItems()
}

const newItem = () => {
  new_defaultItem()
  editedIndex.value = -1
  editedItem.value = { ...defaultItem.value }
  editedItem.value.un = null
  editedItem.value.utenti_notifica = null
  editDialog.value = true
}

const editItem = (item: object) => {
  editedIndex.value = serverItems.value.indexOf(item)

  editedItem.value = { ...item }
  console.log(editedItem.value.utenti_notifica)
  if (editedItem.value.utenti_notifica !== null)
    editedItem.value.utenti_notifica = editedItem.value.utenti_notifica.split(';')
  editedItem.value.notifica = editedItem.value.notifica === '1'
  editDialog.value = true
}

const close = () => {
  isLoading.value = false
  editDialog.value = false
  editedIndex.value = -1
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
</script>

<template>
  <VCol cols="12">
    <VCard
      title="Filters"
      class="mb-6"
    >
      <VCardText>
        <VRow>
          <!-- 👉 Legenda -->
          <VCol
            cols="12"
            sm="4"
          >
            <AppTextField
              v-model="legendaFilter"
              :label="$t('Label.Legenda')"
              clearable
              clear-icon="tabler-x"
              @focusout="loadItems"
            />
          </VCol>
          <!-- 👉 Categoria -->
          <VCol
            cols="12"
            sm="4"
          >
            <AppSelect
              v-model="notificheFilter"
              :label="$t('Label.Notifiche')"
              :placeholder="$t('Label.Notifiche')"
              :items="[{ title: 'Attive', value: true }, { title: 'Disattive', value: false }]"
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
        <!-- Actions -->
        <template #item.actions="{ item }">
          <div class="d-flex gap-1">
            <IconBtn
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
                <!-- 👉 Legenda -->
                <VCol cols="12">
                  <AppTextField
                    v-model="editedItem.condizioni"
                    :rules="[requiredValidator]"
                    :label="$t('Label.Condizioni')"
                    :placeholder="$t('Label.Condizioni')"
                  />
                </VCol>

                <!-- 👉 Legenda -->
                <VCol cols="8">
                  <AppTextField
                    v-model="editedItem.legenda"
                    :rules="[requiredValidator]"
                    :label="$t('Label.Legenda')"
                    :placeholder="$t('Label.Legenda')"
                  />
                </VCol>

                <!-- 👉 Tag -->
                <VCol cols="4">
                  <AppTextField
                    v-model="editedItem.tag"
                    :rules="[requiredValidator]"
                    :label="$t('Label.Tag')"
                    :placeholder="$t('Label.Tag')"
                  />
                </VCol>

                <!-- 👉 Quantita -->
                <VCol cols="12">
                  <AppTextField
                    v-model="editedItem.quantita"
                    :label="$t('Label.Quantita-Minima')"
                    :placeholder="$t('Label.Quantita-Minima')"
                    type="number"
                  />
                </VCol>

                <!-- 👉 Um -->
                <VCol cols="12">
                  <AppSelect
                    v-model="editedItem.un"
                    :label="$t('Label.Um')"
                    :placeholder="$t('Label.Um')"
                    :items="um"
                    item-title="titolo"
                    item-value="id"
                    chips
                    eager
                  />
                </VCol>

                <VCol
                  cols="12"
                  class="mt-8"
                >
                  <VSwitch
                    v-model="editedItem.notifica"
                    :label="$t('Label.Difetto Attivo')"
                  />
                </VCol>

                <!-- 👉 Notifica -->
                <VCol cols="12">
                  <AppAutocomplete
                    v-model="editedItem.utenti_notifica"
                    :label="$t('Label.Utenti-Notifica')"
                    :placeholder="$t('Label.Utenti-Notifica')"
                    :items="guestsOptions"
                    item-title="full_name"
                    item-value="id"
                    chips
                    closable-chips
                    multiple
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

<style scoped lang="scss">

</style>
