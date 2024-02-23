<script setup lang="ts">
import { defineComponent } from "vue";

interface Props {
  id: number
}

const props = defineProps<Props>()

// Data table options
let online = ref(false)


const {data: userOnline} = await useApi<any>(createUrl('/users/userOnLine/'+props.id, ))
online = userOnline.value.online


</script>


<template>
  <h1 v-if="online"><svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-wifi" width="32" height="32" viewBox="0 0 24 24" stroke-width="1.5" stroke="#00b341" fill="none" stroke-linecap="round" stroke-linejoin="round">
    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
    <path d="M12 18l.01 0" />
    <path d="M9.172 15.172a4 4 0 0 1 5.656 0" />
    <path d="M6.343 12.343a8 8 0 0 1 11.314 0" />
    <path d="M3.515 9.515c4.686 -4.687 12.284 -4.687 17 0" />
  </svg></h1>
  <h1 v-else></h1>
</template>
