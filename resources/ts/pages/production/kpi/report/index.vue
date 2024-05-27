<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import moment from 'moment/moment'

definePage({
  meta: {
    action: 'report',
    subject: 'Produzione-Kpi',
  },
})

const { t } = useI18n()
const items = ref({})
const date = new Date()
const year = date.toLocaleString('default', { year: 'numeric' })
const mese = ref('')
const day = date.toLocaleString('default', { day: '2-digit' })
const month = date.toLocaleString('default', { month: '2-digit' })
const anno = ref('')
const periodo = ref(`${year}-${month}-${day}`)
const label_periodo = ref('')

anno.value = date.toLocaleString('default', { year: '2-digit' })
mese.value = date.toLocaleString('default', { month: 'long' })

const loadData = async () => {
  const { data: result } = await useApi<any>(createUrl('/production/kpi/report/', {
    query: {
      periodo: periodo.value,
    },
  }))
  items.value = result.value

  if(periodo.value){
    const dateSelect = new Date(periodo.value)
    anno.value = dateSelect.toLocaleString('default', { year: '2-digit' })
    mese.value = dateSelect.toLocaleString('default', { month: 'long' })
  }

  let d = periodo.value.split('-', 3)
  // eslint-disable-next-line camelcase
  label_periodo.value = `${anno}${d[1]}`

  console.log(items.value)
}

loadData()

