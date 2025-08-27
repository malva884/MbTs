<script lang="ts" setup>
// eslint-disable-next-line @typescript-eslint/consistent-type-imports
import type { VForm } from 'vuetify/components/VForm'
import {VDataTableServer} from "vuetify/labs/VDataTable";
import {useI18n} from "vue-i18n";

definePage({
  meta: {
    action: 'list',
    subject: 'Qualita-Fai',
  },
})

const { t } = useI18n()
const form = ref<VForm>()
const tipo = ref('prova di tipo')
const tipoProva = ['Prova di tipo', 'Fai']
const loading = ref(false)
const serverItems = ref<any>([])
const itemsPerPage = ref(-1)
const errors = ref({})
const proveTipoOptions = ref<any>([])
const standatrdOptions = ref<any>([])
const faiOptions = ref<any>([])
const hiddenFai = ref(true)
const file = ref(null)
const data = ref([])
const isDialogLoading = ref(false)
const router = useRouter()
const img = ref()

interface ProvaTipo {
  id: string | null
  ol: string | null
  fai: string | null
  materiale: string | null
  descrizione: string | null
  esito: string | null
  standard: null
  specifica: string | null
  tipo: null
  data_prova: string | null
  cliente: string | null
  note: string | null
  files_upload: []
  disable: boolean
}

const newItem = ref<ProvaTipo>({})

const loadItems = async (ol: string | null) => {
  loading.value = true
  if (ol !== '') {
    const { data: resultData, error } = await useApi<any>(createUrl(`/qt/prove_tipo/get_prove/${ol}`))

    if (resultData.value !== null)
      serverItems.value = resultData.value

    else
      serverItems.value = []

  }
  else
    serverItems.value = []
  loading.value = false
}

const headers = [
  { title: t('Table.Ordine'), key: 'ol', sortable: false },
  { title: t('Table.Materiale'), key: 'materiale', sortable: false },
  { title: t('Table.Esito'), key: 'esito', sortable: false },
  { title: t('Table.Standard'), key: 'standard', sortable: false },
  { title: t('Table.Specifica'), key: 'spcifica', sortable: false },
  { title: t('Table.Tipologia'), key: 'categoria', sortable: false },
  { title: t('Table.Data'), key: 'data_prova', sortable: false },
]

const onSubmit = async () => {

  newItem.value.files_upload = img.value
  if (newItem.value.ol && newItem.value.esito && newItem.value.standard && newItem.value.tipo && newItem.value.data_prova && newItem.value.files_upload) {
    isDialogLoading.value = true
    const retuenData = await $api('/qt/prove_tipo/stored', {
      method: 'POST',
      body: newItem.value,
    })


    isDialogLoading.value = false
    router.push('/quality/prove/tipo/list')
  }
}

const getMateriale = async () => {
  if (newItem.value.fai) {
    const found = faiOptions.value.find(element => element.id === newItem.value.fai)

    newItem.value.ol = found.ol
  }

  loadItems(newItem.value.ol)

  const resultData = await useApi<any>(createUrl(`/gp/getMateriale/${newItem.value.ol}`))

  newItem.value.materiale = resultData.data.value.Prodotto
  newItem.value.descrizione = resultData.data.value.Descrizione
  newItem.value.cliente = resultData.data.value.Cliente
}

const getTipoProve = async () => {
  const { data: resultData, error } = await useApi<any>(createUrl('/qt/categorie/get_categorie', {
    query: {
      modulo: 1, // tipo_prove
    },
  }))

  proveTipoOptions.value = resultData.value
}

const getStandardProve = async () => {
  const { data: resultData, error } = await useApi<any>(createUrl('/qt/categorie/get_categorie', {
    query: {
      modulo: 2, // Standard
    },
  }))

  if (resultData.value !== null)
    standatrdOptions.value = resultData.value
}

const getFai = async () => {
  const { data: resultData } = await useApi<any>(createUrl('/qt/fai/get_fai'))

  faiOptions.value = resultData.value
}

