<script lang="ts" setup>
import { useI18n } from 'vue-i18n'

interface Props {
  id: string
}

const props = defineProps<Props>()
const { t } = useI18n()

const loading = ref(false)
const richieste = ref<any[]>([])
const riepilogo = ref<any>(null)
const employeeInfo = ref<any>(null)
const searchTipologia = ref<string | null>(null)
const searchStato = ref<string | null>(null)
const expanded = ref<any[]>([])
const annoSelezionato = ref<number | null>(null)
const anniDisponibili = ref<{ title: string; value: number }[]>([])
const viewMode = ref<'lista' | 'analitico'>('lista')
const expandedMonths = ref<string[]>([])

const mesiNomi = ['Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic']
const mesiNomiCompleti = ['Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno', 'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre']

const tipologiaOptions = [
  { title: 'Tutte', value: null },
  { title: 'Ferie', value: '1' },
  { title: '104', value: '2' },
  { title: 'Permesso', value: '5' },
  { title: 'Malattia', value: '3' },
  { title: 'Infortunio', value: '4' },
  { title: 'Ferie Revocate', value: '101' },
  { title: '104 Revocate', value: '102' },
]

const statoOptions = [
  { title: 'Tutti', value: null },
  { title: 'In Attesa', value: 'pending' },
  { title: 'Approvati', value: 'approved' },
  { title: 'Rifiutati', value: 'rejected' },
]

const headers = [
  { title: 'Data Richiesta', key: 'data_richiesta' },
  { title: 'Tipologia', key: 'tipologia_text' },
  { title: 'Giorni', key: 'giorni_count' },
  { title: 'Ore Totali', key: 'ore_totali' },
  { title: 'Stato', key: 'stato_text' },
  { title: 'Dettagli', key: 'data-table-expand' },
]

const formatDate = (dateStr: string) => {
  if (!dateStr) return '-'
  try {
    return new Date(dateStr).toLocaleDateString('it-IT')
  } catch {
    return dateStr
  }
}

const resolveTipologiaColor = (tipologia: any) => {
  switch (parseInt(tipologia)) {
    case 1: return 'info'
    case 2: return 'secondary'
    case 3: return 'error'
    case 4: return 'error'
    case 5: return 'primary'
    case 101: return 'warning'
    case 102: return 'warning'
    default: return 'grey'
  }
}

const resolveStatoColor = (stato: any) => {
  if (stato === null) return 'warning'
  if (stato === 1) return 'success'
  return 'error'
}

const filteredRichieste = computed(() => {
  return richieste.value.filter((r: any) => {
    if (searchTipologia.value && parseInt(r.tipologia) !== parseInt(searchTipologia.value))
      return false
    if (searchStato.value === 'pending' && r.stato !== null)
      return false
    if (searchStato.value === 'approved' && r.stato !== 1)
      return false
    if (searchStato.value === 'rejected' && r.stato !== 0)
      return false
    return true
  }).map((r: any) => {
    let dettagliFiltrati = r.dettagli || []
    if (searchTipologia.value) {
      const tipInt = parseInt(searchTipologia.value)
      dettagliFiltrati = dettagliFiltrati.filter((d: any) => parseInt(d.tipologia) === tipInt)
    }
    const giorniCount = dettagliFiltrati.length
    const oreTotali = dettagliFiltrati.reduce((sum: number, d: any) => {
      if (!d.ore_richieste) return sum
      const parts = String(d.ore_richieste).split(':')
      return sum + (parseInt(parts[0]) || 0) + (parseInt(parts[1]) || 0) / 60
    }, 0)
    return { ...r, dettagli: dettagliFiltrati, giorni_count: giorniCount, ore_totali: Math.round(oreTotali * 100) / 100 }
  })
})

