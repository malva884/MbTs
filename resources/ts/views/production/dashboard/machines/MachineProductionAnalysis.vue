<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { useTheme } from 'vuetify'

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
const { t } = useI18n()
const theme = useTheme()
const svgTextColor = computed(() => theme.global.current.value.dark ? '#f5db04' : '#374151')
const svgStrokeColor = computed(() => theme.global.current.value.dark ? '#a8a2a2' : '#374151')
const velocitaVal = computed(() => {
  if (!props.infoMacchina) return 0
  const val = Number(props.infoMacchina?.Velocità_Linea)
  return (val && !isNaN(val)) ? val : 0
})
const velocitaMinima = computed(() => {
  if (!props.infoMacchina) return 600
  const raw = props.infoMacchina?.velocita_minima
  if (raw === undefined || raw === null) return 600
  const val = Number(raw)
  return (!isNaN(val)) ? val : 600
})
const velocitaMaxima = computed(() => {
  const min = velocitaMinima.value || 600
  const max = min * 1.6 // Velocità minima + 60%
  return (max && !isNaN(max)) ? max : 960
})
const velocitaPercent = computed(() => Number.parseFloat((velocitaVal.value * 100 / velocitaMaxima.value).toFixed(1)))

const speedColor = computed(() => {
  if (velocitaVal.value < velocitaMinima.value) return '#ef4444'
  if (velocitaVal.value < velocitaMinima.value * 1.2) return '#eab308'
  return '#22c55e'
})

const minSpeedAngle = computed(() => {
  const min = velocitaMinima.value
  const max = velocitaMaxima.value
  if (!max || max <= 0) return -120
  return (min / max) * 240 - 120
})

const velocitaEstrusore = computed(() => {
  if (!props.infoMacchina) return 0
  const val = Number(props.infoMacchina?.Velocità_Estrusore)
  return (val && !isNaN(val)) ? val : 0
})
const estrusoreMax = computed(() => 500) // Valore massimo di riferimento per l'estrusore
const estrusorePercent = computed(() => {
  if (estrusoreMax.value <= 0) return 0
  return Math.min(100, Math.max(0, (velocitaEstrusore.value / estrusoreMax.value) * 100))
})
const estrusoreColor = computed(() => {
  if (velocitaEstrusore.value < 200) return '#ef4444'
  if (velocitaEstrusore.value < 350) return '#eab308'
  return '#22c55e'
})

const backgroundArc = computed(() => {
  const cx = 110, cy = 110, r = 80
  const startAngle = -120
  const endAngle = 120
  const toRad = (deg: number) => deg * Math.PI / 180
  const x1 = cx + r * Math.sin(toRad(startAngle))
  const y1 = cy - r * Math.cos(toRad(startAngle))
  const x2 = cx + r * Math.sin(toRad(endAngle))
  const y2 = cy - r * Math.cos(toRad(endAngle))
  return `M ${x1.toFixed(2)} ${y1.toFixed(2)} A ${r} ${r} 0 1 1 ${x2.toFixed(2)} ${y2.toFixed(2)}`
})

const arcPath = computed(() => {
  const cx = 110, cy = 110, r = 80
  const startAngle = -120
  const sweepAngle = Math.min(240, 240 * velocitaVal.value / velocitaMaxima.value)
  const endAngle = startAngle + sweepAngle
  const toRad = (deg: number) => deg * Math.PI / 180
  const x1 = cx + r * Math.sin(toRad(startAngle))
  const y1 = cy - r * Math.cos(toRad(startAngle))
  const x2 = cx + r * Math.sin(toRad(endAngle))
  const y2 = cy - r * Math.cos(toRad(endAngle))
  const largeArc = sweepAngle > 180 ? 1 : 0
  return `M ${x1.toFixed(2)} ${y1.toFixed(2)} A ${r} ${r} 0 ${largeArc} 1 ${x2.toFixed(2)} ${y2.toFixed(2)}`
})

const needleAngle = computed(() => -30 + (velocitaVal.value / velocitaMaxima.value) * 240)

const statoInfo = computed(() => {
  const stato = props.infoMacchina?.Stato
  const map: Record<string, { text: string; color: string }> = {
    Run: { text: 'Run', color: 'success' },
    Stop: { text: 'Stop', color: 'error' },
    OPS: { text: 'OPS', color: 'primary' },
  }
  const info = map[stato] || { text: stato, color: 'warning' }
  return { ...info, class: `text-center text-white color-primary bg-${info.color}` }
})

const earningsReports = computed(() => [
  {
    color: 'info',
    icon: 'tabler-chart-pie-2',
    title: t('Label.Metri-Prodotti'),
    amount: (props.infoMacchina?.Metri_Prodotti / 1000) + ' Km',
  },
  {
    color: 'primary',
    icon: 'tabler-engine',
    title: t('Label.Estrusione'),
    amount: props.infoMacchina?.Velocità_Estrusore,
  },
  {
    color: 'error',
    icon: 'tabler-gradienter',
    title: t('Label.Diametro'),
    amount: props.infoMacchina?.Diametro,
  },
])

