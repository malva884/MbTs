<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useScreens } from 'vue-screen-utils'

definePage({
  meta: {
    action: 'read',
    subject: 'Hr-Richieste',
  },
})

const view = ref(false)
const viewList = ref(false)
const dates = ref<string[]>([])
const attributes = ref<any[]>([])
const dateSelect = ref<string | null>(null)
const listaDipendenti = ref<any[]>([])

// Gestione colonne responsive per il calendario (fino a 4 mesi affiancati)
const { mapCurrent } = useScreens({ xs: '0px', sm: '640px', md: '768px', lg: '1024px' })
const columns = mapCurrent({ lg: 4, md: 2 }, 1)

const loadItems = async () => {
  try {
    const { data: resultData } = await useApi<any>(createUrl('/hr/requests/index'))

    dates.value = resultData.value.data || []

    // Evidenzia sul calendario i giorni in cui ci sono richieste/presenze
    attributes.value = [
      {
        highlight: {
          color: 'blue',
          fillMode: 'light',
        },
        dates: dates.value,
      },
    ]

    view.value = true
  } catch (error) {
    console.error("Errore nel caricamento dell'indice calendario:", error)
  }
}

onMounted(() => {
  loadItems()
})

const modelConfig = {
  type: 'string',
  mask: 'YYYY-MM-DD',
}

// Chiamata API attivata al cambio di data sul calendario
const onDateChange = async (newDate: string | null) => {
  if (!newDate) return

  viewList.value = false
  try {
    const { data: resultData } = await useApi<any>(createUrl('/hr/requests/get_emploee', {
      query: {
        date: newDate,
      },
    }))

    listaDipendenti.value = resultData.value || []
    viewList.value = true
  } catch (error) {
    console.error("Errore nel recupero dei dipendenti per la data:", error)
  }
}

const resolveTipologia = (tipologia: string) => {
  const mapping: Record<string, { color: string; text: string; variant: string }> = {
    '1': { color: 'success', text: 'Ferie', variant: 'tonal' },
    '2': { color: 'purple', text: '104', variant: 'tonal' },
    '5': { color: 'warning', text: 'Permesso', variant: 'tonal' },
    '6': { color: 'error', text: 'Malattia', variant: 'tonal' },
  }
  return mapping[tipologia] || { color: 'secondary', text: '---', variant: 'tonal' }
}
</script>

<template>
  <div class="pa-1">
    <VRow>

      <VCol cols="12" md="8">
        <VCard variant="outlined" v-if="view">
          <VCardItem class="py-4">
            <VCardTitle class="text-h6 font-weight-bold d-flex align-center gap-2">
              <VIcon icon="tabler-calendar" color="primary" />
              {{ $t('label.Calendario-Presenze') }}
            </VCardTitle>
          </VCardItem>
          <VDivider />

          <VCardText class="pa-4">
            <div class="datepicker-wrapper border rounded pa-2 bg-white-calendar">
              <VDatePicker
                v-model="dateSelect"
                :rows="3"
                :columns="columns"
                :attributes="attributes"
                :model-config="modelConfig"
                is-expanded
                borderless
                transparent
                class="w-100"
                @update:model-value="onDateChange"
              />
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" md="4">
        <VCard variant="outlined" v-if="viewList" class="d-flex flex-column h-100">
          <VCardItem class="py-4">
            <VCardTitle class="text-h6 font-weight-bold d-flex align-center gap-2">
              <VIcon icon="tabler-users" color="primary" />
              {{ $t('label.Dipendenti') }}
            </VCardTitle>
            <template #append>
              <VChip size="small" color="primary" variant="flat" class="font-weight-bold">
                {{ listaDipendenti.length }}
              </VChip>
            </template>
          </VCardItem>
          <VDivider />

          <VCardText class="pa-0 overflow-y-auto" style="max-height: 615px;">
            <VList class="py-0" lines="two">
              <VListItem
                v-for="(item, index) in listaDipendenti"
                :key="index"
                class="py-3 border-b-light px-4"
              >
                <template #prepend>
                  <VAvatar color="primary" variant="tonal" size="40" class="font-weight-bold text-uppercase me-3">
                    {{ item.dipendente_cognome?.charAt(0) }}{{ item.dipendente_nome?.charAt(0) }}
                  </VAvatar>
                </template>

                <VListItemTitle class="font-weight-bold text-body-1 text-high-emphasis">
                  {{ item.dipendente_cognome }} {{ item.dipendente_nome }}
                </VListItemTitle>

                <VListItemSubtitle class="mt-1 d-flex align-center gap-2">
                  <VChip
                    :color="resolveTipologia(item.tipologia).color"
                    :variant="resolveTipologia(item.tipologia).variant"
                    size="x-small"
                    class="font-weight-bold"
                  >
                    {{ resolveTipologia(item.tipologia).text }}
                  </VChip>

                  <span
                    v-if="item.tipologia === '5' && item.ora_inizio"
                    class="text-caption text-warning font-weight-medium d-inline-flex align-center gap-1"
                  >
                    <VIcon icon="tabler-clock" size="14" />
                    {{ item.ora_inizio }} &raquo; {{ item.ora_fine }}
                  </span>
                </VListItemSubtitle>
              </VListItem>
            </VList>

            <div v-if="listaDipendenti.length === 0" class="text-center text-disabled py-8">
              Nessun dipendente registrato per questa data.
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>

<style scoped lang="scss">
.gap-1 { gap: 4px; }
.gap-2 { gap: 8px; }

// CSS per forzare lo sfondo interamente bianco sulle aree interne ed esterne di VDatePicker
.datepicker-wrapper.bg-white-calendar {
  background-color: #ffffff !important;
  border-color: rgba(var(--v-border-color), var(--v-border-opacity)) !important;

  :deep(.vc-container) {
    width: 100% !important;
    border: none !important;
    background-color: #ffffff !important;
  }

  :deep(.vc-pane-container),
  :deep(.vc-pane),
  :deep(.vc-header),
  :deep(.vc-weeks) {
    background-color: #ffffff !important;
  }
}

.border-b-light {
  border-bottom: 1px solid rgba(var(--v-border-color), 0.06);
}

.overflow-y-auto {
  overflow-y: auto !important;
  scrollbar-width: thin;
}
</style>
