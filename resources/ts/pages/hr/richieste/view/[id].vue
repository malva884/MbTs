<script setup lang="ts">
import MCalendar from 'mpvue-calendar'
import ListOffEmploee from '@/views/hr/richieste/view/ListOffEmploee.vue'

definePage({
  meta: {
    action: 'read',
    subject: 'Hr-Richieste',
  },
})

const route = useRoute('hr-richieste-view-id')

const view = ref(false)
const approvazione = ref(false)
const isDialogVisible = ref(false)
const meseUno = ref()
const meseDue = ref()
const meseTre = ref()
const meseQuattro = ref()
const meseCinque = ref()
const meseSei = ref()
const meseSette = ref()
const meseOtto = ref()
const meseNove = ref()
const meseDieci = ref()
const meseUndici = ref()
const meseDodici = ref()
const dates = ref()
const datesDisabled = ref()
const richiesta = ref<any>({})
const giorni = ref<any>({})
const log = ref<any>({})
const nota = ref()
const holidays = ref({})

const approvatoriItems = async () => {
  const { data: resultData } = await useApi<any>(createUrl(`/hr/requests/log/${route.params.id}`))
  log.value = resultData.value
}

const loadItems = async () => {
  await approvatoriItems()

  const { data: resultData } = await useApi<any>(createUrl(`/hr/requests/view/${route.params.id}`))

  giorni.value = resultData.value.objs
  richiesta.value = resultData.value.richiesta
  holidays.value = resultData.value.holidays
  dates.value = resultData.value.data
  meseUno.value = [resultData.value.CalUno]
  meseDue.value = [resultData.value.CalDue]
  meseTre.value = [resultData.value.CalTre]
  meseQuattro.value = [resultData.value.CalQuattro]
  meseCinque.value = [resultData.value.CalCinque]
  meseSei.value = [resultData.value.CalSei]
  meseSette.value = [resultData.value.CalSette]
  meseOtto.value = [resultData.value.CalOtto]
  meseNove.value = [resultData.value.CalNove]
  meseDieci.value = [resultData.value.CalDieci]
  meseUndici.value = [resultData.value.CalUndici]
  meseDodici.value = [resultData.value.CalDodici]
  datesDisabled.value = resultData.value.Disableds
  approvazione.value = resultData.value.approvazione
  view.value = true
}

loadItems()

const resolveTipologia = (tipologia: string) => {
  const mapping: Record<string, { color: string; text: string; variant: string }> = {
    '1':   { color: 'success', text: 'Ferie', variant: 'tonal' },
    '2':   { color: 'purple',  text: '104', variant: 'tonal' },
    '5':   { color: 'info',    text: 'Permesso', variant: 'tonal' },
    '101': { color: 'warning', text: 'Annullamento Richiesta', variant: 'flat' },
    '102': { color: 'warning', text: 'Annullamento 104', variant: 'flat' },
    '105': { color: 'warning', text: 'Annullamento Permesso', variant: 'flat' },
  }
  return mapping[tipologia] || { color: 'secondary', text: '---', variant: 'tonal' }
}

const resolveStato = (stato: string) => {
  const mapping: Record<string, { color: string; text: string; icon: string }> = {
    '1': { color: 'success', text: 'Approvato', icon: 'tabler-check' },
    '0': { color: 'warning', text: 'Bocciato', icon: 'tabler-x' },
  }
  return mapping[stato] || { color: 'info', text: 'In Approvazione', icon: 'tabler-circle-dash' }
}

const nega = () => {
  isDialogVisible.value = true
}

const save = async (stato: boolean) => {
  await $api(`/hr/requests/save/${route.params.id}`, {
    method: 'POST',
    body: {
      nota: nota.value,
      esito: stato,
    },
  })

  await loadItems()
  isDialogVisible.value = false
}
</script>

