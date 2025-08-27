<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { sortBy } from 'lodash'

interface Props {
  periodoData: string
  meseSelezionato: string
}

const props = defineProps<Props>()
const { t } = useI18n()
const key = ref(1)
const meseSelezionato = ref('')
const items = ref({})

const loadItems = async () => {
  const { data: resultData } = await useApi<any>(createUrl('/production/plant/revenue/', {
    query: {
      periodo: props.periodoData,
    },
  }))

  items.value = sortBy(resultData.value, ['posizione'])
  key.value = key.value + 1
  meseSelezionato.value = new Date(props.meseSelezionato).toLocaleString('en', { month: 'short' })

}

const convertValue = (value: number) => {
  const result = (value * 89.5) / 10

  return result.toFixed(2)
}

loadItems()
watch(props, () => {

  loadItems()
})
</script>

<template>
  <VCard :title="`${$t('Label.Monthly Revenue – AGP Vs Actuals ')} - ${props.periodoData}`">
    <VTable
      density="compact"
      class="text-no-wrap"
    >
      <thead>
        <tr>
          <th />
          <th
            colspan="4"
            class="text-center bg-info"
          >
            <h3 class="text-white">
              OFC
            </h3>
          </th>
          <th
            colspan="3"
            class="text-center bg-primary"
          >
            <h3 class="text-white">
              Cc
            </h3>
          </th>
        </tr>
        <tr>
          <th />
          <th>
            Ckm / Agp
          </th>
          <th>
            Kfkm / Agp
          </th>
          <th>
            Mn €  / Agp
          </th>
          <th>
            INR Cr / Agp
          </th>

          <th>
            Ckm / Agp
          </th>
          <th>
            Mn € / Agp
          </th>
          <th>
            INR Cr / Agp
          </th>
        </tr>
      </thead>

      <tbody>
        <tr
          :key="key"
          v-for="(item, name) in items"
          :class="item.mese === meseSelezionato ? 'bg-critico' : ''"
        >
          {{console.log(item)}}
          <td v-if="item.posizione === 4 || item.posizione === 8 || item.posizione === 12 || item.posizione === 16" class="bg-totali">
            {{ item.mese }}
          </td>
          <td v-else-if="item.posizione === 17 " class="bg-total">
            {{ item.mese }}
          </td>
          <td v-else  :class="item.agp === true ? 'text-pe' : ''">
            {{ item.mese }}
          </td>
          <td  v-if="item.posizione === 4 || item.posizione === 8 || item.posizione === 12 || item.posizione === 16" class="bg-totali">
            {{ `${item.ckm_ofc.valore} / ${item.ckm_ofc.target}` }}
          </td>
          <td  v-else-if="item.posizione === 17 " class="bg-total">
            {{ `${item.ckm_ofc.valore} / ${item.ckm_ofc.target}` }}
          </td>
          <td v-else>
            <span :class="(item.ckm_ofc.valore >= item.ckm_ofc.target ? 'text-success':'text-error')">{{item.ckm_ofc.valore}}</span> / <span>{{item.ckm_ofc.target}}</span>
          </td>
          <td  v-if="item.posizione === 4 || item.posizione === 8 || item.posizione === 12 || item.posizione === 16" class="bg-totali">
            {{ `${item.fkm_ofc.valore} / ${item.fkm_ofc.target}` }}
          </td>
          <td  v-else-if="item.posizione === 17" class="bg-total">
            {{ `${item.fkm_ofc.valore} / ${item.fkm_ofc.target}` }}
          </td>
          <td v-else>
            <span :class=" item.fkm_ofc.valore >= item.fkm_ofc.target ? 'text-success' : 'text-error' ">{{item.fkm_ofc.valore}}</span> / <span>{{item.fkm_ofc.target}}</span>
          </td>
          <td v-if="item.posizione === 4 || item.posizione === 8 || item.posizione === 12 || item.posizione === 16" class="bg-totali">
            {{ `${item.value_ofc.valore} / ${item.value_ofc.target}` }}
          </td>
          <td v-else-if="item.posizione === 17" class="bg-total">
            {{ `${item.value_ofc.valore} / ${item.value_ofc.target}` }}
          </td>
          <td v-else>
            <span :class="(item.value_ofc.valore >= item.value_ofc.target ? 'text-success':'text-error')">{{item.value_ofc.valore}}</span> / <span>{{item.value_ofc.target}}</span>
          </td>
          <td v-if="item.posizione === 4 || item.posizione === 8 || item.posizione === 12 || item.posizione === 16" class="bg-totali">
            {{ `${convertValue(item.inr_ofc.valore)} / ${item.inr_ofc?.target}` }}
          </td>
          <td v-else-if="item.posizione === 17" class="bg-total">
            {{ `${convertValue(item.inr_ofc.valore)} / ${item.inr_ofc?.target}` }}
          </td>
          <td v-else>
            <span :class="(convertValue(item.inr_ofc.valore) >= item.inr_ofc.target ? 'text-success':'text-error')">{{convertValue(item.inr_ofc.valore)}}</span> / <span>{{item.inr_ofc.target}}</span>
          </td>
          <td v-if="item.posizione === 4 || item.posizione === 8 || item.posizione === 12 || item.posizione === 16" class="bg-totali">
            {{ `${item.ckm_cc.valore} / ${item.ckm_cc.target}` }}
          </td>
          <td v-else-if="item.posizione === 17" class="bg-total">
            {{ `${item.ckm_cc.valore} / ${item.ckm_cc.target}` }}
          </td>
          <td v-else>
            <span :class="(item.ckm_cc.valore >= item.ckm_cc.target ? 'text-success':'text-error')">{{item.ckm_cc.valore}}</span> / <span>{{item.ckm_cc.target}}</span>
          </td>
          <td v-if="item.posizione === 4 || item.posizione === 8 || item.posizione === 12 || item.posizione === 16" class="bg-totali">
            {{ `${item.value_cc.valore} / ${item.value_cc.target}` }}
          </td>
          <td v-else-if="item.posizione === 17" class="bg-total">
            {{ `${item.value_cc.valore} / ${item.value_cc.target}` }}
          </td>
          <td v-else>
            <span :class="(item.value_cc.valore >= item.value_cc.target ? 'text-success':'text-error')">{{item.value_cc.valore}}</span> / <span>{{item.value_cc.target}}</span>
          </td>
          <td v-if="item.posizione === 4 || item.posizione === 8 || item.posizione === 12 || item.posizione === 16" class="bg-totali">
            {{ `${convertValue(item.inr_cc.valore)} / ${item.inr_cc.target}` }}
          </td>
          <td v-else-if="item.posizione === 17" class="bg-total">
            {{ `${convertValue(item.inr_cc.valore)} / ${item.inr_cc.target}` }}
          </td>
          <td v-else>
            <span :class="(convertValue(item.inr_cc.valore) >= item.inr_cc.target ? 'text-success':'text-error')">{{convertValue(item.inr_cc.valore)}}</span> / <span>{{item.inr_cc.target}}</span>
          </td>
        </tr>
      </tbody>
    </VTable>
  </VCard>
</template>

<style scoped lang="scss">
.apexcharts-menu-item .exportCSV {
  display: none;
}
</style>
