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

const route = useRoute('plant-asset-view-id')

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

interface Asset {
  id: string | null
  codice_asset: string | null
  nazione: string | null
  stato: string | null
  condizione_asset: string | null
  utilizzo: string | null
  emp_id: string | null
  utente: string | null
  email: string | null
  data_allocazione: string | null
  scopo: string | null
  tipo_allocazione: string | null
  data_dismesso: string | null
  motivazione_dismesso: string | null
  hostName: string | null
  nome_utente_effetivo: string | null
  tipo_asset: string | null
  cpu: string | null
  cpu_numero: string | null
  hdd_capienza: string | null
  hdd_numero: string | null
  fattura_dt: null
  fattura_numero: string | null
  ip_address: string | null
  ultima_data_allocazione: string | null
  marca: string | null
  modello: string | null
  mause: string | null
  tipo_rete: string | null
  sistema_operativo: string | null
  ram_numero: boolean
  ram_memoria: string | null
  sap_codice_asset: string | null
  numero_seriale: string | null
  fine_garanzia: string | null
}

const newItem = ref<Asset>({})

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

onMounted(() => {

})
</script>

<template>
  <VForm
    ref="form"
    @submit.prevent="onSubmit"
    enctype="multipart/form-data"
    lazy-validation
  >
    <VRow class="mt-5 ml-5 mr-5">
      <!-- 👉 Ol -->
      <VCol
        cols="12"
        md="2"
      >
        <AppTextField
          v-model="newItem.codice_asset"
          :label="$t('Label.Codice-Asset')"
          :placeholder="$t('Label.Codice-Asset')"
          :rules="[requiredValidator]"
        />
      </VCol>

      <VCol
        cols="12"
        md="2"
      >
        <AppTextField
          v-model="newItem.utente"
          :label="$t('Label.Utente')"
          :placeholder="$t('Label.Utente')"
          :rules="[requiredValidator]"
        />
      </VCol>

      <!-- 👉 email -->
      <VCol
        cols="12"
        md="2"
      >
        <AppTextField
          v-model="newItem.email"
          :label="$t('Label.Email')"
          :placeholder="$t('Label.Email')"
        />
      </VCol>

      <VCol
        cols="12"
        md="2"
      >
        <AppTextField
          v-model="newItem.hostName"
          :label="$t('Label.Host-Name')"
          :placeholder="$t('Label.Host-Name')"
        />
      </VCol>

      <VCol
        cols="12"
        md="2"
      >
        <AppTextField
          v-model="newItem.numero_seriale"
          :label="$t('Label.Numero-Seriale')"
          :placeholder="$t('Label.Numero-Seriale')"
          :rules="[requiredValidator]"
        />
      </VCol>

      <VCol
        cols="12"
        md="2"
      >
        <AppSelect
          v-model="newItem.condizione_asset"
          :items="[{ value: 'Good', text: 'Good' }, { value: 'Faulty', text: 'Faulty' }]"
          :rules="[requiredValidator]"
          item-title="text"
          item-value="value"
          :label="$t('Label.Condizione-Asset')"
          :placeholder="$t('Label.Condizione-Asset')"
        />
      </VCol>

      <VCol
        cols="12"
        md="2"
      >
        <AppSelect
          v-model="newItem.tipo_asset"
          :items="standatrdOptions"
          :item-title="item => item.categoria"
          :item-value="item => item.categoria"
          :rules="[requiredValidator]"
          :label="$t('Label.Tipo-Asset')"
          :placeholder="$t('Label.Tipo-Asset')"
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
