<script setup lang="ts">

import {useI18n} from 'vue-i18n'

const { t } = useI18n()
const view = ref(false)
const items = ref({})

interface Props {
  dateFilter?: string
  olFilter?: string
  tipologiaFilter?: string
  materialeFilter?: string
  esitoFilter?: string
  standardFilter?: string
  specificaFilter?: string
}

const props = defineProps<Props>()

const loadData = async () => {
  const { data: topoReport } = await useApi<any>(createUrl('/qt/prove_tipo/report/tipo', {
    query: {
      data: props.dateFilter,
      materiale: props.materialeFilter,
      ol: props.olFilter,
      tipologia: props.tipologiaFilter,
      esito: props.esitoFilter,
      standard: props.standardFilter,
      specifica: props.specificaFilter,
    },
  }))

  items.value = topoReport.value
  view.value = true
}

loadData()
watch(props, () => {
  loadData()
})
</script>

<template>
  <VCard
    :title=" $t('label.Report-Per-Tipo')"

  >
    <VCardText v-if="view">
      <VList class="card-list scroll" height="250">
        <VListItem
          v-for="prove in items"
          :key="prove.categoria"
        >
          <VChip size="small">
            {{ prove.categoria }}
          </VChip>

          <template #append>
            <span class="font-weight-medium text-medium-emphasis me-10">{{ prove.totale }}</span>
          </template>
        </VListItem>
      </VList>
    </VCardText>
  </VCard>
</template>

<style lang="scss" scoped>
.card-list {
  --v-card-list-gap: 26px;
}

scroll {
  margin: 4px, 4px;
  padding: 4px;
  background-color: green;

  overflow-x: hidden;
  overflow-y: auto;
  text-align: justify;
}
</style>
