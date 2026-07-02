<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'

definePage({
  meta: {
    action: 'list',
    subject: 'Competenze',
  },
})

const loading = ref(true)
const teamItems = ref<any[]>([])
const evalDialog = ref(false)
const isLoading = ref(false)
const isSnackbarScrollReverseVisible = ref(false)
const message = ref('')
const color = ref('')
const selectedEmployee = ref<any>(null)
const employeeData = ref<any>(null)
const anno = ref(new Date().getFullYear())
const evalItems = ref<any[]>([])
const activeTab = ref('singolo')
const matrixLoading = ref(false)
const teamMatrix = ref<any[]>([])
const matrixColumns = ref<any[]>([])

const valutazioneOptions = [
  { title: 'Non richiesta', value: 0 },
  { title: 'Insufficiente', value: 1 },
  { title: 'Sufficiente', value: 2 },
  { title: 'Buona', value: 3 },
  { title: 'Ottima', value: 4 },
]

const valutazioneLabel = (val: number) => {
  const item = valutazioneOptions.find(v => v.value === val)
  return item ? item.title : '-'
}

const valutazioneColor = (val: number | null) => {
  if (val === null) return 'grey'
  const colors = ['grey', 'error', 'warning', 'info', 'success']
  return colors[val] || 'grey'
}

const getValutazioneDisplay = (val: number | null) => {
  if (val === null) return null
  const item = valutazioneOptions.find(v => v.value === val)
  return item ? item : valutazioneOptions[0]
}

const valutazioneIniziale = (val: number | null) => {
  const labels = ['N', 'I', 'S', 'B', 'O']
  if (val === null || val === undefined) return ''
  return labels[val] ?? ''
}

const valutazioneBgColor = (val: number | null, ideale: number | null = null) => {
  if (val === null || val === undefined) return 'transparent'
  if (ideale !== null && ideale !== undefined) {
    return val >= ideale ? '#4caf5020' : '#f4433620'
  }
  const colors = ['#9e9e9e', '#ef5350', '#ff9800', '#42a5f5', '#66bb6a']
  return colors[val] ?? 'transparent'
}

const employeeHasActivityRole = (row: any, activity: any) => {
  return Array.isArray(row.role_ids) && Array.isArray(activity.role_ids)
    && activity.role_ids.some((r: any) => row.role_ids.includes(r))
}

const loadTeam = async () => {
  loading.value = true
  const { data: resultData } = await useApi<any>(createUrl('/hr/competenze/my_team'))

  if (resultData.value) {
    teamItems.value = resultData.value
  }
  else {
    teamItems.value = []
  }
  loading.value = false
}

const openEvaluations = async (employee: any) => {
  selectedEmployee.value = employee
  evalDialog.value = true
  await loadEvaluations()
}

const loadEvaluations = async () => {
  if (!selectedEmployee.value) return

  const { data: resultData } = await useApi<any>(createUrl(`/hr/competenze/valutazioni/by_employee/${selectedEmployee.value.id}`, {
    query: { anno: anno.value },
  }))

  if (resultData.value) {
    employeeData.value = resultData.value.employee
    evalItems.value = resultData.value.activities.map((a: any) => ({
      ...a,
      valutazione: a.valutazione === null ? null : a.valutazione,
    }))
  }
}

const saveEvaluations = async () => {
  if (!selectedEmployee.value) return

  isLoading.value = true

  const evaluations = evalItems.value
    .filter((e: any) => e.valutazione !== null)
    .map((e: any) => ({
      activity_id: e.activity_id,
      valutazione: e.valutazione,
      note: e.note,
    }))

  const retuenData = await $api('/hr/competenze/valutazioni/bulk_store', {
    method: 'POST',
    body: {
      employee_id: selectedEmployee.value.id,
      anno: anno.value,
      evaluations,
    },
  })

  message.value = retuenData.message
  color.value = retuenData.color
  isSnackbarScrollReverseVisible.value = true
  isLoading.value = false

  await loadEvaluations()
}

