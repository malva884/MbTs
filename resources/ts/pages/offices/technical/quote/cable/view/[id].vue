<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import type { VForm } from 'vuetify/components/VForm'
import CavoBioPanel from '@/views/offices/technical/cables/view/cavoBioPanel.vue'
import { can } from '@layouts/plugins/casl'
import DefineAbilities from '@/plugins/casl/DefineAbilities'
import PreventivoBioPanel from '@/views/offices/technical/quote/view/preventivoBioPanel.vue'
import type { CavoPreventivo, Preventivo, StrutturaCavoPreventivo } from '@/views/offices/technical/quote/type'
import TotaliPreventivoBioPanel from '@/views/offices/technical/quote/view/totaliPreventivoBioPanel.vue'

definePage({
  meta: {
    action: 'list',
    subject: 'Preventivi',
  },
})

const route = useRoute('offices-technical-quote-cable-view-id')

const { t } = useI18n()
const refForm = ref<VForm>()
const preventivoData = ref<Preventivo>()
const cavoData = ref<CavoPreventivo>()
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

// headers
const headers = [
  { title: t('Table.Posizione'), key: 'posizione', sortable: false },
  { title: t('Table.Macchina'), key: 'centro', sortable: false },
  { title: t('Table.Materiale'), key: 'materiale', sortable: false },
  { title: t('Table.Descrizione'), key: 'descrizione', sortable: false },
  { title: t('Table.Diametro'), key: 'diametro', sortable: false },
  { title: t('Table.Peso'), key: 'peso', sortable: false },
  { title: t('Table.Costo'), key: 'costo', sortable: false },
  { title: t('Table.Costo-Materia-Prima'), key: 'costo_materia_prima', sortable: false },
  { title: t('Table.Costo-Macchina'), key: 'costo_centro', sortable: false },
  { title: t('Table.Produzione-Oraria'), key: 'ordinata', sortable: false },
  { title: t('Table.Numero-Elementi'), key: 'elementi', sortable: false },
  { title: t('Table.Costo-Lavorazione'), key: 'costo_lavorazione', sortable: false },
  { title: t('Table.Ore-Macchina'), key: 'ore_macchina', sortable: false },
  { title: t('Table.Nota'), key: 'nota', sortable: false },
  { title: 'ACTIONS', key: 'actions', sortable: false },
]

const newItem = () => {
  editedItem.value = { ...defaultItem.value }
  isDialogVisible.value = true
}

const editItem = (item: StrutturaCavoPreventivo) => {
  // editedIndex.value = serverItems.value.indexOf(item)
  editedItem.value = { ...item }
  isDialogVisible.value = true
}

const getCavo = async () => {
  const { data: resultData } = await useApi<any>(createUrl(`/to/preventivi/cable/view/${route.params.id}`))

  preventivoData.value = resultData.value.preventivo_obj
  cavoData.value = resultData.value
  await loadItems()
}

const loadItems = async () => {
  const { data: resultData } = await useApi<any>(createUrl(`/to/preventivi/cable/view/${route.params.id}/rows`))

  loading.value = false
  serverItems.value = resultData.value
}

const onSubmit = async () => {
  console.log(editedItem.value)
  if (editedItem.value.id === undefined) {
    const { data: retuenData } = await $api(`/to/preventivi/${route.params.id}/cable/new/${cavoData.value.id}/row`, {
      method: 'POST',
      body: editedItem.value,
    })
  }
  else {
    const { data: retuenData } = await $api(`/to/preventivi/${route.params.id}/cable/update/${cavoData.value.id}/row/${editedItem.value.id}`, {
      method: 'POST',
      body: editedItem.value,
    })
  }

  isDialogVisible.value = false
  editedItem.value = []
  loading.value = true
  loadItems()
  loading.value = false
}

const deleteItem = (item: StrutturaCavoPreventivo) => {
  deletedItem.value = { ...item }
  deleteDialog.value = true
}

const deleteItemConfirm = async () => {
  loading.value = true

  const { data: retuenData } = await $api(`/to/preventivi/${route.params.id}/cable/delete/${cavoData.value.id}/row/${editedItem.value.id}`, {
    method: 'delete',
    body: deletedItem.value,
  })

  loadItems()
  loading.value = false
  deleteDialog.value = false
}

const { data: centriData } = await useApi<any>(createUrl('/to/centri/get_list/'))
const { data: materialiData } = await useApi<any>(createUrl('/to/materiali/get_list/'))

