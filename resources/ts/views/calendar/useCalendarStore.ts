import type { Event, NewEvent } from './types'

const user =  useCookie('userData')

export const useCalendarStore = defineStore('calendar', {
  // arrow function recommended for full type inference
  state: () => ({
    availableCalendars: [
      {
        color: 'info',
        label: user.value.fullName,
        value: user.value.email,
      }
    ],
    selectedCalendars: [user.value.email],
  }),
  actions: {
    async fetchEvents() { 
      const { data, error } = await useApi<any>(createUrl('/reception/getResources'))

      if (error.value)
        return error.value

      return data.value
    },
    async addEvent(event: NewEvent) {console.log(event)
      await $api('/reception/addEvent/', {
        method: 'POST',
        body: event,
      })
    },
    async updateEvent(event: Event) {
      return await $api(`/reception/editEvent/${event.id}`, {
        method: 'PUT',
        body: event,
      })
    },
    async removeEvent(eventId: string) {
      return await $api(`/apps/calendar/${eventId}`, {
        method: 'DELETE',
      })
    },

  },
})
