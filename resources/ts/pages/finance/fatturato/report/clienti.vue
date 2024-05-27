<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import ReportFatturato from "@/components/dialogs/ReportFatturato.vue"

definePage({
  meta: {
    action: 'report',
    subject: 'Finanze-Fatturato',
  },
})

export interface ClientiType {
  cliente: string | null
  codice: number | null

}

const {t} = useI18n()
const currentYear = new Date().getFullYear()
const materialeFilter = ref('')
const dataFilter = ref(`${currentYear}-01-01 to ${currentYear}-12-31`)
const tipologiaCavoFilter = ref([])
const clientiFilter = ref([])
const view = ref(false)
const rows = ref({})
const clientiOptions = ref<ClientiType>()
const reportVisibile = ref(false)
const codiceCleinte = ref('')
const cliente = ref('')
const tipologiaCavo = ref('')
const temp = []

const loadItems = async () => {

  const { data: resultData, error } = await useApi<any>(createUrl('/fi/turnover/report/clienti', {
    query: {
      materiale: materialeFilter.value,
      data: dataFilter.value,
      tipologiaCavo: tipologiaCavoFilter.value,
      clienti:  (temp !== '' ? [temp]:''),
    },
  }))

  rows.value = resultData.value
  view.value = true
}

const reloadItems = () => {
  temp.length = 0
  clientiFilter.value.forEach(function (value) {
    temp.push(value.id)
  })
  loadItems()
}

const clienti = async () => {
  const { data: clientiResult } = await useApi<any>(createUrl('/fi/get_clienti'))
  const arr = []

  clientiResult.value.forEach(value => {
    arr.push({ val: value.cliente, id: value.codice_cliente })
  })
  clientiOptions.value = arr
}

clienti()

// eslint-disable-next-line camelcase
const format_value = (value: string) => {
  if (value === '.000')
    return { text: '0' }

  if (value !== '') {
    let t = value.toString()
    t = t.replaceAll('-', '')

    return { value: t }
  }

  return { text: value }
}

const openList = async (item: object) => {
  codiceCleinte.value = item.codice_cliente
  cliente.value = item.cliente
  tipologiaCavo.value = item.tipologia_cavo
  reportVisibile.value = true
}

const euro = new Intl.NumberFormat('it-IT', {
  style: 'currency',
  currency: 'EUR',
})

loadItems()
</script>

<template>

  <VCol cols="12">
    <VCard
      title="Filters"
      class="mb-6"
    >
      <VCardText>
        <Downloadexcel
          class            = "btn"
          :fetch           = "rows"
        >
          Download Excel
        </Downloadexcel>
        <VRow>
          <!-- 👉 Materiale -->
          <VCol
            cols="12"
            sm="3"
          >
            <AppTextField
              v-model="materialeFilter"
              :label="$t('Label.Materiale')"
              :placeholder="$t('Materiale')"
              clearable
              clear-icon="tabler-x"
              @focusout="reloadItems"
            />
          </VCol>

          <!-- 👉 Clienti -->
          <VCol cols="12" sm="3">

            <AppCombobox
              v-model="clientiFilter"
              :label="$t('Label.Clienti')"
              :placeholder="$t('Label.Clienti')"
              :items="clientiOptions"
              :item-title="item => item.val"
              :item-value="item => item.id"
              chips
              multiple
              eager
              clearable
              clear-icon="tabler-x"
              @focusout="reloadItems"
            />
          </VCol>

          <!-- 👉 tipologia Cavo -->
          <VCol
            cols="12"
            sm="3"
          >
            <AppSelect
              v-model="tipologiaCavoFilter"
              :label="$t('Label.Tipologia-Cavo')"
              :placeholder="$t('Label.Tipologia-Cavo')"
              :items="[{ title: 'Rame', value: 5441 }, { title: 'Ottico', value: 5420 }]"
              clearable
              clear-icon="tabler-x"
              @focusout="reloadItems"
            />
          </VCol>

          <!-- 👉 Data -->
          <VCol
            cols="12"
            sm="3"
          >
            <AppDateTimePicker
              v-model="dataFilter"
              :label="$t('Label.Data')"
              :placeholder="$t('Label.Data')"
              :config="{ mode: 'range' }"
              clearable
              clear-icon="tabler-x"
              @focusout="reloadItems"
            />
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <VTable
      v-if="view"
      density="compact"
      height="600"
      fixed-header
      class="text-no-wrap "
    >
      <thead>
      <tr>
        <th>
          Cliente
        </th>
        <th>
          Tipologia
        </th>
        <th class="text-end">
          Amount in Local Current
        </th>
        <th class="text-end">
          Ckm
        </th>
        <th class="text-end">
          KFKM
        </th>
      </tr>
      </thead>

      <tbody>
      <tr v-for="(item, index) in rows">
        <td>
          <p> <VBtn color="success" variant="plain" @click="openList(item)">
            {{ item.cliente }}
          </VBtn> </p>
        </td>
        <td>
          <p v-if="item.tipologia_cavo === '5441'" class="text-error"> {{ item.tipologia_cavo }}</p>
          <p v-else class="text-warning"> {{ item.tipologia_cavo }}</p>
        </td>
        <td>
          <p class="text-end text-success"> {{  euro.format(item.totale) }}</p>
        </td>
        <td>
          <p class="text-end text-info"> {{ format_value(item.ckm).value }}</p>
        </td>
        <td>
          <p class="text-end text-info"> {{ format_value(item.fkm).value }}</p>
        </td>
      </tr>
      <tr>

      </tr>
      </tbody>
      <tfoot>
      <tr>
        <th>
          Cliente
        </th>
        <th>
          Tipologia
        </th>
        <th class="text-end">
          Amount in Local Current
        </th>
        <th class="text-end">
          Ckm
        </th>
        <th class="text-end">
          KFKM
        </th>
      </tr>
      </tfoot>
    </VTable>
    <VCard/>
  </VCol>
  <ListaCaviClienti
    v-model:isDialogVisible="reportVisibile"
    :data-filter-data="dataFilter"
    :materiale-filter-data="materialeFilter"
    :tipologia-cavo-filter-data="tipologiaCavoFilter"
    :codice-cliente-data="codiceCleinte"
    :tipologia-data="tipologiaCavo"
    :cliente-data="cliente"
  />
</template>
