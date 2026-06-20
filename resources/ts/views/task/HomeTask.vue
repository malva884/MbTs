<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import TaskApprovazione from '@/views/task/home/TaskApprovazione.vue'
import TaskAggiornati from '@/views/task/home/TaskAggiornati.vue'
import TaskStatistica from '@/views/task/home/TaskStatistica.vue'

const { t } = useI18n()
const itemsPerPage = ref(6)
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const page = ref(1)
const serverItems = ref<any[]>([])
const q = ref('')
const loading = ref(false)
const aggiornaTask = ref(false)

const loadItems = async () => {
  loading.value = true
  try {
    const { data: resultData } = await useApi<any>(createUrl('task/dashboard/approvare', {
      query: {
        page: page.value,
        itemsPerPage: itemsPerPage.value,
        sortBy: sortBy.value,
        orderBy: orderBy.value,
        search: q.value,
        stato: 3,
      },
    }))

    if (resultData.value !== null) {
      serverItems.value = resultData.value.data
      totalItems.value = resultData.value.total
    } else {
      serverItems.value = []
      totalItems.value = 0
    }
  } catch (error) {
    console.error("Errore nel caricamento dei dati della dashboard:", error)
  } finally {
    loading.value = false
  }
}

const refresh = () => {
  aggiornaTask.value = true
}
</script>

<template>
  <div class="workspace-container w-100 h-100 d-flex flex-column pa-3 overflow-hidden">

    <div class="d-flex align-center justify-space-between mb-1.5 flex-shrink-0">
      <div class="d-flex align-baseline gap-2">
        <h3 class="text-h6 font-weight-bold mb-0">
          {{ $t('Label.Dashboard-Task') || 'Dashboard Task' }}
        </h3>
        <span class="text-caption text-medium-emphasis d-none d-sm-inline">
          — Stato globale e approvazioni
        </span>
      </div>
    </div>

    <div class="dashboard-grid-layout d-flex flex-column flex-grow-1 h-0 gap-3 overflow-hidden">

      <div class="flex-shrink-0 compact-stats">
        <TaskStatistica />
      </div>

      <div class="widgets-wrapper-row d-flex flex-grow-1 h-0 gap-3">

        <div class="widget-column-half d-flex flex-column">
          <TaskApprovazione class="flex-grow-1 h-100 overflow-hidden" @refresh="refresh"/>
        </div>

        <div class="widget-column-half d-flex flex-column">
          <TaskAggiornati class="flex-grow-1 h-100 overflow-hidden" :aggiorna-task="aggiornaTask"/>
        </div>

      </div>
    </div>
  </div>
</template>

<style scoped lang="scss">
.workspace-container {
  box-sizing: border-box;
}

.gap-2 { gap: 8px; }
.gap-3 { gap: 12px; }

.dashboard-grid-layout {
  width: 100%;
}

.compact-stats {
  /* Forza eventuali sotto-elementi o card delle statistiche a ridurre i padding interni */
  :deep(.v-card-text), :deep(.v-card) {
    padding: 10px 14px !important;
  }
}

.widgets-wrapper-row {
  width: 100%;
  display: flex;
  flex-direction: row;

  @media screen and (max-width: 960px) {
    flex-direction: column;
    overflow-y: auto !important;
    height: auto !important;
  }
}

.widget-column-half {
  flex: 1;
  width: 50%;
  min-width: 0;

  @media screen and (max-width: 960px) {
    width: 100%;
    flex: none;
    height: 380px; /* Ridotta l'altezza dei widget in modalità mobile per compattezza */
  }
}
</style>
