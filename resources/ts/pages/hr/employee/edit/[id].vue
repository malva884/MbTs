<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { VForm } from 'vuetify/components/VForm'
import {can} from "@layouts/plugins/casl";
import DefineAbilities from "@/plugins/casl/DefineAbilities";

const defaultItem = ref<any>({
  id: '',
  nome: '',
  cognome: '',
  email: '',
  data_assunzione: '',
  data_nascita: '',
  data_ultima_visita: '',
  numero_anni_visita_medica: 0,
  tel: '',
  tel_az: '',
  dimesso: false,
  valutatore: false,
  ruolo_id: null,
  reparto_id: null,
  centro_id: null,
  matricola: '',
  sesso: '',
  company_id: '',
})

function new_defaultItem() {
  defaultItem.value = {
    id: '',
    nome: '',
    cognome: '',
    email: '',
    data_assunzione: '',
    data_nascita: '',
    data_ultima_visita: '',
    numero_anni_visita_medica: 0,
    tel: '',
    tel_az: '',
    dimesso: false,
    valutatore: false,
    ruolo_id: null,
    reparto_id: null,
    centro_id: null,
    matricola: '',
    sesso: '',
    company_id: '',
  }
}

const route = useRoute('hr-employee-edit-id')
const refForm = ref<VForm>()
const editedItem = ref<any>(defaultItem.value)
const loadingPage = ref(false)
const categorie = ref([])

const fetchUser = async () => {
  const { data: resultData } = await useApi<any>(createUrl(`/hr/dipendenti/view/${route.params.id}`))

  editedItem.value = resultData.value
  editedItem.value.valutatore = editedItem.value.valutatore === '1'
  editedItem.value.dimesso = editedItem.value.dimesso === '1'
}

fetchUser()

const save = async () => {
  refForm.value?.validate().then(async ({ valid }) => {
    if (valid) {
      loadingPage.value = true
      await $api(`/hr/dipendenti/update/${editedItem.value.id}`, {
        method: 'POST',
        body: editedItem.value,
      })

      nextTick(() => {
        refForm.value?.reset()
        refForm.value?.resetValidation()
      })
      loadingPage.value = false
      window.location.href = `/hr/employee/view/${editedItem.value.id}`
    }
  })
}

const centriOptions = ref([])
const repartiOptions = ref([])

const centroLoad = async () => {
  const resultData = await useApi<any>(createUrl('/hr/centro_di_costo/get_list'))

  const arr = []

  Object.keys(resultData.data.value).forEach(key => {
    arr.push({ text: resultData.data.value[key].centro_di_costo, value: resultData.data.value[key].id })
  })

  centriOptions.value = arr
}

centroLoad()

const repartiLoad = async () => {
  const resultData = await useApi<any>(createUrl('/hr/reparti/getList'))

  const arr = []

  Object.keys(resultData.data.value).forEach(key => {
    arr.push({ text: resultData.data.value[key].reparto, value: resultData.data.value[key].id })
  })

  repartiOptions.value = arr
}

repartiLoad()
</script>

