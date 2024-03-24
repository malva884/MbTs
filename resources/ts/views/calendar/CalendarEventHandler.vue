<script setup lang="ts">
import type { Options } from 'flatpickr/dist/types/options'
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { VForm } from 'vuetify/components/VForm'
import type { Event, NewEvent } from './types'
import { useCalendarStore } from './useCalendarStore'
import avatar1 from '@images/avatars/avatar-1.png'

// 👉 store

const props = defineProps<Props>()

const emit = defineEmits<{
  (e: 'update:isDrawerOpen', val: boolean): void
  (e: 'addEvent', val: NewEvent): void
  (e: 'updateEvent', val: Event): void
  (e: 'removeEvent', eventId: string): void
}>()

definePage({
  meta: {
    action: 'read',
    subject: 'user',
  },
})

interface Props {
  isDrawerOpen: boolean
  event: (Event | NewEvent)
}

const refForm = ref<VForm>()
const esternoNome = ref()
const esternoEmail = ref()

const listaEsterni = ref<any>([])

// 👉 Event
const event = ref<Event>(JSON.parse(JSON.stringify(props.event)))

const resetEvent = () => {
  listaEsterni.value = []
  event.value = JSON.parse(JSON.stringify(props.event))
  console.log(event.value)
  nextTick(() => {
    refForm.value?.resetValidation()
  })
}

watch(() => props.isDrawerOpen, resetEvent)

const removeEvent = () => {
  emit('removeEvent', String((event.value as Event).id))

  // Close drawer
  emit('update:isDrawerOpen', false)
}

const handleSubmit = () => {
  refForm.value?.validate()
    .then(({ valid }) => {
      if (valid) {
        // If id exist on id => Update event
        if ('id' in event.value)
          emit('updateEvent', event.value)

        // Else => add new event
        else emit('addEvent', event.value)

        // Close drawer
        emit('update:isDrawerOpen', false)
      }
    })
}

const guestsOptions = [
  { avatar: avatar1, full_name: '- Commerciale', id: 'sterlite.com_188espiaif2riib0jmt4vkocrfgbk6gb6sp38e1m6co3ge9p70@resource.calendar.google.com' },
  { avatar: avatar1, full_name: '-  Ghitti', id: 'sterlite.com_3830383535383937343438@resource.calendar.google.com' },
  { avatar: avatar1, full_name: '- Vascelli', id: 'sterlite.com_3933323434333537393933@resource.calendar.google.com' },
]

const userOptions = async () => {
  const resultData = await useApi<any>(createUrl('/users/getUsers'))

  resultData.data.value.data.forEach((value) => {
    guestsOptions.push({ avatar: avatar1, full_name: value.full_name, id: value.email })
  })
}

userOptions()


// 👉 Form

const onCancel = () => {
  emit('update:isDrawerOpen', false)

  nextTick(() => {
    refForm.value?.reset()
    resetEvent()
    refForm.value?.resetValidation()
  })
  event.value.extendedProps.esterni.length = 0
  listaEsterni.value = []
  esternoNome.value = esternoEmail.value = ''
}

const startDateTimePickerConfig = computed(() => {
  const config: Options = { enableTime: !event.value.allDay, dateFormat: `Y-m-d${event.value.allDay ? '' : ' H:i'}` }

  if (event.value.end)
    config.maxDate = event.value.end

  return config
})

const endDateTimePickerConfig = computed(() => {
  const config: Options = { enableTime: !event.value.allDay, dateFormat: `Y-m-d${event.value.allDay ? '' : ' H:i'}` }

  if (event.value.start)
    config.minDate = event.value.start

  return config
})

const addEsterno = () => {
  const id = window.URL.createObjectURL(new Blob([])).slice(-36)

  listaEsterni.value.push({ nome: esternoNome.value, email: esternoEmail.value, done: false, id: id })
  event.value.extendedProps.esterni = listaEsterni.value
  esternoNome.value = ''
  esternoEmail.value = ''
}

const dellEsterno = (id: string) => {
  listaEsterni.value = listaEsterni.value.filter((esterno) => esterno.id !== id)
  event.value.extendedProps.esterni = listaEsterni.value
}

const dialogModelValueUpdate = (val: boolean) => {
  emit('update:isDrawerOpen', val)
}
</script>

