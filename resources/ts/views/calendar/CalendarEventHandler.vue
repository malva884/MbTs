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
  <VDialog
    :model-value="props.isDrawerOpen"
    max-width="680"
    class="event-dialog-modern"
    @update:model-value="dialogModelValueUpdate"
  >
    <VCard class="event-dialog-card">
      <!-- 👉 Header -->
      <div class="event-dialog-header">
        <div class="d-flex align-center justify-space-between w-100 px-5 py-4">
          <div class="d-flex align-center gap-3">
            <div class="header-icon-wrap">
              <VIcon :icon="event.id ? 'tabler-edit' : 'tabler-calendar-plus'" size="22" />
            </div>
            <div>
              <h4 class="text-h6 font-weight-bold mb-0">
                {{ event.id ? 'Modifica Evento' : 'Nuovo Evento' }}
              </h4>
              <p class="text-xs text-disabled mb-0">Compila i dettagli dell'evento</p>
            </div>
          </div>
          <div class="d-flex align-center gap-2">
            <IconBtn
              v-show="event.id"
              variant="text"
              color="error"
              size="32"
              @click="removeEvent"
            >
              <VIcon size="18" icon="tabler-trash" />
            </IconBtn>
            <IconBtn
              variant="tonal"
              size="32"
              @click="$emit('update:isDrawerOpen', false)"
            >
              <VIcon size="18" icon="tabler-x" />
            </IconBtn>
          </div>
        </div>
      </div>

      <PerfectScrollbar class="event-dialog-scroll" :options="{ wheelPropagation: false }">
        <div class="event-dialog-body">
          <VForm ref="refForm" @submit.prevent="handleSubmit">
            <!-- 👉 Title -->
            <div class="form-section">
              <AppTextField
                v-model="event.title"
                label="Titolo"
                placeholder="es. Meeting con cliente"
                :rules="[requiredValidator]"
                density="comfortable"
                class="form-field-modern"
              />
            </div>

            <!-- 👉 Date range -->
            <div class="form-section">
              <p class="section-label">
                <VIcon icon="tabler-clock" size="16" class="mr-1" /> Date e Orari
              </p>
              <VRow dense>
                <VCol cols="6">
                  <AppDateTimePicker
                    :key="JSON.stringify(startDateTimePickerConfig)"
                    v-model="event.start"
                    :rules="[requiredValidator]"
                    label="Data Inizio"
                    placeholder="Seleziona"
                    :config="startDateTimePickerConfig"
                    density="comfortable"
                    class="form-field-modern"
                  />
                </VCol>
                <VCol cols="6">
                  <AppDateTimePicker
                    :key="JSON.stringify(endDateTimePickerConfig)"
                    v-model="event.end"
                    :rules="[requiredValidator]"
                    label="Data Fine"
                    placeholder="Seleziona"
                    :config="endDateTimePickerConfig"
                    density="comfortable"
                    class="form-field-modern"
                  />
                </VCol>
              </VRow>
              <VSwitch
                v-model="event.allDay"
                label="Tutto il giorno"
                density="compact"
                color="primary"
                class="mt-1"
              />
            </div>

            <!-- 👉 Visitors -->
            <div class="form-section">
              <p class="section-label">
                <VIcon icon="tabler-users" size="16" class="mr-1" /> Visitatori Esterni
              </p>
              <VRow dense>
                <VCol cols="5">
                  <AppTextField
                    v-model="esternoNome"
                    label="Nome"
                    placeholder="Nome visitatore"
                    density="comfortable"
                    class="form-field-modern"
                  />
                </VCol>
                <VCol cols="5">
                  <AppTextField
                    v-model="esternoEmail"
                    type="email"
                    label="Email"
                    placeholder="email@esempio.com"
                    :rules="[emailValidator]"
                    density="comfortable"
                    class="form-field-modern"
                  />
                </VCol>
                <VCol cols="2" class="d-flex align-end pb-1">
                  <VBtn
                    block
                    variant="flat"
                    color="primary"
                    size="default"
                    class="add-visitor-btn"
                    @click="addEsterno"
                  >
                    <VIcon icon="tabler-plus" size="18" />
                  </VBtn>
                </VCol>
              </VRow>

              <div v-if="event.extendedProps.esterni && event.extendedProps.esterni.length" class="visitor-list">
                <div
                  v-for="list in event.extendedProps.esterni"
                  :key="list.id"
                  class="visitor-chip"
                >
                  <VIcon icon="tabler-user" size="16" class="visitor-avatar" />
                  <span class="visitor-name">{{ list.nome }}</span>
                  <span class="visitor-email">{{ list.email }}</span>
                  <button
                    type="button"
                    class="visitor-remove"
                    @click.prevent="dellEsterno(list.id)"
                  >
                    <VIcon icon="tabler-x" size="14" />
                  </button>
                </div>
              </div>
            </div>

            <!-- 👉 Interni -->
            <div class="form-section">
              <p class="section-label">
                <VIcon icon="tabler-users-group" size="16" class="mr-1" /> Utenti Interni
              </p>
              <AppSelect
                v-model="event.extendedProps.guests"
                placeholder="Seleziona utenti da avvisare"
                :items="guestsOptions"
                :item-title="item => item.full_name"
                :item-value="item => item.id"
                chips
                multiple
                eager
                density="comfortable"
                class="form-field-modern"
              />
            </div>

            <!-- 👉 Descrizione -->
            <div class="form-section">
              <p class="section-label">
                <VIcon icon="tabler-notes" size="16" class="mr-1" /> Descrizione
              </p>
              <AppTextarea
                v-model="event.extendedProps.description"
                placeholder="Aggiungi note o dettagli dell'evento..."
                density="comfortable"
                rows="3"
                class="form-field-modern"
              />
            </div>
          </VForm>
        </div>
      </PerfectScrollbar>

      <!-- 👉 Footer -->
      <div class="event-dialog-footer">
        <VBtn
          variant="text"
          color="secondary"
          size="large"
          class="footer-btn-cancel"
          @click="onCancel"
        >
          Annulla
        </VBtn>
        <VBtn
          type="submit"
          variant="flat"
          color="primary"
          size="large"
          class="footer-btn-submit"
          :disabled="event.extendedProps.esterni !== undefined && (event.extendedProps.esterni.length > 0 && esternoNome.length <= 0 && esternoEmail.length <= 0) ? false : true"
          @click="handleSubmit"
        >
          <VIcon icon="tabler-check" size="18" class="mr-1" />
          {{ event.id ? 'Aggiorna' : 'Crea Evento' }}
        </VBtn>
      </div>
    </VCard>
  </VDialog>
