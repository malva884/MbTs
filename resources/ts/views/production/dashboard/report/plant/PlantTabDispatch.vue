<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { sortBy } from 'lodash'

interface Props {
  periodoData: string
  meseSelezionato: string
}

const props = defineProps<Props>()
const { t } = useI18n()
const key = ref(0)
const meseSelezionato = ref('')
const items = ref({})

const loadItems = async () => {
  const { data: resultData } = await useApi<any>(createUrl('/production/plant/dispatch/', {
    query: {
      periodo: props.periodoData,
    },
  }))

  items.value = sortBy(resultData.value, ['posizione'])
  meseSelezionato.value = new Date(props.meseSelezionato).toLocaleString('en', { month: 'short' })
  key.value = key.value = 1
}

loadItems()
watch(props, () => {
  loadItems()
})
</script>

<template>
  <VCard :title="`${$t('Label.Dispatch Summary for')} - ${props.periodoData}`">
    <VTable
      density="compact"
      class="text-no-wrap"
    >
      <thead>
      <tr>
        <th />
        <th
          colspan="3"
          class="text-center bg-info"
        >
          <h3 class="text-white">
            OFC
          </h3>
        </th>
        <th
          colspan="2"
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
          Ckm
        </th>
        <th>
          Kfkm
        </th>
        <th>
          Mn €
        </th>
        <th>
          Ckm
        </th>
        <th>
          Mn €
        </th>
      </tr>
      </thead>

      <tbody>
      <tr
        :key="key"
        v-for="item in items"
        :class="item.mese === meseSelezionato ? 'bg-critico' : ''"
      >
        <td v-if="item.mese === 'Total'" class="bg-total">{{item.mese}}</td>
        <td v-else>{{item.mese}}</td>
        <td v-if="item.mese === 'Total'" class="bg-total">{{ item.Ckm_ofc}}</td>
        <td v-else>{{ item.Ckm_ofc}}</td>
        <td v-if="item.mese === 'Total'" class="bg-total">{{ item.Fkm_ofc}}</td>
        <td v-else>{{ item.Fkm_ofc}}</td>
        <td v-if="item.mese === 'Total'" class="bg-total">{{ item.Ofc_valore}}</td>
        <td v-else>{{ item.Ofc_valore}}</td>
        <td v-if="item.mese === 'Total'" class="bg-total">{{ item.Cc_ckm}}</td>
        <td v-else>{{ item.Cc_ckm}}</td>
        <td v-if="item.mese === 'Total'" class="bg-total">{{ item.Cc_valore}}</td>
        <td v-else>{{ item.Cc_valore}}</td>
      </tr>
      </tbody>
    </VTable>
  </VCard>
</template>

<style scoped lang="scss">

</style>