const fetchRichieste = async () => {
  loading.value = true
  try {
    const url = annoSelezionato.value
      ? `/hr/requests/list_by_employee/${props.id}?anno=${annoSelezionato.value}`
      : `/hr/requests/list_by_employee/${props.id}`
    const { data: resultData } = await useApi<any>(createUrl(url))
    const data = resultData.value
    if (data && data.success) {
      richieste.value = data.richieste || []
      riepilogo.value = data.riepilogo || null
      employeeInfo.value = data.employee || null
      if (data.anni && data.anni.length) {
        const currentYear = new Date().getFullYear()
        anniDisponibili.value = data.anni.map((a: number) => ({ title: String(a), value: a }))
        if (!annoSelezionato.value && data.anni.some((a: number) => a === currentYear)) {
          annoSelezionato.value = currentYear
          return
        }
      }
    }
  } catch (e) {
    console.error('Errore caricamento richieste:', e)
  } finally {
    loading.value = false
  }
}

watch(annoSelezionato, () => {
  fetchRichieste()
})

const parseOre = (oreStr: any): number => {
  if (!oreStr) return 0
  const parts = String(oreStr).split(':')
  return (parseInt(parts[0]) || 0) + (parseInt(parts[1]) || 0) / 60
}

const isTipologiaFerie = (tip: any) => parseInt(tip) === 1 || parseInt(tip) === 101
const isTipologiaPermessi = (tip: any) => parseInt(tip) === 5 || parseInt(tip) === 105
const isTipologia104 = (tip: any) => parseInt(tip) === 2 || parseInt(tip) === 102
const isTipologiaMalattia = (tip: any) => parseInt(tip) === 3
const isTipologiaInfortunio = (tip: any) => parseInt(tip) === 4

