<script setup lang="ts">
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import 'leaflet.markercluster'
import 'leaflet.markercluster/dist/MarkerCluster.css'
import 'leaflet.markercluster/dist/MarkerCluster.Default.css'

interface DdtPunto {
  lat: number
  lng: number
  mese: string
  nome: string | null
  indirizzo: string | null
  provincia: string | null
  regione: string | null
  paese: string | null
  n_ddt: number
  colli: number
  peso: number
  costo: number
  vettori: string[]
  mesi_distinti?: number
}

const props = defineProps<{ filtri: Record<string, any> }>()

const mapContainer = ref<HTMLElement | null>(null)
const loading = ref(true)
const punti = ref<DdtPunto[]>([])
const mesi = ref<string[]>([])
const meseSelezionato = ref<string | null>(null) // null = tutti i mesi
const geolocalizzati = ref(0)
const nonGeolocalizzati = ref(0)

let map: L.Map | null = null
let clusterGroup: L.MarkerClusterGroup | null = null

const labelMese = (mese: string) => {
  const [y, m] = mese.split('-').map(Number)

  return new Intl.DateTimeFormat('it-IT', { month: 'long', year: 'numeric' }).format(new Date(y, m - 1, 1))
}

const opzioniMese = computed(() => [
  { title: 'Tutti i mesi', value: null },
  ...mesi.value.map(m => ({ title: labelMese(m), value: m })),
])

// In modalita' "Tutti i mesi" le feature dello stesso punto vanno fuse,
// altrimenti si sovrappongono esattamente sullo stesso marker
const mergePerCoordinate = (rows: DdtPunto[]): DdtPunto[] => {
  const merged = new Map<string, DdtPunto>()

  for (const p of rows) {
    const key = `${p.lat}|${p.lng}`
    const esistente = merged.get(key)

    if (!esistente) {
      merged.set(key, { ...p, vettori: [...p.vettori], mesi_distinti: 1 })
      continue
    }

    esistente.n_ddt += p.n_ddt
    esistente.colli += p.colli
    esistente.peso += p.peso
    esistente.costo += p.costo
    esistente.mesi_distinti = (esistente.mesi_distinti ?? 1) + 1

    for (const v of p.vettori) {
      if (!esistente.vettori.includes(v))
        esistente.vettori.push(v)
    }
  }

  return [...merged.values()]
}

const righeVisibili = computed(() =>
  meseSelezionato.value
    ? punti.value.filter(p => p.mese === meseSelezionato.value)
    : mergePerCoordinate(punti.value),
)

// Stesso interpolatore del layer mapbox precedente: n_ddt 1 -> r7, 10 -> r14, 30 -> r22
const raggioPunto = (n: number) => {
  if (n <= 1)
    return 7
  if (n >= 30)
    return 22

  return 7 + ((n - 1) / 29) * 15
}

const fitToData = () => {
  if (!map || righeVisibili.value.length === 0)
    return

  const bounds = L.latLngBounds(
    righeVisibili.value.map(p => [p.lat, p.lng] as [number, number]),
  )

  map.fitBounds(bounds, { padding: [60, 60], maxZoom: 11 })
}

const escapeHtml = (value: unknown) =>
  String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')

const formatEuro = (value: any) =>
  new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(Number(value) || 0)

const formatNumero = (value: any) =>
  Number(value || 0).toLocaleString('it-IT', { maximumFractionDigits: 2 })

const popupHtml = (p: DdtPunto) => {
  const righe: string[] = []

  righe.push(`<div style="font-weight:600;font-size:14px;margin-bottom:4px;">${escapeHtml(p.nome || 'Destinatario')}</div>`)

  if (p.indirizzo)
    righe.push(`<div style="font-size:12px;color:#666;margin-bottom:6px;">${escapeHtml(p.indirizzo)}</div>`)

  const info: string[] = [`<strong>${p.n_ddt}</strong> DDT`]

  if (p.mesi_distinti && p.mesi_distinti > 1)
    info.push(`${p.mesi_distinti} mesi`)
  else if (p.mese)
    info.push(labelMese(p.mese))

  if (p.colli)
    info.push(`${p.colli} colli`)

  if (p.peso)
    info.push(`${formatNumero(p.peso)} kg`)

  if (p.costo)
    info.push(formatEuro(p.costo))

  righe.push(`<div style="font-size:12px;margin-bottom:4px;">${info.join(' &middot; ')}</div>`)

  if (p.vettori?.length)
    righe.push(`<div style="font-size:12px;color:#666;">Vettori: ${escapeHtml(p.vettori.join(', '))}</div>`)

  if (p.paese && p.paese !== 'Italia')
    righe.push(`<div style="font-size:12px;color:#666;">${escapeHtml(p.paese)}</div>`)

  return `<div style="font-family:inherit;">${righe.join('')}</div>`
}

