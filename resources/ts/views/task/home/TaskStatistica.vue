<script setup lang="ts">

const statistics = ref()
const statisticheTaskLoad = async () => {
  const { data: statisticheData } = await useApi<any>(createUrl(`/task/statistiche`))

  statistics.value = [
    {
      title: 'Task Assegnati',
      stats: statisticheData.value.taskAssegnati,
      icon: 'tabler-stack-3',
      color: 'primary',
    },
    {
      title: 'Task Mese',
      stats: statisticheData.value.taskApertiMese,
      icon: 'tabler-calendar',
      color: 'info',
    },
    {
      title: 'Task Chiusi',
      stats: statisticheData.value.taskChiusiMese,
      icon: 'tabler-list-check',
      color: 'success',
    },
    {
      title: 'Task Aperti',
      stats: statisticheData.value.taskAperti,
      icon: 'tabler-hourglass-empty',
      color: 'warning',
    },
    {
      title: 'Task Sospesi',
      stats: statisticheData.value.taskSospesi,
      icon: 'tabler-hand-stop',
      color: 'error',
    },
    {
      title: 'Task In Svolgimento',
      stats: statisticheData.value.taskLavorazione,
      icon: 'tabler-progress',
      color: 'success',
    },
  ]
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
