<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { useI18n } from 'vue-i18n'
import { VForm } from 'vuetify/components/VForm'
import { can } from '@layouts/plugins/casl'
import DefineAbilities from '@/plugins/casl/DefineAbilities'

definePage({
  meta: {
    action: 'list',
    subject: 'It-Assistenza',
  },
})

const { t } = useI18n()
const itemsPerPage = ref(10)
const loading = ref(1)
const refForm = ref<VForm>()
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const utenteFilter = ref('')
const statoFilter = ref()
const numeroFilter = ref('')
const serialeFilter = ref('')
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
  numero_segnalazione: '',
  asset_id: '',
  task_id: '',
  motivazione: '',
  soluzione: '',
  user_id: '',
  stato: '1',
})

function new_defaultItem() {
  defaultItem.value = {
    id: '',
    numero_segnalazione: '',
    asset_id: '',
    task_id: '',
    motivazione: '',
    soluzione: '',
    user_id: '',
    stato: '1',
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

  const { data: resultData } = await useApi<any>(createUrl('/pl/assistance/list', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      utente: utenteFilter.value,
      stato: statoFilter.value,
      numero: numeroFilter.value,
      seriale: serialeFilter.value,
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

const { data: assetList } = useApi<any>(createUrl('/pl/asset/get_list', {
  query: {
    stato: 'Allocated',
  },
}))

// headers
const headers = computed(() => [
  { title: t('Label.Numero'), key: 'numero_segnalazione' },
  { title: t('Label.Host-Name'), key: 'hostName' },
  { title: t('Label.Utente'), key: 'utente' },
  { title: t('Label.Seriale'), key: 'numero_seriale' },
  { title: t('Label.Stato'), key: 'stato' },
  { title: t('Label.Data-Apertura'), key: 'created_at' },
  { title: 'ACTIONS', key: 'actions', sortable: false },
])

const save = async () => {
  if (editedItem.value.asset_id) {
    let path = '/pl/assistance/store/'
    if (editedItem.value.id)
      path = `/pl/assistance/update/${editedItem.value.id}`

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
  editedItem.value.attivo = true
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
  editDialog.value = true
}

const resolveStato = (stato: number) => {
  if (stato == 1)
    return { color: 'primary', text: t('Label.Aperto') }

  return { color: 'success', text: t('Label.Chiuso') }
}

const remoteConnection = (alias: string) => {
  window.location.href = `anydesk:${alias}`
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
          <!-- 👉 Utente -->
          <VCol
            cols="12"
            sm="3"
          >
            <AppTextField
              v-model="utenteFilter"
              :label="$t('Label.Utente')"
              :placeholder="$t('Label.Utente')"
              clearable
              clear-icon="tabler-x"
              @focusout="loadItems"
            />
          </VCol>

          <!-- 👉 Seriale -->
          <VCol
            cols="12"
            sm="3"
          >
            <AppTextField
              v-model="serialeFilter"
              :label="$t('Label.Seriale')"
              :placeholder="$t('Label.Seriale')"
              clearable
              clear-icon="tabler-x"
              @focusout="loadItems"
            />
          </VCol>

          <!-- 👉 Numero -->
          <VCol
            cols="12"
            sm="3"
          >
            <AppTextField
              v-model="numeroFilter"
              :label="$t('Label.Numero')"
              :placeholder="$t('Label.Numero')"
              clearable
              clear-icon="tabler-x"
              @focusout="loadItems"
            />
          </VCol>

          <!-- 👉 Attivo -->
          <VCol
            cols="12"
            sm="3"
          >
            <AppSelect
              v-model="statoFilter"
              :label="$t('Label.Stato')"
              :placeholder="$t('Label.Stato')"
              :items="[{ title: 'Aperto', value: 1 }, { title: 'Chiuso', value: 2 }]"
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
            v-if="can(DefineAbilities.assistance_create.action, DefineAbilities.assistance_create.subject)"
            prepend-icon="tabler-plus"
            color="success"
            @click="newItem"
          >
            Nuovo Intervento
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

        <template #item.stato="{ item }">
          <VChip
            :color="resolveStato(item.stato).color"
            size="small"
          >
            {{ resolveStato(item.stato).text }}
          </VChip>
        </template>

        <!-- date -->
        <template #item.created_at="{ item }">
          <div class="d-flex gap-1">
            {{ formatDate(item.created_at) }}
          </div>
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }">
          <div class="d-flex gap-1">
            <IconBtn
              v-if="item.anydesk_alias != null && can(DefineAbilities.assistance_list.action, DefineAbilities.assistance_list.subject)"
              color="primary"
              @click="remoteConnection(item.anydesk_alias)"
            >
              <VIcon
                icon="tabler-device-remote"
                title="Avvia Connessione Remota"/>
            </IconBtn>
            <IconBtn
              v-if="can(DefineAbilities.assistance_edit.action, DefineAbilities.assistance_edit.subject)"
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

  <!-- 👉 Info Dialog  -->
  <VDialog
    v-model="editDialog"
    max-width="1400px"
  >
    <AppCardActions
      v-model:loading="isLoading"
      :title="editedItem.id ? `${$t('Label.Modifica-Richiesta-Assistenza')}` : `${$t('Label.Nuova-Richiesta-Assistenza')}`"
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
                <!-- 👉 Utente -->
                <VCol
                  cols="12"
                  sm="6"
                  md="6"
                >
                  <AppAutocomplete
                    v-model="editedItem.asset_id"
                    :label="$t('Label.Utente')"
                    :placeholder="$t('Label.Utente')"
                    :items="assetList"
                    :item-title="item => item.utente"
                    :item-value="item => item.id"
                  />
                </VCol>
                <VCol
                  cols="12"
                  sm="6"
                  md="6"
                >
                  <AppAutocomplete
                    v-model="editedItem.asset_id"
                    :label="$t('Label.Seriale')"
                    :placeholder="$t('Label.Seriale')"
                    :items="assetList"
                    :item-title="item => item.numero_seriale"
                    :item-value="item => item.id"
                  />
                </VCol>
                <!-- 👉 Motivazione -->
                <VCol cols="12">
                  <AppTextField
                    v-model="editedItem.motivazione"
                    :label="$t('Label.Motivazione')"
                    :placeholder="$t('Label.Motivazione')"
                  />
                </VCol>
                <!-- 👉 Soluzione -->
                <VCol cols="12">
                  <AppTextarea
                    v-model="editedItem.soluzione"
                    :label="$t('Label.Soluzione')"
                    :placeholder="$t('Label.Soluzione')"
                  />
                </VCol>
                <VCol
                  cols="12"
                  class="mt-8"
                >
                  <AppSelect
                    v-model="editedItem.stato"
                    :items="[{ title: 'Aperto', value: '1' }, { title: 'Chiuso', value: '2' }]"
                    :label="$t('Label.Stato')"
                    :placeholder="$t('Label.Stato')"
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
