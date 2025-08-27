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

// headers
const headers = [
  { title: t('Label.Posizione'), key: 'posizione', sortable: false },
  { title: t('Label.Macchina'), key: 'centro', sortable: false },
  { title: t('Table.Materiale'), key: 'materiale', sortable: false },
  { title: t('Table.Descrizione'), key: 'descrizione', sortable: false },
  { title: t('Label.Diametro'), key: 'diametro', sortable: false },
  { title: t('Label.Peso'), key: 'peso', sortable: false },
  { title: t('Label.Produzione-Oraria'), key: 'ordinata', sortable: false },
  { title: t('Label.Numero-Elementi'), key: 'elementi', sortable: false },
  { title: t('Label.Nota'), key: 'nota', sortable: false },
  { title: 'ACTIONS', key: 'actions', sortable: false },
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
  const { data: resultData } = await useApi<any>(createUrl(`/to/cavi/view/${route.params.id}`))

  cavoData.value = resultData.value
  loadItems()
}

const loadItems = async () => {
  const { data: resultData } = await useApi<any>(createUrl(`/to/cavi/view/${route.params.id}/rows`))

  loading.value = false
  serverItems.value = resultData.value
}

const onSubmit = async () => {
  if (editedItem.value.id === undefined) {
    const { data: retuenData } = await $api(`/to/cavi/${route.params.id}/stored`, {
      method: 'POST',
      body: editedItem.value,
    })
  }
  else {
    const { data: retuenData } = await $api(`/to/cavi/${route.params.id}/update/${editedItem.value.id}`, {
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
  maximumSignificantDigits: 4,
})

onMounted(() => {
  getCavo()
  centriOptions.value = centriData.value
  materialiOptions.value = materialiData.value
})
</script>

<template>
  <VRow v-if="cavoData">
    <VCol
      cols="12"
      md="5"
      lg="3"
    >
      <CavoBioPanel :cavo-data="cavoData" />
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
              v-if="can(DefineAbilities.cavi_create.action, DefineAbilities.cavi_create.subject)"
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
          <VCol
            cols="12"
            sm="4"
            md="4"
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
