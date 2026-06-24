<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { VForm } from 'vuetify/components/VForm'
import { can } from '@layouts/plugins/casl'
import DefineAbilities from '@/plugins/casl/DefineAbilities'
import moment from "moment/moment";

interface Emit {
  (e: 'update:isRefresh', value: boolean): void
}

interface Props {
  id: string
  itemsData: []
}

const emit = defineEmits<Emit>()
const props = defineProps<Props>()

const { t } = useI18n()
const itemsPerPage = ref(10)
const isLoading = ref(false)
const editDialog = ref(false)
const isFormValid = ref(false)
const readonly = ref(false)
const view = ref(false)
const refForm = ref<VForm>()
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const page = ref(1)
const file = ref(null)
const data = ref({})
const fileName = computed(() => file.value?.name)
const fileExtension = computed(() => fileName.value?.substr(fileName.value?.lastIndexOf('.') + 1))
const fileMimeType = computed(() => file.value?.type)
const listTraining = ref({})
const dataCurrent = new Date().getTime()

const defaultItem = ref<any>({
  id: '',
  employee_id: '',
  formazione_id: null,
  data_formazione: '',
  data_scadenza: '',
  path_driver: '',
  utente_id: null,
  file: null,
})

function new_defaultItem() {
  defaultItem.value = {
    id: '',
    employee_id: '',
    formazione_id: null,
    data_formazione: '',
    data_scadenza: '',
    path_driver: '',
    utente_id: null,
    file: null,
  }
}

const editedItem = ref<any>(defaultItem.value)
const editedIndex = ref(-1)

const headersProfessional = [
  { title: t('Table.Formazione'), key: 'formazione', width: '75%', sortable: false },
  { title: t('Table.Scadenza'), key: 'data_scadenza', width: '20%', sortable: false },
  { title: '', key: 'actions', width: '5%', sortable: false },
]

const newItem = () => {
  new_defaultItem()
  editedIndex.value = -1
  editedItem.value = { ...defaultItem.value }
  editedItem.value.employee_id = props.id
  readonly.value = false
  editDialog.value = true
}

const close = () => {
  isLoading.value = false
  editDialog.value = false
  readonly.value = false
  editedIndex.value = -1
  refForm.value?.reset()
}

const rinnovaFormazione = (item: object) => {

  editedItem.value = { ...item }
  editedItem.value.data_formazione = editedItem.value.data_scadenza
  readonly.value = true
  editDialog.value = true
}

const uploadFile = (event: any) => {
  file.value = event.target.files[0]

  const reader = new FileReader()

  reader.readAsDataURL(file.value)
  reader.onload = async () => {
    const encodedFile = reader.result.split(',')[1]

    data.value = {
      file: encodedFile,
      fileName: fileName.value,
      fileExtension: fileExtension.value,
      fileMimeType: fileMimeType.value,
    }
  }
}

const save = async () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      let path = '/hr/formazioni/obbligatori/store/'
      if (editedItem.value.id)
        path = '/hr/formazioni/professionali/upload/'

      editedItem.value.file = data.value
      const retuenData = $api(path, {
        method: 'POST',
        body: editedItem.value,
      })

      emit('update:isRefresh', true)
      editDialog.value = false
      nextTick(() => {
        refForm.value?.reset()
        refForm.value?.resetValidation()
      })
    }
  })
}

const loadTraning = async () => {
  const { data: resultData} = await useApi<any>(createUrl('/hr/gestione/formazioni/get_list', {
    query: {
      tipologia: 'obbligatoria',
    },
  }))

  listTraining.value = resultData.value
  view.value = true
}

loadTraning()

const convertStringToDate = (date: string, check: boolean) => {
  var result = new Date(date)

  if (check)
    result.setMonth(result.getMonth() - 1)

  return Number.parseInt(result.getTime())
}

function openDrivePage(path: string) {
  window.open(`https://drive.google.com/drive/u/0/folders/${path}`, '_blank')
}
</script>

