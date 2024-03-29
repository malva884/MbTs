<script setup lang="ts">
definePage({
  meta: {
    action: 'create',
    subject: 'Reception-Register',
  },
})

const fetchConnection = async () => {
  const responseData = await useApi<any>(createUrl('/reception/google-calendar/connect'))

  console.log(responseData)
  window.location = responseData.data.value
}

fetchConnection()
</script>

<template>

</template>
