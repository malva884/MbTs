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

    editedItem.value.descrizione = cavo.descrizione
    editedItem.value.diametro = resultData.value
  }else{
    editedItem.value.diametro = null
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

      nextTick(() => {
        refForm.value?.reset()
        refForm.value?.resetValidation()
      })
      await loadItems();
      isDialogVisible.value = false

      if (retuenData.checkMateriale === true || retuenData.checkCentro === true) {
        message.value = 'Attenzione! Centri o Materiali '
        color.value = 'error'
      }else{
        message.value = retuenData.message
        color.value = retuenData.color
      }
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
  <VSnackbar
    v-model="isSnackbarScrollReverseVisible"
    transition="scroll-y-reverse-transition"
    location="top central"
    :color="color"
  >
    {{ $t(message) }}
  </VSnackbar>
  <VRow v-if="preventivoData">
    <VCol
      cols="12"
      md="5"
      lg="3"
    >
      <VRow>
        <VCol
          cols="12"
          md="12"
          lg="12"
        >
          <PreventivoBioPanel :preventivo-data="preventivoData" />
        </VCol>
        <VCol
          cols="12"
          md="12"
          lg="12"
        >
          <VCard class="text-center">
            <VBtn
              rounded="pill"
              color="success"
              class="mt-3 mb-3"
              @click="get_fv"
            >
              Foglio Verde
            </VBtn>
            <VBtn
              rounded="pill"
              color="info"
              class="mt-3 mb-3 ml-4"
              @click="stampa"
            >
              Stampa
            </VBtn>
          </VCard>
        </VCol>
        <VCol
          cols="12"
          md="12"
          lg="12"
        >

        </VCol>
      </VRow>

    </VCol>

    <VCol
      cols="12"
      md="7"
      lg="9"
    >
      <VCard>
        <VCardText class="d-flex flex-wrap py-4 gap-4">
          <div class="app-user-search-filter d-flex align-center flex-wrap gap-4">
            <!-- 👉 Add user button -->
            <VBtn
              v-if="can(DefineAbilities.preventivi_create.action, DefineAbilities.preventivi_create.subject)"
              prepend-icon="tabler-plus"
              color="primary"
              @click="newItem"
            >
              {{ $t('Button.Aggiungi-Cavo') }}
            </VBtn>
          </div>
        </VCardText>
        <VDataTableServer
          v-model="selectedRows"
          :headers="headers"
          :items="serverItems"
          show-select
        >
          <template #item.posizione="{ item }">
            <p class="text-error">
              {{ item.posizione }}
            </p>
          </template>
          <template #item.codice="{ item }">
            <div class="d-flex align-center">
              <div class="d-flex flex-column">
                <h6 class="text-base">
                  <RouterLink
                    :to="{ name: 'offices-technical-quote-cable-view-id', params: { id: item.id } }"
                    class="font-weight-medium text-link"
                  >
                    {{ item.codice }}
                  </RouterLink>
                </h6>
              </div>
            </div>
          </template>
          <template #item.costo="{ item }">
            <p class="text-success">
              {{ euro.format(item.costo) }}
            </p>
          </template>
          <template #item.parametro="{ item }">
            <p class="text-success">
              {{ euro.format(item.parametro) }}
            </p>
          </template>
          <template #item.costo_materiali="{ item }">
            <p class="text-success">
              {{ euro.format(item.costo * item.metri) }}
            </p>
          </template>
          <template #bottom />
          <template #item.actions="{ item }">
            <div class="d-flex gap-1">
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
    </VCol>
  </VRow>
  <VCard v-else>
    <VCardTitle class="text-center">
      No User Found
    </VCardTitle>
  </VCard>

  <!-- 👉 New Edit Dialog  -->
  <VDialog
    v-model="isDialogVisible"
    max-width="800"
  >
    <!-- Dialog close btn -->
    <DialogCloseBtn @click="isDialogVisible = !isDialogVisible" />

    <!-- Dialog Content -->
    <VCard :title="editedItem.id ? `${$t('Label.Modifica')} Cavo` : `${$t('Label.Nuovo')} Cavo`">
      <VForm
        ref="refForm"
        @submit.prevent="onSubmit"
      >
        <VCardText>
          <VRow>
            <VCol
              cols="12"

            >
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
            <VCol
              cols="12"
              sm="12"
              md="12"
            >
              <AppTextField
                v-model="editedItem.descrizione"
                :rules="[requiredValidator]"
                :label="$t('Label.Descrizione')"
                :placeholder="$t('Label.Descrizione')"
                @focusin="get_bobina"
              />
            </VCol>
            <VCol
              cols="12"
              sm="6"
              md="6"
            >
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
            <VCol
              cols="12"
              sm="6"
              md="6"
            >
              <AppTextField
                v-model="editedItem.scarto"
                :rules="[requiredValidator]"
                type="number"
                :label="$t('Label.Scarto')"
                :placeholder="$t('Label.Scarto')"
                min="0"
              />
            </VCol>
            <VCol
              cols="12"
              sm="6"
              md="6"
            >
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
            <VCol
              cols="12"
              sm="6"
              md="6"
            >
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
            <VCol
              cols="12"

            >
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
            <VCol
              cols="12"
              sm="6"
              md="6"
            >
              <AppTextField
                v-model="editedItem.bobina.peso"
                :label="$t('Label.Peso')"
                :placeholder="$t('Label.Peso')"
                readonly="true"
              />
            </VCol>
            <VCol
              cols="12"
              sm="6"
              md="6"
            >
              <AppTextField
                v-model="editedItem.bobina.m3"
                :label="$t('Label.M3')"
                :placeholder="$t('Label.M3')"
                readonly="true"
              />
            </VCol>
            <VCol
              cols="12"
              sm="6"
              md="6"
            >
              <AppTextField
                v-model="editedItem.bobina.costo"
                :label="$t('Label.Costo-Bobina')"
                :placeholder="$t('Label.Costo-Bobina')"
                readonly="true"
              />
            </VCol>
            <VCol
              cols="12"
              sm="6"
              md="6"
            >
              <AppTextField
                v-model="editedItem.posizione"
                :rules="[requiredValidator]"
                type="number"
                :label="$t('Label.Posizione')"
                :placeholder="$t('Label.Posizione')"
              />
            </VCol>
            <VCol
              cols="12"
            >
              <AppTextField
                v-model="editedItem.nota"
                type="number"
                :label="$t('Label.Nota')"
                :placeholder="$t('Label.Nota')"
              />
            </VCol>
          </VRow>
        </VCardText>
        <VCardActions class="mt-6">
          <VSpacer />

          <VBtn
            type="reset"
            color="error"
            variant="outlined"
            @click="isDialogVisible = !isDialogVisible"
          >
            Cancel
          </VBtn>

          <VBtn
            type="submit"
            @click="refForm?.validate()"
          >
            Save
          </VBtn>
        </VCardActions>
      </VForm>

    </VCard>
  </VDialog>

  <!-- 👉 Delete Dialog  -->
  <VDialog
    v-model="deleteDialog"
    max-width="500px"
  >
    <VCard>
      <VCardTitle>
        {{ $t('Messaggi.Eliminazione-Item') }}
      </VCardTitle>

      <VCardActions>
        <VSpacer />

        <VBtn
          color="error"
          variant="outlined"
          @click="deleteDialog = false"
        >
          Cancel
        </VBtn>

        <VBtn
          color="success"
          variant="elevated"
          @click="deleteItemConfirm"
        >
          OK
        </VBtn>

        <VSpacer />
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<style scoped lang="scss">

</style>