<template>
  <VCard>
    <VCardText>
      <VForm
        ref="refForm"
        @submit.prevent="save"
      >
        <VRow>
          <VCol
            cols="12"
            md="2"
          >
            <AppSelect
              v-model="editedItem.company_id"
              :label="$t('Label.Azienda')"
              :placeholder="$t('Label.Azienda')"
              :rules="[requiredValidator]"
              :items="[{ title: 'Seleziona', value: '' }, { title: 'Metallurgica Brasciana', value: 'metallurgica' }, { title: 'Optotec', value: 'optotec' }]"
            />
          </VCol>

          <VCol
            cols="12"
            md="3"
          >
            <AppTextField
              v-model="editedItem.nome"
              :rules="[requiredValidator]"
              :label="$t('Label.Nome')"
              :placeholder="$t('Label.Nome')"
            />
          </VCol>

          <VCol
            cols="12"
            md="3"
          >
            <AppTextField
              v-model="editedItem.cognome"
              :rules="[requiredValidator]"
              :label="$t('Label.Cognome')"
              :placeholder="$t('Label.Cognome')"
            />
          </VCol>

          <!-- 👉 Matricola -->
          <VCol
            cols="12"
            md="2"
          >
            <AppTextField
              v-model="editedItem.matricola"
              :rules="[requiredValidator]"
              :label="$t('Label.Matricola')"
              :placeholder="$t('Label.Matricola')"
            />
          </VCol>

          <VCol
            cols="12"
            md="2"
          >
            <AppSelect
              v-model="editedItem.sesso"
              :rules="[requiredValidator]"
              :label="$t('Label.Sesso')"
              :placeholder="$t('Label.Sesso')"
              :items="[{ title: 'Seleziona', value: '' }, { title: 'Maschio', value: 'm' }, { title: 'Femmina', value: 'f' }]"
            />
          </VCol>

          <!-- 👉 Email -->
          <VCol
            cols="12"
            md="4"
          >
            <AppTextField
              v-model="editedItem.email"
              :rules="[emailValidator]"
              :label="$t('Label.Email')"
              :placeholder="$t('Label.Email')"
            />
          </VCol>

          <!-- 👉 Data Di Nascita -->
          <VCol
            cols="12"
            md="2"
          >
            <AppDateTimePicker
              v-model="editedItem.data_nascita"
              :rules="[requiredValidator]"
              :label="$t('Label.Data-Nascita')"
              :placeholder="$t('Label.Data-Nascita')"
            />
          </VCol>

          <!-- 👉 Telefono -->
          <VCol
            cols="12"
            md="2"
          >
            <AppTextField
              v-model="editedItem.tel"
              :label="$t('Label.Telefono')"
              :placeholder="$t('Label.Telefono')"
            />
          </VCol>

          <!-- 👉 Telefono Aziendale -->
          <VCol
            cols="12"
            md="2"
          >
            <AppTextField
              v-model="editedItem.tel_az"
              :label="$t('Label.Telefono-Aziendale')"
              :placeholder="$t('Label.Telefono-Aziendale')"
            />
          </VCol>

          <!-- 👉 Data Assunzione -->
          <VCol
            cols="12"
            md="2"
          >
            <AppDateTimePicker
              v-model="editedItem.data_assunzione"
              :rules="[requiredValidator]"
              :label="$t('Label.Data-Assunzione')"
              :placeholder="$t('Label.Data-Assunzione')"
            />
          </VCol>

          <!-- 👉 Reparto -->
          <VCol
            cols="12"
            md="2"
          >
            <AppSelect
              v-model="editedItem.reparto_id"
              :rules="[requiredValidator]"
              :label="$t('Label.Reparto')"
              :placeholder="$t('Label.Reparto')"
              :items="repartiOptions"
              item-title="text"
              item-value="value"
            />
          </VCol>

          <!-- 👉 Centro Di Costo -->
          <VCol
            cols="12"
            md="2"
          >
            <AppSelect
              v-model="editedItem.centro_id"
              :rules="[requiredValidator]"
              :label="$t('Label.Centro-Di-Costo')"
              :placeholder="$t('Label.Centro-Di-Costo')"
              :items="centriOptions"
              item-title="text"
              item-value="value"
            />
          </VCol>

          <!-- 👉 Data Visita Medica -->
          <VCol
            cols="12"
            md="2"
          >
            <AppDateTimePicker
              v-model="editedItem.data_ultima_visita"
              :label="$t('Label.Data-Visita-Medica')"
              :placeholder="$t('Label.Data-Visita-Medica')"
            />
          </VCol>

          <!-- 👉 Ripetizione Visita Medica -->
          <VCol
            cols="12"
            md="1"
          >
            <AppTextField
              v-model="editedItem.numero_anni_visita_medica"
              :label="$t('Label.Anni-Ripetizione Visita')"
              :placeholder="$t('Label.Anni-Ripetizione Visita')"
              type="number"
            />
          </VCol>

          <!-- 👉 Valutatore -->
          <VCol
            cols="12"
            md="1"
            class="mt-8"
          >
            <VSwitch
              v-model="editedItem.valutatore"
              :label="$t('Label.Valutatore')"
            />
          </VCol>

          <!-- 👉 Dimesso -->
          <VCol
            cols="12"
            md="1"
            class="mt-8"
          >
            <VSwitch
              v-model="editedItem.dimesso"
              :label="$t('Label.Dimesso')"
            />
          </VCol>

          <VCol cols="12">
            <VBtn
              type="submit"
              @click="refForm?.validate()"
            >
              Save
            </VBtn>
          </VCol>
        </VRow>
      </VForm>
    </VCardText>
  </VCard>
  <LoadingStandBy v-model="loadingPage"></LoadingStandBy>
</template>

<style scoped lang="scss">

</style>