<template>
  <div v-if="view" class="pa-1">

    <VRow class="align-center mb-6">
      <VCol cols="12" md="8">
        <div class="d-flex align-center gap-3">
          <VAvatar color="primary" variant="tonal" size="56" class="font-weight-bold text-h5 text-uppercase">
            {{ richiesta.dipendente_cognome?.charAt(0) }}{{ richiesta.dipendente_nome?.charAt(0) }}
          </VAvatar>
          <div>
            <h1 class="text-h4 font-weight-bold text-high-emphasis mb-1">
              {{ richiesta.dipendente_cognome }} {{ richiesta.dipendente_nome }}
            </h1>
            <p class="text-body-2 text-medium-emphasis mb-0">
              {{ $t('Label.Matricola') }}: <span class="font-weight-bold text-high-emphasis">{{ richiesta.dipendente_matricola }}</span>
              <span class="mx-2 text-disabled">|</span>
              {{ $t('Label.Centro-Di-Costo') }}: <span class="font-weight-bold text-high-emphasis">{{ richiesta.centro_di_costo }}</span>
            </p>
          </div>
        </div>
      </VCol>
      <VCol cols="12" md="4" class="text-md-end">
        <VChip
          :color="resolveStato(richiesta.stato).color"
          size="large"
          class="text-uppercase font-weight-bold px-4"
          elevation="1"
        >
          <VIcon start :icon="resolveStato(richiesta.stato).icon" size="18" />
          {{ resolveStato(richiesta.stato).text }}
        </VChip>
      </VCol>
    </VRow>

    <VRow>

      <VCol cols="12" md="9">
        <VRow>

          <VCol cols="12">
            <VCard variant="outlined">
              <VCardItem class="py-4">
                <VCardTitle class="text-h6 font-weight-bold">
                  {{ $t('Label.Info-Richiesta') }}
                </VCardTitle>
              </VCardItem>
              <VDivider />

              <VCardText class="py-6">
                <VRow row-gap="4">
                  <VCol cols="12" md="4">
                    <div class="text-caption text-medium-emphasis mb-1">{{ $t('Label.Dipendente') }}</div>
                    <div class="text-body-1 font-weight-bold">
                      {{ richiesta.dipendente_cognome }} {{ richiesta.dipendente_nome }}
                    </div>
                  </VCol>

                  <VCol cols="6" sm="3" md="2">
                    <div class="text-caption text-medium-emphasis mb-1">{{ $t('Label.Tipologia-Richiesta') }}</div>
                    <div>
                      <VChip
                        :color="resolveTipologia(richiesta.tipologia).color"
                        :variant="resolveTipologia(richiesta.tipologia).variant"
                        size="small"
                        class="font-weight-bold"
                      >
                        {{ resolveTipologia(richiesta.tipologia).text }}
                      </VChip>
                    </div>
                  </VCol>

                  <VCol cols="6" sm="3" md="2">
                    <div class="text-caption text-medium-emphasis mb-1">{{ $t('Label.Data-Richiesta') }}</div>
                    <div class="text-body-1 font-weight-medium text-high-emphasis">{{ richiesta.data_richiesta }}</div>
                  </VCol>

                  <VCol cols="6" sm="3" md="2">
                    <div class="text-caption text-medium-emphasis mb-1">{{ $t('Label.Numero-Ore-Permesso') }}</div>
                    <div class="text-body-1 font-weight-bold">
                      <span v-if="richiesta.tipologia === '5' || richiesta.tipologia === '105'" class="text-info">
                        {{ giorni[0]?.ore_richieste || '---' }} ore
                      </span>
                      <span v-else class="text-disabled">---</span>
                    </div>
                  </VCol>

                  <VCol cols="6" sm="3" md="2">
                    <div class="text-caption text-medium-emphasis mb-1">{{ $t('Label.Ora-Inizio-Fine-Permesso') }}</div>
                    <div class="text-body-1 font-weight-bold">
                      <span v-if="richiesta.tipologia === '5' || richiesta.tipologia === '105'" class="text-info">
                        {{ giorni[0]?.ora_inizio || '--:--' }} - {{ giorni[0]?.ora_fine || '--:--' }}
                      </span>
                      <span v-else class="text-disabled">---</span>
                    </div>
                  </VCol>
                </VRow>

                <VRow row-gap="4" class="mt-4">
                  <VCol cols="12" md="6">
                    <div class="text-caption text-medium-emphasis mb-1">{{ $t('Label.Note') }}</div>
                    <div class="text-body-1 pa-3 rounded bg-light text-italic border-s-primary min-height-box">
                      {{ richiesta.note || 'Nessuna nota aggiuntiva.' }}
                    </div>
                  </VCol>

                  <VCol cols="12" md="6">
                    <div class="text-caption text-warning font-weight-medium mb-1">{{ $t('Label.Motivazione') }}</div>
                    <div class="text-body-1 pa-3 rounded bg-light text-italic border-s-warning min-height-box">
                      {{ richiesta.motivazione || 'Nessuna motivazione specificata.' }}
                    </div>
                  </VCol>
                </VRow>
              </VCardText>
            </VCard>
          </VCol>

          <VCol cols="12">
            <VCard variant="outlined">
              <VCardItem class="py-4">
                <VCardTitle class="text-h6 font-weight-bold">{{ $t('label.Giorni-Richiesti') }}</VCardTitle>
                <template #append>
                  <div class="d-flex gap-4 text-caption text-medium-emphasis align-center">
                    <div class="d-flex align-center gap-1">
                      <span class="d-inline-block bg-info rounded-circle" style="width: 8px; height: 8px;"></span>
                      Giorni Richiesti
                    </div>
                    <div class="d-flex align-center gap-1">
                      <span class="d-inline-block border text-center rounded bg-white text-error font-weight-bold px-1" style="font-size: 10px; line-height: 14px;">----</span>
                      Giorni Emergenza
                    </div>
                  </div>
                </template>
              </VCardItem>
              <VDivider />
              <VCardText class="pa-4">
                <VRow>
                  <VCol
                    v-for="(mese, index) in [meseUno, meseDue, meseTre, meseQuattro, meseCinque, meseSei, meseSette, meseOtto, meseNove, meseDieci, meseUndici, meseDodici]"
                    :key="index"
                    cols="12" sm="6" md="3"
                  >
                    <div class="calendar-wrapper border rounded pa-2 disable-click">
                      <MCalendar
                        :holidays="holidays"
                        selectMode="multi"
                        class-name="monthRange-mode"
                        mode="monthRange"
                        :monthRange="mese"
                        :selectDate="dates"
                      />
                    </div>
                  </VCol>
                </VRow>
              </VCardText>
            </VCard>
          </VCol>
        </VRow>
      </VCol>

      <VCol cols="12" md="3">
        <VRow>

          <VCol cols="12">
            <VCard variant="outlined">
              <VCardItem class="py-4">
                <VCardTitle class="text-subtitle-1 font-weight-bold">{{ $t('Label.Approvatori') }}</VCardTitle>
              </VCardItem>
              <VDivider />
              <VCardText class="pa-0">
                <VList class="py-0" density="compact">
                  <VListItem
                    v-for="user in log"
                    :key="user.id"
                    class="py-3 border-b-light"
                  >
                    <template #title>
                      <div class="text-body-2 font-weight-medium text-high-emphasis">{{ user.approvatore }}</div>
                    </template>
                    <template #append>
                      <VChip
                        :color="resolveStato(user.stato).color.includes('success') ? 'success' : 'warning'"
                        size="x-small"
                        variant="flat"
                        class="font-weight-bold px-2"
                      >
                        {{ resolveStato(user.stato).text }}
                      </VChip>
                    </template>
                  </VListItem>
                  <VListItem v-if="!log || log.length === 0" class="text-center text-disabled py-6">
                    Nessun passaggio registrato.
                  </VListItem>
                </VList>
              </VCardText>
            </VCard>
          </VCol>

          <VCol cols="12" v-if="approvazione">
            <VRow row-gap="2">
              <VCol cols="12">
                <VBtn
                  color="success"
                  block
                  size="large"
                  prepend-icon="tabler-check"
                  class="font-weight-bold text-button"
                  elevation="2"
                  @click="save(true)"
                >
                  Approva
                </VBtn>
              </VCol>
              <VCol cols="12">
                <VBtn
                  color="error"
                  block
                  variant="outlined"
                  size="large"
                  prepend-icon="tabler-x"
                  class="font-weight-bold text-button"
                  @click="nega"
                >
                  Negato
                </VBtn>
              </VCol>
            </VRow>
          </VCol>
        </VRow>
      </VCol>
    </VRow>

    <VRow class="mt-4">
      <VCol cols="12">
        <ListOffEmploee :richiesta="route.params.id" />
      </VCol>
    </VRow>

    <VDialog v-model="isDialogVisible" persistent max-width="550">
      <VCard>
        <VCardItem class="bg-error text-white pa-4">
          <VCardTitle class="text-h6 font-weight-bold text-white">
            {{ $t('Label.Vuoi-Inserire-Una-Motivazione') }}
          </VCardTitle>
        </VCardItem>
        <VCardText class="pt-5 pa-6">
          <div class="d-flex align-center gap-2 text-body-2 text-error mb-4 font-weight-medium">
            <VIcon icon="tabler-alert-circle" size="18" />
            <span>Attenzione, il campo Motivazione non è obbligatorio.</span>
          </div>
          <AppTextField
            v-model="nota"
            :label="$t('Label.Motivazione')"
            :placeholder="$t('Label.Motivazione')"
            variant="outlined"
            density="comfortable"
            autofocus
          />
        </VCardText>
        <VDivider />
        <VCardActions class="pa-4">
          <VSpacer />
          <VBtn variant="text" color="secondary" class="font-weight-bold" @click="isDialogVisible = false">
            Esci
          </VBtn>
          <VBtn color="error" variant="flat" class="px-5 font-weight-bold" @click="save(false)">
            Nega
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

  </div>
</template>

<style scoped lang="scss">
.disable-click {
  pointer-events: none;
}

.calendar-wrapper {
  background-color: var(--v-theme-surface);
  border-color: rgba(var(--v-border-color), var(--v-border-opacity)) !important;
  transition: box-shadow 0.2s ease-in-out;

  &:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
  }

  :deep(.mpvue-calendar) {
    width: 100% !important;
    max-width: 100%;
  }
}

.gap-1 { gap: 4px; }
.gap-3 { gap: 12px; }
.gap-4 { gap: 16px; }

.bg-light {
  background-color: rgb(var(--v-theme-on-surface), 0.03);
}

.border-s-primary {
  border-left: 4px solid rgb(var(--v-theme-primary));
}

.border-s-warning {
  border-left: 4px solid rgb(var(--v-theme-warning));
}

.border-b-light {
  border-bottom: 1px solid rgba(var(--v-border-color), 0.06);
}

.min-height-box {
  min-height: 72px;
}
</style>
