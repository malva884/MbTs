<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { useTheme } from 'vuetify'

// i18n
import moment from 'moment'

const { t } = useI18n()

definePage({
  meta: {
    action: 'admin',
    subject: 'Produzione-Business-Intelligence',
  },
})

// unused t
const route = useRoute()
const router = useRouter()
const vuetifyTheme = useTheme()
const isDark = computed(() => vuetifyTheme.current.value.dark)

// Stato ricerca
const searchCode = ref('')
const searchInput = ref('')
const isSearching = ref(false)
const hasSearched = ref(false)
const summaryData = ref<any>(null)
const searchSuggestions = ref<any[]>([])
const isSuggesting = ref(false)
const lastProductSearch = ref<string | null>(null)

// Tab attiva
const currentTab = ref('strisciate')

// Dati per tab
const loadingStrisciate = ref(false)
const strisciateList = ref<any[]>([])
const strutturaProduzione = ref<any[]>([])
const strisciateKpi = ref<any>(null)

// Dialog dettaglio scarti collegati al lotto di produzione
const scartiDialog = ref(false)
const scartiDialogItem = ref<any>(null)

const openScartiDialog = (item: any) => {
  scartiDialogItem.value = item
  scartiDialog.value = true
}

// Dialog dettaglio consumi collegati al lotto di produzione
const consumiDialog = ref(false)
const consumiDialogItem = ref<any>(null)

const openConsumiDialog = (item: any) => {
  consumiDialogItem.value = item
  consumiDialog.value = true
}

// Dialog dettaglio fermi macchina dell'ordine
const fermiDialog = ref(false)

const loadingFabbisogni = ref(false)
const fabbisogniList = ref<any[]>([])

const loadingGiacenze = ref(false)
const giacenzeList = ref<any[]>([])
const giacenzeKpi = ref<any>(null)

const loadingQualita = ref(false)
const qualitaLoaded = ref(false)
const qualitaSubTab = ref('nc')

const qualitaData = ref<any>({
  non_conformita: [],
  checker_reports: [],
  cpr_tests: [],
  type_tests: [],
  fai_tests: [],
})

const loadingDocumenti = ref(false)
const documentiLoaded = ref(false)

const documentiData = ref<any>({
  documents: [],
  drive_folders: [],
})

const loadingSpedizioni = ref(false)
const spedizioniLoaded = ref(false)

const spedizioniData = ref<any>({
  ddt: [],
  righe_spedite: [],
})

// Telemetria & Diametri PLC da Google Drive
const loadingTelemetria = ref(false)
const telemetriaLoaded = ref(false)
const telemetriaSelectedStrisciata = ref<any>(null)
const telemetriaData = ref<any>(null)
const targetDiametroInput = ref<number | null>(null)
const tolleranzaInput = ref<number | null>(0.1)
const showSpeedSeries = ref(true)

const strisciateConTelemetria = computed(() => {
  return strisciateList.value.filter((s: any) => s.has_telemetria && s.data_inizio && s.macchina_telemetria)
})

// Selettore lavorazioni: filtro per macchina + lista filtrata
const telemetriaMachineFilter = ref<string | null>(null)

const telemetriaMachines = computed(() => {
  const counts = new Map<string, number>()
  strisciateConTelemetria.value.forEach((s: any) => {
    const m = s.macchina_telemetria || s.macchina || '—'
    counts.set(m, (counts.get(m) || 0) + 1)
  })
  return [...counts.entries()]
    .map(([nome, n]) => ({ nome, n }))
    .sort((a, b) => a.nome.localeCompare(b.nome))
})

const strisciateFiltrate = computed(() => {
  if (!telemetriaMachineFilter.value)
    return strisciateConTelemetria.value
  return strisciateConTelemetria.value
    .filter((s: any) => (s.macchina_telemetria || s.macchina) === telemetriaMachineFilter.value)
})

// Struttura produzione (OrderLink): prima gli OL senza order_seq, poi per order_seq decrescente (03,02,01)
const strutturaOrdinata = computed(() => {
  const num = (v: any) => {
    const n = parseInt(String(v ?? '').replace(/\D/g, ''), 10)
    return Number.isNaN(n) ? 0 : n
  }
  const hasSeq = (v: any) => String(v?.order_seq ?? '').trim() !== ''
  return [...strutturaProduzione.value].sort((a: any, b: any) => {
    // Prima gli elementi senza numero di sequenza (order_seq vuoto)
    if (hasSeq(a) !== hasSeq(b))
      return hasSeq(a) ? 1 : -1

    // order_seq decrescente (03, 02, 01...)
    const seqDiff = num(b?.order_seq) - num(a?.order_seq)
    if (seqDiff !== 0)
      return seqDiff

    // A parità di sequenza: OL decrescente
    const olDiff = num(b?.ol) - num(a?.ol)
    if (olDiff !== 0)
      return olDiff
    return String(b?.ol ?? '').localeCompare(String(a?.ol ?? ''))
  })
})

// Tabella strisciate: stessa logica — prima la strisciata ricercata, poi OL decrescente
const strisciateOrdinate = computed(() => {
  const olNum = (v: any) => {
    const n = parseInt(String(v ?? '').replace(/\D/g, ''), 10)
    return Number.isNaN(n) ? 0 : n
  }
  const searched = (item: any) => !!(item.is_ricercato || isSearchedOl(strisciateOl(item)))
  return [...strisciateList.value].sort((a: any, b: any) => {
    if (searched(a) !== searched(b))
      return searched(a) ? -1 : 1

    const diff = olNum(strisciateOl(b)) - olNum(strisciateOl(a))
    if (diff !== 0)
      return diff
    return String(strisciateOl(b)).localeCompare(String(strisciateOl(a)))
  })
})

const strisciataLabel = (s: any) => {
  const mach = s.macchina_telemetria || s.macchina || '—'
  const ini = s.data_inizio ? moment(s.data_inizio).format('DD/MM HH:mm') : '-'
  const fin = s.data_fine ? moment(s.data_fine).format('HH:mm') : ''
  let label = `${mach} — ${ini}${fin ? ' → ' + fin : ''} · OL ${strisciateOl(s)}`
  if (s.bobbina)
    label += ` · Bob. ${s.bobbina}`
  return label
}

// Gestione tabs e badge
const tabs = computed(() => [
  {
    key: 'strisciate',
    title: t('Hub.TabStrisciate'),
    icon: 'tabler-cpu',
    count: summaryData.value?.kpi?.strisciate_count ?? 0,
    color: 'primary',
  },
  {
    key: 'fabbisogni',
    title: t('Hub.TabFabbisogni'),
    icon: 'tabler-components',
    count: summaryData.value?.kpi?.fabbisogni_count ?? 0,
    color: 'info',
  },
  {
    key: 'giacenze',
    title: t('Hub.TabGiacenze'),
    icon: 'tabler-packages',
    count: summaryData.value?.kpi?.giacenze_count ?? giacenzeList.value.length,
    color: 'purple',
  },
  {
    key: 'qualita',
    title: t('Hub.TabQualita'),
    icon: 'tabler-shield-check',
    count: (summaryData.value?.kpi?.nc_totali ?? 0) + (summaryData.value?.kpi?.prove_qualita_count ?? 0),
    badgeColor: (summaryData.value?.kpi?.nc_aperte ?? 0) > 0 ? 'error' : 'secondary',
    badgeAlert: (summaryData.value?.kpi?.nc_aperte ?? 0) > 0,
    color: 'warning',
  },
  {
    key: 'telemetria',
    title: t('Hub.TabDiametri'),
    icon: 'tabler-chart-line',
    count: strisciateConTelemetria.value.length,
    color: 'deep-purple',
  },
  {
    key: 'documenti',
    title: t('Hub.TabDocumenti'),
    icon: 'tabler-folders',
    count: summaryData.value?.kpi?.documenti_count ?? 0,
    color: 'secondary',
  },
  {
    key: 'spedizioni',
    title: t('Hub.TabSpedizioni'),
    icon: 'tabler-truck',
    count: summaryData.value?.kpi?.spedizioni_count ?? 0,
    color: 'success',
  },
])

// Avanzamento percentuale produzione
const avanzamentoPerc = computed(() => {
  const ordinata = Number(summaryData.value?.order_info?.quantita_ordinata ?? 0)
  const prodotta = Number(summaryData.value?.kpi?.quantita_prodotta_totale ?? 0)
  if (!ordinata || ordinata <= 0)
    return 0

  return Math.min(100, Math.round((prodotta / ordinata) * 100))
})

// Caricamento asincrono dati del tab selezionato
const loadCurrentTabData = async () => {
  if (!searchCode.value)
    return

  if (currentTab.value === 'strisciate' && strisciateList.value.length === 0) {
    loadingStrisciate.value = true
    try {
      const { data } = await useApi<any>(createUrl('/production-hub/strisciate', {
        query: { code: searchCode.value },
      }))

      const raw = data.value

      strisciateList.value = Array.isArray(raw)
        ? raw
        : (raw?.data ?? (raw && typeof raw === 'object' ? Object.values(raw) : []))

      strutturaProduzione.value = !Array.isArray(raw) && Array.isArray(raw?.structure)
        ? raw.structure
        : []

      strisciateKpi.value = !Array.isArray(raw) && raw?.kpi
        ? raw.kpi
        : null
    }
    finally {
      loadingStrisciate.value = false
    }
  }
  else if (currentTab.value === 'fabbisogni' && fabbisogniList.value.length === 0) {
    loadingFabbisogni.value = true
    try {
      const { data } = await useApi<any>(createUrl('/production-hub/fabbisogni', {
        query: { code: searchCode.value },
      }))

      const raw = data.value

      fabbisogniList.value = Array.isArray(raw)
        ? raw
        : (raw?.data ?? (raw && typeof raw === 'object' ? Object.values(raw) : []))
    }
    finally {
      loadingFabbisogni.value = false
    }
  }
  else if (currentTab.value === 'giacenze' && !giacenzeKpi.value) {
    loadingGiacenze.value = true
    try {
      const { data } = await useApi<any>(createUrl('/production-hub/giacenze', {
        query: { code: searchCode.value },
      }))

      const raw = data.value ?? {}

      giacenzeList.value = Array.isArray(raw.lotti) ? raw.lotti : []
      giacenzeKpi.value = raw.kpi ?? null
    }
    finally {
      loadingGiacenze.value = false
    }
  }
  else if (currentTab.value === 'qualita' && !qualitaLoaded.value) {
    loadingQualita.value = true
    try {
      const { data } = await useApi<any>(createUrl('/production-hub/qualita', {
        query: { code: searchCode.value },
      }))

      const raw = data.value ?? {}

      qualitaData.value = {
        non_conformita: Array.isArray(raw.non_conformita) ? raw.non_conformita : [],
        checker_reports: Array.isArray(raw.checker_reports) ? raw.checker_reports : [],
        cpr_tests: Array.isArray(raw.cpr_tests) ? raw.cpr_tests : [],
        type_tests: Array.isArray(raw.type_tests) ? raw.type_tests : [],
        fai_tests: Array.isArray(raw.fai_tests) ? raw.fai_tests : [],
      }
      qualitaLoaded.value = true
    }
    finally {
      loadingQualita.value = false
    }
  }
  else if (currentTab.value === 'documenti' && !documentiLoaded.value) {
    loadingDocumenti.value = true
    try {
      const { data } = await useApi<any>(createUrl('/production-hub/documenti', {
        query: { code: searchCode.value },
      }))

      const raw = data.value ?? {}

      documentiData.value = {
        documents: Array.isArray(raw.documents) ? raw.documents : [],
        drive_folders: Array.isArray(raw.drive_folders) ? raw.drive_folders : [],
      }
      documentiLoaded.value = true
    }
    finally {
      loadingDocumenti.value = false
    }
  }
  else if (currentTab.value === 'spedizioni' && !spedizioniLoaded.value) {
    loadingSpedizioni.value = true
    try {
      const { data } = await useApi<any>(createUrl('/production-hub/spedizioni', {
        query: { code: searchCode.value },
      }))

      const raw = data.value ?? {}

      spedizioniData.value = {
        ddt: Array.isArray(raw.ddt) ? raw.ddt : [],
        righe_spedite: Array.isArray(raw.righe_spedite) ? raw.righe_spedite : [],
      }
      spedizioniLoaded.value = true
    }
    finally {
      loadingSpedizioni.value = false
    }
  }
  else if (currentTab.value === 'telemetria') {
    if (strisciateList.value.length === 0) {
      loadingStrisciate.value = true
      try {
        const { data } = await useApi<any>(createUrl('/production-hub/strisciate', {
          query: { code: searchCode.value },
        }))
        const raw = data.value
        strisciateList.value = Array.isArray(raw)
          ? raw
          : (raw?.data ?? (raw && typeof raw === 'object' ? Object.values(raw) : []))
        strutturaProduzione.value = !Array.isArray(raw) && Array.isArray(raw?.structure)
          ? raw.structure
          : []
        strisciateKpi.value = !Array.isArray(raw) && raw?.kpi
          ? raw.kpi
          : null
      }
      finally {
        loadingStrisciate.value = false
      }
    }
    if (!telemetriaLoaded.value && strisciateConTelemetria.value.length > 0) {
      await loadTelemetria(telemetriaSelectedStrisciata.value || strisciateConTelemetria.value[0])
    }
  }
}

// Caricamento telemetria per una specifica strisciata/lavorazione
const loadTelemetria = async (strisc?: any) => {
  if (strisc)
    telemetriaSelectedStrisciata.value = strisc
  else if (!telemetriaSelectedStrisciata.value && strisciateConTelemetria.value.length > 0)
    telemetriaSelectedStrisciata.value = strisciateConTelemetria.value[0]

  const current = telemetriaSelectedStrisciata.value
  if (!current || !current.macchina_telemetria || !current.data_inizio) {
    telemetriaData.value = null
    return
  }

  loadingTelemetria.value = true
  try {
    const { data } = await useApi<any>(createUrl('/production-hub/telemetria', {
      query: {
        macchina: current.macchina_telemetria,
        data_inizio: current.data_inizio,
        data_fine: current.data_fine || '',
        target: targetDiametroInput.value !== null && targetDiametroInput.value !== undefined ? targetDiametroInput.value : '',
        tolleranza: tolleranzaInput.value !== null && tolleranzaInput.value !== undefined ? tolleranzaInput.value : '',
      },
    }))

    if (data.value && data.value.success) {
      telemetriaData.value = data.value
      telemetriaLoaded.value = true
    }
    else {
      telemetriaData.value = null
    }
  }
  catch (e) {
    console.error('Errore caricamento telemetria diametri:', e)
    telemetriaData.value = null
  }
  finally {
    loadingTelemetria.value = false
  }
}

const visualizzaTelemetriaStrisciata = (strisc: any) => {
  telemetriaSelectedStrisciata.value = strisc
  currentTab.value = 'telemetria'
  loadTelemetria(strisc)
}

const round3 = (val: number) => Math.round(val * 1000) / 1000

// Range Slider temporale per telemetria
const telemetriaTimeRange = ref<[number, number]>([0, 0])

const maxRangeIndex = computed(() => {
  const pts = telemetriaData.value?.punti
  return pts && pts.length > 0 ? pts.length - 1 : 0
})

watch(
  () => telemetriaData.value?.punti,
  punti => {
    if (punti && punti.length > 0) {
      telemetriaTimeRange.value = [0, punti.length - 1]
    } else {
      telemetriaTimeRange.value = [0, 0]
    }
  },
  { immediate: true },
)

const rangeIsFiltered = computed(() => {
  const pts = telemetriaData.value?.punti
  if (!pts || pts.length <= 1) return false
  return telemetriaTimeRange.value[0] > 0 || telemetriaTimeRange.value[1] < pts.length - 1
})

const selectedPoints = computed(() => {
  const pts = telemetriaData.value?.punti
  if (!pts || pts.length === 0) return []
  const start = Math.max(0, Math.min(telemetriaTimeRange.value[0], pts.length - 1))
  const end = Math.max(start, Math.min(telemetriaTimeRange.value[1], pts.length - 1))
  return pts.slice(start, end + 1)
})

const isRangeSameDay = computed(() => {
  const pts = telemetriaData.value?.punti
  if (!pts || pts.length < 2) return true
  return moment(pts[0].t).isSame(moment(pts[pts.length - 1].t), 'day')
})

const formatPointTime = (tStr: string) => {
  if (!tStr) return ''
  const m = moment(tStr)
  return isRangeSameDay.value ? m.format('HH:mm:ss') : m.format('DD/MM HH:mm:ss')
}

const rangeStartTime = computed(() => {
  const pts = telemetriaData.value?.punti
  if (!pts || pts.length === 0) return ''
  const idx = Math.max(0, Math.min(telemetriaTimeRange.value[0], pts.length - 1))
  return formatPointTime(pts[idx]?.t)
})