</template>

<style lang="scss" scoped>
.event-dialog-card {
  border-radius: 16px !important;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(15, 23, 42, 0.2);
  display: flex;
  flex-direction: column;
  max-height: 90vh;
  background: rgb(var(--v-theme-surface));
}

.event-dialog-header {
  flex-shrink: 0;
  border-bottom: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  background: rgba(var(--v-theme-surface), 1);
}

.header-icon-wrap {
  display: flex;
  align-items: center;
  justify-content: center;
  inline-size: 42px;
  block-size: 42px;
  border-radius: 12px;
  background: rgba(var(--v-theme-primary), 0.12);
  color: rgb(var(--v-theme-primary));
}

.event-dialog-scroll {
  flex-grow: 1;
  overflow: hidden;
}

.event-dialog-body {
  padding: 1.25rem 1.5rem 1.5rem;
}

.form-section {
  margin-block-end: 1.5rem;
}

.section-label {
  display: flex;
  align-items: center;
  font-size: 0.8rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: rgba(var(--v-theme-on-surface), 0.55);
  margin-block-end: 0.6rem;
}

.form-field-modern {
  :deep(.v-field) {
    border-radius: 10px;
    transition: box-shadow 0.2s ease, border-color 0.2s ease;
  }

  :deep(.v-field--variant-outlined) {
    --v-field-border-opacity: 0.12;
  }

  :deep(.v-field--variant-outlined:hover) {
    --v-field-border-opacity: 0.2;
  }

  :deep(.v-field--focused) {
    box-shadow: 0 0 0 3px rgba(var(--v-theme-primary), 0.08);
  }

  :deep(.v-label) {
    font-weight: 600;
    font-size: 0.85rem;
  }
}

.add-visitor-btn {
  border-radius: 10px !important;
  min-block-size: 40px;
  box-shadow: 0 4px 10px rgba(var(--v-theme-primary), 0.2);
}

.visitor-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-block-start: 0.75rem;
}

.visitor-chip {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  border-radius: 10px;
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  background: rgba(var(--v-theme-on-surface), 0.02);
  transition: border-color 0.2s ease, background 0.2s ease;

  &:hover {
    border-color: rgba(var(--v-theme-primary), 0.2);
    background: rgba(var(--v-theme-primary), 0.04);
  }
}

.visitor-avatar {
  color: rgb(var(--v-theme-primary));
  opacity: 0.7;
}

.visitor-name {
  font-size: 0.84rem;
  font-weight: 600;
  color: rgba(var(--v-theme-on-surface), 0.9);
}

.visitor-email {
  font-size: 0.78rem;
  color: rgba(var(--v-theme-on-surface), 0.5);
  margin-inline-start: auto;
}

.visitor-remove {
  display: flex;
  align-items: center;
  justify-content: center;
  inline-size: 24px;
  block-size: 24px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  color: rgba(var(--v-theme-error), 0.6);
  background: transparent;
  transition: background 0.2s ease, color 0.2s ease;

  &:hover {
    background: rgba(var(--v-theme-error), 0.1);
    color: rgb(var(--v-theme-error));
  }
}

.event-dialog-footer {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 12px;
  padding: 1rem 1.5rem;
  border-top: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  background: rgba(var(--v-theme-surface), 1);
}

.footer-btn-cancel,
.footer-btn-submit {
  border-radius: 10px !important;
  font-weight: 600;
  letter-spacing: 0.2px;
  text-transform: none;
}

.footer-btn-submit {
  box-shadow: 0 8px 18px rgba(var(--v-theme-primary), 0.28);
  transition: transform 0.2s ease, box-shadow 0.2s ease;

  &:not(:disabled):hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 22px rgba(var(--v-theme-primary), 0.35);
  }
}
</style>
