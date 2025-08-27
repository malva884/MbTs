<script lang="ts" setup>
import { useI18n } from 'vue-i18n'
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { can } from '@layouts/plugins/casl'
import DefineAbilities from '@/plugins/casl/DefineAbilities'
import DataTableTrainingProfessionali from "@/views/hr/employee/view/DataTableTrainingProfessionali.vue";
import DataTableTrainingObbligatorie from "@/views/hr/employee/view/DataTableTrainingObbligatorie.vue";
import DataTableSkill from "@/views/hr/employee/view/DataTableSkill.vue";

interface Props {
  id: string
}

const props = defineProps<Props>()
const { t } = useI18n()
const itemsPerPage = ref(10)
const loading = ref(true)
const loadingPage = ref(false)
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const page = ref(1)
const isSnackbarScrollReverseVisible = ref(false)
const message = ref('')
const color = ref('')
const componentKey = ref(false)
const professionaliItems = ref<any>([])
const obbligatorieItems = ref<any>([])

const headersProfessional = [
  { title: t('Table.Formasione'), key: 'formazione' },
  { title: t('Table.Data'), key: 'data_formazione' },
  { title: 'ACTIONS', key: 'actions', sortable: false },
]

const updateOptions = (options: any) => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
  page.value = options.page
  itemsPerPage.value = options.itemsPerPage

  // eslint-disable-next-line @typescript-eslint/no-use-before-define
  fetchProfessionali()
}

const fetchProfessionali = async () => {
  loadingPage.value = true
  const { data: resultData } = await useApi<any>(createUrl('/hr/formazioni/professionali/', {
    query: {
      page: 1,
      itemsPerPage: 100,
      dipendente: props.id,
    },
  }))

  professionaliItems.value = resultData.value
  componentKey.value = true
  loadingPage.value = false
}

fetchProfessionali()

const fetchObbligatorie = async () => {
  loadingPage.value = true
  const { data: resultData } = await useApi<any>(createUrl('/hr/formazioni/obbligatori/', {
    query: {
      page: 1,
      itemsPerPage: 100,
      dipendente: props.id,
    },
  }))

  obbligatorieItems.value = resultData.value
  componentKey.value = true
  loadingPage.value = false
}

fetchObbligatorie()
</script>

<template>
  <VRow>
    <VCol cols="4">
      <DataTableTrainingProfessionali
        v-if="componentKey"
        :id="props.id"
        :items-data="professionaliItems"
        @update:is-refresh="fetchProfessionali"
      />
    </VCol>
    <VCol cols="4">
      <DataTableTrainingObbligatorie
        v-if="componentKey"
        :id="props.id"
        :items-data="obbligatorieItems"
        @update:is-refresh="fetchObbligatorie"
      />
    </VCol>
    <VCol cols="4">
      <DataTableSkill
        v-if="componentKey"
        :id="props.id"
        :items-data="obbligatorieItems"
        @update:is-refresh="fetchObbligatorie"
      />
    </VCol>
  </VRow>

  <LoadingStandBy v-model="loadingPage"></LoadingStandBy>
</template>
