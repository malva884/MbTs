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
    <!-- Dialog Content -->
    <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
      <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
        <div class="d-flex align-center gap-2">
          <VIcon icon="tabler-chart-pie" size="24" color="primary" />
          <div>
            <div class="text-h6 font-weight-medium">{{ props.titoloData }}</div>
            <div class="text-caption text-medium-emphasis">Riepilogo target</div>
          </div>
        </div>
        <div class="d-flex align-center gap-2 d-print-none">
          <VBtn
            color="primary"
            variant="flat"
            density="comfortable"
            prepend-icon="tabler-x"
            @click="close"
          >
            Chiudi
          </VBtn>
        </div>
      </VCardText>
      <VDivider />
      <VCardText class="pa-3 print-row">
        <VRow>
          <VCol
            cols="12"
            sm="6"
            md="4"
            lg="3"
            v-for="target in props.targetsData"
            :key="target.titolo"
          >
            <VCard
              v-if="target.titolo"
              variant="outlined"
              class="bg-surface border-thin rounded-lg mb-3"
            >
              <VCardText class="pa-3">
                <div class="d-flex align-center justify-space-between mb-2">
                  <span class="text-subtitle-2 font-weight-semibold">{{ $t(`Label.${target.titolo}`) }}</span>
                  <VIcon icon="tabler-target-arrow" size="18" color="primary" />
                </div>
                <VueApexCharts
                  :height="target.dimensione"
                  type="radialBar"
                  :options="chartOptions"
                  :series="[target.percentuale]"
                />
                <VRow class="border-t text-center mx-0 mt-2">
                  <VCol cols="6" class="py-2">
                    <p class="text-caption text-medium-emphasis mb-0">Target</p>
                    <p class="text-body-2 font-weight-medium mb-0">{{ target.target }}</p>
                  </VCol>
                  <VCol cols="6" class="py-2">
                    <p class="text-caption text-medium-emphasis mb-0">In Progress</p>
                    <p class="text-body-2 font-weight-medium mb-0">{{ target.valore }}</p>
                  </VCol>
                </VRow>
              </VCardText>
            </VCard>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<style scoped lang="scss">

</style>
