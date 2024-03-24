<script setup lang="ts">
import { VForm }            from 'vuetify/components/VForm'
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import moment               from 'moment'
import {RpRegisterLog, RpRegisterLogNotifiche}      from "@/views/reception/type"
import { useI18n }          from 'vue-i18n'
import type {Fai}           from "@/views/quality/fai/type";
import {PerfectScrollbar} from "vue3-perfect-scrollbar";
import avatar1 from "@images/avatars/avatar-1.png";

definePage({
  meta: {
    action: 'list',
    subject: 'Qualita-Fai',
  },
})

const { t } = useI18n()
const itemsPerPage = ref(10)
let loading = true
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const visitatoreFilter = ref('')
const aziendaFilter = ref('')
const dataFilter = ref('')
const page = ref(1)
const serverItems = ref<any>([])
const isFormValid = ref(false)
const isFormClodesValid = ref(false)
const isUserInfoEditDialogVisible = ref(false)
const editDialog = ref(false)
const resultFaiDialog = ref(false)
const deleteDialog = ref(false)
const isSnackbarScrollReverseVisible = ref(false)
const message = ref('')
const color = ref('')
const refForm = ref<VForm>()
const isLoading = ref(false)
const listUsers = ref({})
const defaultReferenti = ref<RpRegisterLogNotifiche>({
  id: '',
  user: 0,
})
// eslint-disable-next-line @typescript-eslint/no-unused-vars
const defaultItem = ref<RpRegisterLog>({
  id: '',
  user: 0,
  data_prevista: '',
  data_scadenza: '',
  nome: '',
  email: '',
  wifi: false,
  user_interni: [],
  referenti: defaultReferenti,

})

const editedItem = ref<RpRegisterLog>(defaultItem.value)


const updateOptions = (options: any) => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
  page.value = options.page
  itemsPerPage.value = options.itemsPerPage

  // eslint-disable-next-line @typescript-eslint/no-use-before-define
  loadItems()
}

const loadItems = async () => {
  loading = true

  const { data:resultData, error } = await useApi<any>(createUrl('/reception/register/list', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      visitatore: visitatoreFilter.value,
      azienda: aziendaFilter.value,
      data: dataFilter.value,
    },
  }))

  if (resultData.value !== null) {
    serverItems.value = resultData.value.data
    totalItems.value = resultData.value.total
  }
  else {
    serverItems.value = []
    totalItems.value = 0
  }
  loading = false
}

const newItem = () => {

  editDialog.value = true
}

// status options
const selectedOptions = [
  { text: 'Attivo', value: 1 },
  { text: 'Disattivo', value: 2 },
]

// headers
const headers = [
  { title: t('Label.Data'), key: 'data_prevista' },
  { title: t('Label.Visitatore'), key: 'nome' },
  { title: t('Label.Email'), key: 'email' },
  { title: t('Label.Azienda'), key: 'azienda' },
  { title: t('Label.Utente'), key: 'full_name' },
  { title: t('Label.Notifica'), key: 'notifica_inviata' },
  { title: 'ACTIONS', key: 'actions', sortable: false },
]

const guestsOptions = [{}]

const userOptions = async () => {
  const resultData = await useApi<any>(createUrl('/users/getUsers'))

  resultData.data.value.data.forEach((value) => {
    guestsOptions.push({ avatar: avatar1, full_name: value.full_name, id: value.email })
  })
  console.log(guestsOptions)
  guestsOptions.splice(0, 1)
}

userOptions()
function formatDate(date: string): string {
  return moment(String(date)).format('MM/DD/YYYY H:m')
}
</script>