const openChart = () => {
  emit('update:infoMacchina', { id: props.macchinaId, macchina: props.macchina, quatroPuntoZero: props.infoMacchina.quatroPuntoZero })
  emit('update:openChart', true)
}
</script>

<template>
  <VCard class="modern-card" elevation="2">
    <!-- Header Elegante -->
    <div class="card-header pa-2">
      <div class="d-flex align-center justify-space-between">
        <div>
          <h2 class="text-white text-h6 font-weight-bold">{{props.macchina}}</h2>
          <VChip
            :color="statoInfo.color"
            size="small"
            class="mt-1 font-weight-bold"
            label
          >
            {{statoInfo.text}}
          </VChip>
        </div>
        <VBtn
          icon
          variant="text"
          color="white"
          density="compact"
          @click="openChart"
        >
          <VIcon icon="tabler-chart-line" size="20" />
        </VBtn>
      </div>
    </div>

    <VCardText class="pa-3">
      <!-- Sezione Principale -->
      <VRow>
        <!-- Tachimetro -->
        <VCol cols="12" md="4" sm="6">
          <VCard class="metric-card h-100" outlined>
            <VCardText class="d-flex flex-column align-center justify-center pa-3 h-100">
              <div class="tachometer">
                <svg viewBox="0 0 220 140" class="tachometer-svg">
                  <!-- Arco sfondo -->
                  <path :d="backgroundArc" fill="none" stroke="#e5e7eb" stroke-width="12" stroke-linecap="round"/>
                  <!-- Arco valore -->
                  <path :d="arcPath" fill="none" :stroke="speedColor" stroke-width="12" stroke-linecap="butt"/>

                  <!-- Tacche grandi -->
                  <g v-for="n in 11" :key="n"
                     :transform="`rotate(${(n-1) * 24 - 120} 110 110)`">
                    <line x1="110" y1="40" x2="110" y2="50" :stroke="svgStrokeColor" stroke-width="2"/>
                  </g>

                  <!-- Indicatore velocità minima -->
                  <g :transform="`rotate(${minSpeedAngle} 110 110)`">
                    <line x1="110" y1="30" x2="110" y2="55" stroke="#f59e0b" stroke-width="3" stroke-linecap="round"/>
                    <circle cx="110" cy="55" r="3" fill="#f59e0b"/>
                  </g>

                  <!-- Numeri -->
                  <text x="62" y="130" text-anchor="middle" font-size="12" font-weight="600" :fill="svgTextColor">0</text>
                  <text x="65" y="82" text-anchor="middle" font-size="12" font-weight="600" :fill="svgTextColor">{{ Math.round(velocitaMaxima * 0.25) }}</text>
                  <text x="110" y="60" text-anchor="middle" font-size="12" font-weight="600" :fill="svgTextColor">{{ Math.round(velocitaMaxima * 0.5) }}</text>
                  <text x="155" y="82" text-anchor="middle" font-size="12" font-weight="600" :fill="svgTextColor">{{ Math.round(velocitaMaxima * 0.75) }}</text>
                  <text x="158" y="130" text-anchor="middle" font-size="12" font-weight="600" :fill="svgTextColor">{{ Math.round(velocitaMaxima) }}</text>

                  <!-- Lancetta -->
                  <g :transform="`rotate(${(velocitaVal / velocitaMaxima) * 240 - 120} 110 110)`">
                    <polygon points="108,110 112,110 110,35" fill="#dc2626"/>
                    <circle cx="110" cy="110" r="6" :fill="svgTextColor"/>
                    <circle cx="110" cy="110" r="3" fill="#dc2626"/>
                  </g>
                </svg>
                <div class="tachometer-value">
                  <span class="tachometer-number" :style="{ color: speedColor }">{{ velocitaVal.toFixed(1) }}</span>
                  <span class="tachometer-unit">M/min</span>
                </div>
                <div v-if="velocitaMinima > 0" class="tachometer-min">
                  <span class="tachometer-min-label">Min:</span>
                  <span class="tachometer-min-value">{{ velocitaMinima.toFixed(1) }}</span>
                </div>
              </div>
            </VCardText>
          </VCard>
        </VCol>

        <!-- Info Ordine -->
        <VCol v-show="statoInfo.text !== 'Stop'" cols="12" md="4" sm="6">
          <VCard class="metric-card h-100" outlined>
            <VCardText class="d-flex flex-column justify-center align-center h-100 pa-3">
              <VIcon icon="tabler-file-invoice" size="32" color="primary" class="mb-2" />
              <h3 class="text-body-1 font-weight-bold text-center">{{props.ordine?.Ol}}</h3>
              <p class="text-caption text-center text-medium-emphasis mt-1">{{props.ordine?.Prodotto}}</p>
            </VCardText>
          </VCard>
        </VCol>

        <!-- Statistiche Principali -->
        <VCol cols="12" md="4" sm="12">
          <VCard class="metric-card h-100" outlined>
            <VCardText class="d-flex flex-column justify-center h-100 pa-3 gap-2">
              <div class="d-flex align-center gap-3">
                <div class="icon-container-large bg-primary-lighten-4">
                  <VIcon icon="tabler-ruler-measure" size="24" color="primary" />
                </div>
                <div class="d-flex align-center gap-2 flex-wrap">
                  <span class="text-h6 font-weight-bold">{{ props.infoMacchina?.TotaliMetiri }} Km</span>
                  <span class="text-caption text-medium-emphasis">{{ $t('Label.Totali-Metri-Prodotti') }}</span>
                </div>
              </div>
              <div class="d-flex align-center gap-3">
                <div class="icon-container bg-error-lighten-4">
                  <VIcon icon="tabler-tool" size="22" color="error" />
                </div>
                <div class="d-flex align-center gap-2 flex-wrap">
                  <span class="text-h6 font-weight-bold">{{ props.infoMacchina?.TotaleFermo }} h</span>
                  <span class="text-caption text-medium-emphasis">{{$t('Label.Ore-Fermi-Macchina')}}</span>
                </div>
              </div>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <!-- Statistiche Dettagliate -->
      <VRow class="mt-2">
        <VCol
          v-for="report in earningsReports"
          :key="report.title"
          cols="12"
          sm="4"
        >
          <VCard class="detail-card" outlined>
            <VCardText class="d-flex flex-column pa-3 gap-2">
              <div class="d-flex align-center gap-3">
                <div class="icon-container-large" :class="`bg-${report.color}-lighten-4`">
                  <VIcon :icon="report.icon" size="28" :color="report.color" />
                </div>
                <div class="d-flex align-center gap-2 flex-wrap">
                  <span class="text-h5 font-weight-bold">{{ report.amount }}</span>
                  <span class="text-caption text-medium-emphasis">{{ report.title }}</span>
                </div>
              </div>
              <!-- Barra Estrusore -->
              <div v-if="report.title === t('Label.Estrusione')" class="estrusore-bar-container">
                <div class="estrusore-track">
                  <div class="estrusore-fill" :style="{ width: estrusorePercent + '%', backgroundColor: estrusoreColor }"></div>
                </div>
              </div>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <!-- Footer -->
      <div class="text-center mt-2">
        <span class="text-caption text-disabled">
          <VIcon icon="tabler-clock" size="14" class="mr-1" />
          {{ props.infoMacchina?.UltimoDatoRicevuto }}
        </span>
      </div>
    </VCardText>
  </VCard>
