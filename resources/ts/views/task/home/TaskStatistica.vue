<script setup lang="ts">

const statistics = ref()
const statisticheTaskLoad = async () => {
  try {
    const { data: statisticheData } = await useApi<any>(createUrl(`/task/statistiche`))

    if (statisticheData?.value) {
      statistics.value = [
        {
          title: 'Task Assegnati',
          stats: statisticheData.value.taskAssegnati || 0,
          icon: 'tabler-stack-3',
          color: 'primary',
        },
        {
          title: 'Task Mese',
          stats: statisticheData.value.taskApertiMese || 0,
          icon: 'tabler-calendar',
          color: 'info',
        },
        {
          title: 'Task Chiusi',
          stats: statisticheData.value.taskChiusiMese || 0,
          icon: 'tabler-list-check',
          color: 'success',
        },
        {
          title: 'Task Aperti',
          stats: statisticheData.value.taskAperti || 0,
          icon: 'tabler-hourglass-empty',
          color: 'warning',
        },
        {
          title: 'Task Sospesi',
          stats: statisticheData.value.taskSospesi || 0,
          icon: 'tabler-hand-stop',
          color: 'error',
        },
        {
          title: 'Task In Svolgimento',
          stats: statisticheData.value.taskLavorazione || 0,
          icon: 'tabler-progress',
          color: 'success',
        },
      ]
    } else {
      statistics.value = []
    }
  } catch (error) {
    console.error('Error loading statistics:', error)
    statistics.value = []
  }
}

statisticheTaskLoad()
</script>

<template>
  <VCard title="Statistics">
    <VCardText class="pt-6">
      <VRow>
        <VCol
          v-for="item in statistics"
          :key="item.title"
          cols="6"
          md="2"
        >
          <div class="d-flex align-center gap-4">
            <VAvatar
              :color="item.color"
              variant="tonal"
              size="42"
            >
              <VIcon :icon="item.icon" />
            </VAvatar>

            <div class="d-flex flex-column">
              <span class="text-h5 font-weight-medium">{{ item.stats }}</span>
              <span class="text-sm">
                {{ item.title }}
              </span>
            </div>
          </div>
        </VCol>
      </VRow>
    </VCardText>
  </VCard>
</template>

<style scoped lang="scss">

</style>
