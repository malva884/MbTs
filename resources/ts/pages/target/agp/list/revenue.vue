<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { VForm } from 'vuetify/components/VForm'
import { can } from '@layouts/plugins/casl'
import DefineAbilities from '@/plugins/casl/DefineAbilities'

definePage({
  meta: {
    action: 'report',
    subject: 'Produzione-Performance',
  },
})

const date = new Date()
const year = date.toLocaleString('default', { year: 'numeric' })
const years = [{ value: year - 2, title: year - 2 }, { value: year - 1, title: year - 1 }, { value: year, title: year }]


const { t } = useI18n()
const loading = ref(true)
const refForm = ref<VForm>()
const serverItems = ref<any>([])
const isSnackbarScrollReverseVisible = ref(false)
const message = ref('')
const color = ref('')
const editDialog = ref(false)
const valueDialog = ref(false)
const isLoading = ref(false)
const isFormValid = ref(false)

const val = ref<any>({
  periodo: '',
  tipo: 0,
  titolo: '',
  target: 0,
  valore: 0,
  edit: true,
})

const defaultItem = ref<any>({
  id: '',
  tipo: 100,
  ckm_ofc: null,
  kfkm_ofc: null,
  value_ofc: null,
  inr_ofc: null,
  ckm_cc: null,
  value_cc: null,
  inr_cc: null,
  periodo: '',
})

function new_defaultItem() {
  defaultItem.value = {
    id: '',
    tipo: 100,
    ckm_ofc: null,
    kfkm_ofc: null,
    value_ofc: null,
    inr_ofc: null,
    ckm_cc: null,
    value_cc: null,
    inr_cc: null,
    periodo: '',
  }

  val.value = {
    periodo: null,
    tipo: null,
    titolo: null,
    target: null,
    valore: null,
    edit: true,
  }
}

const editedItem = ref<any>(defaultItem.value)
const editedIndex = ref(-1)

const loadItems = async () => {
  loading.value = true

  const { data: resultData } = await useApi<any>(createUrl('/terget/agp', {
    query: {
      modulo: 100,
    },
  }))

  if (resultData.value !== null)
    serverItems.value = resultData.value
  else
    serverItems.value = []

  loading.value = false
}

loadItems()

