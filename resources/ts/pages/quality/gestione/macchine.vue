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
  nome: '',
  nome_gp: '',
  report_gp: 0,
  ativo: 0,
  lavorazione: null,
  categoria: null,
})

function new_defaultItem() {
  defaultItem.value = {
    id: '',
    nome: '',
    nome_gp: '',
    report_gp: 0,
    ativo: 0,
    lavorazione: null,
    categoria: null,
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

  const { data: resultData, error } = await useApi<any>(createUrl('/macchine/list', {
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

// headers
const headers = [
  { title: t('Label.Macchina'), key: 'nome' },
  { title: t('Label.Id Gp'), key: 'name_gp', sortable: false },
  { title: t('Table.Lavorazione'), key: 'lavorazione' },
  { title: t('Label.Report Gp'), key: 'report_gp', sortable: false },
  { title: t('Label.Attivo'), key: 'attivo' },
  { title: 'ACTIONS', key: 'actions', sortable: false },
]

const categorie = [
  { id: 'buffering', titolo: 'Buffering' },
  { id: 'stranding', titolo: 'Stranding' },
  { id: 'jacketing', titolo: 'Jacketing' },
  { id: 'marck', titolo: 'Marck' },
]

const resolveLavorazione = (lavorazione: string) => {
  if (lavorazione === '2')
    return { color: 'warning', text: 'Ottico' }
  else if (lavorazione === '1')
    return { color: 'success', text: 'Rame' }
  else
    return { color: 'primary', text: 'Ottivo/Rame' }
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
  if (editedItem.value.nome) {
    let path = '/macchine/store/'
    if (editedItem.value.id)
      path = `/macchine/update/${editedItem.value.id}`

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
            Nuovo Macchina
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
        <template #item.lavorazione="{ item }">
          <VChip
            :color="resolveLavorazione(item.lavorazione).color"
            size="small"
          >
            {{ resolveLavorazione(item.lavorazione).text }}
          </VChip>
        </template>

        <template #item.report_gp="{ item }">
          <div
            v-if="item.report_gp === '1'"
            class="d-flex gap-1"
          >
            <VIcon
              color="primary"
              icon="tabler-check"
            />
          </div>
          <div
            v-else
            class="d-flex gap-1"
          />
        </template>

        <template #item.attivo="{ item }">
          <div
            v-if="item.attivo === '1'"
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
                    v-model="editedItem.nome"
                    :rules="[requiredValidator]"
                    :label="$t('Label.Macchina')"
                    :placeholder="$t('Label.Macchina')"
                  />
                </VCol>

                <!-- 👉 Nome Gp -->
                <VCol cols="12">
                  <AppTextField
                    v-model="editedItem.name_gp"
                    :label="$t('Label.Id Gp')"
                    :placeholder="$t('Label.Id Gp')"
                  />
                </VCol>

                <!-- 👉 Lavorazione -->
                <VCol cols="12">
                  <AppSelect
                    v-model="editedItem.lavorazione"
                    :label="$t('Label.Lavorazione')"
                    :placeholder="$t('Label.Lavorazione')"
                    :items="[{ title: 'Rame', value: '1' }, { title: 'Ottico', value: '2' }, { title: 'Entrambi', value: '3' }]"
                  />
                </VCol>

                <!-- 👉 Lavorazione -->
                <VCol cols="12">
                  <AppSelect
                    v-model="editedItem.categoria"
                    :label="$t('Label.Categoria')"
                    :placeholder="$t('Label.Categoria')"
                    :items="categorie"
                    item-title="titolo"
                    item-value="id"
                  />
                </VCol>

                <VCol
                  cols="12"
                  class="mt-8"
                >
                  <VSwitch
                    v-model="editedItem.attivo"
                    :label="$t('Label.Machina Attiva')"
                  />
                </VCol>

                <!-- 👉 Report Gp -->
                <VCol
                  cols="12"
                  class="mt-8"
                >
                  <VSwitch
                    v-model="editedItem.report_gp"
                    :label="$t('Label.Report Gp')"
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