getFai()

const colorTipo = (tipo: string) => {
  if (tipo === 'Fai')
    return 'info'

  return 'success'
}

const checkTipo = () => {
  const temp = tipo.value
  form.value?.reset()
  newItem.value.data_prova = null
  serverItems.value = []
  tipo.value = temp
  if (tipo.value === 'fai') {
    hiddenFai.value = false
    olAbilitato.value = true
  }
  else {
    hiddenFai.value = true
    olAbilitato.value = false
  }
}

const resolveStatusVariant = (risultato: string) => {
  if (risultato === 'POSITIVO')
    return { color: 'success', text: 'POSITIVO' }
  else if (risultato === 'NEGATIVO')
    return { color: 'error', text: 'NEGATIVO' }
  else
    return { color: '', text: risultato }
}

const uploadFile = (event: any) => {

  for (let i = 0; i < event.target.files.length; i++) {
    file.value = event.target.files[i]
    let nameFile = file.value.name
    let ext = file.value.name?.substr(file.value.name?.lastIndexOf('.') + 1)
    let mimeTipe = file.value.type

    const reader = new FileReader()

    reader.readAsDataURL(file.value)
    reader.onload = async () => {
      const encodedFile = reader.result.split(',')[1]

      data.value.push({
        file: encodedFile,
        fileName: nameFile,
        fileExtension: ext,
        fileMimeType: mimeTipe,
      })
    }
  }
}

onMounted(() => {
  getTipoProve()
  getStandardProve()
})
</script>

