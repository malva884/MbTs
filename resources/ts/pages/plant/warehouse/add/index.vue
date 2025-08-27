<script setup lang="ts">
import { useDropZone, useFileDialog, useObjectUrl } from '@vueuse/core'
import { ref } from 'vue'
import type { Warehouse } from '@/views/plant/warehouse/type'

const optionCounter = ref(1)
const item = ref<Warehouse>()
const dropZoneRef = ref<HTMLDivElement>()

const defaultItem = ref<Warehouse>({
  id: null,
  marca: '',
  descrizione: '',
  tipologia: null,
  pn_interno: '',
  pn_oem: '',
  quantita_minima: null,
  quantita: null,
  data_fornitura: '',
  prezzo: null,
  foto: null,
})

interface FileData {
  file: File
  url: string
}

const fileData = ref<FileData[]>([])
const { open, onChange } = useFileDialog({ accept: 'image/*' })

function onDrop(DroppedFiles: File[] | null) {
  DroppedFiles?.forEach(file => {
    if (file.type.slice(0, 6) !== 'image/') {
      // eslint-disable-next-line no-alert
      alert('Only image files are allowed')

      return
    }

    fileData.value.push({
      file,
      url: useObjectUrl(file).value ?? '',
    })
  },
  )
}

onChange(selectedFiles => {
  if (!selectedFiles)
    return

  for (const file of selectedFiles) {
    fileData.value.push({
      file,
      url: useObjectUrl(file).value ?? '',
    })
  }
})

useDropZone(dropZoneRef, onDrop)

const content = ref('')

const activeTab = ref('Restock')
const isTaxChargeToProduct = ref(true)

const shippingList = [
  {
    desc: 'You\'ll be responsible for product delivery.Any damage or delay during shipping may cost you a Damage fee',
    title: 'Fulfilled by Seller',
    value: 'Fulfilled by Seller',
  },
  {
    desc: 'Your product, Our responsibility.For a measly fee, we will handle the delivery process for you.',
    title: 'Fulfilled by Company name',
    value: 'Fulfilled by Company name',
  },
] as const

const shippingType = ref<typeof shippingList[number]['value']>('Fulfilled by Company name')
const deliveryType = ref('Worldwide delivery')
const selectedAttrs = ref(['Biodegradable', 'Expiry Date'])

const inventoryTabsData = [
  { icon: 'tabler-cube', title: 'Restock', value: 'Restock' },
  { icon: 'tabler-lock', title: 'Advanced', value: 'Advanced' },
]

const save = async () => {

  const retuenData = await $api('/pl/warehouse/store', {
    method: 'POST',
    body: defaultItem.value,
  })

  if (retuenData.success === 200)
    window.location.href = `/plant/warehouse/view/${retuenData.obj.id}`
}
</script>

<template>
  <div>
    <div class="d-flex flex-wrap justify-start justify-sm-space-between gap-y-4 gap-x-6 mb-6">
      <div class="d-flex flex-column justify-center">
        <h4 class="text-h4 font-weight-medium">
          {{ $t('Label.Nuovo-Prodotto') }}
        </h4>
      </div>

      <div class="d-flex gap-4 align-center flex-wrap">
        <VBtn
          variant="tonal"
          color="success"
          @click="save"
        >
          {{ $t('Label.Salva') }}
        </VBtn>
      </div>
    </div>

    <VRow>
      <VCol md="8">
        <!-- 👉 Product Information -->
        <VCard
          class="mb-6"
          :title="$t('Label.Info-Prodotto')"
        >
          <VCardText>
            <VRow>
              <VCol cols="12">
                <AppTextField
                  v-model="defaultItem.marca"
                  :label="$t('Label.Marca')"
                  :placeholder="$t('Label.Marca')"
                />
              </VCol>
              <VCol
                cols="12"
                md="6"
              >
                <AppTextField
                  v-model="defaultItem.pn_interno"
                  :label="$t('Label.Pn-interno')"
                  :placeholder="$t('Label.Pn-interno')"
                />
              </VCol>
              <VCol
                cols="12"
                md="6"
              >
                <AppTextField
                  v-model="defaultItem.pn_oem"
                  :label="$t('Label.Pn-Oem')"
                  :placeholder="$t('Label.Pn-Oem')"
                />
              </VCol>
              <VCol>
                <AppTextField
                  v-model="defaultItem.descrizione"
                  :label="$t('Label.Descrizione')"
                  :placeholder="$t('Label.Descrizione')"
                />
              </VCol>
            </VRow>
          </VCardText>
        </VCard>
      </VCol>

      <VCol
        md="4"
        cols="12"
      >
        <!-- 👉 Organize -->
        <VCard
          :title="$t('Label.Info')"
          class="mb-6"
        >
          <VCardText>
            <div class="d-flex flex-column gap-y-4">
              <div>
                <VLabel class="d-flex">
                  <div class="d-flex text-sm justify-space-between w-100">
                    <div class="text-high-emphasis">
                      {{ $t('Label.Tipologia') }}
                    </div>
                    <div class="text-primary cursor-pointer">
                      {{ $t('Label.Nuova-Tipologia') }}
                    </div>
                  </div>
                </VLabel>

                <AppSelect
                  v-model="defaultItem.tipologia "
                  :placeholder="$t('Label.Tipologia')"
                  :items="['AC Power', 'Adapter', 'Cable', 'Desktop', 'Device', 'Laptop', 'HUB']"
                />
              </div>
              <AppTextField
                v-model="defaultItem.quantita_minima"
                :label="$t('Label.Quantita-Minima')"
                :placeholder="$t('Label.Quantita-Minima')"
              />
              <AppTextField
                v-model="defaultItem.quantita"
                :label="$t('Label.Quantita')"
                :placeholder="$t('Label.Quantita')"
              />
              <AppTextField
                v-model="defaultItem.prezzo"
                :label="$t('Label.Prezzo')"
                :placeholder="$t('Label.Prezzo')"
                class="mb-6"
              />
            </div>
          </VCardText>
        </VCard>

      </VCol>
    </VRow>
  </div>
</template>

<style lang="scss" scoped>
.drop-zone {
  border: 2px dashed rgba(var(--v-theme-on-surface), 0.12);
  border-radius: 6px;
}
</style>

<style lang="scss">
.inventory-card {
  .v-radio-group,
  .v-checkbox {
    .v-selection-control {
      align-items: start !important;

      .v-selection-control__wrapper {
        margin-block-start: -0.375rem !important;
      }
    }

    .v-label.custom-input {
      border: none !important;
    }
  }

  .v-tabs.v-tabs-pill {
    .v-slide-group-item--active.v-tab--selected.text-primary {
      h6 {
        color: #fff !important
      }
    }
  }

}

.ProseMirror {
  p {
    margin-block-end: 0;
  }

  padding: 0.5rem;
  outline: none;

  p.is-editor-empty:first-child::before {
    block-size: 0;
    color: #adb5bd;
    content: attr(data-placeholder);
    float: inline-start;
    pointer-events: none;
  }
}
</style>
