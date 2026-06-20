<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { useI18n } from 'vue-i18n'
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
  <VCol cols="12">
    <VCard
      title="Filters"
      class="mb-6"
    >
      <VCardText>
        <VRow>
          <!-- 👉 Dipendente -->
          <VCol
            cols="12"
            sm="3"
          >
            <AppTextField
              v-model="dipendenteFilter"
              :label="$t('Label.Dipendente')"
              clearable
              clear-icon="tabler-x"
              @focusout="loadItems"
            />
          </VCol>

          <!-- 👉 Matricola -->
          <VCol
            cols="12"
            sm="3"
          >
            <AppTextField
              v-model="matricolaFilter"
              :label="$t('Label.Matricola')"
              clearable
              clear-icon="tabler-x"
              @focusout="loadItems"
            />
          </VCol>

          <!-- 👉 Raperto -->
          <VCol
            v-if="repartiData"
            cols="12"
            sm="3"
          >
            <AppSelect
              v-model="repartoFilter"
              :label="$t('Label.Reparto')"
              :placeholder="$t('Label.Reparto')"
              :items="repartiData"
              item-title="reparto"
              item-value="id"
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
            :to="{ name: 'hr-employee-new' }"
          >
            {{ $t('Label.Nuovo-Dipendente') }}
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
        <template #item.nome_completo="{ item }">
          <div class="d-flex align-center">
            <!--VAvatar
              size="34"
              :variant="!item.avatar ? 'tonal' : undefined"
              :color="!item.avatar ? resolveUserRoleVariant(item.role).color : undefined"
              class="me-3"
            >
              <VImg
                v-if="item.avatar"
                :src="path + item.avatar"
              />

              <span v-else>{{ avatarText(item.nome_completo) }}</span>
            </VAvatar -->
            <div class="d-flex flex-column">
              <h6 class="text-base">
                <RouterLink
                  :to="{ name: 'hr-employee-view-id', params: { id: item.id } }"
                  class="font-weight-medium text-link"
                >
                  {{ item.nome_completo }}
                </RouterLink>
              </h6>
            </div>
          </div>
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
            <IconBtn
              color="primary"
              @click="openDrivePage(item.path_drive)"
            >
              <VIcon icon="tabler-brand-google-drive" />
            </IconBtn>
          </div>
        </template>
      </VDataTableServer>
    </VCard>
  </VCol>

</template>
