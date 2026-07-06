<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { VDataTable } from 'vuetify/labs/VDataTable'
import { useI18n } from 'vue-i18n'

definePage({
  meta: {
    action: 'list',
    subject: 'Hr-Straordinari',
  },
})

const { t } = useI18n()

const selectedYear = ref(new Date().getFullYear());
const selectedMonth = ref(new Date().getMonth() + 1);
const selectedWeek = ref(1);
const loading = ref(false);
const saving = ref(false);
const loadingTeamSystem = ref(false);
const historicalCosts = ref([]);
const matrixRows = ref([]);
const matrixTotals = ref(null);

const years = ref([]);
const weeks = ref([
  { text: 'Settimana 1', value: 1 },
  { text: 'Settimana 2', value: 2 },
  { text: 'Settimana 3', value: 3 },
  { text: 'Settimana 4', value: 4 },
  { text: 'Settimana 5', value: 5 },
]);
const months = ref([
  { text: 'Gennaio', value: 1 },
  { text: 'Febbraio', value: 2 },
  { text: 'Marzo', value: 3 },
  { text: 'Aprile', value: 4 },
  { text: 'Maggio', value: 5 },
  { text: 'Giugno', value: 6 },
  { text: 'Luglio', value: 7 },
  { text: 'Agosto', value: 8 },
  { text: 'Settembre', value: 9 },
  { text: 'Ottobre', value: 10 },
  { text: 'Novembre', value: 11 },
  { text: 'Dicembre', value: 12 },
]);

const matrixHeaders = ref([
  { title: 'Reparti', key: 'department', sortable: false },
  { title: 'Week 1', key: 'week1', sortable: false, align: 'end' },
  { title: 'Week 2', key: 'week2', sortable: false, align: 'end' },
  { title: 'Week 3', key: 'week3', sortable: false, align: 'end' },
  { title: 'Week 4', key: 'week4', sortable: false, align: 'end' },
  { title: 'Week 5', key: 'week5', sortable: false, align: 'end' },
  { title: 'Cost', key: 'cost', sortable: false, align: 'end' },
  { title: 'Hours', key: 'hours', sortable: false, align: 'end' },
]);

const matrixItems = computed(() => {
  if (!matrixTotals.value) return matrixRows.value;
  return [...matrixRows.value, matrixTotals.value];
});

const formatHours = (value: number) => {
  return Number(value || 0).toFixed(2);
};

const formatCost = (value: number) => {
  return `€ ${Number(value || 0).toFixed(2)}`;
};

const getRowClass = (item: any) => {
  return item.department === 'Totals' ? 'totals-row' : '';
};

const updateRowHours = (item: any) => {
  item.hours = (Number(item.week1) || 0) + (Number(item.week2) || 0) + (Number(item.week3) || 0) + (Number(item.week4) || 0) + (Number(item.week5) || 0);
  updateTotals();
};

const updateTotals = () => {
  const totals = {
    department: 'Totals',
    week1: 0,
    week2: 0,
    week3: 0,
    week4: 0,
    week5: 0,
    cost: 0,
    hours: 0,
  };

  matrixRows.value.forEach((row: any) => {
    totals.week1 += Number(row.week1) || 0;
    totals.week2 += Number(row.week2) || 0;
    totals.week3 += Number(row.week3) || 0;
    totals.week4 += Number(row.week4) || 0;
    totals.week5 += Number(row.week5) || 0;
    totals.cost += Number(row.cost) || 0;
    totals.hours += Number(row.hours) || 0;
  });

  matrixTotals.value = totals;
};

const calculateFromTeamSystem = async () => {
  loadingTeamSystem.value = true
  try {
    const data = await $api('/overtime-costs/calculate-teamsystem', {
      method: 'POST',
      body: {
        year: selectedYear.value,
        month: selectedMonth.value,
        week: selectedWeek.value,
      },
    })
    if (data && data.success) {
      const weekKey = `week${selectedWeek.value}`
      if (matrixRows.value.length === 0) {
        initializeEmptyRows()
      }
      data.data.rows.forEach((row: any) => {
        const existingRow = matrixRows.value.find((r: any) => r.department === row.department)
        if (existingRow) {
          existingRow[weekKey] = row[weekKey]
          updateRowHours(existingRow)
        }
      })
    }
  } catch (error) {
    console.error('Errore nel calcolo delle ore da TeamSystem:', error)
  } finally {
    loadingTeamSystem.value = false
  }
}

const loadHistoricalCosts = async () => {
  loading.value = true
  try {
    const data = await $api('/overtime-costs/matrix', {
      query: {
        year: selectedYear.value,
        month: selectedMonth.value,
      },
    })
    if (data && data.success) {
      matrixRows.value = data.data.rows
      matrixTotals.value = data.data.totals
      historicalCosts.value = data.data.rows

      if (matrixRows.value.length === 0) {
        initializeEmptyRows()
      }
    } else {
      initializeEmptyRows()
    }
  } catch (error) {
    console.error('Errore nel caricamento dei costi storici:', error)
    initializeEmptyRows()
  } finally {
    loading.value = false
  }
}

const initializeEmptyRows = () => {
  const defaultDepartments = [
    'BlueCollar OFC',
    'BlueCollar CC',
    'Maintenance',
    'Quality',
    'Logistic OFC',
    'Warehouse CC',
    'Offices',
  ];

  matrixRows.value = defaultDepartments.map(dept => ({
    department: dept,
    week1: 0,
    week2: 0,
    week3: 0,
    week4: 0,
    week5: 0,
    cost: 0,
    hours: 0,
  }));

  matrixTotals.value = {
    department: 'Totals',
    week1: 0,
    week2: 0,
    week3: 0,
    week4: 0,
    week5: 0,
    cost: 0,
    hours: 0,
  };
};

