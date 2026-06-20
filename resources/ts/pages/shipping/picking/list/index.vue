<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { VForm } from 'vuetify/components/VForm'
import { can } from '@layouts/plugins/casl'
import DefineAbilities from '@/plugins/casl/DefineAbilities'

definePage({
  meta: {
    action: 'report',
    subject: 'Produzione-KPI',
  },
})

const { t } = useI18n()
const serverItems = ref<any>([])
const serverItemsBeath = ref<any>([])
const serverOrdini = ref<any>([])
const numeroMaxOrdini = ref(10)
const itemsPerPage = ref(10)
const itemsPerPageBeath = ref(100)
const loading = ref(true)
const loadingBeath = ref(false)
const totalItems = ref(0)
const totalItemsBeath = ref(0)
const sortBy = ref()
const sortByBeath = ref()
const orderBy = ref()
const orderByBeath = ref()
const page = ref(1)
const pageBeath = ref(1)
const isSnackbarScrollReverseVisible = ref(false)
const message = ref('')
const color = ref('')
const isLoading = ref(false)
const editDialog = ref(false)
const ordine = ref()
const view = ref(false)
const ordiniLista = []
const isDialogLoading = ref(false)
const ordineBatch = ref('')
const taxtButonCopy = ref('Copy')

const headers = [
  { title: t('Table.Ordine'), key: 'ordine' },
  { title: t('Table.Numrto-Batch'), key: 'numeroLotti', sortable: false },
  { title: t('Table.Data'), key: 'created_at', sortable: true },
  { title: 'ACTIONS', key: 'actions', sortable: false },
]

const beathHeaders = [
  { title: t('Table.Ordine'), key: 'ordine' },
  { title: t('Table.Batch'), key: 'lotto', sortable: false },
  { title: t('Table.Materiale'), key: 'materiale', sortable: false },
  { title: t('Table.Giacenza'), key: 'giacenza', sortable: false },
  { title: t('Table.Um'), key: 'um', sortable: false },
  { title: t('Table.Data'), key: 'created_at', sortable: true },
  { title: 'ACTIONS', key: 'actions', sortable: false },
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

  const { data: resultData } = await useApi<any>(createUrl('/sp/picking/', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      numeroOrdini: numeroMaxOrdini.value,
    },
  }))

  serverItems.value = resultData.value.data
  totalItems.value = resultData.value.total
  loading.value = false
}

const updateOptionsBeath = (options: any) => {
  sortByBeath.value = options.sortBy[0]?.key
  orderByBeath.value = options.sortBy[0]?.order
  pageBeath.value = options.page
  itemsPerPage.value = options.itemsPerPage

  // eslint-disable-next-line @typescript-eslint/no-use-before-define
  loadItemsBeath()
}

const loadItemsBeath = async () => {
  loadingBeath.value = true
  taxtButonCopy.value = 'Copy!'

  const { data: resultData } = await useApi<any>(createUrl(`/sp/picking/batch/${ordineBatch.value}`, {
    query: {
      page: pageBeath.value,
      itemsPerPage: itemsPerPageBeath.value,
      sortBy: sortByBeath.value,
      orderBy: orderByBeath.value,
    },
  }))

  serverItemsBeath.value = resultData.value.data
  totalItemsBeath.value = resultData.value.total
  loadingBeath.value = false
}

const save = async () => {
  isDialogLoading.value = true

  const { data: resultData } = await $api('sp/picking/stored', {
    method: 'POST',
    body: {
      ordini: ordiniLista,
    },
  })

  await loadItems()

  isDialogLoading.value = false
  isSnackbarScrollReverseVisible.value = true
}

const addOrdine = (ol: string) => {
  view.value = false
  if (ordiniLista.findIndex(item => item.cdOrdine === ol))
    ordiniLista.push({ cdOrdine: ol })

  view.value = true
}

const dellOrdine = (ol: string) => {
  view.value = false
  console.log(ordiniLista.findIndex(item => item.cdOrdine === ol))
  ordiniLista.splice(ordiniLista.findIndex(item => item.cdOrdine === ol), 1)
  view.value = true
}

const listaOrdine = async () => {
  const { data: resultData } = await useApi<any>(createUrl('/gp/lista_ordini/', {
    query: {
      ordine: ordine.value,
      numeroOrdini: numeroMaxOrdini.value,
    },
  }))

  serverOrdini.value = resultData.value
}

const permissionsCheck = async () => {
  const read = await navigator.permissions.query({ name: 'clipboard-read' })
  const write = await navigator.permissions.query({ name: 'clipboard-write' })

  return write.state === 'granted' && read.state != 'denied'
}

const updateClipboard = (content: any) => {
  navigator.clipboard.writeText(content).then(() => {
    taxtButonCopy.value = 'Copied!'

    return true

    /* clipboard successfully set */
  }, () => {
    return false
  })
}

const copy = async () => {
  let csvContent = ''

  await permissionsCheck().then(allowed => {
    if (allowed) {
      Object.keys(serverItemsBeath.value).forEach(key => {
        if (serverItemsBeath.value[key].lotto !== undefined)
          csvContent += `${serverItemsBeath.value[key].lotto} \r\n `
      })
      updateClipboard(csvContent)
    }
    else { alert('NOT ALLOWED') }
  })
}

