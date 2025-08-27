<script setup lang="ts">
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const loadingPage = ref(false)
const serverItems = ref<any>([])
const panel = ref(0)
let totaliCategorie = { totale: 0, ckm: 0, fkm: 0 }
let totaliMaterie = { totale: 0, ckm: 0, fkm: 0 }
let totaliInCorso = { totale: 0, ckm: 0, fkm: 0 }
let totaleProdottiFiniti = { totale: 0, ckm: 0, fkm: 0 }



interface Props {
  materialeFilter: string
  classeFilter: string
}

const props = defineProps<Props>()

const route = useRoute('production-warehouse-view-id')


const loadItems = async () => {
  loadingPage.value = true

  const {data: resultData} = await useApi<any>(createUrl(`/pr/magazzino/get_magazzono/${route.params.id}`, {
    query: {
      materiale: props.materialeFilter,
      classe: props.classeFilter,
    },
  }))


  serverItems.value = resultData.value.objs
  panel.value = null
  loadingPage.value = false
}

loadItems()

const value = (tabella: string, colonna: string, valore: number, label = null) => {
  // eslint-disable-next-line sonarjs/no-collapsible-if
  if (tabella === 'categorie') {
    if (label !== 'Magazzino')
      totaliCategorie[colonna] = Number.parseFloat( totaliCategorie[colonna] ) + Number.parseFloat(valore)
  }

  if (tabella === 'materie')
    totaliMaterie[colonna] = Number.parseFloat( totaliMaterie[colonna] ) + Number.parseFloat(valore)

  if (tabella === 'in_corso')
    totaliInCorso[colonna] = Number.parseFloat( totaliInCorso[colonna] ) + Number.parseFloat(valore)

  if (tabella === 'finiti')
    totaleProdottiFiniti[colonna] = Number.parseFloat( totaleProdottiFiniti[colonna] ) + Number.parseFloat(valore)

  return valore
}

const euro = new Intl.NumberFormat('it-IT', {
  style: 'currency',
  currency: 'EUR',
})

