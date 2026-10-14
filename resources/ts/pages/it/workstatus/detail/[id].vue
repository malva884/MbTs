<script setup lang="ts">
import { VDataTable } from 'vuetify/labs/VDataTable'
import { can } from '@layouts/plugins/casl'
import DefineAbilities from '@/plugins/casl/DefineAbilities'
import moment from 'moment/moment'

definePage({
  meta: {
    action: 'read',
    subject: 'It-Work-Progress',
  },
})

const route = useRoute()
const headId = route.params.id as string

const head = ref<any>(null)
const rows = ref<any[]>([])
const loading = ref(true)
const allRows = ref(false)
const search = ref('')
const itemsPerPage = ref(60)

const editing = ref('')
const editValue = ref<any>(null)

const isDialogLoading = ref(false)
const dialogMessage = ref('Please stand by')
const isSnackbarVisible = ref(false)
const message = ref('')
const color = ref('')

const num = (v: any) => parseFloat(v) || 0
const fmt = (v: any) => (v === null || v === undefined || v === '') ? '' : num(v).toFixed(2)
const fmtCoeff = (v: any) => (v === null || v === undefined || v === '') ? '' : String(num(v))
const hasCoeff = (v: any) => v !== null && v !== undefined && v !== ''

const canEdit = computed(() =>
  can(DefineAbilities.it_workprogress_edit.action, DefineAbilities.it_workprogress_edit.subject),
)

const STORAGE_KEY = 'ws-detail-hidden-cols'
const hiddenCols = ref<string[]>([])

watch(hiddenCols, v => {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(v))
}, { deep: true })

const headers = [
  { title: 'Ordine', key: 'order', sortable: false },
  { title: 'Prodotto', key: 'material_1', sortable: false },
  { title: 'Qtà ordine', key: 'total_order_quantity', sortable: false },
  { title: 'Lav.', key: 'operation_activity', sortable: false },
  { title: 'Macchina', key: 'work_center', sortable: false },
  { title: 'Costo Macchina', key: 'work_center_cost', sortable: false },
  { title: 'Qtà prod.', key: 'quantity_1', sortable: false },
  { title: 'Qtà conf.', key: 'confirmed_quantity', sortable: false },
  { title: 'Qtà prev. fase', key: 'qty_perv_fase', sortable: false },
  { title: 'Ore macchina', key: 'activity_machine_hours', sortable: false },
  { title: 'Macchina Totale', key: 'machine_cost', sortable: false },
  { title: 'Ore Mano.', key: 'activity_manpower_hours', sortable: false },
  { title: 'Manodopera', key: 'manpower_cost', sortable: false },
  { title: 'Macchina + Mano.', key: 'machine_cost_manpower', sortable: false },
  { title: 'Coefficiente', key: 'calculation_1', sortable: false },
  { title: 'Cons. Lavorazione', key: 'result_1', sortable: false },
  { title: 'Material', key: 'material_2', sortable: false },
  { title: 'Descrizione', key: 'material_description_2', sortable: false },
  { title: 'Qtà richiesta', key: 'requirement_quantity', sortable: false },
  { title: 'UM', key: 'base_unit_measure', sortable: false },
  //{ title: 'Qtà utilizzata', key: 'quantity_2', sortable: false },
  //{ title: 'Costo mat.', key: 'cost_material', sortable: false },
  { title: 'Materiale', key: 'material_cost', sortable: false },
  { title: 'Coefficiente', key: 'calculation_2', sortable: false },
  { title: 'Cons. materiale', key: 'result_2', sortable: false },
  //{ title: 'UM fase', key: 'um_fase', sortable: false },
]

const visibleHeaders = computed(() => headers.filter(h => !hiddenCols.value.includes(h.key)))

