<script setup lang="ts">
// eslint-disable-next-line import/no-unresolved
import pdf from '@images/icons/project-icons/pdf.png'
import avatar1 from '@images/avatars/avatar-1.png'
import avatar2 from '@images/avatars/avatar-2.png'
import pumaShoes from '@images/pages/puma-shoes.jpeg'
import moment from "moment/moment";

interface Props {
  taskId: string
}

const props = defineProps<Props>()
const items = ref({})
const parser = new DOMParser()

function formatDate(date: string): string {
  return moment(String(date)).format('DD/MM/YY HH:mm')
}

const attivitaTaskLoad = async () => {
  const { data: attivitaData } = await useApi<any>(createUrl(`/task/${props.taskId}/task_log`))

  items.value = attivitaData.value
}

attivitaTaskLoad()
watch(props, () => {
  attivitaTaskLoad()
})
</script>

<template>
  <VCard :title="$t('local.Task-Log')">
    <VCardText>
      <VTimeline
        side="end"
        align="start"
        truncate-line="both"
        density="compact"
        class="v-timeline-icon-only "
        style="overflow-y:scroll; overflow-x:hidden; height:600px;"
      >
        <VTimelineItem
          v-for="item in items"
          :key="item.id"
        >
          <template #icon>
            <VIcon
              size="20"
              icon="tabler-circle"
              :color="item.colore"
            />
          </template>

          <VCard
            :class="`bg-light-${item.colore}`"
            variant="text"
            class="mt--1"
          >
            <VCardText class="mt-0">
              <div class="d-flex justify-space-between mb-0">
                <span v-if="item.titolo !== undefined" class="app-timeline-title">{{ `${item.titolo} ( ${item.codice} )` }}</span>
                <span v-else class="app-timeline-title">{{ item.full_name }}</span>
                <span class="app-timeline-meta">{{ formatDate(item.created_at) }}</span>
              </div>
              <div class="d-flex justify-space-between mb-0" v-if="item.titolo !== undefined">
                <span class="app-timeline-title">{{ item.full_name }}</span>
              </div>

              <p
                class="app-timeline-text mb-0"
                v-html="item.azione"
              >
              </p>
            </VCardText>
          </VCard>
        </VTimelineItem>

        <!-- SECTION Flight -->
        <VTimelineItem>
          <template #icon>
            <VIcon
              size="20"
              icon="tabler-send"
              color="primary"
            />
          </template>

          <VCard
            class="bg-light-error"
            variant="text"
          >
            <VCardText>
              <div class="d-flex justify-space-between align-center mb-1">
                <span class="app-timeline-title">Get on the flight</span>
                <small class="app-timeline-meta">Wednesday</small>
              </div>

              <div class="app-timeline-text mb-1">
                <span>Charles de Gaulle Airport, Paris</span>
                <VIcon
                  size="20"
                  icon="tabler-arrow-right"
                  class="mx-2 flip-in-rtl"
                />
                <span>Heathrow Airport, London</span>
              </div>
              <p class="app-timeline-meta mb-1">
                6:30 AM
              </p>

              <div class="app-timeline-text d-flex align-center gap-2">
                <div>

                </div>

                <span>booking-card.pdf</span>
              </div>
            </VCardText>
          </VCard>
        </VTimelineItem>
        <!-- !SECTION -->

        <!-- SECTION Interview Schedule -->
        <VTimelineItem>
          <template #icon>
            <VIcon
              size="20"
              icon="tabler-brush"
              color="success"
            />
          </template>

          <VCard
            class="bg-light-primary"
            variant="text"
          >
            <VCardText>
              <div class="d-flex justify-space-between align-center mb-1">
                <span class="app-timeline-title">Interview Schedule</span>
                <span class="app-timeline-meta">April, 18</span>
              </div>

              <p class="app-timeline-text mb-0">
                Lorem ipsum, dolor sit amet consectetur adipisicing elit. Possimus quos, voluptates voluptas rem veniam
                expedita.
              </p>

              <!-- 👉 Divider -->
              <VDivider class="my-4"/>

              <!-- 👉 Person -->
              <div class="d-flex justify-space-between align-center">
                <!-- 👉 Avatar & Personal Info -->
                <span class="d-flex align-center">
                  <VAvatar
                    size="40"
                    :image="avatar2"
                    class="me-2"
                  />

                  <div>
                    <h6 class="text-sm font-weight-medium">Rebecca Godman</h6>
                    <span class="text-xs">JavaScript Developer</span>
                  </div>
                </span>

                <!-- 👉 Person Actions -->
                <div>
                  <IconBtn>
                    <VIcon
                      size="20"
                      icon="tabler-message"
                    />
                  </IconBtn>
                  <IconBtn>
                    <VIcon
                      size="20"
                      icon="tabler-phone"
                    />
                  </IconBtn>
                </div>
              </div>
            </VCardText>
          </VCard>
        </VTimelineItem>
        <!-- !SECTION -->

        <!-- SECTION Puma Shoes -->
        <VTimelineItem>
          <template #icon>
            <VIcon
              size="20"
              icon="tabler-basket"
              color="error"
            />
          </template>

          <VCard
            class="bg-light-info"
            variant="text"
          >
            <!-- 👉 content -->
            <VCardText>
              <div class="d-flex justify-space-between align-center mb-1">
                <span class="app-timeline-title">Sold Puma POPX Blue Color</span>
                <span class="app-timeline-meta">January, 10</span>
              </div>

              <div class="d-flex align-sm-center flex-sm-row flex-column mb-3 gap-y-2">
                <VImg
                  height="62"
                  width="62"
                  :src="pumaShoes"
                  class="rounded me-4"
                />

                <div>
                  <span class="app-timeline-text">
                    PUMA presents the latest shoes from its collection. Light &amp; comfortable made with highly durable material.
                  </span>
                </div>
              </div>

              <div class="d-flex justify-space-between flex-column flex-sm-row gap-3">
                <div class="text-sm-center">
                  <p class="text-high-emphasis text-sm font-weight-medium mb-0">
                    Customer
                  </p>
                  <span class="text-xs">Micheal Scott</span>
                </div>
                <div class="text-sm-center">
                  <p class="text-high-emphasis text-sm font-weight-medium mb-0">
                    Price
                  </p>
                  <span class="text-xs">$375.00</span>
                </div>
                <div class="text-sm-center">
                  <p class="text-high-emphasis text-sm font-weight-medium mb-0">
                    Quantity
                  </p>
                  <span class="text-xs">1</span>
                </div>
              </div>
            </VCardText>
          </VCard>
        </VTimelineItem>
        <!-- !SECTION -->

        <!-- 👉 Design Review -->
        <VTimelineItem>
          <template #icon>
            <VIcon
              size="20"
              icon="tabler-user-circle"
              color="info"
            />
          </template>

          <VCard
            class="bg-light-success"
            variant="text"
          >
            <VCardText>
              <div class="d-flex justify-space-between mb-1">
                <span class="app-timeline-title">Design Review</span>
                <span class="app-timeline-meta">September, 20</span>
              </div>

              <p class="app-timeline-text mb-2">
                Weekly review of freshly prepared design for our new application.
              </p>
              <div class="d-flex align-center">
                <VAvatar
                  size="40"
                  class="me-2"
                >
                  <VImg :src="avatar1"/>
                </VAvatar>

                <h6 class="text-sm font-weight-medium">
                  John Doe (Client)
                </h6>
              </div>
            </VCardText>
          </VCard>
        </VTimelineItem>
      </VTimeline>
    </VCardText>
  </VCard>
</template>