<template>
  <VCard :title="$t('Label.Formazioni-Obbligatorie')">
    <VCardText class="d-flex flex-wrap py-4 gap-1">
      <div class="app-user-search-filter d-flex align-right flex-wrap gap-1">
        <!-- 👉 Add user button -->
        <VBtn
          v-if="can(DefineAbilities.macchinari_create.action, DefineAbilities.macchinari_create.subject)"
          prepend-icon="tabler-plus"
          color="success"
          @click="newItem"
        >
          {{ $t('Button.Nuova-Formazione') }}
        </VBtn>
      </div>
    </VCardText>
    <!-- 👉 Datatable Formazioni Professionali  -->
    <VDataTableServer
      v-model:items-per-page="itemsPerPage"
      :headers="headersProfessional"
      :items="props.itemsData.data"
      :items-length="props.itemsData.total"
      density="compact"
      class="text-sm-h6"
    >
      <template #item="{ item }">
        <tr v-if="(Number.parseInt(dataCurrent) > convertStringToDate(item.data_scadenza, true) && Number.parseInt(dataCurrent) > convertStringToDate(item.data_scadenza, false))" class="error-row">
          <td><p class="mt-4" style="font-size: 0.7rem">{{ item.formazione }}</p></td>
          <td><p class="mt-4" style="font-size: 0.6rem">{{ item.data_scadenza }}</p></td>
          <td>
            <VBtn
              icon
              variant="text"
              size="15"
              color="small-emphasis"
            >
              <VIcon
                size="12"
                icon="tabler-dots-vertical"
              />
              <VMenu activator="parent">
                <VList>
                  <VListItem
                    v-if="$can(DefineAbilities.user_edit.action, DefineAbilities.user_edit.subject)"
                    @click="rinnovaFormazione(item)"
                  >
                    <template #prepend>
                      <VIcon icon="tabler-browser-plus" />
                    </template>
                    <VListItemTitle>{{ $t('Label.Rinnova-Formazione') }}</VListItemTitle>
                  </VListItem>

                  <VListItem
                    v-if="$can(DefineAbilities.user_deleted.action, DefineAbilities.user_deleted.subject)"
                    color="primary"
                    @click="openDrivePage(item.path_drive)"
                  >
                    <template #prepend>
                      <VIcon icon="tabler-brand-google-drive" />
                    </template>
                    <VListItemTitle>{{ $t('Label.Documenti') }}</VListItemTitle>
                  </VListItem>

                  <VListItem
                    v-if="$can(DefineAbilities.user_deleted.action, DefineAbilities.user_deleted.subject)"
                    @click="deleteUser(item.id)"
                  >
                    <template #prepend>
                      <VIcon icon="tabler-trash" />
                    </template>
                    <VListItemTitle>{{ $t('Label.Elimina') }}</VListItemTitle>
                  </VListItem>
                </VList>
              </VMenu>
            </VBtn>
          </td>
        </tr>
        <tr v-else-if="(Number.parseInt(dataCurrent) > convertStringToDate(item.data_scadenza, true) && Number.parseInt(dataCurrent) < convertStringToDate(item.data_scadenza, false))" class="highlight-row">
          <td><p class="mt-4" style="font-size: 0.7rem">{{ item.formazione }}</p></td>
          <td><p class="mt-4" style="font-size: 0.6rem">{{ item.data_scadenza }}</p></td>
          <td>
            <VBtn
              icon
              variant="text"
              size="15"
              color="small-emphasis"
            >
              <VIcon
                size="12"
                icon="tabler-dots-vertical"
              />
              <VMenu activator="parent">
                <VList>
                  <VListItem
                    v-if="$can(DefineAbilities.user_edit.action, DefineAbilities.user_edit.subject)"
                    @click="rinnovaFormazione(item)"
                  >
                    <template #prepend>
                      <VIcon icon="tabler-browser-plus" />
                    </template>
                    <VListItemTitle>{{ $t('Label.Rinnova-Formazione') }}</VListItemTitle>
                  </VListItem>

                  <VListItem
                    v-if="$can(DefineAbilities.user_deleted.action, DefineAbilities.user_deleted.subject)"
                    color="primary"
                    @click="openDrivePage(item.path_drive)"
                  >
                    <template #prepend>
                      <VIcon icon="tabler-brand-google-drive" />
                    </template>
                    <VListItemTitle>{{ $t('Label.Documenti') }}</VListItemTitle>
                  </VListItem>

                  <VListItem
                    v-if="$can(DefineAbilities.user_deleted.action, DefineAbilities.user_deleted.subject)"
                    @click="deleteUser(item.id)"
                  >
                    <template #prepend>
                      <VIcon icon="tabler-trash" />
                    </template>
                    <VListItemTitle>{{ $t('Label.Elimina') }}</VListItemTitle>
                  </VListItem>
                </VList>
              </VMenu>
            </VBtn>
          </td>
        </tr>
        <tr v-else >
          <td><p class="mt-4" style="font-size: 0.7rem">{{ item.formazione }}</p></td>
          <td><p class="mt-4" style="font-size: 0.6rem">{{ item.data_scadenza }}</p></td>
          <td>
            <VBtn
              icon
              variant="text"
              size="15"
              color="small-emphasis"
            >
              <VIcon
                size="12"
                icon="tabler-dots-vertical"
              />
              <VMenu activator="parent">
                <VList>
                  <VListItem
                    v-if="$can(DefineAbilities.user_edit.action, DefineAbilities.user_edit.subject)"
                    @click="rinnovaFormazione(item)"
                  >
                    <template #prepend>
                      <VIcon icon="tabler-browser-plus" />
                    </template>
                    <VListItemTitle>{{ $t('Label.Rinnova-Formazione') }}</VListItemTitle>
                  </VListItem>

                  <VListItem
                    v-if="$can(DefineAbilities.user_deleted.action, DefineAbilities.user_deleted.subject)"
                    color="primary"
                    @click="openDrivePage(item.path_drive)"
                  >
                    <template #prepend>
                      <VIcon icon="tabler-brand-google-drive" />
                    </template>
                    <VListItemTitle>{{ $t('Label.Documenti') }}</VListItemTitle>
                  </VListItem>

                  <VListItem
                    v-if="$can(DefineAbilities.user_deleted.action, DefineAbilities.user_deleted.subject)"
                    @click="deleteUser(item.id)"
                  >
                    <template #prepend>
                      <VIcon icon="tabler-trash" />
                    </template>
                    <VListItemTitle>{{ $t('Label.Elimina') }}</VListItemTitle>
                  </VListItem>
                </VList>
              </VMenu>
            </VBtn>
          </td>
        </tr>
      </template>
      <template #bottom>
        <VCardText class="pt-2" style="display: block">
          <div class="d-flex flex-wrap justify-center justify-sm-space-between gap-y-2 mt-2">

          </div>
        </VCardText>
      </template>
    </VDataTableServer>
  </VCard>

  <!-- 👉 Edit Dialog  -->
  <VDialog
    v-model="editDialog"
    max-width="800px"
    persistent
  >
    <AppCardActions
      v-model:loading="isLoading"
      :title="editedItem.id ? `${$t('Label.Modifica')} Formazione` : `${$t('Label.Nuova')} Formazione`"
      no-actions
    >
      <VCard variant="flat">
        <VCardText class="pa-6">
          <VForm
            ref="refForm"
            v-model="isFormValid"
          >
            <VRow dense>
              <!-- 👉 Formazione -->
              <VCol cols="12">
                <AppSelect
                  :key="view"
                  v-model="editedItem.formazione_id"
                  :rules="[requiredValidator]"
                  :items="listTraining"
                  :menu-props="{ transition: 'scroll-y-transition' }"
                  :label="$t('Label.Formazione')"
                  :placeholder="$t('Label.Formazione')"
                  item-title="formazione"
                  item-value="id"
                  :readonly="readonly"
                  density="comfortable"
                />
              </VCol>

              <!-- 👉 Data Formazione -->
              <VCol cols="12">
                <AppDateTimePicker
                  v-model="editedItem.data_formazione"
                  :rules="[requiredValidator]"
                  :label="$t('Label.Data-Formazione')"
                  :placeholder="$t('Label.Data-Formazione')"
                  density="comfortable"
                />
              </VCol>

              <!-- 👉 Documento -->
              <VCol cols="12">
                <VFileInput
                  accept=".xlsx, .xls, .pdf"
                  :label="$t('Label.File')"
                  :rules="[requiredValidator]"
                  prepend-icon="tabler-paperclip"
                  density="comfortable"
                  @change="uploadFile"
                />
              </VCol>
            </VRow>
          </VForm>
        </VCardText>

        <VDivider />

        <VCardActions class="pa-4">
          <VSpacer />

          <VBtn
            type="reset"
            color="error"
            variant="text"
            @click="close"
          >
            {{ $t('Button.Annulla') }}
          </VBtn>

          <VBtn
            type="submit"
            color="success"
            variant="elevated"
            @click="save"
          >
            {{ $t('Button.Salva') }}
          </VBtn>
        </VCardActions>
      </VCard>
    </AppCardActions>
  </VDialog>
</template>

<style>
.highlight-row {
  background-color: #e58115;
  color: white;
}

.error-row {
  background-color: #e70808;
  color: white;
}
</style>
