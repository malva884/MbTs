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
const itemsPerPage = ref(60)

const editing = ref('')
const editValue = ref<any>(null)

const isSnackbarVisible = ref(false)
const message = ref('')
const color = ref('')

const euro = new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' })

const canEdit = computed(() =>
  can(DefineAbilities.it_workprogress_edit.action, DefineAbilities.it_workprogress_edit.subject),
)

const headers = [
  { title: 'Order', key: 'order', sortable: false },
  { title: 'Corr. costo M.P.', key: 'total_material', sortable: false },
  { title: 'Corr. costo tot. cons.', key: 'total_consuntivo', sortable: false },
  { title: 'Motivo correzione', key: 'note', sortable: false },
]

const isEditing = (item: any, col: string) => editing.value === `${item.id}-${col}`

const startEdit = (item: any, col: string) => {
  if (!canEdit.value)
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
    const res = await $api('/ws/summary/update', {
      method: 'POST',
      body: { id: item.id, column: col, value: newValue },
    })

    if (res.success)
      item[col] = newValue
  }
  catch (e: any) {
    message.value = e?.data?.message || 'Errore aggiornamento'
    color.value = 'error'
    isSnackbarVisible.value = true
  }
}

const loadRows = async () => {
  loading.value = true

  const { data } = await useApi<any>(`/ws/summary/${headId}`)

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
  a.download = `riepilogo_${headId}.csv`
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
          <VIcon icon="tabler-list-details" size="24" color="primary" />
          <div>
            <div class="text-h6 font-weight-medium">Riepilogo Corso Lavori</div>
            <div class="text-caption text-medium-emphasis">
              {{ head ? moment(head.created_at).format('DD/MM/YYYY HH:mm') : '' }}
            </div>
          </div>
        </div>
        <div class="d-flex align-center gap-2">
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
            :to="{ name: 'it-workstatus' }"
          >
            Lista
          </VBtn>
        </div>
      </VCardText>
      <VDivider />

      <VCardText class="pa-4">
        <VRow>
          <VCol cols="12" md="7">
            <VDataTable
              v-model:items-per-page="itemsPerPage"
              :headers="headers"
              :items="rows"
              :loading="loading"
              density="compact"
              hover
            >
              <template #no-data>
                <div class="py-10 text-center">
                  <VIcon icon="tabler-list-details" size="40" class="text-disabled mb-2" />
                  <p class="text-body-1 text-disabled mb-0">Nessun riepilogo disponibile: eseguire il calcolo dal dettaglio</p>
                </div>
              </template>

              <template #item.order="{ item }">
                <span class="font-weight-bold">{{ item.order }}</span>
              </template>

              <template #item.total_material="{ item }">
                {{ euro.format(item.total_material) }}
              </template>

              <template #item.total_consuntivo="{ item }">
                {{ euro.format(item.total_consuntivo) }}
              </template>

              <template #item.note="{ item }">
                <div
                  v-if="!isEditing(item, 'note')"
                  :class="{ 'ws-editable': canEdit }"
                  style="min-width: 140px"
                  @click="startEdit(item, 'note')"
                >
                  {{ item.note }}
                </div>
                <VTextField
                  v-else
                  v-model="editValue"
                  density="compact"
                  variant="underlined"
                  hide-details
                  autofocus
                  @blur="saveEdit(item, 'note')"
                  @keyup.enter="saveEdit(item, 'note')"
                  @keyup.esc="cancelEdit"
                />
              </template>
            </VDataTable>
          </VCol>

          <VCol cols="12" md="5">
            <VCard variant="outlined">
              <VCardTitle class="text-base">Totali Stato Lavori</VCardTitle>
              <VTable density="compact">
                <tbody>
                  <tr class="bg-secondary">
                    <td>{{ head ? euro.format((head.total_raw_material || 0) - (head.total_final_mp || 0)) : '' }}</td>
                    <td>{{ head ? euro.format((head.raw_material_machine_manpower_cost || 0) - (head.total_final || 0)) : '' }}</td>
                    <td class="font-weight-medium">Variazione</td>
                  </tr>
                  <tr>
                    <td />
                    <td />
                    <td class="text-medium-emphasis">Ordini di sperimentazione</td>
                  </tr>
                  <tr class="bg-warning">
                    <td>{{ head ? euro.format(head.total_raw_material) : '' }}</td>
                    <td>{{ head ? euro.format(head.raw_material_machine_manpower_cost) : '' }}</td>
                    <td class="font-weight-medium">Valore precedente</td>
                  </tr>
                  <tr class="bg-success">
                    <td>{{ head ? euro.format(head.total_final_mp) : '' }}</td>
                    <td>{{ head ? euro.format(head.total_final) : '' }}</td>
                    <td class="font-weight-medium">Valore finale</td>
                  </tr>
                </tbody>
              </VTable>
            </VCard>
          </VCol>
        </VRow>
      </VCardText>
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
  background: rgba(var(--v-theme-primary), 0.12);
}
</style>
