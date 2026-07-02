<script setup lang="ts">
import moment from 'moment'
import { useI18n } from 'vue-i18n'

definePage({
  meta: {
    action: 'list',
    subject: 'Hr-Presenze',
  },
})

const { t } = useI18n()
const loading = ref(true)
const monthFilter = ref(moment().format('YYYY-MM'))
const repartoFilter = ref<string[] | null>(null)
const centroDiCostoFilter = ref<string[] | null>(null)
const companyFilter = ref<string[] | null>(null)

const companies = [
  { value: 'metallurgica', title: 'Metallurgica' },
  { value: 'optotec', title: 'Optotec' },
]

const reparti = ref<any[]>([])
const centriDiCosto = ref<any[]>([])

const matrixData = ref<any[]>([])
const daysInMonth = ref(0)
const holidays = ref<string[]>([])

const absenceMenu = ref(false)
const menuX = ref(0)
const menuY = ref(0)
const selectedEmployee = ref<any>(null)
const selectedDay = ref<number | null>(null)
const selectedDate = ref('')
const absenceLoading = ref(false)
const menuKey = ref(0)

const currentDayState = computed(() => {
  if (!selectedEmployee.value || selectedDay.value === null) return null
  const dayData = getDayData(selectedEmployee.value, selectedDay.value)
  const tipologia = Number(dayData?.assenza?.tipologia)
  if (tipologia === 3) return 'malattia'
  if (tipologia === 4) return 'assenza'
  if (dayData?.turno) return 'turno'
  return 'vuoto'
})

const dayHeaders = computed(() => {
  const headers = []
  for (let day = 1; day <= daysInMonth.value; day++) {
    const date = moment(`${monthFilter.value}-${String(day).padStart(2, '0')}`)
    headers.push({
      day,
      dayName: date.format('ddd'),
      isWeekend: date.day() === 0 || date.day() === 6,
      isHoliday: holidays.value.includes(date.format('YYYY-MM-DD')),
    })
  }
  return headers
})

const getAbsenzaColor = (tipologia: number | string) => {
  const t = Number(tipologia)
  switch (t) {
    case 1: return 'absence-ferie' // Ferie
    case 2: return 'absence-104' // 104
    case 3: return 'absence-malattia' // Malattia
    case 4: return 'absenza-ingiustificata' // Assenza
    case 5: return 'absence-permesso' // Permesso
    default: return 'absence-unknown'
  }
}

const getAbsenzaText = (tipologia: number | string) => {
  const t = Number(tipologia)
  switch (t) {
    case 1: return 'FER'
    case 2: return '104'
    case 3: return 'MAL'
    case 4: return 'ASS'
    case 5: return 'PER'
    default: return ''
  }
}

const fetchReparti = async () => {
  const { data } = await useApi<any>('/hr/reparti/getList')
  reparti.value = data.value || []
}

const fetchCentriDiCosto = async () => {
  const { data } = await useApi<any>('/hr/centro_di_costo/get_list')
  centriDiCosto.value = data.value || []
}

const fetchMatrix = async () => {
  loading.value = true
  try {
    const { data } = await useApi<any>(createUrl('/hr/dipendenti/presenze', {
      query: {
        month: monthFilter.value,
        reparto_id: repartoFilter.value,
        centro_di_costo: centroDiCostoFilter.value,
        company_id: companyFilter.value,
      },
    }))

    if (data.value) {
      matrixData.value = data.value.employees
      daysInMonth.value = data.value.days_in_month
      holidays.value = data.value.holidays || []
    }
  }
  catch (e) {
    console.error('Error fetching presence matrix:', e)
  }
  finally {
    loading.value = false
  }
}

const getDayData = (employee: any, day: number) => {
  return employee[`day_${day}`] || null
}

const getTurnoColor = (type: string) => {
  const t = type?.toUpperCase() || ''
  switch (t) {
    case 'T1': return 'shift-t1'
    case 'T2': return 'shift-t2'
    case 'T3': return 'shift-t3'
    default: return 'shift-default'
  }
}

const openAbsenceDialog = (employee: any, day: number, event: MouseEvent) => {
  selectedEmployee.value = employee
  selectedDay.value = day
  selectedDate.value = moment(`${monthFilter.value}-${String(day).padStart(2, '0')}`).format('YYYY-MM-DD')
  menuX.value = event.clientX
  menuY.value = event.clientY
  absenceMenu.value = true
}