const closeEvalDialog = () => {
  evalDialog.value = false
  selectedEmployee.value = null
  employeeData.value = null
  evalItems.value = []
}

const progressoValutazione = (items: any[]) => {
  if (!items.length) return 0
  const valutati = items.filter(e => e.valutazione !== null).length
  return Math.round((valutati / items.length) * 100)
}

const loadTeamMatrix = async () => {
  if (activeTab.value !== 'team') return

  matrixLoading.value = true
  const { data: resultData } = await useApi<any>(createUrl('/hr/competenze/valutazioni/team_matrix', {
    query: { anno: anno.value },
  }))

  if (resultData.value) {
    teamMatrix.value = resultData.value.matrix
    matrixColumns.value = resultData.value.activities
  } else {
    teamMatrix.value = []
    matrixColumns.value = []
  }
  matrixLoading.value = false
}

const savingCells = ref<Set<string>>(new Set())

const saveCell = async (row: any, activity: any) => {
  const cellKey = `${row.employee_id}_${activity.id}`
  if (savingCells.value.has(cellKey)) return

  const valutazione = row['activity_' + activity.id]
  if (valutazione === null || valutazione === undefined) return

  savingCells.value.add(cellKey)

  try {
    await $api('/hr/competenze/valutazioni/team_bulk_store', {
      method: 'POST',
      body: {
        anno: anno.value,
        evaluations: [{
          employee_id: row.employee_id,
          activity_id: activity.activity_id,
          valutazione,
          note: '',
        }],
      },
    })
  } finally {
    savingCells.value.delete(cellKey)
  }
}

onMounted(() => {
  loadTeam()
})
</script>

