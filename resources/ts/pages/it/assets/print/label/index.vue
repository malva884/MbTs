<script lang="ts" setup>
import QRCodeVue3 from 'qrcode-vue3'

definePage({
  meta: {
    layout: 'blank',
  },
})

const route = useRoute('it-assets-print-label')

interface Asset {
  id: string
  serial_number: string
  asset_tag: string
  brand: string
  model: string
  category: {
    name: string
  }
  location: {
    name: string
  }
}

const assetData = ref<Asset | null>(null)
const path = import.meta.env.VITE_BASE_URL_PORTALE
const key = ref(0)

const print = () => {
  window.print()
}

const fetchAsset = async () => {
  try {
    const resultData = await useApi<any>(`/it/assets/${route.query.id}`)
    assetData.value = resultData.data.value
    key.value = key.value + 1
  } catch (error) {
    console.error('Error fetching asset:', error)
  }
}

fetchAsset()

setTimeout(() => {
  print()
}, 2000)
</script>

<template>
  <VRow class="mt-0 print-row">
    <VCol cols="3" class="mt-0 print-row">
      <VCard :title="$t('Label.Etichetta-Asset')" class="d-print-none"/>
      <VCardText v-if="assetData">
        <div class="hero-image print-row mt-0">
          <VRow class="mt-0">
            <VCol cols="7" class="mt-0 mb--1 print-row">
              <VImg :src="`${path}images/custom/logo_stl.jpg`" />
              <p class="ml-5 mt-3" style="font-weight: 500; font-size: 0.700rem;margin-block-end: 0rem">S.no {{ assetData.serial_number }}</p>
              <p class="ml-5 mt-0" style="font-weight: 500; font-size: 0.700rem; margin-block-end: 0rem">{{ assetData.asset_tag }}</p>
              <p class="ml-5 mt-0" style="font-weight: 500; font-size: 0.700rem; margin-block-end: 0rem">{{ assetData.category.name }}</p>
            </VCol>
            <VCol cols="5" class="mt-0 mb--1">
              <QRCodeVue3
                :key="key"
                :width="90"
                :height="90"
                :value="assetData.serial_number"
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
          <VRow class="mt-0">
            <VCol cols="12" class="mt-0 mb-0">
              <p class="text-center" style="font-weight: 500; font-size: 1.100rem; margin-block-end: 0rem">+39 - 030 - 9771911</p>
              <VImg
                class="ml-1"
                :src="`${path}images/custom/logo_mb.png`"
                height="30"
                width="300"
              />
            </VCol>
          </VRow>
        </div>
      </VCardText>
    </VCol>
  </VRow>
</template>

<style>
.hero-image {
  height: 188.97637795px;
  width: 264.56692913px;
  background-color: white;
  color: black;
}
</style>