const rangeEndTime = computed(() => {
  const pts = telemetriaData.value?.punti
  if (!pts || pts.length === 0) return ''
  const idx = Math.max(0, Math.min(telemetriaTimeRange.value[1], pts.length - 1))
  return formatPointTime(pts[idx]?.t)
})

const fullStartTime = computed(() => {
  const pts = telemetriaData.value?.punti
  if (!pts || pts.length === 0) return ''
  return formatPointTime(pts[0]?.t)
})

const fullEndTime = computed(() => {
  const pts = telemetriaData.value?.punti
  if (!pts || pts.length === 0) return ''
  return formatPointTime(pts[pts.length - 1]?.t)
})

const rangeDuration = computed(() => {
  const pts = telemetriaData.value?.punti
  if (!pts || pts.length === 0) return ''
  const sIdx = Math.max(0, Math.min(telemetriaTimeRange.value[0], pts.length - 1))
  const eIdx = Math.max(sIdx, Math.min(telemetriaTimeRange.value[1], pts.length - 1))
  const t1 = moment(pts[sIdx]?.t)
  const t2 = moment(pts[eIdx]?.t)
  const diffSec = Math.max(0, t2.diff(t1, 'seconds'))
  const h = Math.floor(diffSec / 3600)
  const m = Math.floor((diffSec % 3600) / 60)
  const s = diffSec % 60
  if (h > 0) return `${h}h ${m}m ${s}s`
  if (m > 0) return `${m}m ${s}s`
  return `${s}s`
})

const resetTimeRange = () => {
  telemetriaTimeRange.value = [0, maxRangeIndex.value]
}

const excludeStart = (pct = 5) => {
  const max = maxRangeIndex.value
  if (max <= 5) return
  const cut = Math.min(Math.round(max * (pct / 100)), telemetriaTimeRange.value[1] - 5)
  telemetriaTimeRange.value = [Math.max(0, cut), telemetriaTimeRange.value[1]]
}

const excludeEnd = (pct = 5) => {
  const max = maxRangeIndex.value
  if (max <= 5) return
  const cut = Math.max(Math.round(max * (1 - pct / 100)), telemetriaTimeRange.value[0] + 5)
  telemetriaTimeRange.value = [telemetriaTimeRange.value[0], Math.min(max, cut)]
}

const applyRegimePreset = () => {
  const max = maxRangeIndex.value
  if (max <= 10) return
  const cutStart = Math.round(max * 0.05)
  const cutEnd = Math.round(max * 0.95)
  telemetriaTimeRange.value = [cutStart, cutEnd]
}

// KPI attivi ricalcolati in tempo reale sulla finestra selezionata
const activeKpi = computed(() => {
  const kpiGlobal = telemetriaData.value?.kpi
  if (!kpiGlobal) return null

  if (!rangeIsFiltered.value) {
    return {
      ...kpiGlobal,
      is_filtered: false,
    }
  }

  const pts = selectedPoints.value
  if (!pts || pts.length === 0) {
    return {
      ...kpiGlobal,
      is_filtered: true,
      campioni_grafico: 0,
      campioni_totali: 0,
      medio: 0,
      min: 0,
      max: 0,
      delta: 0,
      std_dev: 0,
      ovalizzazione_max: 0,
      fuori_tolleranza: 0,
      fuori_tolleranza_pct: 0,
    }
  }

  const diams = pts
    .map((p: any) => p.d)
    .filter((d: any) => typeof d === 'number' && d > 0.001)

  const count = diams.length
  if (count === 0) {
    return {
      ...kpiGlobal,
      is_filtered: true,
      campioni_grafico: 0,
      campioni_totali: 0,
    }
  }

  const sum = diams.reduce((acc: number, v: number) => acc + v, 0)
  const mean = sum / count
  const min = Math.min(...diams)
  const max = Math.max(...diams)
  const delta = max - min

  let variance = 0
  for (const d of diams) {
    variance += Math.pow(d - mean, 2)
  }
  const stdDev = Math.sqrt(variance / count)

  let maxOval = 0
  for (const p of pts) {
    if (p.x > 0.001 && p.y > 0.001) {
      const oval = Math.abs(p.x - p.y)
      if (oval > maxOval) maxOval = oval
    }
  }

  const target = targetDiametroInput.value !== null && targetDiametroInput.value !== undefined
    ? targetDiametroInput.value
    : kpiGlobal.target
  const toll = tolleranzaInput.value !== null && tolleranzaInput.value !== undefined
    ? tolleranzaInput.value
    : kpiGlobal.tolleranza

  let fuoriTolleranza = 0
  if (target !== null && target !== undefined && toll !== null && toll !== undefined && toll > 0) {
    const limMin = target - toll
    const limMax = target + toll
    for (const d of diams) {
      if (d < limMin || d > limMax) {
        fuoriTolleranza++
      }
    }
  }
  const fuoriPct = count > 0 ? (fuoriTolleranza / count) * 100 : 0

  return {
    ...kpiGlobal,
    is_filtered: true,
    campioni_grafico: count,
    campioni_totali: count,
    medio: round3(mean),
    min: round3(min),
    max: round3(max),
    delta: round3(delta),
    std_dev: round3(stdDev),
    ovalizzazione_max: round3(maxOval),
    target,
    tolleranza: toll,
    fuori_tolleranza: fuoriTolleranza,
    fuori_tolleranza_pct: Math.round(fuoriPct * 10) / 10,
  }
})

const apexSeries = computed(() => {
  if (!telemetriaData.value || !telemetriaData.value.punti || telemetriaData.value.punti.length === 0)
    return []

  const punti = selectedPoints.value
  if (!punti || punti.length === 0)
    return []

  const toMs = (tStr: string) => {
    if (!tStr) return 0
    return new Date(tStr.replace(' ', 'T')).getTime()
  }

  const series: any[] = [
    {
      name: t('Hub.SeriesDiameter'),
      type: 'line',
      data: punti.map((p: any) => ({
        x: toMs(p.t),
        y: p.d,
      })),
    },
  ]

  const hasX = punti.some((p: any) => p.x > 0)
  const hasY = punti.some((p: any) => p.y > 0)

  if (hasX) {
    series.push({
      name: t('Hub.SeriesDiameterX'),
      type: 'line',
      data: punti.map((p: any) => ({
        x: toMs(p.t),
        y: p.x,
      })),
    })
  }

  if (hasY) {
    series.push({
      name: t('Hub.SeriesDiameterY'),
      type: 'line',
      data: punti.map((p: any) => ({
        x: toMs(p.t),
        y: p.y,
      })),
    })
  }

  // Serie opzionale Velocità Linea 4.0 su asse Y secondario
  const serie40 = telemetriaData.value.processo_40?.serie
  if (showSpeedSeries.value && serie40 && serie40.length > 0) {
    const minMs = toMs(punti[0].t)
    const maxMs = toMs(punti[punti.length - 1].t)
    const speedPts = serie40
      .filter((s: any) => {
        if (s.v === null || s.v === undefined) return false
        const ms = toMs(s.t)
        return ms >= minMs && ms <= maxMs
      })
      .map((s: any) => ({
        x: toMs(s.t),
        y: s.v,
      }))

    if (speedPts.length > 0) {
      series.push({
        name: t('Hub.SeriesSpeed'),
        type: 'line',
        isSpeed: true,
        data: speedPts,
      })
    }
  }

  return series
})

const apexOptions = computed(() => {
  const dark = isDark.value
  const kpi = activeKpi.value || telemetriaData.value?.kpi
  const yAnnotations: any[] = []

  const textColor = dark ? '#cfcde4' : '#4b465c'
  const mutedTextColor = dark ? '#a8a6b9' : '#6f6b7d'
  const borderColor = dark ? 'rgba(255, 255, 255, 0.15)' : 'rgba(0, 0, 0, 0.15)'
  const gridLineColor = dark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.08)'

  if (kpi?.target) {
    yAnnotations.push({
      y: kpi.target,
      borderColor: '#28c76f',
      borderWidth: 2,
      strokeDashArray: 0,
      label: {
        borderColor: '#28c76f',
        style: {
          color: '#ffffff',
          background: '#28c76f',
          fontSize: '12px',
          fontWeight: 600,
          padding: { left: 8, right: 8, top: 4, bottom: 4 },
        },
        text: `Target: ${kpi.target} mm`,
      },
    })

    if (kpi.tolleranza) {
      yAnnotations.push({
        y: round3(kpi.target + kpi.tolleranza),
        borderColor: '#ea5455',
        borderWidth: 1.5,
        strokeDashArray: 4,
        label: {
          borderColor: '#ea5455',
          style: {
            color: '#ffffff',
            background: '#ea5455',
            fontSize: '12px',
            fontWeight: 600,
            padding: { left: 8, right: 8, top: 4, bottom: 4 },
          },
          text: `Max: ${round3(kpi.target + kpi.tolleranza)} mm`,
        },
      })
      yAnnotations.push({
        y: round3(kpi.target - kpi.tolleranza),
        borderColor: '#ea5455',
        borderWidth: 1.5,
        strokeDashArray: 4,
        label: {
          borderColor: '#ea5455',
          style: {
            color: '#ffffff',
            background: '#ea5455',
            fontSize: '12px',
            fontWeight: 600,
            padding: { left: 8, right: 8, top: 4, bottom: 4 },
          },
          text: `Min: ${round3(kpi.target - kpi.tolleranza)} mm`,
        },
      })
    }
  }

  // Costruzione assi Y in ApexCharts:
  // In presenza di più serie, per ogni serie nell'array series ci deve essere una corrispondente configurazione yaxis
  // (oppure le serie che condividono lo stesso asse devono avere lo stesso seriesName o show: false sui duplicati).
  const currentSeries = apexSeries.value
  const hasSpeed = currentSeries.some((s: any) => s.isSpeed)

  let yAxes: any[] = []
  if (!hasSpeed) {
    yAxes = [
      {
        decimalsInFloat: 3,
        axisBorder: {
          show: true,
          color: borderColor,
        },
        axisTicks: {
          show: true,
          color: borderColor,
        },
        labels: {
          formatter: (val: number) => (val ? val.toFixed(3) : ''),
          style: {
            colors: mutedTextColor,
            fontSize: '12px',
            fontWeight: 500,
          },
        },
        title: {
          text: t('Hub.SeriesDiameter'),
          style: {
            color: textColor,
            fontSize: '13px',
            fontWeight: 600,
          },
        },
      },
    ]
  } else {
    // Mappa 1:1 per ciascuna serie di apexSeries
    yAxes = currentSeries.map((s: any, idx: number) => {
      const isSpeed = s.isSpeed
      if (isSpeed) {
        return {
          seriesName: s.name,
          opposite: true,
          decimalsInFloat: 1,
          axisBorder: {
            show: true,
            color: '#28c76f',
          },
          axisTicks: {
            show: true,
            color: '#28c76f',
          },
          labels: {
            formatter: (val: number) => (val ? `${val.toFixed(1)} m/min` : ''),
            style: {
              colors: '#28c76f',
              fontSize: '12px',
              fontWeight: 600,
            },
          },
          title: {
            text: t('Hub.SeriesSpeed'),
            style: {
              color: '#28c76f',
              fontSize: '13px',
              fontWeight: 600,
            },
          },
        }
      }

      // È una serie di diametro (Diametro, Diametro X, Diametro Y)
      const isFirstDiameter = idx === 0
      return {
        seriesName: t('Hub.SeriesDiameter'),
        show: isFirstDiameter,
        decimalsInFloat: 3,
        axisBorder: {
          show: isFirstDiameter,
          color: borderColor,
        },
        axisTicks: {
          show: isFirstDiameter,
          color: borderColor,
        },
        labels: {
          formatter: (val: number) => (val ? val.toFixed(3) : ''),
          style: {
            colors: mutedTextColor,
            fontSize: '12px',
            fontWeight: 500,
          },
        },
        title: {
          text: isFirstDiameter ? t('Hub.SeriesDiameter') : '',
          style: {
            color: textColor,
            fontSize: '13px',
            fontWeight: 600,
          },
        },
      }
    })
  }

  return {
    chart: {
      type: 'line',
      height: 420,
      zoom: {
        enabled: true,
        type: 'x',
        autoScaleYaxis: true,
      },
      toolbar: {
        show: true,
        tools: {
          download: true,
          selection: true,
          zoom: true,
          zoomin: true,
          zoomout: true,
          pan: true,
          reset: true,
        },
      },
      animations: {
        enabled: false,
      },
    },
    theme: {
      mode: dark ? 'dark' : 'light',
    },
    colors: ['#00cfe8', '#ff9f43', '#a855f7', '#28c76f'],
    stroke: {
      width: [3, 2, 2, 2.5],
      curve: 'straight',
      dashArray: [0, 4, 4, 0],
    },
    xaxis: {
      type: 'datetime',
      axisBorder: {
        show: true,
        color: borderColor,
      },
      axisTicks: {
        show: true,
        color: borderColor,
      },
      labels: {
        datetimeUTC: false,
        format: 'HH:mm:ss',
        style: {
          colors: mutedTextColor,
          fontSize: '12px',
          fontWeight: 500,
        },
      },
      title: {
        text: 'Orario Lavorazione',
        style: {
          color: textColor,
          fontSize: '13px',
          fontWeight: 600,
        },
      },
    },
    yaxis: yAxes,
    tooltip: {
      theme: dark ? 'dark' : 'light',
      x: {
        format: 'dd/MM/yyyy HH:mm:ss',
      },
      y: {
        formatter: (val: number, opts: any) => {
          if (!val) return '-'
          const seriesIdx = opts?.seriesIndex ?? 0
          const sName = apexSeries.value[seriesIdx]?.name ?? ''
          if (sName.includes('Velocità'))
            return `${val.toFixed(2)} m/min`
          return `${val.toFixed(3)} mm`
        },
      },
    },
    annotations: {
      yaxis: yAnnotations,
    },
    legend: {
      position: 'top',
      horizontalAlign: 'left',
      offsetX: 10,
      offsetY: 0,
      fontSize: '13px',
      fontWeight: 600,
      labels: {
        colors: textColor,
      },
      markers: {
        width: 10,
        height: 10,
        radius: 12,
      },
      itemMargin: {
        horizontal: 14,
        vertical: 4,
      },
    },
    grid: {
      borderColor: gridLineColor,
      strokeDashArray: 3,
      xaxis: {
        lines: {
          show: true,
        },
      },
      yaxis: {
        lines: {
          show: true,
        },
      },
    },
  }
})

// Funzione di ricerca rapida
const executeSearch = async (codeToSearch?: string) => {
  const code = (codeToSearch ?? searchInput.value).trim()
  if (!code)
    return

  searchCode.value = code
  searchInput.value = code
  isSearching.value = true
  hasSearched.value = true

  // Aggiorna URL senza ricaricare la pagina
  router.replace({ query: { code } })

  try {
    // Eseguiamo summary e caricamento tab attivo in parallelo per dimezzare i tempi di attesa
    const [summaryRes] = await Promise.all([
      useApi<any>(createUrl('/production-hub/summary', {
        query: { code },
      })),
      loadCurrentTabData(),
    ])

    const { data } = summaryRes

    if (data.value && data.value.found) {
      summaryData.value = data.value

      if (data.value.is_product_search) {
        lastProductSearch.value = data.value.code
      }
    }
    else {
      summaryData.value = null
    }
  }
  catch (error) {
    console.error('Errore durante la ricerca nel Fascicolo 4.0:', error)
    summaryData.value = null
  }
  finally {
    isSearching.value = false
  }
}

// Reset quando si cerca un nuovo codice
const resetTabData = () => {
  scartiDialog.value = false
  scartiDialogItem.value = null
  consumiDialog.value = false
  consumiDialogItem.value = null
  fermiDialog.value = false
  strisciateList.value = []
  strutturaProduzione.value = []
  strisciateKpi.value = null
  fabbisogniList.value = []
  giacenzeList.value = []
  giacenzeKpi.value = null
  qualitaLoaded.value = false
  documentiLoaded.value = false
  spedizioniLoaded.value = false
  qualitaData.value = {
    non_conformita: [],
    checker_reports: [],
    cpr_tests: [],
    type_tests: [],
    fai_tests: [],
  }
  documentiData.value = { documents: [], drive_folders: [] }
  spedizioniData.value = { ddt: [], righe_spedite: [] }
  telemetriaLoaded.value = false
  telemetriaSelectedStrisciata.value = null
  telemetriaData.value = null
  telemetriaTimeRange.value = [0, 0]
}

const onTabChange = (newTab: string) => {
  currentTab.value = newTab
  loadCurrentTabData()
}

