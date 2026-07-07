<script setup lang="ts">
import { useI18n } from 'vue-i18n'

const props = defineProps<{
  id: string
}>()

const { t } = useI18n()
const currentMonth = ref(new Date().toISOString().slice(0, 7))
const overtimeData = ref<any[]>([])
const loading = ref(false)

const fetchOvertime = async () => {
  loading.value = true
  try {
    const { data: resultData } = await useApi<any>(createUrl('/teamsystem/straordinari/dipendente', {
      query: {
        employee_id: props.id,
        mese: currentMonth.value,
      },
    }))
    overtimeData.value = resultData.value?.data || []
  }
  catch (e) {
    console.error('Error fetching overtime:', e)
  }
  finally {
    loading.value = false
  }
}

watch(currentMonth, () => {
  fetchOvertime()
})

onMounted(() => {
  fetchOvertime()
})

const formatDate = (dateStr: string) => {
  const date = new Date(dateStr)
  return date.toLocaleDateString('it-IT', { day: '2-digit', month: '2-digit' })
}

const formatHours = (hours: number) => {
  return hours.toFixed(2)
}

const getDaysInMonth = () => {
  const [year, month] = currentMonth.value.split('-').map(Number)
  return new Date(year, month, 0).getDate()
}

const getCalendarDays = () => {
  const days = []
  const daysInMonth = getDaysInMonth()
  const [year, month] = currentMonth.value.split('-').map(Number)
  
  // Get the day of the week for the first day of the month (0 = Sunday, 1 = Monday, etc.)
  const firstDay = new Date(year, month - 1, 1).getDay()
  // Convert to Monday-based (0 = Monday, 6 = Sunday)
  const firstDayMonday = firstDay === 0 ? 6 : firstDay - 1
  
  // Add empty cells for days before the first day of the month
  for (let i = 0; i < firstDayMonday; i++) {
    days.push({
      day: null,
      date: null,
      overtime: null,
      empty: true,
    })
  }
  
  // Add actual days
  for (let day = 1; day <= daysInMonth; day++) {
    const dateStr = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`
    const overtime = overtimeData.value.find(o => o.data === dateStr)
    days.push({
      day,
      date: dateStr,
      overtime: overtime || null,
      empty: false,
    })
  }
  
  return days
}

const previousMonth = () => {
  const [year, month] = currentMonth.value.split('-').map(Number)
  const date = new Date(year, month - 2, 1)
  const newYear = date.getFullYear()
  const newMonth = String(date.getMonth() + 1).padStart(2, '0')
  currentMonth.value = `${newYear}-${newMonth}`
}

const nextMonth = () => {
  const [year, month] = currentMonth.value.split('-').map(Number)
  const date = new Date(year, month, 1)
  const newYear = date.getFullYear()
  const newMonth = String(date.getMonth() + 1).padStart(2, '0')
  currentMonth.value = `${newYear}-${newMonth}`
}

const totalHours = computed(() => {
  return overtimeData.value.reduce((sum, item) => sum + item.ore, 0)
})

const monthName = computed(() => {
  const [year, month] = currentMonth.value.split('-').map(Number)
  const date = new Date(year, month - 1, 1)
  return date.toLocaleDateString('it-IT', { month: 'long', year: 'numeric' })
})
</script>

<template>
  <div class="d-flex flex-column gap-3 ">
    <!-- Month Selector & Total Hours -->
    <div class="d-flex align-center justify-space-between gap-3">
      <div class="d-flex align-center gap-2">
        <VBtn
          variant="outlined"
          size="small"
          prepend-icon="tabler-chevron-left"
          @click="previousMonth"
        >
          Prec
        </VBtn>
        <h3 class="text-subtitle-1 font-weight-semibold text-capitalize">
          {{ monthName }}
        </h3>
        <VBtn
          variant="outlined"
          size="small"
          append-icon="tabler-chevron-right"
          @click="nextMonth"
        >
          Succ
        </VBtn>
      </div>
      <div class="d-flex align-center gap-2">
        <VIcon icon="tabler-clock" size="20" color="#28C76F" />
        <div class="text-h5 font-weight-semibold" style="color: #28C76F;">
          {{ formatHours(totalHours) }}h
        </div>
      </div>
    </div>

    <!-- Calendar & Detail Side by Side -->
    <VRow class="flex-grow-1">
      <!-- Calendar Grid -->
      <VCol cols="12" md="7" class="d-flex">
        <VCard variant="outlined" class="bg-surface border-thin rounded-lg flex-grow-1 w-100 overflow-hidden">
          <VCardText class="pa-3 h-100">
            <VProgressCircular v-if="loading" indeterminate color="primary" class="d-block mx-auto" />
            <div v-else class="calendar-grid h-100">
              <div class="calendar-header">
                <div v-for="day in ['Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom']" :key="day" class="calendar-day-header">
                  {{ day }}
                </div>
              </div>
              <div class="calendar-body">
                <div
                  v-for="item in getCalendarDays()"
                  :key="item.date || item.day"
                  class="calendar-day"
                  :class="{ 'has-overtime': item.overtime, 'empty': item.empty }"
                >
                  <div v-if="!item.empty" class="day-number">{{ item.day }}</div>
                  <div v-if="item.overtime" class="overtime-info">
                    <div class="overtime-hours">{{ formatHours(item.overtime.ore) }}h</div>
                    <div class="overtime-type">{{ item.overtime.tipo }}</div>
                  </div>
                </div>
              </div>
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <!-- Overtime List -->
      <VCol cols="12" md="5" class="d-flex">
        <VCard v-if="overtimeData.length > 0" variant="outlined" class="bg-surface border-thin rounded-lg w-100">
          <VCardText class="pa-3">
            <h4 class="text-subtitle-2 font-weight-semibold text-high-emphasis mb-2">
              Dettaglio Straordinari
            </h4>
            <VTable density="compact">
              <thead>
                <tr>
                  <th>Data</th>
                  <th>Ore</th>
                  <th>Tipo</th>
                  <th>Causale</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in overtimeData" :key="item.data">
                  <td>{{ formatDate(item.data) }}</td>
                  <td>{{ formatHours(item.ore) }}h</td>
                  <td>{{ item.tipo }}</td>
                  <td>{{ item.causale }}</td>
                </tr>
              </tbody>
            </VTable>
          </VCardText>
        </VCard>

        <VCard v-else-if="!loading" variant="outlined" class="bg-surface border-thin rounded-lg w-100">
          <VCardText class="pa-4 text-center">
            <VIcon icon="tabler-calendar-off" size="32" class="text-disabled mb-1" />
            <p class="text-caption text-disabled mb-0">Nessuno straordinario trovato per questo mese</p>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>

<style lang="scss" scoped>
.calendar-grid {
  display: flex;
  flex-direction: column;
  gap: 8px;
  height: 100%;
}

.calendar-header {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 4px;
  flex-shrink: 0;
}

.calendar-day-header {
  text-align: center;
  font-weight: 600;
  font-size: 0.875rem;
  color: rgb(var(--v-theme-on-surface));
  padding: 8px;
}

.calendar-body {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 4px;
  flex-grow: 1;
  place-items: center;
}

.calendar-day {
  aspect-ratio: 1;
  border: 1px solid rgb(var(--v-border-color));
  border-radius: 2px;
  padding: 2px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background-color: rgb(var(--v-theme-surface));
  transition: all 0.2s ease;
  width: 100%;
  max-height: 100px;
  margin: 0 auto;

  &.empty {
    background-color: transparent;
    border-color: transparent;
  }

  &.has-overtime {
    background-color: rgba(40, 199, 111, 0.1);
    border-color: #28C76F;
  }

  &:hover:not(.empty) {
    background-color: rgba(40, 199, 111, 0.05);
  }
}

.day-number {
  font-weight: 600;
  font-size: 0.625rem;
  color: rgb(var(--v-theme-on-surface));
}

.overtime-info {
  margin-top: 1px;
  text-align: center;
}

.overtime-hours {
  font-weight: 700;
  font-size: 0.8rem;
  color: #28C76F;
}

.overtime-type {
  font-size: 0.4375rem;
  color: rgb(var(--v-theme-on-surface-variant));
  line-height: 1;
}
</style>
