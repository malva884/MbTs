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
const richiesta = ref({})
const giorni = ref({})
const log = ref({})
const nota = ref()
const holidays = ref({})

const approvatoriItems = async () => {
  const { data: resultData } = await useApi<any>(createUrl(`/hr/requests/log/${route.params.id}`))

  log.value = resultData.value
}

const loadItems = async () => {
  await approvatoriItems()

  const { data: resultData } = await useApi<any>(createUrl(`/hr/requests/view/${route.params.id}`))

  console.log(resultData.value.CalUno)
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
  if (tipologia === '1')
    return { color: 'text-button  mb-1 text-success', text: 'Ferie' }
  else if (tipologia === '2')
    return { color: 'text-button  mb-1 text-cord', text: '104' }
  else if (tipologia === '101')
    return { color: 'warning', text: 'Annullamento Richiesta' }
  else if (tipologia === '102')
    return { color: 'warning', text: 'Annullamento 104' }
  else if (tipologia === '5')
    return { color: 'white', text: 'Permesso' }
  else
    return { color: 'warning', text: '---' }
}

const resolveStato = (stato: string) => {
  if (stato === '1')
    return { color: 'text-button  mb-1 text-success', text: 'Approvato' }
  else if (stato === '0')
    return { color: 'text-button  mb-1 text-warning', text: 'Bocciato' }
  else
    return { color: 'text-button  mb-1 text-info', text: 'In Approvazione' }
}

const nega = () => {
  isDialogVisible.value = true
}