// Icona cluster: stesse soglie/colori dello step mapbox precedente
// (somma n_ddt <10 azzurro, <30 giallo, >=30 rosa)
const clusterIcon = (cluster: L.MarkerCluster) => {
  const somma = cluster.getAllChildMarkers()
    .reduce((acc, m) => acc + ((m as any).ddtPunto?.n_ddt ?? 0), 0)

  const colore = somma >= 30 ? '#f28cb1' : somma >= 10 ? '#f1f075' : '#51bbd6'
  const size = somma >= 30 ? 60 : somma >= 10 ? 44 : 34

  return L.divIcon({
    className: '',
    iconSize: [size, size],
    html: `<div style="inline-size:${size}px;block-size:${size}px;border-radius:50%;background:${colore};border:2px solid #fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:600;color:#1f2937;box-shadow:0 1px 4px rgba(0,0,0,0.3)">${somma}</div>`,
  })
}

const updateMarkers = () => {
  if (!clusterGroup)
    return

  clusterGroup.clearLayers()

  for (const p of righeVisibili.value) {
    const marker = L.circleMarker([p.lat, p.lng], {
      radius: raggioPunto(p.n_ddt),
      color: '#ffffff',
      weight: 2,
      fillColor: '#7367f0',
      fillOpacity: 0.8,
      opacity: 1,
    })

    ;(marker as any).ddtPunto = p
    marker.bindPopup(popupHtml(p), { maxWidth: 320 })
    clusterGroup.addLayer(marker)
  }
}

const loadPunti = async () => {
  loading.value = true

  const { data } = await useApi<any>(createUrl('/sp/ddt/mappa', {
    query: props.filtri,
  }))

  const res = data.value

  punti.value = res?.punti ?? []
  mesi.value = res?.mesi ?? []
  geolocalizzati.value = res?.geolocalizzati ?? 0
  nonGeolocalizzati.value = res?.non_geolocalizzati ?? 0

  if (meseSelezionato.value && !mesi.value.includes(meseSelezionato.value))
    meseSelezionato.value = null

  updateMarkers()
  fitToData()
  loading.value = false
}

watch(() => props.filtri, () => {
  loadPunti()
}, { deep: true })

watch(meseSelezionato, () => {
  updateMarkers()
})

onMounted(() => {
  map = L.map(mapContainer.value!, {
    zoomControl: false,
    minZoom: 3,
  }).setView([41.9, 12.5], 5)

  L.control.zoom({ position: 'topright' }).addTo(map)

  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
  }).addTo(map)

  clusterGroup = L.markerClusterGroup({
    maxClusterRadius: 50,
    showCoverageOnHover: false,
    zoomToBoundsOnClick: true, // click sul cluster = zoom di espansione
    iconCreateFunction: clusterIcon,
  })

  map.addLayer(clusterGroup)

  loadPunti()

  // il container e' appena stato montato da un v-if: forza il ricalcolo dimensioni
  nextTick(() => map?.invalidateSize())
})

onBeforeUnmount(() => {
  map?.remove()
  map = null
  clusterGroup = null
})
</script>

<template>
  <div>
    <div class="d-flex align-center flex-wrap gap-3 mb-3">
      <div style="min-width: 220px; max-width: 280px;">
        <AppSelect
          v-model="meseSelezionato"
          :items="opzioniMese"
          label="Mese"
          prepend-inner-icon="tabler-calendar"
        />
      </div>
      <VChip
        color="primary"
        variant="tonal"
        size="small"
      >
        {{ geolocalizzati }} spedizioni geolocalizzate
      </VChip>
      <VChip
        v-if="nonGeolocalizzati > 0"
        color="warning"
        variant="tonal"
        size="small"
      >
        {{ nonGeolocalizzati }} non geolocalizzate
      </VChip>
    </div>

    <div class="ddt-map-wrapper position-relative">
      <div
        ref="mapContainer"
        class="ddt-map-container rounded-lg"
      />
      <VOverlay
        :model-value="loading"
        contained
        persistent
        class="align-center justify-center"
      >
        <VProgressCircular
          indeterminate
          color="primary"
        />
      </VOverlay>
      <div
        v-if="!loading && punti.length === 0"
        class="position-absolute d-flex flex-column align-center justify-center"
        style="inset: 0; background: rgba(var(--v-theme-surface), 0.75);"
      >
        <VIcon
          icon="tabler-map-off"
          size="40"
          class="text-disabled mb-2"
        />
        <p class="text-body-1 text-disabled mb-0">
          Nessuna spedizione geolocalizzata per i filtri selezionati
        </p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.ddt-map-wrapper {
  block-size: calc(100vh - 230px);
  min-block-size: 480px;
}

.ddt-map-container {
  block-size: 100%;
  inline-size: 100%;
}
</style>
