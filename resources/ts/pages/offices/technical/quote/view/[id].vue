<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import type { VForm } from 'vuetify/components/VForm'
import { CavoPreventivo, Preventivo, Bobina } from '@/views/offices/technical/quote/type'
import { can } from '@layouts/plugins/casl'
import DefineAbilities from '@/plugins/casl/DefineAbilities'
import PreventivoBioPanel from '@/views/offices/technical/quote/view/preventivoBioPanel.vue'

definePage({
  meta: {
    action: 'list',
    subject: 'Preventivi',
  },
})

const router = useRouter()
const route = useRoute('offices-technical-quote-view-id')

const { t } = useI18n()
const refForm = ref<VForm>()
const preventivoData = ref<Preventivo>([])
const loading = ref(true)
const serverItems = ref<[]>([])
const bobineOptions = ref([])
const caviOptions = ref([])
const deletedItem = ref({})
const diametro = ref()
const isSnackbarScrollReverseVisible = ref(false)
const message = ref('')
const color = ref('')

const isDialogVisible = ref(false)
const deleteDialog = ref(false)

const defaultBobina = ref<Bobina>({
  id: null,
  bobina: '',
  capacita: null,
  m3: null,
  codice_as: '',
  costo: null,
  costo_medio: '',
  peso: '',
  dimensioni: '',
  lettera: '',
})

const defaultItem = ref<CavoPreventivo>({
  id: '',
  preventivo: '',
  codice: null,
  descrizione: '',
  metri: null,
  scarto: null,
  diametro: null,
  pezzatura: null,
  bobina: [
    {
      id: null,
      bobina: '',
      capacita: null,
      m3: null,
      codice_as: '',
      costo: null,
      costo_medio: '',
      peso: '',
      dimensioni: '',
      lettera: '',
    },
  ],
  posizione: null,
  mota: '',
})



const editedItem = ref(<CavoPreventivo>{})
const selectedRows = ref<string[]>([])
const searchCavo = ref('')

const filteredItems = computed(() => {
  if (!searchCavo.value) return serverItems.value
  const q = searchCavo.value.toLowerCase()
  return serverItems.value.filter((item: CavoPreventivo) =>
    (item.codice || '').toLowerCase().includes(q) ||
    (item.descrizione || '').toLowerCase().includes(q)
  )
})

const filterCavi = () => {
  // La ricerca è gestita via computed, questa funzione può essere usata per estendere in futuro
}

// headers
const headers = [
  { title: t('Label.Posizione'), key: 'posizione', sortable: false },
  { title: t('Label.Metri'), key: 'metri', sortable: false },
  { title: t('Table.Codice-Cavo'), key: 'codice', sortable: false },
  { title: t('Table.Descrizione'), key: 'descrizione', sortable: false },
  { title: t('Label.Costo'), key: 'costo', sortable: false },
  { title: t('Label.Param'), key: 'parametro', sortable: false },
  { title: t('Label.Totale-Posizione'), key: 'costo_materiali', sortable: false },
  { title: 'ACTIONS', key: 'actions', sortable: false },
]

const newItem = () => {
  editedItem.value = { ...defaultItem.value }
  editedItem.value.preventivo = preventivoData.value
  let posizione = Object.keys(serverItems.value).length
  editedItem.value.posizione = posizione + 1
  isDialogVisible.value = true
}

const editItem = (item: CavoPreventivo) => {
  // editedIndex.value = serverItems.value.indexOf(item)
  editedItem.value = { ...item }
  const cavo = caviData.value.find(t=>t.codice == editedItem.value.codice)
  editedItem.value.codice = cavo.id

  if(cavo.id === undefined || editedItem.value.bobina_id === null){
    message.value = 'Messagge.Errore-Cavo'
    color.value = 'error'
    isSnackbarScrollReverseVisible.value = true
  }
  else{
    editedItem.value.bobina = editedItem.value.bobina_id
    isDialogVisible.value = true
  }
}

const getPreventivo = async () => {
  const { data: resultData } = await useApi<any>(createUrl(`/to/preventivi/view/${route.params.id}`))

  preventivoData.value = resultData.value

  await loadItems()
}

