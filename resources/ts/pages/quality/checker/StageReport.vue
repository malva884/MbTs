<script setup lang="ts">

import {useI18n} from "vue-i18n";

const { t } = useI18n()
const view = ref(false)
const items = ref({})

interface Props {
  user?: number
  dateFilter?: string
}

const props = defineProps<Props>()
const loadData = async () => {
  const {data: stageReport } = await useApi<any>(createUrl('/qt/checker/report/stage', {
    query: {
      userId: props.user,
      dataFilter: props.dateFilter,
    },
  }))

  items.value = stageReport.value
  view.value = true
}


const resolveStatusVariant = (stage: string) => {
  if (stage === 'BUF')
    return { color: 'buf', text: 'BUF' }
  else if (stage === 'SZ')
    return { color: 'sz', text: 'SZ' }
  else if (stage === 'FC')
    return { color: 'fc', text: 'FC' }
  else if (stage === 'PE')
    return { color: 'pe', text: 'PE' }
  else if (stage === 'COL')
    return { color: 'col', text: 'COL' }
  else if (stage === 'GUA')
    return { color: 'gua', text: 'GUA' }
  else if (stage === 'ISOL')
    return { color: 'isol', text: 'ISOL' }
  else if (stage === 'CORD')
    return { color: 'cord', text: 'CORD' }
  else if (stage === 'ARMA')
    return { color: 'arma', text: 'ARMA' }
  else if (stage === 'NASTR')
    return { color: 'nastr', text: 'NASTR' }
  else
    return { color: 'sf', text: 'SF' }
}

loadData()
watch(props, () => {
  //items.value = []
  loadData()
})
</script>

<template>
  <VCard :title=" $t('label.Reprot-Per-Stage')">
    <VCardText v-if="view">
      <VList class="card-list">
        <VListItem
          v-for="state in items"
          :key="state.stage"
        >
          <VChip
            :color="resolveStatusVariant(state.stage).color"
            size="small"
          >
            {{ resolveStatusVariant(state.stage).text }}
          </VChip>

          <template #append>
            <span class="font-weight-medium text-medium-emphasis me-15">{{ state.totale }}</span>
            <span class="font-weight-medium text-medium-emphasis me-3">{{ state.km }} Km</span>
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
</style>