<template>
  <VCol cols="12">
    <VCard
      title="Filters"
      class="mb-6"
    >
      <VCardText>
        <VRow>
          <!-- 👉 Visitatore -->
          <VCol
            cols="12"
            sm="4"
          >
            <AppTextField
              v-model="visitatoreFilter"
              :label="$t('Label.Visitatore')"
              clearable
              clear-icon="tabler-x"
              @focusout="loadItems"
            />
          </VCol>

          <!-- 👉 Azienda -->
          <VCol
            cols="12"
            sm="4"
          >
            <AppTextField
              v-model="aziendaFilter"
              :label="$t('Label.Azienda')"
              clearable
              clear-icon="tabler-x"
              @focusout="loadItems"
            />
          </VCol>

          <!-- 👉 Data -->
          <VCol
            cols="12"
            sm="4"
          >
            <AppDateTimePicker
              v-model="dataFilter"
              :label="$t('Label.Data')"
              clearable
              clear-icon="tabler-x"
              @focusout="loadItems"
            />
          </VCol>
        </VRow>
      </VCardText>
    </VCard>
    <VCard>
      <VCardText class="d-flex flex-wrap py-4 gap-4">
        <VSnackbar
          v-model="isSnackbarScrollReverseVisible"
          transition="scroll-y-reverse-transition"
          location="top central"
          :color="color"
        >
          {{ $t(message) }}
        </VSnackbar>
        <div class="app-user-search-filter d-flex align-center flex-wrap gap-4">
          <!-- 👉 Add user button -->
          <VBtn
            prepend-icon="tabler-plus"
            color="success"
            @click="newItem"
          >
            Nuovo Visitatore
          </VBtn>
        </div>
      </VCardText>
      <!-- 👉 Datatable  -->
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        :headers="headers"
        :items="serverItems"
        :items-length="totalItems"
        :loading="loading"
        @update:options="updateOptions"
      >
        <template #item.data_azione="{ item }">
          <div class="d-flex gap-1">
            {{ formatDate(item.data_azione) }}
          </div>
        </template>

        <template #item.notifica_inviata="{ item }">
          <div class="d-flex gap-1" v-if="item.notifica_inviata">
            Si
          </div>
          <div class="d-flex gap-1" v-else>
            No
          </div>
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }">
          <div class="d-flex gap-1">
            <IconBtn
              color="primary"
              @click=""
            >
              <VIcon icon="tabler-info-circle"/>
            </IconBtn>

            <IconBtn
              color="warning"
              @click=""
            >
              <VIcon icon="tabler-send"/>
            </IconBtn>
          </div>
        </template>
      </VDataTableServer>
    </VCard>
  </VCol>

  <VNavigationDrawer
    temporary=""
    location="end"
    :model-value="editDialog"
    width="800"
    class="scrollable-content"

  >
    <!-- 👉 Header -->
    <AppDrawerHeaderSection
      :title="editedItem.id ? $t('Label.Modifica') + ' Fai' : $t('Label.Nuovo') + ' Visitatore'"

    >
    </AppDrawerHeaderSection>

    <PerfectScrollbar :options="{ wheelPropagation: false }">
      <VCard flat>
        <VCardText>
          <!-- SECTION Form -->
          <VForm
            ref="refForm"

          >
            <VRow>
              <!-- 👉 Title -->
              <VCol cols="12">
                <AppTextField
                  v-model="editedItem.nome"
                  :rules="[requiredValidator]"
                  :label="$t('Label.Nomme')"
                  :placeholder="$t('Label.Nome')"
                  required
                />
              </VCol>

              <!-- 👉 Start date -->
              <VCol cols="6">
                <AppDateTimePicker
                  v-model="editedItem.data_prevista"
                  :rules="[requiredValidator]"
                  label="Data Inizio"
                  placeholder="Select Date"
                />
              </VCol>

              <!-- 👉 End date -->
              <VCol cols="6">
                <AppDateTimePicker
                  v-model="editedItem.data_scadenza"
                  :rules="[requiredValidator]"
                  label="Data Fine"
                  placeholder="Select End Date"
                />
              </VCol>

              <!-- 👉 Interni -->
              <VCol cols="12">
                <AppSelect
                  v-model="editedItem.user_interni"
                  label="Utenti Interni Da Avvisare"
                  placeholder="Select Utenti Interni"
                  :items="guestsOptions"
                  :item-title="item => item.full_name"
                  :item-value="item => item.id"
                  chips
                  multiple
                  eager
                />
              </VCol>

              <!-- 👉 Descrizione -->


              <!-- 👉 Form buttons -->
              <VCol cols="12">
                <VBtn
                  type="submit"
                  class="me-3"

                >
                  Submit
                </VBtn>
                <VBtn
                  variant="outlined"
                  color="secondary"

                >
                  Cancel
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
          <!-- !SECTION -->
        </VCardText>
      </VCard>
    </PerfectScrollbar>
  </VNavigationDrawer>


</template>

