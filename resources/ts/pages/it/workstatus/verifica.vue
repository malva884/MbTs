<script setup lang="ts">
definePage({
  meta: {
    action: 'import',
    subject: 'It-Work-Progress',
  },
})

const file = ref<File | null>(null)
const loading = ref(false)
const results = ref<Record<string, string[]>>({})
const checked = ref(false)
const isSnackbarVisible = ref(false)
const message = ref('')
const color = ref('')

const orders = computed(() => Object.keys(results.value))

const uploadFile = (event: any) => {
  file.value = event.target.files?.[0] ?? null
}

const check = async () => {
  if (!file.value)
    return

  loading.value = true
  results.value = {}
  checked.value = false

  try {
    const formData = new FormData()

    formData.append('file', file.value)

    const retuenData = await $api('/ws/verifica/check', {
      method: 'POST',
      body: formData,
    })

    results.value = retuenData.duplicates || {}
    checked.value = true
  }
  catch (e: any) {
    message.value = e?.data?.message || 'Errore verifica file'
    color.value = 'error'
    isSnackbarVisible.value = true
  }

  loading.value = false
}
</script>

<template>
  <div class="workspace-container w-100 d-flex flex-column pa-4 gap-3">
    <VSnackbar v-model="isSnackbarVisible" transition="scroll-y-reverse-transition" location="top center" :timeout="4000">
      {{ message }}
    </VSnackbar>

    <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
      <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
        <div class="d-flex align-center gap-2">
          <VIcon icon="tabler-file-check" size="24" color="primary" />
          <div>
            <div class="text-h6 font-weight-medium">Zestra Check</div>
            <div class="text-caption text-medium-emphasis">Verifica materiali duplicati per ordine nel file Zestra</div>
          </div>
        </div>
        <VBtn
          prepend-icon="tabler-arrow-left"
          variant="outlined"
          color="secondary"
          density="comfortable"
          :to="{ name: 'it-workstatus' }"
        >
          Torna alla lista
        </VBtn>
      </VCardText>
      <VDivider />

      <VCardText class="pa-6">
        <VRow align="center">
          <VCol cols="12" md="6">
            <VFileInput
              accept=".xlsx,.xls,.csv"
              label="File Zestra da verificare"
              @change="uploadFile"
            />
          </VCol>
          <VCol cols="12" md="6">
            <VBtn
              color="primary"
              prepend-icon="tabler-check"
              :loading="loading"
              :disabled="!file"
              @click="check"
            >
              Controlla
            </VBtn>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <VCard v-if="checked" variant="outlined" class="bg-surface border-thin rounded-lg">
      <VCardText class="py-3">
        <div class="text-h6 font-weight-medium mb-1">
          Ordini con materiali duplicati
        </div>
        <div v-if="!orders.length" class="text-body-2 text-success py-4">
          Nessun materiale duplicato trovato
        </div>
        <VList v-else density="compact">
          <VListItem v-for="order in orders" :key="order">
            <template #prepend>
              <VIcon icon="tabler-alert-triangle" color="warning" class="me-3" />
            </template>
            <VListItemTitle class="font-weight-medium">
              Ordine {{ order }}
            </VListItemTitle>
            <VListItemSubtitle>
              {{ results[order].join(', ') }}
            </VListItemSubtitle>
          </VListItem>
        </VList>
      </VCardText>
    </VCard>
  </div>
</template>