<template>
  <VNavigationDrawer
    temporary
    location="end"
    :model-value="props.isDrawerOpen"
    width="800"
    class="scrollable-content"
    @update:model-value="dialogModelValueUpdate"
  >
    <!-- 👉 Header -->
    <AppDrawerHeaderSection
      :title="event.id ? 'Update Event' : 'Add Event'"
      @cancel="$emit('update:isDrawerOpen', false)"
    >
      <template #beforeClose>
        <IconBtn
          v-show="event.id"
          @click="removeEvent"
        >
          <VIcon
            size="18"
            icon="tabler-trash"
          />
        </IconBtn>
      </template>
    </AppDrawerHeaderSection>

    <PerfectScrollbar :options="{ wheelPropagation: false }">
      <VCard flat>
        <VCardText>
          <!-- SECTION Form -->
          <VForm
            ref="refForm"
            @submit.prevent="handleSubmit"
          >
            <VRow>
              <!-- 👉 Title -->
              <VCol cols="12">
                <AppTextField
                  v-model="event.title"
                  label="Titolo"
                  placeholder="Meeting with Jane"
                  :rules="[requiredValidator]"
                />
              </VCol>

              <!-- 👉 Start date -->
              <VCol cols="6">
                <AppDateTimePicker
                  :key="JSON.stringify(startDateTimePickerConfig)"
                  v-model="event.start"
                  :rules="[requiredValidator]"
                  label="Data Inizio"
                  placeholder="Select Date"
                  :config="startDateTimePickerConfig"
                />
              </VCol>

              <!-- 👉 End date -->
              <VCol cols="6">
                <AppDateTimePicker
                  :key="JSON.stringify(endDateTimePickerConfig)"
                  v-model="event.end"
                  :rules="[requiredValidator]"
                  label="Data Fine"
                  placeholder="Select End Date"
                  :config="endDateTimePickerConfig"
                />
              </VCol>

              <!-- 👉 All day -->
              <VCol cols="12">
                <VSwitch
                  v-model="event.allDay"
                  label="All day"
                />
              </VCol>

              <!-- 👉 Visitors -->
              <VCol cols="12"
                    md="5">
                <AppTextField
                  v-model="esternoNome"
                  label="Nome Visitatore"
                  placeholder="Nome Visitatore"
                />
              </VCol>

              <VCol cols="8"
                    md="5">
                <AppTextField
                  v-model="esternoEmail"
                  type="email"
                  label="Email Visitatore"
                  placeholder="Email Visitatore"
                  :rules="[emailValidator]"
                />
              </VCol>
              <VCol cols="2 mt-5">
                <VBtn @click="addEsterno">Aggiungi</VBtn>
              </VCol>
              <VCol cols="12">
                <div >
                  <div class="tasks-container" >
                    <div class="card-task"
                         v-bind:class="{ done: listaEsterni.done }"
                         v-for="list in event.extendedProps.esterni"
                    >

                      <strong class="card-task__name">
                        {{ list.nome + '&nbsp; &nbsp; &nbsp; &nbsp; ' + list.email }}
                      </strong>

                      <button
                        type="button"
                        class="card-task__button"
                        v-on:click.prevent="dellEsterno(list.id)"
                      >
                        ❌
                      </button>
                    </div>
                  </div>
                </div>
              </VCol>

              <!-- 👉 Interni -->
              <VCol cols="12">
                <AppSelect
                  v-model="event.extendedProps.guests"
                  label="Utenti Interni Da Avvisare"
                  placeholder="Select Utenti Interni"
                  :items="guestsOptions"
                  :item-title="item => item.full_name"
                  :item-value="item => item.id"
                  chips
                  multiple
                  eager
                />
              </VCol>

              <!-- 👉 Descrizione -->
              <VCol cols="12">
                <AppTextarea
                  v-model="event.extendedProps.description"
                  label="Descrizione"
                  placeholder="Meeting description"
                />
              </VCol>

              <!-- 👉 Form buttons -->
              <VCol cols="12">
                <VBtn
                  type="submit"
                  class="me-3"
                  :disabled=" event.extendedProps.esterni !== undefined && (event.extendedProps.esterni.length > 0 && esternoNome.length <= 0 && esternoEmail.length <= 0) ? false:true"
                >
                  Submit
                </VBtn>
                <VBtn
                  variant="outlined"
                  color="secondary"
                  @click="onCancel"
                >
                  Cancel
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
          <!-- !SECTION -->
        </VCardText>
      </VCard>
    </PerfectScrollbar>
  </VNavigationDrawer>
</template>