const handleRowClick = (e: any, item: StrutturaCavoPreventivo) => {
  //  @click:row="(e, item) => handleRowClick(e, item?.item)"
  // editedItem.value = { ...item }
  // isDialogVisible.value = true
}

const euro = new Intl.NumberFormat('it-IT', {
  maximumSignificantDigits: 4,
})

onMounted(() => {
  getCavo()
  centriOptions.value = centriData.value
  materialiOptions.value = materialiData.value
})
</script>

<template>
  <VRow v-if="preventivoData">
    <VCol
      cols="12"
      md="5"
      lg="3"
    >
      <PreventivoBioPanel :preventivo-data="preventivoData" />
    </VCol>
    <VCol
      cols="12"
      md="5"
      lg="3"
    >
      <CavoBioPanel :cavo-data="cavoData" />
    </VCol>
    <VRow>
      <VCol
        cols="12"
        md="12"
        lg="12"
      >
        <TotaliPreventivoBioPanel :cavo-data="cavoData" />
      </VCol>
      <VCol cols="12">
        <VCard >
          <!-- 👉 Buttons -->
          <VCardText>
            <VRow>

            </VRow>
            <!-- 👉 User Details list -->
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
    <VCol
      cols="12"
      md="12"
      lg="12"
    >
      <VCard>
        <VCardText class="d-flex flex-wrap py-4 gap-4">
          <div class="app-user-search-filter d-flex align-center flex-wrap gap-4">
            <!-- 👉 Add user button -->
            <VBtn
              v-if="can(DefineAbilities.cavi_edit.action, DefineAbilities.cavi_edit.subject)"
              prepend-icon="tabler-plus"
              color="primary"
              @click="newItem"
            >
              Nuova Elemento
            </VBtn>
          </div>
        </VCardText>
        <!-- 👉 Datatable  -->
        <VDataTableServer
          :headers="headers"
          :items="serverItems"
        >
          <template #item.posizione="{ item }">
            <p class="text-error">
              {{ item.posizione }}
            </p>
          </template>
          <template #item.diametro="{ item }">
            <p
              v-if="item.diametro <= 0"
              class="text-arancione"
            />
            <p
              v-else
              class="text-arancione"
            >
              {{ euro.format(item.diametro) }}
            </p>
          </template>
          <template #item.peso="{ item }">
            <p
              v-if="item.peso <= 0"
              class="text-arancione"
            />
            <p
              v-else
              class="text-arancione"
            >
              {{ euro.format(item.peso) }}
            </p>
          </template>
          <template #item.costo="{ item }">
            <p
              v-if="item.costo <= 0"
              class="text-arancione"
            />
            <p
              v-else
              class="text-arancione"
            >
              {{ euro.format(item.costo) }}
            </p>
          </template>
          <template #item.costo_materia_prima="{ item }">
            <p
              v-if="item.costo_materia_prima <= 0"
              class="text-arancione"
            />
            <p
              v-else
              class="text-arancione"
            >
              {{ euro.format(item.costo_materia_prima) }}
            </p>
          </template>
          <template #item.costo_centro="{ item }">
            <p
              v-if="item.costo_centro <= 0"
              class="text-arancione"
            />
            <p
              v-else
              class="text-arancione"
            >
              {{ euro.format(item.costo_centro) }}
            </p>
          </template>
          <template #item.ordinata="{ item }">
            <p
              v-if="item.ordinata <= 0"
              class="text-arancione"
            />
            <p
              v-else
              class="text-arancione"
            >
              {{ euro.format(item.ordinata) }}
            </p>
          </template>
          <template #item.elementi="{ item }">
            <p class="text-primary">
              {{ item.elementi }}
            </p>
          </template>
          <template #item.costo_lavorazione="{ item }">
            <p
              v-if="item.costo_lavorazione <= 0"
              class="text-arancione"
            />
            <p
              v-else
              class="text-arancione"
            >
              {{ euro.format(item.costo_lavorazione) }}
            </p>
          </template>
          <template #item.ore_macchina="{ item }">
            <p
              v-if="item.ore_macchina <= 0"
              class="text-arancione"
            />
            <p
              v-else
              class="text-arancione"
            >
              {{ euro.format(item.ore_macchina) }}
            </p>
          </template>
          <template #bottom />
          <template #item.actions="{ item }">
            <div class="d-flex gap-1">
              <IconBtn
                v-if="can(DefineAbilities.cavi_edit.action, DefineAbilities.cavi_edit.subject)"
                color="primary"
                @click="editItem(item)"
              >
                <VIcon icon="tabler-edit" />
              </IconBtn>
              <IconBtn
                v-if="can(DefineAbilities.cavi_edit.action, DefineAbilities.cavi_edit.subject)"
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
    max-width="2100"
  >
    <!-- Dialog close btn -->
    <DialogCloseBtn @click="isDialogVisible = !isDialogVisible" />

    <!-- Dialog Content -->
    <VCard :title="editedItem.id ? `${$t('Label.Modifica')} Riga` : `${$t('Label.Nuova')} Riga`">
      <VCardText>
        <VRow>
          <VCol
            cols="12"
            sm="2"
            md="1"
          >
            <AppTextField
              v-model="editedItem.posizione"
              type="number"
              :label="$t('Label.Posizione')"
              :placeholder="$t('Label.Posizione')"
              min="1"
            />
          </VCol>
          <VCol
            cols="12"
            sm="4"
            md="3"
          >
            <AppAutocomplete
              v-model="editedItem.centro"
              :label="$t('Label.Centro')"
              :placeholder="$t('Label.Centro')"
              :items="centriOptions"
              :item-title="item => item.centro"
              :item-value="item => item.centro"
              clearable
              clear-icon="tabler-x"
            />
          </VCol>
          <VCol
            cols="12"
            sm="4"
            md="3"
          >
            <AppAutocomplete
              v-model="editedItem.materiale"
              :label="$t('Label.Materiale')"
              :placeholder="$t('Label.Materiale')"
              :items="materialiOptions"
              :item-title="item => item.materiale"
              :item-value="item => item.materiale"
              clearable
              clear-icon="tabler-x"
            />
          </VCol>
          <VCol
            cols="12"
            sm="5"
            md="5"
          >
            <AppTextField
              v-model="editedItem.descrizione"
              :label="$t('Label.Descrizione')"
              :placeholder="$t('Label.Descrizione')"
            />
          </VCol>
          <VCol
            cols="12"
            sm="3"
            md="2"
          >
            <AppTextField
              v-model="editedItem.diametro"
              type="number"
              :label="$t('Label.Diametro')"
              :placeholder="$t('Label.Diametro')"
              min="0"
            />
          </VCol>
          <VCol
            cols="12"
            sm="3"
            md="2"
          >
            <AppTextField
              v-model="editedItem.peso"
              type="number"
              :label="$t('Label.Peso')"
              :placeholder="$t('Label.Peso')"
              min="0"
            />
          </VCol>
          <VCol
            cols="12"
            sm="3"
            md="2"
          >
            <AppTextField
              v-model="editedItem.ordinata"
              type="number"
              :label="$t('Label.Produzione-Oraria')"
              :placeholder="$t('Label.Produzione-Oraria')"
              min="0"
            />
          </VCol>
          <VCol
            cols="12"
            sm="3"
            md="2"
          >
            <AppTextField
              v-model="editedItem.elementi"
              type="number"
              :label="$t('Label.Numero-Elementi')"
              :placeholder="$t('Label.Numero-Elementi')"
              min="0"
            />
          </VCol>
          <!--VCol
            cols="12"
            sm="2"
            md="1"
          >
            <AppTextField
              v-model="editedItem.costo_lavorazione"
              type="number"
              :label="$t('Label.Costo-Lavorazione')"
              :placeholder="$t('Label.Costo-Lavorazione')"
              min="0"
            />
          </VCol-->
          <VCol
            cols="12"
            sm="2"
            md="1"
          >
            <AppTextField
              v-model="editedItem.ore_macchina"
              type="number"
              :label="$t('Label.Ore-Macchina')"
              :placeholder="$t('Label.Ore-Macchina')"
              min="0"
            />
          </VCol>
          <VCol
            cols="12"
            sm="2"
            md="2"
          >
            <AppTextField
              v-model="editedItem.nota"
              :label="$t('Label.Nota')"
              :placeholder="$t('Label.Nota')"
            />
          </VCol>
        </VRow>
      </VCardText>

      <VCardText class="d-flex justify-end flex-wrap gap-3">
        <VBtn
          variant="tonal"
          color="secondary"
          @click="isDialogVisible = false"
        >
          Close
        </VBtn>
        <VBtn @click="onSubmit">
          Save
        </VBtn>
      </VCardText>
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
