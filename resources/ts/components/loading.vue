<script setup lang="ts">

const editDialog = true

</script>


<template>
  <VDialog
    v-model="editDialog"
    max-width="900px"
  >
  <template #content-loading>
    <div>
      <h1 class="text-h1">
        Loading...
      </h1>
    </div>
  </template>
  </VDialog>
</template>