const saveAbsence = async (tipologia: number) => {
  absenceLoading.value = true
  try {
    await $api('/hr/dipendenti/quick-absence', {
      method: 'POST',
      body: {
        employee_id: selectedEmployee.value.id,
        data: selectedDate.value,
        tipologia,
        note: '',
      },
    })
    absenceMenu.value = false
    await fetchMatrix()
  }
  catch (e) {
    console.error('Error saving absence:', e)
    alert('Errore durante il salvataggio dell\'assenza')
  }
  finally {
    absenceLoading.value = false
  }
}

const updateAbsence = async (tipologia: number) => {
  console.log('updateAbsence', { tipologia })
  absenceLoading.value = true
  try {
    await $api('/hr/dipendenti/quick-absence/update', {
      method: 'POST',
      body: {
        employee_id: selectedEmployee.value.id,
        data: selectedDate.value,
        tipologia,
      },
    })
    absenceMenu.value = false
    await fetchMatrix()
  }
  catch (e) {
    console.error('Error updating absence:', e)
    alert('Errore durante l\'aggiornamento dell\'assenza')
  }
  finally {
    absenceLoading.value = false
  }
}

const deleteAbsence = async () => {
  console.log('deleteAbsence')
  absenceLoading.value = true
  try {
    await $api('/hr/dipendenti/quick-absence/delete', {
      method: 'POST',
      body: {
        employee_id: selectedEmployee.value.id,
        data: selectedDate.value,
      },
    })
    absenceMenu.value = false
    await fetchMatrix()
  }
  catch (e) {
    console.error('Error deleting absence:', e)
    alert('Errore durante l\'eliminazione dell\'assenza')
  }
  finally {
    absenceLoading.value = false
  }
}

const closeMenu = () => {
  absenceMenu.value = false
  menuKey.value++
}

onMounted(() => {
  fetchReparti()
  fetchCentriDiCosto()
  fetchMatrix()
})

onUnmounted(() => {
  document.removeEventListener('click', () => {})
})

watch([monthFilter, repartoFilter, centroDiCostoFilter], () => {
  fetchMatrix()
})
</script>

