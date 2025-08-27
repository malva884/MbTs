<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { useI18n } from 'vue-i18n'
import { VForm } from 'vuetify/components/VForm'
import { can } from '@layouts/plugins/casl'
import DefineAbilities from '@/plugins/casl/DefineAbilities'

definePage({
  meta: {
    action: 'list',
    subject: 'Cavi',
  },
})

const { t } = useI18n()
const itemsPerPage = ref(10)
const loading = ref(1)
const refForm = ref<VForm>()
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const bobinaFilter = ref('')
const codiceFilter = ref('')
const page = ref(1)
const serverItems = ref<any>([])
const isSnackbarScrollReverseVisible = ref(false)
const message = ref('')
const color = ref('')
const editDialog = ref(false)
const isLoading = ref(false)
const isFormValid = ref(false)

const defaultItem = ref<any>({
  id: '',
  bobina: '',
  codice_as: '',
  capacita: '',
  m3: '',
  costo: '',
  costo_medio: '',
  dimensioni: '',
  lettera: '',
})

function new_defaultItem() {
  defaultItem.value = {
    id: '',
    bobina: '',
    codice_as: '',
    capacita: '',
    m3: '',
    costo: '',
    costo_medio: '',
    dimensioni: '',
    lettera: '',
  }
}

const editedItem = ref<any>(defaultItem.value)
const editedIndex = ref(-1)

const updateOptions = (options: any) => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
  page.value = options.page
  itemsPerPage.value = options.itemsPerPage

  // eslint-disable-next-line @typescript-eslint/no-use-before-define
  loadItems()
}

