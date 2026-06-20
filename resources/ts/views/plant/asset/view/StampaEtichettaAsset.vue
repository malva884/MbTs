<script lang="ts" setup>
import { VForm } from 'vuetify/components/VForm'
import QRCodeVue3 from 'qrcode-vue3'
import { can } from '@layouts/plugins/casl'
import DefineAbilities from '@/plugins/casl/DefineAbilities'

const props = defineProps<Props>()

definePage({
  meta: {
    layout: 'blank',
  },
})
interface Props {
  id: number
}

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
const path = import.meta.env.VITE_BASE_URL_PORTALE
const key = ref(0)

const fetchasset = async () => {
  const resultData = await useApi<any>(createUrl(`/pl/asset/view/${props.id}`))

  assetData.value = resultData.data.value
  assetData.value.monitoraggio_attivo = assetData.value.monitoraggio_attivo == '1' ? 'true' : 'false'
  key.value = key.value + 1
}

const print = () => {
  window.print()
}

onMounted(() => {
  fetchasset()
})
</script>

<template>
  <VRow>
    <VCol cols="3" class="print-row">
      <VCard :title="$t('Label.Etichetta-Asset')">
        <VCardText>
          <VBtn
            class="d-print-none"
            icon="tabler-printer"
            variant="outlined"
            color="success"
            @click="print"
          />
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