const saveData = async () => {
  saving.value = true
  try {
    const data = await $api('/overtime-costs/save-manual', {
      method: 'POST',
      body: {
        year: selectedYear.value,
        month: selectedMonth.value,
        rows: matrixRows.value,
      },
    })
    if (data && data.success) {
      await loadHistoricalCosts()
    }
  } catch (error) {
    console.error('Errore nel salvataggio dei dati:', error)
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  const currentYear = new Date().getFullYear();
  for (let i = currentYear - 5; i <= currentYear + 1; i++) {
    years.value.push({ text: i.toString(), value: i });
  }
  loadHistoricalCosts();
});

watch([selectedYear, selectedMonth], () => {
  loadHistoricalCosts();
});
</script>

<template>
  <div class="workspace-container w-100 d-flex flex-column pa-4 gap-3">
    <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
      <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
        <div class="d-flex align-center gap-2">
          <VIcon icon="tabler-currency-euro" size="24" color="primary" />
          <div>
            <div class="text-h6 font-weight-medium">Costi Straordinari</div>
            <div class="text-caption text-medium-emphasis">Inserimento manuale costi per reparto e settimana</div>
          </div>
        </div>
      </VCardText>
      <VDivider />
      <VCardText class="pa-3">
        <VRow class="mb-2">
          <VCol cols="12" sm="3">
            <AppSelect
              v-model="selectedYear"
              label="Anno"
              :items="years"
              item-title="text"
              item-value="value"
            />
          </VCol>
          <VCol cols="12" sm="3">
            <AppSelect
              v-model="selectedMonth"
              label="Mese"
              :items="months"
              item-title="text"
              item-value="value"
            />
          </VCol>
          <VCol cols="12" sm="2">
            <AppSelect
              v-model="selectedWeek"
              label="Settimana"
              :items="weeks"
              item-title="text"
              item-value="value"
            />
          </VCol>
          <VCol cols="12" sm="2">
            <VBtn
              color="info"
              variant="elevated"
              @click="loadHistoricalCosts"
              :loading="loading"
              block
            >
              <VIcon icon="tabler-refresh" start />
              Carica Dati
            </VBtn>
          </VCol>
          <VCol cols="12" sm="2">
            <VBtn
              color="warning"
              variant="elevated"
              @click="calculateFromTeamSystem"
              :loading="loadingTeamSystem"
              block
            >
              <VIcon icon="tabler-database" start />
              TeamSystem
            </VBtn>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <VCard v-if="matrixRows.length > 0" variant="outlined" class="bg-surface border-thin rounded-lg">
      <VCardText class="pa-3">
        <VDataTable
          :headers="matrixHeaders"
          :items="matrixItems"
          :item-class="getRowClass"
          hide-default-footer
          class="matrix-table"
        >
          <template #item.week1="{ item }">
            <VTextField
              v-if="item.department !== 'Totals'"
              v-model="item.week1"
              type="number"
              density="compact"
              hide-details
              @update:model-value="updateRowHours(item)"
            />
            <span v-else class="font-weight-bold">{{ formatHours(item.week1) }}</span>
          </template>
          <template #item.week2="{ item }">
            <VTextField
              v-if="item.department !== 'Totals'"
              v-model="item.week2"
              type="number"
              density="compact"
              hide-details
              @update:model-value="updateRowHours(item)"
            />
            <span v-else class="font-weight-bold">{{ formatHours(item.week2) }}</span>
          </template>
          <template #item.week3="{ item }">
            <VTextField
              v-if="item.department !== 'Totals'"
              v-model="item.week3"
              type="number"
              density="compact"
              hide-details
              @update:model-value="updateRowHours(item)"
            />
            <span v-else class="font-weight-bold">{{ formatHours(item.week3) }}</span>
          </template>
          <template #item.week4="{ item }">
            <VTextField
              v-if="item.department !== 'Totals'"
              v-model="item.week4"
              type="number"
              density="compact"
              hide-details
              @update:model-value="updateRowHours(item)"
            />
            <span v-else class="font-weight-bold">{{ formatHours(item.week4) }}</span>
          </template>
          <template #item.week5="{ item }">
            <VTextField
              v-if="item.department !== 'Totals'"
              v-model="item.week5"
              type="number"
              density="compact"
              hide-details
              @update:model-value="updateRowHours(item)"
            />
            <span v-else class="font-weight-bold">{{ formatHours(item.week5) }}</span>
          </template>
          <template #item.cost="{ item }">
            <VTextField
              v-if="item.department !== 'Totals'"
              v-model="item.cost"
              class="bg-primary bg-opacity-10 pa-1 rounded"
              type="number"
              density="compact"
              hide-details
              prefix="€"
            />
            <span v-else class="font-weight-bold bg-primary bg-opacity-10 pa-1 rounded">{{ formatCost(item.cost) }}</span>
          </template>
          <template #item.hours="{ item }">
            <span :class="item.department === 'Totals' ? 'font-weight-bold bg-success bg-opacity-10 pa-1 rounded' : 'bg-success bg-opacity-10 pa-1 rounded'">
              {{ formatHours(item.hours) }}
            </span>
          </template>
        </VDataTable>
      </VCardText>
    </VCard>

    <VBtn
      color="success"
      variant="elevated"
      @click="saveData"
      :loading="saving"
      block
    >
      <VIcon icon="tabler-device-floppy" start />
      Salva Dati
    </VBtn>

    <LoadingStandBy v-model="loading"></LoadingStandBy>
  </div>
</template>

<style scoped lang="scss">
.matrix-table :deep(.totals-row) {
  background-color: #ffd700 !important;
}
</style>