const save = async () => {
  if (editedItem.value.periodo) {
    let path = '/terget/save_agp/'
    if (editedItem.value.id)
      path = `/terget/save_agp/${editedItem.value.id}`

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
}

const saveValue = async () => {
  if (val.value.periodo) {
    const path = '/terget/save_agp/'

    isLoading.value = true

    const retuenData = await $api(path, {
      method: 'POST',
      body: val.value,
    })

    nextTick(() => {
      refForm.value?.reset()
      refForm.value?.resetValidation()
    })
    new_defaultItem()
    message.value = retuenData.message
    color.value = retuenData.color
    isSnackbarScrollReverseVisible.value = true

    isLoading.value = false
    valueDialog.value = false
    await loadItems()
  }
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

const value = (camp: string, riferimeto: string, agp: number, va: number) => {

  val.value = {
    periodo: riferimeto,
    tipo: 100,
    titolo: camp,
    target: agp,
    valore: va,
    edit: true,
  }
  valueDialog.value = true
}
</script>

<template>
  <VCol cols="12">
    <VCard
      title="Filters"
      class="mb-6"
    >
      <VCardText>

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
            v-if="can(DefineAbilities.macchinari_create.action, DefineAbilities.macchinari_create.subject)"
            prepend-icon="tabler-plus"
            color="success"
            @click="newItem"
          >
            Nuovo Target
          </VBtn>
        </div>
      </VCardText>
      <!-- 👉 Datatable  -->
      <VTable
        density="compact"
        class="text-no-wrap"
      >
        <thead>
          <tr>
            <th>
              Periodo
            </th>
            <th>
              Rame Ckm
            </th>
            <th>
              Rame Mil €
            </th>
            <th>
              Rame Inr
            </th>
            <th>
              Ottico Ckm
            </th>
            <th>
              Ottico KfKm
            </th>
            <th>
              Ottico Mil €
            </th>
            <th>
              Ottico Inr
            </th>
          </tr>
        </thead>

        <tbody>
          <tr
            v-for="(item, index) in serverItems"
            :key="production"
          >
            <td>
              {{ item.periodo }}
            </td>
            <td @click="value('ckm_cc', item.periodo, item.ckm_cc?.agp, item.ckm_cc?.value)">
              {{ item.ckm_cc?.agp }} {{ item.ckm_cc?.value > 0 ? ` / ${item.ckm_cc?.value}` : '' }}
            </td>
            <td @click="value('value_cc', item.periodo, item.value_cc?.agp, item.value_cc?.value)">
              {{ item.value_cc?.agp }} {{ item.value_cc?.value > 0 ? ` / ${item.value_cc?.value}` : '' }}
            </td>
            <td @click="value('inr_cc', item.periodo, item.inr_cc?.agp, item.inr_cc?.value)">
              {{ item.inr_cc?.agp }} {{ item.inr_cc?.value > 0 ? ` / ${item.inr_cc?.value}` : '' }}
            </td>
            <td @click="value('ckm_ofc', item.periodo, item.ckm_ofc?.agp, item.ckm_ofc?.value)">
              {{ item.ckm_ofc?.agp }} {{ item.ckm_ofc?.value > 0 ? ` / ${item.ckm_ofc?.value}` : '' }}
            </td>
            <td @click="value('kfkm_ofc', item.periodo, item.kfkm_ofc?.agp, item.kfkm_ofc?.value)">
              {{ item.kfkm_ofc?.agp }} {{ item.kfkm_ofc?.value > 0 ? ` / ${item.kfkm_ofc?.value}` : '' }}
            </td>
            <td @click="value('value_ofc', item.periodo, item.value_ofc?.agp, item.value_ofc?.value)">
              {{ item.value_ofc?.agp }} {{ item.value_ofc?.value > 0 ? ` / ${item.value_ofc?.value}` : '' }}
            </td>
            <td @click="value('inr_ofc', item.periodo, item.inr_ofc?.agp, item.inr_ofc?.value)">
              {{ item.inr_ofc?.agp }} {{ item.inr_ofc?.value > 0 ? ` / ${item.inr_ofc?.value}` : '' }}
            </td>
          </tr>
        </tbody>
      </VTable>
    </VCard>
  </VCol>

  <!-- 👉 Edit Dialog  -->
  <VDialog
    v-model="editDialog"
    max-width="1800px"
    persistent
  >
    <AppCardActions
      v-model:loading="isLoading"
      :title="editedItem.id ? `${$t('Label.Modifica')} Target` : `${$t('Label.Nuovo')} Target`"
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
                <!-- 👉 Periodo -->
                <VCol cols="2">
                  <AppDateTimePicker
                    v-model="editedItem.periodo"
                    :label="$t('Local.Periodo-Riferimento')"
                    :placeholder="$t('Local.Periodo-Riferimento')"
                    :config="{ shorthand: true, dateFormat: 'Y F', altFormat: 'Y F' }"
                  />
                </VCol>
              </VRow>
              <VRow>
                <!-- 👉 CC Ckm -->
                <VCol cols="2">
                  <AppTextField
                    v-model="editedItem.ckm_cc"
                    type="number"
                    :label="$t('Label.Cc-Ckm-Agp')"
                    :placeholder="$t('Label.Cc-Ckm-Agp')"
                  />
                </VCol>

                <!-- 👉 CC Mil -->
                <VCol cols="3">
                  <AppTextField
                    v-model="editedItem.value_cc"
                    type="number"
                    :label="$t('Label.Cc-Mil €')"
                    :placeholder="$t('Label.Cc-Mil €')"
                  />
                </VCol>

                <!-- 👉 CC Inr -->
                <VCol cols="3">
                  <AppTextField
                    v-model="editedItem.inr_cc"
                    type="number"
                    :label="$t('Label.Cc-Inr')"
                    :placeholder="$t('Label.Cc-Inr')"
                  />
                </VCol>
              </VRow>
              <VRow>
                <!-- 👉 Periodo -->
                <VCol cols="3">
                  <AppTextField
                    v-model="editedItem.ckm_ofc"
                    type="number"
                    :label="$t('Label.Ofc-Ckm')"
                    :placeholder="$t('Label.Ofc-Ckm')"
                  />
                </VCol>

                <!-- 👉 CC Ckm -->
                <VCol cols="3">
                  <AppTextField
                    v-model="editedItem.kfkm_ofc"
                    type="number"
                    :label="$t('Label.Ofc-KfKm')"
                    :placeholder="$t('Label.Ofc-KfKm')"
                  />
                </VCol>

                <!-- 👉 CC Mil -->
                <VCol cols="3">
                  <AppTextField
                    v-model="editedItem.value_ofc"
                    type="number"
                    :label="$t('Label.Ofc-Mil €')"
                    :placeholder="$t('Label.Ofc-Mil €')"
                  />
                </VCol>

                <!-- 👉 CC Inr -->
                <VCol cols="3">
                  <AppTextField
                    v-model="editedItem.inr_ofc"
                    type="number"
                    :label="$t('Label.Ofc-Inr')"
                    :placeholder="$t('Label.Ofc-Inr')"
                  />
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

  <!-- 👉 Edit Dialog  -->
  <VDialog
    v-model="valueDialog"
    max-width="600px"
    persistent
  >
    <AppCardActions
      v-model:loading="isLoading"
      :title="$t('Label.Modifica-Agp')"
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
                <!-- 👉 Periodo -->
                <VCol cols="6">
                  <AppDateTimePicker
                    v-model="val.periodo"
                    :label="$t('Local.Periodo-Riferimento')"
                    :placeholder="$t('Local.Periodo-Riferimento')"
                    :config="{ shorthand: true, dateFormat: 'Y F', altFormat: 'Y F' }"
                    :readonly="true"
                  />
                </VCol>
              </VRow>
              <VRow>
                <!-- 👉 CC Ckm -->
                <VCol cols="6">
                  <AppTextField
                    v-model="val.target"
                    type="number"
                    :label="$t('Label.Agp')"
                    :placeholder="$t('Label.Agp')"
                  />
                </VCol>

                <!-- 👉 CC Mil -->
                <VCol cols="6">
                  <AppTextField
                    v-model="val.valore"
                    type="number"
                    :label="$t('Label.valore')"
                    :placeholder="$t('Label.valore')"
                  />
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
            @click="valueDialog = false"
          >
            Cancel
          </VBtn>

          <VBtn
            type="submit"
            color="success"
            variant="elevated"
            @click="saveValue"
          >
            Save
          </VBtn>
        </VCardActions>
      </VCard>
    </AppCardActions>
  </VDialog>
</template>
