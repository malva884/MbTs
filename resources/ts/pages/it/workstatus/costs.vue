<script setup lang="ts">
import moment from 'moment/moment'

definePage({
  meta: {
    action: 'import',
    subject: 'It-Work-Progress',
  },
})

const isSnackbarVisible = ref(false)
const message = ref('')
const color = ref('')

// VFileInput (Vuetify 3.3) espone sempre un array di File
const machineFile = ref<File[] | null>(null)
const materialFile = ref<File[] | null>(null)
const manpower = ref<string>('')

const loadingMachines = ref(false)
const loadingMaterials = ref(false)
const loadingManpower = ref(false)

const machineDates = ref<string[]>([])
const materialDates = ref<string[]>([])

const formatDate = (d: any) => moment(String(d.date_import ?? d).substring(0, 10)).format('DD/MM/YYYY')

const loadDates = async () => {
  const { data } = await useApi<any>('/ws/cost/dates')

  if (data.value) {
    machineDates.value = (data.value.machines || []).map(formatDate)
    materialDates.value = (data.value.materials || []).map(formatDate)
    manpower.value = data.value.manpower ?? ''
  }
}

const uploadMachines = async () => {
  loadingMachines.value = true
  try {
    const formData = new FormData()

    const file = machineFile.value?.[0]

    if (file)
      formData.append('file', file)

    const retuenData = await $api('/ws/cost/machines', { method: 'POST', body: formData })

    message.value = retuenData.message || 'Costi macchinari importati'
    color.value = retuenData.color || 'success'
    isSnackbarVisible.value = true
    machineFile.value = null
    await loadDates()
  }
  catch (e: any) {
    message.value = e?.data?.message || 'Errore importazione'
    color.value = 'error'
    isSnackbarVisible.value = true
  }
  loadingMachines.value = false
}

const uploadMaterials = async () => {
  loadingMaterials.value = true
  try {
    const formData = new FormData()

    const file = materialFile.value?.[0]

    if (file)
      formData.append('file', file)

    const retuenData = await $api('/ws/cost/materials', { method: 'POST', body: formData })

    message.value = retuenData.message || 'Costi materiali importati'
    color.value = retuenData.color || 'success'
    isSnackbarVisible.value = true
    materialFile.value = null
    await loadDates()
  }
  catch (e: any) {
    message.value = e?.data?.message || 'Errore importazione'
    color.value = 'error'
    isSnackbarVisible.value = true
  }
  loadingMaterials.value = false
}

const saveManpower = async () => {
  loadingManpower.value = true
  try {
    const retuenData = await $api('/ws/cost/manpower', {
      method: 'POST',
      body: { manpower: manpower.value },
    })

    message.value = retuenData.message || 'Costo manodopera salvato'
    color.value = retuenData.color || 'success'
    isSnackbarVisible.value = true
  }
  catch (e: any) {
    message.value = e?.data?.message || 'Errore salvataggio'
    color.value = 'error'
    isSnackbarVisible.value = true
  }
  loadingManpower.value = false
}

loadDates()
</script>

<template>
  <div class="workspace-container w-100 d-flex flex-column pa-4 gap-3">
    <VSnackbar v-model="isSnackbarVisible" transition="scroll-y-reverse-transition" location="top center" :timeout="4000">
      {{ $t(message) }}
    </VSnackbar>

    <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
      <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
        <div class="d-flex align-center gap-2">
          <VIcon icon="tabler-table-import" size="24" color="primary" />
          <div>
            <div class="text-h6 font-weight-medium">Gestione Costi</div>
            <div class="text-caption text-medium-emphasis">Listini utilizzati per la valorizzazione dello Stato Lavori</div>
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
        <VRow>
          <!-- Macchinari -->
          <VCol cols="12" md="6">
            <VCard variant="outlined">
              <VCardTitle class="text-base">Import Macchinari</VCardTitle>
              <VCardText>
                <VAlert density="compact" type="info" variant="tonal" class="mb-3 text-caption">
                  <div class="font-weight-medium mb-1">Formato file (1ª riga = intestazione)</div>
                  <div><b>A</b> → Codice Macchina <span class="text-medium-emphasis">(obbligatoria)</span></div>
                  <div><b>B</b> → Costo orario macchina</div>
                  <div><b>C</b> → Costo orario manodopera <span class="text-medium-emphasis">(opzionale)</span></div>
                </VAlert>
                <VFileInput
                  v-model="machineFile"
                  accept=".xlsx,.xls,.csv"
                  label="File costi macchinari"
                  density="compact"
                  class="mb-2"
                />
                <VBtn
                  color="primary"
                  prepend-icon="tabler-upload"
                  :loading="loadingMachines"
                  @click="uploadMachines"
                >
                  Import
                </VBtn>
                <div class="text-caption text-medium-emphasis mt-2">
                  Senza file i costi vengono importati dall'ultimo listino del vecchio portale
                </div>
                <div v-if="machineDates.length" class="text-caption text-medium-emphasis mt-3">
                  Ultime date disponibili: {{ machineDates.slice(0, 5).join(', ') }}
                </div>
              </VCardText>
            </VCard>
          </VCol>

          <!-- Materiali -->
          <VCol cols="12" md="6">
            <VCard variant="outlined">
              <VCardTitle class="text-base">Import Materiali</VCardTitle>
              <VCardText>
                <VAlert density="compact" type="info" variant="tonal" class="mb-3 text-caption">
                  <div class="font-weight-medium mb-1">Formato file (1ª riga = intestazione)</div>
                  <div><b>A</b> → Codice Materiale <span class="text-medium-emphasis">(obbligatoria)</span></div>
                  <div><b>N</b> → Costo unitario materiale</div>
                </VAlert>
                <VFileInput
                  v-model="materialFile"
                  accept=".xlsx,.xls,.csv"
                  label="File costi materiali"
                  density="compact"
                  class="mb-2"
                />
                <VBtn
                  color="primary"
                  prepend-icon="tabler-upload"
                  :loading="loadingMaterials"
                  @click="uploadMaterials"
                >
                  Import
                </VBtn>
                <div class="text-caption text-medium-emphasis mt-2">
                  Senza file i costi vengono importati dall'anagrafica materiali
                </div>
                <div v-if="materialDates.length" class="text-caption text-medium-emphasis mt-3">
                  Ultime date disponibili: {{ materialDates.slice(0, 5).join(', ') }}
                </div>
              </VCardText>
            </VCard>
          </VCol>

          <!-- Manodopera -->
          <VCol cols="12" md="6">
            <VCard variant="outlined">
              <VCardTitle class="text-base">Costo Manodopera</VCardTitle>
              <VCardText>
                <div class="d-flex align-center gap-3">
                  <AppTextField
                    v-model="manpower"
                    type="number"
                    step="0.5"
                    label="Costo orario manodopera (€/h)"
                    style="max-width: 260px"
                  />
                  <VBtn
                    color="primary"
                    prepend-icon="tabler-device-floppy"
                    :loading="loadingManpower"
                    :disabled="!manpower"
                    @click="saveManpower"
                  >
                    Salva
                  </VBtn>
                </div>
              </VCardText>
            </VCard>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>
  </div>
</template>