const loadItems = async () => {
  const { data: resultData } = await useApi<any>(createUrl(`/to/preventivi/${route.params.id}/list`))

  loading.value = false
  serverItems.value = resultData.value.data
}

const loadDiametro = async () => {
  if(editedItem.value.codice != undefined){
    const { data: resultData } = await useApi<any>(createUrl(`/to/cavi/get_diametro/${editedItem.value.codice}`))

    const cavo = caviData.value.find(t=>t.id == editedItem.value.codice)

    editedItem.value.descrizione = cavo?.descrizione || ''
    const d = Number(resultData.value?.diametro ?? resultData.value)
    const p = Number(resultData.value?.pezzatura ?? 10000)
    editedItem.value.diametro = isNaN(d) ? 0 : d
    editedItem.value.pezzatura = isNaN(p) ? 10000 : p
  }else{
    editedItem.value.diametro = 0
    editedItem.value.pezzatura = 10000
    editedItem.value.bobina = []
  }
}

const get_bobina = async () => {
  if(editedItem.value.codice != undefined && editedItem.value.metri != undefined && editedItem.value.diametro != undefined && editedItem.value.pezzatura != undefined){
    const { data: resultData } = await useApi<any>(createUrl(`/to/bobine/get_bobina`, {
      query: {
        diametro: editedItem.value.diametro,
        pezzatura: editedItem.value.pezzatura,
      },
    }))

    editedItem.value.bobina = resultData.value
  }
}

const onSubmit = async () => {
  refForm.value?.validate().then(async ({valid}) => {
    if (valid) {
      let path = `/to/preventivi/${route.params.id}/stored`
      if(editedItem.value.id !== undefined && editedItem.value.id !== '')
        path = `/to/preventivi/${route.params.id}/update/${editedItem.value.id}`

      const retuenData = await $api(path, {
        method: 'POST',
        body: editedItem.value,
      })

      if (retuenData.success === false) {
        message.value = retuenData.details || retuenData.message || 'Errore durante il salvataggio'
        color.value = retuenData.color || 'error'
        isSnackbarScrollReverseVisible.value = true
        return
      }

      nextTick(() => {
        refForm.value?.reset()
        refForm.value?.resetValidation()
      })
      await loadItems();
      isDialogVisible.value = false
      message.value = retuenData.message
      color.value = retuenData.color
      isSnackbarScrollReverseVisible.value = true
    }
  })
}

const get_fv = async () => {
  const retuenData = await $api(`/to/preventivi/export/fv/${route.params.id}`, {
    method: 'POST',
  })

  const blob = retuenData//new Blob([retuenData.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' })
  const link = document.createElement('a')
  link.href = URL.createObjectURL(blob)
  link.download = 'proventivo.xlsx'
  link.click()
  URL.revokeObjectURL(link.href)
}

const deleteItem = (item: CavoPreventivo) => {
  deletedItem.value = { ...item }
  deleteDialog.value = true
}

const deleteItemConfirm = async () => {
  loading.value = true

  const { data: retuenData } = await $api(`/to/preventivi/${route.params.id}/cable/delete/${deletedItem.value.id}`, {
    method: 'delete',
    body: deletedItem.value,
  })

  loadItems()
  loading.value = false
  deleteDialog.value = false
}

const { data: bobineData } = await useApi<any>(createUrl('/to/bobine/get_list/'))
const { data: caviData } = await useApi<any>(createUrl('/to/cavi/get_list/'))

const handleRowClick = (e: any, item: CavoPreventivo) => {
  //  @click:row="(e, item) => handleRowClick(e, item?.item)"
  // editedItem.value = { ...item }
  // isDialogVisible.value = true
}

const euro = new Intl.NumberFormat('it-IT', {
  maximumSignificantDigits: 8,
})

const numFormat = (val: number | string | null, digits = 2) => {
  if (val === null || val === undefined || val === '') return ''
  const n = Number(val)
  if (isNaN(n)) return String(val)
  return new Intl.NumberFormat('it-IT', { minimumFractionDigits: digits, maximumFractionDigits: digits }).format(n)
}

