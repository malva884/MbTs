<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { useI18n } from 'vue-i18n'
import { VForm } from 'vuetify/components/VForm'
import { onMounted, computed } from 'vue'
import { can } from '@layouts/plugins/casl'
import DefineAbilities from '@/plugins/casl/DefineAbilities'

definePage({
  meta: {
    action: 'list',
    subject: 'Formazioni',
  },
})

const { t } = useI18n()
const itemsPerPage = ref(10)
const loading = ref(true)
const refForm = ref<VForm>()
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const formazioneFilter = ref('')
const obbligatorioFilter = ref('')
const autoCreazioneFilter = ref('')
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
  formazione: '',
  tipologia: 'obbligatoria',
  obbligatorio: null,
  auto_creazione: null,
})

function new_defaultItem() {
  defaultItem.value = {
    id: '',
    formazione: '',
    tipologia: 'obbligatoria',
    obbligatorio: null,
    auto_creazione: null,
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

  const { data: resultData } = await useApi<any>(createUrl('/hr/gestione/formazioni/list', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      formazione: formazioneFilter.value,
      obbligatorio: obbligatorioFilter.value,
      auto: autoCreazioneFilter.value,
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

const headers = computed(() => [
  { title: t('Label.Formazione'), key: 'formazione' },
  { title: 'Tipologia', key: 'tipologia' },
  { title: t('Table.Auto-Generato'), key: 'auto_creazione' },
  { title: 'ACTIONS', key: 'actions', sortable: false },
])

const save = async () => {
  if (editedItem.value.formazione) {
    editedItem.value.obbligatorio = editedItem.value.tipologia === 'obbligatoria'
    let path = '/hr/gestione/formazioni/store'
    if (editedItem.value.id)
      path = `/hr/gestione/formazioni/update/${editedItem.value.id}`

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
  editedItem.value.tipologia = editedItem.value.tipologia || (editedItem.value.obbligatorio === '1' || editedItem.value.obbligatorio === 1 ? 'obbligatoria' : 'professionale')
  editedItem.value.obbligatorio = editedItem.value.obbligatorio === '1' || editedItem.value.obbligatorio === 1 || editedItem.value.obbligatorio === true
  editedItem.value.auto_creazione = editedItem.value.auto_creazione === '1' || editedItem.value.auto_creazione === 1 || editedItem.value.auto_creazione === true
  editDialog.value = true
}

const formazioniOptions = ref([])

const formazioniList = async () => {
  const resultData = await useApi<any>(createUrl('/hr/gestione/formazioni/get_list'))
  const arr = []

  resultData.data.value.data.forEach(value => {
    arr.push({ full_name: value.formazione, id: value.id })
  })
  formazioniOptions.value = arr
}

formazioniList()
</script>

<template>
  <div class="workspace-container w-100 d-flex flex-column pa-4 gap-3">
    <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
      <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
        <div class="d-flex align-center gap-2">
          <VAvatar color="primary" variant="tonal" size="38">
            <VIcon icon="tabler-school" size="20" />
          </VAvatar>
          <div>
            <div class="text-h6 font-weight-medium">Gestione Formazioni</div>
            <div class="text-caption text-medium-emphasis">Gestisci il catalogo dei corsi e le formazioni obbligatorie del personale</div>
          </div>
        </div>
        <div class="d-flex align-center gap-2">
          <VBtn
            prepend-icon="tabler-plus"
            color="primary"
            variant="flat"
            density="comfortable"
            class="px-3"
            @click="newItem"
          >
            Nuova Formazione
          </VBtn>
        </div>
      </VCardText>
      <VDivider />

      <VCardText class="pa-3">
        <VRow class="mb-2">
          <!-- 👉 Cerca Formazione -->
          <VCol cols="12" sm="4">
            <AppTextField
              v-model="formazioneFilter"
              :label="$t('Label.Formazione')"
              placeholder="Cerca corso..."
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-search"
              @keyup.enter="loadItems"
              @click:clear="loadItems"
            />
          </VCol>

          <!-- 👉 Tipologia -->
          <VCol cols="12" sm="4">
            <AppSelect
              v-model="obbligatorioFilter"
              label="Tipologia"
              placeholder="Filtra per tipologia"
              :items="[{ title: 'Obbligatoria', value: 1 }, { title: 'Professionale', value: 0 }]"
              clearable
              clear-icon="tabler-x"
              @update:model-value="loadItems"
            />
          </VCol>

          <!-- 👉 Auto-Generato -->
          <VCol cols="12" sm="4">
            <AppSelect
              v-model="autoCreazioneFilter"
              :label="$t('Label.Auto-Generato')"
              placeholder="Filtra per auto generazione"
              :items="[{ title: 'Si', value: 1 }, { title: 'No', value: 0 }]"
              clearable
              clear-icon="tabler-x"
              @update:model-value="loadItems"
            />
          </VCol>
        </VRow>
      </VCardText>
      <VDivider />

      <!-- 👉 Datatable  -->
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        :headers="headers"
        :items="serverItems"
        :items-length="totalItems"
        :loading="loading"
        @update:options="updateOptions"
      >
        <template #item.tipologia="{ item }">
          <VChip
            label
            size="small"
            :color="item.obbligatorio === '1' || item.obbligatorio === 1 || item.obbligatorio === true ? 'error' : 'info'"
            variant="tonal"
          >
            {{ item.tipologia === 'obbligatoria' || item.obbligatorio === '1' || item.obbligatorio === 1 || item.obbligatorio === true ? 'Obbligatoria' : 'Professionale' }}
          </VChip>
        </template>

        <template #item.auto_creazione="{ item }">
          <VIcon
            v-if="item.auto_creazione === '1' || item.auto_creazione === 1 || item.auto_creazione === true"
            color="success"
            icon="tabler-check"
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

    <VSnackbar
      v-model="isSnackbarScrollReverseVisible"
      transition="scroll-y-reverse-transition"
      location="top central"
      :color="color"
    >
      {{ $t(message) }}
    </VSnackbar>
  </div>

  <!-- 👉 Edit Dialog  -->
  <VDialog
    v-model="editDialog"
    max-width="650px"
    persistent
  >
    <DialogCloseBtn @click="close" />

    <VCard :title="editedItem.id ? `${$t('Label.Modifica')} Formazione` : `${$t('Label.Nuova')} Formazione`">
      <VCardText>
        <VForm
          ref="refForm"
          v-model="isFormValid"
        >
          <VRow>
            <!-- 👉 Formazione -->
            <VCol cols="12">
              <AppTextField
                v-model="editedItem.formazione"
                :rules="[requiredValidator]"
                :label="$t('Label.Formazione')"
                :placeholder="$t('Label.Formazione')"
                prepend-inner-icon="tabler-school"
              />
            </VCol>

            <VCol cols="12" sm="6">
              <AppSelect
                v-model="editedItem.tipologia"
                :items="[{ title: 'Obbligatoria', value: 'obbligatoria' }, { title: 'Professionale', value: 'professionale' }]"
                label="Tipologia"
                prepend-inner-icon="tabler-tag"
                density="comfortable"
              />
            </VCol>

            <VCol cols="12" sm="6">
              <VSwitch
                v-model="editedItem.auto_creazione"
                :label="$t('Label.Auto-Generato')"
                color="primary"
                inset
              />
            </VCol>
          </VRow>
        </VForm>
      </VCardText>

      <VCardText class="d-flex justify-end flex-wrap gap-3">
        <VBtn
          variant="tonal"
          color="secondary"
          @click="close"
        >
          Annulla
        </VBtn>
        <VBtn
          color="primary"
          variant="elevated"
          :loading="isLoading"
          @click="save"
        >
          Salva
        </VBtn>
      </VCardText>
    </VCard>
  </VDialog>
</template>
