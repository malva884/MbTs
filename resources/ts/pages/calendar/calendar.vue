<script setup lang="ts">
import FullCalendar from '@fullcalendar/vue3'
import { blankEvent, useCalendar } from '@/views/calendar/useCalendar'
import { useCalendarStore } from '@/views/calendar/useCalendarStore'

// Components
import CalendarEventHandler from '@/views/calendar/CalendarEventHandler.vue'

definePage({
  meta: {
    action: 'create',
    subject: 'Reception-Register',
  },
})

const routes = useRoute()

// 👉 Store
const store = useCalendarStore()

// 👉 Event
const event = ref(structuredClone(blankEvent))
const isEventHandlerSidebarActive = ref(false)

watch(isEventHandlerSidebarActive, val => {
  if (!val)
    event.value = structuredClone(blankEvent)
})

const { isLeftSidebarOpen } = useResponsiveLeftSidebar()

// 👉 useCalendar
const { refCalendar, calendarOptions, addEvent, updateEvent, removeEvent, jumpToDate } = useCalendar(event, isEventHandlerSidebarActive, isLeftSidebarOpen)

// SECTION Sidebar
// 👉 Check all
const checkAll = computed({
  /*
    GET: Return boolean `true` => if length of options matches length of selected filters => Length matches when all events are selected
    SET: If value is `true` => then add all available options in selected filters => Select All
          Else if => all filters are selected (by checking length of both array) => Empty Selected array  => Deselect All
  */
  get: () => store.availableCalendars.every(i => store.selectedCalendars.includes(i.value)),
  set: val => {
    if (val)
      store.selectedCalendars = store.availableCalendars.map(i => i.value)

    else
      store.selectedCalendars = []
  },
})
// !SECTION
</script>

<template>
  <div class="calendar-page atera-calendar-page">
    <VCard class="calendar-shell atera-shell">
      <!-- `z-index: 0` Allows overlapping vertical nav on calendar -->
      <VLayout style="z-index: 0;" class="calendar-layout-shell">
        <!-- 👉 Navigation drawer -->
        <VNavigationDrawer
          v-model="isLeftSidebarOpen"
          width="292"
          absolute
          touchless
          location="start"
          class="calendar-add-event-drawer atera-sidebar"
          :temporary="$vuetify.display.mdAndDown"
        >
          <div class="atera-sidebar-top">
            <VBtn
              block
              prepend-icon="tabler-plus"
              variant="flat"
              color="primary"
              class="atera-add-btn"
              @click="isEventHandlerSidebarActive = true"
            >
              Add event
            </VBtn>
          </div>

          <VDivider />

          <div class="d-flex align-center justify-center pa-2 mb-3">
            <AppDateTimePicker
              :model-value="new Date().toJSON().slice(0, 10)"
              :config="{ inline: true }"
              class="calendar-date-picker"
              @input="jumpToDate($event.target.value)"
            />
          </div>

          <VDivider />
          <div class="pa-7 atera-filters-wrap">
            <p class="text-sm text-uppercase text-disabled mb-3 atera-filter-title">
              FILTER
            </p>

            <div class="d-flex flex-column calendars-checkbox">
              <VCheckbox
                v-model="checkAll"
                label="View all"
                class="atera-check"
              />
              <VCheckbox
                v-for="calendar in store.availableCalendars"
                :key="calendar.value"
                v-model="store.selectedCalendars"
                :value="calendar.value"
                :color="calendar.color"
                :label="calendar.label"
                class="atera-check"
              />
            </div>
          </div>
        </VNavigationDrawer>

        <VMain class="calendar-main atera-main">
          <VCard flat class="calendar-content-shell atera-content">
            <FullCalendar
              ref="refCalendar"
              :options="calendarOptions"
              class="atera-fullcalendar"
            />
          </VCard>
        </VMain>
      </VLayout>
    </VCard>
    <CalendarEventHandler
      v-model:isDrawerOpen="isEventHandlerSidebarActive"
      :event="event"
      @add-event="addEvent"
      @update-event="updateEvent"
      @remove-event="removeEvent"
    />
  </div>
</template>

<style lang="scss">
@use "@core-scss/template/libs/full-calendar";

