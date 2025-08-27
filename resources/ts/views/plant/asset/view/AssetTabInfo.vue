<script lang="ts" setup>
import { VForm } from 'vuetify/components/VForm'
import QRCodeVue3 from 'qrcode-vue3'
import { can } from '@layouts/plugins/casl'
import DefineAbilities from '@/plugins/casl/DefineAbilities'

interface Props {
  id: number
}

const props = defineProps<Props>()
const router = useRouter()

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
  monitoraggio_attivo: boolean
  anydesk_alias: string | null
}

const assetData = ref<Asset>({})
const edit = ref(false)
const loadingPage = ref(false)
const message = ref('')
const color = ref('')
const isSnackbarScrollReverseVisible = ref(false)
const path = import.meta.env.VITE_BASE_URL_PORTALE
const key = ref(0)

const checkboxContent: CustomInputContent[] = [
  {
    title: 'Monitoriggio Attivo',
    desc: 'Attiva il Controllo Del Dispositivo',
    value: 'true',
    icon: { icon: 'tabler-heart-rate-monitor', size: '28' },
  },
]

const fetchasset = async () => {
  const resultData = await useApi<any>(createUrl(`/pl/asset/view/${props.id}`))

  assetData.value = resultData.data.value
  assetData.value.monitoraggio_attivo = assetData.value.monitoraggio_attivo == '1' ? 'true' : 'false'
  key.value = key.value + 1
}

const editAssetData = async () => {
  if (assetData.value.numero_seriale) {
    loadingPage.value = true

    const retuenData = await $api(`/pl/asset/update/${assetData.value.id}`, {
      method: 'POST',
      body: assetData.value,
    })

    message.value = retuenData.message
    color.value = retuenData.color
    isSnackbarScrollReverseVisible.value = true

    loadingPage.value = false
    edit.value = false
  }
}

const editAsset = () => {
  edit.value = true
}

const print = () => {
  const printRedirect = router.resolve({ path: '/plant/asset/print/etichetta', query: { ids: assetData.value.id } })

  window.open(printRedirect.href, '_blank')
}

onMounted(() => {
  fetchasset()
})
</script>

