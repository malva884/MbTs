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
    <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
      <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
        <div class="d-flex align-center gap-2">
          <VIcon icon="tabler-report" size="24" color="primary" />
          <div>
            <div class="text-h6 font-weight-medium">Report Fatturato</div>
            <div class="text-caption text-medium-emphasis">Riepilogo per area geografica</div>
          </div>
        </div>
        <div class="d-flex align-center gap-2">
          <VBtn
            color="secondary"
            variant="outlined"
            density="comfortable"
            prepend-icon="tabler-printer"
            class="d-print-none"
            @click="printInvoice"
          >
            Print
          </VBtn>
          <VBtn
            color="primary"
            variant="flat"
            density="comfortable"
            prepend-icon="tabler-check"
            class="d-print-none"
            @click="close"
          >
            Chiudi
          </VBtn>
        </div>
      </VCardText>
      <VDivider />
      <VCardText class="pa-3 print-row">
        <VTable
          v-if="view"
          density="comfortable"
          class="text-no-wrap rounded-lg"
        >
          <thead>
            <tr>
              <th>Paese</th>
              <th class="text-end">Amount in Local Current</th>
              <th class="text-end">Ckm</th>
              <th class="text-end">KFKM</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, index) in labelTabel" :key="index">
              <td>
                <span v-if="item === 'Italia' || item === 'Eu' || item === 'Extra Eu'" class="text-success font-weight-medium">{{ item }}</span>
                <span v-else class="text-info ml-4">- {{ item }}</span>
              </td>
              <td>
                <span v-if="item === 'Italia' || item === 'Eu' || item === 'Extra Eu'" class="text-success text-end d-block">{{ euro.format(value(index, 'totale').text) }}</span>
                <span v-else class="text-info text-end d-block">{{ euro.format(value(index, 'totale').text) }}</span>
              </td>
              <td>
                <span v-if="item === 'Italia' || item === 'Eu' || item === 'Extra Eu'" class="text-success text-end d-block">{{ value(index, 'ckm').text }}</span>
                <span v-else class="text-info text-end d-block">{{ value(index, 'ckm').text }}</span>
              </td>
              <td>
                <span v-if="item === 'Italia' || item === 'Eu' || item === 'Extra Eu'" class="text-success text-end d-block">{{ value(index, 'kfkm').text }}</span>
                <span v-else class="text-info text-end d-block">{{ value(index, 'kfkm').text }}</span>
              </td>
            </tr>
            <tr class="font-weight-bold">
              <td class="text-warning">Totale Complessivo</td>
              <td class="text-warning text-end">{{ euro.format(totali.totale) }}</td>
              <td class="text-warning text-end">{{ totali.ckm }}</td>
              <td class="text-warning text-end">{{ totali.kfkm }}</td>
            </tr>
          </tbody>
        </VTable>
      </VCardText>
    </VCard>
  </VDialog>
</template>
