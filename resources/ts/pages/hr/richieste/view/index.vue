<script setup lang="ts">
import { useScreens } from 'vue-screen-utils'

definePage({
  meta: {
    action: 'read',
    subject: 'Hr-Richieste',
  },
})

const view = ref(false)
const viewList = ref(false)
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
const attributes = ref()
const dateSelect = ref()
const listaDipendenti = ref()

const { mapCurrent } = useScreens({ xs: '0px', sm: '640px', md: '768px', lg: '1024px' })
const columns = mapCurrent({ lg: 4 }, 1)

const loadItems = async () => {
  const { data: resultData } = await useApi<any>(createUrl('/hr/requests/index'))

  attributes.value = [
    {
      highlight: 'blue',
      dates: resultData.value.data,
    },
  ]
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
  view.value = true
}

loadItems()

const modelConfig = {
  type: 'string',
  mask: 'YYYY-MM-DD', // Uses 'iso' if missing
}

const onSelect = async () => {
  viewList.value = false
  const { data: resultData } = await useApi<any>(createUrl('/hr/requests/get_emploee', {
    query: {
      date: dateSelect.value,
    },
  }))

  listaDipendenti.value = resultData.value
  viewList.value = true
}

const resolveTipologia = (tipologia: string) => {
  if (tipologia === '1')
    return { color: 'primary', text: 'Ferie' }
  else if (tipologia === '2')
    return { color: 'error', text: '104' }
  else if (tipologia === '5')
    return { color: 'warning', text: 'Permesso' }
  else if (tipologia === '6')
    return { color: 'error', text: 'Malattia' }
  else
    return { color: 'warning', text: '---' }
}
</script>

<template>
  <VRow>
    <VCol cols="8">
      <VCard :title=" $t('label.Calendario-Presenze')" v-if="view" >
        <VRow>
          <VCol cols="6">
            <VDatePicker
              v-model="dateSelect"
              :rows="3"
              :columns="columns"
              :attributes="attributes"
              :model-config="modelConfig"
              @click="onSelect"
            />
          </VCol>
        </VRow>
      </VCard>
    </VCol>
    <VCol cols="4">
      <VCard :title=" $t('label.Dipendenti')" v-if="viewList" >
        <VRow>
          <VCol cols="12">
            <VTable
              density="compact"
              fixed-header
              class="text-no-wrap"
              height="800"
            >
              <thead>
              <tr>
                <th>
                  Dipendente
                </th>
                <th>
                  Tipologia
                </th>
                <th>
                  Info
                </th>
              </tr>
              </thead>

              <tbody>
              <tr
                v-for="item in listaDipendenti"
                :key="item.dessert"
              >
                <td>
                  {{ item.dipendente_cognome+' '+item.dipendente_nome}}
                </td>
                <td>
                  <VChip
                    :color="resolveTipologia(item.tipologia).color"
                    size="small"
                  >
                    {{ resolveTipologia(item.tipologia).text }}
                  </VChip>
                </td>
                <td>
                  {{ (item.tipologia === '5' ? item.ora_inizio+'/'+item.ora_fine : '') }}
                </td>
              </tr>
              </tbody>
            </VTable>
          </VCol>
        </VRow>
      </VCard>
    </VCol>
  </VRow>

  <!--ListOffEmploee :richiesta="route.params.id" / -->
</template>

<style scoped lang="scss">
.disable-click {
  pointer-events: none;
}

.vc-calendar-timetable .vc-calendar-timetable-wrap .vc-calendar-body .vc-calendar-row .vc-calendar-day .vc-calendar-almanac.vc-calendar-holiday,
.vc-calendar-timetable .vc-calendar-timetable-wrap .vc-calendar-body .vc-calendar-row .vc-calendar-day .vc-calendar-almanac.vc-calendar-isTerm {
  color: #6082ff!important;
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
