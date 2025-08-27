<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import moment from "moment/moment";


definePage({
  meta: {
    action: 'report',
    subject: 'Produzione-Performance',
  },
})

const { t } = useI18n()
const items = ref({})
const date = new Date()
const year = date.toLocaleString('default', { year: 'numeric' })
const month = date.toLocaleString('default', { month: '2-digit' })
const day = date.toLocaleString('default', { day: '2-digit' })

const periodo = ref(`${year}-${month}-` + `01 to ${year}-${month}-${day}`)
const label_periodo = ref('')

// const years = [{text:now.getFullYear(),value:now.getFullYear()},{text:now.getFullYear()-1,value:now.getFullYear()-1},{text:now.getFullYear()-2,value:now.getFullYear()-2}]
const loadData = async () => {
  const { data: result } = await useApi<any>(createUrl('/production/performance/report/', {
    query: {
      periodo: periodo.value,
    },
  }))

  items.value = result.value

  label_periodo.value = periodo.value.split(' to ', 2)
  console.log(label_periodo.value)
}

loadData()

const report_rame = [
  {
    label: t('Label.Produzione-Ckm'),
    key: 'production_ckm_',
    um: '',
    class: 'text-error',
  },
  {
    label: t('Label.Spedito-Ckm'),
    key: 'dispatch_ckm_',
    um: '',
    class: 'text-warning',
  },
  {
    label: t('Label.Fatturato-Ckm'),
    key: 'sales_ckm_',
    um: '',
    class: 'text-info',
  },
  {
    label: t('Label.Fatturato-Totale'),
    key: 'sales_value_',
    um: ' € ',
    class: 'text-success',
  },

]

const report_ottico = [
  {
    label: t('Label.Produzione-Kfkm'),
    key: 'production_kfkm_',
    um: '',
    class: 'text-error',
  },
  {
    label: t('Label.Produzione-Ckm'),
    key: 'production_ckm_',
    um: '',
    class: 'text-error',
  },
  {
    label: t('Label.Spedito-Kfkm'),
    key: 'dispatch_kfkm_',
    um: '',
    class: 'text-warning',
  },
  {
    label: t('Label.Spedito-Ckm'),
    key: 'dispatch_ckm_',
    um: '',
    class: 'text-warning',
  },
  {
    label: t('Label.Fatturato-Kfkm'),
    key: 'sales_kfkm_',
    um: '',
    class: 'text-info',
  },
  {
    label: t('Label.Fatturato-Ckm'),
    key: 'sales_ckm_',
    um: '',
    class: 'text-info',
  },
  {
    label: t('Label.Fatturato-Totale'),
    key: 'sales_value_',
    um: ' € ',
    class: 'text-success',
  },

]

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
              :label="$t('Label.Periodo')"
              placeholder="Select date"
              :config="{ mode: 'range' }"
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
        :title="$t('Label.Ottico')"
        :subtitle="`${moment(String(label_periodo[0])).format('YYYY-MM-DD') } / ${  moment(String(label_periodo[1])).format('YYYY-MM-DD')}`"
      >
        <VTable class="text-no-wrap">
          <thead>
            <tr>
              <th />
              <th>
                -
              </th>
              <th class="text-center">
                Values
              </th>
              <th class="text-center">
                AGP
              </th>
              <th class="text-center">
                %
              </th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="row in report_ottico"
              :key="row.label"
            >
              <td :class="row.class">
                {{ row.label }}
              </td>
              <td />
              <td
                v-if="row.um === ' € '"
                :class="`${row.class} text-end`"
              >
                {{ euro.format(items[`${row.key}ottico_totale`]) }}
              </td>
              <td
                v-else
                :class="`${row.class} text-end`"
              >
                {{ items[`${row.key}ottico_totale`] }}
              </td>
              <td :class="`${row.class} text-end`">
                {{ items[`${row.key}ottico_agv`] }}
              </td>
              <td
                v-if="items[`${row.key}ottico_agv_perc`] !== undefined"
                :class="`${row.class} text-end`"
              >
                {{ `${items[`${row.key}ottico_agv_perc`]} %` }}
              </td>
            </tr>
          </tbody>
        </VTable>
      </VCard>
    </VCol>
    <VCol cols="6">
      <VCard :title="$t('Label.Rame')" :subtitle="moment(String(label_periodo[0])).format('YYYY-MM-DD') +' / ' + moment(String(label_periodo[1])).format('YYYY-MM-DD')">
        <VTable class="text-no-wrap">
          <thead>
            <tr>
              <th />
              <th>
                -
              </th>
              <th class="text-center">
                Values
              </th>
              <th class="text-center">
                AGP
              </th>
              <th class="text-center">
                %
              </th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="row in report_rame"
              :key="row.label"
            >
              <td :class="row.class">
                {{ row.label }}
              </td>
              <td />
              <td
                v-if="row.um === ' € '"
                :class="`${row.class} text-end`"
              >
                {{ euro.format(items[`${row.key}rame_totale`]) }}
              </td>
              <td
                v-else
                :class="`${row.class} text-end`"
              >
                {{ items[`${row.key}rame_totale`] + row.um }}
              </td>
              <td :class="`${row.class} text-end`">
                {{ items[`${row.key}rame_agv`] }}
              </td>
              <td
                v-if="items[`${row.key}rame_agv_perc`] !== undefined"
                :class="`${row.class} text-end`"
              >
                {{ `${items[`${row.key}rame_agv_perc`]} %` }}
              </td>
            </tr>
          </tbody>
        </VTable>
      </VCard>
    </VCol>
  </VRow>
</template>

<style scoped lang="scss">

</style>