// Autocomplete rapido
let debounceTimeout: any = null

const onInputSearch = (val: string) => {
  clearTimeout(debounceTimeout)
  if (!val || val.length < 2) {
    searchSuggestions.value = []

    return
  }
  debounceTimeout = setTimeout(async () => {
    isSuggesting.value = true
    try {
      const { data } = await useApi<any>(createUrl('/production-hub/autocomplete', {
        query: { q: val },
      }))

      searchSuggestions.value = data.value ?? []
    }
    finally {
      isSuggesting.value = false
    }
  }, 300)
}

const selectSuggestion = (item: any) => {
  searchInput.value = item.code
  searchSuggestions.value = []
  resetTabData()
  executeSearch(item.code)
}

const openOrderFromProduct = (order: any) => {
  const targetCode = order.commessa || order.ordine
  if (!targetCode)
    return
  resetTabData()
  executeSearch(targetCode)
}

const backToProductSearch = () => {
  if (!lastProductSearch.value)
    return
  resetTabData()
  executeSearch(lastProductSearch.value)
}

const openDrive = (driveId: string) => {
  if (!driveId)
    return
  window.open(`https://drive.google.com/drive/folders/${driveId}`, '_blank')
}

const driveFolderUrl = (id: string) => `https://drive.google.com/drive/folders/${id}`
const driveFileUrl = (id: string) => `https://drive.google.com/file/d/${id}/view`

// Normalizza un codice OL (rimuove zeri iniziali) per i confronti
const normCode = (v: any) => (v ?? '').toString().trim().replace(/^0+(?=\d)/, '')

// OL di produzione della singola strisciata (campo ol_produzione dal backend; fallback NumeroOrdineAcquisto/NRigaOrd)
const strisciateOl = (item: any) => {
  const ol = (item.ol_produzione ?? '').toString().trim()
  if (ol && ol !== '-')
    return ol

  const ord = (item.ordine ?? '').toString().trim()
  if (ord)
    return ord

  const riga = normCode(item.riga_ord)
  if (riga)
    return riga

  return '-'
}

// L'OL cercato coincide con l'OL di questa strisciata/voce di struttura?
const isSearchedOl = (ol: any) => {
  const searched = normCode(searchCode.value)

  return searched !== '' && normCode(ol) === searched
}

// Colore chip per l'esito delle prove (POSITIVO/NEGATIVO)
const esitoColor = (esito: any) => {
  const e = (esito ?? '').toString().toUpperCase()
  if (e === 'POSITIVO')
    return 'success'
  if (e === 'NEGATIVO')
    return 'error'

  return 'default'
}

// Caricamento iniziale se presente query param `code`
onMounted(() => {
  const codeParam = route.query.code as string
  if (codeParam) {
    searchInput.value = codeParam
    executeSearch(codeParam)
  }
})
</script>