<template>
  <div class="workspace-container w-100 d-flex flex-column pa-4 gap-3">
    <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
      <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
        <div class="d-flex align-center gap-2">
          <VAvatar color="primary" variant="tonal" size="38">
            <VIcon icon="tabler-clipboard-check" size="20" />
          </VAvatar>
          <div>
            <div class="text-h6 font-weight-medium">Valutazioni Competenze</div>
            <div class="text-caption text-medium-emphasis">Valuta le competenze del tuo team per l'anno {{ anno }}</div>
          </div>
        </div>
      </VCardText>
      <VDivider />

      <VCardText class="pa-4">
        <VTabs
          v-model="activeTab"
          color="primary"
          class="mb-4"
          @update:model-value="loadTeamMatrix"
        >
          <VTab value="singolo">Singolo</VTab>
          <VTab value="team">Team</VTab>
        </VTabs>

        <VWindow v-model="activeTab">
          <VWindowItem value="singolo">
            <VAlert
              v-if="!loading && teamItems.length === 0"
              type="info"
              variant="tonal"
              density="comfortable"
            >
              Non hai dipendenti assegnati per la valutazione.
            </VAlert>

            <VRow>
              <VCol
                v-for="employee in teamItems"
                :key="employee.id"
                cols="12"
                sm="6"
                md="4"
              >
                <VCard
                  variant="outlined"
                  class="border-thin rounded-lg cursor-pointer"
                  @click="openEvaluations(employee)"
                >
                  <VCardText class="d-flex align-center gap-3">
                    <VAvatar
                      :color="employee.sesso === 'm' ? 'info' : 'primary'"
                      variant="tonal"
                      size="48"
                    >
                      <VIcon icon="tabler-user" />
                    </VAvatar>
                    <div class="flex-grow-1">
                      <div class="text-subtitle-1 font-weight-medium">{{ employee.nome_completo }}</div>
                      <div class="text-caption text-medium-emphasis">
                        Matr. {{ employee.matricola }}
                        <span v-if="employee.department"> · {{ employee.department.reparto }}</span>
                      </div>
                      <div class="d-flex gap-1 mt-1 flex-wrap">
                        <VChip
                          v-for="role in employee.roles"
                          :key="role.id"
                          label
                          size="x-small"
                          :color="role.tipo === 'macchina' ? 'warning' : 'info'"
                          variant="tonal"
                        >
                          {{ role.ruolo }}
                        </VChip>
                      </div>
                    </div>
                    <VIcon icon="tabler-chevron-right" color="medium-emphasis" />
                  </VCardText>
                </VCard>
              </VCol>
            </VRow>
          </VWindowItem>

          <VWindowItem value="team">
            <VAlert
              v-if="!matrixLoading && matrixColumns.length === 0"
              type="info"
              variant="tonal"
              density="comfortable"
              class="mb-4"
            >
              Nessuna attività configurata per le mansioni del team.
            </VAlert>

            <VCard
              v-if="matrixColumns.length > 0"
              variant="outlined"
              class="border-thin rounded-lg overflow-hidden"
            >
              <div class="overflow-x-auto">
                <VTable density="compact" class="team-matrix-table">
                  <thead>
                    <tr>
                      <th class="text-left sticky-column employee-header">Dipendente</th>
                      <th
                        v-for="activity in matrixColumns"
                        :key="activity.id"
                        class="text-center activity-header"
                        :style="{ backgroundColor: valutazioneBgColor(activity.valutazione_ideale) + '20' }"
                      >
                        <div class="rotated-header text-caption">
                          {{ activity.attivita }}
                        </div>
                        <div class="text-caption font-weight-bold mt-1">
                          {{ valutazioneIniziale(activity.valutazione_ideale) }}
                        </div>
                        <div class="text-caption text-medium-emphasis">
                          {{ activity.ruolo }}
                        </div>
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="row in teamMatrix"
                      :key="row.employee_id"
                    >
                      <td class="sticky-column font-weight-medium employee-cell">
                        {{ row.nome_completo }}
                      </td>
                      <td
                        v-for="activity in matrixColumns"
                        :key="activity.id"
                        class="text-center px-1"
                        :style="{ backgroundColor: employeeHasActivityRole(row, activity)
                          ? valutazioneBgColor(row['activity_' + activity.id], activity.valutazione_ideale)
                          : 'transparent' }"
                      >
                        <VSelect
                          :model-value="getValutazioneDisplay(row['activity_' + activity.id])"
                          @update:model-value="(v: any) => {
                            row['activity_' + activity.id] = v?.value ?? v
                            saveCell(row, activity)
                          }"
                          :items="valutazioneOptions"
                          item-title="title"
                          item-value="value"
                          density="compact"
                          variant="outlined"
                          hide-details
                          class="matrix-select"
                          placeholder="-"
                          menu-icon=""
                        />
                      </td>
                    </tr>
                  </tbody>
                </VTable>
              </div>
            </VCard>
          </VWindowItem>
        </VWindow>
      </VCardText>
    </VCard>

    <VSnackbar
      v-model="isSnackbarScrollReverseVisible"
      transition="scroll-y-reverse-transition"
      location="top central"
      :color="color"
    >
      {{ $t(message) }}
    </VSnackbar>
  </div>

  <VDialog
    v-model="evalDialog"
    max-width="900px"
    persistent
  >
    <DialogCloseBtn @click="closeEvalDialog" />

    <VCard>
      <VCardText class="d-flex align-center justify-space-between flex-wrap py-4 px-6 gap-3">
        <div class="d-flex align-center gap-3">
          <VAvatar
            :color="selectedEmployee?.sesso === 'm' ? 'info' : 'primary'"
            variant="tonal"
            size="48"
          >
            <VIcon icon="tabler-user" />
          </VAvatar>
          <div>
            <div class="text-h6 font-weight-medium">{{ selectedEmployee?.nome_completo }}</div>
            <div class="text-caption text-medium-emphasis">Matr. {{ selectedEmployee?.matricola }}</div>
          </div>
        </div>
        <div class="d-flex align-center gap-2">
          <AppTextField
            v-model="anno"
            type="number"
            label="Anno"
            density="compact"
            style="width: 100px;"
            @update:model-value="loadEvaluations"
          />
        </div>
      </VCardText>

      <VDivider />

      <VCardText class="pa-4">
        <div
          v-if="evalItems.length > 0"
          class="mb-4"
        >
          <div class="d-flex align-center justify-space-between mb-1">
            <span class="text-caption text-medium-emphasis">Progresso valutazioni</span>
            <span class="text-caption font-weight-medium">{{ progressoValutazione(evalItems) }}%</span>
          </div>
          <VProgressLinear
            :model-value="progressoValutazione(evalItems)"
            color="primary"
            height="6"
            rounded
          />
        </div>

        <VAlert
          v-if="evalItems.length === 0"
          type="info"
          variant="tonal"
          density="comfortable"
        >
          Nessuna attività configurata per le mansioni di questo dipendente.
        </VAlert>

        <VTable v-if="evalItems.length > 0" density="comfortable">
          <thead>
            <tr>
              <th>Attività</th>
              <th>Mansione</th>
              <th>Ideale</th>
              <th>Valutazione</th>
              <th>Note</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(item, index) in evalItems"
              :key="index"
            >
              <td class="font-weight-medium">{{ item.attivita }}</td>
              <td>
                <VChip
                  label
                  size="x-small"
                  :color="item.tipo === 'macchina' ? 'warning' : 'info'"
                  variant="tonal"
                >
                  {{ item.ruolo }}
                </VChip>
              </td>
              <td>
                <VChip
                  label
                  size="x-small"
                  :color="valutazioneColor(item.valutazione_ideale)"
                  variant="tonal"
                >
                  {{ valutazioneLabel(item.valutazione_ideale) }}
                </VChip>
              </td>
              <td>
                <VSelect
                  :model-value="getValutazioneDisplay(item.valutazione)"
                  @update:model-value="(v: any) => item.valutazione = v?.value ?? v"
                  :items="valutazioneOptions"
                  item-title="title"
                  item-value="value"
                  density="compact"
                  variant="outlined"
                  hide-details
                  style="width: 160px;"
                  placeholder="Valuta..."
                />
              </td>
              <td>
                <VTextField
                  v-model="item.note"
                  density="compact"
                  variant="outlined"
                  hide-details
                  placeholder="Note..."
                />
              </td>
            </tr>
          </tbody>
        </VTable>
      </VCardText>

      <VCardText class="d-flex justify-end flex-wrap gap-3">
        <VBtn
          variant="tonal"
          color="secondary"
          @click="closeEvalDialog"
        >
          Chiudi
        </VBtn>
        <VBtn
          color="primary"
          variant="elevated"
          :loading="isLoading"
          :disabled="evalItems.length === 0"
          @click="saveEvaluations"
        >
          Salva Valutazioni
        </VBtn>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<style scoped>
.team-matrix-table {
  table-layout: fixed;
  min-width: 100%;
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
  width: 90px;
  min-width: 90px;
  max-width: 90px;
  vertical-align: bottom;
  height: 100px;
}

.rotated-header {
  writing-mode: vertical-rl;
  transform: rotate(180deg);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-height: 80px;
  margin: 0 auto;
  font-size: 10px;
  line-height: 1.2;
}

.matrix-select {
  width: 80px;
  min-width: 80px;
}

.matrix-select :deep(.v-field__input) {
  min-height: 32px !important;
  padding-inline-start: 8px !important;
  padding-inline-end: 8px !important;
  font-size: 13px;
}

.matrix-select :deep(.v-field__append-inner) {
  padding-inline-start: 2px !important;
  padding-inline-end: 2px !important;
}

.matrix-select :deep(.v-input__details) {
  display: none;
}
</style>
