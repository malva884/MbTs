<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import TaskApprovazione from '@/views/task/home/TaskApprovazione.vue'
import TaskAggiornati from '@/views/task/home/TaskAggiornati.vue'
import TaskStatistica from '@/views/task/home/TaskStatistica.vue'

const { t } = useI18n()
const itemsPerPage = ref(6)
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const page = ref(1)
const serverItems = ref<any>([])
const q = ref('')
const loading = ref(false)
const aggiornaTask = ref(false)

const loadItems = async () => {
  loading.value = true

  const { data: resultData } = await useApi<any>(createUrl('task/dashboard/approvare', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      search: q.value,
      stato: 3,
    },
  }))

  if (resultData.value !== null) {
    serverItems.value = resultData.value.data
    totalItems.value = resultData.value.total
  }
  else {
    serverItems.value = []
    totalItems.value = 0
  }
  loading.value = false
}

const refresh = () => {
  aggiornaTask.value = true
}
</script>

<template>
  <VCard>
    <VRow>
      <VCol
        cols="12"
        md="12"
      >
        <TaskStatistica />
      </VCol>
      <VCol
        cols="12"
        md="6"
      >
        <TaskApprovazione @refresh="refresh"/>
      </VCol>
      <VCol
        cols="12"
        md="6"
      >
        <TaskAggiornati :aggiorna-task="aggiornaTask"/>
      </VCol>
    </VRow>
    <!-- 👉 task da Approvare -->
  </VCard>
</template>

<style scoped lang="scss">

</style>
