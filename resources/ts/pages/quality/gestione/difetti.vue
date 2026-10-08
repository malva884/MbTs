<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { useI18n } from 'vue-i18n'
import { VForm } from 'vuetify/components/VForm'
import { can } from '@layouts/plugins/casl'
import DefineAbilities from '@/plugins/casl/DefineAbilities'

definePage({
  meta: {
    action: 'list',
    subject: 'Difetti',
  },
})

const { t } = useI18n()
const itemsPerPage = ref(10)
const loading = ref(true)
const refForm = ref<VForm>()
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const difettoFilter = ref('')
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

const getDefaultItem = () => ({
  id: '',
  difetto: '',
  categoria: null,
  sl_no: null,
  requisiti: '',
  lavorazione: '0',
  attivo: true,
})

const editedItem = ref<any>(getDefaultItem())
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

  const { data: resultData } = await useApi<any>(createUrl('/difetti/list', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      difetto: difettoFilter.value,
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
const headers = computed(() => [
  { title: t('Label.Difetto'), key: 'difetto' },
  { title: t('Label.Categoria'), key: 'categoria', sortable: false },
  { title: t('Label.Lavorazione'), key: 'lavorazione' },
  { title: t('Label.Attivo'), key: 'attivo' },
  { title: t('Table.Azioni'), key: 'actions', sortable: false },
])

const resolveLavorazione = (lavorazione: string | number) => {
  if (Number(lavorazione) === 2)
    return { color: 'warning', text: 'Ottico' }
  else if (Number(lavorazione) === 1)
    return { color: 'success', text: 'Rame' }
  else
    return { color: 'primary', text: 'Ottico/Rame' }
}

const save = async () => {
  const validation = await refForm.value?.validate()

  if (validation && !validation.valid)
    return

  isLoading.value = true

  try {
    let path = '/difetti/store/'
    if (editedItem.value.id)
      path = `/difetti/update/${editedItem.value.id}`

    const returnData = await $api(path, {
      method: 'POST',
      body: editedItem.value,
    })

    nextTick(() => {
      refForm.value?.reset()
      refForm.value?.resetValidation()
    })
    message.value = returnData.message
    color.value = returnData.color
    isSnackbarScrollReverseVisible.value = true

    editDialog.value = false
    await loadItems()
  }
  catch (e: any) {
    message.value = e?.data?.message || 'Errore durante il salvataggio'
    color.value = 'error'
    isSnackbarScrollReverseVisible.value = true
  }
  finally {
    isLoading.value = false
  }
}

const newItem = () => {
  editedIndex.value = -1
  editedItem.value = getDefaultItem()
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
  editedItem.value.attivo = Number(editedItem.value.attivo) === 1
  editDialog.value = true
}
</script>

<template>
  <div class="workspace-container w-100 d-flex flex-column pa-4 gap-3">
    <VSnackbar
      v-model="isSnackbarScrollReverseVisible"
      transition="scroll-y-reverse-transition"
      location="top center"
      :color="color"
      :timeout="3000"
    >
      {{ $t(message) }}
    </VSnackbar>

    <VCard
      variant="outlined"
      class="bg-surface border-thin rounded-lg"
    >
      <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
        <div class="d-flex align-center gap-2">
          <VAvatar
            color="primary"
            variant="tonal"
            size="38"
          >
            <VIcon
              icon="tabler-bug"
              size="20"
            />
          </VAvatar>
          <div>
            <div class="text-h6 font-weight-medium">
              Gestione Difetti
            </div>
            <div class="text-caption text-medium-emphasis">
              {{ totalItems }} difetti registrati
            </div>
          </div>
        </div>
        <div class="d-flex align-center gap-2">
          <VBtn
            v-if="can(DefineAbilities.difetti_create.action, DefineAbilities.difetti_create.subject)"
            prepend-icon="tabler-plus"
            color="primary"
            variant="flat"
            density="comfortable"
            class="px-3"
            @click="newItem"
          >
            Nuovo Difetto
          </VBtn>
        </div>
      </VCardText>
      <VDivider />
      <VCardText class="pa-3">
        <VRow class="mb-2">
          <!-- 👉 Difetto -->
          <VCol
            cols="12"
            sm="4"
          >
            <AppTextField
              v-model="difettoFilter"
              :label="$t('Label.Difetto')"
              :placeholder="$t('Label.Difetto')"
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-search"
              @keyup.enter="loadItems"
              @click:clear="loadItems"
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
              :items="[{ title: 'Rame', value: 1 }, { title: 'Ottico', value: 2 }, { title: 'Entrambi', value: '0' }]"
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-filter"
              @update:model-value="loadItems"
              @click:clear="loadItems"
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
              prepend-inner-icon="tabler-filter"
              @update:model-value="loadItems"
              @click:clear="loadItems"
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
        density="comfortable"
        hover
        @update:options="updateOptions"
      >
        <template #no-data>
          <div class="py-10 text-center">
            <VIcon
              icon="tabler-bug"
              size="40"
              class="text-disabled mb-2"
            />
            <p class="text-body-1 text-disabled mb-0">
              Nessun difetto trovato
            </p>
          </div>
        </template>
        <template #item.lavorazione="{ item }">
          <VChip
            :color="resolveLavorazione(item.lavorazione).color"
            size="small"
          >
            {{ resolveLavorazione(item.lavorazione).text }}
          </VChip>
        </template>

        <template #item.attivo="{ item }">
          <div
            v-if="Number(item.attivo) === 1"
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
              v-if="can(DefineAbilities.difetti_edit.action, DefineAbilities.difetti_edit.subject)"
              color="primary"
              size="small"
              @click="editItem(item)"
            >
              <VIcon
                icon="tabler-edit"
                size="18"
              />
            </IconBtn>
          </div>
        </template>
      </VDataTableServer>
    </VCard>
  </div>

  <!-- 👉 Edit Dialog  -->
  <VDialog
    v-model="editDialog"
    max-width="800px"
  >
    <AppCardActions
      v-model:loading="isLoading"
      :title="editedItem.id ? `${$t('Label.Modifica')} Difetto` : `${$t('Label.Nuovo')} Difetto`"
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
                <!-- 👉 Difetto -->
                <VCol cols="12">
                  <AppTextField
                    v-model="editedItem.difetto"
                    :rules="[requiredValidator]"
                    :label="$t('Label.Difetto')"
                    :placeholder="$t('Label.Difetto')"
                  />
                </VCol>

                <!-- 👉 Categoria -->
                <VCol
                  cols="12"
                  sm="6"
                >
                  <AppSelect
                    v-model="editedItem.categoria"
                    :label="$t('Label.Categoria')"
                    :placeholder="$t('Label.Categoria')"
                    :items="[{ title: 'PHYSICAL', value: 'PHYSICAL' }, { title: 'OPTICAL', value: 'OPTICAL' }, { title: 'ELECTRICAL', value: 'ELECTRICAL' }]"
                  />
                </VCol>

                <!-- 👉 Lavorazione -->
                <VCol
                  cols="12"
                  sm="6"
                >
                  <AppSelect
                    v-model="editedItem.lavorazione"
                    :label="$t('Label.Lavorazione')"
                    :placeholder="$t('Label.Lavorazione')"
                    :items="[{ title: 'Rame', value: '1' }, { title: 'Ottico', value: '2' }, { title: 'Entrambi', value: '0' }]"
                  />
                </VCol>

                <!-- 👉 Sl No -->
                <VCol
                  cols="12"
                  sm="6"
                >
                  <AppTextField
                    v-model="editedItem.sl_no"
                    :label="$t('Label.Sl_No')"
                    :placeholder="$t('Label.Sl_No')"
                    type="number"
                  />
                </VCol>

                <!-- 👉 Requisiti -->
                <VCol
                  cols="12"
                  sm="6"
                >
                  <AppTextField
                    v-model="editedItem.requisiti"
                    :label="$t('Label.Requisiti')"
                    :placeholder="$t('Label.Requisiti')"
                  />
                </VCol>

                <VCol cols="12">
                  <VSwitch
                    v-model="editedItem.attivo"
                    :label="$t('Label.Difetto Attivo')"
                  />
                </VCol>
              </VRow>
            </VForm>
          </VContainer>
        </VCardText>

        <VCardActions>
          <VSpacer />

          <VBtn
            color="error"
            variant="outlined"
            @click="close"
          >
            Annulla
          </VBtn>
          <VBtn
            color="success"
            variant="elevated"
            :loading="isLoading"
            @click="save"
          >
            Salva
          </VBtn>
        </VCardActions>
      </VCard>
    </AppCardActions>
  </VDialog>
</template>