<template>
  <!-- List -->
  <VList lines="two">
    <VListSubheader>Prove Ol</VListSubheader>

    <VDataTableServer
      v-model:items-per-page="itemsPerPage"
      :headers="headers"
      :items="serverItems"
      :loading="loading"
      class="text-no-wrap mb-0"
      density="compact"
    >
      <!-- risultato -->
      <template #item.esito="{ item }">
        <div class="d-flex gap-1" v-if="item.esito">
          <VChip
            :color="resolveStatusVariant(item.esito).color"
            size="small"
          >
            {{ resolveStatusVariant(item.esito).text }}
          </VChip>
        </div>
      </template>

      <template #bottom>
        <VCardText class="pt-2" style="display: block">
          <div class="d-flex flex-wrap justify-center justify-sm-space-between gap-y-2 mt-2">

          </div>
        </VCardText>
      </template>
    </VDataTableServer>
  </VList>
  <VForm
    ref="form"
    @submit.prevent="onSubmit"
    enctype="multipart/form-data"
    lazy-validation
  >
    <VRow class="mt-5 ml-5 mr-5">
      <VCol
        cols="12"
        md="5"
      />
      <!-- 👉 Ol -->
      <VCol
        cols="12"
        md="2"
      >
        <VRadioGroup
          v-model="tipo"
          inline
        >
          <div>
            <VRadio
              v-for="radio in tipoProva"
              :key="radio"
              :label="radio"
              :color="colorTipo(radio)"
              :value="radio.toLocaleLowerCase()"
              @change="checkTipo"
            />
          </div>
        </VRadioGroup>
      </VCol>
      <VCol
        cols="12"
        md="5"
      />
      <!-- 👉 Fai -->
      <VCol
        v-if="!hiddenFai"
        cols="12"
        md="2"
        class="mt-0"
      >
        <AppSelect
          v-model="newItem.fai"
          :items="faiOptions"
          :item-title="item => item.numero_fai"
          :item-value="item => item.id"
          :label="$t('Label.Fai')"
          :placeholder="$t('Label.Fai')"
          :readonly="!!newItem.disable"
          @focusout="getMateriale"
        />
      </VCol>
      <!-- 👉 Ol -->
      <VCol
        cols="12"
        md="2"
      >
        <AppTextField
          v-model="newItem.ol"
          :label="$t('Label.Numero Ordine')"
          :placeholder="$t('Label.Numero Ordine')"
          :rules="[requiredValidator]"
          :error-messages="errors.ol"
          :readonly="!!olAbilitato"
          @focusout="getMateriale"
        />
      </VCol>

      <VCol
        cols="12"
        md="2"
      >
        <AppTextField
          v-model="newItem.materiale"
          :label="$t('Label.Materiale')"
          :placeholder="$t('Label.Materiale')"
          readonly
        />
      </VCol>

      <!-- 👉 Cliente -->
      <VCol
        cols="12"
        md="2"
      >
        <AppTextField
          v-model="newItem.cliente"
          :label="$t('Label.Cliente')"
          :placeholder="$t('Label.Cliente')"
        />
      </VCol>

      <VCol
        cols="12"
        md="4"
      >
        <AppTextField
          v-model="newItem.descrizione"
          :label="$t('Label.Descrizione-Materiale')"
          :placeholder="$t('Label.Descrizione-Materiale')"
          readonly
        />
      </VCol>

      <VCol
        cols="12"
        md="2"
      >
        <AppSelect
          v-model="newItem.esito"
          :items="[{ value: 'POSITIVO', text: 'POSITIVO' }, { value: 'NEGATIVO', text: 'NEGATIVO' }]"
          :rules="[requiredValidator]"
          item-title="text"
          item-value="value"
          :label="$t('Label.Stato')"
          :placeholder="$t('Label.Stato')"
        />
      </VCol>

      <VCol
        cols="12"
        md="2"
      >
        <AppSelect
          v-model="newItem.standard"
          :items="standatrdOptions"
          :item-title="item => item.categoria"
          :item-value="item => item.categoria"
          :rules="[requiredValidator]"
          :label="$t('Label.Standard')"
          :placeholder="$t('Label.Standard')"
        />
      </VCol>

      <!-- 👉 Specifica -->
      <VCol
        cols="12"
        md="2"
      >
        <AppTextField
          v-model="newItem.specifica"
          :label="$t('Label.Specifica')"
          :placeholder="$t('Label.Specifica')"
        />
      </VCol>

      <!-- 👉 Tipologia Test -->
      <VCol
        cols="12"
        md="2"
      >
        <AppSelect
          v-model="newItem.tipo"
          :items="proveTipoOptions"
          :item-title="item => item.categoria"
          :item-value="item => item.id"
          :label="$t('Label.Tipologia')"
          :placeholder="$t('Label.Tipologia')"
          :rules="[requiredValidator]"
        />
      </VCol>

      <VCol
        cols="12"
        md="2"
      >
        <AppDateTimePicker
          v-model="newItem.data_prova"
          :rules="[requiredValidator]"
          :label="$t('Label.Data')"
          :placeholder="$t('Label.Data')"
        />
      </VCol>

      <!-- 👉 Note -->
      <VCol
        cols="12"
        md="6"
      >
        <AppTextarea
          v-model="newItem.note"
          :label="$t('Label.Note')"
          placeholder="Note"
          :readonly="!!newItem.disable"
          :error-messages="errors.note"
        />
      </VCol>

      <!-- 👉 Upload -->
      <VCol
        v-if="!newItem.disable"
        cols="12"
        md="6"
      >
        <VFileInput
          multiple
          v-model="img"
          accept="image/*,application/pdf"
          :label="$t('Label.File')"
          :rules="[requiredValidator]"
          @change="uploadFile"
        />
      </VCol>

      <VCol
        cols="12"
        class="d-flex flex-wrap gap-4"
      >
        <VBtn
          color="success"
          type="submit"
        >
          Salva
        </VBtn>
      </VCol>
    </VRow>
  </VForm>

  <!-- Dialog Loading -->
  <VDialog
    v-model="isDialogLoading"
    width="300"
  >
    <VCard
      color="primary"
      width="300"
    >
      <VCardText class="pt-3">
        <span class="ml-4 mb-3">Please stand by</span>
        <VProgressLinear
          :size="40"
          color="warning"
          class="mt-3"
          indeterminate
        />
      </VCardText>
    </VCard>
  </VDialog>
</template>
