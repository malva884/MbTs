<script setup lang="ts">
import { VDataTable } from 'vuetify/labs/VDataTable'
import moment from 'moment/moment'

definePage({
  meta: {
    action: 'edit',
    subject: 'It-Work-Progress',
  },
})

const route = useRoute()
const headId = route.params.id as string

const head = ref<any>(null)
const rows = ref<any[]>([])
const loading = ref(true)
const search = ref('')
const itemsPerPage = ref(60)

const editing = ref('')
const editValue = ref<any>(null)

const isSnackbarVisible = ref(false)
const message = ref('')
const color = ref('')

const num = (v: any) => parseFloat(v) || 0
const fmt = (v: any) => (v === null || v === undefined || v === '') ? '' : num(v).toFixed(2)

const headers = [
  { title: 'Ordine', key: 'order', sortable: false },
  { title: 'Codice Articolo', key: 'material_2', sortable: false },
  { title: 'Descrizione', key: 'material_description_2', sortable: false },
  { title: 'Quantità richiesta', key: 'requirement_quantity', sortable: false },
  { title: 'UM', key: 'base_unit_measure', sortable: false },
  { title: 'Quantità utilizzata', key: 'quantity_2', sortable: false },
  { title: 'Costo Materiale', key: 'cost_material', sortable: false },
  { title: 'Totale', key: 'material_cost', sortable: false },
]

const isEditing = (item: any, col: string) => editing.value === `${item.id}-${col}`

const startEdit = (item: any, col: string) => {
  editing.value = `${item.id}-${col}`
  editValue.value = item[col]
}

const cancelEdit = () => {
  editing.value = ''
}

const saveEdit = async (item: any, col: string) => {
  const newValue = editValue.value

  editing.value = ''

  if (newValue === item[col])
    return

  try {
    const res = await $api('/ws/rows/update-check', {
      method: 'POST',
      body: { id: item.id, column: col, value: newValue },
    })

    if (res.success) {
      item[col] = newValue
      if (res.material_cost !== undefined && res.material_cost !== null)
        item.material_cost = res.material_cost

      // Riga completata: esce dai prelievi mancanti
      if (res.check_row === false)
        rows.value = rows.value.filter(r => r.id !== item.id)
    }
  }
  catch (e: any) {
    message.value = e?.data?.message || 'Errore aggiornamento cella'
    color.value = 'error'
    isSnackbarVisible.value = true
  }
}

const loadRows = async () => {
  loading.value = true

  const { data } = await useApi<any>(`/ws/rows-check/${headId}`)

  rows.value = data.value || []
  loading.value = false
}

const loadHead = async () => {
  const { data } = await useApi<any>(`/ws/head/${headId}`)

  head.value = data.value
}

const csvCell = (v: any) => {
  const s = (v ?? '').toString().replace(/;/g, ',')

  // Valori numerici: separatore decimale a virgola (Excel italiano)
  return s !== '' && !isNaN(Number(s)) ? s.replace('.', ',') : s
}

const exportCsv = () => {
  const cols = headers.map(h => h.key)
  const lines = [headers.map(h => h.title).join(';')]

  for (const row of rows.value)
    lines.push(cols.map(c => csvCell(row[c])).join(';'))

  const blob = new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')

  a.href = url
  a.download = `prelievi_mancanti_${headId}.csv`
  a.click()
  URL.revokeObjectURL(url)
}

loadHead()
loadRows()
</script>

<template>
  <div class="workspace-container w-100 d-flex flex-column pa-4 gap-3">
    <VSnackbar v-model="isSnackbarVisible" transition="scroll-y-reverse-transition" location="top center" :timeout="4000">
      {{ message }}
    </VSnackbar>

    <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
      <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
        <div class="d-flex align-center gap-2">
          <VIcon icon="tabler-package" size="24" color="warning" />
          <div>
            <div class="text-h6 font-weight-medium">Prelievi Mancanti</div>
            <div class="text-caption text-medium-emphasis">
              {{ head ? moment(head.created_at).format('DD/MM/YYYY HH:mm') : '' }} - {{ rows.length }} righe da completare
            </div>
          </div>
        </div>
        <div class="d-flex align-center gap-2 flex-wrap">
          <AppTextField
            v-model="search"
            placeholder="Search..."
            density="compact"
            hide-details
            prepend-inner-icon="tabler-search"
            style="min-width: 200px"
            clearable
          />
          <VBtn
            variant="outlined"
            color="secondary"
            density="comfortable"
            prepend-icon="tabler-file-export"
            @click="exportCsv"
          >
            Export CSV
          </VBtn>
          <VBtn
            prepend-icon="tabler-arrow-left"
            variant="outlined"
            color="secondary"
            density="comfortable"
            :to="{ name: 'it-workstatus-detail-id', params: { id: headId } }"
          >
            Dettaglio
          </VBtn>
        </div>
      </VCardText>
      <VDivider />

      <VDataTable
        v-model:items-per-page="itemsPerPage"
        :headers="headers"
        :items="rows"
        :search="search"
        :loading="loading"
        density="compact"
        hover
        fixed-header
        height="70vh"
      >
        <template #no-data>
          <div class="py-10 text-center">
            <VIcon icon="tabler-check" size="40" class="text-success mb-2" />
            <p class="text-body-1 text-disabled mb-0">Nessun prelievo mancante</p>
          </div>
        </template>

        <template #item.order="{ item }">
          <span class="font-weight-bold">{{ item.father || item.order }}</span>
        </template>

        <template #item.requirement_quantity="{ item }">{{ fmt(item.requirement_quantity) }}</template>
        <template #item.cost_material="{ item }">{{ fmt(item.cost_material) }}</template>
        <template #item.material_cost="{ item }">{{ fmt(item.material_cost) }}</template>

        <template #item.quantity_2="{ item }">
          <div
            v-if="!isEditing(item, 'quantity_2')"
            class="ws-editable"
            @click="startEdit(item, 'quantity_2')"
          >
            {{ item.quantity_2 }}
          </div>
          <VTextField
            v-else
            v-model="editValue"
            type="number"
            density="compact"
            variant="underlined"
            hide-details
            autofocus
            @blur="saveEdit(item, 'quantity_2')"
            @keyup.enter="saveEdit(item, 'quantity_2')"
            @keyup.esc="cancelEdit"
          />
        </template>
      </VDataTable>
    </VCard>
  </div>
</template>

<style scoped>
.ws-editable {
  min-height: 24px;
  cursor: text;
  border-radius: 4px;
}

.ws-editable:hover {
  background: rgba(var(--v-theme-warning), 0.12);
}
</style>
