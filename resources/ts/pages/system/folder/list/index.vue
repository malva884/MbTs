<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { useI18n } from 'vue-i18n'
import { VForm } from 'vuetify/components/VForm'
import { can } from '@layouts/plugins/casl'
import DefineAbilities from '@/plugins/casl/DefineAbilities'
import type {Fai} from "@/views/quality/fai/type";

definePage({
  meta: {
    action: 'list',
    subject: 'Macchinari',
  },
})

const { t } = useI18n()
const isSnackbarScrollReverseVisible = ref(false)
const cartellaFilter = ref()
const utenteFilter = ref()
const color = ref()
const message = ref()
const itemsPerPage = ref(10)
let loading = true
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const page = ref(1)
const serverItems = ref<Fai[]>([])

const headers = computed(() => [
  { title: t('Label.Cartella'), key: 'titolo' },
  { title: 'ACTIONS', key: 'actions', sortable: false },
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
  loading = true

  const { data: resultData } = await useApi<any>(createUrl('/system/folder/', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
    },
  }))

  if (resultData.value !== null) {
    serverItems.value = resultData.value.data
    totalItems.value = resultData.value.total
  } else {
    serverItems.value = []
    totalItems.value = 0
  }
  loading = false
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
          <!-- 👉 Catella Filter-->
          <VCol
            cols="12"
            sm="4"
          >
            <AppTextField
              v-model="cartellaFilter"
              :label="$t('Label.Catella')"
              clearable
              clear-icon="tabler-x"
              @focusout="loadItems"
            />
          </VCol>

          <!-- 👉 Utente Filter -->
          <VCol
            cols="12"
            sm="4"
          >
            <AppSelect
              v-model="utenteFilter"
              :label="$t('Label.Utente')"
              :placeholder="$t('Label.Utente')"
              :items="[]"
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
          <!-- 👉 Add folder button -->
          <VBtn
            v-if="can(DefineAbilities.macchinari_create.action, DefineAbilities.macchinari_create.subject)"
            prepend-icon="tabler-plus"
            color="success"
            @click="newItem"
          >
            Nuovo Cartella
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
        <!-- Actions -->
        <template #item.actions="{ item }">
        </template>
      </VDataTableServer>
    </VCard>
  </VCol>
</template>
