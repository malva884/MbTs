<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { useI18n } from 'vue-i18n'
import { VForm } from 'vuetify/components/VForm'
import { can } from '@layouts/plugins/casl'
import DefineAbilities from '@/plugins/casl/DefineAbilities'

definePage({
  meta: {
    action: 'list',
    subject: 'Macchinari',
  },
})

const date = new Date()
const year = date.toLocaleString('default', { year: 'numeric' })
const years = [{value: year-2, title: year-2},{value: year-1, title: year-1},{value: year, title: year}]
const monts = [{value: '01', title: 'Gennaio'},{value: '02', title: 'Febbraio'},{value: '03', title: 'Marzo'},{value: '04', title: 'Aprile'},{value: '05', title: 'Maggio'},
  {value: '06', title: 'Giugno'},{value: '07', title: 'Luglio'},{value: '08', title: 'Agosto'},{value: '09', title: 'Settembre'},{value: 10, title: 'Ottobre'},{value: 11, title: 'Novembre'},
  {value: 12, title: 'Dicembre'}]
const route = useRoute('target-list-id')

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
const editDialog = ref(false)
const isLoading = ref(false)
const isFormValid = ref(false)

const defaultItem = ref<any>({
  id: '',
  target: 0,
  modulo: '',
  titolo: '',
  anno: '',
  mese: '',
})

function new_defaultItem() {
  defaultItem.value = {
    id: '',
    nome: '',
    nome_gp: '',
    report_gp: 0,
    ativo: 0,
    lavorazione: 0,
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

  const { data: resultData, error } = await useApi<any>(createUrl(`/terget/${route.params.id}`, {
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

const resolveModule = (modulo: string) => {
  if (modulo === '1')
    return 'Fatturato'
  else if (modulo === '2')
    return 'Spedito'
  else if (modulo === '3')
    return 'Produzione'
  else
    return '-'
}
const modulo = resolveModule(route.params.id)

const targets = [
  { title: `${modulo} ${t('Label.Cc-Ckm')}`, value: 'ckm_cc' },
  { title: `${modulo} ${t('Label.Ofc-Ckm')}`, value: 'ckm_ofc' },
  { title: `${modulo} ${t('Label.Ofc-Kfkm')}`, value: 'kfkm_ofc' },
  { title: `${modulo} ${t('Label.Ofc-Fkm')}`, value: 'fkm_ofc' },
  { title: `${modulo} ${t('Label.Cc-value')}`, value: 'value_cc' },
  { title: `${modulo} ${t('Label.Ofc-value')}`, value: 'value_ofc' },
]

// headers
const headers = computed(() => [
  { title: t('Label.Modulo'), key: 'tipo' },
  { title: t('Label.Titolo'), key: 'titolo', sortable: false },
  { title: t('Table.Target'), key: 'target' },
  { title: t('Table.Valore'), key: 'valore' },
  { title: t('Label.Periodo'), key: 'data_riferimento' },
  { title: 'ACTIONS', key: 'actions', sortable: false },
])



const save = async () => {
  if (editedItem.value.titolo) {
    let path = '/terget/save/'
    if (editedItem.value.id)
      path = `/terget/edit/${editedItem.value.id}`

    isLoading.value = true

    editedItem.value.modulo = route.params.id

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
  editedItem.value.anno = editedItem.value.data_riferimento.split('-', 2)[0]
  editedItem.value.mese = editedItem.value.data_riferimento.split('-', 2)[1]
  editDialog.value = true
}

const reloadItem = async (item: object) =>{
  const retuenData = await $api('/terget/ricalcola/' + item.id, {
    method: 'POST',
  })

  loadItems()
  message.value = retuenData.message
  color.value = retuenData.color
  isSnackbarScrollReverseVisible.value = true
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
            v-if="can(DefineAbilities.macchinari_create.action, DefineAbilities.macchinari_create.subject)"
            prepend-icon="tabler-plus"
            color="success"
            @click="newItem"
          >
            Nuovo Target
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
        <template #item.tipo="{ item }">
          {{ resolveModule(item.tipo) }}
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
            <IconBtn
              color="warning"
              @click="reloadItem(item)"
            >
              <VIcon icon="tabler-refresh" />
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
      :title="editedItem.id ? `${$t('Label.Modifica')} Target` : `${$t('Label.Nuovo')} Target`"
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
                <!-- 👉 Titolo -->
                <VCol cols="6">
                  <AppSelect
                    v-model="editedItem.titolo"
                    :label="$t('Label.Titolo')"
                    :placeholder="$t('Label.Titolo')"
                    :items="targets"
                  />
                </VCol>

                <!-- 👉 Target -->
                <VCol cols="6">
                  <AppTextField
                    v-model="editedItem.target"
                    type="number"
                    :label="$t('Label.Target')"
                    :placeholder="$t('Label.Target')"
                  />
                </VCol>

                <!-- 👉 Data Anno -->
                <VCol cols="6">
                  <AppSelect
                    v-model="editedItem.anno"
                    :label="$t('Label.Anno')"
                    :placeholder="$t('Label.Anno')"
                    :items="years"
                  />
                </VCol>

                <!-- 👉 Data Anno -->
                <VCol cols="6">
                  <AppSelect
                    v-model="editedItem.mese"
                    :label="$t('Label.Mese')"
                    :placeholder="$t('Label.Mese')"
                    :items="monts"
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
