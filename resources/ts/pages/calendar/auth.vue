<script setup lang="ts">

definePage({
  meta: {
    action: 'read',
    subject: 'user',
  },
})


const fetchConnection = async () => {
  const responseData = await useApi<any>(createUrl('/reception/google-calendar/connect', ))

  console.log(responseData.data.value)

  window.location = responseData.data.value;


}

fetchConnection()


</script>
