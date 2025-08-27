<script setup lang="ts">
import { useTheme } from 'vuetify'
import { hexToRgb } from '@layouts/utils'
import type {Conformita} from "@/views/quality/conformita/type";
import {useI18n} from "vue-i18n";

interface Emit {
  (e: 'update:openChart', value: boolean): void
  (e: 'update:infoMacchina', value: object): void
}
interface Props {
  macchina: string
  macchinaId: string
  ordine: object
  infoMacchina: object
}

const props = defineProps<Props>()
const emit = defineEmits<Emit>()
const vuetifyTheme = useTheme()
const { t } = useI18n()
const series = [(props.infoMacchina?.Velocità_Linea * 100 / 600)]

const chartOptions = computed(() => {
  const currentTheme = vuetifyTheme.current.value.colors
  const variableTheme = vuetifyTheme.current.value.variables

  return {
    labels: [t('Label.Velocita-Linea')],
    chart: {
      type: 'radialBar',
    },
    plotOptions: {
      radialBar: {
        size: 130,
        offsetY: 10,
        startAngle: -130,
        endAngle: 130,
        hollow: {
          size: '65%',
        },
        track: {
          background: `rgba(${hexToRgb(currentTheme['on-background'])},${variableTheme['disabled-opacity']})`,
          strokeWidth: '150%',
        },
        dataLabels: {
          name: {
            offsetY: -5,
            color: `rgba(${hexToRgb(currentTheme['on-background'])},${variableTheme['high-emphasis-opacity']})`,
            fontSize: '0.800rem',
            fontFamily: 'Public Sans',
          },
          value: {
            formatter(val: any) {
              return `${Number.parseFloat(val).toFixed(2)} M/min`
            },
            offsetY: 45,
            color: `rgba(${hexToRgb(currentTheme['on-background'])},${variableTheme['high-emphasis-opacity']})`,
            fontSize: '1.314rem',
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
        type: 'horizontal',
        shadeIntensity: 0.5,
        gradientToColors: [currentTheme.success],
        inverseColors: !0,
        opacityFrom: 1,
        opacityTo: 1,
        stops: [0, 180],
      },
    },
    stroke: {
      dashArray: 8,
    },
    responsive: [
      {
        breakpoint: 260,
        options: {
          chart: {
            height: 340,
          },
        },
      },
    ],
  }
})

const earningsReports = [
  {
    color: 'info',
    icon: 'tabler-chart-pie-2',
    title: t('Label.Metri-Prodotti'),
    amount: (props.infoMacchina?.Metri_Prodotti / 1000) + ' Km',
  },
  {
    color: 'primary',
    icon: 'tabler-currency-dollar',
    title: t('Label.Estrusione'),
    amount: props.infoMacchina?.Velocità_Estrusore,
  },
  {
    color: 'error',
    icon: 'tabler-gradienter',
    title: t('Label.Diametro'),
    amount: props.infoMacchina?.Diametro,
  },
]

const resolveStato = (stato: string) => {
  if (stato === 'Run')
    return { text: stato, class: 'text-center text-white color-primary bg-success' }
  else if (stato === 'Stop')
    return { text: stato, class: 'text-center text-white color-primary bg-error' }
  else if (stato === 'OPS')
    return { text: stato, class: 'text-center text-white color-primary bg-primary' }
  else
    return { text: stato, class: 'text-center text-white color-primary bg-warning' }
}

const openChart = () => {
  emit('update:infoMacchina', { id: props.macchinaId, macchina: props.macchina, quatroPuntoZero: props.infoMacchina.quatroPuntoZero })
  emit('update:openChart', true)
}
</script>

<template>
  <VCard>
    <VCardItem class="pb-0">
      <VCardTitle class="text-center">{{props.macchina}}</VCardTitle>
      <VCardSubtitle :class="resolveStato(props.infoMacchina.Stato).class" style="border-radius: 10px 10px 10px 10px;"> {{resolveStato(props.infoMacchina.Stato).text}}</VCardSubtitle>

      <template #append>
        <div class="mt-n10 me-n2">
          <IconBtn>
            <VIcon
              :size="18"
              icon="tabler-window-maximize"
              @click="openChart"
            />
          </IconBtn>
        </div>
      </template>
    </VCardItem>

    <VCardText>
      <VRow>
        <VCol
          cols="12"
          md="4"
          sm="3"
        >
          <VueApexCharts
            :options="chartOptions"
            :series="series"
            height="180"
          />
        </VCol>

        <VCol
          v-if="resolveStato(props.infoMacchina.Stato).text !== 'Stop'"
          cols="12"
          md="8"
          sm="7"
          class="mt-8"
        >
          <p class="text-center">{{props.ordine.Ol}}</p>
          <p class="text-center">{{props.ordine.Prodotto}}</p>
          <div class="d-flex align-center gap-4">
            <VAvatar
              color="primary"
              variant="tonal"
              size="32"
            >
              <VIcon icon="tabler-ruler-measure" />
            </VAvatar>

            <div class="d-flex flex-column">
              <span class="text-h5 font-weight-medium">{{ props.infoMacchina?.TotaliMetiri  }} Km</span>
              <span class="text-sm">
                {{$t('Label.Totali-Metri-Prodotti')}}
              </span>
            </div>
            <VAvatar
              color="primary"
              variant="tonal"
              size="32"
            >
              <VIcon icon="tabler-tool" />
            </VAvatar>

            <div class="d-flex flex-column">
              <span class="text-h5 font-weight-medium">{{ props.infoMacchina?.TotaleFermo }} h</span>
              <span class="text-sm">
                {{$t('Label.Ore-Fermi-Macchina')}}
              </span>
            </div>
          </div>
        </VCol>
      </VRow>
      <div class="border rounded mt-3 px-5 py-4">
        <VRow>
          <VCol
            v-for="report in earningsReports"
            :key="report.title"
            cols="12"
            sm="4"
          >
            <div class="d-flex align-center">
              <VAvatar
                rounded
                size="22"
                :color="report.color"
                variant="tonal"
                class="me-2"
              >
                <VIcon
                  size="15"
                  :icon="report.icon"
                />
              </VAvatar>

              <h6 class="text-h6 font-weight-small">
                {{ report.title }}
              </h6>
            </div>
            <h6 class="text-h5 my-3">
              {{ report.amount }}
            </h6>
          </VCol>
        </VRow>
      </div>
    </VCardText>
    <p class="text-center">{{ props.infoMacchina?.UltimoDatoRicevuto }}</p>
  </VCard>
</template>

<style lang="scss" scoped>
.card-list {
  --v-card-list-gap: 18px;
}
</style>