// Indici (1-based) delle colonne coefficiente fra le colonne visibili
const coeffIdx1 = computed(() => visibleHeaders.value.findIndex(h => h.key === 'calculation_1') + 1)
const coeffIdx2 = computed(() => visibleHeaders.value.findIndex(h => h.key === 'calculation_2') + 1)

const coeffStyle = computed(() => {
  const rules: string[] = []

  if (coeffIdx1.value > 0)
    rules.push(`.ws-table th:nth-child(${coeffIdx1.value}), .ws-table td:nth-child(${coeffIdx1.value})`)
  if (coeffIdx2.value > 0)
    rules.push(`.ws-table th:nth-child(${coeffIdx2.value}), .ws-table td:nth-child(${coeffIdx2.value})`)

  return rules.length ? `${rules.join(', ')} { background: rgba(var(--v-theme-success), 0.18); }` : ''
})

const editable = (item: any, col: string): boolean => {
  if (!canEdit.value)
    return false

  switch (col) {
    case 'quantity_1':
      return !!item.order
    case 'calculation_1':
      return num(item.machine_cost_manpower) > 0
    case 'calculation_2':
      return num(item.material_cost) > 0
    case 'quantity_2':
      return num(item.requirement_quantity) > 0
    case 'qty_perv_fase':
      return !!item.material_2
    default:
      return false
  }
}

const isEditing = (item: any, col: string) => editing.value === `${item.id}-${col}`

const startEdit = (item: any, col: string) => {
  if (!editable(item, col))
    return

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
    const res = await $api('/ws/rows/update', {
      method: 'POST',
      body: { id: item.id, column: col, value: newValue },
    })

    if (res.success) {
      item[col] = newValue
      if (res.material_cost !== undefined && res.material_cost !== null)
        item.material_cost = res.material_cost
      if (res.result_1 !== undefined && res.result_1 !== null)
        item.result_1 = res.result_1
      if (res.result_2 !== undefined && res.result_2 !== null)
        item.result_2 = res.result_2
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

  const endpoint = allRows.value ? `/ws/rows-all/${headId}` : `/ws/rows/${headId}`
  const { data } = await useApi<any>(endpoint)

  rows.value = data.value || []
  loading.value = false
}

const loadHead = async () => {
  const { data } = await useApi<any>(`/ws/head/${headId}`)

  head.value = data.value
}

const toggleRows = () => {
  allRows.value = !allRows.value
  loadRows()
}

const calcola = async () => {
  isDialogLoading.value = true
  dialogMessage.value = 'Calcolo consuntivo in corso...'

  try {
    const res = await $api(`/ws/rows/final/${headId}`, { method: 'POST' })

    message.value = res.message || 'Calcolo completato'
    color.value = res.color || 'success'
    isSnackbarVisible.value = true

    await Promise.all([loadHead(), loadRows()])
  }
  catch (e: any) {
    message.value = e?.data?.message || 'Errore calcolo'
    color.value = 'error'
    isSnackbarVisible.value = true
  }

  isDialogLoading.value = false
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
  a.download = `workstatus_${headId}.csv`
  a.click()
  URL.revokeObjectURL(url)
}

const resolveStatus = (status: number) => {
  if (status === 2)
    return { color: 'success', text: 'Completato' }
  else if (status === 1)
    return { color: 'primary', text: 'In Lavorazione' }
  else if (status === 3)
    return { color: 'error', text: 'Errore' }
  else
    return { color: 'secondary', text: 'Import' }
}

hiddenCols.value = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]')

loadHead()
loadRows()
</script>