<template>
  <div class="workspace-container w-100 d-flex flex-column pa-4 gap-3">
    <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
      <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
        <div class="d-flex align-center gap-2">
          <VIcon icon="tabler-calendar-event" size="24" color="primary" />
          <div>
            <div class="text-h6 font-weight-medium">Matrice Presenze</div>
            <div class="text-caption text-medium-emphasis">{{ matrixData.length || 0 }} dipendenti</div>
          </div>
        </div>
      </VCardText>
      <VDivider />
      <VCardText class="pa-3">
        <VRow class="mb-2">
          <!-- 👉 Mese -->
          <VCol cols="12" sm="3">
            <AppTextField
              v-model="monthFilter"
              type="month"
              label="Mese"
              placeholder="Mese"
              @update:model-value="fetchMatrix"
            />
          </VCol>

          <!-- 👉 Azienda -->
          <VCol cols="12" sm="3">
            <AppSelect
              v-model="companyFilter"
              label="Azienda"
              placeholder="Tutte le aziende"
              clearable
              :items="companies"
              item-title="title"
              item-value="value"
              multiple
              chips
              @update:model-value="fetchMatrix"
            />
          </VCol>

          <!-- 👉 Reparto -->
          <VCol cols="12" sm="3">
            <AppSelect
              v-model="repartoFilter"
              label="Reparto"
              placeholder="Tutti i reparti"
              clearable
              :items="reparti"
              item-title="reparto"
              item-value="id"
              multiple
              chips
              @update:model-value="fetchMatrix"
            />
          </VCol>

          <!-- 👉 Centro di Costo -->
          <VCol cols="12" sm="3">
            <AppSelect
              v-model="centroDiCostoFilter"
              label="Centro di Costo"
              placeholder="Tutti i centri"
              clearable
              :items="centriDiCosto"
              item-title="centro_di_costo"
              item-value="id"
              multiple
              chips
              @update:model-value="fetchMatrix"
            />
          </VCol>
        </VRow>
      </VCardText>
      <VDivider />

      <!-- 👉 Matrice Presenze -->
      <div class="overflow-x-auto">
        <table class="presence-matrix">
          <thead>
            <tr class="days-row">
              <th class="employee-header" rowspan="2">Dipendente</th>
              <th class="employee-header" rowspan="2">Matricola</th>
              <th class="employee-header" rowspan="2">Reparto</th>
              <th
                v-for="header in dayHeaders"
                :key="`day-num-${header.day}`"
                class="day-header"
                :class="{ 'weekend': header.isWeekend, 'holiday': header.isHoliday }"
              >
                {{ header.day }}
              </th>
            </tr>
            <tr class="weekdays-row">
              <th
                v-for="header in dayHeaders"
                :key="`day-name-${header.day}`"
                class="day-name-header"
                :class="{ 'weekend': header.isWeekend, 'holiday': header.isHoliday }"
              >
                {{ header.dayName.toUpperCase() }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td :colspan="3 + daysInMonth" class="text-center py-4">
                <VProgressCircular indeterminate color="primary" />
              </td>
            </tr>
            <tr v-if="!loading && matrixData.length === 0">
              <td :colspan="3 + daysInMonth" class="text-center py-4 text-disabled">
                Nessun dato trovato
              </td>
            </tr>
            <tr
              v-for="employee in matrixData"
              :key="employee.id"
              class="employee-row"
            >
              <td class="employee-cell">
                <div class="font-weight-medium">{{ employee.nome_completo }}</div>
              </td>
              <td class="employee-cell">{{ employee.matricola }}</td>
              <td class="employee-cell">{{ employee.reparto }}</td>
              <td
                v-for="header in dayHeaders"
                :key="`cell-${employee.id}-${header.day}`"
                class="day-cell"
                :class="{
                  'weekend': header.isWeekend,
                  'holiday': header.isHoliday,
                  [getAbsenzaColor(getDayData(employee, header.day)?.assenza?.tipologia)]: getDayData(employee, header.day)?.assenza,
                }"
                @click="openAbsenceDialog(employee, header.day, $event)"
              >
                <template v-if="header.isHoliday">
                  <span class="text-error font-weight-bold">F</span>
                </template>
                <template v-else-if="getDayData(employee, header.day)?.assenza">
                  <VTooltip location="top">
                    <template #activator="{ props }">
                      <span
                        v-bind="props"
                        class="day-badge text-white"
                      >
                        {{ getDayData(employee, header.day)?.turno?.type?.toUpperCase() || header.day }}
                      </span>
                    </template>
                    <div>
                      <div class="font-weight-bold">{{ getDayData(employee, header.day)?.assenza?.tipologia_testo }}</div>
                      <div v-if="getDayData(employee, header.day)?.turno">
                        Turno: {{ getDayData(employee, header.day)?.turno?.type?.toUpperCase() }}
                      </div>
                      <div v-if="getDayData(employee, header.day)?.turno?.machine">
                        Macchina: {{ getDayData(employee, header.day)?.turno?.machine }}
                      </div>
                      <div v-if="getDayData(employee, header.day)?.assenza?.ora_inizio">
                        {{ getDayData(employee, header.day)?.assenza?.ora_inizio }} - {{ getDayData(employee, header.day)?.assenza?.ora_fine }}
                      </div>
                    </div>
                  </VTooltip>
                </template>
                <template v-else-if="getDayData(employee, header.day)?.turno">
                  <VTooltip location="top">
                    <template #activator="{ props }">
                      <span
                        v-bind="props"
                        class="day-badge text-white"
                        :class="getTurnoColor(getDayData(employee, header.day)?.turno?.type)"
                      >
                        {{ getDayData(employee, header.day)?.turno?.type?.toUpperCase() }}
                      </span>
                    </template>
                    <div>
                      <div class="font-weight-bold">Turno: {{ getDayData(employee, header.day)?.turno?.type?.toUpperCase() }}</div>
                      <div v-if="getDayData(employee, header.day)?.turno?.machine">
                        Macchina: {{ getDayData(employee, header.day)?.turno?.machine }}
                      </div>
                    </div>
                  </VTooltip>
                </template>
                <template v-else>
                  <span class="text-disabled">{{ header.day }}</span>
                </template>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </VCard>

    <!-- 👉 Legend -->
    <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
      <VCardText class="pa-3">
        <div class="d-flex align-center gap-4 flex-wrap">
          <div class="text-subtitle-2 font-weight-medium">Legenda:</div>
          <div class="d-flex align-center gap-1">
            <span class="day-badge absence-ferie">FER</span>
            <span class="text-caption">Ferie</span>
          </div>
          <div class="d-flex align-center gap-1">
            <span class="day-badge absence-104">104</span>
            <span class="text-caption">104</span>
          </div>
          <div class="d-flex align-center gap-1">
            <span class="day-badge absence-permesso">PER</span>
            <span class="text-caption">Permesso</span>
          </div>
          <div class="d-flex align-center gap-1">
            <span class="day-badge absence-malattia">MAL</span>
            <span class="text-caption">Malattia</span>
          </div>
          <div class="d-flex align-center gap-1">
            <span class="day-badge absenza-ingiustificata">ASS</span>
            <span class="text-caption">Assenza</span>
          </div>
          <div class="d-flex align-center gap-1">
            <span class="day-badge shift-t1">T1</span>
            <span class="text-caption">Turno 1</span>
          </div>
          <div class="d-flex align-center gap-1">
            <span class="day-badge shift-t2">T2</span>
            <span class="text-caption">Turno 2</span>
          </div>
          <div class="d-flex align-center gap-1">
            <span class="day-badge shift-t3">T3</span>
            <span class="text-caption">Turno 3</span>
          </div>
          <div class="d-flex align-center gap-1">
            <span class="text-error font-weight-bold">F</span>
            <span class="text-caption">Festivo</span>
          </div>
          <div class="d-flex align-center gap-1">
            <span class="text-disabled">-</span>
            <span class="text-caption">Presente</span>
          </div>
        </div>
      </VCardText>
    </VCard>

    <!-- Quick Absence Menu -->
    <VCard
      v-if="absenceMenu"
      :key="menuKey"
      class="absence-menu"
      :style="{ left: menuX + 'px', top: menuY + 'px' }"
      min-width="180"
      elevation="8"
    >
      <!-- Shift or empty day: show add options -->
      <template v-if="currentDayState === 'turno' || currentDayState === 'vuoto'">
        <div
          class="d-flex align-center pa-2 cursor-pointer"
          :class="{ 'opacity-50': absenceLoading }"
          @click="saveAbsence(3)"
        >
          <VIcon icon="tabler-heart" color="secondary" size="20" class="mr-2" />
          <span class="text-body-2">Malattia</span>
        </div>
        <div
          class="d-flex align-center pa-2 cursor-pointer"
          :class="{ 'opacity-50': absenceLoading }"
          @click="saveAbsence(4)"
        >
          <VIcon icon="tabler-alert-circle" color="error" size="20" class="mr-2" />
          <span class="text-body-2">Assenza</span>
        </div>
      </template>

      <!-- Malattia: show change to assenza or delete -->
      <template v-else-if="currentDayState === 'malattia'">
        <div
          class="d-flex align-center pa-2 cursor-pointer"
          :class="{ 'opacity-50': absenceLoading }"
          @click="updateAbsence(4)"
        >
          <VIcon icon="tabler-alert-circle" color="error" size="20" class="mr-2" />
          <span class="text-body-2">Cambia in Assenza</span>
        </div>
        <div
          class="d-flex align-center pa-2 cursor-pointer"
          :class="{ 'opacity-50': absenceLoading }"
          @click="deleteAbsence"
        >
          <VIcon icon="tabler-trash" color="error" size="20" class="mr-2" />
          <span class="text-body-2">Elimina</span>
        </div>
      </template>

      <!-- Assenza: show change to malattia or delete -->
      <template v-else-if="currentDayState === 'assenza'">
        <div
          class="d-flex align-center pa-2 cursor-pointer"
          :class="{ 'opacity-50': absenceLoading }"
          @click="updateAbsence(3)"
        >
          <VIcon icon="tabler-heart" color="secondary" size="20" class="mr-2" />
          <span class="text-body-2">Cambia in Malattia</span>
        </div>
        <div
          class="d-flex align-center pa-2 cursor-pointer"
          :class="{ 'opacity-50': absenceLoading }"
          @click="deleteAbsence"
        >
          <VIcon icon="tabler-trash" color="error" size="20" class="mr-2" />
          <span class="text-body-2">Elimina</span>
        </div>
      </template>

      <!-- Cancel button -->
      <VDivider />
      <div
        class="d-flex align-center pa-2 cursor-pointer"
        :class="{ 'opacity-50': absenceLoading }"
        @click="closeMenu"
      >
        <VIcon icon="tabler-x" color="disabled" size="20" class="mr-2" />
        <span class="text-body-2 text-disabled">Annulla</span>
      </div>
    </VCard>
  </div>
