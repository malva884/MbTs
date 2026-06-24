<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { VForm } from 'vuetify/components/VForm'

definePage({
  meta: {
    action: 'create',
    subject: 'Employee',
  },
})

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
  ruolo_ids: [],
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
    ruolo_ids: [],
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

const close = () => {
  router.push({ name: 'hr-employee-list' })
}

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

const rolesLoad = async () => {
  const resultData = await useApi<any>(createUrl('/hr/gestione/ruoli/get_list'))

  const arr = []

  if (resultData.data.value) {
    resultData.data.value.forEach(value => {
      arr.push({ text: value.ruolo, value: value.id })
    })
  }

  categorie.value = arr
}

rolesLoad()
</script>

<template>
  <div class="workspace-container w-100 d-flex flex-column pa-4 gap-3">
    <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
      <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
        <div class="d-flex align-center gap-2">
          <VAvatar color="primary" variant="tonal" size="38">
            <VIcon icon="tabler-user-plus" size="20" />
          </VAvatar>
          <div>
            <div class="text-h6 font-weight-medium">Nuovo Dipendente</div>
            <div class="text-caption text-medium-emphasis">Inserisci i dati anagrafici e lavorativi per registrare il dipendente</div>
          </div>
        </div>
      </VCardText>
      <VDivider />

      <VCardText class="pa-4">
        <VForm
          ref="refForm"
          v-model="isFormValid"
        >
          <VRow>
            <!-- 👉 Company -->
            <VCol cols="12" md="3">
              <AppSelect
                v-model="editedItem.company_id"
                :label="$t('Label.Azienda')"
                :placeholder="$t('Label.Azienda')"
                :rules="[requiredValidator]"
                :items="[{ title: 'Metallurgica Brasciana', value: 'metallurgica' }, { title: 'Optotec', value: 'optotec' }]"
              />
            </VCol>

            <!-- 👉 Nome -->
            <VCol cols="12" md="3">
              <AppTextField
                v-model="editedItem.nome"
                :rules="[requiredValidator]"
                :label="$t('Label.Nome')"
                placeholder="Nome"
              />
            </VCol>

            <!-- 👉 Cognome -->
            <VCol cols="12" md="3">
              <AppTextField
                v-model="editedItem.cognome"
                :rules="[requiredValidator]"
                :label="$t('Label.Cognome')"
                placeholder="Cognome"
              />
            </VCol>

            <!-- 👉 Sesso -->
            <VCol cols="12" md="3">
              <AppSelect
                v-model="editedItem.sesso"
                :rules="[requiredValidator]"
                :label="$t('Label.Sesso')"
                placeholder="Seleziona sesso"
                :items="[{ title: 'Maschio', value: 'm' }, { title: 'Femmina', value: 'f' }]"
              />
            </VCol>

            <!-- 👉 Matricola -->
            <VCol cols="12" md="3">
              <AppTextField
                v-model="editedItem.matricola"
                :rules="[requiredValidator]"
                :label="$t('Label.Matricola')"
                placeholder="Matricola"
              />
            </VCol>

            <!-- 👉 Email -->
            <VCol cols="12" md="3">
              <AppTextField
                v-model="editedItem.email"
                :rules="[emailValidator]"
                :label="$t('Label.Email')"
                placeholder="Email"
              />
            </VCol>

            <!-- 👉 Data Di Nascita -->
            <VCol cols="12" md="3">
              <AppDateTimePicker
                v-model="editedItem.data_nascita"
                :rules="[requiredValidator]"
                :label="$t('Label.Data-Nascita')"
                placeholder="Data di nascita"
              />
            </VCol>

            <!-- 👉 Telefono -->
            <VCol cols="12" md="3">
              <AppTextField
                v-model="editedItem.tel"
                :label="$t('Label.Telefono')"
                placeholder="Telefono cellulare"
              />
            </VCol>

            <!-- 👉 Telefono Aziendale -->
            <VCol cols="12" md="3">
              <AppTextField
                v-model="editedItem.tel_az"
                :label="$t('Label.Telefono-Aziendale')"
                placeholder="Telefono aziendale"
              />
            </VCol>

            <!-- 👉 Data Assunzione -->
            <VCol cols="12" md="3">
              <AppDateTimePicker
                v-model="editedItem.data_assunzione"
                :rules="[requiredValidator]"
                :label="$t('Label.Data-Assunzione')"
                placeholder="Data assunzione"
              />
            </VCol>

            <!-- 👉 Reparto -->
            <VCol cols="12" md="3">
              <AppSelect
                v-model="editedItem.reparto_id"
                :rules="[requiredValidator]"
                :label="$t('Label.Reparto')"
                placeholder="Seleziona reparto"
                :items="repartiOptions"
                item-title="text"
                item-value="value"
              />
            </VCol>

            <!-- 👉 Ruolo -->
            <VCol cols="12" md="3">
              <AppSelect
                v-model="editedItem.ruolo_ids"
                :rules="[requiredValidator]"
                :label="$t('Label.Ruolo')"
                placeholder="Seleziona ruolo"
                multiple
                chips
                closable-chips
                :items="categorie"
                item-title="text"
                item-value="value"
              />
            </VCol>

            <!-- 👉 Centro Di Costo -->
            <VCol cols="12" md="3">
              <AppSelect
                v-model="editedItem.centro_id"
                :rules="[requiredValidator]"
                :label="$t('Label.Centro-Di-Costo')"
                placeholder="Centro di costo"
                :items="centriOptions"
                item-title="text"
                item-value="value"
              />
            </VCol>

            <!-- 👉 Data Visita Medica -->
            <VCol cols="12" md="3">
              <AppDateTimePicker
                v-model="editedItem.data_ultima_visita"
                :label="$t('Label.Data-Visita-Medica')"
                placeholder="Data ultima visita"
              />
            </VCol>

            <!-- 👉 Ripetizione Visita Medica -->
            <VCol cols="12" md="2">
              <AppTextField
                v-model="editedItem.numero_anni_visita_medica"
                :label="$t('Label.Anni-Ripetizione Visita')"
                placeholder="Anni validità"
                type="number"
              />
            </VCol>

            <!-- 👉 Valutatore -->
            <VCol
              cols="12"
              md="2"
              class="d-flex align-center mt-md-4"
            >
              <VSwitch
                v-model="editedItem.valutatore"
                :label="$t('Label.Valutatore')"
                color="primary"
              />
            </VCol>

            <!-- 👉 Dimesso -->
            <VCol
              cols="12"
              md="2"
              class="d-flex align-center mt-md-4"
            >
              <VSwitch
                v-model="editedItem.dimesso"
                :label="$t('Label.Dimesso')"
                color="error"
              />
            </VCol>
          </VRow>
        </VForm>
      </VCardText>
      <VDivider />

      <VCardActions class="pa-4 justify-end">
        <VBtn
          type="reset"
          color="error"
          variant="outlined"
          @click="close"
        >
          Annulla
        </VBtn>

        <VBtn
          type="submit"
          color="primary"
          variant="elevated"
          @click="save"
        >
          Salva
        </VBtn>
      </VCardActions>
    </VCard>
    <LoadingStandBy v-model="loadingPage"></LoadingStandBy>
  </div>
</template>

<style scoped lang="scss">

</style>
