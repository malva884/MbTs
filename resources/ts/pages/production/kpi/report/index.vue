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
              <h3 class="text-orange">Best Achieved {{anno}}</h3>
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
              <h4 class="text-orange">Month</h4>
            </th>
            <th>
              <h4 class="text-orange">CC Sales</h4>
            </th>
            <th>
              <h4 class="text-orange">Achieved</h4>
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
            <td><h5 class="text-secondary">VOLUME</h5></td>
            <td><h4 class="text-arancione">PRODUCTION</h4></td>
            <td><h4 class="text-orange">{{items?.cc_ckm_data}}</h4></td>
            <td><h4 class="text-orange">{{items?.cc_ckm_fatturato}} Ckm</h4></td>
            <td><h4 class="text-orange">{{items?.cc_ckm_prodotto}} Ckm</h4></td>
            <td><h4 class="text-rosa">{{items?.cc_ckm_mese_fatturato}} Ckm</h4></td>
            <td><h4 class="text-rosa">{{items?.cc_ckm_mese_prodotto}} Ckm</h4></td>
            <td />
            <td><h4 class="text-success"> </h4></td>
          </tr>
          <tr>
            <td rowspan="5">
              <h5 class="text-secondary">COST</h5>
            </td>
            <td><h4 class="text-arancione">MFG COST</h4></td>
            <td><h4 class="text-orange"></h4></td>
            <td><h4 class="text-orange"> </h4></td>
            <td><h4 class="text-orange"> </h4></td>
            <td><h4 class="text-rosa"> </h4></td>
            <td><h4 class="text-rosa"> </h4></td>
            <td />
            <td><h4 class="text-success"></h4></td>
          </tr>
          <tr>
            <td><h4 class="text-arancione">POWER + GAS COST</h4></td>
            <td><h4 class="text-orange">{{items?.cc_power_data}}</h4></td>
            <td><h4 class="text-orange">{{items?.cc_ckm_power}} ckm</h4></td>
            <td><h4 class="text-orange">{{items?.cc_power_cost}} K</h4></td>
            <td><h4 class="text-rosa">{{items?.cc_ckm_power_mese}} ckm</h4></td>
            <td><h4 class="text-rosa">{{items?.cc_power_cost_mese}} K</h4></td>
            <td />
            <td><h4 class="text-success"> </h4></td>
          </tr>
          <tr>
            <td>
              <h4 class="text-arancione">BLUE COLLAR COST / Ckm</h4>
            </td>
            <td><h4 class="text-orange">{{items?.cc_costo_personale_data}}</h4></td>
            <td><h4 class="text-orange">{{items?.cc_fatturato_ckm_employee_cost_month}} Ckm</h4></td>
            <td><h4 class="text-orange">{{items?.cc_costo_personale}} €</h4></td>
            <td><h4 class="text-rosa">{{items?.cc_ckm_power_mese}} ckm</h4></td>
            <td><h4 class="text-rosa">{{items?.cc_ckm_employee_cost_month}} €</h4></td>
            <td />
            <td><h4 class="text-success"> </h4></td>
          </tr>
          <tr>
            <td><h4 class="text-arancione">PRODN / PERSON</h4></td>
            <td><h4 class="text-orange"></h4></td>
            <td><h4 class="text-orange"> </h4></td>
            <td><h4 class="text-orange"> </h4></td>
            <td><h4 class="text-rosa"> </h4></td>
            <td><h4 class="text-rosa"> </h4></td>
            <td />
            <td><h4 class="text-success"> </h4></td>
          </tr>
          <tr>
            <td><h4 class="text-arancione">PLANT FIXED COST / Ckm</h4></td>
            <td><h4 class="text-orange"></h4></td>
            <td><h4 class="text-orange"> </h4></td>
            <td><h4 class="text-orange"> </h4></td>
            <td><h4 class="text-rosa"> </h4></td>
            <td><h4 class="text-rosa"> </h4></td>
            <td />
            <td><h4 class="text-success"> </h4> </td>
          </tr>
          <tr>
            <td><h5 class="text-secondary">PERFORMANCE</h5></td>
            <td><h4 class="text-arancione">EBITDA / Ckm</h4></td>
            <td><h4 class="text-orange"></h4></td>
            <td><h4 class="text-orange"> </h4></td>
            <td><h4 class="text-orange"> </h4></td>
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
              <h3 class="text-orange">Best Achieved {{anno}}</h3>
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
              <h4 class="text-orange">Month</h4>
            </th>
            <th>
              <h4 class="text-orange">OFC Sales</h4>
            </th>
            <th>
              <h4 class="text-orange">Achieved</h4>
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
            <td><h5 class="text-secondary">VOLUME</h5></td>
            <td><h4 class="text-arancione">PRODUCTION</h4></td>
            <td><h4 class="text-orange">{{items?.ofc_kfkm_data}}</h4></td>
            <td><h4 class="text-orange">{{items?.ofc_kfkm_fatturato}} kfkm</h4></td>
            <td><h4 class="text-orange">{{items?.ofc_kfkm_prodotto}} kfkm</h4></td>
            <td><h4 class="text-rosa">{{items?.ofc_kfkm_mese_fatturato}} kfkm</h4></td>
            <td><h4 class="text-rosa">{{items?.ofc_kfkm_mese_prodotto}} kfkm</h4></td>
            <td />
            <td><h4 class="text-success"> </h4></td>
          </tr>
          <tr>
            <td rowspan="3">
              <h5 class="text-secondary">EFFICIENCY</h5>
            </td>
            <td><h4 class="text-arancione">OEE</h4></td>
            <td><h4 class="text-orange">{{items?.ofc_oee_top_periodo}}</h4></td>
            <td><h4 class="text-orange">{{items?.ofc_oee_kfkm_top}} kfkm</h4></td>
            <td><h4 class="text-orange">{{items?.ofc_oee_top}} %</h4></td>
            <td><h4 class="text-rosa">{{items?.ofc_ftr_kfkm_month}} kfkm</h4></td>
            <td><h4 class="text-rosa">{{items?.ofc_oee_month}} %</h4></td>
            <td />
            <td><h4 class="text-success"></h4></td>
          </tr>
          <tr>
            <td><h4 class="text-arancione">FTR</h4></td>
            <td><h4 class="text-orange">{{items?.ofc_ftr_top_data}}</h4></td>
            <td><h4 class="text-orange">{{items?.ofc_ftr_kfkm_top}} kfkm</h4></td>
            <td><h4 class="text-orange">{{items?.ofc_ftr_top}} %</h4></td>
            <td><h4 class="text-rosa">{{items?.ofc_ftr_kfkm_month}} kfkm</h4></td>
            <td><h4 class="text-rosa">{{items?.ofc_ftr_month}} %</h4></td>
            <td />
            <td><h4 class="text-success"> </h4></td>
          </tr>
          <tr>
            <td>
              <h4 class="text-arancione">Scrap</h4>
            </td>
            <td><h4 class="text-orange">{{items?.ofc_scarp_top_data}}</h4></td>
            <td><h4 class="text-orange">{{items?.ofc_scarp_kfkm_top}} kfkm</h4></td>
            <td><h4 class="text-orange">{{items?.ofc_scarp_top}} %</h4></td>
            <td><h4 class="text-rosa">{{items?.ofc_scarp_kfkm_month}} kfkm</h4></td>
            <td><h4 class="text-rosa">{{items?.ofc_scarp_month}} %</h4></td>
            <td />
            <td><h4 class="text-success"> </h4></td>
          </tr>
          <tr>
            <td rowspan="5">
              <h5 class="text-secondary">COST</h5>
            </td>
            <td><h4 class="text-arancione">MFG COST</h4></td>
            <td><h4 class="text-orange"></h4></td>
            <td><h4 class="text-orange"> </h4></td>
            <td><h4 class="text-orange"> </h4></td>
            <td><h4 class="text-rosa"></h4></td>
            <td><h4 class="text-rosa"> </h4></td>
            <td />
            <td><h4 class="text-success"> </h4></td>
          </tr>
          <tr>
            <td><h4 class="text-arancione">POWER + GAS COST</h4></td>
            <td><h4 class="text-orange">{{items?.ofc_power_data}}</h4></td>
            <td><h4 class="text-orange">{{items?.ofc_kfkm_power}} Kfkm</h4></td>
            <td><h4 class="text-orange">{{items?.ofc_power_cost}} K</h4></td>
            <td><h4 class="text-rosa">{{items?.ofc_kfkm_power_mese}} Kfkm</h4></td>
            <td><h4 class="text-rosa">{{items?.ofc_power_cost_mese}} K</h4></td>
            <td />
            <td><h4 class="text-success"> </h4></td>
          </tr>
          <tr>
            <td>
              <h4 class="text-arancione">BLUE COLLAR COST / Ckm</h4>
            </td>
            <td><h4 class="text-orange">{{items?.ofc_costo_personale_data}}</h4></td>
            <td><h4 class="text-orange">{{items?.ofc_fatturato_ckm_employee_cost_top}} Kfkm</h4></td>
            <td><h4 class="text-orange">{{items?.ofc_costo_personale}} €</h4></td>
            <td><h4 class="text-rosa">{{items?.ofc_kfkm_power_mese}} Kfkm</h4></td>
            <td><h4 class="text-rosa">{{items?.ofc_ckm_employee_cost_month}} €</h4></td>
            <td />
            <td><h4 class="text-success"> </h4></td>
          </tr>
          <tr>
            <td><h4 class="text-arancione">PRODN / PERSON</h4></td>
            <td><h4 class="text-orange"></h4></td>
            <td><h4 class="text-orange"> </h4></td>
            <td><h4 class="text-orange"> </h4></td>
            <td><h4 class="text-rosa"> </h4></td>
            <td><h4 class="text-rosa"> </h4></td>
            <td />
            <td><h4 class="text-success"></h4></td>
          </tr>
          <tr>
            <td><h4 class="text-arancione">PLANT FIXED COST / Ckm</h4></td>
            <td><h4 class="text-orange"></h4></td>
            <td><h4 class="text-orange"> </h4></td>
            <td><h4 class="text-orange"> </h4></td>
            <td><h4 class="text-rosa"> </h4></td>
            <td><h4 class="text-rosa"> </h4></td>
            <td />
            <td><h4 class="text-success"> </h4> </td>
          </tr>
          <tr>
            <td><h5 class="text-secondary">PERFORMANCE</h5></td>
            <td><h4 class="text-arancione">EBITDA / Ckm</h4></td>
            <td><h4 class="text-orange"></h4></td>
            <td><h4 class="text-orange"> </h4></td>
            <td><h4 class="text-orange"> </h4></td>
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

      >
        <VTable class="text-no-wrap">
          <tbody>
          <tr>
            <td rowspan="6">
              INVENTORY
            </td>

            <td><h4 class="text-arancione">KPI</h4></td>
            <td><h4 class="text-orange">MONTH</h4></td>
            <td><h3 class="text-orange">Best Achieved {{anno}}</h3></td>
            <td><h3 class="text-rosa">Achieved {{anno}} {{mese}} </h3></td>
          </tr>
          <tr>
            <td><h4 class="text-arancione">WIP for CC</h4></td>
            <td><h4 class="text-orange">{{items?.cc_wip_data}}</h4></td>
            <td><h4 class="text-orange">{{items?.cc_wip}} €</h4></td>
            <td><h4 class="text-rosa"> {{items?.cc_wip_mese}} €</h4></td>
          </tr>
          <tr>
            <td><h4 class="text-arancione">WIP for OFC</h4></td>
            <td><h4 class="text-orange">{{items?.ofc_wip_data}}</h4></td>
            <td><h4 class="text-orange">{{items?.ofc_wip}} €</h4></td>
            <td><h4 class="text-rosa"> {{items?.ofc_wip_mese}} €</h4></td>
          </tr>
          <tr>
            <td><h4 class="text-arancione">Total Wip</h4></td>
            <td><h4 class="text-orange">{{items?.total_wip_data}}</h4></td>
            <td><h4 class="text-orange">{{items?.total_wip}} €</h4></td>
            <td><h4 class="text-rosa"> {{items?.total_wip_mese}} €</h4></td>
          </tr>
          <tr>
            <td><h4 class="text-arancione">RM</h4></td>
            <td><h4 class="text-orange">{{items?.total_rm_data}}</h4></td>
            <td><h4 class="text-orange">{{items?.total_rm}} €</h4></td>
            <td><h4 class="text-rosa"> {{items?.total_rm_mese}} €</h4></td>
          </tr>
          <tr>
            <td><h4 class="text-arancione">Overall Inventory</h4></td>
            <td><h4 class="text-orange">{{items?.total_warehouse_date}}</h4></td>
            <td><h4 class="text-orange">{{items?.total_warehouse}} €</h4></td>
            <td><h4 class="text-rosa">{{items?.total_warehous_mese}} €</h4></td>
          </tr>
          </tbody>
        </VTable>
      </VCard>
    </VCol>
  </VRow>
</template>

<style scoped lang="scss">

</style>