const allDettagli = computed(() => {
  const items: any[] = []
  filteredRichieste.value.forEach((r: any) => {
    ;(r.dettagli || []).forEach((d: any) => {
      const date = new Date(d.data)
      const monthKey = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`
      items.push({
        ...d,
        richiesta_id: r.id,
        richiesta_data: r.data_richiesta,
        richiesta_stato: r.stato_text,
        richiesta_note: r.note,
        monthKey,
        monthLabel: `${mesiNomiCompleti[date.getMonth()]} ${date.getFullYear()}`,
        ore_decimali: parseOre(d.ore_richieste),
      })
    })
  })
  return items.sort((a, b) => new Date(a.data).getTime() - new Date(b.data).getTime())
})

const monthlyBreakdown = computed(() => {
  const map = new Map<string, any>()
  allDettagli.value.forEach((d: any) => {
    if (!map.has(d.monthKey)) {
      map.set(d.monthKey, {
        monthKey: d.monthKey,
        monthLabel: d.monthLabel,
        ferie_giorni: 0, ferie_ore: 0,
        permessi_giorni: 0, permessi_ore: 0,
        centoquattro_giorni: 0, centoquattro_ore: 0,
        malattie_giorni: 0, malattie_ore: 0,
        infortuni_giorni: 0, infortuni_ore: 0,
        totale_giorni: 0, totale_ore: 0,
        dettagli: [],
      })
    }
    const m = map.get(d.monthKey)
    m.dettagli.push(d)
    m.totale_giorni++
    m.totale_ore += d.ore_decimali
    if (isTipologiaFerie(d.tipologia)) { m.ferie_giorni++; m.ferie_ore += d.ore_decimali }
    else if (isTipologiaPermessi(d.tipologia)) { m.permessi_giorni++; m.permessi_ore += d.ore_decimali }
    else if (isTipologia104(d.tipologia)) { m.centoquattro_giorni++; m.centoquattro_ore += d.ore_decimali }
    else if (isTipologiaMalattia(d.tipologia)) { m.malattie_giorni++; m.malattie_ore += d.ore_decimali }
    else if (isTipologiaInfortunio(d.tipologia)) { m.infortuni_giorni++; m.infortuni_ore += d.ore_decimali }
  })
  return Array.from(map.values()).sort((a, b) => a.monthKey.localeCompare(b.monthKey))
})

const totaliGenerali = computed(() => {
  const items = allDettagli.value
  const ferie = items.filter(d => isTipologiaFerie(d.tipologia))
  const permessi = items.filter(d => isTipologiaPermessi(d.tipologia))
  const centoquattro = items.filter(d => isTipologia104(d.tipologia))
  const malattie = items.filter(d => isTipologiaMalattia(d.tipologia))
  const infortuni = items.filter(d => isTipologiaInfortunio(d.tipologia))
  return {
    ferie_giorni: ferie.length,
    ferie_ore: Math.round(ferie.reduce((s, d) => s + d.ore_decimali, 0) * 100) / 100,
    permessi_giorni: permessi.length,
    permessi_ore: Math.round(permessi.reduce((s, d) => s + d.ore_decimali, 0) * 100) / 100,
    centoquattro_giorni: centoquattro.length,
    centoquattro_ore: Math.round(centoquattro.reduce((s, d) => s + d.ore_decimali, 0) * 100) / 100,
    malattie_giorni: malattie.length,
    malattie_ore: Math.round(malattie.reduce((s, d) => s + d.ore_decimali, 0) * 100) / 100,
    infortuni_giorni: infortuni.length,
    infortuni_ore: Math.round(infortuni.reduce((s, d) => s + d.ore_decimali, 0) * 100) / 100,
    totale_giorni: items.length,
    totale_ore: Math.round(items.reduce((s, d) => s + d.ore_decimali, 0) * 100) / 100,
  }
})

const toggleMonth = (monthKey: string) => {
  const idx = expandedMonths.value.indexOf(monthKey)
  if (idx >= 0) expandedMonths.value.splice(idx, 1)
  else expandedMonths.value.push(monthKey)
}

const expandAllMonths = () => {
  expandedMonths.value = monthlyBreakdown.value.map(m => m.monthKey)
}

const collapseAllMonths = () => {
  expandedMonths.value = []
}

const exportCSV = () => {
  const rows = filteredRichieste.value
  if (!rows.length) return

  const csvLines: string[] = []
  csvLines.push('Data Richiesta;Tipologia;Giorni;Ore Totali;Stato;Note')
  rows.forEach((r: any) => {
    const data = formatDate(r.data_richiesta)
    const tipologia = r.tipologia_text || ''
    const giorni = r.giorni_count || 0
    const ore = r.ore_totali || 0
    const stato = r.stato_text || ''
    const note = (r.note || '').replace(/;/g, ',').replace(/\n/g, ' ')
    csvLines.push(`${data};${tipologia};${giorni};${ore};${stato};${note}`)
  })

  const csv = csvLines.join('\n')
  const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' })
  const link = document.createElement('a')
  const url = URL.createObjectURL(blob)
  const fileName = employeeInfo.value
    ? `richieste_${employeeInfo.value.matricola}_${annoSelezionato.value || 'tutti'}.csv`
    : 'richieste.csv'
  link.setAttribute('href', url)
  link.setAttribute('download', fileName)
  link.style.visibility = 'hidden'
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

const printReport = () => {
  const printContent = generatePrintHTML()
  const win = window.open('', '_blank', 'width=900,height=700')
  if (!win) return
  win.document.write(printContent)
  win.document.close()
  win.focus()
  setTimeout(() => {
    win.print()
  }, 300)
}

const generatePrintHTML = (): string => {
  const emp = employeeInfo.value || {}
  const riep = riepilogo.value || {}
  const rows = filteredRichieste.value

  const dettagliRows = (r: any): string => {
    if (!r.dettagli || !r.dettagli.length) return '<tr><td colspan="5" style="text-align:center;color:#999;">Nessun dettaglio</td></tr>'
    return r.dettagli.map((d: any) => `
      <tr>
        <td>${formatDate(d.data)}</td>
        <td>${d.tipologia_text || '-'}</td>
        <td>${d.ore_richieste || '-'}</td>
        <td>${d.ora_inizio || '-'}</td>
        <td>${d.ora_fine || '-'}</td>
      </tr>`).join('')
  }

  const richiesteRows = rows.map((r: any) => `
    <tr>
      <td>${formatDate(r.data_richiesta)}</td>
      <td>${r.tipologia_text || '-'}</td>
      <td style="text-align:center;">${r.giorni_count || 0}</td>
      <td style="text-align:center;">${r.ore_totali || 0}</td>
      <td>${r.stato_text || '-'}</td>
    </tr>
    <tr>
      <td colspan="5" style="padding:0;">
        <table class="dettagli-table">
          <thead>
            <tr><th>Data</th><th>Tipologia</th><th>Ore</th><th>Inizio</th><th>Fine</th></tr>
          </thead>
          <tbody>${dettagliRows(r)}</tbody>
        </table>
      </td>
    </tr>`).join('')

  return `<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<title>Report Richieste - ${emp.nome_completo || ''}</title>
<style>
  body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
  h1 { font-size: 20px; margin-bottom: 5px; }
  h2 { font-size: 14px; color: #666; margin-top: 0; }
  .info { margin-bottom: 20px; font-size: 13px; }
  .riepilogo { display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap; }
  .card { border: 1px solid #ddd; border-radius: 6px; padding: 12px 18px; text-align: center; min-width: 120px; }
  .card .label { font-size: 11px; color: #888; text-transform: uppercase; }
  .card .value { font-size: 22px; font-weight: bold; margin-top: 4px; }
  .card .sub { font-size: 11px; color: #aaa; margin-top: 2px; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
  th, td { border: 1px solid #ddd; padding: 6px 10px; font-size: 12px; text-align: left; }
  th { background: #f5f5f5; font-weight: bold; }
  .dettagli-table { margin: 0; }
  .dettagli-table th { background: #fafafa; font-size: 11px; }
  .dettagli-table td { font-size: 11px; }
  @media print { body { margin: 10px; } }
</style>
</head>
<body>
  <h1>Report Richieste</h1>
  <h2>${emp.nome_completo || ''} - Matr. ${emp.matricola || ''} ${annoSelezionato.value ? '- Anno ' + annoSelezionato.value : ''}</h2>
  <div class="info">Generato il ${new Date().toLocaleDateString('it-IT')}</div>
  <div class="riepilogo">
    <div class="card"><div class="label">Ferie</div><div class="value">${riep.ferie || 0}</div><div class="sub">${riep.ferie_giorni || 0} gg / ${riep.ferie_ore || 0} h</div></div>
    <div class="card"><div class="label">Permessi</div><div class="value">${riep.permessi || 0}</div><div class="sub">${riep.permessi_giorni || 0} gg / ${riep.permessi_ore || 0} h</div></div>
    <div class="card"><div class="label">104</div><div class="value">${riep.centoquattro || 0}</div><div class="sub">${riep.centoquattro_giorni || 0} gg / ${riep.centoquattro_ore || 0} h</div></div>
    <div class="card"><div class="label">Malattia</div><div class="value">${riep.malattie || 0}</div><div class="sub">${riep.malattie_giorni || 0} gg / ${riep.malattie_ore || 0} h</div></div>
    <div class="card"><div class="label">Infortunio</div><div class="value">${riep.infortuni || 0}</div><div class="sub">${riep.infortuni_giorni || 0} gg / ${riep.infortuni_ore || 0} h</div></div>
    <div class="card"><div class="label">In Attesa</div><div class="value">${riep.in_attesa || 0}</div></div>
    <div class="card"><div class="label">Approvate</div><div class="value">${riep.approvate || 0}</div></div>
    <div class="card"><div class="label">Rifiutate</div><div class="value">${riep.rifiutate || 0}</div></div>
  </div>
  <table>
    <thead>
      <tr><th>Data Richiesta</th><th>Tipologia</th><th>Giorni</th><th>Ore</th><th>Stato</th></tr>
    </thead>
    <tbody>${richiesteRows}</tbody>
  </table>
</body>
</html>`
}

fetchRichieste()
</script>

<template>
  <div class="d-flex flex-column gap-4">
    <!-- Riepilogo Cards -->
    <VRow v-if="riepilogo" dense>
      <VCol cols="6" sm="4" md="2">
        <VCard variant="outlined" class="border-thin rounded-lg text-center pa-3">
          <VIcon icon="tabler-calendar-event" color="info" size="28" class="mb-1" />
          <div class="text-h5 font-weight-bold">{{ riepilogo.ferie }}</div>
          <div class="text-caption text-medium-emphasis">Ferie</div>
          <div class="text-caption text-disabled">{{ riepilogo.ferie_giorni || 0 }} gg / {{ riepilogo.ferie_ore || 0 }} h</div>
        </VCard>
      </VCol>
      <VCol cols="6" sm="4" md="2">
        <VCard variant="outlined" class="border-thin rounded-lg text-center pa-3">
          <VIcon icon="tabler-clock" color="primary" size="28" class="mb-1" />
          <div class="text-h5 font-weight-bold">{{ riepilogo.permessi }}</div>
          <div class="text-caption text-medium-emphasis">Permessi</div>
          <div class="text-caption text-disabled">{{ riepilogo.permessi_giorni || 0 }} gg / {{ riepilogo.permessi_ore || 0 }} h</div>
        </VCard>
      </VCol>
      <VCol cols="6" sm="4" md="2">
        <VCard variant="outlined" class="border-thin rounded-lg text-center pa-3">
          <VIcon icon="tabler-accessible" color="secondary" size="28" class="mb-1" />
          <div class="text-h5 font-weight-bold">{{ riepilogo.centoquattro }}</div>
          <div class="text-caption text-medium-emphasis">104</div>
          <div class="text-caption text-disabled">{{ riepilogo.centoquattro_giorni || 0 }} gg / {{ riepilogo.centoquattro_ore || 0 }} h</div>
        </VCard>
      </VCol>
      <VCol cols="6" sm="4" md="2">
        <VCard variant="outlined" class="border-thin rounded-lg text-center pa-3">
          <VIcon icon="tabler-virus" color="error" size="28" class="mb-1" />
          <div class="text-h5 font-weight-bold">{{ riepilogo.malattie || 0 }}</div>
          <div class="text-caption text-medium-emphasis">Malattia</div>
          <div class="text-caption text-disabled">{{ riepilogo.malattie_giorni || 0 }} gg / {{ riepilogo.malattie_ore || 0 }} h</div>
        </VCard>
      </VCol>
      <VCol cols="6" sm="4" md="2">
        <VCard variant="outlined" class="border-thin rounded-lg text-center pa-3">
          <VIcon icon="tabler-bandage" color="error" size="28" class="mb-1" />
          <div class="text-h5 font-weight-bold">{{ riepilogo.infortuni || 0 }}</div>
          <div class="text-caption text-medium-emphasis">Infortunio</div>
          <div class="text-caption text-disabled">{{ riepilogo.infortuni_giorni || 0 }} gg / {{ riepilogo.infortuni_ore || 0 }} h</div>
        </VCard>
      </VCol>
      <VCol cols="6" sm="4" md="2">
        <VCard variant="outlined" class="border-thin rounded-lg text-center pa-3">
          <VIcon icon="tabler-hourglass-high" color="warning" size="28" class="mb-1" />
          <div class="text-h5 font-weight-bold">{{ riepilogo.in_attesa }}</div>
          <div class="text-caption text-medium-emphasis">In Attesa</div>
        </VCard>
      </VCol>
    </VRow>

    <!-- Filtri -->
    <VRow dense class="align-center">
      <VCol cols="12" sm="3">
        <AppSelect
          v-model="annoSelezionato"
          :items="anniDisponibili"
          item-title="title"
          item-value="value"
          label="Anno"
          density="compact"
          variant="outlined"
          clearable
        />
      </VCol>
      <VCol cols="12" sm="3">
        <AppSelect
          v-model="searchTipologia"
          :items="tipologiaOptions"
          item-title="title"
          item-value="value"
          label="Filtra per tipologia"
          density="compact"
          variant="outlined"
          clearable
        />
      </VCol>
      <VCol cols="12" sm="3">
        <AppSelect
          v-model="searchStato"
          :items="statoOptions"
          item-title="title"
          item-value="value"
          label="Filtra per stato"
          density="compact"
          variant="outlined"
          clearable
        />
      </VCol>
      <VCol cols="12" sm="3" class="d-flex gap-2 justify-end">
        <VBtnToggle v-model="viewMode" density="compact" variant="outlined" divided>
          <VBtn value="lista" prepend-icon="tabler-list">
            <span class="d-none d-sm-inline">Lista</span>
          </VBtn>
          <VBtn value="analitico" prepend-icon="tabler-chart-bar">
            <span class="d-none d-sm-inline">Analitico</span>
          </VBtn>
        </VBtnToggle>
      </VCol>
    </VRow>

    <!-- VISTA: Lista -->
    <VDataTable
      v-if="viewMode === 'lista'"
      :headers="headers"
      :items="filteredRichieste"
      :loading="loading"
      v-model:expanded="expanded"
      item-key="id"
      show-expand
      density="comfortable"
      class="border-thin rounded-lg"
    >
      <!-- Data Richiesta -->
      <template #item.data_richiesta="{ item }">
        <span class="font-weight-medium">{{ formatDate(item.data_richiesta) }}</span>
      </template>

      <!-- Tipologia -->
      <template #item.tipologia_text="{ item }">
        <VChip
          label
          size="small"
          :color="resolveTipologiaColor(item.tipologia)"
          variant="tonal"
          class="font-weight-medium"
        >
          {{ item.tipologia_text }}
        </VChip>
      </template>

      <!-- Giorni -->
      <template #item.giorni_count="{ item }">
        <span class="text-body-2">{{ item.giorni_count }} gg</span>
      </template>

      <!-- Ore Totali -->
      <template #item.ore_totali="{ item }">
        <span class="text-body-2">{{ item.ore_totali }} h</span>
      </template>

      <!-- Stato -->
      <template #item.stato_text="{ item }">
        <VChip
          label
          size="small"
          :color="resolveStatoColor(item.stato)"
          variant="tonal"
          class="font-weight-medium"
        >
          {{ item.stato_text }}
        </VChip>
      </template>

      <!-- Expanded Row: Dettagli Giorni -->
      <template #expanded-row="{ item }">
        <tr>
          <td :colspan="headers.length + 1" class="pa-4 bg-surface">
            <div v-if="item.dettagli && item.dettagli.length" class="d-flex flex-column gap-2">
              <div class="text-subtitle-2 font-weight-semibold text-medium-emphasis mb-1">
                Giorni richiesti ({{ item.dettagli.length }})
              </div>
              <VTable density="compact" class="border-thin rounded">
                <thead>
                  <tr>
                    <th class="text-left">Data</th>
                    <th class="text-left">Tipologia</th>
                    <th class="text-left">Ore</th>
                    <th class="text-left">Ora Inizio</th>
                    <th class="text-left">Ora Fine</th>
                    <th class="text-left">Confermato</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="d in item.dettagli" :key="d.id">
                    <td>{{ formatDate(d.data) }}</td>
                    <td>
                      <VChip label size="x-small" :color="resolveTipologiaColor(d.tipologia)" variant="tonal">
                        {{ d.tipologia_text }}
                      </VChip>
                    </td>
                    <td>{{ d.ore_richieste || '-' }}</td>
                    <td>{{ d.ora_inizio || '-' }}</td>
                    <td>{{ d.ora_fine || '-' }}</td>
                    <td>
                      <VIcon
                        :icon="d.confermato ? 'tabler-check' : 'tabler-x'"
                        :color="d.confermato ? 'success' : 'error'"
                        size="16"
                      />
                    </td>
                  </tr>
                </tbody>
              </VTable>
              <div v-if="item.note" class="text-body-2 text-medium-emphasis mt-2">
                <strong>Note:</strong> {{ item.note }}
              </div>
            </div>
            <div v-else class="text-body-2 text-medium-emphasis italic">
              Nessun dettaglio disponibile
            </div>
          </td>
        </tr>
      </template>

      <!-- Empty State -->
      <template #no-data>
        <div class="pa-8 text-center">
          <VIcon icon="tabler-calendar-off" size="48" class="text-disabled mb-2" />
          <div class="text-body-1 text-medium-emphasis">Nessuna richiesta trovata per questo dipendente</div>
        </div>
      </template>
    </VDataTable>

    <!-- VISTA: Analitico -->
    <div v-if="viewMode === 'analitico'" class="d-flex flex-column gap-4">
      <!-- Pulsanti azione -->
      <div class="d-flex justify-end gap-2">
        <VBtn variant="outlined" density="comfortable" prepend-icon="tabler-file-spreadsheet" @click="exportCSV" :disabled="!allDettagli.length">
          CSV
        </VBtn>
        <VBtn variant="outlined" density="comfortable" prepend-icon="tabler-printer" @click="printReport" :disabled="!allDettagli.length">
          Stampa
        </VBtn>
      </div>

      <!-- Tabella riepilogo mensile -->
      <VCard variant="outlined" class="border-thin rounded-lg">
        <VCardText class="pa-4">
          <div class="d-flex align-center justify-space-between mb-3">
            <h3 class="text-h6 font-weight-semibold">Riepilogo Mensile</h3>
            <div class="d-flex gap-2">
              <VBtn size="x-small" variant="text" @click="expandAllMonths" :disabled="!monthlyBreakdown.length">Espandi tutto</VBtn>
              <VBtn size="x-small" variant="text" @click="collapseAllMonths" :disabled="!expandedMonths.length">Comprimi tutto</VBtn>
            </div>
          </div>

          <VTable density="comfortable" class="border-thin rounded" v-if="monthlyBreakdown.length">
            <thead>
              <tr>
                <th class="text-left">Mese</th>
                <th class="text-center">Ferie (gg)</th>
                <th class="text-center">Ferie (h)</th>
                <th class="text-center">Perm. (gg)</th>
                <th class="text-center">Perm. (h)</th>
                <th class="text-center">104 (gg)</th>
                <th class="text-center">104 (h)</th>
                <th class="text-center">Mal. (gg)</th>
                <th class="text-center">Mal. (h)</th>
                <th class="text-center">Inf. (gg)</th>
                <th class="text-center">Inf. (h)</th>
                <th class="text-center">Tot (gg)</th>
                <th class="text-center">Tot (h)</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="m in monthlyBreakdown"
                :key="m.monthKey"
                @click="toggleMonth(m.monthKey)"
                style="cursor: pointer;"
                class="month-row"
              >
                <td class="font-weight-medium">
                  <VIcon
                    :icon="expandedMonths.includes(m.monthKey) ? 'tabler-chevron-down' : 'tabler-chevron-right'"
                    size="16"
                    class="me-1"
                  />
                  {{ m.monthLabel }}
                </td>
                <td class="text-center">{{ m.ferie_giorni || '-' }}</td>
                <td class="text-center text-disabled">{{ m.ferie_ore ? m.ferie_ore.toFixed(2) : '-' }}</td>
                <td class="text-center">{{ m.permessi_giorni || '-' }}</td>
                <td class="text-center text-disabled">{{ m.permessi_ore ? m.permessi_ore.toFixed(2) : '-' }}</td>
                <td class="text-center">{{ m.centoquattro_giorni || '-' }}</td>
                <td class="text-center text-disabled">{{ m.centoquattro_ore ? m.centoquattro_ore.toFixed(2) : '-' }}</td>
                <td class="text-center">{{ m.malattie_giorni || '-' }}</td>
                <td class="text-center text-disabled">{{ m.malattie_ore ? m.malattie_ore.toFixed(2) : '-' }}</td>
                <td class="text-center">{{ m.infortuni_giorni || '-' }}</td>
                <td class="text-center text-disabled">{{ m.infortuni_ore ? m.infortuni_ore.toFixed(2) : '-' }}</td>
                <td class="text-center font-weight-bold">{{ m.totale_giorni }}</td>
                <td class="text-center font-weight-bold">{{ m.totale_ore.toFixed(2) }}</td>
              </tr>
            </tbody>
            <tfoot>
              <tr class="font-weight-bold bg-surface">
                <td>TOTALE</td>
                <td class="text-center">{{ totaliGenerali.ferie_giorni }}</td>
                <td class="text-center">{{ totaliGenerali.ferie_ore.toFixed(2) }}</td>
                <td class="text-center">{{ totaliGenerali.permessi_giorni }}</td>
                <td class="text-center">{{ totaliGenerali.permessi_ore.toFixed(2) }}</td>
                <td class="text-center">{{ totaliGenerali.centoquattro_giorni }}</td>
                <td class="text-center">{{ totaliGenerali.centoquattro_ore.toFixed(2) }}</td>
                <td class="text-center">{{ totaliGenerali.malattie_giorni }}</td>
                <td class="text-center">{{ totaliGenerali.malattie_ore.toFixed(2) }}</td>
                <td class="text-center">{{ totaliGenerali.infortuni_giorni }}</td>
                <td class="text-center">{{ totaliGenerali.infortuni_ore.toFixed(2) }}</td>
                <td class="text-center">{{ totaliGenerali.totale_giorni }}</td>
                <td class="text-center">{{ totaliGenerali.totale_ore.toFixed(2) }}</td>
              </tr>
            </tfoot>
          </VTable>

          <div v-else class="pa-8 text-center">
            <VIcon icon="tabler-calendar-off" size="48" class="text-disabled mb-2" />
            <div class="text-body-1 text-medium-emphasis">Nessun dato disponibile per il periodo selezionato</div>
          </div>
        </VCardText>
      </VCard>

      <!-- Dettaglio giornaliero per mese espandibile -->
      <VCard variant="outlined" class="border-thin rounded-lg" v-if="monthlyBreakdown.length">
        <VCardText class="pa-4">
          <h3 class="text-h6 font-weight-semibold mb-3">Dettaglio Giornaliero</h3>

          <div v-for="m in monthlyBreakdown" :key="m.monthKey" class="mb-3">
            <!-- Header mese cliccabile -->
            <div
              class="d-flex align-center gap-2 pa-2 rounded cursor-pointer month-header"
              @click="toggleMonth(m.monthKey)"
            >
              <VIcon
                :icon="expandedMonths.includes(m.monthKey) ? 'tabler-chevron-down' : 'tabler-chevron-right'"
                size="20"
              />
              <span class="text-subtitle-1 font-weight-semibold">{{ m.monthLabel }}</span>
              <VChip label size="small" variant="tonal" color="primary">{{ m.totale_giorni }} giorni</VChip>
              <VChip label size="small" variant="tonal" color="info">{{ m.totale_ore.toFixed(2) }} ore</VChip>
            </div>

            <!-- Tabella dettagli giorni del mese -->
            <VTable v-if="expandedMonths.includes(m.monthKey)" density="compact" class="border-thin rounded mt-1">
              <thead>
                <tr>
                  <th class="text-left">Data</th>
                  <th class="text-left">Giorno</th>
                  <th class="text-left">Tipologia</th>
                  <th class="text-left">Ore</th>
                  <th class="text-left">Ora Inizio</th>
                  <th class="text-left">Ora Fine</th>
                  <th class="text-left">Stato Richiesta</th>
                  <th class="text-center">Confermato</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="d in m.dettagli" :key="d.id">
                  <td class="font-weight-medium">{{ formatDate(d.data) }}</td>
                  <td class="text-disabled">{{ new Date(d.data).toLocaleDateString('it-IT', { weekday: 'short' }) }}</td>
                  <td>
                    <VChip label size="x-small" :color="resolveTipologiaColor(d.tipologia)" variant="tonal">
                      {{ d.tipologia_text }}
                    </VChip>
                  </td>
                  <td>{{ d.ore_richieste || '-' }}</td>
                  <td>{{ d.ora_inizio || '-' }}</td>
                  <td>{{ d.ora_fine || '-' }}</td>
                  <td>
                    <VChip label size="x-small" :color="resolveStatoColor(d.richiesta_stato === 'Approvata' ? 1 : d.richiesta_stato === 'Rifiutata' ? 0 : null)" variant="tonal">
                      {{ d.richiesta_stato }}
                    </VChip>
                  </td>
                  <td class="text-center">
                    <VIcon
                      :icon="d.confermato ? 'tabler-check' : 'tabler-x'"
                      :color="d.confermato ? 'success' : 'error'"
                      size="16"
                    />
                  </td>
                </tr>
              </tbody>
            </VTable>
          </div>
        </VCardText>
      </VCard>
    </div>

    <!-- Pulsanti export per vista Lista -->
    <div v-if="viewMode === 'lista'" class="d-flex justify-end gap-2">
      <VBtn variant="outlined" density="comfortable" prepend-icon="tabler-file-spreadsheet" @click="exportCSV" :disabled="!filteredRichieste.length">
        CSV
      </VBtn>
      <VBtn variant="outlined" density="comfortable" prepend-icon="tabler-printer" @click="printReport" :disabled="!filteredRichieste.length">
        Stampa
      </VBtn>
    </div>

    <LoadingStandBy v-model="loading" />
  </div>
</template>

<style lang="scss" scoped>
.month-row:hover {
  background: rgba(var(--v-theme-primary), 0.04);
}

.month-header:hover {
  background: rgba(var(--v-theme-primary), 0.06);
}
</style>
