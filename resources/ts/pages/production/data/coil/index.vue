<script setup lang="ts">
import {useI18n} from 'vue-i18n'
import moment from 'moment/moment'

definePage({
  meta: {
    action: 'report',
    subject: 'Produzione-Kpi',
  },
})

const {t} = useI18n()
const items = ref({})
const date = new Date()
const year = date.toLocaleString('default', {year: 'numeric'})
const mese = ref('')
const day = date.toLocaleString('default', {day: '2-digit'})
const month = date.toLocaleString('default', {month: '2-digit'})
const anno = ref('')

const loadData = async () => {
  const {data: result} = await useApi<any>(createUrl('/gp/produzione/bobine/', {
    query: {
      anno: year,
      mese: month,
    },
  }))

  items.value = result.value
}

loadData()
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard>
        <VCardText class="d-flex flex-wrap py-4 gap-4">
          <div class="me-3 d-flex gap-3"/>
          <VSpacer/>

        </VCardText>

        <VDivider/>
      </VCard>
    </VCol>
  </VRow>
  <VRow>
    <VCol cols="3">
      <VCard
        :title="$t('Label.Buffering')"
      >
        <VTable
          height="800"
          fixed-header
          class="text-no-wrap"
        >
          <thead>
          <tr>
            <th class="">
              <h4 class="text-orange">{{$t('Label.Ordine')}}</h4>
            </th>
            <th class="">
              <h4 class="text-orange">{{$t('Label.Materiale')}}</h4>
            </th>
            <th>
              <h4 class="text-orange">GP</h4>
            </th>
            <th>
              <h4 class="text-orange">Sheet</h4>
            </th>
            <th>
              <h4 class="text-rosa">Km</h4>
            </th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="(item, name) in items">
            <td v-if="item.stage === 'BUF'"><h5 class="text-primary">{{ name }}</h5></td>
            <td v-if="item.stage === 'BUF'"><h4 class="text-arancione">{{ item.materiale }}</h4></td>
            <td v-if="item.stage === 'BUF'"><h4 :class="(item.gp == item.sheet ? 'text-success' : 'text-error')">{{ item.gp }}</h4></td>
            <td v-if="item.stage === 'BUF'"><h4 :class="(item.gp == item.sheet ? 'text-success' : 'text-error')">{{ item.sheet }}</h4></td>
            <td v-if="item.stage === 'BUF'"><h4 class="text-orange">{{ item?.km }} Km</h4></td>
          </tr>
          </tbody>
        </VTable>
      </VCard>
    </VCol>
    <VCol cols="3">
      <VCard
        :title="$t('Label.Stranding')"
      >
        <VTable
          height="800"
          fixed-header
          class="text-no-wrap"
        >
          <thead>
          <tr>
            <th class="">
              <h4 class="text-orange">{{$t('Label.Ordine')}}</h4>
            </th>
            <th class="">
              <h4 class="text-orange">{{$t('Label.Materiale')}}</h4>
            </th>
            <th>
              <h4 class="text-orange">GP</h4>
            </th>
            <th>
              <h4 class="text-orange">Sheet</h4>
            </th>
            <th>
              <h4 class="text-rosa">Km</h4>
            </th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="(item, name) in items">
            <td v-if="item.stage === 'STR'"><h5 class="text-primary">{{ name }}</h5></td>
            <td v-if="item.stage === 'STR'"><h4 class="text-arancione">{{ item.materiale }}</h4></td>
            <td v-if="item.stage === 'STR'"><h4 :class="(item.gp == item.sheet ? 'text-success' : 'text-error')">{{ item.gp }}</h4></td>
            <td v-if="item.stage === 'STR'"><h4 :class="(item.gp == item.sheet ? 'text-success' : 'text-error')">{{ item.sheet }}</h4></td>
            <td v-if="item.stage === 'STR'"><h4 class="text-orange">{{ item?.km }} Km</h4></td>
          </tr>
          </tbody>
        </VTable>
      </VCard>
    </VCol>
    <VCol cols="3">
      <VCard
        :title="$t('Label.Jackenting')"
      >
        <VTable
          height="800"
          fixed-header
          class="text-no-wrap"
        >
          <thead>
          <tr>
            <th class="">
              <h4 class="text-orange">{{$t('Label.Ordine')}}</h4>
            </th>
            <th class="">
              <h4 class="text-orange">{{$t('Label.Materiale')}}</h4>
            </th>
            <th>
              <h4 class="text-orange">GP</h4>
            </th>
            <th>
              <h4 class="text-orange">Sheet</h4>
            </th>
            <th>
              <h4 class="text-rosa">Km</h4>
            </th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="(item, name) in items">
            <td v-if="item.stage === 'JAC'"><h5 class="text-primary">{{ name }}</h5></td>
            <td v-if="item.stage === 'JAC'"><h4 class="text-arancione">{{ item.materiale }}</h4></td>
            <td v-if="item.stage === 'JAC'"><h4 :class="(item.gp == item.sheet ? 'text-success' : 'text-error')">{{ item.gp }}</h4></td>
            <td v-if="item.stage === 'JAC'"><h4 :class="(item.gp == item.sheet ? 'text-success' : 'text-error')">{{ item.sheet }}</h4></td>
            <td v-if="item.stage === 'JAC'"><h4 class="text-orange">{{ item?.km }} Km</h4></td>
          </tr>
          </tbody>
        </VTable>
      </VCard>
    </VCol>
    <VCol cols="3">
      <VCard
        :title="$t('Label.Non-Corrisponde')"
      >
        <VTable
          height="800"
          fixed-header
          class="text-no-wrap"
        >
          <thead>
          <tr>
            <th class="">
              <h4 class="text-orange">{{$t('Label.Ordine')}}</h4>
            </th>
            <th class="">
              <h4 class="text-orange">{{$t('Label.Materiale')}}</h4>
            </th>
            <th>
              <h4 class="text-orange">GP</h4>
            </th>
            <th>
              <h4 class="text-orange">Sheet</h4>
            </th>
            <th>
              <h4 class="text-rosa">Km</h4>
            </th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="(item, name) in items">
            <td v-if="item.stage === '-'"><h5 class="text-secondary">{{ name }}</h5></td>
            <td v-if="item.stage === '-'"><h4 class="text-arancione">{{ item.materiale }}</h4></td>
            <td v-if="item.stage === '-'"><h4 class="text-arancione">{{ item.gp }}</h4></td>
            <td v-if="item.stage === '-'"><h4 class="text-orange">{{ item.sheet }}</h4></td>
            <td v-if="item.stage === '-'"><h4 class="text-orange">{{ item?.km }} Km</h4></td>
          </tr>
          </tbody>
        </VTable>
      </VCard>
    </VCol>
  </VRow>
  <VRow>

  </VRow>
</template>

<style scoped lang="scss">

</style>