<template>
  <div class="production-hub-container">
    <!-- Header e Titolo -->
    <VRow class="mb-2">
      <VCol
        cols="12"
        class="d-flex align-center justify-space-between flex-wrap gap-4"
      >
        <div>
          <h2 class="text-h4 font-weight-bold d-flex align-center gap-2">
            <VIcon
              icon="tabler-topology-star-3"
              color="primary"
              size="32"
            />
            {{ t('Hub.Title') }}
          </h2>
          <p class="text-body-1 text-medium-emphasis mb-0">
            {{ t('Hub.Subtitle') }}
          </p>
        </div>
      </VCol>
    </VRow>

    <!-- Barra di Ricerca Unificata -->
    <VCard
      class="mb-6 elevation-2"
      rounded="lg"
    >
      <VCardText class="pa-4">
        <VRow align="center">
          <VCol
            cols="12"
            md="8"
            lg="9"
            class="position-relative"
          >
            <VTextField
              v-model="searchInput"
              :label="t('Hub.SearchLabel')"
              placeholder="Es. 2400123, 9400551, C24-001..."
              prepend-inner-icon="tabler-search"
              clearable
              variant="outlined"
              density="comfortable"
              hide-details
              @keyup.enter="() => { resetTabData(); executeSearch(); }"
              @update:model-value="onInputSearch"
            />

            <!-- Dropdown suggerimenti -->
            <div
              v-if="searchSuggestions.length > 0"
              class="search-suggestions-dropdown elevation-4 bg-surface rounded-lg mt-1"
            >
              <div
                v-for="item in searchSuggestions"
                :key="item.code"
                class="suggestion-item d-flex align-center justify-space-between pa-3 cursor-pointer"
                @click="selectSuggestion(item)"
              >
                <div class="d-flex align-center gap-2">
                  <VIcon
                    :icon="item.type === 'Prodotto' ? 'tabler-package' : (item.type === 'Commessa' ? 'tabler-briefcase' : 'tabler-file-analytics')"
                    size="20"
                    :color="item.type === 'Prodotto' ? 'secondary' : 'primary'"
                  />
                  <div>
                    <span class="font-weight-bold">{{ item.code }}</span>
                    <span
                      v-if="item.commessa"
                      class="text-caption text-primary ms-2 font-weight-medium"
                    >(Commessa: {{ item.commessa }})</span>
                    <span
                      v-else-if="item.ol"
                      class="text-caption text-info ms-2 font-weight-medium"
                    >(OL: {{ item.ol }})</span>
                    <span
                      v-if="item.description || item.product"
                      class="text-caption text-medium-emphasis ms-2"
                    >— {{ item.description || item.product }}</span>
                    <span
                      v-if="item.cliente"
                      class="text-caption text-success ms-2 font-weight-medium"
                    >• {{ item.cliente }}</span>
                  </div>
                </div>
                <VChip
                  size="x-small"
                  :color="item.type === 'Prodotto' ? 'secondary' : (item.type === 'Commessa' ? 'warning' : 'info')"
                  variant="tonal"
                >
                  {{ item.type }}
                </VChip>
              </div>
            </div>
          </VCol>

          <VCol
            cols="12"
            md="4"
            lg="3"
            class="d-flex gap-2"
          >
            <VBtn
              color="primary"
              variant="elevated"
              class="flex-grow-1"
              prepend-icon="tabler-scan"
              :loading="isSearching"
              @click="() => { resetTabData(); executeSearch(); }"
            >
              {{ t('Hub.FetchData') }}
            </VBtn>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <!-- Risultati Ricerca -->
    <!-- CASO 1: RICERCA PER CODICE PRODOTTO (Elenco Ordini / Clienti) -->
    <div v-if="summaryData && summaryData.found && summaryData.is_product_search">
      <VCard
        class="mb-6 elevation-2"
        rounded="lg"
      >
        <VCardItem class="pa-4 pb-3 border-b">
          <div class="d-flex align-center justify-space-between flex-wrap gap-4">
            <div class="d-flex align-center gap-3">
              <VAvatar
                color="secondary"
                variant="tonal"
                size="48"
              >
                <VIcon
                  icon="tabler-package"
                  size="28"
                />
              </VAvatar>
              <div>
                <div class="d-flex align-center flex-wrap gap-2">
                  <h3 class="text-h5 font-weight-bold mb-0">
                    {{ summaryData.product_info?.cd_prodotto || summaryData.code }}
                  </h3>
                  <VChip
                    size="small"
                    color="secondary"
                    variant="tonal"
                    class="font-weight-medium"
                  >
                    <VIcon
                      icon="tabler-list-numbers"
                      size="14"
                      class="me-1"
                    />
                    {{ t('Hub.ProductSearchOrdersFound', { n: summaryData.orders?.length ?? 0 }) }}
                  </VChip>
                </div>
                <p
                  v-if="summaryData.product_info?.descrizione"
                  class="text-subtitle-2 text-medium-emphasis mb-0 mt-1"
                >
                  {{ summaryData.product_info.descrizione }}
                </p>
              </div>
            </div>
          </div>
        </VCardItem>

        <VCardText class="pa-0">
          <VTable
            v-if="summaryData.orders?.length > 0"
            hover
            density="comfortable"
          >
            <thead>
              <tr>
                <th>{{ t('Hub.ColCommessa') }}</th>
                <th>{{ t('Hub.ColOl') }}</th>
                <th>{{ t('Hub.ColCustomer') }}</th>
                <th>{{ t('Hub.ColOrderedQty') }}</th>
                <th>{{ t('Hub.ColStock') }}</th>
                <th>{{ t('Hub.ColOrderDate') }}</th>
                <th>{{ t('Hub.ColWfStatus') }}</th>
                <th class="text-end pe-4">
                  {{ t('Hub.ColActions') }}
                </th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(ord, idx) in summaryData.orders"
                :key="idx"
                class="cursor-pointer"
                @click="openOrderFromProduct(ord)"
              >
                <td class="font-weight-bold text-primary">
                  <div class="d-flex align-center gap-1">
                    <VIcon
                      icon="tabler-topology-star-3"
                      size="16"
                      color="primary"
                    />
                    {{ ord.commessa || '-' }}
                  </div>
                </td>
                <td class="font-weight-medium">
                  {{ ord.ordine || '-' }}
                </td>
                <td class="text-high-emphasis font-weight-medium">
                  {{ ord.cliente || '-' }}
                </td>
                <td>
                  <strong>{{ ord.quantita_ordinata ? Number(ord.quantita_ordinata).toLocaleString() : '-' }}</strong>
                  <span class="text-caption text-medium-emphasis ms-1">{{ ord.um || '' }}</span>
                </td>
                <td>
                  <template v-if="ord.giacenza !== null && ord.giacenza !== undefined">
                    <strong :class="ord.giacenza > 0 ? 'text-success' : 'text-medium-emphasis'">
                      {{ Number(ord.giacenza).toLocaleString() }}
                    </strong>
                    <span class="text-caption text-medium-emphasis ms-1">{{ ord.giacenza_um || '' }}</span>
                  </template>
                  <span
                    v-else
                    class="text-medium-emphasis text-caption"
                  >-</span>
                </td>
                <td>
                  {{ ord.data_ordine ? moment(ord.data_ordine).format('DD/MM/YYYY') : '-' }}
                </td>
                <td>
                  <VChip
                    v-if="ord.wf_stato"
                    size="x-small"
                    :color="ord.wf_stato === 'Approved' ? 'success' : (ord.wf_stato === 'In-Approval' ? 'warning' : 'default')"
                    variant="flat"
                  >
                    {{ ord.wf_stato }}
                  </VChip>
                  <span
                    v-else
                    class="text-medium-emphasis text-caption"
                  >-</span>
                </td>
                <td class="text-end pe-4">
                  <VBtn
                    size="small"
                    color="primary"
                    variant="tonal"
                    prepend-icon="tabler-scan"
                    @click.stop="openOrderFromProduct(ord)"
                  >
                    {{ t('Hub.OpenOrderDossier') }}
                  </VBtn>
                </td>
              </tr>
            </tbody>
          </VTable>

          <div
            v-else
            class="text-center pa-8 text-medium-emphasis"
          >
            {{ t('Hub.NoOrdersForProduct') }}
          </div>
        </VCardText>
      </VCard>
    </div>

    <!-- CASO 2: FASCICOLO 4.0 ORDINE SINGOLO -->
    <div v-else-if="summaryData && summaryData.found">
      <!-- Pulsante Torna all'elenco ordini per prodotto (se si proviene da ricerca articolo) -->
      <div
        v-if="lastProductSearch"
        class="mb-3"
      >
        <VBtn
          variant="tonal"
          size="small"
          color="secondary"
          prepend-icon="tabler-arrow-left"
          @click="backToProductSearch"
        >
          {{ t('Hub.BackToProductList', { product: lastProductSearch }) }}
        </VBtn>
      </div>

      <!-- Scheda Anagrafica Ordine & Badge Rapidi -->
      <VCard
        class="mb-6 elevation-2"
        rounded="lg"
      >
        <VCardItem class="pa-4 pb-2 border-b">
          <div class="d-flex align-center justify-space-between flex-wrap gap-4">
            <div class="d-flex align-center gap-3">
              <VAvatar
                color="primary"
                variant="tonal"
                size="48"
              >
                <VIcon
                  icon="tabler-barcode"
                  size="28"
                />
              </VAvatar>
              <div>
                <div class="d-flex align-center flex-wrap gap-2">
                  <h3 class="text-h5 font-weight-bold mb-0">
                    <span v-if="summaryData.order_info?.ordine">OL: {{ summaryData.order_info.ordine }}</span>
                    <span v-else>{{ t('Hub.Code') }}: {{ summaryData.code }}</span>
                  </h3>
                  <VChip
                    v-if="summaryData.order_info?.commessa"
                    size="small"
                    color="primary"
                    variant="tonal"
                    class="font-weight-medium"
                  >
                    <VIcon
                      icon="tabler-topology-star-3"
                      size="14"
                      class="me-1"
                    />
                    {{ t('Hub.JobOrder') }}: {{ summaryData.order_info.commessa }}
                  </VChip>
                  <VChip
                    v-if="summaryData.order_info?.wf_stato"
                    size="small"
                    :color="summaryData.order_info.wf_stato === 'Approved' ? 'success' : 'warning'"
                    variant="flat"
                  >
                    {{ t('Hub.WfStatus') }}: {{ summaryData.order_info.wf_stato }}
                  </VChip>
                  <VChip
                    v-if="summaryData.order_info?.giacenze_lotti?.lotti?.length"
                    size="small"
                    color="purple"
                    variant="tonal"
                    class="cursor-pointer"
                    :title="t('Hub.TabGiacenze')"
                    @click="onTabChange('giacenze')"
                  >
                    <VIcon
                      icon="tabler-packages"
                      size="14"
                      class="me-1"
                    />
                    <span v-if="summaryData.order_info.giacenze_lotti.lotti.length === 1">
                      {{ t('Hub.LotStock') }} {{ summaryData.order_info.giacenze_lotti.lotti[0].lotto }}:
                      {{ Number(summaryData.order_info.giacenze_lotti.lotti[0].giacenza).toLocaleString() }}
                      {{ summaryData.order_info.giacenze_lotti.lotti[0].um }}
                    </span>
                    <span v-else>
                      {{ t('Hub.LotStockTotal', { n: summaryData.order_info.giacenze_lotti.lotti.length }) }}:
                      {{ Number(summaryData.order_info.giacenze_lotti.totale).toLocaleString() }}
                      {{ summaryData.order_info.giacenze_lotti.um }}
                    </span>
                  </VChip>
                </div>
                <p class="text-caption text-medium-emphasis mb-0 mt-1">
                  {{ t('Hub.Product') }}:
                  <strong
                    class="text-high-emphasis cursor-pointer text-decoration-underline"
                    :title="t('Hub.ProductSearchTitle')"
                    @click="summaryData.order_info?.prodotto ? executeSearch(summaryData.order_info.prodotto) : null"
                  >
                    {{ summaryData.order_info?.prodotto || 'N/D' }}
                  </strong>
                  <span v-if="summaryData.order_info?.descrizione"> - {{ summaryData.order_info.descrizione }}</span>
                </p>
              </div>
            </div>

            <!-- Pulsante Cartella Drive Commessa se presente -->
            <div v-if="summaryData.order_info?.folder_drive">
              <VBtn
                variant="outlined"
                color="secondary"
                size="small"
                prepend-icon="tabler-brand-google-drive"
                @click="openDrive(summaryData.order_info.folder_drive)"
              >
                {{ t('Hub.DriveFolder') }}
              </VBtn>
            </div>
          </div>
        </VCardItem>

        <VCardText class="pa-4">
          <VRow>
            <!-- Dati anagrafici -->
            <VCol
              cols="12"
              md="6"
              lg="3"
            >
              <div class="text-caption text-medium-emphasis">
                {{ t('Hub.OrderedQty') }}
              </div>
              <div class="text-h6 font-weight-bold">
                {{ summaryData.order_info?.quantita_ordinata ? Number(summaryData.order_info.quantita_ordinata).toLocaleString() : '-' }}
                <span class="text-caption">{{ summaryData.order_info?.um || '' }}</span>
              </div>
            </VCol>

            <VCol
              cols="12"
              md="6"
              lg="3"
            >
              <div class="text-caption text-medium-emphasis">
                {{ t('Hub.ProducedQty') }}
              </div>
              <div class="text-h6 font-weight-bold text-primary">
                {{ Number(summaryData.kpi?.quantita_prodotta_totale ?? 0).toLocaleString() }}
                <span class="text-caption">{{ summaryData.order_info?.um || '' }}</span>
              </div>
            </VCol>

            <VCol
              cols="12"
              md="6"
              lg="3"
            >
              <div class="text-caption text-medium-emphasis">
                {{ t('Hub.Customer') }}
              </div>
              <div class="text-body-1 font-weight-medium">
                {{ summaryData.order_info?.cliente || t('Hub.NotSpecified') }}
              </div>
            </VCol>

            <VCol
              cols="12"
              md="6"
              lg="3"
            >
              <div class="text-caption text-medium-emphasis">
                {{ t('Hub.OrderDate') }}
              </div>
              <div class="text-body-1 font-weight-medium">
                {{ summaryData.order_info?.data_ordine ? moment(summaryData.order_info.data_ordine).format('DD/MM/YYYY') : '-' }}
              </div>
            </VCol>

            <!-- Barra avanzamento produzione -->
            <VCol
              cols="12"
              class="pt-2"
            >
              <div class="d-flex align-center justify-space-between mb-1">
                <span class="text-caption font-weight-bold">{{ t('Hub.ProdProgress') }}</span>
                <span class="text-caption font-weight-bold">{{ avanzamentoPerc }}%</span>
              </div>
              <VProgressLinear
                :model-value="avanzamentoPerc"
                height="10"
                rounded
                :color="avanzamentoPerc >= 100 ? 'success' : 'primary'"
                striped
              />
            </VCol>
          </VRow>
        </VCardText>
      </VCard>

      <!-- KPI Summary Cards -->
      <VRow class="mb-6">
        <VCol
          cols="6"
          sm="4"
          lg="2"
        >
          <VCard
            variant="tonal"
            color="primary"
            class="text-center pa-3"
          >
            <div class="text-h4 font-weight-bold">
              {{ summaryData.kpi?.strisciate_count ?? 0 }}
            </div>
            <div class="text-caption text-medium-emphasis font-weight-medium mt-1">
              {{ t('Hub.GpRuns') }}
            </div>
          </VCard>
        </VCol>

        <VCol
          cols="6"
          sm="4"
          lg="2"
        >
          <VCard
            variant="tonal"
            color="info"
            class="text-center pa-3"
          >
            <div class="text-h4 font-weight-bold">
              {{ summaryData.kpi?.fabbisogni_count ?? 0 }}
            </div>
            <div class="text-caption text-medium-emphasis font-weight-medium mt-1">
              {{ t('Hub.MaterialComp') }}
            </div>
          </VCard>
        </VCol>

        <VCol
          cols="6"
          sm="4"
          lg="2"
        >
          <VCard
            variant="tonal"
            :color="(summaryData.kpi?.nc_aperte ?? 0) > 0 ? 'error' : 'warning'"
            class="text-center pa-3"
          >
            <div class="text-h4 font-weight-bold">
              {{ summaryData.kpi?.nc_totali ?? 0 }}
              <span
                v-if="(summaryData.kpi?.nc_aperte ?? 0) > 0"
                class="text-caption text-error font-weight-bold"
              >
                ({{ summaryData.kpi?.nc_aperte }} {{ t('Hub.Open') }})
              </span>
            </div>
            <div class="text-caption text-medium-emphasis font-weight-medium mt-1">
              {{ t('Hub.NonConformities') }}
            </div>
          </VCard>
        </VCol>

        <VCol
          cols="6"
          sm="4"
          lg="2"
        >
          <VCard
            variant="tonal"
            color="teal"
            class="text-center pa-3"
          >
            <div class="text-h4 font-weight-bold">
              {{ summaryData.kpi?.prove_qualita_count ?? 0 }}
            </div>
            <div class="text-caption text-medium-emphasis font-weight-medium mt-1">
              {{ t('Hub.QualityTests') }}
            </div>
          </VCard>
        </VCol>

        <VCol
          cols="6"
          sm="4"
          lg="2"
        >
          <VCard
            variant="tonal"
            color="secondary"
            class="text-center pa-3"
          >
            <div class="text-h4 font-weight-bold">
              {{ summaryData.kpi?.documenti_count ?? 0 }}
            </div>
            <div class="text-caption text-medium-emphasis font-weight-medium mt-1">
              {{ t('Hub.Documents') }}
            </div>
          </VCard>
        </VCol>

        <VCol
          cols="6"
          sm="4"
          lg="2"
        >
          <VCard
            variant="tonal"
            color="success"
            class="text-center pa-3"
          >
            <div class="text-h4 font-weight-bold">
              {{ summaryData.kpi?.spedizioni_count ?? 0 }}
            </div>
            <div class="text-caption text-medium-emphasis font-weight-medium mt-1">
              {{ t('Hub.ShipmentsDdt') }}
            </div>
          </VCard>
        </VCol>
      </VRow>

      <!-- Navigazione a Tabs -->
      <VTabs
        :model-value="currentTab"
        class="v-tabs-pill mb-4"
        grow
        @update:model-value="onTabChange"
      >
        <VTab
          v-for="tabItem in tabs"
          :key="tabItem.key"
          :value="tabItem.key"
          class="font-weight-medium"
        >
          <VIcon
            :icon="tabItem.icon"
            size="18"
            class="me-2"
          />
          {{ tabItem.title }}
          <VChip
            size="x-small"
            class="ms-2"
            :color="tabItem.badgeAlert ? 'error' : (tabItem.count > 0 ? tabItem.color : 'default')"
            variant="tonal"
          >
            {{ tabItem.count }}
          </VChip>
        </VTab>
      </VTabs>

      <!-- Finestra Contenuto Tabs -->
      <VWindow
        :model-value="currentTab"
        class="disable-tab-transition"
      >
        <!-- TAB 1: STRISCIATE PRODUZIONE -->
        <VWindowItem value="strisciate">
          <VCard
            class="elevation-2"
            rounded="lg"
          >
            <VCardItem class="pa-4 border-b">
              <div class="d-flex align-center justify-space-between">
                <div class="d-flex align-center gap-2">
                  <VIcon
                    icon="tabler-cpu"
                    color="primary"
                    size="22"
                  />
                  <span class="text-h6 font-weight-bold">{{ t('Hub.StrisciateTitle') }}</span>
                </div>
                <VChip
                  size="small"
                  color="primary"
                  variant="tonal"
                >
                  {{ t('Hub.TotalRecords', { n: strisciateList.length }) }}
                </VChip>
              </div>
            </VCardItem>

            <VCardText
              v-if="strutturaProduzione.length > 0"
              class="d-flex flex-wrap align-center gap-2 py-3"
            >
              <span class="text-caption font-weight-medium text-medium-emphasis">
                {{ t('Hub.ProdStructure') }} (OrderLink {{ strutturaOrdinata[0]?.order_link }}):
              </span>
              <VChip
                v-for="s in strutturaOrdinata"
                :key="s.ol"
                size="small"
                :color="isSearchedOl(s.ol) ? 'success' : 'default'"
                :variant="isSearchedOl(s.ol) ? 'flat' : 'tonal'"
              >
                {{ s.order_seq }} · OL {{ s.ol }}
                <span
                  v-if="s.prodotto"
                  class="ms-1 text-medium-emphasis"
                >
                  {{ s.prodotto }}
                </span>
              </VChip>
            </VCardText>

            <!-- KPI Cards Efficienza & OEE in testa al tab -->
            <VCardText
              v-if="strisciateKpi && strisciateList.length > 0"
              class="border-b bg-var-theme-background pa-4"
            >
              <VRow dense>
                <!-- Card 1: OEE Semplificato -->
                <VCol
                  cols="12"
                  sm="6"
                  md="2"
                >
                  <VCard
                    variant="tonal"
                    :color="strisciateKpi.oee_perc === null ? 'default' : (strisciateKpi.oee_perc >= 85 ? 'success' : (strisciateKpi.oee_perc >= 70 ? 'warning' : 'error'))"
                    class="h-100"
                  >
                    <VTooltip
                      activator="parent"
                      location="top"
                      max-width="380"
                    >
                      <div class="font-weight-medium">
                        {{ t('Hub.KpiOeeFormula', {
                          avail: strisciateKpi.disponibilita_perc ?? 'N/D',
                          resa: strisciateKpi.resa_qualita_perc ?? 'N/D',
                        }) }}
                      </div>
                      <div class="text-caption">
                        {{ t('Hub.KpiOeeFormulaNote') }}
                      </div>
                    </VTooltip>
                    <VCardText class="pa-3">
                      <div class="d-flex align-center justify-space-between mb-1">
                        <span class="text-caption font-weight-medium text-medium-emphasis">
                          {{ t('Hub.KpiOee') }}
                        </span>
                        <VIcon
                          icon="tabler-gauge"
                          size="18"
                        />
                      </div>
                      <div class="text-h5 font-weight-bold">
                        {{ strisciateKpi.oee_perc !== null ? `${strisciateKpi.oee_perc}%` : 'N/D' }}
                      </div>
                      <div class="text-caption text-medium-emphasis text-truncate mt-1">
                        {{ t('Hub.KpiOeeHint') }}
                      </div>
                    </VCardText>
                  </VCard>
                </VCol>

                <!-- Card 2: Disponibilità Impianto (Lavoro vs Fermi) -->
                <VCol
                  cols="12"
                  sm="6"
                  md="2"
                >
                  <VCard
                    variant="tonal"
                    :color="strisciateKpi.disponibilita_perc === null ? 'default' : (strisciateKpi.disponibilita_perc >= 90 ? 'success' : (strisciateKpi.disponibilita_perc >= 75 ? 'warning' : 'error'))"
                    class="h-100"
                  >
                    <VTooltip
                      activator="parent"
                      location="top"
                      max-width="380"
                    >
                      {{ t('Hub.KpiDisponibilitaFormula', {
                        work: strisciateKpi.tempo_lavoro_minuti,
                        down: strisciateKpi.tempo_fermo_minuti,
                      }) }}
                    </VTooltip>
                    <VCardText class="pa-3">
                      <div class="d-flex align-center justify-space-between mb-1">
                        <span class="text-caption font-weight-medium text-medium-emphasis">
                          {{ t('Hub.KpiDisponibilita') }}
                        </span>
                        <VIcon
                          icon="tabler-clock-play"
                          size="18"
                        />
                      </div>
                      <div class="text-h5 font-weight-bold">
                        {{ strisciateKpi.disponibilita_perc !== null ? `${strisciateKpi.disponibilita_perc}%` : 'N/D' }}
                      </div>
                      <div class="text-caption text-medium-emphasis text-truncate mt-1">
                        {{ t('Hub.KpiDisponibilitaHint', {
                          work: (strisciateKpi.tempo_lavoro_minuti / 60).toFixed(1),
                          total: ((strisciateKpi.tempo_lavoro_minuti + strisciateKpi.tempo_fermo_minuti) / 60).toFixed(1),
                        }) }}
                      </div>
                    </VCardText>
                  </VCard>
                </VCol>

                <!-- Card 3: Resa Qualitativa (Yield) -->
                <VCol
                  cols="12"
                  sm="6"
                  md="3"
                >
                  <VCard
                    variant="tonal"
                    :color="strisciateKpi.resa_qualita_perc === null ? 'default' : (strisciateKpi.resa_qualita_perc >= 98 ? 'success' : (strisciateKpi.resa_qualita_perc >= 95 ? 'warning' : 'error'))"
                    class="h-100"
                  >
                    <VTooltip
                      activator="parent"
                      location="top"
                      max-width="380"
                    >
                      {{ t('Hub.KpiResaFormula', {
                        good: Number(strisciateKpi.quantita_buona).toLocaleString('it-IT', { maximumFractionDigits: 2 }),
                        scrap: Number(strisciateKpi.scarto_totale_qta).toLocaleString('it-IT', { maximumFractionDigits: 3 }),
                      }) }}
                    </VTooltip>
                    <VCardText class="pa-3">
                      <div class="d-flex align-center justify-space-between mb-1">
                        <span class="text-caption font-weight-medium text-medium-emphasis">
                          {{ t('Hub.KpiResa') }}
                        </span>
                        <VIcon
                          icon="tabler-shield-check"
                          size="18"
                        />
                      </div>
                      <div class="text-h5 font-weight-bold">
                        {{ strisciateKpi.resa_qualita_perc !== null ? `${strisciateKpi.resa_qualita_perc}%` : 'N/D' }}
                      </div>
                      <div class="text-caption text-medium-emphasis text-truncate mt-1">
                        {{ t('Hub.KpiResaHint', {
                          scrap: Number(strisciateKpi.scarto_totale_qta).toLocaleString('it-IT', { maximumFractionDigits: 2 }),
                          val: Number(strisciateKpi.scarto_totale_valore).toLocaleString('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 }),
                        }) }}
                      </div>
                    </VCardText>
                  </VCard>
                </VCol>

                <!-- Card 4: Consumi Materiali -->
                <VCol
                  cols="12"
                  sm="6"
                  md="3"
                >
                  <VCard
                    variant="tonal"
                    color="info"
                    class="h-100"
                  >
                    <VTooltip
                      activator="parent"
                      location="top"
                      max-width="380"
                    >
                      {{ t('Hub.KpiConsumiFormula', {
                        qty: Number(strisciateKpi.consumo_totale_qta || 0).toLocaleString('it-IT', { maximumFractionDigits: 3 }),
                        val: Number(strisciateKpi.consumo_totale_valore || 0).toLocaleString('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 }),
                      }) }}
                    </VTooltip>
                    <VCardText class="pa-3">
                      <div class="d-flex align-center justify-space-between mb-1">
                        <span class="text-caption font-weight-medium text-medium-emphasis">
                          {{ t('Hub.KpiConsumi') }}
                        </span>
                        <VIcon
                          icon="tabler-box-seam"
                          size="18"
                        />
                      </div>
                      <div class="text-h5 font-weight-bold">
                        € {{ Number(strisciateKpi.consumo_totale_valore || 0).toLocaleString('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                      </div>
                      <div class="text-caption text-medium-emphasis text-truncate mt-1">
                        {{ t('Hub.KpiConsumiHint', {
                          count: strisciateKpi.consumi_count || 0,
                          val: Number(strisciateKpi.consumo_totale_valore || 0).toLocaleString('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 }),
                        }) }}
                      </div>
                    </VCardText>
                  </VCard>
                </VCol>

                <!-- Card 5: Fermi Macchina (click per aprire dialog dettagli) -->
                <VCol
                  cols="12"
                  sm="6"
                  md="2"
                >
                  <VCard
                    variant="tonal"
                    :color="strisciateKpi.tempo_fermo_minuti === 0 ? 'success' : (strisciateKpi.tempo_fermo_minuti <= 60 ? 'warning' : 'error')"
                    class="h-100 cursor-pointer"
                    @click="strisciateKpi.numero_fermi > 0 ? (fermiDialog = true) : null"
                  >
                    <VTooltip
                      activator="parent"
                      location="top"
                      max-width="380"
                    >
                      {{ t('Hub.KpiFermiFormula') }}
                    </VTooltip>
                    <VCardText class="pa-3">
                      <div class="d-flex align-center justify-space-between mb-1">
                        <span class="text-caption font-weight-medium text-medium-emphasis">
                          {{ t('Hub.KpiFermi') }}
                        </span>
                        <VIcon
                          icon="tabler-alert-circle"
                          size="18"
                        />
                      </div>
                      <div class="text-h5 font-weight-bold">
                        {{ (strisciateKpi.tempo_fermo_minuti / 60).toFixed(1) }}h
                        <span class="text-caption font-weight-regular ms-1">({{ strisciateKpi.tempo_fermo_minuti }}m)</span>
                      </div>
                      <div class="text-caption text-medium-emphasis text-truncate mt-1 d-flex align-center justify-space-between">
                        <span>{{ t('Hub.KpiFermiHint', { n: strisciateKpi.numero_fermi }) }}</span>
                        <VIcon
                          v-if="strisciateKpi.numero_fermi > 0"
                          icon="tabler-chevron-right"
                          size="14"
                        />
                      </div>
                    </VCardText>
                  </VCard>
                </VCol>
              </VRow>
            </VCardText>

            <VCardText class="pa-0">
              <div
                v-if="loadingStrisciate"
                class="text-center pa-6"
              >
                <VProgressCircular
                  indeterminate
                  color="primary"
                />
                <div class="text-caption text-medium-emphasis mt-2">
                  {{ t('Hub.LoadingStrisciate') }}
                </div>
              </div>

              <VTable
                v-else-if="strisciateList.length > 0"
                hover
                density="comfortable"
              >
                <thead>
                  <tr>
                    <th>{{ t('Hub.ColOlProd') }}</th>
                    <th>{{ t('Hub.ColStart') }}</th>
                    <th>{{ t('Hub.ColEnd') }}</th>
                    <th>{{ t('Hub.ColMachine') }}</th>
                    <th>{{ t('Hub.ColMaterial') }}</th>
                    <th class="text-end">
                      {{ t('Hub.ColQty') }}
                    </th>
                    <th>UM</th>
                    <th>{{ t('Hub.ColFibers') }}</th>
                    <th>{{ t('Hub.ColStatus') }}</th>
                    <th>{{ t('Hub.ColConsumptions') }}</th>
                    <th>{{ t('Hub.ColScrap') }}</th>
                    <th>{{ t('Hub.ColPlcDiameters') }}</th>
                    <th>{{ t('Hub.ColSource') }}</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="item in strisciateOrdinate"
                    :key="item.id || item.data_inizio"
                    :class="{ 'strisciata-evidenziata': item.is_ricercato || isSearchedOl(strisciateOl(item)) }"
                  >
                    <td>
                      <VChip
                        size="x-small"
                        color="info"
                        variant="outlined"
                        class="font-weight-bold"
                      >
                        {{ strisciateOl(item) }}
                      </VChip>
                    </td>
                    <td>{{ item.data_inizio ? moment(item.data_inizio).format('DD/MM/YYYY HH:mm') : '-' }}</td>
                    <td>{{ item.data_fine ? moment(item.data_fine).format('DD/MM/YYYY HH:mm') : '-' }}</td>
                    <td>
                      <VChip
                        size="x-small"
                        color="primary"
                        variant="tonal"
                      >
                        {{ item.macchina || 'N/D' }}
                      </VChip>
                    </td>
                    <td>
                      <div class="font-weight-medium">
                        {{ item.prodotto }}
                      </div>
                      <div
                        v-if="item.descrizione"
                        class="text-caption text-medium-emphasis"
                      >
                        {{ item.descrizione }}
                      </div>
                    </td>
                    <td class="text-end font-weight-bold text-high-emphasis">
                      {{ Number(item.quantita).toLocaleString() }}
                    </td>
                    <td>{{ item.um }}</td>
                    <td>{{ item.fibre || '-' }}</td>
                    <td>
                      <VChip
                        size="x-small"
                        :color="item.confermato ? 'success' : 'warning'"
                        variant="flat"
                      >
                        {{ item.confermato ? t('Hub.Confirmed') : t('Hub.Pending') }}
                      </VChip>
                    </td>
                    <td>
                      <VBtn
                        v-if="item.consumi_count > 0"
                        size="x-small"
                        color="info"
                        variant="tonal"
                        prepend-icon="tabler-box-seam"
                        @click="openConsumiDialog(item)"
                      >
                        {{ Number(item.consumo_qta).toLocaleString('it-IT', { maximumFractionDigits: 3 }) }}
                        <span class="text-caption ms-1">({{ item.consumi_count }})</span>
                      </VBtn>
                      <span
                        v-else
                        class="text-caption text-medium-emphasis"
                      >-</span>
                    </td>
                    <td>
                      <VBtn
                        v-if="item.scarti_count > 0"
                        size="x-small"
                        color="error"
                        variant="tonal"
                        prepend-icon="tabler-alert-triangle"
                        @click="openScartiDialog(item)"
                      >
                        {{ Number(item.scarto_qta).toLocaleString('it-IT', { maximumFractionDigits: 3 }) }}
                        <span class="text-caption ms-1">({{ item.scarti_count }})</span>
                      </VBtn>
                      <span
                        v-else
                        class="text-caption text-medium-emphasis"
                      >-</span>
                    </td>
                    <td>
                      <VBtn
                        v-if="item.has_telemetria"
                        size="x-small"
                        color="deep-purple"
                        variant="tonal"
                        prepend-icon="tabler-chart-line"
                        @click="visualizzaTelemetriaStrisciata(item)"
                      >
                        {{ item.has_diametri ? t('Hub.ViewPlc') : t('Hub.View40') }}
                      </VBtn>
                      <span
                        v-else
                        class="text-caption text-medium-emphasis"
                      >-</span>
                    </td>
                    <td>
                      <span class="text-caption text-medium-emphasis">{{ item.fonte }}</span>
                    </td>
                  </tr>
                </tbody>
              </VTable>

              <div
                v-else
                class="text-center pa-6 text-medium-emphasis"
              >
                {{ t('Hub.NoStrisciate') }}
              </div>
            </VCardText>
          </VCard>
        </VWindowItem>

        <!-- TAB 2: FABBISOGNI MATERIALI -->
        <VWindowItem value="fabbisogni">
          <VCard
            class="elevation-2"
            rounded="lg"
          >
            <VCardItem class="pa-4 border-b">
              <div class="d-flex align-center justify-space-between">
                <div class="d-flex align-center gap-2">
                  <VIcon
                    icon="tabler-components"
                    color="info"
                    size="22"
                  />
                  <span class="text-h6 font-weight-bold">{{ t('Hub.FabbisogniTitle') }}</span>
                </div>
                <VChip
                  size="small"
                  color="info"
                  variant="tonal"
                >
                  {{ t('Hub.TotalComponents', { n: fabbisogniList.length }) }}
                </VChip>
              </div>
            </VCardItem>

            <VCardText class="pa-0">
              <div
                v-if="loadingFabbisogni"
                class="text-center pa-6"
              >
                <VProgressCircular
                  indeterminate
                  color="info"
                />
                <div class="text-caption text-medium-emphasis mt-2">
                  {{ t('Hub.LoadingFabbisogni') }}
                </div>
              </div>

              <VTable
                v-else-if="fabbisogniList.length > 0"
                hover
                density="comfortable"
              >
                <thead>
                  <tr>
                    <th>{{ t('Hub.ColProductCode') }}</th>
                    <th>{{ t('Hub.ColDescription') }}</th>
                    <th class="text-end">
                      {{ t('Hub.ColQuantity') }}
                    </th>
                    <th>UM</th>
                    <th>{{ t('Hub.ColFiberCount') }}</th>
                    <th>{{ t('Hub.ColExportDate') }}</th>
                    <th>{{ t('Hub.ColProgressStatus') }}</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="(item, idx) in fabbisogniList"
                    :key="item.id || idx"
                  >
                    <td class="font-weight-bold text-high-emphasis">
                      {{ item.prodotto }}
                    </td>
                    <td>{{ item.descrizione || '-' }}</td>
                    <td class="text-end font-weight-bold">
                      {{ Number(item.quantita).toLocaleString() }}
                    </td>
                    <td>{{ item.um }}</td>
                    <td>{{ item.fibre || '-' }}</td>
                    <td>{{ item.data_esportazione ? moment(item.data_esportazione).format('DD/MM/YYYY HH:mm') : '-' }}</td>
                    <td>
                      <VChip
                        v-if="item.avanzamento"
                        size="x-small"
                        color="primary"
                        variant="tonal"
                      >
                        {{ item.avanzamento }}
                      </VChip>
                      <span
                        v-else
                        class="text-caption text-medium-emphasis"
                      >-</span>
                    </td>
                  </tr>
                </tbody>
              </VTable>

              <div
                v-else
                class="text-center pa-6 text-medium-emphasis"
              >
                {{ t('Hub.NoFabbisogni') }}
              </div>
            </VCardText>
          </VCard>
        </VWindowItem>

        <!-- TAB 3: GIACENZE PER LOTTO -->
        <VWindowItem value="giacenze">
          <VCard
            class="elevation-2"
            rounded="lg"
          >
            <VCardItem class="pa-4 border-b">
              <div class="d-flex align-center justify-space-between flex-wrap gap-2">
                <div class="d-flex align-center gap-2">
                  <VIcon
                    icon="tabler-packages"
                    color="purple"
                    size="22"
                  />
                  <span class="text-h6 font-weight-bold">{{ t('Hub.GiacenzeTitle') }}</span>
                </div>
                <div class="d-flex align-center gap-2">
                  <VChip
                    size="small"
                    color="purple"
                    variant="tonal"
                  >
                    {{ t('Hub.TotalLots', { n: giacenzeKpi?.lotti_count ?? giacenzeList.length }) }}
                  </VChip>
                  <VChip
                    v-if="giacenzeKpi && giacenzeList.length > 0"
                    size="small"
                    color="success"
                    variant="tonal"
                  >
                    {{ t('Hub.TotalStock') }}: {{ Number(giacenzeKpi.giacenza_totale).toLocaleString() }}
                  </VChip>
                </div>
              </div>
            </VCardItem>

            <VCardText class="pa-0">
              <div
                v-if="loadingGiacenze"
                class="text-center pa-6"
              >
                <VProgressCircular
                  indeterminate
                  color="purple"
                />
                <div class="text-caption text-medium-emphasis mt-2">
                  {{ t('Hub.LoadingGiacenze') }}
                </div>
              </div>

              <VTable
                v-else-if="giacenzeList.length > 0"
                hover
                density="comfortable"
              >
                <thead>
                  <tr>
                    <th>{{ t('Hub.ColLot') }}</th>
                    <th>{{ t('Hub.ColProductCode') }}</th>
                    <th>{{ t('Hub.ColDescription') }}</th>
                    <th class="text-end">
                      {{ t('Hub.ColStock') }}
                    </th>
                    <th>UM</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="(item, idx) in giacenzeList"
                    :key="`${item.lotto}-${idx}`"
                  >
                    <td class="font-weight-bold text-high-emphasis">
                      {{ item.lotto }}
                    </td>
                    <td>{{ item.prodotto }}</td>
                    <td>{{ item.descrizione || '-' }}</td>
                    <td class="text-end font-weight-bold">
                      {{ Number(item.giacenza).toLocaleString() }}
                    </td>
                    <td>{{ item.um }}</td>
                  </tr>
                </tbody>
              </VTable>

              <div
                v-else
                class="text-center pa-6 text-medium-emphasis"
              >
                {{ t('Hub.NoGiacenze') }}
              </div>
            </VCardText>
          </VCard>
        </VWindowItem>

        <!-- TAB 4: QUALITÀ & NON CONFORMITÀ -->
        <VWindowItem value="qualita">
          <VCard
            class="elevation-2"
            rounded="lg"
          >
            <VCardItem class="pa-4 border-b">
              <div class="d-flex align-center gap-2">
                <VIcon
                  icon="tabler-certificate"
                  color="warning"
                  size="22"
                />
                <span class="text-h6 font-weight-bold">{{ t('Hub.QualitaTitle') }}</span>
              </div>
            </VCardItem>

            <VTabs
              v-model="qualitaSubTab"
              class="px-4 border-b"
              density="compact"
            >
              <VTab value="nc">
                <VIcon
                  icon="tabler-alert-triangle"
                  size="18"
                  class="me-2"
                />
                {{ t('Hub.NonConformities') }}
                <VChip
                  size="x-small"
                  :color="qualitaData.non_conformita.length > 0 ? 'warning' : 'success'"
                  variant="tonal"
                  class="ms-2"
                >
                  {{ qualitaData.non_conformita.length }}
                </VChip>
              </VTab>
              <VTab value="checker">
                <VIcon
                  icon="tabler-checkup-list"
                  size="18"
                  class="me-2"
                />
                Checker
                <VChip
                  size="x-small"
                  color="teal"
                  variant="tonal"
                  class="ms-2"
                >
                  {{ qualitaData.checker_reports?.length ?? 0 }}
                </VChip>
              </VTab>
              <VTab value="tipo">
                <VIcon
                  icon="tabler-test-pipe"
                  size="18"
                  class="me-2"
                />
                {{ t('Hub.ProveTipo') }}
                <VChip
                  size="x-small"
                  color="purple"
                  variant="tonal"
                  class="ms-2"
                >
                  {{ qualitaData.type_tests?.length ?? 0 }}
                </VChip>
              </VTab>
              <VTab value="cpr">
                <VIcon
                  icon="tabler-certificate"
                  size="18"
                  class="me-2"
                />
                {{ t('Hub.ProveCpr') }}
                <VChip
                  size="x-small"
                  color="indigo"
                  variant="tonal"
                  class="ms-2"
                >
                  {{ qualitaData.cpr_tests?.length ?? 0 }}
                </VChip>
              </VTab>
            </VTabs>

            <VCardText class="pa-0">
              <div
                v-if="loadingQualita"
                class="text-center pa-6"
              >
                <VProgressCircular
                  indeterminate
                  color="warning"
                />
              </div>

              <VWindow
                v-else
                v-model="qualitaSubTab"
              >
                <VWindowItem value="nc">
              <VTable
                v-if="qualitaData.non_conformita.length > 0"
                hover
                density="comfortable"
              >
                <thead>
                  <tr>
                    <th>{{ t('Hub.ColYearNum') }}</th>
                    <th>{{ t('Hub.ColOpenDate') }}</th>
                    <th>{{ t('Hub.ColOlProduct') }}</th>
                    <th>{{ t('Hub.ColBobbin') }}</th>
                    <th>{{ t('Hub.Machine') }}</th>
                    <th>{{ t('Hub.ColDefect') }}</th>
                    <th>{{ t('Hub.ColStatus') }}</th>
                    <th>{{ t('Hub.ColSolution') }}</th>
                    <th>{{ t('Hub.ColActions') }}</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="nc in qualitaData.non_conformita"
                    :key="nc.id"
                    :class="{ 'strisciata-evidenziata': isSearchedOl(nc.ol) }"
                  >
                    <td class="font-weight-bold">
                      #{{ nc.numero }} / {{ nc.anno }}
                    </td>
                    <td>{{ nc.data_apertura ? moment(nc.data_apertura).format('DD/MM/YYYY') : '-' }}</td>
                    <td>
                      <div class="font-weight-bold">
                        {{ nc.ol || '-' }}
                      </div>
                      <div
                        v-if="nc.prodotto"
                        class="text-caption text-medium-emphasis"
                      >
                        {{ nc.prodotto }}
                      </div>
                    </td>
                    <td>
                      <VChip
                        size="x-small"
                        variant="outlined"
                      >
                        {{ nc.bobina || '-' }}
                      </VChip>
                    </td>
                    <td>{{ nc.macchina }}</td>
                    <td>
                      <div class="font-weight-medium text-error">
                        {{ nc.difetto }}
                      </div>
                      <div
                        v-if="nc.note"
                        class="text-caption text-medium-emphasis"
                      >
                        {{ nc.note }}
                      </div>
                    </td>
                    <td>
                      <VChip
                        size="x-small"
                        :color="nc.stato === 3 ? 'success' : 'error'"
                        variant="flat"
                      >
                        {{ nc.stato_label }}
                      </VChip>
                    </td>
                    <td>
                      <div
                        v-if="nc.motivazione_chiusura"
                        class="text-caption font-weight-bold"
                      >
                        {{ nc.motivazione_chiusura }}
                      </div>
                      <div
                        v-if="nc.soluzione"
                        class="text-caption text-medium-emphasis"
                      >
                        {{ nc.soluzione }}
                      </div>
                    </td>
                    <td>
                      <VBtn
                        v-if="nc.google_drive_id"
                        size="x-small"
                        variant="tonal"
                        color="primary"
                        icon="tabler-brand-google-drive"
                        @click="openDrive(nc.google_drive_id)"
                      />
                    </td>
                  </tr>
                </tbody>
              </VTable>

              <div
                v-else
                class="text-center pa-6 text-success"
              >
                <VIcon
                  icon="tabler-circle-check"
                  size="24"
                  class="me-1"
                />
                {{ t('Hub.NoNc') }}
              </div>
                </VWindowItem>

                <VWindowItem value="checker">
              <VTable
                v-if="qualitaData.checker_reports?.length > 0"
                hover
                density="comfortable"
              >
                <thead>
                  <tr>
                    <th>{{ t('Hub.ColDate') }}</th>
                    <th>{{ t('Hub.ColOlProduct') }}</th>
                    <th>{{ t('Hub.ColBobbin') }}</th>
                    <th>Km</th>
                    <th>Num FO</th>
                    <th>Stage</th>
                    <th>{{ t('Hub.ColCompliance') }}</th>
                    <th>{{ t('Hub.ColNote') }}</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="c in qualitaData.checker_reports"
                    :key="c.id"
                    :class="{ 'strisciata-evidenziata': isSearchedOl(c.ol) }"
                  >
                    <td>{{ c.date_create ? moment(c.date_create).format('DD/MM/YYYY') : '-' }}</td>
                    <td>
                      <div class="font-weight-bold">
                        {{ c.ol || '-' }}
                      </div>
                      <div
                        v-if="c.prodotto"
                        class="text-caption text-medium-emphasis"
                      >
                        {{ c.prodotto }}
                      </div>
                    </td>
                    <td class="font-weight-bold">
                      {{ c.coil }}
                    </td>
                    <td>{{ c.km }}</td>
                    <td>{{ c.num_fo }}</td>
                    <td>{{ c.stage }}</td>
                    <td>
                      <VChip
                        size="x-small"
                        :color="c.not_conformity === '0' ? 'success' : 'error'"
                        variant="flat"
                      >
                        {{ c.not_conformity === "0" ? t('Hub.Compliant') : t('Hub.NonCompliant') }}
                      </VChip>
                    </td>
                    <td>{{ c.note || '-' }}</td>
                  </tr>
                </tbody>
              </VTable>

              <div
                v-else
                class="text-center pa-6 text-medium-emphasis"
              >
                {{ t('Hub.NoChecker') }}
              </div>
                </VWindowItem>

                <VWindowItem value="tipo">
              <VTable
                v-if="qualitaData.type_tests?.length > 0"
                hover
                density="comfortable"
              >
                <thead>
                  <tr>
                    <th>{{ t('Hub.ColTestDate') }}</th>
                    <th>{{ t('Hub.ColOlProduct') }}</th>
                    <th>{{ t('Hub.ColMaterial2') }}</th>
                    <th>{{ t('Hub.ColTypology') }}</th>
                    <th>Standard</th>
                    <th>Specifica</th>
                    <th>{{ t('Hub.ColResult') }}</th>
                    <th>{{ t('Hub.ColActions') }}</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="t in qualitaData.type_tests"
                    :key="t.id"
                    :class="{ 'strisciata-evidenziata': isSearchedOl(t.ol) }"
                  >
                    <td>{{ t.data_prova ? moment(t.data_prova).format('DD/MM/YYYY') : '-' }}</td>
                    <td>
                      <div class="font-weight-bold">
                        {{ t.ol || '-' }}
                      </div>
                      <div
                        v-if="t.prodotto"
                        class="text-caption text-medium-emphasis"
                      >
                        {{ t.prodotto }}
                      </div>
                    </td>
                    <td>
                      <div class="font-weight-medium">
                        {{ t.materiale || '-' }}
                      </div>
                      <div
                        v-if="t.descrizione"
                        class="text-caption text-medium-emphasis"
                      >
                        {{ t.descrizione }}
                      </div>
                    </td>
                    <td>{{ t.categoria || '-' }}</td>
                    <td>{{ t.standard || '-' }}</td>
                    <td>{{ t.specifica || '-' }}</td>
                    <td>
                      <VChip
                        size="x-small"
                        :color="esitoColor(t.esito)"
                        variant="flat"
                      >
                        {{ t.esito || '-' }}
                      </VChip>
                    </td>
                    <td>
                      <VBtn
                        v-if="t.path_drive"
                        size="x-small"
                        variant="tonal"
                        color="primary"
                        icon="tabler-brand-google-drive"
                        @click="openDrive(t.path_drive)"
                      />
                    </td>
                  </tr>
                </tbody>
              </VTable>

              <div
                v-else
                class="text-center pa-6 text-medium-emphasis"
              >
                {{ t('Hub.NoTypeTests') }}
              </div>
                </VWindowItem>

                <VWindowItem value="cpr">
              <VTable
                v-if="qualitaData.cpr_tests?.length > 0"
                hover
                density="comfortable"
              >
                <thead>
                  <tr>
                    <th>{{ t('Hub.ColTestDate') }}</th>
                    <th>{{ t('Hub.ColOlProduct') }}</th>
                    <th>{{ t('Hub.ColMaterial2') }}</th>
                    <th>{{ t('Hub.ColTypology') }}</th>
                    <th>Standard</th>
                    <th>{{ t('Hub.ColClassification') }}</th>
                    <th>{{ t('Hub.ColResult') }}</th>
                    <th>{{ t('Hub.ColActions') }}</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="t in qualitaData.cpr_tests"
                    :key="t.id"
                    :class="{ 'strisciata-evidenziata': isSearchedOl(t.ol) }"
                  >
                    <td>{{ t.data_prova ? moment(t.data_prova).format('DD/MM/YYYY') : '-' }}</td>
                    <td>
                      <div class="font-weight-bold">
                        {{ t.ol || '-' }}
                      </div>
                      <div
                        v-if="t.prodotto"
                        class="text-caption text-medium-emphasis"
                      >
                        {{ t.prodotto }}
                      </div>
                    </td>
                    <td>
                      <div class="font-weight-medium">
                        {{ t.materiale || '-' }}
                      </div>
                      <div
                        v-if="t.descrizione"
                        class="text-caption text-medium-emphasis"
                      >
                        {{ t.descrizione }}
                      </div>
                    </td>
                    <td>{{ t.categoria || '-' }}</td>
                    <td>{{ t.standard || '-' }}</td>
                    <td class="font-weight-bold">
                      {{ t.class || '-' }}
                    </td>
                    <td>
                      <VChip
                        size="x-small"
                        :color="esitoColor(t.esito)"
                        variant="flat"
                      >
                        {{ t.esito || '-' }}
                      </VChip>
                    </td>
                    <td>
                      <VBtn
                        v-if="t.path_drive"
                        size="x-small"
                        variant="tonal"
                        color="primary"
                        icon="tabler-brand-google-drive"
                        @click="openDrive(t.path_drive)"
                      />
                    </td>
                  </tr>
                </tbody>
              </VTable>

              <div
                v-else
                class="text-center pa-6 text-medium-emphasis"
              >
                {{ t('Hub.NoCpr') }}
              </div>
                </VWindowItem>
              </VWindow>
            </VCardText>
          </VCard>
        </VWindowItem>

        <!-- TAB 4: TELEMETRIA & DIAMETRI PLC -->
        <VWindowItem value="telemetria">
          <VCard
            class="elevation-2"
            rounded="lg"
          >
            <VCardItem class="pa-4 border-b">
              <div class="d-flex align-center justify-space-between flex-wrap gap-2">
                <div class="d-flex align-center gap-2">
                  <VIcon
                    icon="tabler-chart-line"
                    color="deep-purple"
                    size="24"
                  />
                  <div>
                    <span class="text-h6 font-weight-bold">{{ t('Hub.TelemetryTitle') }}</span>
                    <span
                      v-if="telemetriaData?.macchina"
                      class="text-caption text-medium-emphasis ms-2"
                    >
                      {{ t('Hub.Machine') }}: <strong>{{ telemetriaData.macchina }}</strong>
                      ({{ telemetriaData.data_inizio }} &rarr; {{ telemetriaData.data_fine }})
                    </span>
                  </div>
                </div>

                <div class="d-flex align-center gap-2">
                  <VChip
                    size="small"
                    color="deep-purple"
                    variant="tonal"
                  >
                    {{ t('Hub.RunsWithPlc', { n: strisciateConTelemetria.length }) }}
                  </VChip>
                </div>
              </div>
            </VCardItem>

            <!-- Nessuna strisciata con telemetria -->
            <VCardText
              v-if="strisciateConTelemetria.length === 0"
              class="pa-6 text-center"
            >
              <VIcon
                icon="tabler-info-circle"
                size="40"
                color="secondary"
                class="mb-2"
              />
              <div class="text-subtitle-1 font-weight-medium">
                {{ t('Hub.NoTelemetry') }}
              </div>
              <div class="text-caption text-medium-emphasis mt-1">
                {{ t('Hub.EnabledMachines') }}
                <strong>TR 120 C1, TR 120 C2, TR 100 C, TR 120 SM</strong>.
              </div>
            </VCardText>

            <VCardText
              v-else
              class="pa-4"
            >
              <!-- Barra Selettore Lavorazioni -->
              <div class="mb-4">
                <div class="text-caption font-weight-medium text-medium-emphasis mb-2">
                  {{ t('Hub.SelectRun') }}
                </div>

                <!-- Filtro rapido per macchina -->
                <div
                  v-if="telemetriaMachines.length > 1"
                  class="d-flex flex-wrap align-center gap-2 mb-3"
                >
                  <VChip
                    size="small"
                    :color="!telemetriaMachineFilter ? 'deep-purple' : 'default'"
                    :variant="!telemetriaMachineFilter ? 'elevated' : 'outlined'"
                    @click="telemetriaMachineFilter = null"
                  >
                    {{ t('Hub.All') }} ({{ strisciateConTelemetria.length }})
                  </VChip>
                  <VChip
                    v-for="m in telemetriaMachines"
                    :key="m.nome"
                    size="small"
                    :color="telemetriaMachineFilter === m.nome ? 'deep-purple' : 'default'"
                    :variant="telemetriaMachineFilter === m.nome ? 'elevated' : 'outlined'"
                    prepend-icon="tabler-cpu"
                    @click="telemetriaMachineFilter = telemetriaMachineFilter === m.nome ? null : m.nome"
                  >
                    {{ m.nome }}
                    <span class="text-caption ml-1 opacity-80">({{ m.n }})</span>
                  </VChip>
                </div>

                <!-- Autocomplete ricercabile -->
                <VAutocomplete
                  :model-value="telemetriaSelectedStrisciata"
                  :items="strisciateFiltrate"
                  :item-title="(s: any) => strisciataLabel(s)"
                  return-object
                  density="compact"
                  variant="outlined"
                  hide-details
                  clearable
                  :placeholder="t('Hub.SearchRun')"
                  prepend-inner-icon="tabler-search"
                  @update:model-value="(v: any) => v && loadTelemetria(v)"
                >
                  <template #item="{ props, item }">
                    <VListItem v-bind="props">
                      <template #subtitle>
                        <span class="text-caption">
                          {{ item.raw.data_inizio ? moment(item.raw.data_inizio).format('DD/MM/YYYY HH:mm') : '-' }}
                          <template v-if="item.raw.data_fine"> → {{ moment(item.raw.data_fine).format('HH:mm') }}</template>
                          · OL {{ strisciateOl(item.raw) }}<template v-if="item.raw.bobbina"> · Bob. {{ item.raw.bobbina }}</template>
                        </span>
                      </template>
                    </VListItem>
                  </template>
                </VAutocomplete>
              </div>

              <!-- Barra Parametri Tolleranza -->
              <VCard
                variant="tonal"
                color="deep-purple"
                class="pa-3 mb-4"
                rounded="lg"
              >
                <div class="d-flex align-center justify-space-between flex-wrap gap-3">
                  <div class="d-flex align-center gap-3 flex-wrap">
                    <div style="min-width: 170px;">
                      <VTextField
                        v-model.number="targetDiametroInput"
                        :label="t('Hub.TargetNominal')"
                        placeholder="Es. 14.05"
                        type="number"
                        step="0.01"
                        density="compact"
                        variant="outlined"
                        hide-details
                      />
                    </div>
                    <div style="min-width: 170px;">
                      <VTextField
                        v-model.number="tolleranzaInput"
                        :label="t('Hub.Tolerance')"
                        placeholder="Es. 0.10"
                        type="number"
                        step="0.01"
                        density="compact"
                        variant="outlined"
                        hide-details
                      />
                    </div>
                    <VBtn
                      size="small"
                      color="deep-purple"
                      variant="elevated"
                      prepend-icon="tabler-adjustments"
                      :loading="loadingTelemetria"
                      @click="loadTelemetria()"
                    >
                      {{ t('Hub.ApplyTolerance') }}
                    </VBtn>
                  </div>

                  <div
                    v-if="activeKpi"
                    class="text-caption text-medium-emphasis"
                  >
                    {{ t('Hub.SamplesExtracted') }} ({{ telemetriaData.fonte || 'PLC' }}):
                    <strong class="text-high-emphasis">{{ activeKpi.campioni_totali.toLocaleString() }}</strong>
                    ({{ t('Hub.ChartPoints') }}: <strong class="text-high-emphasis">{{ activeKpi.campioni_grafico }}</strong>)
                  </div>
                </div>
              </VCard>

              <!-- Loading spinner -->
              <div
                v-if="loadingTelemetria"
                class="text-center pa-8"
              >
                <VProgressCircular
                  indeterminate
                  color="deep-purple"
                  size="48"
                />
                <div class="text-caption text-medium-emphasis mt-3">
                  {{ t('Hub.LoadingTelemetry') }}
                </div>
              </div>

              <!-- Dati Telemetria -->
              <div v-else-if="activeKpi || telemetriaData?.processo_40?.disponibile">
                <!-- Avviso quando la macchina è 4.0 ma senza log diametri -->
                <VAlert
                  v-if="!activeKpi && telemetriaData?.processo_40?.disponibile"
                  type="info"
                  variant="tonal"
                  density="compact"
                  class="mb-4"
                  icon="tabler-info-circle"
                >
                  {{ t('Hub.Only40Alert') }}
                </VAlert>

                <!-- Alert quando è attivo il filtro parziale temporale -->
                <VAlert
                  v-if="activeKpi?.is_filtered"
                  type="info"
                  variant="tonal"
                  density="compact"
                  class="mb-3"
                  icon="tabler-filter"
                >
                  <div class="d-flex flex-wrap align-center justify-space-between gap-2">
                    <div>
                      <strong>{{ t('Hub.FilteredIntervalActive') }}:</strong>
                      <span class="ms-1">{{ rangeStartTime }} &rarr; {{ rangeEndTime }}</span>
                      <span class="text-caption text-medium-emphasis ms-2">
                        ({{ activeKpi.campioni_grafico }} {{ t('Hub.ChartPoints') }} &bull; {{ rangeDuration }})
                        &mdash; {{ t('Hub.KpiRecalculated') }}
                      </span>
                    </div>
                    <VBtn
                      size="x-small"
                      color="info"
                      variant="elevated"
                      prepend-icon="tabler-rotate"
                      @click="resetTimeRange"
                    >
                      {{ t('Hub.ResetFullRange') }}
                    </VBtn>
                  </div>
                </VAlert>

                <!-- KPI Cards Grid: Diametri -->
                <VRow
                  v-if="activeKpi"
                  class="mb-4"
                >
                  <VCol
                    cols="12"
                    sm="6"
                    md="3"
                  >
                    <VCard
                      variant="outlined"
                      rounded="lg"
                      class="pa-3"
                    >
                      <div class="d-flex align-center justify-space-between mb-1">
                        <span class="text-caption font-weight-bold text-high-emphasis">
                          {{ t('Hub.AvgDiameter') }}
                        </span>
                        <VIcon
                          icon="tabler-gauge"
                          color="info"
                          size="22"
                        />
                      </div>
                      <div class="text-h4 font-weight-bold text-info">
                        {{ activeKpi.medio }} <span class="text-caption font-weight-medium text-medium-emphasis">mm</span>
                      </div>
                      <div class="text-caption text-medium-emphasis mt-1">
                        {{ t('Hub.StdDev') }} &sigma; = {{ activeKpi.std_dev }} mm
                      </div>
                    </VCard>
                  </VCol>

                  <VCol
                    cols="12"
                    sm="6"
                    md="3"
                  >
                    <VCard
                      variant="outlined"
                      rounded="lg"
                      class="pa-3"
                    >
                      <div class="d-flex align-center justify-space-between mb-1">
                        <span class="text-caption font-weight-bold text-high-emphasis">
                          {{ t('Hub.MinMaxRange') }}
                        </span>
                        <VIcon
                          icon="tabler-arrows-vertical"
                          color="primary"
                          size="22"
                        />
                      </div>
                      <div class="text-h5 font-weight-bold text-primary">
                        {{ activeKpi.min }} - {{ activeKpi.max }} <span class="text-caption font-weight-medium text-medium-emphasis">mm</span>
                      </div>
                      <div class="text-caption text-medium-emphasis mt-1">
                        {{ t('Hub.TotalDelta') }} {{ activeKpi.delta }} mm
                      </div>
                    </VCard>
                  </VCol>

                  <VCol
                    cols="12"
                    sm="6"
                    md="3"
                  >
                    <VCard
                      variant="outlined"
                      rounded="lg"
                      class="pa-3"
                    >
                      <div class="d-flex align-center justify-space-between mb-1">
                        <span class="text-caption font-weight-bold text-high-emphasis">
                          {{ t('Hub.MaxOvality') }}
                        </span>
                        <VIcon
                          icon="tabler-circle-dotted"
                          color="warning"
                          size="22"
                        />
                      </div>
                      <div class="text-h4 font-weight-bold text-warning">
                        {{ activeKpi.ovalizzazione_max }} <span class="text-caption font-weight-medium text-medium-emphasis">mm</span>
                      </div>
                      <div class="text-caption text-medium-emphasis mt-1">
                        max |Diam_X - Diam_Y|
                      </div>
                    </VCard>
                  </VCol>

                  <VCol
                    cols="12"
                    sm="6"
                    md="3"
                  >
                    <VCard
                      variant="outlined"
                      rounded="lg"
                      class="pa-3"
                    >
                      <div class="d-flex align-center justify-space-between mb-1">
                        <span class="text-caption font-weight-bold text-high-emphasis">
                          {{ t('Hub.ToleranceCompliance') }}
                        </span>
                        <VIcon
                          :icon="activeKpi.target ? (activeKpi.fuori_tolleranza === 0 ? 'tabler-circle-check' : 'tabler-alert-triangle') : 'tabler-chart-dots'"
                          :color="activeKpi.target ? (activeKpi.fuori_tolleranza === 0 ? 'success' : 'error') : 'secondary'"
                          size="22"
                        />
                      </div>
                      <div
                        v-if="activeKpi.target"
                        class="text-h4 font-weight-bold"
                        :class="activeKpi.fuori_tolleranza === 0 ? 'text-success' : 'text-error'"
                      >
                        {{ activeKpi.fuori_tolleranza_pct }}%
                        <span class="text-caption font-weight-medium text-medium-emphasis">{{ t('Hub.OutOfTolerance') }}</span>
                      </div>
                      <div
                        v-else
                        class="text-h4 font-weight-bold text-secondary"
                      >
                        {{ activeKpi.campioni_totali.toLocaleString() }}
                        <span class="text-caption font-weight-medium text-medium-emphasis">{{ t('Hub.Samples') }}</span>
                      </div>
                      <div class="text-caption text-medium-emphasis mt-1">
                        {{ activeKpi.target ? t('Hub.SamplesOverThreshold', { n: activeKpi.fuori_tolleranza.toLocaleString() }) : t('Hub.SetTargetHint') }}
                      </div>
                    </VCard>
                  </VCol>
                </VRow>

                <!-- KPI Cards Grid: Processo 4.0 (se disponibili da STL_Info_Ordine_V) -->
                <VRow
                  v-if="telemetriaData.processo_40?.disponibile"
                  class="mb-4"
                >
                  <VCol
                    cols="12"
                    sm="6"
                    md="3"
                  >
                    <VCard
                      variant="tonal"
                      color="success"
                      rounded="lg"
                      class="pa-3"
                    >
                      <div class="d-flex align-center justify-space-between mb-1">
                        <span class="text-caption font-weight-bold">
                          {{ t('Hub.LineSpeed') }}
                        </span>
                        <VIcon
                          icon="tabler-dashboard"
                          size="22"
                        />
                      </div>
                      <div class="text-h4 font-weight-bold">
                        {{ telemetriaData.processo_40.kpi.velocita_linea_media }} <span class="text-caption font-weight-medium">m/min</span>
                      </div>
                      <div class="text-caption mt-1">
                        {{ t('Hub.MaxPeak') }} {{ telemetriaData.processo_40.kpi.velocita_linea_max }} m/min
                        <span v-if="telemetriaData.processo_40.kpi.velocita_minima_teorica">
                          (min: {{ telemetriaData.processo_40.kpi.velocita_minima_teorica }})
                        </span>
                      </div>
                    </VCard>
                  </VCol>

                  <VCol
                    cols="12"
                    sm="6"
                    md="3"
                  >
                    <VCard
                      variant="tonal"
                      color="primary"
                      rounded="lg"
                      class="pa-3"
                    >
                      <div class="d-flex align-center justify-space-between mb-1">
                        <span class="text-caption font-weight-bold">
                          {{ t('Hub.AvgExtruder') }}
                        </span>
                        <VIcon
                          icon="tabler-engine"
                          size="22"
                        />
                      </div>
                      <div class="text-h4 font-weight-bold">
                        {{ telemetriaData.processo_40.kpi.velocita_estrusore_media }} <span class="text-caption font-weight-medium">RPM</span>
                      </div>
                      <div class="text-caption mt-1">
                        {{ t('Hub.AvgExtrusionRate') }}
                      </div>
                    </VCard>
                  </VCol>

                  <VCol
                    cols="12"
                    sm="6"
                    md="3"
                  >
                    <VCard
                      variant="tonal"
                      :color="telemetriaData.processo_40.kpi.totale_fermo_minuti > 0 ? 'warning' : 'secondary'"
                      rounded="lg"
                      class="pa-3"
                    >
                      <div class="d-flex align-center justify-space-between mb-1">
                        <span class="text-caption font-weight-bold">
                          {{ t('Hub.Downtime') }}
                        </span>
                        <VIcon
                          icon="tabler-clock-pause"
                          size="22"
                        />
                      </div>
                      <div class="text-h4 font-weight-bold">
                        {{ telemetriaData.processo_40.kpi.totale_fermo_minuti }} <span class="text-caption font-weight-medium">min</span>
                      </div>
                      <div class="text-caption mt-1">
                        {{ t('Hub.EventsRecorded', { n: telemetriaData.processo_40.kpi.numero_fermi }) }}
                      </div>
                    </VCard>
                  </VCol>

                  <VCol
                    cols="12"
                    sm="6"
                    md="3"
                  >
                    <VCard
                      variant="tonal"
                      color="info"
                      rounded="lg"
                      class="pa-3"
                    >
                      <div class="d-flex align-center justify-space-between mb-1">
                        <span class="text-caption font-weight-bold">
                          {{ t('Hub.PlcOperator') }}
                        </span>
                        <VIcon
                          icon="tabler-user-check"
                          size="22"
                        />
                      </div>
                      <div class="text-h6 font-weight-bold text-truncate">
                        {{ telemetriaData.processo_40.operatore_principale || '-' }}
                      </div>
                      <div class="text-caption mt-1 text-truncate">
                        {{ t('Hub.GpMachineId') }} {{ telemetriaData.processo_40.macchina_id }}
                      </div>
                    </VCard>
                  </VCol>
                </VRow>

                <!-- Grafico ApexCharts -->
                <VCard
                  variant="outlined"
                  rounded="lg"
                  class="pa-4 mb-4"
                >
                  <div class="d-flex flex-wrap align-center justify-space-between gap-2 mb-2">
                    <div>
                      <span class="text-subtitle-1 font-weight-bold text-high-emphasis">
                        {{ t('Hub.ChartTitle') }}
                      </span>
                      <div class="text-caption text-medium-emphasis">
                        {{ t('Hub.ChartHint') }}
                      </div>
                    </div>
                    <div
                      v-if="telemetriaData.processo_40?.serie?.length"
                      class="d-flex align-center gap-2"
                    >
                      <VSwitch
                        v-model="showSpeedSeries"
                        color="success"
                        density="compact"
                        hide-details
                        :label="t('Hub.OverlaySpeed')"
                      />
                    </div>
                  </div>

                  <!-- Range Slider Temporale per analizzare solo una parte del grafico -->
                  <div
                    v-if="telemetriaData?.punti?.length > 1"
                    class="my-3 pa-3 rounded bg-surface border"
                  >
                    <div class="d-flex flex-wrap align-center justify-space-between gap-2 mb-2">
                      <div class="d-flex align-center gap-2">
                        <VIcon
                          icon="tabler-adjustments-horizontal"
                          color="deep-purple"
                          size="20"
                        />
                        <span class="text-caption font-weight-bold text-high-emphasis">
                          {{ t('Hub.TimeRangeTitle') }}
                        </span>
                        <span class="text-caption text-medium-emphasis">
                          ({{ rangeDuration }} &bull; {{ selectedPoints.length }} / {{ telemetriaData.punti.length }} {{ t('Hub.ChartPoints') }})
                        </span>
                      </div>

                      <div class="d-flex flex-wrap align-center gap-1">
                        <VChip
                          v-if="rangeIsFiltered"
                          size="x-small"
                          color="warning"
                          variant="tonal"
                          prepend-icon="tabler-filter"
                        >
                          {{ t('Hub.PartialInterval') }}
                        </VChip>

                        <VBtn
                          v-if="rangeIsFiltered"
                          size="x-small"
                          color="secondary"
                          variant="tonal"
                          prepend-icon="tabler-rotate"
                          @click="resetTimeRange"
                        >
                          {{ t('Hub.ResetFullRange') }}
                        </VBtn>

                        <VBtn
                          size="x-small"
                          color="default"
                          variant="outlined"
                          @click="excludeStart(5)"
                        >
                          {{ t('Hub.ExcludeStart') }}
                        </VBtn>

                        <VBtn
                          size="x-small"
                          color="default"
                          variant="outlined"
                          @click="excludeEnd(5)"
                        >
                          {{ t('Hub.ExcludeEnd') }}
                        </VBtn>

                        <VBtn
                          size="x-small"
                          color="default"
                          variant="outlined"
                          @click="applyRegimePreset"
                        >
                          {{ t('Hub.RegimePreset') }}
                        </VBtn>
                      </div>
                    </div>

                    <!-- Estremi orari selezionati e cursore a due maniglie -->
                    <div class="d-flex align-center justify-space-between text-caption font-weight-semibold px-1 mb-1">
                      <span class="text-primary d-flex align-center gap-1">
                        <VIcon icon="tabler-player-play" size="13" />
                        {{ t('Hub.RangeStart') }}: <strong>{{ rangeStartTime }}</strong>
                      </span>
                      <span class="text-caption text-medium-emphasis">
                        {{ Math.round((selectedPoints.length / telemetriaData.punti.length) * 100) }}% {{ t('Hub.OfProduction') }}
                      </span>
                      <span class="text-primary d-flex align-center gap-1">
                        {{ t('Hub.RangeEnd') }}: <strong>{{ rangeEndTime }}</strong>
                        <VIcon icon="tabler-player-stop" size="13" />
                      </span>
                    </div>

                    <VRangeSlider
                      v-model="telemetriaTimeRange"
                      :min="0"
                      :max="maxRangeIndex"
                      :step="1"
                      strict
                      color="deep-purple"
                      track-color="rgba(var(--v-theme-on-surface), 0.12)"
                      thumb-size="14"
                      track-size="5"
                      hide-details
                      class="px-1"
                    />

                    <div class="d-flex align-center justify-space-between text-caption text-disabled px-1 mt-1">
                      <span>{{ fullStartTime }}</span>
                      <span>{{ fullEndTime }}</span>
                    </div>
                  </div>

                  <VueApexCharts
                    type="line"
                    height="420"
                    :options="apexOptions"
                    :series="apexSeries"
                  />
                </VCard>

                <!-- Opzione 3: Card Condizioni di Processo 4.0 ed Eventi di Fermo -->
                <VCard
                  v-if="telemetriaData.processo_40?.disponibile"
                  variant="outlined"
                  rounded="lg"
                  class="pa-4 mb-4"
                >
                  <div class="d-flex align-center justify-space-between mb-3">
                    <div class="d-flex align-center gap-2">
                      <VIcon
                        icon="tabler-cpu-2"
                        color="deep-purple"
                        size="22"
                      />
                      <span class="text-subtitle-1 font-weight-bold">
                        {{ t('Hub.ProcessCardTitle') }}
                      </span>
                    </div>
                    <VChip
                      size="small"
                      :color="telemetriaData.processo_40.fermi.length > 0 ? 'warning' : 'success'"
                      variant="tonal"
                    >
                      {{ telemetriaData.processo_40.fermi.length > 0 ? t('Hub.StopsRecorded', { n: telemetriaData.processo_40.fermi.length }) : t('Hub.NoStops') }}
                    </VChip>
                  </div>

                  <!-- Tabella eventi di fermo -->
                  <div v-if="telemetriaData.processo_40.fermi.length > 0">
                    <VTable
                      density="compact"
                      class="border rounded"
                    >
                      <thead>
                        <tr>
                          <th class="text-left font-weight-bold">
                            {{ t('Hub.StopStart') }}
                          </th>
                          <th class="text-left font-weight-bold">
                            {{ t('Hub.StopEnd') }}
                          </th>
                          <th class="text-left font-weight-bold">
                            {{ t('Hub.StopDuration') }}
                          </th>
                          <th class="text-left font-weight-bold">
                            {{ t('Hub.StopReason') }}
                          </th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr
                          v-for="(f, fIdx) in telemetriaData.processo_40.fermi"
                          :key="fIdx"
                        >
                          <td>
                            <VIcon
                              icon="tabler-clock-play"
                              size="14"
                              class="me-1 text-medium-emphasis"
                            />
                            {{ f.inizio ? moment(f.inizio).format('DD/MM/YYYY HH:mm:ss') : '-' }}
                          </td>
                          <td>
                            <VIcon
                              icon="tabler-clock-stop"
                              size="14"
                              class="me-1 text-medium-emphasis"
                            />
                            {{ f.fine ? moment(f.fine).format('DD/MM/YYYY HH:mm:ss') : t('Hub.InProgress') }}
                          </td>
                          <td>
                            <VChip
                              size="x-small"
                              color="warning"
                              variant="flat"
                            >
                              {{ f.durata_minuti }} min
                            </VChip>
                          </td>
                          <td class="font-weight-medium">
                            {{ f.causale }}
                          </td>
                        </tr>
                      </tbody>
                    </VTable>
                  </div>

                  <!-- Sintesi se nessun fermo -->
                  <div
                    v-else
                    class="d-flex align-center gap-2 pa-2 text-caption text-success"
                  >
                    <VIcon
                      icon="tabler-circle-check"
                      size="18"
                    />
                    <span>{{ t('Hub.NoStopsText') }}</span>
                  </div>

                  <!-- Operatori coinvolti -->
                  <div
                    v-if="telemetriaData.processo_40.operatori.length > 0"
                    class="d-flex align-center gap-2 mt-3 pt-2 border-t text-caption text-medium-emphasis"
                  >
                    <VIcon
                      icon="tabler-users"
                      size="16"
                    />
                    <span>
                      {{ t('Hub.OperatorsAtTerminal') }}
                      <strong class="text-high-emphasis">{{ telemetriaData.processo_40.operatori.join(', ') }}</strong>
                    </span>
                  </div>
                </VCard>

                <!-- Footer Informativo -->
                <div
                  v-if="activeKpi"
                  class="d-flex align-center gap-2 text-caption text-medium-emphasis"
                >
                  <VIcon
                    :icon="telemetriaData.fonte?.includes('Drive') ? 'tabler-brand-google-drive' : 'tabler-database'"
                    :color="telemetriaData.fonte?.includes('Drive') ? 'primary' : 'success'"
                    size="18"
                  />
                  <span>
                    {{ t('Hub.TelemetrySource') }} <strong>{{ telemetriaData.fonte || 'Google Drive / PLC' }}</strong> ({{ t('Hub.OnMachine') }} <strong>{{ telemetriaData.macchina }}</strong>).
                  </span>
                </div>
              </div>

              <!-- Nessun dato trovato per l'intervallo -->
              <div
                v-else
                class="text-center pa-6 text-medium-emphasis"
              >
                {{ t('Hub.NoDiameterData') }}
              </div>
            </VCardText>
          </VCard>
        </VWindowItem>

        <!-- TAB 5: DOCUMENTI & DRIVE -->
        <VWindowItem value="documenti">
          <VCard
            class="elevation-2"
            rounded="lg"
          >
            <VCardItem class="pa-4 border-b">
              <div class="d-flex align-center justify-space-between">
                <div class="d-flex align-center gap-2">
                  <VIcon
                    icon="tabler-folders"
                    color="secondary"
                    size="22"
                  />
                  <span class="text-h6 font-weight-bold">{{ t('Hub.DocsTitle') }}</span>
                </div>
                <VChip
                  size="small"
                  color="secondary"
                  variant="tonal"
                >
                  {{ t('Hub.Attachments', { n: documentiData.documents?.length ?? 0 }) }}
                </VChip>
              </div>
            </VCardItem>

            <VCardText class="pa-4">
              <div
                v-if="loadingDocumenti"
                class="text-center pa-6"
              >
                <VProgressCircular
                  indeterminate
                  color="secondary"
                />
              </div>

              <!-- Cartelle Google Drive della Commessa -->
              <div
                v-if="documentiData.drive_folders?.length > 0"
                class="mb-4"
              >
                <h4 class="text-subtitle-1 font-weight-bold mb-2">
                  {{ t('Hub.DriveFoldersTitle') }}
                </h4>
                <div class="d-flex flex-wrap gap-2">
                  <VBtn
                    v-for="(f, idx) in documentiData.drive_folders"
                    :key="idx"
                    variant="tonal"
                    color="primary"
                    prepend-icon="tabler-brand-google-drive"
                    :href="f.folder_drive ? driveFolderUrl(f.folder_drive) : driveFileUrl(f.id_file_drive)"
                    target="_blank"
                    rel="noopener"
                  >
                    {{ t('Hub.JobOrder') }} {{ f.commessa }} (Rev. {{ f.revisione || '0' }})
                  </VBtn>
                </div>
              </div>

              <!-- Tabella Documenti -->
              <VTable
                v-if="documentiData.documents?.length > 0"
                hover
                density="comfortable"
              >
                <thead>
                  <tr>
                    <th>{{ t('Hub.ColFileName') }}</th>
                    <th>{{ t('Hub.ColTypology') }}</th>
                    <th>{{ t('Hub.ColReference') }}</th>
                    <th>{{ t('Hub.ColUploadDate') }}</th>
                    <th>{{ t('Hub.ColActions') }}</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="doc in documentiData.documents"
                    :key="doc.id"
                  >
                    <td class="font-weight-medium">
                      <VIcon
                        icon="tabler-file-text"
                        size="18"
                        class="me-2 text-primary"
                      />
                      {{ doc.nome_file }}
                    </td>
                    <td>
                      <VChip
                        size="x-small"
                        variant="tonal"
                      >
                        {{ doc.tipologia_label || doc.tipologia || t('Hub.Attachment') }}
                      </VChip>
                    </td>
                    <td>{{ doc.riferimento || '-' }}</td>
                    <td>{{ doc.created_at ? moment(doc.created_at).format('DD/MM/YYYY HH:mm') : '-' }}</td>
                    <td>
                      <VBtn
                        v-if="doc.id_file_drive"
                        size="small"
                        variant="tonal"
                        color="primary"
                        prepend-icon="tabler-external-link"
                        :href="driveFileUrl(doc.id_file_drive)"
                        target="_blank"
                        rel="noopener"
                      >
                        {{ t('Hub.View') }}
                      </VBtn>
                    </td>
                  </tr>
                </tbody>
              </VTable>

              <div
                v-else-if="!loadingDocumenti"
                class="text-center pa-6 text-medium-emphasis"
              >
                {{ t('Hub.NoDocs') }}
              </div>
            </VCardText>
          </VCard>
        </VWindowItem>

        <!-- TAB 5: SPEDIZIONI & DDT -->
        <VWindowItem value="spedizioni">
          <VCard
            class="elevation-2 mb-6"
            rounded="lg"
          >
            <VCardItem class="pa-4 border-b">
              <div class="d-flex align-center justify-space-between">
                <div class="d-flex align-center gap-2">
                  <VIcon
                    icon="tabler-truck"
                    color="success"
                    size="22"
                  />
                  <span class="text-h6 font-weight-bold">{{ t('Hub.DdtTitle') }}</span>
                </div>
                <VChip
                  size="small"
                  color="success"
                  variant="tonal"
                >
                  {{ spedizioniData.ddt?.length ?? 0 }} DDT
                </VChip>
              </div>
            </VCardItem>

            <VCardText class="pa-0">
              <div
                v-if="loadingSpedizioni"
                class="text-center pa-6"
              >
                <VProgressCircular
                  indeterminate
                  color="success"
                />
              </div>

              <VTable
                v-else-if="spedizioniData.ddt?.length > 0"
                hover
                density="comfortable"
              >
                <thead>
                  <tr>
                    <th>{{ t('Hub.ColDdtNumber') }}</th>
                    <th>{{ t('Hub.ColDdtDate') }}</th>
                    <th>{{ t('Hub.ColDestination') }}</th>
                    <th>{{ t('Hub.ColCarrier') }}</th>
                    <th class="text-end">
                      {{ t('Hub.ColPackages') }}
                    </th>
                    <th class="text-end">
                      {{ t('Hub.ColGrossWeight') }}
                    </th>
                    <th>{{ t('Hub.ColShipStatus') }}</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="d in spedizioniData.ddt"
                    :key="d.id"
                  >
                    <td class="font-weight-bold">
                      {{ d.numero_ddt || '-' }}
                    </td>
                    <td>{{ d.data_ddt ? moment(d.data_ddt).format('DD/MM/YYYY') : '-' }}</td>
                    <td>
                      <div class="font-weight-medium">
                        {{ d.destinazione_nome || '-' }}
                      </div>
                      <div class="text-caption text-medium-emphasis">
                        {{ d.destinazione_indirizzo }}
                      </div>
                    </td>
                    <td>{{ d.vettore || '-' }}</td>
                    <td class="text-end font-weight-medium">
                      {{ d.n_colli || '-' }}
                    </td>
                    <td class="text-end font-weight-bold">
                      {{ d.peso_lordo_kg ? Number(d.peso_lordo_kg).toLocaleString() : '-' }}
                    </td>
                    <td>
                      <VChip
                        size="x-small"
                        color="success"
                        variant="tonal"
                      >
                        {{ d.status || t('Hub.Fulfilled') }}
                      </VChip>
                    </td>
                  </tr>
                </tbody>
              </VTable>

              <div
                v-else-if="!loadingSpedizioni"
                class="text-center pa-6 text-medium-emphasis"
              >
                {{ t('Hub.NoDdt') }}
              </div>
            </VCardText>
          </VCard>

          <!-- Righe di Spedizione da Gestione Spedito -->
          <VCard
            v-if="spedizioniData.righe_spedite?.length > 0"
            class="elevation-2"
            rounded="lg"
          >
            <VCardItem class="pa-4 border-b">
              <div class="d-flex align-center gap-2">
                <VIcon
                  icon="tabler-package"
                  color="success"
                  size="22"
                />
                <span class="text-h6 font-weight-bold">{{ t('Hub.ShippedRowsTitle') }}</span>
              </div>
            </VCardItem>

            <VCardText class="pa-0">
              <VTable
                hover
                density="comfortable"
              >
                <thead>
                  <tr>
                    <th>{{ t('Hub.ColDate') }}</th>
                    <th>{{ t('Hub.ColClientRecipient') }}</th>
                    <th>{{ t('Hub.ColItem') }}</th>
                    <th class="text-end">
                      {{ t('Hub.ColDeliveredQty') }}
                    </th>
                    <th>{{ t('Hub.ColDocument') }}</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="r in spedizioniData.righe_spedite"
                    :key="r.id"
                  >
                    <td>{{ r.date_row ? moment(r.date_row).format('DD/MM/YYYY') : '-' }}</td>
                    <td>{{ r.client || r.recipient || '-' }}</td>
                    <td>
                      <div class="font-weight-medium">
                        {{ r.material || r.item }}
                      </div>
                      <div class="text-caption text-medium-emphasis">
                        {{ r.description }}
                      </div>
                    </td>
                    <td class="text-end font-weight-bold">
                      {{ Number(r.delivered_qty).toLocaleString() }} {{ r.unit }}
                    </td>
                    <td>{{ r.document || '-' }}</td>
                  </tr>
                </tbody>
              </VTable>
            </VCardText>
          </VCard>
        </VWindowItem>
      </VWindow>
    </div>

    <!-- Stato vuoto iniziale o nessun risultato -->
    <VCard
      v-else-if="hasSearched && !isSearching"
      class="text-center pa-10 elevation-1"
      rounded="lg"
    >
      <VAvatar
        color="warning"
        variant="tonal"
        size="64"
        class="mb-4"
      >
        <VIcon
          icon="tabler-zoom-question"
          size="36"
        />
      </VAvatar>
      <h3 class="text-h5 font-weight-bold mb-2">
        {{ t('Hub.NoDataFound', { code: searchCode }) }}
      </h3>
      <p class="text-body-1 text-medium-emphasis max-w-500 mx-auto">
        {{ t('Hub.NoDataHint') }}
      </p>
    </VCard>

    <VCard
      v-else-if="!hasSearched"
      class="text-center pa-12 elevation-1"
      rounded="lg"
    >
      <VAvatar
        color="primary"
        variant="tonal"
        size="72"
        class="mb-4"
      >
        <VIcon
          icon="tabler-topology-star-3"
          size="40"
        />
      </VAvatar>
      <h3 class="text-h5 font-weight-bold mb-2">
        {{ t('Hub.HeroTitle') }}
      </h3>
      <p class="text-body-1 text-medium-emphasis max-w-600 mx-auto">
        {{ t('Hub.HeroText') }}
      </p>
    </VCard>

    <!-- Dialog dettaglio scarti di produzione -->
    <VDialog
      v-model="scartiDialog"
      max-width="900"
      scrollable
    >
      <VCard rounded="lg">
        <VCardItem class="pa-4 border-b">
          <div class="d-flex align-center gap-2">
            <VIcon
              icon="tabler-alert-triangle"
              color="error"
              size="22"
            />
            <span class="text-h6 font-weight-bold">
              {{ t('Hub.ScrapDialogTitle', { lotto: scartiDialogItem?.lotto_out || '-' }) }}
            </span>
          </div>
          <template #append>
            <VBtn
              icon="tabler-x"
              size="small"
              variant="text"
              @click="scartiDialog = false"
            />
          </template>
        </VCardItem>
        <VCardText class="pa-0">
          <VTable
            v-if="scartiDialogItem?.scarti?.length"
            hover
            density="comfortable"
          >
            <thead>
              <tr>
                <th>{{ t('Hub.ColMaterial') }}</th>
                <th>{{ t('Hub.ColTypology') }}</th>
                <th>{{ t('Hub.ColDocument') }}</th>
                <th>{{ t('Hub.ColDate') }}</th>
                <th class="text-end">{{ t('Hub.ColQuantity') }}</th>
                <th>UM</th>
                <th class="text-end">{{ t('Hub.ColValue') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="s in scartiDialogItem.scarti"
                :key="s.id"
              >
                <td>
                  <div class="font-weight-medium">
                    {{ s.materiale }}
                  </div>
                  <div
                    v-if="s.descrizione"
                    class="text-caption text-medium-emphasis"
                  >
                    {{ s.descrizione }}
                  </div>
                </td>
                <td>
                  <VChip
                    size="x-small"
                    color="error"
                    variant="outlined"
                  >
                    {{ s.tipo_movimento }}
                  </VChip>
                </td>
                <td>{{ s.documento_materiale || '-' }}</td>
                <td>{{ s.data_pubblicazione ? moment(s.data_pubblicazione).format('DD/MM/YYYY') : '-' }}</td>
                <td class="text-end font-weight-bold text-error">
                  {{ Number(s.quantita).toLocaleString('it-IT', { maximumFractionDigits: 3 }) }}
                </td>
                <td>{{ s.um }}</td>
                <td class="text-end">
                  € {{ Number(s.importo).toLocaleString('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                </td>
              </tr>
            </tbody>
            <tfoot>
              <tr>
                <td
                  colspan="4"
                  class="text-end font-weight-bold text-medium-emphasis"
                >
                  {{ t('Label.Scarti') }} ({{ scartiDialogItem.scarti_count }})
                </td>
                <td class="text-end font-weight-bold text-error">
                  {{ Number(scartiDialogItem.scarto_qta).toLocaleString('it-IT', { maximumFractionDigits: 3 }) }}
                </td>
                <td />
                <td class="text-end font-weight-bold">
                  € {{ Number(scartiDialogItem.scarto_valore).toLocaleString('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                </td>
              </tr>
            </tfoot>
          </VTable>
          <div
            v-else
            class="text-center pa-6 text-medium-emphasis"
          >
            {{ t('Hub.ScrapNoData') }}
          </div>
        </VCardText>
      </VCard>
    </VDialog>

    <!-- Dialog dettaglio consumi di produzione -->
    <VDialog
      v-model="consumiDialog"
      max-width="950"
      scrollable
    >
      <VCard rounded="lg">
        <VCardItem class="pa-4 border-b">
          <div class="d-flex align-center gap-2">
            <VIcon
              icon="tabler-box-seam"
              color="info"
              size="22"
            />
            <span class="text-h6 font-weight-bold">
              {{ t('Hub.ConsumptionsDialogTitle', { lotto: consumiDialogItem?.lotto_out || '-' }) }}
            </span>
          </div>
          <template #append>
            <VBtn
              icon="tabler-x"
              size="small"
              variant="text"
              @click="consumiDialog = false"
            />
          </template>
        </VCardItem>
        <VCardText class="pa-0">
          <VTable
            v-if="consumiDialogItem?.consumi?.length"
            hover
            density="comfortable"
          >
            <thead>
              <tr>
                <th>{{ t('Hub.ColMaterial') }}</th>
                <th>{{ t('Hub.ColTypology') }}</th>
                <th>{{ t('Hub.ColDocument') }}</th>
                <th>{{ t('Hub.ColDate') }}</th>
                <th class="text-end">{{ t('Hub.ColQuantity') }}</th>
                <th>UM</th>
                <th class="text-end">{{ t('Hub.ColValue') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="c in consumiDialogItem.consumi"
                :key="c.id"
              >
                <td>
                  <div class="font-weight-medium">
                    {{ c.materiale }}
                  </div>
                  <div
                    v-if="c.descrizione"
                    class="text-caption text-medium-emphasis"
                  >
                    {{ c.descrizione }}
                  </div>
                </td>
                <td>
                  <VChip
                    size="x-small"
                    color="info"
                    variant="outlined"
                  >
                    {{ c.tipo_movimento }}
                  </VChip>
                </td>
                <td>{{ c.documento_materiale || '-' }}</td>
                <td>{{ c.data_pubblicazione ? moment(c.data_pubblicazione).format('DD/MM/YYYY') : '-' }}</td>
                <td class="text-end font-weight-bold text-info">
                  {{ Number(c.quantita).toLocaleString('it-IT', { maximumFractionDigits: 3 }) }}
                </td>
                <td>{{ c.um }}</td>
                <td class="text-end">
                  € {{ Number(c.importo).toLocaleString('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                </td>
              </tr>
            </tbody>
            <tfoot>
              <tr>
                <td
                  colspan="4"
                  class="text-end font-weight-bold text-medium-emphasis"
                >
                  {{ t('Label.Consumi') }} ({{ consumiDialogItem.consumi_count }})
                </td>
                <td class="text-end font-weight-bold text-info">
                  {{ Number(consumiDialogItem.consumo_qta).toLocaleString('it-IT', { maximumFractionDigits: 3 }) }}
                </td>
                <td />
                <td class="text-end font-weight-bold">
                  € {{ Number(consumiDialogItem.consumo_valore).toLocaleString('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                </td>
              </tr>
            </tfoot>
          </VTable>
          <div
            v-else
            class="text-center pa-6 text-medium-emphasis"
          >
            {{ t('Hub.ConsumptionsNoData') }}
          </div>
        </VCardText>
      </VCard>
    </VDialog>

    <!-- Dialog dettaglio fermi macchina dell'ordine -->
    <VDialog
      v-model="fermiDialog"
      max-width="700"
      scrollable
    >
      <VCard rounded="lg">
        <VCardItem class="pa-4 border-b">
          <div class="d-flex align-center gap-2">
            <VIcon
              icon="tabler-alert-circle"
              color="warning"
              size="22"
            />
            <span class="text-h6 font-weight-bold">
              {{ t('Hub.FermiDialogTitle') }}
            </span>
          </div>
          <template #append>
            <VBtn
              icon="tabler-x"
              size="small"
              variant="text"
              @click="fermiDialog = false"
            />
          </template>
        </VCardItem>
        <VCardText class="pa-0">
          <VTable
            v-if="strisciateKpi?.fermi_principali?.length"
            hover
            density="comfortable"
          >
            <thead>
              <tr>
                <th>{{ t('Hub.ColReason') }}</th>
                <th class="text-end">{{ t('Hub.ColDowntime') }}</th>
                <th class="text-end">% su Totale Fermi</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="f in strisciateKpi.fermi_principali"
                :key="f.causale"
              >
                <td class="font-weight-medium">
                  {{ f.causale }}
                </td>
                <td class="text-end font-weight-bold">
                  {{ f.minuti }} m
                  <span class="text-caption text-medium-emphasis ms-1">({{ (f.minuti / 60).toFixed(1) }}h)</span>
                </td>
                <td class="text-end">
                  <VChip
                    size="x-small"
                    color="warning"
                    variant="tonal"
                  >
                    {{ strisciateKpi.tempo_fermo_minuti > 0 ? Math.round((f.minuti / strisciateKpi.tempo_fermo_minuti) * 100) : 0 }}%
                  </VChip>
                </td>
              </tr>
            </tbody>
            <tfoot>
              <tr>
                <td class="font-weight-bold">
                  Totale ({{ strisciateKpi.numero_fermi }} fermi)
                </td>
                <td class="text-end font-weight-bold text-error">
                  {{ strisciateKpi.tempo_fermo_minuti }} m
                  <span class="text-caption ms-1">({{ (strisciateKpi.tempo_fermo_minuti / 60).toFixed(1) }}h)</span>
                </td>
                <td class="text-end font-weight-bold">
                  100%
                </td>
              </tr>
            </tfoot>
          </VTable>
          <div
            v-else
            class="text-center pa-6 text-medium-emphasis"
          >
            {{ t('Hub.FermiNoData') }}
          </div>
        </VCardText>
      </VCard>
    </VDialog>
  </div>
</template>

<style scoped>
.search-suggestions-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  z-index: 50;
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  max-height: 280px;
  overflow-y: auto;
}

.suggestion-item:hover {
  background-color: rgba(var(--v-theme-primary), 0.08);
}

.max-w-500 {
  max-width: 500px;
}

.max-w-600 {
  max-width: 600px;
}

.strisciata-evidenziata td {
  background-color: rgba(var(--v-theme-success), 0.12) !important;
}
</style>