const save = async (stato: boolean) => {
  const retuenData = await $api(`/hr/requests/save/${route.params.id}`, {
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
  <VRow>
    <VCol cols="12">
      <VCard :title="$t('Label.Info-Richiesta')">
        <VCardText>
          <VRow no-gutters>
            <VCol
              cols="12"
              md="2"
            >
              <span class="text-button">{{ $t('Label.Dipendente') }}:</span>
            </VCol>
            <VCol
              cols="12"
              md="2"
            >
              <p class="text-button mb-1">
                {{ richiesta.dipendente_cognome + ' ' + richiesta.dipendente_nome }}
              </p>
            </VCol>
            <VCol
              cols="12"
              md="2"
            >
              <span class="text-button">{{ $t('Label.Tipologia-Richiesta') }}:</span>
            </VCol>
            <VCol
              cols="12"
              md="2"
            >
              <p :class="resolveTipologia(richiesta.tipologia).color">
                {{ resolveTipologia(richiesta.tipologia).text }}
              </p>
            </VCol>
            <VCol
              cols="12"
              md="2"
            >
              <span class="text-button">{{ $t('Label.Data-Richiesta') }}:</span>
            </VCol>
            <VCol
              cols="12"
              md="2"
            >
              <p class="text-button mb-1">
                {{ richiesta.data_richiesta }}
              </p>
            </VCol>
          </VRow>
          <VRow class="mt-4" no-gutters>
            <VCol
              cols="12"
              md="2"
            >
              <span class="text-button">{{ $t('Label.Matricola') }}:</span>
            </VCol>
            <VCol
              cols="12"
              md="2"
            >
              <p class="text-button mb-1">
                {{ richiesta.dipendente_matricola }}
              </p>
            </VCol>
            <VCol
              cols="12"
              md="2"
            >
              <span class="text-button">{{ $t('Label.Centro-Di-Costo') }}:</span>
            </VCol>
            <VCol
              cols="12"
              md="2"
            >
              <p class="text-button mb-1">
                {{ richiesta.centro_di_costo }}
              </p>
            </VCol>
            <VCol
              cols="12"
              md="2"
            >
              <span class="text-button">{{ $t('Label.Stato') }}:</span>
            </VCol>
            <VCol
              cols="12"
              md="2"
            >
              <p :class="resolveStato(richiesta.stato).color">
                {{ resolveStato(richiesta.stato).text }}
              </p>
            </VCol>
          </VRow>
          <VRow class="mt-4" no-gutters>
            <VCol
              v-if="richiesta.tipologia === '5'"
              cols="12"
              md="2"
            >
              <span class="text-success">{{ $t('Label.Numero-Ore-Permesso') }}:</span>
            </VCol>
            <VCol
              v-if="richiesta.tipologia === '5'"
              cols="12"
              md="2"
            >
              <p class="text-success mb-1">
                {{ giorni[0]?.ore_richieste }}
              </p>
            </VCol>
            <VCol
              v-if="richiesta.tipologia === '5'"
              cols="12"
              md="2"
            >
              <span class="text-success">{{ $t('Label.Ora-Inizio-Fine-Permesso') }}:</span>
            </VCol>
            <VCol
              v-if="richiesta.tipologia === '5'"
              cols="12"
              md="2"
            >
              <p class="text-success mb-1">
                {{ `${giorni[0]?.ora_inizio} / ${giorni[0]?.ora_fine}` }}
              </p>
            </VCol>
            <VCol
              cols="12"
              md="2"
            >
              <span class="text-button">{{ $t('Label.Note') }}:</span>
            </VCol>
            <VCol
              cols="12"
              md="2"
            >
              <p class="text-button mb-1">
                {{ richiesta.note }}
              </p>
            </VCol>
          </VRow>
          <VSpacer/>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
  <VRow>
    <VCol cols="9">
      <VCard :title=" $t('label.Giorni-Richiesti')" v-if="view" >
        <template #append>
          <div class="mt-n2 me-n2 ">
            <div>
              <VAvatar
                size="x-small"
                rounded="0"
                color="info"
              />
              Giorni Richiesti
            </div>
            <div>
              <VAvatar
                rounded="0"
                color="bianco"
                size="x-small"
              >
                <small>----</small>
              </VAvatar>
              Giorini Emergenza
            </div>
          </div>
        </template>
        <VRow>
          <VCol cols="3">
            <div class="container-view-modes disable-click">
              <MCalendar
                :holidays="holidays"
                selectMode="multi"
                class-name="monthRange-mode"
                mode="monthRange"
                :monthRange="meseUno"
                :selectDate="dates"
              />
            </div>
          </VCol>
          <VCol cols="3" >
            <div class="container-view-modes disable-click">
              <MCalendar
                selectMode="multi"
                class-name="monthRange-mode"
                mode="monthRange"
                :monthRange="meseDue"
                :selectDate="dates"
              />
            </div>
          </VCol>
          <VCol cols="3">
            <div class="container-view-modes disable-click">
              <MCalendar
                selectMode="multi"
                class-name="monthRange-mode"
                mode="monthRange"
                :monthRange="meseTre"
                :selectDate="dates"
              />
            </div>
          </VCol>
          <VCol cols="3">
            <div class="container-view-modes disable-click">
              <MCalendar
                selectMode="multi"
                class-name="monthRange-mode"
                mode="monthRange"
                :monthRange="meseQuattro"
                :selectDate="dates"
              />
            </div>
          </VCol>
        </VRow>
        <VRow>
          <VCol cols="3">
            <div class="container-view-modes disable-click">
              <MCalendar
                selectMode="multi"
                class-name="monthRange-mode"
                mode="monthRange"
                :monthRange="meseCinque"
                :selectDate="dates"
              />
            </div>
          </VCol>
          <VCol cols="3">
            <div class="container-view-modes disable-click">
              <MCalendar
                selectMode="multi"
                class-name="monthRange-mode"
                mode="monthRange"
                :monthRange="meseSei"
                :selectDate="dates"
              />
            </div>
          </VCol>
          <VCol cols="3">
            <div class="container-view-modes  disable-click">
              <MCalendar
                :holidays="holidays"
                selectMode="multi"
                class-name="monthRange-mode"
                mode="monthRange"
                :monthRange="meseSette"
                :selectDate="dates"
              />
            </div>
          </VCol>
          <VCol cols="3">
            <div class="container-view-modes disable-click">
              <MCalendar
                selectMode="multi"
                class-name="monthRange-mode"
                mode="monthRange"
                :monthRange="meseOtto"
                :selectDate="dates"
              />
            </div>
          </VCol>
        </VRow>
        <VRow>
          <VCol cols="3">
            <div class="container-view-modes disable-click">
              <MCalendar
                selectMode="multi"
                class-name="monthRange-mode"
                mode="monthRange"
                :monthRange="meseNove"
                :selectDate="dates"
              />
            </div>
          </VCol>
          <VCol cols="3">
            <div class="container-view-modes disable-click">
              <MCalendar
                selectMode="multi"
                class-name="monthRange-mode"
                mode="monthRange"
                :monthRange="meseDieci"
                :selectDate="dates"
              />
            </div>
          </VCol>
          <VCol cols="3">
            <div class="container-view-modes disable-click">
              <MCalendar
                selectMode="multi"
                class-name="monthRange-mode"
                mode="monthRange"
                :monthRange="meseUndici"
                :selectDate="dates"
              />
            </div>
          </VCol>
          <VCol cols="3">
            <div class="container-view-modes disable-click">
              <MCalendar
                selectMode="multi"
                class-name="monthRange-mode"
                mode="monthRange"
                :monthRange="meseDodici"
                :selectDate="dates"
              />
            </div>
          </VCol>
        </VRow>
      </VCard>
    </VCol>
    <VCard :title=" $t('Label.Approvatori')">
      <VCardText v-if="view">
        <VList class="card-list">
          <VListItem
            v-for="user in log"
            :key="user.id"
          >
            <span class="font-weight-medium text-medium-emphasis me-10">{{ user.approvatore }}</span>
            <VChip
              :color="resolveStato(user.stato).color"
              size="small"
            >
              {{ resolveStato(user.stato).text }}
            </VChip>
          </VListItem>
        </VList>
      </VCardText>
    </VCard>
  </VRow>
  <VRow>
    <VCol
      cols="12"
      sm="4"
    >
      <VBtn
        v-if="approvazione"
        color="success"
        block
        @click="save(true)"
      >
        Approva
      </VBtn>
    </VCol>

    <VCol
      v-if="approvazione"
      cols="12"
      sm="6"
    >
      <VBtn
        color="error"
        block
        @click="nega"
      >
        Negato
      </VBtn>
    </VCol>
  </VRow>

  <VDialog
    v-model="isDialogVisible"
    persistent
    max-width="600"
  >
    <!-- Dialog close btn -->
    <DialogCloseBtn @click="isDialogVisible = !isDialogVisible" />

    <!-- Dialog Content -->
    <VCard :title="$t('Label.Vuoi-Inserire-Una-Motivazione')">
      <VCardText>
        <VRow>
          <VCardText class="text-error">
            Attenzione, il campo Motivazione non è obbligatorio.
          </VCardText>
          <VCol
            cols="12"
            sm="12"
            md="12"
          >
            <AppTextField
              v-model="nota"
              :label="$t('Label.Motivazione')"
              :placeholder="$t('Label.Motivazione')"
            />
          </VCol>
        </VRow>
      </VCardText>

      <VCardText class="d-flex justify-end flex-wrap gap-3">
        <VBtn
          color="error"
          @click="save(false)"
        >
          Nega
        </VBtn>
        <VBtn @click="isDialogVisible = false">
          Esci
        </VBtn>
      </VCardText>
    </VCard>
  </VDialog>

  <ListOffEmploee :richiesta="route.params.id" />
</template>

<style scoped lang="scss">
.disable-click {
  pointer-events: none;
}

body, html {
  background-color: #fbf9fe;
  margin: 0;
  padding: 0;
}
.container{
  width: 1000px;
  margin: 0 auto;
  .select-mode{
    .vc-calendar-year{
      margin-right: 10px;
    }
  }
  .container-select-modes{
    display: flex;
    flex-wrap: wrap;
    .select-mode, .multi-mode, .range-mode, .multiRange-mode{
      &.mpvue-calendar{
        width: 400px;
        margin: 0 auto;
        flex: none;
      }
    }
  }
  .container-view-modes{
    display: flex;
    flex-wrap: wrap;
    position: relative;
    .week-mode, .multi-mode, .range-mode, .multiRange-mode, .monthRange-mode{
      &.mpvue-calendar{
        width: 400px;
        margin: 0 auto;
        flex: none;
      }
    }
  }
}

.select-mode{
  &:before{
    content: 'select mode';
    text-align: center;
    display: block;
    color: #38778a;
    font-weight: bold;
    margin-bottom: 2px;
  }
  .vc-calendar-holiday{
    white-space: nowrap;
  }
}

.multi-mode{
  &:before{
    content: 'multi select mode';
    text-align: center;
    display: block;
    color: #38778a;
    font-weight: bold;
    margin-bottom: 2px;
  }
  .content-item-classname{
    color: #fff;
    background: #0b6cbc;
    display: inline-block;
    white-space: nowrap;
    padding: 0 3px;
    border-radius: 3px;
    transform: scale(.8);
  }
}

.range-mode{
  &:before{
    content: 'range select mode';
    text-align: center;
    display: block;
    color: #38778a;
    font-weight: bold;
    margin-bottom: 2px;
  }
}

.multiRange-mode{
  &:before{
    content: 'multi range select mode';
    text-align: center;
    display: block;
    color: #38778a;
    font-weight: bold;
    margin-bottom: 2px;
  }
}

.week-mode{
  &:before{
    content: 'week mode';
    text-align: center;
    display: block;
    color: #38778a;
    font-weight: bold;
    margin-bottom: 2px;
  }
}

.monthRange-mode{
  &:before{
    content: '';
    text-align: center;
    display: block;
    color: #38778a;
    font-weight: bold;
    margin-bottom: 2px;
  }

}

.back-to-today{
  position: absolute;
  left: 50px;
  top: 220px;
  box-shadow: 2px 0px 2px rgb(68, 146, 123, .2);
  height: 22px;
  border: none;
  cursor: pointer;
}

@media screen and (max-width: 600px) {
  .container{
    width: 100%;
  }
}
</style>