<template>
  <VRow>
    <VSnackbar
      v-model="isSnackbarScrollReverseVisible"
      transition="scroll-y-reverse-transition"
      location="top central"
      :color="color"
    >
      {{ $t(message) }}
    </VSnackbar>
    <VCol cols="12" class="d-print-none">
      <VCard :title="$t('Label.Info-Asset')">
        <VCardText>
          <VForm
            ref="form"
            enctype="multipart/form-data"
            lazy-validation
            @submit.prevent="editAssetData"
          >
            <VRow class="mt-5 ml-5 mr-5">
              <VCol
                cols="12"
                md="2"
              >
                <AppTextField
                  v-model="assetData.codice_asset"
                  :label="$t('Label.Codice-Asset')"
                  :placeholder="$t('Label.Codice-Asset')"
                  :readonly="!edit"
                />
              </VCol>

              <VCol
                cols="12"
                md="3"
              >
                <AppTextField
                  v-model="assetData.utente"
                  :label="$t('Label.Utente')"
                  :placeholder="$t('Label.Utente')"
                  :rules="[requiredValidator]"
                  :readonly="!edit"
                />
              </VCol>

              <!-- 👉 email -->
              <VCol
                cols="12"
                md="3"
              >
                <AppTextField
                  v-model="assetData.email"
                  :label="$t('Label.Email')"
                  :placeholder="$t('Label.Email')"
                  :readonly="!edit"
                />
              </VCol>

              <VCol
                cols="12"
                md="3"
              >
                <AppTextField
                  v-model="assetData.hostName"
                  :label="$t('Label.Host-Name')"
                  :placeholder="$t('Label.Host-Name')"
                  :readonly="!edit"
                />
              </VCol>

              <VCol
                cols="12"
                md="3"
              >
                <AppTextField
                  v-model="assetData.nome_utente_effetivo"
                  :label="$t('Label.Nome-Utete-Effettivo')"
                  :placeholder="$t('Label.Nome-Utete-Effettivo')"
                  :readonly="!edit"
                />
              </VCol>

              <VCol
                cols="12"
                md="2"
              >
                <AppDateTimePicker
                  v-if="edit"
                  v-model="assetData.data_allocazione"
                  :label="$t('Label.Data-Allocazione')"
                  :placeholder="$t('Label.Data-Allocazione')"
                />
                <AppTextField
                  v-else
                  v-model="assetData.data_allocazione"
                  :label="$t('Label.Data-Allocazione')"
                  :placeholder="$t('Label.Data-Allocazione')"
                  :readonly="!edit"
                />
              </VCol>

              <VCol
                cols="12"
                md="2"
              >
                <AppTextField
                  v-model="assetData.numero_seriale"
                  :label="$t('Label.Numero-Seriale')"
                  :placeholder="$t('Label.Numero-Seriale')"
                  :rules="[requiredValidator]"
                  :readonly="!edit"
                />
              </VCol>

              <VCol
                cols="12"
                md="2"
              >
                <AppSelect
                  v-model="assetData.condizione_asset"
                  :items="[{ value: 'Good', text: 'Good' }, { value: 'Faulty', text: 'Faulty' }]"
                  :rules="[requiredValidator]"
                  item-title="text"
                  item-value="value"
                  :label="$t('Label.Condizione-Asset')"
                  :placeholder="$t('Label.Condizione-Asset')"
                  :readonly="!edit"
                />
              </VCol>

              <VCol
                cols="12"
                md="2"
              >
                <AppSelect
                  v-model="assetData.tipo_asset"
                  :items="[{ value: 'Desktop', text: 'Desktop' }, { value: 'Firewall', text: 'Firewall' }, { value: 'Laptop', text: 'Laptop' }, { value: 'Server', text: 'Server' }, { value: 'Storage', text: 'Storage' }, { value: 'Switch', text: 'Switch' }, { value: 'Printer', text: 'Printer' }, { value: 'WIFI', text: 'WIFI' }]"
                  :rules="[requiredValidator]"
                  item-title="text"
                  item-value="value"
                  :label="$t('Label.Tipo-Asset')"
                  :placeholder="$t('Label.Tipo-Asset')"
                  :readonly="!edit"
                />
              </VCol>

              <VCol
                cols="12"
                md="3"
              >
                <AppTextField
                  v-model="assetData.anydesk_alias"
                  :label="$t('Label.Alias-Anydesk')"
                  :placeholder="$t('Label.Alias-Anydesk')"
                  :readonly="!edit"
                />
              </VCol>

              <VCol
                cols="12"
                md="3"
              >
                <AppTextField
                  v-model="assetData.marca"
                  :label="$t('Label.Marca')"
                  :placeholder="$t('Label.Marca')"
                  :rules="[requiredValidator]"
                  :readonly="!edit"
                />
              </VCol>

              <VCol
                cols="12"
                md="3"
              >
                <AppTextField
                  v-model="assetData.modello"
                  :label="$t('Label.Modello')"
                  :placeholder="$t('Label.Modello')"
                  :rules="[requiredValidator]"
                  :readonly="!edit"
                />
              </VCol>

              <VCol
                cols="12"
                md="3"
              >
                <AppTextField
                  v-model="assetData.cpu"
                  :label="$t('Label.Cpu')"
                  :placeholder="$t('Label.Cpu')"
                  :rules="[requiredValidator]"
                  :readonly="!edit"
                />
              </VCol>
              <VCol
                cols="12"
                md="1"
              >
                <AppTextField
                  v-model="assetData.cpu_numero"
                  type="number"
                  :label="$t('Label.Cpu-N')"
                  :placeholder="$t('Label.Cpu-N')"
                  :readonly="!edit"
                />
              </VCol>

              <VCol
                cols="12"
                md="1"
              >
                <AppTextField
                  v-model="assetData.hdd_capienza"
                  :label="$t('Label.Hdd-Capienza')"
                  :placeholder="$t('Label.Hdd-Capienza')"
                  :readonly="!edit"
                />
              </VCol>

              <VCol
                cols="12"
                md="1"
              >
                <AppTextField
                  v-model="assetData.hdd_numero"
                  type="number"
                  :label="$t('Label.Hdd-N')"
                  :placeholder="$t('Label.Hdd-N')"
                  :readonly="!edit"
                />
              </VCol>

              <VCol
                cols="12"
                md="1"
              >
                <AppTextField
                  v-model="assetData.ram_memoria"
                  :label="$t('Label.Ram-Memoria')"
                  :placeholder="$t('Label.Ram-Memoria')"
                  :readonly="!edit"
                />
              </VCol>

              <VCol
                cols="12"
                md="1"
              >
                <AppTextField
                  v-model="assetData.ram_numero"
                  type="number"
                  :label="$t('Label.Ram-N')"
                  :placeholder="$t('Label.Ram-N')"
                  :readonly="!edit"
                />
              </VCol>

              <VCol
                cols="12"
                md="2"
              >
                <AppDateTimePicker
                  v-if="edit"
                  v-model="assetData.data_dismesso"
                  :label="$t('Label.Data-Dismesso')"
                  :placeholder="$t('Label.Data-Dismesso')"
                />
                <AppTextField
                  v-else
                  v-model="assetData.data_dismesso"
                  :label="$t('Label.Data-Dismesso')"
                  :placeholder="$t('Label.Data-Dismesso')"
                  :readonly="!edit"
                />
              </VCol>
              <VCol
                cols="12"
                md="3"
              >
                <AppTextarea
                  v-model="assetData.motivazione_dismesso"
                  :label="$t('Label.Motivazione-Dismesso')"
                  :placeholder="$t('Label.Motivazione-Dismesso')"
                  :readonly="!edit"
                />
              </VCol>

              <VCol
                cols="12"
                md="2"
              >
                <AppTextField
                  v-model="assetData.ip_address"
                  :label="$t('Label.Ip')"
                  :placeholder="$t('Label.Ip')"
                  :readonly="!edit"
                />
              </VCol>

              <VCol
                cols="12"
                md="4"
              >
                <CustomCheckboxesWithIcon
                  v-model:selected-checkbox="assetData.monitoraggio_attivo"
                  :checkbox-content="checkboxContent"
                  :grid-column="{ sm: '4', cols: '12' }"
                />
              </VCol>
              <VCol
                v-if="edit"
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
              <VCol
                v-else-if="can(DefineAbilities.asset_edit.action, DefineAbilities.asset_edit.subject)"
                cols="12"
                class="d-flex flex-wrap gap-4"
              >
                <VIcon
                  color="success"
                  icon="tabler-edit"
                  @click="editAsset"
                />
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
        <VDivider />
      </VCard>
    </VCol>
    <VCol cols="3">
      <VCard :title="$t('Label.Etichetta-Asset')">
        <VBtn
          class="d-print-none"
          icon="tabler-printer"
          variant="outlined"
          color="success"
          @click="print"
        />
        <VCardText>
          <div class="hero-image print-row">
            <VRow>
              <VCol cols="7" class="mb--1">
                <VImg :src="`${path}images/custom/logo_stl.jpg`" />
                <h6 class="ml-5 mt-3">S.no {{ assetData.numero_seriale }}</h6>
                <h6 class="ml-5 ">ITALY {{ assetData.codice_asset }}</h6>
              </VCol>
              <VCol cols="5" class="mb--1">
                <QRCodeVue3
                  :key="key"
                  :width="90"
                  :height="90"
                  :value="assetData.numero_seriale"
                  :qr-options="{ typeNumber: 0, mode: 'Byte', errorCorrectionLevel: 'H' }"
                  :image-options="{ hideBackgroundDots: true, imageSize: 0.4, margin: 0 }"
                  :dots-options="{
                  type: 'dots',
                  color: '#000000',
                  gradient: {
                    type: 'linear',
                    rotation: 0,
                    colorStops: [
                      { offset: 0, color: '#000000' },
                      { offset: 1, color: '#000000' },
                    ],
                  },
                }"
                  :background-options="{ color: '#ffffff' }"
                  :corners-square-options="{ type: 'dot', color: '#000000' }"
                  :corners-dot-options="{ type: undefined, color: '#000000' }"
                />
              </VCol>
            </VRow>
            <VRow>
              <VCol cols="12" class="mt--2">
                <h4 class="text-center">+39 - 030 - 9771911</h4>
                <h4 class="text-center">Sterlite Tecnologies Limited</h4>
              </VCol>
            </VRow>
          </div>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>

  <LoadingStandBy v-model="loadingPage" />
</template>

<style>
.hero-image {

  /* Set a specific height */
  height: 188.97637795px;
  width: 264.56692913px;

  /* Position and center the image to scale nicely on all screens */
  background-color: white;
  color: black;
}

</style>
