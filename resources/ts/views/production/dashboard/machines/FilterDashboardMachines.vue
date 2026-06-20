<script setup lang="ts">
import { useI18n } from 'vue-i18n'

interface Emit {
  (e: 'update:filter', value: object): void
}

const emit = defineEmits<Emit>()
const { t } = useI18n()

const macchinaFilter = ref()
const categoriaFilter = ref()
const tipologiaFilter = ref()
const statoFilter = ref('Run')

const macchineOptions = ref<any[]>([{ id: null, titolo: 'Tutte' }])
const categorieOptions = ref<any[]>([])

const resolveCategorie = (cat: string) => {
  const map: Record<string, string> = {
    buffering: 'Buffering',
    stranding: 'Stranding',
    jacketing: 'Jacketing',
    marck: 'Marck',
  }
  return map[cat] || cat
}

const loadMacchine = async () => {
  try {
    const { data: resultData } = await useApi<any>(createUrl('/macchine/get_list', {
      query: { attivo: true },
    }))

    const seenCategories = new Set<string>()
    resultData.value?.forEach((value: any) => {
      macchineOptions.value.push({ id: value.name_gp, titolo: value.nome })
      if (value.categoria && !seenCategories.has(value.categoria)) {
        seenCategories.add(value.categoria)
        categorieOptions.value.push({ id: value.categoria, titolo: resolveCategorie(value.categoria) })
      }
    })
  } catch (e) {
    console.error('Errore caricamento macchine:', e)
  }
}

loadMacchine()

const setFilter = () => {
  emit('update:filter', {
    macchina: macchinaFilter.value,
    categoria: categoriaFilter.value,
    tipologia: tipologiaFilter.value,
    stato: statoFilter.value,
  })
}

const resetFilter = () => {
  macchinaFilter.value = null
  categoriaFilter.value = null
  tipologiaFilter.value = null
  statoFilter.value = null
  setFilter()
}
</script>

<template>
  <VCard flat class="filter-bar pa-3 mb-4">
    <VRow align="center" dense>
      <VCol cols="12" sm="6" md="3">
        <AppSelect
          v-model="macchinaFilter"
          :items="macchineOptions"
          :label="t('Label.Macchine')"
          :placeholder="t('Label.Macchine')"
          :item-title="(item: any) => item.titolo"
          :item-value="(item: any) => item.id"
          clearable
          clear-icon="tabler-x"
          persistent-hint
          @update:modelValue="setFilter"
        />
      </VCol>
      <VCol cols="12" sm="6" md="3">
        <AppSelect
          v-model="categoriaFilter"
          :items="categorieOptions"
          :label="t('Label.Categoria')"
          :placeholder="t('Label.Categoria')"
          :item-title="(item: any) => item.titolo"
          :item-value="(item: any) => item.id"
          clearable
          clear-icon="tabler-x"
          persistent-hint
          @update:modelValue="setFilter"
        />
      </VCol>
      <VCol cols="12" sm="6" md="2">
        <AppSelect
          v-model="tipologiaFilter"
          :items="[{ value: null, title: 'Tutti' }, { value: 1, title: 'Ottico' }, { value: 2, title: 'Rame' }]"
          :label="t('Label.Tipologia')"
          :placeholder="t('Label.Tipologia')"
          :item-title="(item: any) => item.title"
          :item-value="(item: any) => item.value"
          clearable
          clear-icon="tabler-x"
          persistent-hint
          @update:modelValue="setFilter"
        />
      </VCol>
      <VCol cols="12" sm="6" md="2">
        <AppSelect
          v-model="statoFilter"
          :items="[{ value: null, title: 'Tutte' }, { value: 'Run', title: 'Run' }, { value: 'Stop', title: 'Stop' }, { value: 'Fermo', title: 'Fermo Attivo' }]"
          :label="t('Label.Stato')"
          :placeholder="t('Label.Stato')"
          :item-title="(item: any) => item.title"
          :item-value="(item: any) => item.value"
          clearable
          clear-icon="tabler-x"
          persistent-hint
          @update:modelValue="setFilter"
        />
      </VCol>
      <VCol cols="12" md="2" class="d-flex justify-end">
        <VBtn
          variant="tonal"
          color="secondary"
          density="compact"
          prepend-icon="tabler-refresh"
          @click="resetFilter"
        >
          {{ t('Label.Reset') }}
        </VBtn>
      </VCol>
    </VRow>
  </VCard>
</template>

<style lang="scss" scoped>
.filter-bar {
  border-radius: 12px;
  background: rgba(var(--v-theme-surface), 1);
}
</style>