const euro = new Intl.NumberFormat('it-IT', {
  style: 'currency',
  currency: 'EUR',
})
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard>
        <VCardText class="d-flex flex-wrap py-4 gap-4">
          <div class="me-3 d-flex gap-3" />
          <VSpacer />
          <VCol
            cols="12"
            sm="2"
          >
            <AppDateTimePicker
              v-model="periodo"
              :label="$t('Label.Data')"
              :placeholder="$t('Label.Data')"
              @focusout="loadData"
            />
          </VCol>
        </VCardText>

        <VDivider />
      </VCard>
    </VCol>
  </VRow>
  <VRow>
    <VCol cols="6">
      <VCard
        :title="$t('Label.STL Italy MB CC KPI')"
        :subtitle="`${moment(String(label_periodo[0])).format('YYYY-MM-DD')}`"
      >
        <VTable class="text-no-wrap">
          <thead>
            <tr>
              <th />
              <th
                rowspan="2"
                class="text-center"
              >
                <h3 class="text-arancione">KPI</h3>
              </th>
              <th
                colspan="3"
                class="text-center"
              >
                <h3 class="text-viola">Best Achieved {{anno}}</h3>
              </th>
              <th
                colspan="2"
                class="text-center"
              >
                <h3 class="text-rosa"> {{mese}} {{anno}} Actuals</h3>
              </th>
              <th colspan="2" />
            </tr>
            <tr>
              <th />
              <th class="">
                <h4 class="text-viola">Month</h4>
              </th>
              <th>
                <h4 class="text-viola">CC Sales</h4>
              </th>
              <th>
                <h4 class="text-viola">Achieved</h4>
              </th>
              <th>
                <h4 class="text-rosa">CC Sales</h4>
              </th>
              <th>
                <h4 class="text-rosa">Achieved</h4>
              </th>
              <th>
                <h4 class="text-success">CC Sales</h4>
              </th>
              <th>
                <h4 class="text-success">TARGET</h4>
              </th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><h4 class="text-secondary">VOLUME</h4></td>
              <td><h4 class="text-arancione">PRODUCTION</h4></td>
              <td><h4 class="text-viola">{{items?.cc_ckm_data}}</h4></td>
              <td><h4 class="text-viola">{{items?.cc_ckm_fatturato}} Ckm</h4></td>
              <td><h4 class="text-viola">{{items?.cc_ckm_prodotto}} Ckm</h4></td>
              <td><h4 class="text-rosa">{{items?.cc_ckm_mese_fatturato}} Ckm</h4></td>
              <td><h4 class="text-rosa">{{items?.cc_ckm_mese_prodotto}} Ckm</h4></td>
              <td />
              <td><h4 class="text-success"> </h4></td>
            </tr>
            <tr>
              <td rowspan="5">
                <h4 class="text-secondary">COST</h4>
              </td>
              <td><h4 class="text-arancione">MFG COST</h4></td>
              <td><h4 class="text-viola">May 23</h4></td>
              <td><h4 class="text-viola"> </h4></td>
              <td><h4 class="text-viola"> </h4></td>
              <td><h4 class="text-rosa"> </h4></td>
              <td><h4 class="text-rosa"> </h4></td>
              <td />
              <td><h4 class="text-success">626 kfkm</h4></td>
            </tr>
            <tr>
              <td><h4 class="text-arancione">POWER COST</h4></td>
              <td><h4 class="text-viola">{{items?.cc_power_data}}</h4></td>
              <td><h4 class="text-viola">{{items?.cc_ckm_power}} ckm</h4></td>
              <td><h4 class="text-viola">{{items?.cc_power_cost}} K</h4></td>
              <td><h4 class="text-rosa">{{items?.cc_ckm_power_mese}} ckm</h4></td>
              <td><h4 class="text-rosa">{{items?.cc_power_cost_mese}} ckm</h4></td>
              <td />
              <td><h4 class="text-success"> </h4></td>
            </tr>
            <tr>
              <td>
                <h4 class="text-arancione">BLUE COLLAR COST / Ckm</h4>
              </td>
              <td><h4 class="text-viola">{{items?.cc_costo_personale_data}}</h4></td>
              <td><h4 class="text-viola"></h4></td>
              <td><h4 class="text-viola">{{items?.cc_costo_personale}} €</h4></td>
              <td><h4 class="text-rosa"> </h4></td>
              <td><h4 class="text-rosa"> </h4></td>
              <td />
              <td><h4 class="text-success"> </h4></td>
            </tr>
            <tr>
              <td><h4 class="text-arancione">PRODN / PERSON</h4></td>
              <td><h4 class="text-viola">May 23</h4></td>
              <td><h4 class="text-viola"> </h4></td>
              <td><h4 class="text-viola"> </h4></td>
              <td><h4 class="text-rosa"> </h4></td>
              <td><h4 class="text-rosa"> </h4></td>
              <td />
              <td><h4 class="text-success"> </h4></td>
            </tr>
            <tr>
              <td><h4 class="text-arancione">PLANT FIXED COST / Ckm</h4></td>
              <td><h4 class="text-viola">May 23</h4></td>
              <td><h4 class="text-viola"> </h4></td>
              <td><h4 class="text-viola"> </h4></td>
              <td><h4 class="text-rosa"> </h4></td>
              <td><h4 class="text-rosa"> </h4></td>
              <td />
              <td><h4 class="text-success"> </h4> </td>
            </tr>
            <tr>
              <td><h4 class="text-secondary">PERFORMANCE</h4></td>
              <td><h4 class="text-arancione">EBITDA / Ckm</h4></td>
              <td><h4 class="text-viola">May 23</h4></td>
              <td><h4 class="text-viola"> </h4></td>
              <td><h4 class="text-viola"> </h4></td>
              <td><h4 class="text-rosa"> </h4></td>
              <td><h4 class="text-rosa"> </h4></td>
              <td />
              <td><h4 class="text-success"> </h4></td>
            </tr>
          </tbody>
        </VTable>
      </VCard>
    </VCol>
    <VCol cols="6">
      <VCard
        :title="$t('Label.STL Italy MB OFC KPI')"
        :subtitle="`${moment(String(label_periodo[0])).format('YYYY-MM-DD')} / ${moment(String(label_periodo[1])).format('YYYY-MM-DD')}`"
      >
        <VTable class="text-no-wrap">
          <thead>
          <tr>
            <th />
            <th
              rowspan="2"
              class="text-center"
            >
              <h3 class="text-arancione">KPI</h3>
            </th>
            <th
              colspan="3"
              class="text-center"
            >
              <h3 class="text-viola">Best Achieved {{anno}}</h3>
            </th>
            <th
              colspan="2"
              class="text-center"
            >
              <h3 class="text-rosa"> {{mese}} {{anno}} Actuals</h3>
            </th>
            <th colspan="2" />
          </tr>
          <tr>
            <th />
            <th class="">
              <h4 class="text-viola">Month</h4>
            </th>
            <th>
              <h4 class="text-viola">OFC Sales</h4>
            </th>
            <th>
              <h4 class="text-viola">Achieved</h4>
            </th>
            <th>
              <h4 class="text-rosa">OFC Sales</h4>
            </th>
            <th>
              <h4 class="text-rosa">Achieved</h4>
            </th>
            <th>
              <h4 class="text-success">OFC Sales</h4>
            </th>
            <th>
              <h4 class="text-success">TARGET</h4>
            </th>
          </tr>
          </thead>
          <tbody>
          <tr>
            <td><h4 class="text-secondary">VOLUME</h4></td>
            <td><h4 class="text-arancione">PRODUCTION</h4></td>
            <td><h4 class="text-viola">{{items?.ofc_kfkm_data}}</h4></td>
            <td><h4 class="text-viola">{{items?.ofc_kfkm_fatturato}} kfkm</h4></td>
            <td><h4 class="text-viola">{{items?.ofc_kfkm_prodotto}} kfkm</h4></td>
            <td><h4 class="text-rosa">{{items?.ofc_kfkm_mese_fatturato}} kfkm</h4></td>
            <td><h4 class="text-rosa">{{items?.ofc_kfkm_mese_prodotto}} kfkm</h4></td>
            <td />
            <td><h4 class="text-success"> </h4></td>
          </tr>
          <tr>
            <td rowspan="3">
              <h4 class="text-secondary">EFFICIENCY</h4>
            </td>
            <td><h4 class="text-arancione">OEE</h4></td>
            <td><h4 class="text-viola">May 23</h4></td>
            <td><h4 class="text-viola"> </h4></td>
            <td><h4 class="text-viola"> </h4></td>
            <td><h4 class="text-rosa"> </h4></td>
            <td><h4 class="text-rosa"> </h4></td>
            <td />
            <td><h4 class="text-success">626 kfkm</h4></td>
          </tr>
          <tr>
            <td><h4 class="text-arancione">FRT</h4></td>
            <td><h4 class="text-viola">May 23</h4></td>
            <td><h4 class="text-viola"> </h4></td>
            <td><h4 class="text-viola"> </h4></td>
            <td><h4 class="text-rosa"> </h4></td>
            <td><h4 class="text-rosa"> </h4></td>
            <td />
            <td><h4 class="text-success"> </h4></td>
          </tr>
          <tr>
            <td>
              <h4 class="text-arancione">Scrap</h4>
            </td>
            <td><h4 class="text-viola">May 23</h4></td>
            <td><h4 class="text-viola"> </h4></td>
            <td><h4 class="text-viola"> </h4></td>
            <td><h4 class="text-rosa"></h4></td>
            <td><h4 class="text-rosa"> </h4></td>
            <td />
            <td><h4 class="text-success"> </h4></td>
          </tr>
          <tr>
            <td rowspan="5">
              <h4 class="text-secondary">COST</h4>
            </td>
            <td><h4 class="text-arancione">MFG COST</h4></td>
            <td><h4 class="text-viola">May 23</h4></td>
            <td><h4 class="text-viola"> </h4></td>
            <td><h4 class="text-viola"> </h4></td>
            <td><h4 class="text-rosa"></h4></td>
            <td><h4 class="text-rosa"> </h4></td>
            <td />
            <td><h4 class="text-success"> </h4></td>
          </tr>
          <tr>
            <td><h4 class="text-arancione">POWER COST</h4></td>
            <td><h4 class="text-viola">{{items?.ofc_power_data}}</h4></td>
            <td><h4 class="text-viola">{{items?.ofc_kfkm_power}} Kfkm</h4></td>
            <td><h4 class="text-viola">{{items?.ofc_power_cost}} K</h4></td>
            <td><h4 class="text-rosa">{{items?.ofc_kfkm_power_mese}} Kfkm</h4></td>
            <td><h4 class="text-rosa"></h4></td>
            <td />
            <td><h4 class="text-success"> </h4></td>
          </tr>
          <tr>
            <td>
              <h4 class="text-arancione">BLUE COLLAR COST / Ckm</h4>
            </td>
            <td><h4 class="text-viola">{{items?.ofc_costo_personale_data}}</h4></td>
            <td><h4 class="text-viola"></h4></td>
            <td><h4 class="text-viola">{{items?.ofc_costo_personale}} €</h4></td>
            <td><h4 class="text-rosa"> </h4></td>
            <td><h4 class="text-rosa"> </h4></td>
            <td />
            <td><h4 class="text-success"> </h4></td>
          </tr>
          <tr>
            <td><h4 class="text-arancione">PRODN / PERSON</h4></td>
            <td><h4 class="text-viola">May 23</h4></td>
            <td><h4 class="text-viola"> </h4></td>
            <td><h4 class="text-viola"> </h4></td>
            <td><h4 class="text-rosa"> </h4></td>
            <td><h4 class="text-rosa"> </h4></td>
            <td />
            <td><h4 class="text-success">626 kfkm</h4></td>
          </tr>
          <tr>
            <td><h4 class="text-arancione">PLANT FIXED COST / Ckm</h4></td>
            <td><h4 class="text-viola">May 23</h4></td>
            <td><h4 class="text-viola"> </h4></td>
            <td><h4 class="text-viola"> </h4></td>
            <td><h4 class="text-rosa"> </h4></td>
            <td><h4 class="text-rosa"> </h4></td>
            <td />
            <td><h4 class="text-success"> </h4> </td>
          </tr>
          <tr>
            <td><h4 class="text-secondary">PERFORMANCE</h4></td>
            <td><h4 class="text-arancione">EBITDA / Ckm</h4></td>
            <td><h4 class="text-viola">May 23</h4></td>
            <td><h4 class="text-viola"> </h4></td>
            <td><h4 class="text-viola"> </h4></td>
            <td><h4 class="text-rosa"> </h4></td>
            <td><h4 class="text-rosa"> </h4></td>
            <td />
            <td><h4 class="text-success"> </h4></td>
          </tr>
          </tbody>
        </VTable>
      </VCard>
    </VCol>
  </VRow>
  <VRow>
    <VCol cols="6">
      <VCard
        :title="$t('Label.STL Italy MB CC & OFC INVENTORY')"
        :subtitle="`${moment(String(label_periodo[0])).format('YYYY-MM-DD')} / ${moment(String(label_periodo[1])).format('YYYY-MM-DD')}`"
      >
        <VTable class="text-no-wrap">
          <tbody>
            <tr>
              <td rowspan="5">
                INVENTORY
              </td>
              <td>WIP for CC</td>
              <td>May 23</td>
              <td>594 kfkm</td>
              <td>647 kfkm</td>
              <td>405 kfkm</td>
              <td>405 kfkm</td>
              <td />
              <td>626 kfkm </td>
            </tr>
            <tr>
              <td>WIP for OFC</td>
              <td>May 23</td>
              <td>594 kfkm</td>
              <td>647 kfkm</td>
              <td>405 kfkm</td>
              <td>405 kfkm</td>
              <td />
              <td>626 kfkm </td>
            </tr>
            <tr>
              <td>Total Wip</td>
              <td>May 23</td>
              <td>594 kfkm</td>
              <td>647 kfkm</td>
              <td>405 kfkm</td>
              <td>405 kfkm</td>
              <td />
              <td>626 kfkm </td>
            </tr>
            <tr>
              <td>RM</td>
              <td>May 23</td>
              <td>594 kfkm</td>
              <td>647 kfkm</td>
              <td>405 kfkm</td>
              <td>405 kfkm</td>
              <td />
              <td>626 kfkm </td>
            </tr>
            <tr>
              <td>Overall Inventory</td>
              <td>May 23</td>
              <td>594 kfkm</td>
              <td>647 kfkm</td>
              <td>405 kfkm</td>
              <td>405 kfkm</td>
              <td />
              <td>626 kfkm </td>
            </tr>
          </tbody>
        </VTable>
      </VCard>
    </VCol>
  </VRow>
</template>

<style scoped lang="scss">

</style>