const stampa = async () => {

  //const retuenData = router.resolve({ path: '/offices/technical/quote/print/print', query: { ids: selectedRows.value } })
  //window.open(printRedirect.href, '_blank');
  const retuenData = await $api(`/to/preventivi/cable/`, {
    method: 'POST',
    query: {
      ids: JSON.stringify(selectedRows.value)}
  })
  const blob = retuenData//new Blob([retuenData.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' })
  const link = document.createElement('a')
  link.href = URL.createObjectURL(blob)
  link.download = 'stampa.xlsx'
  link.click()
  URL.revokeObjectURL(link.href)
}

onMounted(() => {
  getPreventivo()
  bobineOptions.value = bobineData.value
  caviOptions.value = caviData.value
})
</script>

<template>
  <div class="workspace-container w-100 h-100 d-flex flex-column pa-4 overflow-hidden">
    <VSnackbar
      v-model="isSnackbarScrollReverseVisible"
      transition="scroll-y-reverse-transition"
      location="top center"
      :color="color"
    >
      {{ $t(message) }}
    </VSnackbar>

    <template v-if="preventivoData">
      <!-- Header Preventivo -->
      <VCard class="mb-3 flex-shrink-0">
        <VCardText>
          <VRow class="align-center">
            <VCol cols="12" md="6">
              <div class="d-flex align-center gap-4">
                <VAvatar size="64" rounded variant="tonal" color="primary">
                  <VImg :src="path+'images/custom/preventivo.png'" />
                </VAvatar>
                <div>
                  <h5 class="text-h5 font-weight-bold">{{ preventivoData.numero }}</h5>
                  <p class="text-body-2 text-medium-emphasis mb-0">
                    {{ preventivoData.cliente_obj?.ragione_sociale }}
                    <span class="mx-2">|</span>
                    RDO: {{ preventivoData.rdo }}
                  </p>
                </div>
              </div>
            </VCol>
            <VCol cols="12" md="3">
              <div class="d-flex flex-column text-md-right">
                <span class="text-caption text-medium-emphasis">Data Preventivo</span>
                <span class="text-body-1 font-weight-medium">{{ formatDate(preventivoData.data_preventivo) }}</span>
                <span class="text-caption text-medium-emphasis mt-1">Base Cu: {{ preventivoData.cu }}</span>
              </div>
            </VCol>
            <VCol cols="12" md="3" class="text-md-right">
              <VBtn rounded="pill" color="success" size="small" class="me-2" @click="get_fv">
                Foglio Verde
              </VBtn>
              <VBtn rounded="pill" color="info" size="small" @click="stampa">
                Stampa
              </VBtn>
            </VCol>
          </VRow>
        </VCardText>
      </VCard>

      <!-- Card Tabella Cavi -->
      <VCard variant="outlined" class="bg-surface border-thin rounded-lg d-flex flex-column flex-grow-1 overflow-hidden">
        <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3 flex-shrink-0">
          <VBtn
            v-if="can(DefineAbilities.preventivi_create.action, DefineAbilities.preventivi_create.subject)"
            prepend-icon="tabler-plus"
            color="primary"
            variant="flat"
            density="comfortable"
            class="px-3"
            @click="newItem"
          >
            {{ $t('Button.Aggiungi-Cavo') }}
          </VBtn>
          <AppTextField
            v-model="searchCavo"
            placeholder="Cerca cavo..."
            prepend-inner-icon="tabler-search"
            single-line
            hide-details
            density="compact"
            clearable
            clear-icon="tabler-x"
            style="max-width: 280px;"
            @keyup.enter="filterCavi"
            @click:clear="searchCavo = ''; filterCavi()"
          />
        </VCardText>
        <VDivider />
        <VDataTableServer
          v-model="selectedRows"
          :headers="headers"
          :items="filteredItems"
          :loading="loading"
          show-select
          density="comfortable"
          class="flex-grow-1"
          style="min-height: 300px;"
        >
          <template #no-data>
            <div class="py-10 text-center">
              <VIcon icon="tabler-jump-rope" size="40" class="text-disabled mb-2" />
              <p class="text-body-1 text-disabled mb-0">Nessun cavo nel preventivo</p>
            </div>
          </template>
          <template #item.posizione="{ item }">
            <VChip size="small" color="error" variant="flat" class="font-weight-bold">
              {{ item.posizione }}
            </VChip>
          </template>
          <template #item.codice="{ item }">
            <VChip size="small" color="primary" variant="tonal" class="font-weight-bold font-monospace cursor-pointer">
              <RouterLink
                :to="{ name: 'offices-technical-quote-cable-view-id', params: { id: item.id } }"
                class="text-white text-decoration-none"
              >
                {{ item.codice }}
              </RouterLink>
            </VChip>
          </template>
          <template #item.descrizione="{ item }">
            <span class="text-body-2 text-medium-emphasis">{{ item.descrizione }}</span>
          </template>
          <template #item.metri="{ item }">
            <span class="text-body-1 font-weight-medium">{{ item.metri }}</span>
          </template>
          <template #item.costo="{ item }">
            <VChip size="small" color="success" variant="tonal" class="font-weight-bold">
              {{ euro.format(item.costo) }}
            </VChip>
          </template>
          <template #item.parametro="{ item }">
            <span class="text-body-2 text-success">{{ euro.format(item.parametro) }}</span>
          </template>
          <template #item.costo_materiali="{ item }">
            <span class="text-body-2 text-success">{{ euro.format(item.costo * item.metri) }}</span>
          </template>
          <template #bottom />
          <template #item.actions="{ item }">
            <div class="d-flex gap-1 justify-center">
              <IconBtn
                v-if="can(DefineAbilities.preventivi_edit.action, DefineAbilities.preventivi_edit.subject)"
                color="primary"
                @click="editItem(item)"
              >
                <VIcon icon="tabler-edit" />
              </IconBtn>
              <IconBtn
                v-if="can(DefineAbilities.preventivi_edit.action, DefineAbilities.preventivi_edit.subject)"
                color="error"
                @click="deleteItem(item)"
              >
                <VIcon icon="tabler-trash" />
              </IconBtn>
            </div>
          </template>
        </VDataTableServer>
      </VCard>
    </template>
    <VCard v-else class="text-center pa-6">
      <VIcon icon="tabler-file-invoice" size="48" class="text-disabled mb-3" />
      <VCardTitle class="text-h6 justify-center">
        Preventivo non trovato
      </VCardTitle>
    </VCard>
  </div>

  <!-- 👉 New Edit Dialog  -->
  <VDialog v-model="isDialogVisible" persistent max-width="800">
    <VCard>
      <VCardTitle class="text-h5 pa-4 pb-2 d-flex align-center gap-2">
        <VIcon icon="tabler-jump-rope" size="24" />
        {{ editedItem.id ? `${$t('Label.Modifica')} Cavo` : `${$t('Label.Nuovo')} Cavo` }}
      </VCardTitle>
      <VDivider />
      <VCardText class="pa-4">
        <VForm ref="refForm" @submit.prevent="onSubmit">
          <VRow dense>
            <VCol cols="12">
              <AppAutocomplete
                v-model="editedItem.codice"
                :rules="[requiredValidator]"
                :label="$t('Label.Codice-Cavo')"
                :placeholder="$t('Label.Codice-Cavo')"
                :items="caviOptions"
                :item-title="item => item.codice+' ( '+item.categoria+' )'"
                :item-value="item => item.id"
                clearable
                clear-icon="tabler-x"
                @focusout="loadDiametro"
                :readonly="!!editedItem.id"
              />
            </VCol>
            <VCol cols="12">
              <AppTextField
                v-model="editedItem.descrizione"
                :rules="[requiredValidator]"
                :label="$t('Label.Descrizione')"
                :placeholder="$t('Label.Descrizione')"
                @focusin="get_bobina"
              />
            </VCol>
            <VCol cols="12" sm="6">
              <AppTextField
                v-model="editedItem.metri"
                :rules="[requiredValidator]"
                type="number"
                :label="$t('Label.Metri')"
                :placeholder="$t('Label.Metri')"
                min="1"
                @focusout="get_bobina"
              />
            </VCol>
            <VCol cols="12" sm="6">
              <AppTextField
                v-model="editedItem.scarto"
                :rules="[requiredValidator]"
                type="number"
                :label="$t('Label.Scarto')"
                :placeholder="$t('Label.Scarto')"
                min="0"
              />
            </VCol>
            <VCol cols="12" sm="6">
              <AppTextField
                v-model="editedItem.diametro"
                :rules="[requiredValidator]"
                type="number"
                :label="$t('Label.Diametro')"
                :placeholder="$t('Label.Diametro')"
                min="0"
                @focusout="get_bobina"
              />
            </VCol>
            <VCol cols="12" sm="6">
              <AppTextField
                v-model="editedItem.pezzatura"
                :rules="[requiredValidator]"
                type="number"
                :label="$t('Label.Pezzatura')"
                :placeholder="$t('Label.Pezzatura')"
                min="0"
                @focusout="get_bobina"
              />
            </VCol>
            <VCol cols="12">
              <AppSelect
                v-model="editedItem.bobina.bobina"
                :rules="[requiredValidator]"
                :label="$t('Label.Bobina')"
                :placeholder="$t('Label.Bobina')"
                :items="bobineOptions"
                :item-title="item => item.bobina+' - '+item.lettera"
                :item-value="item => item.bobina"
                clearable
                clear-icon="tabler-x"
              />
            </VCol>
            <VCol cols="12" sm="6">
              <AppTextField
                :model-value="numFormat(editedItem.bobina?.peso, 2)"
                :label="$t('Label.Peso')"
                :placeholder="$t('Label.Peso')"
                readonly
              />
            </VCol>
            <VCol cols="12" sm="6">
              <AppTextField
                :model-value="numFormat(editedItem.bobina?.m3, 3)"
                :label="$t('Label.M3')"
                :placeholder="$t('Label.M3')"
                readonly
              />
            </VCol>
            <VCol cols="12" sm="6">
              <AppTextField
                :model-value="numFormat(editedItem.bobina?.costo, 2)"
                :label="$t('Label.Costo-Bobina')"
                :placeholder="$t('Label.Costo-Bobina')"
                readonly
              />
            </VCol>
            <VCol cols="12" sm="6">
              <AppTextField
                v-model="editedItem.posizione"
                :rules="[requiredValidator]"
                type="number"
                :label="$t('Label.Posizione')"
                :placeholder="$t('Label.Posizione')"
              />
            </VCol>
            <VCol cols="12">
              <AppTextField
                v-model="editedItem.nota"
                :label="$t('Label.Nota')"
                :placeholder="$t('Label.Nota')"
              />
            </VCol>
          </VRow>
        </VForm>
      </VCardText>
      <VDivider />
      <VCardActions class="pa-4">
        <VSpacer />
        <VBtn color="error" variant="tonal" @click="isDialogVisible = false">
          Annulla
        </VBtn>
        <VBtn color="primary" variant="flat" @click="refForm?.validate().then(({ valid }) => { if (valid) onSubmit() })">
          Salva
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <!-- 👉 Delete Dialog  -->
  <VDialog v-model="deleteDialog" max-width="400">
    <VCard class="text-center pa-4">
      <VIcon icon="tabler-alert-triangle" size="48" color="error" class="mb-3" />
      <VCardTitle class="text-h6 justify-center">
        Conferma Eliminazione
      </VCardTitle>
      <VCardText class="text-body-1">
        Sei sicuro di voler eliminare questo cavo?<br>
        <span class="text-caption text-medium-emphasis">Questa azione non può essere annullata.</span>
      </VCardText>
      <VCardActions class="justify-center gap-2">
        <VBtn color="secondary" variant="tonal" @click="deleteDialog = false">
          Annulla
        </VBtn>
        <VBtn color="error" variant="flat" @click="deleteItemConfirm">
          Elimina
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<style scoped lang="scss">

</style>
