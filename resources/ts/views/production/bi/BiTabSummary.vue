<script setup lang="ts">
import {VDataTable} from 'vuetify/labs/VDataTable'

interface Props {
  titolo: string
  tipologia: number
  dataFilter: string
  groupFilter: string
  macchineFilter: object
  lavorazioneFilter: string
  materialeFilter: string
}

const load = ref(true)
const items = ref([])
const props = defineProps<Props>()
const groupBy = ref([])

const loadItems = async () => {
  load.value = true

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
  load.value = false
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

const numero = new Intl.NumberFormat('it-IT', {
  maximumSignificantDigits: 10,
})

const groupTotals = computed(() => {
  const totals: Record<string, { ckm: number, fkm: number }> = {}
  for (const item of items.value) {
    const key = item[props.groupFilter]
    if (!totals[key])
      totals[key] = { ckm: 0, fkm: 0 }
    const q = Number.parseFloat(item.quantita) || 0
    const fibre = Number.parseFloat(item.NumeroFibre) || 0
    totals[key].ckm += q
    totals[key].fkm += q * fibre
  }
  return totals
})

const check_tipologia = (tipologia: number) => {
  return tipologia == props.tipologia
}

let debounceTimer: ReturnType<typeof setTimeout> | null = null

watch(props, () => {
  if (debounceTimer)
    clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    groupBy.value = [{key: props.groupFilter}]
    loadItems()
  }, 300)
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
          <span class="text-warning">{{ numero.format(groupTotals[item.value]?.ckm ?? 0) }}</span>
        </th>
        <th>
          <span v-if="check_tipologia(20)" class="text-warning">{{
            numero.format(groupTotals[item.value]?.fkm ?? 0)
          }}</span>
        </th>
      </template>
    </VDataTable>
  </VCard>
</template>

<style scoped lang="scss">

</style>
