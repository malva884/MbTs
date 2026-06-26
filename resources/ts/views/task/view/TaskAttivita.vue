<script setup lang="ts">
import { ref, watch } from 'vue'
import moment from "moment/moment"

interface Props {
  taskId: string
}

const props = defineProps<Props>()
const items = ref<any[]>([])

function formatDate(date: string): string {
  return moment(String(date)).format('DD/MM/YY HH:mm')
}

const attivitaTaskLoad = async () => {
  if (!props.taskId) return
  const { data: attivitaData } = await useApi<any>(createUrl(`/task/${props.taskId}/task_log`))
  items.value = attivitaData.value ?? []
}

// Caricamento iniziale
attivitaTaskLoad()

defineExpose({ refresh: attivitaTaskLoad })

// Monitora il cambio del taskId per ricaricare i dati corretto
watch(() => props.taskId, () => {
  attivitaTaskLoad()
})
</script>

<template>
  <div class="w-100">
    <div v-if="items.length === 0" class="text-center py-6 text-disabled text-caption">
      Nessun registro di attività presente.
    </div>

    <VTimeline
      v-else
      side="end"
      align="start"
      truncate-line="both"
      density="compact"
      class="v-timeline-icon-only pa-0"
    >
      <VTimelineItem
        v-for="item in items"
        :key="item.id"
      >
        <template #icon>
          <VIcon
            size="16"
            icon="tabler-circle-filled"
            :color="item.colore || 'secondary'"
          />
        </template>

        <VCard
          :class="`bg-light-${item.colore || 'secondary'} rounded-sm mb-2`"
          variant="text"
        >
          <VCardText class="pa-3">
            <div class="d-flex justify-space-between align-start flex-wrap gap-1 mb-1">
              <span v-if="item.titolo !== undefined" class="app-timeline-title font-weight-bold text-body-2 text-high-emphasis">
                {{ item.titolo }} <span v-if="item.codice" class="text-disabled text-caption">({{ item.codice }})</span>
              </span>
              <span v-else class="app-timeline-title font-weight-bold text-body-2 text-high-emphasis">
                {{ item.full_name }}
              </span>
              <span class="app-timeline-meta text-xs text-disabled">{{ formatDate(item.created_at) }}</span>
            </div>

            <div class="d-flex mb-1" v-if="item.titolo !== undefined && item.full_name">
              <span class="text-caption text-medium-emphasis font-weight-medium">{{ item.full_name }}</span>
            </div>

            <div
              class="app-timeline-text text-caption text-wrap mt-1 text-medium-emphasis"
              v-html="item.azione"
            ></div>
          </VCardText>
        </VCard>
      </VTimelineItem>
    </VTimeline>
  </div>
</template>

<style lang="scss" scoped>
.v-timeline-icon-only {
  :deep(.v-timeline-item__activity) {
    inset-block-start: 16px !important;
  }
}

.text-wrap {
  white-space: normal !important;
  word-break: break-word !important;
}
</style>