watch(props, () => {
   totaliCategorie = { totale: 0, ckm: 0, fkm: 0 }
   totaliMaterie = { totale: 0, ckm: 0, fkm: 0 }
   totaliInCorso = { totale: 0, ckm: 0, fkm: 0 }
   totaleProdottiFiniti = { totale: 0, ckm: 0, fkm: 0 }
  loadItems()
})
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VCard :title="$t('Label.Riepilogo-Magazino')">
        <VCardText>
          <VRow>
            <VCol cols="6">
              <VCard
                :title="$t('Label.Categorie')"
                class="mb-10"
              >
                <VTable
                  theme=""
                  class="text-no-wrap rounded-0"
                >
                  <thead>
                  <tr style="background-color: #0f7609">
                    <th>
                      <span class="text-white">{{ $t('Label.Categorie') }}</span>
                    </th>
                    <th>
                      <span class="text-white">{{ $t('Label.Quantita') }}</span>
                    </th>
                    <th>
                      <span class="text-white">{{ $t('Label.Fkm') }}</span>
                    </th>
                    <th>
                      <span class="text-white">{{ $t('Label.Totale') }}</span>
                    </th>
                  </tr>
                  </thead>

                  <tbody>
                  <tr
                    v-for="(item, index) in serverItems"
                    :key="index"
                  >
                    <td>
                      {{ index }}
                    </td>
                    <td>
                      {{ value('categorie', 'ckm', item.ckm) }}
                    </td>
                    <td>
                      {{ value('categorie', 'fkm', item.fkm) }}
                    </td>
                    <td v-if="index === 'Magazzino'" class="text-white" style="background-color: #1da302">
                      {{ euro.format(value('categorie', 'totale', item.valore, index)) }}
                    </td>
                    <td v-else-if="index === 'Corso Lavori'" class="text-white" style="background-color: #1da302">
                      {{ euro.format(value('categorie', 'totale', item.valore, index)) }}
                    </td>
                    <td v-else>
                      {{ euro.format(value('categorie', 'totale', item.valore, index)) }}
                    </td>
                  </tr>
                  <tr style="background-color: #c14d1e">
                    <td class="text-white">
                      {{ $t('Label.Totale')}}
                    </td>
                    <td class="text-white">
                      {{ totaliCategorie['ckm'] }}
                    </td >
                    <td class="text-white">
                      {{ totaliCategorie['fkm'] }}
                    </td>
                    <td class="text-white">
                      {{ euro.format(totaliCategorie['totale']) }}
                    </td>
                  </tr>
                  </tbody>
                </VTable>
              </VCard>
            </VCol>
            <VCol cols="6">
              <VCard
                :title="$t('Label.Materia-Prima')"
                class="mb-10"
              >
                <VTable
                  theme=""
                  class="text-no-wrap rounded-0"
                >
                  <thead>
                  <tr style="background-color: #0f7609">
                    <th>
                      <span class="text-white">{{ $t('Label.Categorie') }}</span>
                    </th>
                    <th>
                      <span class="text-white">{{ $t('Label.Totale') }}</span>
                    </th>
                  </tr>
                  </thead>

                  <tbody>
                  <tr
                    v-for="(item, index) in serverItems"
                    :key="index"
                  >
                    <td v-if="index === 'Raw Materials OFC' || index === 'Fiber Optics OFC' || index === 'Raw Materials CC' || index === 'Packaging'">
                      {{ index }}
                    </td>
                    <td v-if="index === 'Raw Materials OFC' || index === 'Fiber Optics OFC' || index === 'Raw Materials CC' || index === 'Packaging'">
                      {{ euro.format(value('materie', 'totale', item.valore)) }}
                    </td>
                  </tr>
                  <tr style="background-color: #c14d1e">
                    <td>
                      <span class="text-white">{{ $t('Label.Totale') }}</span>
                    </td>
                    <td>
                      <span class="text-white">{{ euro.format(totaliMaterie['totale']) }}</span>
                    </td>
                  </tr>
                  </tbody>
                </VTable>
              </VCard>

              <VCard
                :title="$t('Label.Lavori-In-Corso')"
                class="mb-10"
              >
                <VTable
                  theme=""
                  class="text-no-wrap rounded-0"
                >
                  <thead>
                  <tr style="background-color: #0f7609">
                    <th>
                      <span class="text-white">{{ $t('Label.Categorie') }}</span>
                    </th>
                    <th>
                      <span class="text-white">{{ $t('Label.Totale') }}</span>
                    </th>
                  </tr>
                  </thead>

                  <tbody>
                  <tr
                    v-for="(item, index) in serverItems"
                    :key="index"
                  >
                    <td v-if="index === 'WIP OFC' || index === 'WIP CC' || index === 'Corso Lavori'">
                      {{ index }}
                    </td>
                    <td v-if="index === 'WIP OFC' || index === 'WIP CC' || index === 'Corso Lavori'">
                      {{ euro.format(value('in_corso', 'totale', item.valore)) }}
                    </td>
                  </tr>
                  <tr style="background-color: #c14d1e">
                    <td>
                      <span class="text-white">{{ $t('Label.Totale') }}</span>
                    </td>
                    <td>
                      <span class="text-white">{{ euro.format(totaliInCorso['totale']) }}</span>
                    </td>
                  </tr>
                  </tbody>
                </VTable>
              </VCard>

              <VCard
                :title="$t('Label.Prodotti-Finiti')"
                class="mb-10"
              >
                <VTable
                  theme=""
                  class="text-no-wrap rounded-0"
                >
                  <thead>
                  <tr style="background-color: #0f7609">
                    <th>
                      <span class="text-white">{{ $t('Label.Categorie') }}</span>
                    </th>
                    <th>
                      <span class="text-white">{{ $t('Label.Totale') }}</span>
                    </th>
                  </tr>
                  </thead>

                  <tbody>
                  <tr
                    v-for="(item, index) in serverItems"
                    :key="index"
                  >
                    <td v-if="index === 'Finished Products CC' || index === 'Finished Products OFC'">
                      {{ index }}
                    </td>
                    <td v-if="index === 'Finished Products CC' || index === 'Finished Products OFC'">
                      {{ euro.format(value('finiti', 'totale', item.valore)) }}
                    </td>
                  </tr>
                  <tr style="background-color: #c14d1e">
                    <td>
                      <span class="text-white">{{ $t('Label.Totale') }}</span>
                    </td>
                    <td>
                      <span class="text-white">{{ euro.format(totaleProdottiFiniti['totale']) }}</span>
                    </td>
                  </tr>
                  </tbody>
                </VTable>
              </VCard>
            </VCol>
          </VRow>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>

  <LoadingStandBy v-model="loadingPage"></LoadingStandBy>
</template>
