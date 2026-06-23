<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import type { VForm } from 'vuetify/components/VForm'
import type { Cavo, StrutturaCavo } from '@/views/offices/technical/cables/type'
import CavoBioPanel from '@/views/offices/technical/cables/view/cavoBioPanel.vue'
import { can } from '@layouts/plugins/casl'
import DefineAbilities from '@/plugins/casl/DefineAbilities'

definePage({
  meta: {
    action: 'list',
    subject: 'Cavi',
  },
})

const route = useRoute('offices-technical-cables-view-id')

const { t } = useI18n()
const refForm = ref<VForm>()
const cavoData = ref<Cavo>()
const loading = ref(true)
const serverItems = ref<[]>([])
const centriOptions = ref([])
const materialiOptions = ref([])
const deletedItem = ref({})

const isDialogVisible = ref(false)
const deleteDialog = ref(false)

const defaultItem = ref<any>({
  cavo_id: '',
  centro: null,
  materiale: null,
  descrizione: '',
  diametro: 0,
  peso: 0,
  ordinata: '',
  elementi: '',
  nota: '',
  posizione: '',
})

const editedItem = ref({})
const snackbar = ref({ show: false, message: '', color: '' })

const hasCentro = computed(() => !!editedItem.value?.centro)
const hasMateriale = computed(() => !!editedItem.value?.materiale)
const righeConProblemi = computed(() => serverItems.value.filter((r: any) => r.centro_missing || r.materiale_missing))

// headers
const headers = [
  { title: '#', key: 'posizione', sortable: false, width: '60px' },
  { title: t('Table.Macchina'), key: 'centro', sortable: false },
  { title: t('Table.Materiale'), key: 'materiale', sortable: false },
  { title: t('Table.Descrizione'), key: 'descrizione', sortable: false },
  { title: 'Dim.', key: 'dimensioni', sortable: false, width: '120px' },
  { title: 'Produzione', key: 'produzione', sortable: false, width: '160px' },
  { title: '', key: 'nota', sortable: false, width: '40px' },
  { title: '', key: 'actions', sortable: false, width: '90px' },
]

const newItem = () => {
  editedItem.value = { ...defaultItem.value }
  isDialogVisible.value = true
}

const editItem = (item: StrutturaCavo) => {
  // editedIndex.value = serverItems.value.indexOf(item)
  editedItem.value = { ...item }
  isDialogVisible.value = true
}

const getCavo = async () => {
  const [cavoResult, rowsResult] = await Promise.all([
    useApi<any>(createUrl(`/to/cavi/view/${route.params.id}`)),
    useApi<any>(createUrl(`/to/cavi/view/${route.params.id}/rows`)),
  ])

  cavoData.value = cavoResult.data.value
  serverItems.value = rowsResult.data.value
  loading.value = false
}

const loadItems = async () => {
  const { data: resultData } = await useApi<any>(createUrl(`/to/cavi/view/${route.params.id}/rows`))

  loading.value = false
  serverItems.value = resultData.value
}

const onSubmit = async () => {
  loading.value = true
  let result: any
  if (editedItem.value.id === undefined) {
    result = await $api(`/to/cavi/${route.params.id}/stored`, {
      method: 'POST',
      body: editedItem.value,
    })
  } else {
    result = await $api(`/to/cavi/${route.params.id}/update/${editedItem.value.id}`, {
      method: 'POST',
      body: editedItem.value,
    })
  }
  isDialogVisible.value = false
  editedItem.value = {}
  snackbar.value = { show: true, message: result?.message ?? 'Salvato', color: result?.color ?? 'success' }
  await loadItems()
}

const deleteItem = (item: StrutturaCavo) => {
  deletedItem.value = { ...item }
  deleteDialog.value = true
}

const deleteItemConfirm = async () => {
  loading.value = true

  const { data: retuenData } = await $api(`/to/cavi/${route.params.id}/delete/${deletedItem.value.id}`, {
    method: 'delete',
    body: deletedItem.value,
  })

  loadItems()
  loading.value = false
  deleteDialog.value = false
}

const { data: centriData } = await useApi<any>(createUrl('/to/centri/get_list/'))
const { data: materialiData } = await useApi<any>(createUrl('/to/materiali/get_list/'))

const handleRowClick = (e: any, item: StrutturaCavo) => {
  //  @click:row="(e, item) => handleRowClick(e, item?.item)"
  // editedItem.value = { ...item }
  // isDialogVisible.value = true
}

const euro = new Intl.NumberFormat('it-IT', {
  minimumFractionDigits: 0,
  maximumFractionDigits: 4,
})

