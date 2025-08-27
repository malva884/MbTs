<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import {watch} from "vue";

interface Props {
  fornitoreId: string
  keyTab: string
}

const props = defineProps<Props>()
const { t } = useI18n()
const componentKey = ref(0)
let activitys = ref([])

const fetchLog = async () => {
  const activitiesData = await useApi<any>(createUrl(`/qt/supplier/log/${props.fornitoreId}`))

  activitys = activitiesData.data
  componentKey.value += 1
}

fetchLog()

// Project Table Header

function humanTimeDiff(value) {
  const date = Date.parse(value)

  if (isNaN(date))
    return ''

  const now = new Date()

  // alert(now.getTime()+' '+date)
  const delta = Number.parseInt((now.getTime() - date) / 1000, 10)

  // delta = delta + (now.getTimezoneOffset() * 60)

  if (delta < 60)
    return 'less than a minute ago'

  if (delta < 120)
    return 'about a minute ago'

  if (delta < (60 * 60))
    return `${Number.parseInt(delta / 60, 10).toString()} minutes ago`

  if (delta < (120 * 60))
    return 'about an hour ago'

  if (delta < (24 * 60 * 60))
    return `about ${Number.parseInt(delta / 3600, 10).toString()} hours ago`

  if (delta < (48 * 60 * 60))
    return 'a day ago'

  return `${Number.parseInt(delta / 86400, 10).toString()} days ago`
}

watch(props, () => {
  fetchLog()
})
</script>

<template>
  <VRow>
    <VCol cols="12">
      <!-- 👉 Activity timeline -->
      <VCard :title="t('Label.Attivita-Fornitore')">
        <VCardText>
          <VList class="card-list">
            <VTimeline
            :key="componentKey"
            density="compact"
            align="start"
            truncate-line="both"
            class="v-timeline-density-compact"
          >
            <template
              v-for="activity in activitys"
              :key="activity.id"
            >
              <VTimelineItem
                :dot-color="activity.color"
                size="x-small"
              >
                <div class="d-flex justify-space-between align-center flex-wrap gap-2 mb-3">
                  <span class="app-timeline-title">
                    {{activity.subject}}
                  </span>
                  <span class="app-timeline-meta">{{humanTimeDiff(activity.created_at)}}</span>
                </div>
                <p v-html="activity.html"></p>

              </VTimelineItem>
            </template>
          </VTimeline>
          </VList>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

<style scoped lang="scss">
.card-list {
  --v-card-list-gap: 26px;
}
</style>
