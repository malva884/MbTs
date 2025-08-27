<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { useI18n } from 'vue-i18n'


definePage({
  meta: {
    action: 'list',
    subject: 'Qt-Supplier',
  },
})

const { t } = useI18n()
const itemsPerPage = ref(10)
const loading = ref(false)
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const ragioneSocialeFilter = ref()
const codiceSapFilter = ref()
const categoriaFilter = ref()
const page = ref(1)
const serverItems = ref<any>([])
const isSnackbarScrollReverseVisible = ref(false)
const message = ref('')
const color = ref('')
const isview = ref(false)
const certificazioni = ref({})

// headers
const headers = [
  { title: t('Table.Fornitore'), key: 'ragioneSociale', dynamic: false },
  { title: t('Table.Prezzo'), key: 'prezzo', sortable: false, dynamic: false },
  { title: t('Table.Servizio'), key: 'servizio', dynamic: false },
  { title: t('Table.Critico'), key: 'critico', dynamic: false },
  { title: t('Table.Qualificato'), key: 'qualificato', dynamic: false },
  { title: t('Table.Categoria'), key: 'categoria', dynamic: false },
  { title: t('Table.Nazione'), key: 'nazione', dynamic: false },

  // { title: 'ACTIONS', key: 'actions', sortable: false },
]

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

  const { data: resultData, error } = await useApi<any>(createUrl('/qt/supplier/rating', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      ragioneSociale: ragioneSocialeFilter.value,
      cdSap: codiceSapFilter.value,
      categoria: categoriaFilter.value,
    },
  }))

  if (resultData.value !== null) {
    serverItems.value = resultData.value.list.data
    totalItems.value = resultData.value.list.total

    certificazioni.value = resultData.value.certificazioni

    const tp = resultData.value.certificazioni

    Object.keys(tp).forEach(key => {
      headers.push({ title: tp[key].titolo, key: tp[key].id, sortable: false, dynamic: true })
    })
  }
  else {
    serverItems.value = []
    totalItems.value = 0
  }
  isview.value = true
  loading.value = false
}

loadItems()

const restolveValue = (valutazione: any) => {
  if (valutazione === null)
    return ''

  const temp = valutazione.substring(0, 1)

  if (temp === 'A')
    return { label: valutazione, color: 'text-success' }
  else if (temp === 'B')
    return { label: valutazione, color: 'text-warning' }
  else if (temp === 'E')
    return { label: valutazione, color: 'text-error' }
  else if (temp === '0')
    return { label: '<s>Respinto</s>', color: 'text-primary' }
  else if (temp === 'N')
    return { label: '<b>Da Approvare</b>', color: 'text-primary' }
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
          <!-- 👉 Fornitore -->
          <VCol
            cols="12"
            sm="4"
          >
            <AppTextField
              v-model="ragioneSocialeFilter"
              :label="$t('Label.Fornitore')"
              :placeholder="$t('Label.Fornitore')"
              clearable
              clear-icon="tabler-x"
              @focusout="loadItems"
            />
          </VCol>

          <!-- 👉 Codice -->
          <VCol
            cols="12"
            sm="4"
          >
            <AppTextField
              v-model="codiceSapFilter"
              :label="$t('Label.Codice-Sap')"
              :placeholder="$t('Label.Codice-Sap')"
              clearable
              clear-icon="tabler-x"
              @focusout="loadItems"
            />
          </VCol>

          <!-- 👉 Categoria -->
          <VCol
            cols="12"
            sm="4"
          >
            <AppSelect
              v-model="categoriaFilter"
              :label="$t('Label.Categoria')"
              :placeholder="$t('Label.Categoria')"
              :items="[{ title: 'Ausiliari, connessioni', value: 'AUS' }, { value: 'BOB', title: 'Bobine legno e plastica' },
                       { value: 'CCU', title: 'Catodi di rame e vergella' }, { value: 'COL', title: 'Coloranti in granuli e polvere' }, { value: 'FIA', title: 'Filo acciaio ' },
                       { value: 'FIL', title: 'Filo di rame e multifilo rosso, stagnato, argentato' }, { value: 'FIO', title: 'Fibre ottiche monomodali e multimodali' }, { value: 'FIR', title: 'Filati di rinforzo aramidici e tessili' },
                       { value: 'JEL', title: 'Jelly e altri composti per FO' }, { value: 'LEG', title: 'Fili in lega per termocoppie e coax' }, { value: 'MEG', title: 'Mescole in gomma in strisce e granuli' },
                       { title: 'Prodotti per mescole PVC', value: 'MES' }, { title: 'Nastri e semilavorati in alluminio e leghe', value: 'NAA' }, { title: 'Nastro ferro e semilavorati in ferro e zincati', value: 'NAF' },
                       { title: 'Nastro rame', value: 'NAR' }, { title: 'Nastri in materiale sintetico e tessile', value: 'NAS' }, { title: 'Servizi', value: 'SER' },
                       { title: 'Semilavorati e varie', value: 'SLA' }, { title: 'Granuli termoplastici per isolamento e guaina', value: 'TER' }, { title: 'Tubetti in rame e altri profili', value: 'TUB' },
                       { title: 'Vernici ed inchiostri', value: 'VER' },
              ]"
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
        v-if="isview"
        v-model:items-per-page="itemsPerPage"
        :headers="headers"
        :items="serverItems"
        :items-length="totalItems"
        :loading="loading"
        @update:options="updateOptions"
      >
        <template #item.ragioneSociale="{ item }">
          <div class="d-flex align-center">
            <div class="d-flex flex-column">
              <h6 class="text-base">
                <RouterLink
                  :to="{ name: 'quality-fornitori-view-id', params: { id: item.id } }"
                  class="font-weight-medium text-link text-primary"
                >
                  {{ item.ragioneSociale }}
                </RouterLink>
              </h6>
            </div>
          </div>
        </template>

        <template
          v-for="certificazione in certificazioni"
          v-slot:[`item.${certificazione.id}`]="{ item }"

        >
          <slot :name="`item.${certificazione.id}`" :value="item[certificazione.id]" >
            <span :class="restolveValue(item[certificazione.id]).color" v-html="restolveValue(item[certificazione.id]).label"></span>
          </slot>
        </template>

        <template #item.qualificato="{ item }">
          <div
            v-if="item.qualificato === '1'"
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
          >
            <VIcon
              color="error"
              icon="tabler-alert-triangle"
            />
          </div>
        </template>

        <template #item.critico="{ item }">
          <div
            v-if="item.critico === '1'"
            class="d-flex gap-1"
          >
            <VIcon
              color="warning"
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

          </div>
        </template>
      </VDataTableServer>
    </VCard>
  </VCol>
</template>