</template>

<style scoped>
.presence-matrix {
  border-collapse: collapse;
  width: 100%;
  font-size: 12px;
}

.presence-matrix th,
.presence-matrix td {
  border: 1px solid rgba(var(--v-theme-on-surface), 0.12);
  text-align: center;
  padding: 4px 6px;
  min-width: 32px;
  height: 36px;
}

.employee-header {
  background-color: rgb(var(--v-theme-primary));
  color: rgb(var(--v-theme-on-primary));
  font-weight: 600;
  text-align: left;
  min-width: 120px;
  position: sticky;
  left: 0;
  z-index: 2;
}

.employee-header:nth-child(2) {
  left: 120px;
}

.employee-header:nth-child(3) {
  left: 220px;
}

.day-header,
.day-name-header {
  background-color: rgb(var(--v-theme-primary));
  color: rgb(var(--v-theme-on-primary));
  font-weight: 600;
  padding: 2px 4px;
}

.day-header {
  height: 28px;
  font-size: 11px;
}

.day-name-header {
  height: 24px;
  font-size: 10px;
}

.day-header.weekend,
.day-name-header.weekend {
  background-color: rgba(var(--v-theme-primary), 0.75);
}

.day-header.holiday,
.day-name-header.holiday {
  background-color: rgb(var(--v-theme-error));
  color: rgb(var(--v-theme-on-error));
}