<template>
  <div class="workspace-container w-100 d-flex flex-column pa-4 gap-3">
    <VSnackbar v-model="isSnackbarVisible" transition="scroll-y-reverse-transition" location="top center" :timeout="4000">
      {{ $t(message) }}
    </VSnackbar>

    <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
      <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
        <div class="d-flex align-center gap-2">
          <VIcon icon="tabler-progress" size="24" color="primary" />
          <div>
            <div class="text-h6 font-weight-medium">
              Corso Lavori
              <VChip
                v-if="head"
                :color="resolveStatus(head.status).color"
                size="x-small"
                class="ml-2"
              >
                {{ resolveStatus(head.status).text }}
              </VChip>
            </div>
            <div class="text-caption text-medium-emphasis">
              {{ head ? moment(head.created_at).format('DD/MM/YYYY HH:mm') : '' }} - {{ rows.length }} righe
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
            prepend-icon="tabler-eye"
            @click="toggleRows"
          >
            {{ allRows ? 'Righe da calcolare' : 'Tutte le righe' }}
          </VBtn>
          <VMenu :close-on-content-click="false">
            <template #activator="{ props }">
              <VBtn
                v-bind="props"
                variant="outlined"
                color="secondary"
                density="comfortable"
                prepend-icon="tabler-columns"
              >
                Colonne
              </VBtn>
            </template>
            <VCard min-width="230">
              <VList density="compact" class="py-1">
                <VListItem v-for="h in headers" :key="h.key">
                  <VCheckbox
                    v-model="hiddenCols"
                    :value="h.key"
                    :label="h.title"
                    density="compact"
                    hide-details
                  />
                </VListItem>
              </VList>
            </VCard>
          </VMenu>
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
            v-if="canEdit"
            color="primary"
            density="comfortable"
            prepend-icon="tabler-calculator"
            @click="calcola"
          >
            Calcola
          </VBtn>
          <VBtn
            prepend-icon="tabler-arrow-left"
            variant="outlined"
            color="secondary"
            density="comfortable"
            :to="{ name: 'it-workstatus' }"
          >
            Lista
          </VBtn>
        </div>
      </VCardText>
      <VDivider />

      <VDataTable
        class="ws-table"
        v-model:items-per-page="itemsPerPage"
        :headers="visibleHeaders"
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
            <VIcon icon="tabler-progress" size="40" class="text-disabled mb-2" />
            <p class="text-body-1 text-disabled mb-0">Nessuna riga trovata</p>
          </div>
        </template>

        <template #item.order="{ item }">
          <span
            v-if="item.order"
            class="font-weight-bold"
            style="background: rgba(0, 207, 232, 0.25); padding: 0 6px; border-radius: 4px"
          >{{ item.order }}</span>
        </template>

        <!-- Celle editabili -->
        <template #item.quantity_1="{ item }">
          <div
            v-if="!isEditing(item, 'quantity_1')"
            :class="{ 'ws-editable': editable(item, 'quantity_1') }"
            @click="startEdit(item, 'quantity_1')"
          >
            {{ item.material_1 ? item.quantity_1 : '' }}
          </div>
          <VTextField
            v-else
            v-model="editValue"
            type="number"
            density="compact"
            variant="underlined"
            hide-details
            autofocus
            @blur="saveEdit(item, 'quantity_1')"
            @keyup.enter="saveEdit(item, 'quantity_1')"
            @keyup.esc="cancelEdit"
          />
        </template>

        <template #item.calculation_1="{ item }">
          <div
            v-if="!isEditing(item, 'calculation_1')"
            :class="{
              'ws-editable': editable(item, 'calculation_1'),
              'ws-coeff-ko': !hasCoeff(item.calculation_1) && num(item.machine_cost_manpower) > 0,
            }"
            @click="startEdit(item, 'calculation_1')"
          >
            {{ fmtCoeff(item.calculation_1) }}
          </div>
          <VTextField
            v-else
            v-model="editValue"
            type="number"
            step="0.001"
            density="compact"
            variant="underlined"
            hide-details
            autofocus
            @blur="saveEdit(item, 'calculation_1')"
            @keyup.enter="saveEdit(item, 'calculation_1')"
            @keyup.esc="cancelEdit"
          />
        </template>

        <template #item.calculation_2="{ item }">
          <div
            v-if="!isEditing(item, 'calculation_2')"
            :class="{
              'ws-editable': editable(item, 'calculation_2'),
              'ws-coeff-ko': !hasCoeff(item.calculation_2) && num(item.material_cost) > 0,
            }"
            @click="startEdit(item, 'calculation_2')"
          >
            {{ fmtCoeff(item.calculation_2) }}
          </div>
          <VTextField
            v-else
            v-model="editValue"
            type="number"
            step="0.001"
            density="compact"
            variant="underlined"
            hide-details
            autofocus
            @blur="saveEdit(item, 'calculation_2')"
            @keyup.enter="saveEdit(item, 'calculation_2')"
            @keyup.esc="cancelEdit"
          />
        </template>

        <template #item.quantity_2="{ item }">
          <div
            v-if="!isEditing(item, 'quantity_2')"
            :class="{ 'ws-editable': editable(item, 'quantity_2') }"
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

        <template #item.qty_perv_fase="{ item }">
          <div
            v-if="!isEditing(item, 'qty_perv_fase')"
            :class="{ 'ws-editable': editable(item, 'qty_perv_fase') }"
            @click="startEdit(item, 'qty_perv_fase')"
          >
            {{ item.qty_perv_fase }}
          </div>
          <VTextField
            v-else
            v-model="editValue"
            type="number"
            density="compact"
            variant="underlined"
            hide-details
            autofocus
            @blur="saveEdit(item, 'qty_perv_fase')"
            @keyup.enter="saveEdit(item, 'qty_perv_fase')"
            @keyup.esc="cancelEdit"
          />
        </template>

        <!-- Celle numeriche formattate -->
        <template #item.total_order_quantity="{ item }">{{ item.material_1 ? fmt(item.total_order_quantity) : '' }}</template>
        <template #item.confirmed_quantity="{ item }">{{ fmt(item.confirmed_quantity) }}</template>
        <template #item.work_center_cost="{ item }">{{ fmt(item.work_center_cost) }}</template>
        <template #item.activity_machine_hours="{ item }">{{ fmt(item.activity_machine_hours) }}</template>
        <template #item.machine_cost="{ item }">{{ fmt(item.machine_cost) }}</template>
        <template #item.activity_manpower_hours="{ item }">{{ fmt(item.activity_manpower_hours) }}</template>
        <template #item.manpower_cost="{ item }">{{ fmt(item.manpower_cost) }}</template>
        <template #item.machine_cost_manpower="{ item }">{{ fmt(item.machine_cost_manpower) }}</template>
        <template #item.result_1="{ item }">{{ editable(item, 'calculation_1') ? fmt(item.result_1) : '' }}</template>
        <template #item.requirement_quantity="{ item }">{{ fmt(item.requirement_quantity) }}</template>
        <template #item.cost_material="{ item }">{{ fmt(item.cost_material) }}</template>
        <template #item.material_cost="{ item }">{{ fmt(item.material_cost) }}</template>
        <template #item.result_2="{ item }">{{ fmt(item.result_2) }}</template>
      </VDataTable>
    </VCard>
  </div>

  <!-- Dialog Loading -->
  <VDialog v-model="isDialogLoading" width="300" persistent>
    <VCard color="primary" width="300">
      <VCardText class="pt-3">
        <span class="ml-4 mb-3">{{ dialogMessage }}</span>
        <VProgressLinear :size="40" color="warning" class="mt-3" indeterminate />
      </VCardText>
    </VCard>
  </VDialog>
</template>

<style scoped>
.ws-editable {
  min-height: 24px;
  cursor: text;
  border-radius: 4px;
}

.ws-editable:hover {
  background: rgba(var(--v-theme-primary), 0.12);
}

/* Coefficiente atteso ma non calcolato: cella rossa */
.ws-table :deep(td:has(.ws-coeff-ko)) {
  background: rgba(var(--v-theme-error), 0.22);
}
</style>
