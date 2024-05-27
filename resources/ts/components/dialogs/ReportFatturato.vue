<script setup lang="ts">
interface Emit {
  (e: 'update:isDialogVisible', value: boolean): void
  (e: 'dataFilter', value: string): void
}

interface Props {
  dataFilterData?: string
  materialeFilterData?: string
  tipologiaCavoFilterData?: string
  clientiFilterData?: any
  idHeadData?: string
  isDialogVisible: boolean
}

const props = defineProps<Props>()
const emit = defineEmits<Emit>()
const report = ref({})
const view = ref(false)
const isDialogLoading = ref(false)
let totali = { totale: 0, ckm: 0, kfkm: 0 }
const labelTabel = {
  italia_totali: 'Italia',
  italia_5420: '5420',
  italia_5441: '5441',
  eu_totali: 'Eu',
  eu_5420: '5420',
  eu_5441: '5441',
  exstra_totali: 'Extra Eu',
  exstra_5420: '5420',
  exstra_5441: '5441',
}

const loadReport = async () => {
  totali = { totale: 0, ckm: 0, kfkm: 0 }
  isDialogLoading.value = true
  const resultData = await useApi<any>(createUrl('/fi/turnover/reprot', {
    query: {
      data: props.dataFilterData,
      materiale: props.materialeFilterData,
      tipologiaCavo: props.tipologiaCavoFilterData,
      id: props.idHeadData,
      clienti: [props.clientiFilterData],
    },
  }))

  view.value = true
  report.value = resultData.data.value[0]
  isDialogLoading.value = false
}

let euro = new Intl.NumberFormat('it-IT', {
  style: 'currency',
  currency: 'EUR',
})

const value = (index: string, label: string) => {

  const tmp = report.value
  if (tmp[index][label] === '.000')
    return { text: '' }

  if (tmp[index][label] !== null) {
    let t = tmp[index][label].toString()
    t = t.replaceAll('-', '')
    if (index === 'italia_totali' || index === 'eu_totali' || index === 'exstra_totali'){
      totali[label] = parseFloat(totali[label])+parseFloat(t)

    }

    return { text: t }
  }
  return { text: '' }
}

const close = () => {
  // eslint-disable-next-line vue/no-mutating-props
  props.isDialogVisible = false
  emit('update:isDialogVisible', false)
}

const printInvoice = () => {
  window.print()
}

watch(props, () => {
  view.value = false
  report.value = ref({})
  console.log(props)
  loadReport()
})
</script>

<template>
  <VDialog
    :model-value="props.isDialogVisible"
    persistent
    class="v-dialog-lg"
  >
    <!-- Dialog close btn -->
    <DialogCloseBtn @click="close" class="d-print-none"/>

    <!-- Dialog Content -->
    <VCard title="Report Fatturato">
      <VCardText class="d-flex flex-wrap justify-space-between flex-column flex-sm-row print-row">
      <VTable
        v-if="view"
        density="compact"
        class="text-no-wrap"
      >
        <thead>
          <tr>
            <th>
              Paese
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
          <tr v-for="(item, index) in labelTabel">
            <td>
              <p v-if="item === 'Italia' || item === 'Eu' || item === 'Extra Eu'" class="text-success mt-3">{{ item }}</p>
              <p v-else class="text-info ml-4 mt-3">- {{ item }}</p>
            </td>
            <td>
              <p v-if="item === 'Italia' || item === 'Eu' || item === 'Extra Eu'" class="text-success text-end mt-3">{{ euro.format(value(index, 'totale').text) }}</p>
              <p v-else class="text-info text-end mt-3">{{ euro.format(value(index, 'totale').text) }}</p>
            </td>
            <td>
              <p v-if="item === 'Italia' || item === 'Eu' || item === 'Extra Eu'" class="text-success text-end mt-3"> {{ value(index, 'ckm').text}}</p>
              <p v-else class="text-info text-end mt-3">{{ value(index, 'ckm').text }}</p>

            </td>
            <td>
              <p v-if="item === 'Italia' || item === 'Eu' || item === 'Extra Eu'" class="text-success text-end mt-3"> {{ value(index, 'kfkm').text}}</p>
              <p v-else class="text-info text-end mt-3">{{ value(index, 'kfkm').text }}</p>
            </td>
          </tr>
        <tr>
          <td class="text-warning mt-3">Totale Complessivo</td>
          <td class="text-warning text-end mt-3">{{euro.format(totali.totale)}}</td>
          <td class="text-warning text-end mt-3">{{totali.ckm}}</td>
          <td class="text-warning text-end mt-3">{{totali.kfkm}}</td>
        </tr>
        </tbody>
      </VTable>
      </VCardText>
      <VCardText class="d-flex justify-end gap-3 flex-wrap d-print-none">
        <VBtn
          color="secondary"
          variant="tonal"
          @click="printInvoice"
        >
          Print
        </VBtn>
        <VBtn @click="close">
          Agree
        </VBtn>
      </VCardText>
    </VCard>
  </VDialog>
</template>
