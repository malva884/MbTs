<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { useI18n } from 'vue-i18n'
import { VForm } from 'vuetify/components/VForm'
import { can } from '@layouts/plugins/casl'
import DefineAbilities from '@/plugins/casl/DefineAbilities'

definePage({
  meta: {
    action: 'list',
    subject: 'Hr-Richieste',
  },
})

const { t } = useI18n()
const itemsPerPage = ref(10)
const loading = ref(false)
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const dipendenteFilter = ref('')
const matricolaFilter = ref('')
const tipologiaFilter = ref()
const approvatiFilter = ref('100')
const page = ref(1)
const serverItems = ref<any>([])
const isSnackbarScrollReverseVisible = ref(false)
const message = ref('')
const color = ref('')

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

  const { data: resultData, error } = await useApi<any>(createUrl('/hr/requests/list', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      dipendente: dipendenteFilter.value,
      tipologia: tipologiaFilter.value,
      matricola: matricolaFilter.value,
      statoApprovazione: approvatiFilter.value,
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
  { title: t('Table.Dipendente'), key: 'dipendente_cognome' },
  { title: t('Table.Matricola'), key: 'dipendente_matricola' },
  { title: t('Table.Tipo-Richiesta'), key: 'tipologia' },
  { title: t('Table.Stato'), key: 'stato' },
  { title: t('Table.Data-Richiesta'), key: 'data_richiesta' },
]

const resolveTipologia = (tipologia: string) => {
  if (tipologia === '1')
    return { color: 'primary', text: 'Ferie' }
  else if (tipologia === '2')
    return { color: 'giallo', text: '104' }
  else if (tipologia === '101')
    return { color: 'warning', text: 'Annullamento Richiesta' }
  else if (tipologia === '102')
    return { color: 'warning', text: 'Annullamento 104' }
  else if (tipologia === '5')
    return { color: 'white', text: 'Permesso' }
  else
    return { color: 'warning', text: '---' }
}

const resolveStato = (stato: string) => {

  if (stato === '1')
    return { color: 'success', text: 'Approvato' }
  else if (stato === '0')
    return { color: 'warning', text: 'Bocciato' }
  else if(stato === null)
    return { color: 'info', text: 'In Approvazione' }
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
              :placeholder="$t('Label.Dipendente')"
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
              :placeholder="$t('Label.Matricola')"
              clearable
              clear-icon="tabler-x"
              @focusout="loadItems"
            />
          </VCol>

          <!-- 👉 Tipologia -->
          <VCol
            cols="12"
            sm="3"
          >
            <AppSelect
              v-model="tipologiaFilter"
              :label="$t('Label.Tipologia')"
              :placeholder="$t('Label.Tipologia')"
              :items="[{ title: '104', value: 2 }, { title: 'Ferie', value: 1 }, { title: 'Permessi', value: 5 }, { title: 'Tutti', value: '' }]"
              clearable
              clear-icon="tabler-x"
              @focusout="loadItems"
            />
          </VCol>
          <!-- 👉 Tipologia -->
          <VCol
            cols="12"
            sm="3"
          >
            <AppSelect
              v-model="approvatiFilter"
              :label="$t('Label.Stato')"
              :placeholder="$t('Label.Stato')"
              :items="[{ title: 'Da Approvare', value: '100' }, { title: 'Approvati', value: 1 }, { title: 'Bocciati', value: false }]"
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
        @update:options="updateOptions"
      >
        <template #item.dipendente_cognome="{ item }">
          <div class="d-flex align-center">
            <div class="d-flex flex-column">
              <h6 class="text-base">
                <RouterLink
                  :to="{ name: 'hr-richieste-view-id', params: { id: item.id } }"
                  class="font-weight-medium text-link"
                >
                  {{ item.dipendente_cognome + ' ' + item.dipendente_nome }}
                </RouterLink>
              </h6>
            </div>
          </div>
        </template>

        <template #item.tipologia="{ item }">
          <VChip
            :color="resolveTipologia(item.tipologia).color"
            size="small"
          >
            {{ resolveTipologia(item.tipologia).text }}
          </VChip>
        </template>

        <template #item.stato="{ item }">
          <VChip
            :color="resolveStato(item.stato).color"
            size="small"
          >
            {{ resolveStato(item.stato).text }}
          </VChip>
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
      </VDataTableServer>
    </VCard>
  </VCol>
</template>
