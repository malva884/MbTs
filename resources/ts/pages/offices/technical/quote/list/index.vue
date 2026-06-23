<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { useI18n } from 'vue-i18n'
import { VForm } from 'vuetify/components/VForm'
import { can } from '@layouts/plugins/casl'
import DefineAbilities from '@/plugins/casl/DefineAbilities'
import type { Preventivo } from '@/views/offices/technical/quote/type'
import type {Conformita} from "@/views/quality/conformita/type";

definePage({
  meta: {
    action: 'list',
    subject: 'Preventivi',
  },
})

const { t } = useI18n()
const dataCorrente = new Date()
const itemsPerPage = ref(100)
const loading = ref(true)
const refForm = ref<VForm>()
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const page = ref(1)
const serverItems = ref<any>([])
const isSnackbarScrollReverseVisible = ref(false)
const isDialogLoading = ref(false)
const message = ref('')
const color = ref('')
const editDialog = ref(false)
const copiaDialog = ref(false)
const isLoading = ref(false)
const deleteDialog = ref(false)
const clienteFilter = ref()
const numeroFilter = ref()
const annoFilter = ref()
const cavoFilter = ref()
const copiaPreventivo = ref<any>({})
const cliente = ref()
// Statistiche riassuntive
const stats = computed(() => {
  const items = serverItems.value || []
  const totali = items.length
  const clientiUnici = new Set(items.map((i: any) => i.cliente_id)).size
  const totaleCavi = items.reduce((sum: number, i: any) => sum + (i.num_cavi || 0), 0)
  return { totali, clientiUnici, totaleCavi }
})

const defaultItem = ref<Preventivo>({
  id: null,
  user: null,
  numero: '',
  rdo: '',
  paramentro: '',
  cliente_id: '',
  cliente_obj: {
    id: '',
    ragione_sociale: '',
    codice_sap: null,
  },
  cu: null,
  data_rdo: '',
  data_preventivo: '',
  nota: '',
})

