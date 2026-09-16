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
  <div class="d-flex flex-column gap-4">
    <VRow>
      <!-- 👉 Categorie Table -->
      <VCol cols="12" md="6">
        <VCard variant="outlined" class="bg-surface border-thin rounded-lg h-100">
          <VCardText class="d-flex align-center gap-2 py-3">
            <VAvatar color="primary" variant="tonal" size="34">
              <VIcon icon="tabler-list" size="18" />
            </VAvatar>
            <div>
              <div class="text-subtitle-1 font-weight-medium">{{ $t('Label.Categorie') }}</div>
              <div class="text-caption text-medium-emphasis">{{ $t('Label.Riepilogo-Categorie') }}</div>
            </div>
          </VCardText>
          <VDivider />
          <VTable density="comfortable" class="text-no-wrap">
            <thead>
              <tr>
                <th class="text-uppercase text-caption font-weight-medium text-medium-emphasis">{{ $t('Label.Categorie') }}</th>
                <th class="text-uppercase text-caption font-weight-medium text-medium-emphasis">{{ $t('Label.Quantita') }}</th>
                <th class="text-uppercase text-caption font-weight-medium text-medium-emphasis">{{ $t('Label.Fkm') }}</th>
                <th class="text-uppercase text-caption font-weight-medium text-medium-emphasis text-right">{{ $t('Label.Totale') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(item, index) in serverItems"
                :key="index"
                :class="index === 'Magazzino' || index === 'Corso Lavori' ? 'bg-success bg-opacity-10 font-weight-bold' : ''"
              >
                <td class="font-weight-bold">{{ index }}</td>
                <td>{{ value('categorie', 'ckm', item.ckm) }}</td>
                <td>{{ value('categorie', 'fkm', item.fkm) }}</td>
                <td class="text-right text-high-emphasis font-weight-bold">{{ euro.format(value('categorie', 'totale', item.valore, index)) }}</td>
              </tr>
              <tr class="bg-error bg-opacity-10">
                <td class="font-weight-bold">{{ $t('Label.Totale') }}</td>
                <td class="font-weight-bold">{{ totaliCategorie['ckm'] }}</td>
                <td class="font-weight-bold">{{ totaliCategorie['fkm'] }}</td>
                <td class="text-right font-weight-bold text-error">{{ euro.format(totaliCategorie['totale']) }}</td>
              </tr>
            </tbody>
          </VTable>
        </VCard>
      </VCol>

      <!-- 👉 Right column: sub-categories stacked -->
      <VCol cols="12" md="6" class="d-flex flex-column gap-4">
        <!-- 👉 Materia Prima -->
        <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
          <VCardText class="d-flex align-center gap-2 py-3">
            <VAvatar color="info" variant="tonal" size="34">
              <VIcon icon="tabler-package" size="18" />
            </VAvatar>
            <div class="text-subtitle-1 font-weight-medium">{{ $t('Label.Materia-Prima') }}</div>
          </VCardText>
          <VDivider />
          <VTable density="comfortable" class="text-no-wrap">
            <thead>
              <tr>
                <th class="text-uppercase text-caption font-weight-medium text-medium-emphasis">{{ $t('Label.Categorie') }}</th>
                <th class="text-uppercase text-caption font-weight-medium text-medium-emphasis text-right">{{ $t('Label.Totale') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(item, index) in serverItems"
                :key="index"
              >
                <template v-if="index === 'Raw Materials OFC' || index === 'Fiber Optics OFC' || index === 'Raw Materials CC' || index === 'Packaging'">
                  <td class="font-weight-medium">{{ index }}</td>
                  <td class="text-right text-success font-weight-medium">{{ euro.format(value('materie', 'totale', item.valore)) }}</td>
                </template>
              </tr>
              <tr class="bg-error bg-opacity-10">
                <td class="font-weight-bold">{{ $t('Label.Totale') }}</td>
                <td class="text-right font-weight-bold text-error">{{ euro.format(totaliMaterie['totale']) }}</td>
              </tr>
            </tbody>
          </VTable>
        </VCard>

        <!-- 👉 Lavori In Corso -->
        <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
          <VCardText class="d-flex align-center gap-2 py-3">
            <VAvatar color="warning" variant="tonal" size="34">
              <VIcon icon="tabler-progress" size="18" />
            </VAvatar>
            <div class="text-subtitle-1 font-weight-medium">{{ $t('Label.Lavori-In-Corso') }}</div>
          </VCardText>
          <VDivider />
          <VTable density="comfortable" class="text-no-wrap">
            <thead>
              <tr>
                <th class="text-uppercase text-caption font-weight-medium text-medium-emphasis">{{ $t('Label.Categorie') }}</th>
                <th class="text-uppercase text-caption font-weight-medium text-medium-emphasis text-right">{{ $t('Label.Totale') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(item, index) in serverItems"
                :key="index"
              >
                <template v-if="index === 'WIP OFC' || index === 'WIP CC' || index === 'Corso Lavori'">
                  <td class="font-weight-medium">{{ index }}</td>
                  <td class="text-right text-success font-weight-medium">{{ euro.format(value('in_corso', 'totale', item.valore)) }}</td>
                </template>
              </tr>
              <tr class="bg-error bg-opacity-10">
                <td class="font-weight-bold">{{ $t('Label.Totale') }}</td>
                <td class="text-right font-weight-bold text-error">{{ euro.format(totaliInCorso['totale']) }}</td>
              </tr>
            </tbody>
          </VTable>
        </VCard>

        <!-- 👉 Prodotti Finiti -->
        <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
          <VCardText class="d-flex align-center gap-2 py-3">
            <VAvatar color="success" variant="tonal" size="34">
              <VIcon icon="tabler-circle-check" size="18" />
            </VAvatar>
            <div class="text-subtitle-1 font-weight-medium">{{ $t('Label.Prodotti-Finiti') }}</div>
          </VCardText>
          <VDivider />
          <VTable density="comfortable" class="text-no-wrap">
            <thead>
              <tr>
                <th class="text-uppercase text-caption font-weight-medium text-medium-emphasis">{{ $t('Label.Categorie') }}</th>
                <th class="text-uppercase text-caption font-weight-medium text-medium-emphasis text-right">{{ $t('Label.Totale') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(item, index) in serverItems"
                :key="index"
              >
                <template v-if="index === 'Finished Products CC' || index === 'Finished Products OFC'">
                  <td class="font-weight-medium">{{ index }}</td>
                  <td class="text-right text-success font-weight-medium">{{ euro.format(value('finiti', 'totale', item.valore)) }}</td>
                </template>
              </tr>
              <tr class="bg-error bg-opacity-10">
                <td class="font-weight-bold">{{ $t('Label.Totale') }}</td>
                <td class="text-right font-weight-bold text-error">{{ euro.format(totaleProdottiFiniti['totale']) }}</td>
              </tr>
            </tbody>
          </VTable>
        </VCard>
      </VCol>
    </VRow>
  </div>

  <LoadingStandBy v-model="loadingPage"></LoadingStandBy>
</template>