const listBeath = async (ol: string) => {
  ordineBatch.value = ol
  loadItemsBeath()
}

listaOrdine()
</script>

<template>
  <VRow>
    <VCol cols="5">
      <VCard
        title="Filters"
        class="mb-6"
      >
        <VCardText>
          <VRow />
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
              v-if="can(DefineAbilities.shipping_picking_create.action, DefineAbilities.shipping_picking_create.subject)"
              prepend-icon="tabler-plus"
              color="success"
              @click="editDialog = true"
            >
              {{ $t('Label.Ordini') }}
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
          <template #item.ordine="{ item }">
            <div class="d-flex align-center">
              <div class="d-flex flex-column">
                <h6 class="text-base">
                  <RouterLink
                    to=""
                    class="font-weight-medium text-link"
                    @click="listBeath(item.id)"
                  >
                    {{ item.ordine }}
                  </RouterLink>
                </h6>
              </div>
            </div>
          </template>

          <!-- date -->
          <template #item.created_at="{ item }">
            {{ formatDate(item.created_at) }}
          </template>

          <!-- Actions -->
          <template #item.actions="{ item }">
            <div class="d-flex gap-1">
              <IconBtn
                v-if="can(DefineAbilities.shipping_picking_deleted.action, DefineAbilities.shipping_picking_deleted.subject)"
                color="text-success"
                @click="editItem(item)"
              >
                <VIcon icon="tabler-trash" />
              </IconBtn>
            </div>
          </template>
        </VDataTableServer>
      </VCard>
    </VCol>

    <VCol cols="7">
      <VCard
        title="Beatch"
        class="mb-1"
      >
        <VCol
          cols="12"
          sm="12"
        >
          <div class="float-end ">
            <!-- 👉 Add user button -->
            <VBtn
              rounded="pill"
              color="primary"
              @click="copy"
            >
              {{ taxtButonCopy }}
            </VBtn>
          </div>
        </VCol>
        <!-- 👉 Datatable  -->
        <VDataTableServer
          v-model:items-per-page="itemsPerPageBeath"
          :headers="beathHeaders"
          :items="serverItemsBeath"
          :items-length="totalItemsBeath"
          :loading="loadingBeath"
          @update:options="updateOptionsBeath"
        >
          <template #item.giacenza="{ item }">
            <p v-if="item.giacenza <= 0">
              0
            </p>
            <p v-else>
              {{ item.giacenza }}
            </p>
          </template>
          <!-- date -->
          <template #item.created_at="{ item }">
            {{ formatDate(item.created_at) }}
          </template>
          <!-- Actions -->
          <!-- Actions -->
          <template #item.actions="{ item }">
            <div class="d-flex gap-1">
              <IconBtn
                v-if="can(DefineAbilities.macchinari_edit.action, DefineAbilities.macchinari_edit.subject)"
                color="text-success"
                @click="editItem(item)"
              >
                <VIcon icon="tabler-trash" />
              </IconBtn>
            </div>
          </template>
        </VDataTableServer>
      </VCard>
    </VCol>
  </VRow>

  <!-- 👉 Edit Dialog  -->
  <VDialog
    v-model="editDialog"
    max-width="1400px"
  >
    <AppCardActions
      v-model:loading="isLoading"
      :title="$t('Label.Packing-List')"
      no-actions
    >
      <VCard>
        <VCardText>
          <VContainer>
            <VForm
              ref="refForm"
              v-model="isFormValid"
            >
              <VCol
                cols="12"
                md="6"
              >
                <VTextField
                  v-model="ordine"
                  :label="$t('Label.Ordine')"
                  variant="underlined"
                  @change="listaOrdine"
                />
              </VCol>
              <VRow>
                <VCol

                  cols="12"
                  md="6"
                >
                  <VList
                    nav
                    :lines="true"
                  >
                    <VListItem
                      v-for="item in serverOrdini"
                      :key="item.cdOrdine"
                      :value="item.cdOrdine"
                    >
                      <VListItemTitle>
                        <h3
                          class="text-success"
                          @click="addOrdine(item.cdOrdine)"
                        >
                          {{ item.cdOrdine }}
                        </h3>
                      </VListItemTitle>
                    </VListItem>
                  </VList>
                </VCol>

                <VCol

                  cols="12"
                  md="2"
                >
                  <VList
                    v-if="view"
                    nav
                    :lines="false"
                  >
                    <VListItem
                      v-for="itemOrdine in ordiniLista"
                      :key="itemOrdine.cdOrdine"
                      :value="itemOrdine.cdOrdine"
                    >
                      <VListItemTitle>
                        <h3 class="text-primary">
                          {{ itemOrdine.cdOrdine }}
                        </h3>
                      </VListItemTitle>
                      <template #append>
                        <VIcon
                          icon="tabler-x"
                          @click="dellOrdine(itemOrdine.cdOrdine)"
                        />
                      </template>
                    </VListItem>
                  </VList>
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
            @click="editDialog = false"
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