</template>

<style lang="scss" scoped>
.modern-card {
  border-radius: 16px;
  overflow: hidden;
}

.card-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.metric-card {
  border-radius: 12px;
}

.detail-card {
  border-radius: 12px;
}

.icon-container {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.icon-container-large {
  width: 56px;
  height: 56px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.gap-4 {
  gap: 16px;
}

/* Tachimetro Realistico */
.tachometer {
  position: relative;
  width: 280px;
  height: 170px;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.tachometer-svg {
  width: 100%;
  height: 140px;
}


.tachometer-value {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-top: -10px;
}

.tachometer-number {
  font-size: 2rem;
  font-weight: 800;
  line-height: 1;
  font-family: 'Public Sans', sans-serif;
}

.tachometer-unit {
  font-size: 0.75rem;
  font-weight: 500;
  color: rgb(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
  font-family: 'Public Sans', sans-serif;
}

.tachometer-min {
  display: flex;
  align-items: center;
  gap: 4px;
  margin-top: 4px;
}

.tachometer-min-label {
  font-size: 0.7rem;
  font-weight: 500;
  color: rgb(var(--v-theme-on-surface), var(--v-disabled-opacity));
  font-family: 'Public Sans', sans-serif;
}

.tachometer-min-value {
  font-size: 0.85rem;
  font-weight: 600;
  color: rgb(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
  font-family: 'Public Sans', sans-serif;
}

/* Barra Estrusore */
.estrusore-bar-container {
  width: 100%;
  margin-top: 8px;
  padding: 0 16px;
}

.estrusore-label {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 4px;
  font-size: 0.75rem;
  font-weight: 500;
  color: rgb(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
  font-family: 'Public Sans', sans-serif;
}

.estrusore-track {
  width: 100%;
  height: 8px;
  background-color: rgba(var(--v-theme-on-surface), 0.12);
  border-radius: 4px;
  overflow: hidden;
}

.estrusore-fill {
  height: 100%;
  border-radius: 4px;
  transition: width 0.3s ease, background-color 0.3s ease;
}
</style>