.employee-cell {
  text-align: left;
  background-color: rgb(var(--v-theme-surface));
  color: rgb(var(--v-theme-on-surface));
  position: sticky;
  left: 0;
  z-index: 1;
  font-weight: 500;
}

.employee-cell:nth-child(2) {
  left: 120px;
}

.employee-cell:nth-child(3) {
  left: 220px;
}

.day-cell {
  cursor: default;
  transition: background-color 0.2s;
}

.day-cell:hover {
  background-color: rgba(var(--v-theme-on-surface), 0.08);
}

.day-cell.weekend {
  background-color: rgba(var(--v-theme-primary), 0.08);
}

.day-cell.holiday {
  background-color: rgba(var(--v-theme-error), 0.12);
}

.absence-menu {
  position: fixed;
  z-index: 9999;
}

.day-cell.absence-ferie,
.employee-row:nth-child(even) .day-cell.absence-ferie {
  background-color: #ff9800 !important;
  color: #fff !important;
}

.day-cell.absence-104,
.employee-row:nth-child(even) .day-cell.absence-104 {
  background-color: #0288d1 !important;
  color: #fff !important;
}

.day-cell.absence-malattia,
.employee-row:nth-child(even) .day-cell.absence-malattia {
  background-color: #9c27b0 !important;
  color: #fff !important;
}

.day-cell.absenza-ingiustificata,
.employee-row:nth-child(even) .day-cell.absenza-ingiustificata {
  background-color: #f44336 !important;
  color: #fff !important;
}

.day-cell.absence-permesso,
.employee-row:nth-child(even) .day-cell.absence-permesso {
  background-color: #00897b !important;
  color: #fff !important;
}

.day-cell.absence-unknown,
.employee-row:nth-child(even) .day-cell.absence-unknown {
  background-color: #607d8b !important;
  color: #fff !important;
}

.day-badge.absence-ferie {
  background-color: #ff9800;
  color: #fff;
}

.day-badge.absence-104 {
  background-color: #0288d1;
  color: #fff;
}

.day-badge.absence-malattia {
  background-color: #9c27b0;
  color: #fff;
}

.day-badge.absenza-ingiustificata {
  background-color: #f44336;
  color: #fff;
}

.day-badge.absence-permesso {
  background-color: #00897b;
  color: #fff;
}

.day-badge.absence-unknown {
  background-color: #607d8b;
  color: #fff;
}

.day-badge.shift-t1 {
  background-color: #4caf50;
  color: #fff;
}

.day-badge.shift-t2 {
  background-color: #2196f3;
  color: #fff;
}

.day-badge.shift-t3 {
  background-color: #ff5722;
  color: #fff;
}

.day-badge.shift-default {
  background-color: #795548;
  color: #fff;
}

.day-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  padding: 2px 6px;
  font-size: 10px;
  font-weight: 700;
  min-width: 28px;
  height: 20px;
}

.employee-row:nth-child(even) .day-cell:not(.weekend):not(.holiday) {
  background-color: rgba(var(--v-theme-on-surface), 0.03);
}

.employee-row:nth-child(even) .employee-cell {
  background-color: rgba(var(--v-theme-on-surface), 0.03);
}

.employee-row:nth-child(even) .day-cell.weekend {
  background-color: rgba(var(--v-theme-primary), 0.12);
}

.employee-row:nth-child(even) .day-cell.holiday {
  background-color: rgba(var(--v-theme-error), 0.18);
}

.overflow-x-auto {
  overflow-x: auto;
  max-height: 65vh;
}

.overflow-x-auto thead {
  position: sticky;
  top: 0;
  z-index: 3;
}
</style>
