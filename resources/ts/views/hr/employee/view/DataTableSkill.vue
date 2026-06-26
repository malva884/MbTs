<script setup lang="ts">
interface Props {
  id: string
}

const props = defineProps<Props>()

const loading = ref(true)
const items = ref<any[]>([])
const anno = ref(new Date().getFullYear())

const evalDialog = ref(false)
const selectedItem = ref<any>(null)
const selectedValutazione = ref<number | null>(null)
const isSaving = ref(false)

const valutazioneOptions = [
  { title: 'Non richiesta', value: 0 },
  { title: 'Insufficiente', value: 1 },
  { title: 'Sufficiente', value: 2 },
  { title: 'Buona', value: 3 },
  { title: 'Ottima', value: 4 },
]

const valutazioneLabel = (val: number | null) => {
  if (val === null || val === undefined) return '-'
  const labels = ['Non richiesta', 'Insufficiente', 'Sufficiente', 'Buona', 'Ottima']
  return labels[val] ?? '-'
}

const valutazioneColor = (val: number | null) => {
  if (val === null || val === undefined) return 'grey'
  const colors = ['grey', 'error', 'warning', 'info', 'success']
  return colors[val] ?? 'grey'
}

const isScaduta = (item: any) => {
  if (item.valutazione === null || item.valutazione === undefined) return false
  if (!item.created_at) return true
  const scadenza = new Date(item.created_at)
  scadenza.setFullYear(scadenza.getFullYear() + 1)
  return scadenza < new Date()
}

const openDialog = (item: any) => {
  selectedItem.value = item
  selectedValutazione.value = item.valutazione ?? null
  evalDialog.value = true
}

const closeDialog = () => {
  evalDialog.value = false
  selectedItem.value = null
  selectedValutazione.value = null
}

const saveValutazione = async () => {
  if (!selectedItem.value || selectedValutazione.value === null) return

  isSaving.value = true
  await $api('/hr/competenze/valutazioni/store', {
    method: 'POST',
    body: {
      employee_id: props.id,
      activity_id: selectedItem.value.activity_id,
      valutazione: selectedValutazione.value,
      anno: anno.value,
      note: '',
    },
  })
  isSaving.value = false
  closeDialog()
  await loadCompetenze()
}

const loadCompetenze = async () => {
  loading.value = true
  const { data: resultData } = await useApi<any>(createUrl(`/hr/competenze/valutazioni/by_employee/${props.id}`, {
    query: { anno: anno.value },
  }))

  if (resultData.value) {
    items.value = resultData.value.activities
  } else {
    items.value = []
  }
  loading.value = false
}

onMounted(() => {
  loadCompetenze()
})
</script>

<template>
  <VCard :title="$t('Label.Competenze')">
    <VCardText>
      <div class="competence-content">
        <div v-if="loading" class="text-center py-4">
          <VProgressCircular indeterminate color="primary" size="24" />
        </div>
        <VList v-else-if="items.length" lines="two" density="compact">
          <VListItem
            v-for="item in items"
            :key="item.activity_id"
            :title="item.attivita"
            :class="{ 'expired-item': isScaduta(item), 'cursor-pointer': true }"
            @click="openDialog(item)"
          >
            <template #subtitle>
              <div class="d-flex align-center gap-2 flex-wrap">
                <VChip label size="x-small" variant="tonal" color="secondary">
                  {{ item.ruolo }}
                </VChip>
                <span v-if="item.valutatore" class="text-caption text-medium-emphasis">
                  {{ item.valutatore }}
                </span>
                <span v-if="item.data_valutazione" class="text-caption text-medium-emphasis">
                  {{ new Date(item.data_valutazione).toLocaleDateString('it-IT') }}
                </span>
              </div>
            </template>
            <template #append>
              <VChip
                label
                size="x-small"
                variant="tonal"
                :color="valutazioneColor(item.valutazione)"
              >
                {{ valutazioneLabel(item.valutazione) }}
              </VChip>
            </template>
          </VListItem>
        </VList>
        <VAlert
          v-else
          type="info"
          variant="tonal"
          density="comfortable"
        >
          Nessuna competenza disponibile.
        </VAlert>
      </div>
    </VCardText>
  </VCard>

  <VDialog v-model="evalDialog" max-width="500px">
    <VCard>
      <VCardTitle>
        {{ selectedItem?.attivita }}
      </VCardTitle>
      <VCardText>
        <VSelect
          v-model="selectedValutazione"
          :items="valutazioneOptions"
          item-title="title"
          item-value="value"
          label="Valutazione"
          density="compact"
          variant="outlined"
        />
      </VCardText>
      <VCardActions>
        <VSpacer />
        <VBtn variant="text" @click="closeDialog">Annulla</VBtn>
        <VBtn color="primary" :loading="isSaving" @click="saveValutazione">Salva</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<style scoped>
.competence-content {
  max-height: 70vh;
  overflow-y: auto;
}

.expired-item {
  background-color: rgba(231, 8, 8, 0.15);
}

.cursor-pointer {
  cursor: pointer;
}
</style>
