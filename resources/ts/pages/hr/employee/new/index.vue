<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { VForm } from 'vuetify/components/VForm'

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

const refForm = ref<VForm>()
const editedItem = ref<any>(defaultItem.value)
const router = useRouter()
const loadingPage = ref(false)
const categorie = ref([])

const save = async () => {
  if (editedItem.value.nome) {
    loadingPage.value = true
    let path = '/hr/dipendenti/store'
    if (editedItem.value.id)
      path = `/hr/dipendenti/update/${editedItem.value.id}`

    const retuenData = await $api(path, {
      method: 'POST',
      body: editedItem.value,
    })

    nextTick(() => {
      refForm.value?.reset()
      refForm.value?.resetValidation()
    })
    loadingPage.value = false
    router.push(`/hr/employee/view/${retuenData.obj.id}`)
  }
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
        v-model="isFormValid"
      >
        <VRow>
          <!-- 👉 Company -->
          <VCol cols="2">
            <AppSelect
              v-model="editedItem.company_id"
              :label="$t('Label.Azienda')"
              :placeholder="$t('Label.Azienda')"
              :rules="[requiredValidator]"
              :items="[{ title: 'Seleziona', value: '' }, { title: 'Metallurgica Brasciana', value: 'metallurgica' }, { title: 'Optotec', value: 'optotec' }]"
            />
          </VCol>

          <!-- 👉 Nome -->
          <VCol cols="4">
            <AppTextField
              v-model="editedItem.nome"
              :rules="[requiredValidator]"
              :label="$t('Label.Nome')"
              :placeholder="$t('Label.Nome')"
            />
          </VCol>

          <!-- 👉 Cognome -->
          <VCol cols="4">
            <AppTextField
              v-model="editedItem.cognome"
              :rules="[requiredValidator]"
              :label="$t('Label.Cognome')"
              :placeholder="$t('Label.Cognome')"
            />
          </VCol>

          <!-- 👉 Sesso -->
          <VCol cols="2">
            <AppSelect
              v-model="editedItem.sesso"
              :rules="[requiredValidator]"
              :label="$t('Label.Sesso')"
              :placeholder="$t('Label.Sesso')"
              :items="[{ title: 'Seleziona', value: '' }, { title: 'Maschio', value: 'm' }, { title: 'Femmina', value: 'f' }]"
            />
          </VCol>

          <!-- 👉 Matricola -->
          <VCol cols="2">
            <AppTextField
              v-model="editedItem.matricola"
              :rules="[requiredValidator]"
              :label="$t('Label.Matricola')"
              :placeholder="$t('Label.Matricola')"
            />
          </VCol>

          <!-- 👉 Email -->
          <VCol cols="4">
            <AppTextField
              v-model="editedItem.email"
              :rules="[emailValidator]"
              :label="$t('Label.Email')"
              :placeholder="$t('Label.Email')"
            />
          </VCol>

          <!-- 👉 Data Di Nascita -->
          <VCol cols="3">
            <AppDateTimePicker
              v-model="editedItem.data_nascita"
              :rules="[requiredValidator]"
              :label="$t('Label.Data-Nascita')"
              :placeholder="$t('Label.Data-Nascita')"
            />
          </VCol>

          <!-- 👉 Telefono -->
          <VCol cols="3">
            <AppTextField
              v-model="editedItem.tel"
              :label="$t('Label.Telefono')"
              :placeholder="$t('Label.Telefono')"
            />
          </VCol>

          <!-- 👉 Telefono Aziendale -->
          <VCol cols="2">
            <AppTextField
              v-model="editedItem.tel_az"
              :label="$t('Label.Telefono-Aziendale')"
              :placeholder="$t('Label.Telefono-Aziendale')"
            />
          </VCol>

          <!-- 👉 Data Assunzione -->
          <VCol cols="2">
            <AppDateTimePicker
              v-model="editedItem.data_assunzione"
              :rules="[requiredValidator]"
              :label="$t('Label.Data-Assunzione')"
              :placeholder="$t('Label.Data-Assunzione')"
            />
          </VCol>

          <!-- 👉 Reparto -->
          <VCol cols="3">
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

          <!-- 👉 Ruolo -->
          <VCol cols="3">
            <AppSelect
              v-model="editedItem.ruolo_id"
              :label="$t('Label.Ruolo')"
              :placeholder="$t('Label.Ruolo')"
              :items="categorie"
              item-title="text"
              item-value="value"
            />
          </VCol>

          <!-- 👉 Centro Di Costo -->
          <VCol cols="3">
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
          <VCol cols="3">
            <AppDateTimePicker
              v-model="editedItem.data_ultima_visita"
              :label="$t('Label.Data-Visita-Medica')"
              :placeholder="$t('Label.Data-Visita-Medica')"
            />
          </VCol>

          <!-- 👉 Ripetizione Visita Medica -->
          <VCol cols="2">
            <AppTextField
              v-model="editedItem.numero_anni_visita_medica"
              :label="$t('Label.Anni-Ripetizione Visita')"
              :placeholder="$t('Label.Anni-Ripetizione Visita')"
              type="number"
            />
          </VCol>

          <!-- 👉 Valutatore -->
          <VCol
            cols="2"
            class="mt-8"
          >
            <VSwitch
              v-model="editedItem.valutatore"
              :label="$t('Label.Valutatore')"
            />
          </VCol>

          <!-- 👉 Dimesso -->
          <VCol
            cols="2"
            class="mt-8"
          >
            <VSwitch
              v-model="editedItem.dimesso"
              :label="$t('Label.Dimesso')"
            />
          </VCol>
        </VRow>
      </VForm>
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
  <LoadingStandBy v-model="loadingPage"></LoadingStandBy>
</template>

<style scoped lang="scss">

</style>