onMounted(() => {
  getCavo()
  centriOptions.value = centriData.value
  materialiOptions.value = materialiData.value
})
</script>

<template>
  <div class="workspace-container w-100 d-flex flex-column pa-4 gap-3">
    <VSnackbar v-model="snackbar.show" :color="snackbar.color" location="top center" :timeout="3000">
      {{ $t(snackbar.message) }}
    </VSnackbar>

    <template v-if="cavoData">
      <VRow class="ma-0">
        <!-- Bio Panel -->
        <VCol cols="12" md="4" lg="3" class="pa-0 pe-md-3">
          <CavoBioPanel :cavo-data="cavoData" />
        </VCol>

        <!-- Tabella Struttura -->
        <VCol cols="12" md="8" lg="9" class="pa-0 d-flex flex-column">
          <VCard variant="outlined" class="bg-surface border-thin rounded-lg d-flex flex-column">
            <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3 flex-shrink-0">
              <div class="d-flex align-center gap-2">
                <VIcon icon="tabler-layers-linked" size="24" color="primary" />
                <div>
                  <div class="text-h6 font-weight-medium">Struttura Cavo</div>
                  <div class="text-caption text-medium-emphasis">{{ serverItems.length }} elementi configurati</div>
                </div>
              </div>
              <VBtn
                v-if="can(DefineAbilities.cavi_create.action, DefineAbilities.cavi_create.subject)"
                prepend-icon="tabler-plus"
                color="primary"
                variant="flat"
                density="comfortable"
                class="px-3"
                @click="newItem"
              >
                Nuovo Elemento
              </VBtn>
            </VCardText>
            <VDivider />

            <!-- Avviso anomalie -->
            <VAlert
              v-if="righeConProblemi.length"
              type="warning"
              variant="tonal"
              density="compact"
              icon="tabler-alert-triangle"
              class="ma-3"
            >
              <div class="font-weight-bold mb-1">{{ righeConProblemi.length }} elemento/i con anomalie nell'anagrafica</div>
              <div v-for="row in righeConProblemi" :key="row.id" class="text-caption">
                <VIcon icon="tabler-point" size="10" class="me-1" />
                Pos. <strong>{{ row.posizione }}</strong>
                <template v-if="row.centro_missing"> · macchina <strong>{{ row.centro }}</strong> non trovata</template>
                <template v-if="row.materiale_missing"> · materiale <strong>{{ row.materiale }}</strong> non trovato</template>
              </div>
            </VAlert>

            <VDataTableServer
              :headers="headers"
              :items="serverItems"
              density="comfortable"
              hover
              style="min-height: 250px;"
            >
              <template #no-data>
                <div class="py-10 text-center">
                  <VIcon icon="tabler-layers-linked" size="40" class="text-disabled mb-2" />
                  <p class="text-body-1 text-disabled mb-0">Nessun elemento configurato</p>
                </div>
              </template>
              <template #item.posizione="{ item }">
                <span class="text-error font-weight-bold">{{ item.posizione }}</span>
              </template>
              <template #item.centro="{ item }">
                <VChip
                  v-if="item.centro && !item.centro_missing"
                  size="small" color="primary" variant="tonal" class="font-weight-medium"
                >
                  {{ item.centro }}
                </VChip>
                <VChip
                  v-else-if="item.centro && item.centro_missing"
                  size="small" color="error" variant="tonal" prepend-icon="tabler-alert-triangle"
                >
                  {{ item.centro }}
                </VChip>
                <span v-else class="text-disabled">-</span>
              </template>
              <template #item.materiale="{ item }">
                <VChip
                  v-if="item.materiale && !item.materiale_missing"
                  size="small"
                  :color="item.materiale.startsWith('RA') ? 'warning' : item.materiale.startsWith('FO') ? 'info' : 'secondary'"
                  variant="tonal"
                  class="font-weight-medium"
                >
                  {{ item.materiale }}
                </VChip>
                <VChip
                  v-else-if="item.materiale && item.materiale_missing"
                  size="small" color="error" variant="tonal" prepend-icon="tabler-alert-triangle"
                >
                  {{ item.materiale }}
                </VChip>
                <span v-else class="text-disabled">-</span>
              </template>
              <template #item.dimensioni="{ item }">
                <div class="d-flex flex-column align-end">
                  <span v-if="item.diametro > 0" class="text-caption font-weight-medium">
                    &phi; {{ euro.format(item.diametro) }} mm
                  </span>
                  <span v-if="item.peso > 0" class="text-caption text-disabled">
                    {{ euro.format(item.peso) }} kg
                  </span>
                </div>
              </template>
              <template #item.produzione="{ item }">
                <div class="d-flex flex-column gap-1">
                  <div v-if="Number(item.ordinata) > 0" class="d-flex align-center gap-1">
                    <VIcon icon="tabler-gauge" size="14" class="text-disabled" />
                    <span class="text-caption">{{ euro.format(item.ordinata) }}</span>
                  </div>
                  <div v-if="Number(item.elementi) > 0" class="d-flex align-center gap-1">
                    <VIcon icon="tabler-stack-2" size="14" class="text-disabled" />
                    <span class="text-caption">{{ euro.format(item.elementi) }} el</span>
                  </div>
                </div>
              </template>
              <template #item.nota="{ item }">
                <VTooltip v-if="item.nota" :text="item.nota" location="top">
                  <template #activator="{ props }">
                    <VIcon v-bind="props" icon="tabler-note" size="18" color="warning" />
                  </template>
                </VTooltip>
              </template>
              <template #bottom />
              <template #item.actions="{ item }">
                <div class="d-flex gap-1 justify-center">
                  <IconBtn
                    v-if="can(DefineAbilities.cavi_edit.action, DefineAbilities.cavi_edit.subject)"
                    color="primary"
                    size="small"
                    @click="editItem(item)"
                  >
                    <VIcon icon="tabler-edit" size="18" />
                  </IconBtn>
                  <IconBtn
                    v-if="can(DefineAbilities.cavi_edit.action, DefineAbilities.cavi_edit.subject)"
                    color="error"
                    size="small"
                    @click="deleteItem(item)"
                  >
                    <VIcon icon="tabler-trash" size="18" />
                  </IconBtn>
                </div>
              </template>
            </VDataTableServer>
          </VCard>
        </VCol>
      </VRow>
    </template>
    <VCard v-else class="text-center pa-6">
      <VIcon icon="tabler-alert-circle" size="48" class="text-disabled mb-3" />
      <VCardTitle class="text-h6 justify-center">
        Cavo non trovato o dati non disponibili
      </VCardTitle>
    </VCard>
  </div>

  <!-- Edit Dialog -->
  <VDialog v-model="isDialogVisible" max-width="1200">
    <DialogCloseBtn @click="isDialogVisible = !isDialogVisible" />
    <VCard>
      <VCardItem class="py-3">
        <template #prepend>
          <VAvatar :color="editedItem.id ? 'primary' : 'success'" variant="tonal" size="38">
            <VIcon :icon="editedItem.id ? 'tabler-edit' : 'tabler-plus'" size="20" />
          </VAvatar>
        </template>
        <VCardTitle>{{ editedItem.id ? 'Modifica Elemento' : 'Nuovo Elemento' }}</VCardTitle>
        <VCardSubtitle>Posizione {{ editedItem.posizione || '?' }} · struttura cavo {{ cavoData?.codice }}</VCardSubtitle>
      </VCardItem>
      <VDivider />

      <!-- Avviso anomalie anagrafica -->
      <VAlert
        v-if="editedItem.centro_missing || editedItem.materiale_missing"
        type="error"
        variant="tonal"
        density="compact"
        icon="tabler-alert-triangle"
        class="ma-3"
      >
        <div class="font-weight-bold mb-1">Anomalie rilevate — aggiornare i dati prima di salvare</div>
        <div v-if="editedItem.centro_missing" class="text-caption">
          <VIcon icon="tabler-point" size="10" class="me-1" />
          Macchina <strong>{{ editedItem.centro }}</strong> non trovata nell'anagrafica centri
        </div>
        <div v-if="editedItem.materiale_missing" class="text-caption">
          <VIcon icon="tabler-point" size="10" class="me-1" />
          Materiale <strong>{{ editedItem.materiale }}</strong> non trovato nell'anagrafica materiali
        </div>
      </VAlert>

      <!-- Sezione 1: Identificazione -->
      <VCardText class="pb-2 pt-4">
        <div class="dialog-section-label text-primary">
          <VIcon icon="tabler-fingerprint" size="16" />
          <span>Identificazione</span>
        </div>
        <VRow class="mt-2">
          <VCol cols="4" sm="2" md="1">
            <AppTextField v-model="editedItem.posizione" type="number" label="Pos." min="1" />
          </VCol>
          <VCol cols="12" sm="5" md="4">
            <AppAutocomplete
              v-model="editedItem.centro"
              :label="$t('Label.Centro')"
              :items="centriOptions"
              :item-title="item => item.centro"
              :item-value="item => item.centro"
              clearable clear-icon="tabler-x"
            />
          </VCol>
          <VCol cols="12" sm="5" md="4">
            <AppAutocomplete
              v-model="editedItem.materiale"
              :label="$t('Label.Materiale')"
              :items="materialiOptions"
              :item-title="item => item.materiale"
              :item-value="item => item.materiale"
              clearable clear-icon="tabler-x"
            />
          </VCol>
          <VCol cols="12" md="3">
            <AppTextField v-model="editedItem.descrizione" :label="$t('Label.Descrizione')" />
          </VCol>
        </VRow>
      </VCardText>

      <!-- Sezione 2: Dati Materiale -->
      <template v-if="hasMateriale">
        <VDivider />
        <VCardText class="pb-2 pt-3">
          <div class="dialog-section-label text-success">
            <VIcon icon="tabler-ruler-2" size="16" />
            <span>Dati Materiale</span>
            <VChip size="x-small" color="success" variant="tonal" class="ms-2">{{ editedItem.materiale }}</VChip>
          </div>
          <VRow class="mt-2">
            <VCol cols="6" sm="3" md="2">
              <AppTextField v-model="editedItem.diametro" type="number" label="Diametro" suffix="mm" min="0" />
            </VCol>
            <VCol cols="6" sm="3" md="2">
              <AppTextField v-model="editedItem.peso" type="number" label="Peso" suffix="kg/km" min="0" />
            </VCol>
          </VRow>
        </VCardText>
      </template>

      <!-- Sezione 3: Dati Produzione -->
      <template v-if="hasCentro">
        <VDivider />
        <VCardText class="pb-2 pt-3">
          <div class="dialog-section-label text-warning">
            <VIcon icon="tabler-settings" size="16" />
            <span>Dati Produzione</span>
            <VChip size="x-small" color="warning" variant="tonal" class="ms-2">{{ editedItem.centro }}</VChip>
          </div>
          <VRow class="mt-2">
            <VCol cols="6" sm="3" md="2">
              <AppTextField v-model="editedItem.ordinata" type="number" :label="$t('Label.Produzione-Oraria')" min="0" />
            </VCol>
            <VCol cols="6" sm="3" md="2">
              <AppTextField v-model="editedItem.elementi" type="number" :label="$t('Label.Numero-Elementi')" min="0" />
            </VCol>
          </VRow>
        </VCardText>
      </template>

      <!-- Sezione 4: Note -->
      <VDivider />
      <VCardText class="pb-2 pt-3">
        <div class="dialog-section-label text-info">
          <VIcon icon="tabler-note" size="16" />
          <span>Note</span>
        </div>
        <VRow class="mt-2">
          <VCol cols="12">
            <AppTextField v-model="editedItem.nota" :label="$t('Label.Nota')" placeholder="Inserisci eventuali note..." />
          </VCol>
        </VRow>
      </VCardText>

      <VDivider />
      <VCardText class="d-flex justify-end gap-3 py-3">
        <VBtn variant="tonal" color="secondary" prepend-icon="tabler-x" @click="isDialogVisible = false">Annulla</VBtn>
        <VBtn color="primary" prepend-icon="tabler-device-floppy" :loading="loading" @click="onSubmit">Salva</VBtn>
      </VCardText>
    </VCard>
  </VDialog>

  <!-- Delete Dialog -->
  <VDialog v-model="deleteDialog" max-width="440">
    <VCard>
      <VCardItem class="py-4">
        <template #prepend>
          <VAvatar color="error" variant="tonal" size="38">
            <VIcon icon="tabler-trash" size="20" />
          </VAvatar>
        </template>
        <VCardTitle>Eliminazione Elemento</VCardTitle>
        <VCardSubtitle>Questa azione non è reversibile</VCardSubtitle>
      </VCardItem>
      <VDivider />
      <VCardText class="py-4">
        <p class="text-body-2">Sei sicuro di voler eliminare l'elemento in posizione <strong>{{ deletedItem.posizione }}</strong>?</p>
      </VCardText>
      <VDivider />
      <VCardText class="d-flex justify-end gap-3 py-3">
        <VBtn variant="tonal" color="secondary" @click="deleteDialog = false">Annulla</VBtn>
        <VBtn color="error" prepend-icon="tabler-trash" :loading="loading" @click="deleteItemConfirm">Elimina</VBtn>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<style scoped lang="scss">
.dialog-section-label {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}
</style>