function new_defaultItem() {
  defaultItem.value = {
    id: null,
    user: null,
    numero: '',
    rdo: '',
    paramentro: '',
    cliente_id: '',
    cliente_obj: {
      id: '',
      ragione_sociale: '',
      codice_sap: null,
    },
    cu: null,
    data_rdo: '',
    data_preventivo: '',
    nota: '',
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

  const { data: resultData, error } = await useApi<any>(createUrl('/to/preventivi/list', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      numero: numeroFilter.value,
      cliente: clienteFilter.value,
      cavo: cavoFilter.value,
      anno: annoFilter.value,
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
  {
    title: t('Label.Numero'),
    align: 'start',
    sortable: true,
    key: 'numero',
  },
  { title: t('Label.Rdo'), key: 'rdo', sortable: false },
  { title: t('Table.Cliente'), key: 'ragione_sociale', sortable: false },
  { title: t('Label.Data-Preventivo'), key: 'data_preventivo', sortable: true },
  { title: t('Label.Cavi'), key: 'num_cavi', sortable: false, align: 'center' },
  { title: t('Label.Lista-Cavi'), key: 'cables', sortable: false },
  { title: t('Label.Base-Cu'), key: 'cu', sortable: false, align: 'end' },
  { title: 'ACTIONS', key: 'actions', sortable: false, align: 'center' },
]

const clientiOptions = ref([])
const { data: clientiData } = await useApi<any>(createUrl('/to/clienti/get_list/'))

const save = async () => {
  isLoading.value = true
  refForm.value?.validate().then(async ({ valid }) => {
    if (valid) {
      let path = 'to/preventivi/stored'
      // eslint-disable-next-line eqeqeq
      if (editedItem.value.id != undefined)
        path = `to/preventivi/update/${editedItem.value.id}`

      const retuenData = await $api(path, {
        method: 'POST',
        body: editedItem.value,
      })

      nextTick(() => {
        refForm.value?.reset()
        refForm.value?.resetValidation()
        loadItems()
      })
	  editDialog.value = false
    }
  })
  isLoading.value = false
}

const newItem = () => {
  new_defaultItem()
  editedIndex.value = -1
  editedItem.value = { ...defaultItem.value }
  editedItem.value.data_rdo = ref(`${dataCorrente.getFullYear()}-${dataCorrente.getMonth()+1}-${dataCorrente.getUTCDate()}`)

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
  editDialog.value = true
}

const copy = (item: Preventivo) => {
  copiaPreventivo.value = { ...item }
  copiaPreventivo.value.numero = `${copiaPreventivo.value.numero} (Copy)`
  copiaDialog.value = true
}

const saveCopy = async () => {
  isDialogLoading.value = true
  refForm.value?.validate().then(async ({ valid }) => {
    if (valid) {
      const retuenData = await $api(`to/preventivi/duplicate/${copiaPreventivo.value.id}`, {
        method: 'POST',
        body: {
          numero: copiaPreventivo.value.numero,
          cliente: cliente.value,
        },
      })

      nextTick(() => {
        refForm.value?.reset()
        refForm.value?.resetValidation()
        loadItems()
        copiaDialog.value = false
        message.value = retuenData.message
        color.value = retuenData.color
        isSnackbarScrollReverseVisible.value = true
      })
    }
  })
  isDialogLoading.value = false
}

const deleteItem = (item: Preventivo) => {
  editedIndex.value = serverItems.value.indexOf(item)
  editedItem.value = { ...item }
  deleteDialog.value = true
}

const closeDelete = () => {
  deleteDialog.value = false
  editedIndex.value = -1
  editedItem.value = { ...defaultItem.value }
}

const deleteItemConfirm = async () => {
  isLoading.value = true

  const retuenData = await $api(`/to/preventivi/delete/${editedItem.value.id}`, {
    method: 'DELETE',
  })

  await loadItems()

  closeDelete()
  isLoading.value = false
  message.value = retuenData.message
  color.value = retuenData.color
  isSnackbarScrollReverseVisible.value = true
}

onMounted(() => {
  clientiOptions.value = clientiData.value
})
</script>

<template>
  <div class="workspace-container w-100 h-100 d-flex flex-column pa-4 overflow-hidden">
    <VSnackbar
      v-model="isSnackbarScrollReverseVisible"
      transition="scroll-y-reverse-transition"
      location="top center"
      :color="color"
    >
      {{ $t(message) }}
    </VSnackbar>

    <!-- Statistiche -->
    <VRow class="mb-3 flex-shrink-0">
      <VCol cols="12" sm="4">
        <VCard class="pa-4 d-flex align-center gap-3" color="primary" variant="tonal">
          <VIcon icon="tabler-file-invoice" size="32" />
          <div>
            <div class="text-h5 font-weight-bold">{{ stats.totali }}</div>
            <div class="text-caption">Preventivi</div>
          </div>
        </VCard>
      </VCol>
      <VCol cols="12" sm="4">
        <VCard class="pa-4 d-flex align-center gap-3" color="success" variant="tonal">
          <VIcon icon="tabler-users" size="32" />
          <div>
            <div class="text-h5 font-weight-bold">{{ stats.clientiUnici }}</div>
            <div class="text-caption">Clienti</div>
          </div>
        </VCard>
      </VCol>
      <VCol cols="12" sm="4">
        <VCard class="pa-4 d-flex align-center gap-3" color="info" variant="tonal">
          <VIcon icon="tabler-jump-rope" size="32" />
          <div>
            <div class="text-h5 font-weight-bold">{{ stats.totaleCavi }}</div>
            <div class="text-caption">Cavi Totali</div>
          </div>
        </VCard>
      </VCol>
    </VRow>

    <!-- Header -->
    <div class="d-flex align-center justify-space-between flex-wrap gap-x-4 gap-y-2 mb-3 flex-shrink-0">
      <div class="d-flex align-baseline gap-2">
        <h3 class="text-h5 font-weight-bold mb-0">
          {{ $t('Label.Preventivi') || 'Preventivi' }}
        </h3>
        <span class="text-caption text-medium-emphasis d-none d-sm-inline">
          — Gestione preventivi e offerte
        </span>
      </div>
      <VBtn
        v-if="can(DefineAbilities.preventivi_create.action, DefineAbilities.preventivi_create.subject)"
        prepend-icon="tabler-plus"
        color="primary"
        variant="flat"
        density="comfortable"
        class="px-3"
        @click="newItem"
      >
        {{ $t('Button.Nuovo-Preventivo') }}
      </VBtn>
    </div>

    <!-- Card con tabella -->
    <VCard variant="outlined" class="bg-surface border-thin rounded-lg d-flex flex-column flex-grow-1 overflow-hidden">

      <!-- Toolbar filtri -->
      <div class="filter-toolbar px-4 py-3 border-b border-thin flex-shrink-0">
        <VRow dense>
          <VCol cols="12" sm="6" md="3">
            <AppTextField
              v-model="numeroFilter"
              :label="$t('Label.Numero')"
              :placeholder="$t('Label.Numero')"
              prepend-inner-icon="tabler-search"
              hide-details
              density="compact"
              clearable
              clear-icon="tabler-x"
              @keyup.enter="loadItems"
              @focusout="loadItems"
              @click:clear="setTimeout(() => { numeroFilter = ''; loadItems() }, 50)"
            />
          </VCol>
          <VCol cols="12" sm="6" md="3">
            <AppTextField
              v-model="cavoFilter"
              :label="$t('Label.Cavo')"
              :placeholder="$t('Label.Cavo')"
              hide-details
              density="compact"
              clearable
              clear-icon="tabler-x"
              @keyup.enter="loadItems"
              @focusout="loadItems"
              @click:clear="setTimeout(() => { cavoFilter = ''; loadItems() }, 50)"
            />
          </VCol>
          <VCol cols="12" sm="6" md="4">
            <AppSelect
              v-model="clienteFilter"
              :label="$t('Label.Clienti')"
              :placeholder="$t('Label.Clienti')"
              :items="clientiOptions"
              :item-title="item => item.ragione_sociale"
              :item-value="item => item.id"
              hide-details
              density="compact"
              clearable
              clear-icon="tabler-x"
              @update:model-value="loadItems"
              @click:clear="setTimeout(() => { clienteFilter = null; loadItems() }, 50)"
            />
          </VCol>
          <VCol cols="12" sm="6" md="2">
            <AppTextField
              v-model="annoFilter"
              :label="$t('Label.Anno')"
              :placeholder="$t('Label.Anno')"
              hide-details
              density="compact"
              clearable
              clear-icon="tabler-x"
              @keyup.enter="loadItems"
              @focusout="loadItems"
              @click:clear="setTimeout(() => { annoFilter = ''; loadItems() }, 50)"
            />
          </VCol>
        </VRow>
      </div>

      <!-- Tabella -->
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        :headers="headers"
        :items="serverItems"
        :items-length="totalItems"
        :loading="loading"
        item-value="id"
        density="comfortable"
        class="flex-grow-1"
        style="min-height: 400px;"
        @update:options="updateOptions"
      >
        <template #no-data>
          <div class="py-10 text-center">
            <VIcon icon="tabler-file-invoice" size="40" class="text-disabled mb-2" />
            <p class="text-body-1 text-disabled mb-0">Nessun preventivo trovato</p>
          </div>
        </template>
        <template #item.numero="{ item }">
          <VChip
            size="small"
            color="primary"
            variant="flat"
            class="font-weight-bold cursor-pointer font-monospace"
          >
            <RouterLink
              :to="{ name: 'offices-technical-quote-view-id', params: { id: item.id } }"
              class="text-white text-decoration-none"
            >
              {{ item.numero }}
            </RouterLink>
          </VChip>
        </template>
        <template #item.rdo="{ item }">
          <span class="text-body-2 text-medium-emphasis">{{ item.rdo }}</span>
        </template>
        <template #item.ragione_sociale="{ item }">
          <span class="text-body-1 font-weight-medium">{{ item.ragione_sociale }}</span>
        </template>
        <template #item.data_preventivo="{ item }">
          <span class="text-body-2 text-medium-emphasis">{{ formatDate(item.data_preventivo) }}</span>
        </template>
        <template #item.cu="{ item }">
          <span class="text-body-2 font-weight-medium">{{ item.cu }}</span>
        </template>
        <template #item.num_cavi="{ item }">
          <VChip size="small" color="primary" variant="tonal">
            {{ item.num_cavi || 0 }}
          </VChip>
        </template>
        <template #item.cables="{ item }">
          <div class="d-flex flex-wrap gap-1">
            <VChip
              v-for="(cavo, idx) in (item.cables || []).slice(0, 3)"
              :key="idx"
              size="x-small"
              color="secondary"
              variant="tonal"
              class="text-caption"
            >
              {{ cavo.codice }}
            </VChip>
            <VMenu v-if="(item.cables || []).length > 3" location="top" offset-y>
              <template #activator="{ props: menuProps }">
                <VChip
                  v-bind="menuProps"
                  size="x-small"
                  color="grey"
                  variant="tonal"
                  class="cursor-pointer"
                >
                  +{{ (item.cables || []).length - 3 }}
                </VChip>
              </template>
              <VCard min-width="200">
                <VCardText class="pa-2">
                  <div class="text-caption text-medium-emphasis mb-1">Cavi nel preventivo:</div>
                  <div class="d-flex flex-wrap gap-1">
                    <VChip
                      v-for="(cavo, idx) in (item.cables || []).slice(3)"
                      :key="idx"
                      size="x-small"
                      color="secondary"
                      variant="tonal"
                    >
                      {{ cavo.codice }}
                    </VChip>
                  </div>
                </VCardText>
              </VCard>
            </VMenu>
          </div>
        </template>
        <template #item.actions="{ item }">
          <div class="d-flex gap-1 justify-center">
            <IconBtn
              v-if="can(DefineAbilities.preventivi_edit.action, DefineAbilities.preventivi_edit.subject)"
              color="warning"
              @click="editItem(item)"
            >
              <VIcon icon="tabler-edit"/>
            </IconBtn>
            <IconBtn
              v-if="can(DefineAbilities.preventivi_create.action, DefineAbilities.preventivi_create.subject)"
              color="primary"
              @click="copy(item)"
            >
              <VIcon icon="tabler-copy"/>
            </IconBtn>
            <IconBtn
              v-if="can(DefineAbilities.preventivi_create.action, DefineAbilities.preventivi_create.subject)"
              color="error"
              @click="deleteItem(item)"
            >
              <VIcon icon="tabler-trash"/>
            </IconBtn>
          </div>
        </template>
      </VDataTableServer>
    </VCard>
  </div>

  <!-- 👉 Edit Dialog  -->
  <VDialog
    v-model="editDialog"
    persistent
    max-width="800"
  >
    <VCard>
      <VCardTitle class="text-h5 pa-4 pb-2 d-flex align-center gap-2">
        <VIcon icon="tabler-file-invoice" size="24" />
        {{ editedItem.id ? `${$t('Label.Modifica')} Preventivo` : `${$t('Label.Nuovo')} Preventivo` }}
      </VCardTitle>
      <VDivider />
      <VCardText class="pa-4">
        <VForm ref="refForm" @submit.prevent="save">
          <VRow dense>
            <VCol cols="12" sm="4">
              <AppTextField
                v-model="editedItem.numero"
                :label="$t('Label.Numero')"
                :placeholder="$t('Label.Numero')"
                :rules="[requiredValidator]"
              />
            </VCol>
            <VCol cols="12" sm="4">
              <AppTextField
                v-model="editedItem.cu"
                type="number"
                :label="$t('Label.Base-Cu')"
                :placeholder="$t('Label.Base-Cu')"
                :rules="[requiredValidator]"
                min="0"
              />
            </VCol>
            <VCol cols="12" sm="4">
              <AppTextField
                v-model="editedItem.parametro"
                type="number"
                :label="$t('Label.Parametro')"
                :placeholder="$t('Label.Parametro')"
                :rules="[requiredValidator]"
                min="0"
              />
            </VCol>
            <VCol cols="12">
              <VAutocomplete
                v-model="editedItem.cliente_id"
                :label="$t('Label.Cliente')"
                :placeholder="$t('Label.Cliente')"
                :items="clientiOptions"
                :item-title="item => item.ragione_sociale"
                :item-value="item => item.id"
                :rules="[requiredValidator]"
                clearable
                hide-details="auto"
                density="comfortable"
                prepend-inner-icon="tabler-search"
              />
            </VCol>
            <VCol cols="12" sm="6">
              <AppTextField
                v-model="editedItem.rdo"
                :label="$t('Label.Rdo')"
                :placeholder="$t('Label.Rdo')"
                :rules="[requiredValidator]"
              />
            </VCol>
            <VCol cols="12" sm="6">
              <AppDateTimePicker
                v-model="editedItem.data_rdo"
                :label="$t('Label.Del')"
                :placeholder="$t('Label.Del')"
                :rules="[requiredValidator]"
                clearable
                clear-icon="tabler-x"
              />
            </VCol>
            <VCol cols="12">
              <AppTextField
                v-model="editedItem.nota"
                :label="$t('Label.Nota')"
                :placeholder="$t('Label.Nota')"
              />
            </VCol>
          </VRow>
        </VForm>
      </VCardText>
      <VDivider />
      <VCardActions class="pa-4">
        <VSpacer />
        <VBtn
          color="error"
          variant="tonal"
          @click="close"
        >
          Annulla
        </VBtn>
        <VBtn
          color="primary"
          variant="flat"
          @click="refForm?.validate().then(({ valid }) => { if (valid) save() })"
        >
          Salva
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <!-- 👉 Copia Dialog  -->
  <VDialog v-model="copiaDialog" max-width="600">
    <VCard>
      <VCardTitle class="text-h5 pa-4 pb-2 d-flex align-center gap-2">
        <VIcon icon="tabler-copy" size="24" />
        {{ $t('Label.Duplica-Cavo') }}
      </VCardTitle>
      <VDivider />
      <VCardText class="pa-4">
        <VForm ref="refForm" @submit.prevent="saveCopy">
          <VRow dense>
            <VCol cols="12" sm="6">
              <AppTextField
                v-model="copiaPreventivo.numero"
                :label="$t('Label.Numero-Preventivo')"
                :placeholder="$t('Label.Numero-Preventivo')"
                :rules="[requiredValidator]"
              />
            </VCol>
            <VCol cols="12" sm="6">
              <VAutocomplete
                v-model="cliente"
                :label="$t('Label.Clienti')"
                :placeholder="$t('Label.Clienti')"
                :items="clientiOptions"
                :item-title="item => item.ragione_sociale"
                :item-value="item => item.id"
                clearable
                hide-details="auto"
                density="comfortable"
                prepend-inner-icon="tabler-search"
              />
            </VCol>
          </VRow>
        </VForm>
      </VCardText>
      <VDivider />
      <VCardActions class="pa-4">
        <VSpacer />
        <VBtn color="error" variant="tonal" @click="copiaDialog = false">
          Annulla
        </VBtn>
        <VBtn color="primary" variant="flat" @click="refForm?.validate().then(({ valid }) => { if (valid) saveCopy() })">
          Duplica
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <!-- 👉 Delete Dialog  -->
  <VDialog v-model="deleteDialog" max-width="400">
    <VCard class="text-center pa-4">
      <VIcon icon="tabler-alert-triangle" size="48" color="error" class="mb-3" />
      <VCardTitle class="text-h6 justify-center">
        Conferma Eliminazione
      </VCardTitle>
      <VCardText class="text-body-1">
        Sei sicuro di voler eliminare questo preventivo?<br>
        <span class="text-caption text-medium-emphasis">Questa azione non può essere annullata.</span>
      </VCardText>
      <VCardActions class="justify-center gap-2">
        <VBtn color="secondary" variant="tonal" @click="closeDelete">
          Annulla
        </VBtn>
        <VBtn color="error" variant="flat" @click="deleteItemConfirm">
          Elimina
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <!-- Dialog -->
  <VDialog
    v-model="isDialogLoading"
    width="300"
  >
    <VCard
      color="primary"
      width="300"
    >
      <VCardText class="pt-3">
        <span class="ml-4 mb-3">Please stand by</span>
        <VProgressLinear
          :size="40"
          color="warning"
          class="mt-3"
          indeterminate
        />
      </VCardText>
    </VCard>
  </VDialog>
</template>
