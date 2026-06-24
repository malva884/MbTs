<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { useI18n } from 'vue-i18n'
import { can } from '@layouts/plugins/casl'
import DefineAbilities from '@/plugins/casl/DefineAbilities'

definePage({
  meta: {
    action: 'list',
    subject: 'Employee',
  },
})

const { t } = useI18n()
const itemsPerPage = ref(10)
const loading = ref(true)
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const dipendenteFilter = ref('')
const attivoFilter = ref()
const repartoFilter = ref()
const matricolaFilter = ref('')
const page = ref(1)
const serverItems = ref<any>([])
const isSnackbarScrollReverseVisible = ref(false)
const message = ref('')
const color = ref('')
const path = import.meta.env.VITE_BASE_URL_PORTALE
const isImporting = ref(false)

const importOldPortal = async () => {
  isImporting.value = true
  try {
    const response = await $api('/hr/dipendenti/import', {
      method: 'POST',
    })
    message.value = response.message
    color.value = response.color || 'success'
    isSnackbarScrollReverseVisible.value = true
    loadItems()
  } catch (error: any) {
    message.value = error.message || 'Errore durante l\'importazione'
    color.value = 'error'
    isSnackbarScrollReverseVisible.value = true
  } finally {
    isImporting.value = false
  }
}

const { data: repartiData } = await useApi<any>('/hr/reparti/getList')

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

  const { data: resultData, error } = await useApi<any>(createUrl('/hr/dipendenti/list', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      dipendente: dipendenteFilter.value,
      matricola: matricolaFilter.value,
      attivo: attivoFilter.value,
      reparto: repartoFilter.value,
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
  { title: t('Label.Dipendente'), key: 'nome_completo' },
  { title: t('Label.Matricola'), key: 'matricola' },
  { title: t('Table.Reparto'), key: 'reparto' },
  { title: 'ACTIONS', key: 'actions', sortable: false },
]

const resolveLavorazione = (lavorazione: string) => {
  if (lavorazione === '2')
    return { color: 'warning', text: 'Ottico' }
  else if (lavorazione === '1')
    return { color: 'success', text: 'Rame' }
  else
    return { color: 'primary', text: 'Ottivo/Rame' }
}

function openDrivePage(path: string) {
  window.open(`https://drive.google.com/drive/u/0/folders/${path}`, '_blank')
}
</script>

<template>
  <div class="workspace-container w-100 d-flex flex-column pa-4 gap-3">
    <VSnackbar v-model="isSnackbarScrollReverseVisible" transition="scroll-y-reverse-transition" location="top center" :timeout="3000">
      {{ $t(message) }}
    </VSnackbar>

    <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
      <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
        <div class="d-flex align-center gap-2">
          <VIcon icon="tabler-users" size="24" color="primary" />
          <div>
            <div class="text-h6 font-weight-medium">Anagrafica Dipendenti</div>
            <div class="text-caption text-medium-emphasis">{{ totalItems }} dipendenti registrati</div>
          </div>
        </div>
        <div class="d-flex align-center gap-2">
          <VBtn
            v-if="can(DefineAbilities.employee_admin.action, DefineAbilities.employee_admin.subject)"
            prepend-icon="tabler-database-import"
            color="secondary"
            variant="outlined"
            density="comfortable"
            class="px-3 me-1"
            :loading="isImporting"
            @click="importOldPortal"
          >
            Sincronizza vecchio gestionale
          </VBtn>
          <VBtn
            v-if="can(DefineAbilities.employee_create.action, DefineAbilities.employee_create.subject)"
            prepend-icon="tabler-plus"
            color="primary"
            variant="flat"
            density="comfortable"
            class="px-3"
            :to="{ name: 'hr-employee-new' }"
          >
            {{ $t('Label.Nuovo-Dipendente') }}
          </VBtn>
        </div>
      </VCardText>
      <VDivider />
      <VCardText class="pa-3">
        <VRow class="mb-2">
          <!-- 👉 Dipendente -->
          <VCol cols="12" sm="3">
            <AppTextField
              v-model="dipendenteFilter"
              :label="$t('Label.Dipendente')"
              placeholder="Cerca dipendente"
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-search"
              @keyup.enter="loadItems"
              @click:clear="loadItems"
            />
          </VCol>

          <!-- 👉 Matricola -->
          <VCol cols="12" sm="3">
            <AppTextField
              v-model="matricolaFilter"
              :label="$t('Label.Matricola')"
              placeholder="Matricola"
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-search"
              @keyup.enter="loadItems"
              @click:clear="loadItems"
            />
          </VCol>

          <!-- 👉 Reparto -->
          <VCol v-if="repartiData" cols="12" sm="3">
            <AppSelect
              v-model="repartoFilter"
              :label="$t('Label.Reparto')"
              placeholder="Seleziona reparto"
              :items="repartiData"
              item-title="reparto"
              item-value="id"
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-filter"
              @update:model-value="loadItems"
              @click:clear="loadItems"
            />
          </VCol>

          <!-- 👉 Attivo -->
          <VCol cols="12" sm="3">
            <AppSelect
              v-model="attivoFilter"
              :label="$t('Label.Attive')"
              placeholder="Tutti"
              :items="[{ title: 'Sì (Attivo)', value: 1 }, { title: 'No (Cessato)', value: 0 }]"
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
            <VIcon icon="tabler-users" size="40" class="text-disabled mb-2" />
            <p class="text-body-1 text-disabled mb-0">Nessun dipendente trovato</p>
          </div>
        </template>
        <template #item.nome_completo="{ item }">
          <div class="d-flex align-center gap-2">
            <VAvatar size="34" variant="tonal" color="primary">
              <VImg v-if="item.avatar" :src="path + item.avatar" />
              <span v-else class="text-caption font-weight-semibold">{{ item.nome?.charAt(0) }}{{ item.cognome?.charAt(0) }}</span>
            </VAvatar>
            <div class="d-flex flex-column">
              <RouterLink
                :to="{ name: 'hr-employee-view-id', params: { id: item.id } }"
                class="font-weight-medium text-primary text-decoration-none"
              >
                {{ item.nome_completo }}
              </RouterLink>
              <span class="text-caption text-medium-emphasis">{{ item.email }}</span>
            </div>
          </div>
        </template>
        <!-- Actions -->
        <template #item.actions="{ item }">
          <div class="d-flex gap-1">
            <IconBtn
              v-if="can(DefineAbilities.employee_admin.action, DefineAbilities.employee_admin.subject)"
              color="primary"
              size="small"
              :to="{ name: 'hr-employee-edit-id', params: { id: item.id } }"
            >
              <VIcon icon="tabler-edit" size="18" />
            </IconBtn>
            <IconBtn
              v-if="item.path_drive"
              color="success"
              size="small"
              @click="openDrivePage(item.path_drive)"
            >
              <VIcon icon="tabler-brand-google-drive" size="18" />
            </IconBtn>
          </div>
        </template>
      </VDataTableServer>
    </VCard>
  </div>
</template>
