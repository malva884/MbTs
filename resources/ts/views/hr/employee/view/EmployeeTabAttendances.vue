<script lang="ts" setup>

interface Props {
  id: string
}

const props = defineProps<Props>()
const componentKey = ref(0)
let activitys = ref([])

const fetchPermissions = async () => {
  const activitiesData = await useApi<any>(createUrl(`/users/activities/${props.id}`))

  activitys = activitiesData.data
  componentKey.value += 1
}

//fetchPermissions()

</script>

<template>
  <VRow>
    Presenze
  </VRow>
</template>
