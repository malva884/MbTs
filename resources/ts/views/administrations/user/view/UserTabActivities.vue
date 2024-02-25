<script lang="ts" setup>
import { VDataTable } from 'vuetify/labs/VDataTable'

import UserInvoiceTable from './UserInvoiceTable.vue'
import avatar2 from '@images/avatars/avatar-2.png'

// eslint-disable-next-line @typescript-eslint/no-unused-vars
interface Props {
  id: number
}

const props = defineProps<Props>()
const componentKey = ref(0)
let activitys = ref([])

const fetchPermissions = async () => {
  const activitiesData = await useApi<any>(createUrl(`/users/activities/${props.id}`))

  activitys = activitiesData.data
  componentKey.value += 1
}

fetchPermissions()

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
</script>

<template>
  <VRow>
    <VCol cols="6">
      <!-- 👉 Activity timeline -->
      <VCard title="User Activity Timeline">
        <VCardText>
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

            <VTimelineItem
              dot-color="error"
              size="x-small"
            >
              <div class="d-flex justify-space-between align-center flex-wrap gap-2 mb-3">
                <span class="app-timeline-title">
                  asdasdasda
                </span>
                <span class="app-timeline-meta"></span>
              </div>

              <p class="app-timeline-text mb-2">
                Invoices have been paid to the company
              </p>
              <div class="d-flex align-center mt-2">
                <VIcon
                  color="error"
                  icon="tabler-file"
                  size="18"
                  class="me-2"
                />
                <h6 class="font-weight-medium text-sm">
                  Invoices.pdf
                </h6>
              </div>
            </VTimelineItem>

            <VTimelineItem
              dot-color="primary"
              size="x-small"
            >
              <div class="d-flex justify-space-between align-center flex-wrap gap-2 mb-3">
                <span class="app-timeline-title">
                  Meeting with john
                </span>
                <span class="app-timeline-meta">45 min ago</span>
              </div>

              <p class="app-timeline-text mb-1">
                React Project meeting with john @10:15am
              </p>

              <div class="d-flex align-center mt-3">
                <VAvatar
                  size="34"
                  class="me-2"
                  :image="avatar2"
                />
                <div>
                  <h6 class="text-sm font-weight-medium mb-n1">
                    John Doe (Client)
                  </h6>
                  <span class="text-xs">CEO of Kelly Group</span>
                </div>
              </div>
            </VTimelineItem>

            <VTimelineItem
              dot-color="info"
              size="x-small"
            >
              <div class="d-flex justify-space-between align-center flex-wrap gap-2 mb-3">
                <span class="app-timeline-title">
                  Create a new react project for client
                </span>
                <span class="app-timeline-meta">{{humanTimeDiff(new Date().getTime())}}</span>
              </div>

              <p class="app-timeline-text mb-0">
                Add files to new design folder
              </p>
            </VTimelineItem>

            <VTimelineItem
              dot-color="success"
              size="x-small"
            >
              <div class="d-flex justify-space-between align-center flex-wrap gap-2 mb-3">
                <span class="app-timeline-title">
                  12 Create invoices for client
                </span>
                <span class="app-timeline-meta">5 day ago</span>
              </div>
              <p class="app-timeline-text mb-0">
                Weekly review of freshly prepared design for our new app.
              </p>
            </VTimelineItem>
          </VTimeline>
        </VCardText>
      </VCard>
    </VCol>

    <VCol cols="12">
      <UserInvoiceTable/>
    </VCol>
  </VRow>
</template>
