<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { can } from '@layouts/plugins/casl'
import DefineAbilities from '@/plugins/casl/DefineAbilities'
import {Professional} from "@/views/hr/employee/training/type";
import {VForm} from "vuetify/components/VForm";
import {aS} from "@fullcalendar/core/internal-common";

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

const defaultItem = ref<any>({
  id: '',
  employee_id: '',
  formazione: '',
  data_formazione: '',
  path_drive: '',
  utente_id: null,
  tipologia: null,
  file: null,
})

function new_defaultItem() {
  defaultItem.value = {
    id: '',
    employee_id: '',
    formazione: '',
    data_formazione: '',
    path_drive: '',
    utente_id: null,
    tipologia: null,
    file: null,
  }
}

const editedItem = ref<any>(defaultItem.value)
const editedIndex = ref(-1)

const headersProfessional = [
  { title: t('Table.Formazione'), key: 'formazione', width: '75%', sortable: false },
  { title: t('Table.Data'), key: 'data_formazione', width: '20%', sortable: false },
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

const nuovoDocuemnto = (item: object) => {

  editedItem.value = { ...item }
  editedItem.value.employee_id = props.id
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
  await refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      let path = '/hr/formazioni/professionali/store/'
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

function openDrivePage(path: string) {
  window.open(`https://drive.google.com/drive/u/0/folders/${path}`, '_blank')
}
</script>

<template>
  <VCard :title="$t('Label.Formazioni-Professionali')">
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
      <!-- Data Formazione -->
      <template #item.formazione="{ item }">
        <p class="mt-4" style="font-size: 0.7rem">{{ item.formazione }}</p>
      </template>
      <!-- Data Formazione -->
      <template #item.data_formazione="{ item }">
        <p class="mt-4" style="font-size: 0.6rem">{{ item.data_formazione }}</p>
      </template>
      <!-- Actions -->
      <template #item.actions="{ item }">
        <VBtn
          icon
          variant="text"
          size="20"
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
                @click="nuovoDocuemnto(item)"
              >
                <template #prepend>
                  <VIcon icon="tabler-upload" />
                </template>
                <VListItemTitle>{{ $t('Label.Nuovo-Documento') }}</VListItemTitle>
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
    max-width="1400px"
    persistent
  >
    <AppCardActions
      v-model:loading="isLoading"
      :title="editedItem.id ? `${$t('Label.Modifica')} Formazione` : `${$t('Label.Nuova')} Formazione`"
      no-actions
    >
      <VCard>
        <VCardText>
          <VContainer>
            <VForm
              ref="refForm"
              v-model="isFormValid"
            >
              <VRow>
                <!-- 👉 Formazione -->
                <VCol cols="6">
                  <AppTextField
                    v-model="editedItem.formazione"
                    :rules="[requiredValidator]"
                    :label="$t('Label.Formazione')"
                    :placeholder="$t('Label.Formazione')"
                    :readonly="readonly"
                  />
                </VCol>

                <!-- 👉 Data Formazione -->
                <VCol cols="6">
                  <AppDateTimePicker
                    v-model="editedItem.data_formazione"
                    :rules="[requiredValidator]"
                    :label="$t('Label.Data-Formazione')"
                    :placeholder="$t('Label.Data-Formazione')"
                    :readonly="readonly"
                  />
                </VCol>

                <!-- 👉 Documento -->
                <VCol cols="6">
                  <VFileInput
                    accept=".xlsx, .xls, .pdf"
                    :label="$t('Label.File')"
                    :rules="[requiredValidator]"
                    @change="uploadFile"
                  />
                </VCol>

                <VCol cols="6">
                </VCol>

                <VCol
                  cols="6"
                >
                  <VRadioGroup
                    v-model="editedItem.tipologia"
                    :rules="[requiredValidator]"
                    inline
                  >
                    <VRadio
                      :label="$t('Label.Richiesta')"
                      value="1"
                    />
                    <VRadio
                      :label="$t('Label.Attestato')"
                      value="2"
                    />
                    <VRadio
                      :label="$t('Label.Valutazione')"
                      value="3"
                    />
                  </VRadioGroup>
                </VCol>
              </VRow>
            </VForm>
          </VContainer>
        </VCardText>

        <VCardActions>
          <VSpacer />

          <VBtn
            type="reset"
            color="error"
            variant="outlined"
            @click="close"
          >
            Cancel
          </VBtn>

          <VBtn
            type="submit"
            color="success"
            variant="elevated"
            @click="save"
          >
            Save
          </VBtn>
        </VCardActions>
      </VCard>
    </AppCardActions>
  </VDialog>
</template>
