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
const macchinaFilter = ref('')
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
  nome: '',
  name_gp: '',
  lavorazione: null,
  categoria: null,
  velocita_minima: '',
  id_gp: '',
  attivo: true,
  report_gp: false,
  check_downtime: false,
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

  const { data: resultData } = await useApi<any>(createUrl('/macchine/list', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      macchina: macchinaFilter.value,
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
  { title: t('Label.Macchina'), key: 'nome' },
  { title: t('Label.Id Gp'), key: 'name_gp', sortable: false },
  { title: t('Table.Lavorazione'), key: 'lavorazione' },
  { title: t('Label.Report Gp'), key: 'report_gp', sortable: false },
  { title: t('Label.Attivo'), key: 'attivo' },
  { title: t('Table.Azioni'), key: 'actions', sortable: false },
])

const categorie = [
  { id: 'buffering', titolo: 'Buffering' },
  { id: 'stranding', titolo: 'Stranding' },
  { id: 'jacketing', titolo: 'Jacketing' },
  { id: 'marck', titolo: 'Marck' },
]

const resolveLavorazione = (lavorazione: string | number) => {
  if (Number(lavorazione) === 2)
    return { color: 'warning', text: t('Label.Ottico') }
  else if (Number(lavorazione) === 1)
    return { color: 'success', text: t('Label.Rame') }
  else
    return { color: 'primary', text: t('Label.Entrambi') }
}

const save = async () => {
  const validation = await refForm.value?.validate()

  if (validation && !validation.valid)
    return

  isLoading.value = true

  try {
    let path = '/macchine/store/'
    if (editedItem.value.id)
      path = `/macchine/update/${editedItem.value.id}`

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
    message.value = e?.data?.message || 'Messaggi.Errore-Salvataggio'
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
  editedItem.value.report_gp = Number(editedItem.value.report_gp) === 1
  editedItem.value.check_downtime = Number(editedItem.value.check_downtime) === 1
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
              icon="tabler-engine"
              size="20"
            />
          </VAvatar>
          <div>
            <div class="text-h6 font-weight-medium">
              {{ $t('Label.Gestione-Macchine') }}
            </div>
            <div class="text-caption text-medium-emphasis">
              {{ totalItems }} {{ $t('Label.Macchinari-Registrati') }}
            </div>
          </div>
        </div>
        <div class="d-flex align-center gap-2">
          <VBtn
            v-if="can(DefineAbilities.macchinari_create.action, DefineAbilities.macchinari_create.subject)"
            prepend-icon="tabler-plus"
            color="primary"
            variant="flat"
            density="comfortable"
            class="px-3"
            @click="newItem"
          >
            {{ $t('Label.Nuova-Macchina') }}
          </VBtn>
        </div>
      </VCardText>
      <VDivider />

      <VCardText class="pa-3">
        <VRow class="mb-2">
          <!-- 👉 Macchina -->
          <VCol
            cols="12"
            sm="4"
          >
            <AppTextField
              v-model="macchinaFilter"
              :label="$t('Label.Macchina')"
              :placeholder="$t('Label.Macchina')"
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
              :items="[{ title: $t('Label.Rame'), value: 1 }, { title: $t('Label.Ottico'), value: 2 }, { title: $t('Label.Entrambi'), value: 3 }]"
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
              :items="[{ title: $t('Label.Si'), value: 1 }, { title: $t('Label.No'), value: 0 }]"
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
              icon="tabler-engine"
              size="40"
              class="text-disabled mb-2"
            />
            <p class="text-body-1 text-disabled mb-0">
              {{ $t('Label.Nessun-Macchinario-Trovato') }}
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

        <template #item.report_gp="{ item }">
          <VIcon
            v-if="Number(item.report_gp) === 1"
            color="primary"
            icon="tabler-check"
          />
        </template>

        <template #item.attivo="{ item }">
          <VIcon
            v-if="Number(item.attivo) === 1"
            color="success"
            icon="tabler-check"
          />
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }">
          <div class="d-flex gap-1">
            <IconBtn
              v-if="can(DefineAbilities.macchinari_edit.action, DefineAbilities.macchinari_edit.subject)"
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
      :title="editedItem.id ? `${$t('Label.Modifica')} ${$t('Label.Macchina')}` : `${$t('Label.Nuova')} ${$t('Label.Macchina')}`"
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
                <VCol
                  cols="12"
                  sm="6"
                >
                  <AppTextField
                    v-model="editedItem.name_gp"
                    :label="$t('Label.Id Gp')"
                    :placeholder="$t('Label.Id Gp')"
                  />
                </VCol>

                <!-- 👉 Id Macchina Gp -->
                <VCol
                  cols="12"
                  sm="6"
                >
                  <AppTextField
                    v-model="editedItem.id_gp"
                    :label="$t('Label.Id-Macchina-Gp')"
                    :placeholder="$t('Label.Id-Macchina-Gp')"
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
                    :items="[{ title: $t('Label.Rame'), value: '1' }, { title: $t('Label.Ottico'), value: '2' }, { title: $t('Label.Entrambi'), value: '3' }]"
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
                    :items="categorie"
                    item-title="titolo"
                    item-value="id"
                  />
                </VCol>

                <!-- 👉 Velocita -->
                <VCol cols="12">
                  <AppTextField
                    v-model="editedItem.velocita_minima"
                    :label="$t('Label.Velocita-Minima')"
                    :placeholder="$t('Label.Velocita-Minima')"
                  />
                </VCol>

                <VCol
                  cols="12"
                  sm="4"
                >
                  <VSwitch
                    v-model="editedItem.attivo"
                    :label="$t('Label.Macchina-Attiva')"
                  />
                </VCol>

                <!-- 👉 Report Gp -->
                <VCol
                  cols="12"
                  sm="4"
                >
                  <VSwitch
                    v-model="editedItem.report_gp"
                    :label="$t('Label.Report Gp')"
                  />
                </VCol>

                <!-- 👉 Check Efficenza -->
                <VCol
                  cols="12"
                  sm="4"
                >
                  <VSwitch
                    v-model="editedItem.check_downtime"
                    :label="$t('Label.Check-Efficienza')"
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
            {{ $t('Label.Annulla') }}
          </VBtn>

          <VBtn
            type="submit"
            color="success"
            variant="elevated"
            :loading="isLoading"
            @click="save"
          >
            {{ $t('Label.Salva') }}
          </VBtn>
        </VCardActions>
      </VCard>
    </AppCardActions>
  </VDialog>
</template>