.atera-calendar-page {
  background: linear-gradient(180deg, rgba(var(--v-theme-surface), 0.24) 0%, rgba(var(--v-theme-background), 0.6) 100%);
  border-radius: 14px;
}

.atera-shell {
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  border-radius: 14px !important;
  box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
  background: rgba(var(--v-theme-surface), 0.9);
}

.atera-sidebar {
  background: rgba(var(--v-theme-surface), 0.96) !important;
  border-inline-end: 1px solid rgba(var(--v-theme-on-surface), 0.08);
}

.atera-sidebar-top {
  margin: 1.1rem;
}

.atera-add-btn {
  border-radius: 10px !important;
  font-weight: 600;
  letter-spacing: 0.2px;
  box-shadow: 0 8px 18px rgba(var(--v-theme-primary), 0.28);
}

.atera-filters-wrap {
  padding-top: 1.4rem !important;
}

.atera-filter-title {
  font-weight: 700;
  letter-spacing: 0.8px;
}

.atera-check {
  margin-block: 2px;
}

.calendars-checkbox {
  .v-label {
    color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));
    opacity: var(--v-high-emphasis-opacity);
    font-size: 0.9rem;
  }
}

.calendar-add-event-drawer {
  &.v-navigation-drawer:not(.v-navigation-drawer--temporary) {
    border-end-start-radius: 0.375rem;
    border-start-start-radius: 0.375rem;
  }
}

.calendar-date-picker {
  display: none;

  +.flatpickr-input {
    +.flatpickr-calendar.inline {
      border: none;
      box-shadow: none;

      .flatpickr-months {
        border-block-end: none;
      }
    }
  }

  & ~ .flatpickr-calendar .flatpickr-weekdays {
    margin-block: 0 4px;
  }
}

.atera-content {
  border-radius: 0 14px 14px 0 !important;
  border-inline-start: 1px solid rgba(var(--v-theme-on-surface), 0.06);
  background: rgba(var(--v-theme-surface), 0.9);
}

.atera-fullcalendar .fc {
  color: rgb(var(--v-theme-on-surface));
}

.atera-fullcalendar .fc .fc-toolbar {
  margin: 0;
  padding: 14px 16px 10px;
  border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.06);
}

.atera-fullcalendar .fc .fc-toolbar-title {
  font-size: 1.08rem;
  font-weight: 700;
  letter-spacing: -0.2px;
}

.atera-fullcalendar .fc .fc-button {
  border-radius: 9px !important;
  border: 1px solid rgba(var(--v-theme-on-surface), 0.12) !important;
  background: rgba(var(--v-theme-surface), 0.85) !important;
  color: rgb(var(--v-theme-on-surface)) !important;
  text-transform: none;
  box-shadow: none !important;
}

.atera-fullcalendar .fc .fc-button-primary:not(:disabled).fc-button-active,
.atera-fullcalendar .fc .fc-button-primary:not(:disabled):active {
  background: rgba(var(--v-theme-primary), 0.18) !important;
  border-color: rgba(var(--v-theme-primary), 0.4) !important;
  color: rgb(var(--v-theme-primary)) !important;
}

.atera-fullcalendar .fc .fc-col-header-cell {
  background: rgba(var(--v-theme-on-surface), 0.02);
}

.atera-fullcalendar .fc .fc-scrollgrid {
  border-color: rgba(var(--v-theme-on-surface), 0.07);
}

.atera-fullcalendar .fc .fc-day-today {
  background: rgba(var(--v-theme-primary), 0.08) !important;
}

.atera-fullcalendar .fc .fc-daygrid-day-number {
  font-weight: 600;
  opacity: 0.92;
}

.atera-fullcalendar .fc .fc-event {
  border: 0;
  border-radius: 7px;
  font-weight: 500;
  padding-inline: 4px;
}
</style>

<style lang="scss" scoped>
.calendar-page {
  height: calc(100vh - 7.5rem);
  height: calc(100dvh - 7.5rem);
  overflow: hidden;
}

.calendar-shell,
.calendar-layout-shell,
.calendar-main,
.calendar-content-shell {
  height: 100%;
}

.calendar-shell,
.calendar-content-shell {
  overflow: hidden;
}

:deep(.fc) {
  height: 100% !important;
}

:deep(.fc-view-harness) {
  min-height: 0;
}

.v-layout {
  overflow: visible !important;
}
</style>
