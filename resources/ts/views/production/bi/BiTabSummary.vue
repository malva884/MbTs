<script setup lang="ts">
import {VDataTable} from 'vuetify/labs/VDataTable'
import {can} from "@layouts/plugins/casl";
import DefineAbilities from "@/plugins/casl/DefineAbilities";

interface Props {
  titolo: string
  tipologia: number
  dataFilter: string
  groupFilter: string
  macchineFilter: object
  lavorazioneFilter: string
  materialeFilter: string
}

const loadingPage = ref(false)
const load = ref(true)
const items = ref([])
const panel = ref()
const props = defineProps<Props>()
const ckm = ref(0)
const fkm = ref(0)
const tipologia = ref()

const loadItems = async () => {
  load.value = true
  loadingPage.value = true

  const {data: resultData} = await useApi<any>(createUrl('/gp/bi', {
    query: {
      data: props.dataFilter,
      group: props.groupFilter,
      macchine: props.macchineFilter,
      lavorazione: props.lavorazioneFilter,
      materiale: props.materialeFilter,
      tipologia: props.tipologia,
    },
  }))

  items.value = resultData.value
  panel.value = null
  load.value = false
  loadingPage.value = false
}

loadItems()

const headers = [
  {title: 'Group by status', key: 'data-table-group'},
  {title: 'Prodotto', key: 'Prodotto'},
  {title: 'Descrizione', key: 'DescrizioneProdotto'},
  {title: 'Ckm', key: 'quantita'},

]

if (props.tipologia == 20)
  headers.push({title: 'Fkm', key: 'fkm'})

const getIcon = (props: Record<string, unknown>) => props.icon as any

let groupBy = []

const numero = new Intl.NumberFormat('it-IT', {
  maximumSignificantDigits: 10,
})

const calculateTotals = (item: object) => {
  const a = JSON.parse(JSON.stringify(item))

  const total = a.reduce((acc, val) => ({
    ckm: acc.ckm + Number.parseFloat(val.raw.quantita),
    fkm: acc.fkm + Number.parseFloat(val.raw.quantita * val.raw.NumeroFibre),
  }), {ckm: 0, fkm: 0})

  ckm.value = ckm.value + total.ckm
  fkm.value = fkm.value + total.fkm

  return total
}

const check_tipologia = (tipologia: number) => {
  return tipologia == props.tipologia
}

watch(props, () => {
  tipologia.value = props.tipologia
  groupBy = []
  load.value = true
  groupBy.push({key: props.groupFilter})

  load.value = false

  loadItems()
})
</script>

<template>
  <VCard :title="$t(`Label.${props.titolo}`)">
    <VDataTable
      :headers="headers"
      :items="items"
      :items-per-page="25"
      :loading="load"
      :group-by="groupBy"
    >
      <template #item.quantita="{ item }">
        <span>{{ numero.format(item.quantita) }}</span>
      </template>
      <template #item.fkm="{ item }">
        <span v-if="props.tipologia == 20">{{ numero.format((item.quantita * item.NumeroFibre)) }}</span>
      </template>
      <template #data-table-group="{ props, item, count, group }">
        <td>
          <VBtn
            v-bind="props"
            variant="text"
            density="comfortable"
          >
            <VIcon
              class="flip-in-rtl"
              :icon="getIcon(props)"
            />
          </VBtn>
          <span>{{ `${item.value} (${count})` }}</span>
        </td>
        <th colspan="2"></th>
        <th>
          <span class="text-warning">{{ numero.format(calculateTotals(item.items).ckm) }}</span>
        </th>
        <th>
          <span v-if="check_tipologia(20)" class="text-warning">{{
            numero.format(calculateTotals(item.items).fkm)
          }}</span>
        </th>
      </template>
    </VDataTable>
  </VCard>
</template>

<style scoped lang="scss">

</style>
