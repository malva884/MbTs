<script setup lang="ts">

import {useI18n} from "vue-i18n";
import {VDataTableServer} from "vuetify/labs/VDataTable";
import {can} from "@layouts/plugins/casl";
import DefineAbilities from "@/plugins/casl/DefineAbilities";

definePage({
  meta: {
    action: 'read',
    subject: 'Qualita-Prove-Tipo',
  },
})

const {t} = useI18n()
const route = useRoute('quality-prove-tipo-view-id')
const loading = ref(true)
const isDialogLoading = ref(false)
const prova = ref<any>([])
const serverItems = ref<any>([])
const itemsPerPage = ref(-1)

const headers = [
  {title: t('Table.Ordine'), key: 'ol', sortable: false},
  {title: t('Table.Materiale'), key: 'materiale', sortable: false},
  {title: t('Table.Esito'), key: 'esito', sortable: false},
  {title: t('Table.Standard'), key: 'standard', sortable: false},
  {title: t('Table.Specifica'), key: 'spcifica', sortable: false},
  {title: t('Table.Tipologia'), key: 'categoria', sortable: false},
  {title: t('Table.Data'), key: 'data_prova', sortable: false},
]

const loadItem = async () => {
  const { data: resultData } = await useApi<any>(createUrl(`/qt/prove_tipo/view/${route.params.id}`))

  prova.value = resultData.value

  const { data: proveData, error } = await useApi<any>(createUrl(`/qt/prove_tipo/get_prove/${prova.value.ol}`))

  serverItems.value = proveData.value
  loading.value = false
}

const reloadItem = async (id: string) => {
  isDialogLoading.value = true
  const { data: resultData } = await useApi<any>(createUrl(`/qt/prove_tipo/view/${id}`))

  prova.value = resultData.value

  isDialogLoading.value = false
}

const resolveStatusVariant = (risultato: string) => {
  if (risultato === 'POSITIVO')
    return { color: 'success', text: 'POSITIVO' }
  else if (risultato === 'NEGATIVO')
    return { color: 'error', text: 'NEGATIVO' }
  else
    return { color: '', text: risultato }
}

watch(
  loadItem()
)
</script>

<template>
  <VCol cols="12">
    <VCard :title="$t('Label.Dettaglio-Prova')">
      <VCardText>
        <VRow no-gutters>
          <VCol
            cols="12"
            md="1"
          >
            <span class="text-button">{{ $t('Label.Ol') }}:</span>
          </VCol>
          <VCol
            cols="12"
            md="2"
          >
            <p class="text-button mb-1">
              {{ prova.ol }}
            </p>
          </VCol>
          <VCol
            cols="12"
            md="1"
          >
            <span class="text-button">{{ $t('Label.Esito') }}:</span>
          </VCol>
          <VCol
            cols="12"
            md="2"
          >
            <p v-if="prova.esito === 'POSITIVO'" class="text-button text-success mb-1">
              {{ prova.esito }}
            </p>
            <p v-else class="text-button text-error mb-1">
              {{ prova.esito }}
            </p>
          </VCol>
          <VCol
            cols="12"
            md="1"
          >
            <span class="text-button">{{ $t('Label.Cliente') }}:</span>
          </VCol>
          <VCol
            cols="12"
            md="2"
          >
            <p class="text-button mb-1">
              {{ prova.cliente }}
            </p>
          </VCol>

          <VCol
            cols="12"
            md="1"
          >
            <span class="text-button">{{ $t('Label.Fai') }}:</span>
          </VCol>

          <VCol
            cols="12"
            md="2"
          >
            <p class="text-button mb-1">
              {{ (!prova.fai ? '-' : prova.fai) }}
            </p>
          </VCol>
        </VRow>

        <VRow class="mt-5" no-gutters>
          <VCol
            cols="12"
            md="1"
          >
            <span class="text-button">{{ $t('Label.Standard') }}:</span>
          </VCol>
          <VCol
            cols="12"
            md="2"
          >
            <p class="text-button mb-1">
              {{ prova.standard }}
            </p>
          </VCol>
          <VCol
            cols="12"
            md="1"
          >
            <span class="text-button">{{ $t('Label.Materiale') }}:</span>
          </VCol>
          <VCol
            cols="12"
            md="2"
          >
            <p class="text-button mb-1">
              {{ prova.materiale }}
            </p>
          </VCol>
          <VCol
            cols="12"
            md="1"
          >
            <span class="text-button">{{ $t('Label.Descrizione') }}:</span>
          </VCol>
          <VCol
            cols="12"
            md="5"
          >
            <p class="text-button mb-1">
              {{ prova.descrizione }}
            </p>
          </VCol>
        </VRow>

        <VRow class="mt-5" no-gutters>
          <VCol
            cols="12"
            md="1"
          >
            <span class="text-button">{{ $t('Label.Specifica') }}:</span>
          </VCol>
          <VCol
            cols="12"
            md="2"
          >
            <p class="text-button mb-1">
              {{ (!prova.specifica ? '-' : prova.specifica) }}
            </p>
          </VCol>
          <VCol
            cols="12"
            md="1"
          >
            <span class="text-button">{{ $t('Label.Tipologia') }}:</span>
          </VCol>
          <VCol
            cols="12"
            md="2"
          >
            <p class="text-button mb-1">
              {{ prova.categoria }}
            </p>
          </VCol>
          <VCol
            cols="12"
            md="1"
          >
            <span class="text-button">{{ $t('Label.Nota') }}:</span>
          </VCol>
          <VCol
            cols="12"
            md="5"
          >
            <p class="text-button text-error mb-1">
              {{ prova.note }}
            </p>
          </VCol>
        </VRow>
        <VSpacer/>
      </VCardText>
    </VCard>
  </VCol>
  <VCol cols="7">
    <VCard
      :title="$t('Label.Prove-Per-Ol')"
      class="mb-6"
    >
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        :headers="headers"
        :items="serverItems"
        :loading="loading"
        class="text-no-wrap mb-0"
        density="compact"
      >
        <!-- Ol -->
        <template #item.ol="{ item }">
          <div class="d-flex align-center">
            <div
              v-if="can(DefineAbilities.qt_prove_tipo_read.action, DefineAbilities.qt_prove_tipo_read.subject)"
              class="d-flex flex-column">
              <h6 class="text-base">
                <VIcon
                  v-if="item.id === prova.id"
                  icon="tabler-arrow-right"
                  size="24"
                  color="primary"
                ></VIcon>
                &nbsp;
                <span
                  @click="reloadItem(item.id)"
                  class="font-weight-medium text-link"
                >
                  {{ item.ol }}
                </span>
                <span v-if="item.note">⚠️</span>
              </h6>
            </div>
            <div
              v-else
              class="d-flex flex-column">
              <h6 class="text-base">
                {{ item.ol }}
                <span v-if="item.note">⚠️</span>
              </h6>
            </div>
          </div>
        </template>

        <!-- risultato -->
        <template #item.esito="{ item }">
          <div class="d-flex gap-1" v-if="item.esito">
            <VChip
              :color="resolveStatusVariant(item.esito).color"
              size="small"
            >
              {{ resolveStatusVariant(item.esito).text }}
            </VChip>
          </div>
        </template>

        <template #bottom>
          <VCardText class="pt-2" style="display: block">
            <div class="d-flex flex-wrap justify-center justify-sm-space-between gap-y-2 mt-2">

            </div>
          </VCardText>
        </template>
      </VDataTableServer>
    </VCard>
  </VCol>

  <!-- Dialog Loading -->
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

<style scoped lang="scss">

</style>
