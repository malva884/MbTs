<script setup lang="ts">
import {useI18n} from 'vue-i18n'
import {VDataTableServer} from 'vuetify/labs/VDataTable'
import {VForm} from 'vuetify/components/VForm'
import {can} from "@layouts/plugins/casl";
import DefineAbilities from "@/plugins/casl/DefineAbilities";
import {themeConfig} from "@themeConfig";
import {VNodeRenderer} from "@layouts/components/VNodeRenderer";

definePage({
  meta: {
    action: '',
    subject: '',
    layout: 'blank',
    public: true,
  },
})

const {t} = useI18n()
const serverItems = ref<any>([])
const serverOrdini = ref<any>([])
const numeroMaxOrdini = ref(10)
const itemsPerPage = ref(10)
const loading = ref(true)
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const page = ref(1)
const isSnackbarScrollReverseVisible = ref(false)
const message = ref('')
const color = ref('')
const isLoading = ref(false)
const editDialog = ref(false)
const ordine = ref()
const view = ref(false)
const ordiniLista = []
const isDialogLoading = ref(false)


const headers = computed(() => [
  {title: t('Table.Ordine'), key: 'ordine'},
  {title: t('Table.Numrto-Batch'), key: 'numeroLotti', sortable: false},
  {title: t('Table.Data'), key: 'created_at', sortable: true},
  {title: 'ACTIONS', key: 'actions', sortable: false},
])

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

  const {data: resultData} = await useApi<any>(createUrl('/sp/picking/', {
    query: {
    },
  }))

  serverItems.value = resultData.value.data
  totalItems.value = resultData.value.total
  loading.value = false
}

const listaOrdine = async () => {
  const {data: resultData} = await useApi<any>(createUrl('/gp/lista_ordini/', {
    query: {
      ordine: ordine.value,
      numeroOrdini: numeroMaxOrdini.value,
    },
  }))

  serverOrdini.value = resultData.value
}

listaOrdine()
</script>

<template>
  <VRow
    no-gutters
    class="auth-wrapper bg-surface"
  >
    <VCardItem class="justify-center">
      <template #prepend>
        <div class="d-flex">
          <VNodeRenderer :nodes="themeConfig.app.logo" />
        </div>
      </template>

      <VCardTitle class="font-weight-bold text-capitalize text-h3 py-1">
        {{ themeConfig.app.title }}
      </VCardTitle>
    </VCardItem>
  </VRow>
  <h1 class="text-center mt-3">Lista Ordini</h1>

  <VRow>
    <VCol cols="12">
      <VCard
        title="Ordini"
        class="mt-6 mb-6"
      >
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
          <template #item.ordine="{ item }">
            <div class="d-flex align-center">
              <div class="d-flex flex-column">
                <h6 class="text-base">
                  <RouterLink
                    :to="{ name: 'shipping-picking-batch-insert-id', params: { id: item.id } }"
                    class="font-weight-medium text-link"
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
          <template #item.actions="{ item }"/>
        </VDataTableServer>
      </VCard>
    </VCol>
  </VRow>

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
