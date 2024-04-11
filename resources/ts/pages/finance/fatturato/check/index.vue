<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { useI18n } from 'vue-i18n'
import { VForm } from 'vuetify/components/VForm'
import { can } from '@layouts/plugins/casl'
import DefineAbilities from '@/plugins/casl/DefineAbilities'

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
const materialeFilter = ref('')
const dataFilter = ref('')
const tipologiaCavoFilter = ref('')
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
  nome: '',
  nome_gp: '',
  report_gp: 0,
  ativo: 0,
  lavorazione: 0,
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

  const { data: resultData, error } = await useApi<any>(createUrl('/fi/turnover/check/list', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      materiale: materialeFilter.value,
      data: dataFilter.value,
      tipologiaCavo: tipologiaCavoFilter.value,
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
  { title: t('Table.Data'), key: 'data_documento' },
  { title: t('Table.Materiale'), key: 'materiale' },
  { title: t('Table.Cliente'), key: 'cliente' },
  { title: t('Table.Tipologia-Cavo'), key: 'tipologia_cavo' },
  { title: t('Table.Unit'), key: 'unit' },
  { title: t('Table.Quantita'), key: 'quantita', align: 'end' },
  { title: t('Table.Amount'), key: 'importo_valuta_locale', align: 'end' },
  { title: t('Table.Documento Tipo'), key: 'documento_tipo' },
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
  if (editedItem.value.quantita) {
    isLoading.value = true

    const retuenData = await $api(`fi/turnover/quantita/${editedItem.value.id}`, {
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

const euro = new Intl.NumberFormat('it-IT', {
  style: 'currency',
  currency: 'EUR',
})

const editItem = (item: object) => {
  editedIndex.value = serverItems.value.indexOf(item)

  editedItem.value = { ...item }
  editedItem.value.attivo = editedItem.value.attivo === '1'
  editedItem.value.report_gp = editedItem.value.report_gp === '1'
  console.log(editedItem.value)
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
          <!-- 👉 Materiale -->
          <VCol
            cols="12"
            sm="4"
          >
            <AppTextField
              v-model="materialeFilter"
              :label="$t('Label.Materiale')"
              :placeholder="$t('Materiale')"
              clearable
              clear-icon="tabler-x"
              @focusout="loadItems"
            />
          </VCol>

          <!-- 👉 tipologia Cavo -->
          <VCol
            cols="12"
            sm="4"
          >
            <AppSelect
              v-model="tipologiaCavoFilter"
              :label="$t('Label.Tipologia-Cavo')"
              :placeholder="$t('Tipologia-Cavo')"
              :items="[{ title: 'Rame', value: 5441 }, { title: 'Ottico', value: 5420 }]"
              clearable
              clear-icon="tabler-x"
              @focusout="loadItems"
            />
          </VCol>

          <!-- 👉 Data -->
          <VCol
            cols="12"
            sm="4"
          >
            <AppDateTimePicker
              v-model="dataFilter"
              :label="$t('Label.Data')"
              :placeholder="$t('Label.Data')"
              :config="{ mode: 'range' }"
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

      </VCardText>
      <!-- 👉 Datatable  -->
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        :headers="headers"
        :items="serverItems"
        :items-length="totalItems"
        :loading="loading"
        height="600"
        fixed-header
        @update:options="updateOptions"
      >
        <template #item.importo_valuta_locale="{ item }">
          <p class="text-success">
            {{ euro.format(item.importo_valuta_locale) }}
          </p>
        </template>

        <template #item.quantita="{ item }">
          <p
            v-if="item.quantita === '.000'"
            class=""
          >
            0
          </p>
          <p
            v-else
            class=""
          >
            {{ item.quantita }}
          </p>
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }">
          <div class="d-flex gap-1">
            <IconBtn @click="editItem(item)"
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
                <!-- ol -->
                <!-- 👉 Macchina -->
                <VCol cols="12">
                  <AppTextField
                    v-model="editedItem.quantita"
                    :rules="[requiredValidator]"
                    :label="$t('Label.Quantita')"
                    :placeholder="$t('Label.Quantita')"
                  />
                </VCol>

                <!-- 👉 unit -->
                <VCol
                  cols="12"
                  sm="4"
                >
                  <AppSelect
                    v-model="editedItem.unit"
                    :label="$t('Label.Unit')"
                    :placeholder="$t('Label.Unit')"
                    :items="[{ title: 'Metri', value: 'M' }, { title: 'Chilometri', value: 'KM' }]"
                    clearable
                    clear-icon="tabler-x"
                    @focusout="loadItems"
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
