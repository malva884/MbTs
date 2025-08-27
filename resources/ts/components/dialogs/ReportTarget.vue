<script setup lang="ts">
import { useTheme } from 'vuetify'
import { hexToRgb } from '@layouts/utils'
interface Emit {
  (e: 'update:isDialogVisible', value: boolean): void
  (e: 'targetsData', value: any): void
}

interface Props {
  isDialogVisible: boolean
  targetsData: any
  titoloData: string
}
const props = defineProps<Props>()
const emit = defineEmits<Emit>()

const close = () => {
  emit('update:isDialogVisible', false)
}

const vuetifyTheme = useTheme()

const chartOptions = computed(() => {
  const currentTheme = vuetifyTheme.current.value.colors
  const variableTheme = vuetifyTheme.current.value.variables

  return {
    labels: [''],
    chart: {
      type: 'radialBar',
    },
    plotOptions: {
      radialBar: {
        offsetY: 10,
        startAngle: -140,
        endAngle: 100,
        hollow: {
          size: '65%',
        },
        track: {
          background: currentTheme.surface,
          strokeWidth: '100%',
        },
        dataLabels: {
          name: {
            offsetY: -20,
            color: `rgba(${hexToRgb(currentTheme['on-surface'])},${variableTheme['disabled-opacity']})`,
            fontSize: '13px',
            fontWeight: '400',
            fontFamily: 'Public Sans',
          },
          value: {
            offsetY: 0,
            color: `rgba(${hexToRgb(currentTheme['on-background'])},${variableTheme['high-emphasis-opacity']})`,
            fontSize: '38px',
            fontWeight: '400',
            fontFamily: 'Public Sans',
          },
        },
      },
    },
    colors: [currentTheme.error],
    fill: {
      type: 'gradient',
      gradient: {
        shade: 'dark',
        shadeIntensity: 0.5,
        gradientToColors: [currentTheme.success],
        inverseColors: true,
        opacityFrom: 1,
        opacityTo: 1,
        stops: [0, 70, 100],
      },
    },
    stroke: {
      dashArray: 5,
    },
    grid: {
      padding: {
        top: -20,
        bottom: 5,
      },
    },
    states: {
      hover: {
        filter: {
          type: 'none',
        },
      },
      active: {
        filter: {
          type: 'none',
        },
      },
    },
    responsive: [
      {
        breakpoint: 960,
        options: {
          chart: {
            height: 280,
          },
        },
      },
    ],
  }
})

const printInvoice = () => {
  window.print()
}

onMounted(() => {
  console.log(props)
})
</script>

<template>
  <VDialog
    :model-value="props.isDialogVisible"
    persistent
    class="v-dialog-l"
  >
    <!-- Dialog close btn -->
    <DialogCloseBtn @click="close" class="d-print-none"/>
    <VCard :title="props.titoloData">
      <VCardText class="d-flex flex-wrap justify-space-between flex-column flex-sm-row print-row">
        <VRow>
          <VCol
            cols="2"
            v-for="target in props.targetsData"
            :key="target.titolo"
          >
            <VCard
              v-if="target.titolo"
              :title="$t(`Label.${target.titolo}`)"
              class="mb-6"
            >
              <VueApexCharts
                :height="target.dimensione"
                type="radialBar"
                :options="chartOptions"
                :series="[target.percentuale]"
              />
              <VRow class="border-top text-center mx-0 mt-3">
                <VCol sm="6">
                  <p class="card-text text-muted mb-0">Target</p>
                  <p class="card-text text-muted mb-0">{{target.target}}</p>
                </VCol>
                <VCol sm="6">
                  <p class="card-text text-muted mb-0">In Progress</p>
                  <p class="card-text text-muted mb-0">{{target.valore}}</p>
                </VCol>
              </VRow>
            </VCard>
          </VCol>
        </VRow>
      </VCardText>
      <VCardText class="d-flex justify-end gap-3 flex-wrap d-print-none">
        <VBtn @click="close">
          Close
        </VBtn>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<style scoped lang="scss">

</style>