const loadItems = async () => {
  loading.value = true

  const { data: resultData, error } = await useApi<any>(createUrl('/to/bobine/list', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      bobina: bobinaFilter.value,
      codice: codiceFilter.value,
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
  loading.value = false
}

// headers
const headers = [
  { title: t('Table.Bobina'), key: 'bobina' },
  { title: t('Table.Codice-As'), key: 'codice_as' },
  { title: t('Table.Capacita'), key: 'capacita' },
  { title: t('Table.M3'), key: 'm3' },
  { title: t('Table.Costo'), key: 'costo' },
  { title: t('Table.Peso'), key: 'peso' },
  { title: t('Table.Dimensioni'), key: 'dimensioni' },
  { title: t('Table.Lettera'), key: 'lettera' },
  { title: 'ACTIONS', key: 'actions', sortable: false },
]

const save = async () => {
  refForm.value?.validate().then(async ({valid}) => {
    if (valid) {
      let path = '/to/bobine/stored/'
      if (editedItem.value.id)
        path = `/to/bobine/update/${editedItem.value.id}`

      isLoading.value = true

      const retuenData = await $api(path, {
        method: 'POST',
        body: editedItem.value,
      })

      nextTick(() => {
        refForm.value?.reset()
        refForm.value?.resetValidation()
      })

      message.value = retuenData.message
      color.value = retuenData.color
      isSnackbarScrollReverseVisible.value = true

      isLoading.value = false
      editDialog.value = false
      await loadItems()
    }
  })
}

const newItem = () => {
  new_defaultItem()
  editedIndex.value = -1
  editedItem.value = { ...defaultItem.value }
  editDialog.value = true
}

const close = () => {
  isLoading.value = false
  editDialog.value = false
  editedIndex.value = -1
  refForm.value?.reset()
}

const editItem = (item: object) => {
  editedIndex.value = serverItems.value.indexOf(item)

  editedItem.value = { ...item }
  editDialog.value = true
}

const euro = new Intl.NumberFormat('it-IT', {
  maximumSignificantDigits: 4,
})
</script>

<template>
  <VCol cols="12">
    <VCard
      title="Filters"
      class="mb-6"
    >
      <VCardText>
        <VRow>
          <!-- 👉 Cliente -->
          <VCol
            cols="12"
            sm="4"
          >
            <AppTextField
              v-model="bobinaFilter"
              :label="$t('Label.Bobina')"
              :placeholder="$t('Label.Bobina')"
              clearable
              clear-icon="tabler-x"
              @focusout="loadItems"
            />
          </VCol>
          <!-- 👉 Codice Sap -->
          <VCol
            cols="12"
            sm="4"
          >
            <AppTextField
              v-model="codiceFilter"
              :label="$t('Label.Codice-Sap')"
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
            v-if="can(DefineAbilities.qt_non_conformita_create.action, DefineAbilities.qt_non_conformita_create.subject)"
            prepend-icon="tabler-plus"
            color="success"
            @click="newItem"
          >
            {{$t('Button.Nuova-Bobina')}}
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
        <template #item.bobina="{ item }">
          <p class="text-success">
            {{item.bobina}}
          </p>
        </template>
        <template #item.capacita="{ item }">
          <p class="text-success">
            {{ euro.format(item.capacita) }}
          </p>
        </template>
        <template #item.costo="{ item }">
          <p class="text-success">
            {{ euro.format(item.costo) }}
          </p>
        </template>
        <template #item.m3="{ item }">
          <p class="text-success">
            {{ euro.format(item.m3) }}
          </p>
        </template>
        <template #item.lettera="{ item }">
          <p class="text-error">
            {{item.lettera}}
          </p>
        </template>
        <!-- Actions -->
        <template #item.actions="{ item }">
          <div class="d-flex gap-1">
            <IconBtn
              v-if="can(DefineAbilities.qt_non_conformita_create.action, DefineAbilities.qt_non_conformita_create.subject)"
              color="warning"
              @click="editItem(item)"
            >
              <VIcon icon="tabler-edit" />
            </IconBtn>
          </div>
        </template>
      </VDataTableServer>
    </VCard>
  </VCol>

  <!-- 👉 Edit Dialog  -->
  <VDialog
    v-model="editDialog"
    max-width="1400px"
  >
    <AppCardActions
      v-model:loading="isLoading"
      :title="editedItem.id ? `${$t('Label.Modifica')} Bobina` : `${$t('Label.Nuova')} Bobina`"
      no-actions
    >
      <VCard>
        <VCardText>
          <VContainer>
            <VForm
              ref="refForm"
              @submit.prevent="save"
            >
              <VRow>

                <!-- 👉 Bobina -->
                <VCol cols="3">
                  <AppTextField
                    v-model="editedItem.bobina"
                    :rules="[requiredValidator]"
                    :label="$t('Label.Bobina')"
                    :placeholder="$t('Label.Bobina')"
                  />
                </VCol>

                <!-- 👉 Codice As -->
                <VCol cols="3">
                  <AppTextField
                    v-model="editedItem.codice_as"
                    :label="$t('Label.Codice-As')"
                    :placeholder="$t('Label.Codice-As')"
                  />
                </VCol>

                <!-- 👉 Capacita -->
                <VCol cols="3">
                  <AppTextField
                    v-model="editedItem.capacita"
                    :rules="[requiredValidator]"
                    type="number"
                    :label="$t('Label.Capacita')"
                    :placeholder="$t('Label.Capacita')"
                  />
                </VCol>

                <!-- 👉 M3 -->
                <VCol cols="3">
                  <AppTextField
                    v-model="editedItem.m3"
                    :rules="[requiredValidator]"
                    type="number"
                    :label="$t('Label.M3')"
                    :placeholder="$t('Label.M3')"
                  />
                </VCol>

                <!-- 👉 Costo -->
                <VCol cols="6">
                  <AppTextField
                    v-model="editedItem.costo"
                    :rules="[requiredValidator]"
                    type="number"
                    :label="$t('Label.Costo')"
                    :placeholder="$t('Label.Costo')"
                  />
                </VCol>

                <!-- 👉 Costo Medio -->
                <VCol cols="6">
                  <AppTextField
                    v-model="editedItem.costo_medio"
                    type="number"
                    :label="$t('Label.Costo-Medio')"
                    :placeholder="$t('Label.Costo-Medio')"
                  />
                </VCol>

                <!-- 👉 Peso -->
                <VCol cols="4">
                  <AppTextField
                    v-model="editedItem.peso"
                    :rules="[requiredValidator]"
                    :label="$t('Label.Peso')"
                    :placeholder="$t('Label.Peso')"
                  />
                </VCol>

                <!-- 👉 Dimensioni -->
                <VCol cols="4">
                  <AppTextField
                    v-model="editedItem.dimensioni"
                    :rules="[requiredValidator]"
                    :label="$t('Label.Dimensioni')"
                    :placeholder="$t('Label.Dimensioni')"
                  />
                </VCol>

                <!-- 👉 Lettera -->
                <VCol cols="4">
                  <AppTextField
                    v-model="editedItem.lettera"
                    :rules="[requiredValidator]"
                    :label="$t('Label.Lettera')"
                    :placeholder="$t('Label.Lettera')"
                  />
                </VCol>
              </VRow>
              <VCardActions class="mt-6">
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
                  @click="refForm?.validate()"
                >
                  Save
                </VBtn>
              </VCardActions>
            </VForm>
          </VContainer>
        </VCardText>
      </VCard>
    </AppCardActions>
  </VDialog>
</template>
