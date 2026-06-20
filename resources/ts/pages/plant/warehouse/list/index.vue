<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { useI18n } from 'vue-i18n'
import { VForm } from 'vuetify/components/VForm'
import moment from 'moment/moment'
import { can } from '@layouts/plugins/casl'
import DefineAbilities from '@/plugins/casl/DefineAbilities'
import type { Warehouse } from '@/views/plant/warehouse/type'

definePage({
  meta: {
    action: 'list',
    subject: 'Plant-Asset',
  },
})

const { t } = useI18n()
const itemsPerPage = ref(10)
const loading = ref(true)
const refForm = ref<VForm>()
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const page = ref(1)
const descrizioneFilter = ref()
const pnInternoFilter = ref()
const serverItems = ref<any>([])
const tipologieList = ref({})
const marcaFilter = ref()
const isSnackbarScrollReverseVisible = ref(false)
const message = ref('')
const color = ref('')
const editDialog = ref(false)
const isLoading = ref(false)
const isFormValid = ref(false)
const data = ref({})
const isDialogLoading = ref(false)
const viewTipologie = ref(false)
const item = ref({})
const items = ['Foo', 'Bar', 'Fizz', 'Buzz']

const defaultItem = ref<Warehouse>({
  id: null,
  marca: '',
  descrizione: '',
  tipologia: '',
  pn_interno: '',
  pn_oem: '',
  quantita_minima: null,
  quantita: null,
  data_fornitura: '',
  prezzo: null,
})

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

  const { data: resultData } = await useApi<any>(createUrl('/pl/warehouse/list', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      descrizione: descrizioneFilter.value,
      marca: marcaFilter.value,
      pnInterno: pnInternoFilter.value,
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
  { title: t('Table.Tipologia'), key: 'tipologia' },
  { title: t('Table.Descrizione'), key: 'descrizione' },
  { title: t('Table.Marca'), key: 'marca', sortable: true },
  { title: t('Table.P/N Interno'), key: 'pn_interno' },
  { title: t('Table.P/N OEM'), key: 'pn_oem', sortable: true },
  { title: t('Table.Quantita'), key: 'quantita' },
  { title: 'ACTIONS', key: 'actions', sortable: false },
]

const loadTipologie = async () => {
  const { data: tipologieData } = await useApi<any>(createUrl('/pl/typology/get_list', {
    query: {
      attivo: 1,
    },
  }))

  tipologieList.value = tipologieData.value
  viewTipologie.value = true
}

loadTipologie()

const resolveTipologia = (tipologia: string) => {
  if (tipologia === 'AC Power')
    return { color: 'warning', text: 'AC Power' }
  else if (tipologia === 'Adapter')
    return { color: 'success', text: 'Adapter' }
  else if (tipologia === 'Cable')
    return { color: 'primary', text: 'Cable' }
  else if (tipologia === 'Consumables')
    return { color: 'secondary', text: 'Consumables' }
  else if (tipologia === 'Device')
    return { color: 'info', text: 'Device' }
  else if (tipologia === 'HUB')
    return { color: 'error', text: 'HUB' }
  else if (tipologia === 'Desktop')
    return { color: 'success', text: 'Desktop' }
  else if (tipologia === 'Laptop')
    return { color: 'success', text: 'Laptop' }
  else
    return { color: 'error', text: '---' }
}

const save = async () => {
  isDialogLoading.value = true
  await $api('pl/asset/import', {
    method: 'POST',
    body: {
      file_upload: data.value,
    },
  })
  loadItems()
  editDialog.value = false
  isDialogLoading.value = false
}

const newItem = () => {
  item.value = { ...defaultItem.value }
  editDialog.value = true
}

const close = () => {
  isLoading.value = false
  editDialog.value = false
  refForm.value?.reset()
}

function formatDate(date: string): string {
  return moment(String(date)).format('YYYY - MMMM')
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
          <!-- 👉 Lavorazione -->
          <VCol
            v-if="viewTipologie"
            cols="12"
            sm="4"
          >
            <AppTextField
              v-model="descrizioneFilter"
              :label="$t('Label.Descrizione')"
              :placeholder="$t('Label.Descrizione')"
              clearable
              clear-icon="tabler-x"
              @focusout="loadItems"
            />
          </VCol>
          <!-- 👉 Marca -->
          <VCol
            cols="12"
            sm="4"
          >
            <AppTextField
              v-model="marcaFilter"
              :label="$t('Label.Marca')"
              :placeholder="$t('Label.Marca')"
              clearable
              clear-icon="tabler-x"
              @focusout="loadItems"
            />
          </VCol>
          <!-- 👉 Pn Interno -->
          <VCol
            cols="12"
            sm="4"
          >
            <AppTextField
              v-model="pnInternoFilter"
              :label="$t('Label.Pn-Interno')"
              :placeholder="$t('Label.Pn-Interno')"
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
            prepend-icon="tabler-plus"
            color="success"
            to="/plant/warehouse/add"
          >
            {{ $t('Label.Nuovo') }}
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
        <!-- Tipologia -->
        <template #item.tipologia="{ item }">
          <div class="d-flex gap-1">
            <IconBtn :color="resolveTipologia(item.tipologia).color">
              {{ resolveTipologia(item.tipologia).text }}
            </IconBtn>
          </div>
        </template>

        <!-- Marca -->
        <template #item.marca="{ item }">
          <div class="d-flex gap-1">
            <VChip
              :label="false"
              color="info"
            >
              {{ item.marca }}
            </VChip>
          </div>
        </template>

        <!-- Descrizione -->
        <template #item.descrizione="{ item }">
          <div class="d-flex align-center">
            <div class="d-flex flex-column">
              <h6 class="text-base">
                <RouterLink
                  :to="{ name: 'plant-warehouse-view-id', params: { id: item.id } }"
                  class="font-weight-medium text-link"
                >
                  {{ item.descrizione }}
                </RouterLink>
              </h6>
            </div>
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
                title="Avvia Connessione Remota"
              />
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
      :title="$t('Label.Nuova-Materiale')"
      no-actions
    >
      <VCard>
        <VCardText>
          <VContainer>
            <VForm @submit.prevent="() => {}">
              <VRow>
                <!-- 👉 Marca -->
                <VCol
                  cols="12"
                  md="6"
                >
                  <AppSelect
                    v-model="item.tipologia"
                    :items="items"
                    :label="$t('local.Tipologia')"
                    :placeholder="$t('local.Tipologia')"
                  />
                </VCol>
                <!-- 👉 Marca -->
                <VCol
                  cols="12"
                  md="6"
                >
                  <AppTextField
                    v-model="item.marca"
                    :label="$t('local.Marca')"
                    :placeholder="$t('local.Marca')"
                  />
                </VCol>

                <!-- 👉 Descrizione -->
                <VCol
                  cols="12"
                  md="6"
                >
                  <AppTextField
                    v-model="item.descrizione"
                    :label="$t('local.Descrizione')"
                    :placeholder="$t('local.Descrizione')"
                  />
                </VCol>

                <!-- 👉 Pn -->
                <VCol
                  cols="12"
                  md="6"
                >
                  <AppTextField
                    v-model="item.pn_interno"
                    :label="$t('local.Pn-interno')"
                    :placeholder="$t('local.Pn-interno')"
                  />
                </VCol>

                <!-- 👉 Pn -->
                <VCol
                  cols="12"
                  md="6"
                >
                  <AppTextField
                    v-model="item.pn_oem"
                    :label="$t('local.Pn-Oem')"
                    :placeholder="$t('local.Pn-Oem')"
                  />
                </VCol>

                <!-- 👉 Quantita -->
                <VCol
                  cols="12"
                  md="6"
                >
                  <AppTextField
                    v-model="item.quantita"
                    :label="$t('local.Quantita')"
                    :placeholder="$t('local.Quantita')"
                  />
                </VCol>

                <!-- 👉 Company -->
                <VCol
                  cols="12"
                  md="6"
                >
                  <AppTextField
                    v-model="item.quantita_minima"
                    :label="$t('local.Quantita-Minima')"
                    :placeholder="$t('local.Quantita-Minima')"
                  />
                </VCol>

                <VCol
                  cols="12"
                  class="d-flex gap-4"
                >
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
                </VCol>
              </VRow>
            </VForm>
          </VContainer>
        </VCardText>
      </VCard>
    </AppCardActions>
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
