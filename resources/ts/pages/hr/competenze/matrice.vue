<script setup lang="ts">
definePage({
  meta: {
    action: 'list',
    subject: 'Competenze',
  },
})

const loading = ref(true)
const matrix = ref<any[]>([])
const columns = ref<any[]>([])
const anno = ref(new Date().getFullYear())
const reparti = ref<string[]>([])
const repartiOptions = ref<any[]>([])

const loadReparti = async () => {
  const { data: resultData } = await useApi<any>(createUrl('/hr/gestione/reparti/get_list'))
  if (resultData.value) {
    repartiOptions.value = resultData.value.map((r: any) => ({
      title: r.reparto,
      value: r.id,
    }))
  }
}


const valutazioneOptions = [
  { title: 'Non richiesta', value: 0 },
  { title: 'Insufficiente', value: 1 },
  { title: 'Sufficiente', value: 2 },
  { title: 'Buona', value: 3 },
  { title: 'Ottima', value: 4 },
]

const valutazioneLabel = (val: number | null) => {
  const item = valutazioneOptions.find(v => v.value === val)
  return item ? item.title : '-'
}

const valutazioneIniziale = (val: number | null) => {
  const labels = ['N', 'I', 'S', 'B', 'O']
  if (val === null || val === undefined) return '-'
  return labels[val] ?? '-'
}

const valutazioneColor = (val: number | null) => {
  if (val === null) return 'grey'
  const colors = ['grey', 'error', 'warning', 'info', 'success']
  return colors[val] || 'grey'
}

const isExpired = (createdAt: string | null) => {
  if (!createdAt) return true
  const expiration = new Date(createdAt)
  expiration.setFullYear(expiration.getFullYear() + 1)
  return expiration < new Date()
}

const cellBgColor = (row: any, activity: any) => {
  const val = row['activity_' + activity.id]
  if (val === null || val === undefined) return 'transparent'
  const createdAt = row['created_at_' + activity.id]
  return isExpired(createdAt) ? 'rgba(231, 8, 8, 0.15)' : 'transparent'
}

const loadMatrix = async () => {
  loading.value = true
  const { data: resultData } = await useApi<any>(createUrl('/hr/competenze/valutazioni/matrix', {
    query: {
      anno: anno.value,
      reparti: reparti.value.length ? reparti.value.join(',') : undefined,
    },
  }))

  if (resultData.value) {
    matrix.value = resultData.value.matrix
    columns.value = resultData.value.activities
  }
  else {
    matrix.value = []
    columns.value = []
  }
  loading.value = false
}

const exportExcel = () => {
  const url = createUrl('/hr/competenze/valutazioni/matrix_export', {
    query: {
      anno: anno.value,
      reparti: reparti.value.length ? reparti.value.join(',') : undefined,
    },
  }).value

  const link = document.createElement('a')
  link.href = url
  link.download = `matrice_competenze_${anno.value}.xlsx`
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

onMounted(() => {
  loadReparti()
  loadMatrix()
})
</script>

<template>
  <div>
    <VCard>
      <VCardTitle class="d-flex align-center flex-wrap gap-4 py-4">
        <span class="text-h6">Matrice Competenze</span>
        <VSpacer />
        <VBtn
          color="success"
          prepend-icon="tabler-file-excel"
          @click="exportExcel"
        >
          Excel
        </VBtn>
        <VSelect
          v-model="reparti"
          :items="repartiOptions"
          item-title="title"
          item-value="value"
          label="Reparti"
          density="compact"
          variant="outlined"
          multiple
          chips
          clearable
          style="width: 300px"
          @update:model-value="loadMatrix"
        />
        <AppTextField
          v-model="anno"
          type="number"
          label="Anno"
          density="compact"
          style="width: 120px"
          @update:model-value="loadMatrix"
        />
      </VCardTitle>
      <VDivider />
      <VCardText>
        <div v-if="loading" class="text-center py-4">
          <VProgressCircular indeterminate color="primary" />
        </div>
        <div v-else class="matrix-container">
          <VTable density="compact" class="team-matrix-table">
            <thead>
              <tr>
                <th class="text-left sticky-column employee-header">Dipendente</th>
                <th
                  v-for="activity in columns"
                  :key="activity.id"
                  class="text-center activity-header"
                >
                  <div class="rotated-header text-caption">
                    {{ activity.attivita }}
                  </div>
                  <div class="text-caption font-weight-bold mt-1">
                    {{ valutazioneIniziale(activity.valutazione_ideale) }}
                  </div>
                </th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="row in matrix"
                :key="row.employee_id"
              >
                <td class="sticky-column font-weight-medium employee-cell">
                  {{ row.nome_completo }}
                </td>
                <td
                  v-for="activity in columns"
                  :key="activity.id"
                  class="text-center px-1"
                  :style="{ backgroundColor: cellBgColor(row, activity) }"
                >
                  <VChip
                    label
                    size="small"
                    variant="tonal"
                    :color="valutazioneColor(row['activity_' + activity.id])"
                    class="text-caption"
                  >
                    {{ valutazioneLabel(row['activity_' + activity.id]) }}
                  </VChip>
                </td>
              </tr>
            </tbody>
          </VTable>
        </div>
      </VCardText>
    </VCard>
  </div>
</template>

<style scoped>
.matrix-container {
  overflow: auto;
  max-height: calc(100vh - 200px);
}

.team-matrix-table {
  table-layout: fixed;
  min-width: 100%;
}

.team-matrix-table thead {
  position: sticky;
  top: 0;
  z-index: 3;
}

.team-matrix-table th,
.team-matrix-table td {
  padding: 4px 2px;
  border-right: 1px solid rgba(255, 255, 255, 0.05);
}

.team-matrix-table th:last-child,
.team-matrix-table td:last-child {
  border-right: none;
}

.employee-header,
.employee-cell {
  width: 180px;
  min-width: 180px;
  max-width: 180px;
  position: sticky;
  left: 0;
  z-index: 2;
  background: inherit;
}

.activity-header {
  width: 120px;
  min-width: 120px;
  max-width: 120px;
  vertical-align: bottom;
  height: 100px;
}

.rotated-header {
  writing-mode: vertical-rl;
  transform: rotate(180deg);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-height: 110px;
  margin: 0 auto;
  font-size: 10px;
  line-height: 1.2;
}
</style>
