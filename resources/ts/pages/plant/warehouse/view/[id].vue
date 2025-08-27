<script setup lang="ts">
import { ref } from 'vue'
import type { Provider } from '@/views/plant/warehouse/type'

const route = useRoute('plant-warehouse-view-id')

const obj = ref({})
const objInfo = ref({})
const fornitoreVisible = ref(false)
const assetDialog = ref(false)
const test = ref(false)
const nuovaQuantita = ref()
const isSnackbarScrollReverseVisible = ref(false)
const message = ref('')
const color = ref('')
const newProvider = ref<Provider>({})
const listNewAsset = ref({})
const listAsset = ref({})
const idRegistrazione = ref()
const quantitaDevice = ref(1)
const idAsset = ref()
const inventoryTabsData = [
  { icon: 'tabler-cube', title: 'Restock', value: 'Restock' },
]

const fetchObj = async () => {
  const { data: resultData } = await useApi<any>(createUrl(`/pl/warehouse/view/${route.params.id}`))
  const { data: resultDataInfo } = await useApi<any>(createUrl(`/pl/warehouse/getInfo/${route.params.id}`))
  const { data: resultDataAsset } = await useApi<any>(createUrl('/pl/asset/not_associated'))

  listNewAsset.value = resultDataAsset.value

  const { data: resultAsset } = await useApi<any>(createUrl('/pl/asset/associated'))

  listAsset.value = resultAsset.value

  obj.value = { ...resultData.value }
  objInfo.value = resultDataInfo.value

  if (obj.value.tipologia === 'Laptop' || obj.value.tipologia === 'Desktop')
    inventoryTabsData.push({ icon: 'tabler-device-imac', title: 'Asset', value: 'Asset-set' })

  if (obj.value.tipologia !== 'Laptop' && obj.value.tipologia !== 'Desktop')
    inventoryTabsData.push({ icon: 'tabler-headset', title: 'Device', value: 'Device' })


  test.value = true
}

fetchObj()


const activeTab = ref('Restock')


const save = async () => {
  const retuenData = await $api(`/pl/warehouse/update/${route.params.id}`, {
    method: 'POST',
    body: obj.value,
  })

  if (retuenData.success === 200) {
    fetchObj()
    message.value = retuenData.message
    color.value = retuenData.color
    isSnackbarScrollReverseVisible.value = true
  }
}

const nuovaFornitura = async () => {
  const retuenData = await $api(`/pl/warehouse/storeQuantity/${route.params.id}`, {
    method: 'POST',
    body: {
      quantita: nuovaQuantita.value,
    },
  })

  if (retuenData.success === 200) {
    nuovaQuantita.value = null
    fetchObj()
    message.value = retuenData.message
    color.value = retuenData.color
    isSnackbarScrollReverseVisible.value = true
  }
}

const nuovoFornitore = () => {
  fornitoreVisible.value = true
}

const saveFornitore = async () => {
  const retuenData = await $api(`/pl/warehouse/storeProvider/${route.params.id}`, {
    method: 'POST',
    body: newProvider.value,
  })

  message.value = retuenData.message
  color.value = retuenData.color
  isSnackbarScrollReverseVisible.value = true
  fetchObj()
  fornitoreVisible.value = false
}

const newAsset = async () => {
  const retuenData = await $api(`/pl/warehouse/register/${route.params.id}`, {
    method: 'POST',
    body: {
      idRegistrazione: idRegistrazione.value,
      quantita: 1,
    },
  })

  message.value = retuenData.message
  color.value = retuenData.color
  isSnackbarScrollReverseVisible.value = true
  fetchObj()
}

const deviceAsset = async () => {

  if (Number(quantitaDevice.value) <= Number(obj.value.quantita)) {

    /*
    const retuenData = await $api(`/pl/warehouse/devideAsset/${route.params.id}`, {
      method: 'POST',
      body: {
        idAsset: idAsset.value,
        quantita: quantitaDevice.value,
      },
    })

    idAsset.value = null
    quantitaDevice.value = 1
    message.value = retuenData.message
    color.value = retuenData.color
    isSnackbarScrollReverseVisible.value = true
     */
    fetchObj()
  }
  else {
    message.value = 'Quantita Insuficiente'
    color.value = 'error'
    isSnackbarScrollReverseVisible.value = true
  }
}

const euro = new Intl.NumberFormat('it-IT', {
  style: 'currency',
  currency: 'EUR',
})

onMounted(() => {


})
</script>

<template>
  <div v-if="test">
    <VSnackbar
      v-model="isSnackbarScrollReverseVisible"
      transition="scroll-y-reverse-transition"
      location="top central"
      :color="color"
    >
      {{ $t(message) }}
    </VSnackbar>
    <div class="d-flex flex-wrap justify-start justify-sm-space-between gap-y-4 gap-x-6 mb-6">
      <div class="d-flex flex-column justify-center">
        <h4 class="text-h4 font-weight-medium">
          {{ $t('Label.Dettaglio-Materiale') }}
        </h4>
      </div>

      <div class="d-flex gap-4 align-center flex-wrap">
        <VBtn
          variant="tonal"
          color="success"
          @click="save"
        >
          {{ $t('Button.Salva') }}
        </VBtn>
      </div>
    </div>

    <VRow>
      <VCol md="8">
        <!-- 👉 Product Information -->
        <VCard
          class="mb-6"
          :title="$t('Label.Info-Materiale')"
        >
          <VCardText>
            <VRow>
              <VCol cols="12">
                <AppTextField
                  v-model="obj.marca"
                  :label="$t('Label.Marca')"
                  :placeholder="$t('Label.Marca')"
                />
              </VCol>
              <VCol
                cols="12"
                md="12"
              >
                <AppTextField
                  v-model="obj.descrizione"
                  :label="$t('Label.Descrizione')"
                  :placeholder="$t('Label.Descrizione')"
                />
              </VCol>
              <VCol
                cols="12"
                md="6"
              >
                <AppTextField
                  v-model="obj.pn_interno"
                  :label="$t('Label.Pn-interno')"
                  :placeholder="$t('Label.Pn-interno')"
                />
              </VCol>
              <VCol
                cols="12"
                md="6"
              >
                <AppTextField
                  v-model="obj.pn_oem"
                  :label="$t('Label.Pn-Oem')"
                  :placeholder="$t('Label.Pn-Oem')"
                />
              </VCol>
            </VRow>
          </VCardText>
        </VCard>

        <!-- 👉 Inventory -->
        <VCard
          title="Inventory"
          class="inventory-card"
        >
          <VCardText>
            <VRow>
              <VCol
                cols="12"
                md="3"
              >
                <div class="pe-2">
                  <VTabs
                    v-model="activeTab"
                    direction="vertical"
                    color="primary"
                    class="v-tabs-pill"
                  >
                    <VTab
                      v-for="(tab, index) in inventoryTabsData"
                      :key="index"
                    >
                      <VIcon
                        :icon="tab.icon"
                        class="me-2"
                      />
                      <div class="text-truncate font-weight-medium text-start">
                        {{ tab.title }}
                      </div>
                    </VTab>
                  </VTabs>
                </div>
              </VCol>

              <VDivider :vertical="!$vuetify.display.smAndDown" />

              <VCol
                cols="12"
                md="9"
              >
                <VWindow
                  v-model="activeTab"
                  class="w-100"
                  :touch="false"
                >
                  <VWindowItem value="Restock">
                    <div class="d-flex flex-column gap-y-4 ps-3">
                      <h5 class="text-h5">
                        {{ $t('Label.Quantita') }}
                      </h5>

                      <div class="d-flex gap-x-4 align-center">
                        <AppTextField
                          v-model="nuovaQuantita"
                          type="number"
                          :label="$t('Label.Aggiungi-Al-Magazzino')"
                          :placeholder="$t('Label.Quantita')"
                          density="compact"
                        />
                        <VBtn
                          prepend-icon="tabler-check"
                          class="align-self-end"
                          @click="nuovaFornitura"
                        >
                          {{ $t('Button.Conferma') }}
                        </VBtn>
                      </div>

                      <div>
                        <div class="text-base text-high-emphasis font-weight-medium mb-1">
                          {{ $t('Label.Quantita-Magazino') }}: {{ obj.quantita }}
                        </div>
                        <div class="text-base text-high-emphasis font-weight-medium mb-1">
                          {{ $t('Label.Data-Ultima-Fornitura') }}: {{ obj.data_fornitura }}
                        </div>
                      </div>
                    </div>
                  </VWindowItem>

                  <VWindowItem
                    v-if="obj.tipologia === 'Desktop' || obj.tipologia === 'Laptop'"
                    value="Asset-set"
                  >
                    <div class="d-flex flex-column gap-y-4 ps-3">
                      <h5 class="text-h5">
                        {{ $t('Label.Registra-Assset') }}
                      </h5>

                      <div class="d-flex gap-x-4 align-center">
                        <AppAutocomplete
                          v-model="idRegistrazione"
                          :items="listNewAsset"
                          :label="$t('Label.Asset-Non-Registrati')"
                          :placeholder="$t('Label.Asset-Non-Registrati')"
                          item-title="titolo"
                          item-value="id"
                          clearable
                          clear-icon="tabler-x"
                        />
                        <VBtn
                          prepend-icon="tabler-check"
                          class="align-self-end"
                          color="warning"
                          :disabled="listNewAsset.length ? false : true"
                          @click="newAsset"
                        >
                          {{ $t('Button.Registra') }}
                        </VBtn>
                      </div>
                    </div>
                  </VWindowItem>
                  <VWindowItem
                    v-if="obj.tipologia !== 'Desktop' || obj.tipologia !== 'Laptop'"
                    value="device"
                  >
                    <div class="d-flex flex-column gap-y-4 ps-3">
                      <h5 class="text-h5">
                        {{ $t('Label.Asset') }}
                      </h5>

                      <div class="d-flex gap-x-4 align-center">
                        <AppAutocomplete
                          v-model="idAsset"
                          :items="listAsset"
                          :label="$t('Label.Asset')"
                          :placeholder="$t('Label.Asset')"
                          :menu-props="{ transition: 'scroll-y-transition' }"
                          item-title="titolo"
                          item-value="id"
                          clearable
                          clear-icon="tabler-x"
                        />
                        <AppTextField
                          v-model="quantitaDevice"
                          type="number"
                          :max="obj.quantita"
                          min="1"
                          :label="$t('Label.Quantita')"
                          :placeholder="$t('Label.Quantita')"
                        />
                        <VBtn
                          prepend-icon="tabler-check"
                          class="align-self-end"
                          color="warning"
                          :disabled="obj.quantita > 0 ? false : true "
                          @click="deviceAsset"
                        >
                          {{ $t('Button.Registra') }}
                        </VBtn>
                      </div>
                    </div>
                  </VWindowItem>
                </VWindow>
              </VCol>
            </VRow>
          </VCardText>
        </VCard>
      </VCol>

      <VCol
        md="4"
        cols="12"
      >
        <!-- 👉 Info -->
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
                  v-model="obj.tipologia "
                  :placeholder="$t('Label.Tipologia')"
                  :items="['AC Power', 'Adapter', 'Cable', 'Desktop', 'Device', 'Laptop', 'HUB']"
                />
              </div>
              <AppTextField
                v-model="obj.quantita_minima"
                type="number"
                :label="$t('Label.Quantita-Minima')"
                :placeholder="$t('Label.Quantita-Minima')"
              />
              <AppTextField
                v-model="obj.quantita"
                :label="$t('Label.Quantita')"
                :placeholder="$t('Label.Quantita')"
                readonly
              />
              <AppTextField
                v-model="obj.prezzo"
                type="number"
                :label="$t('Label.Prezzo')"
                :placeholder="$t('Label.Prezzo')"
                class="mb-6"
              />
            </div>
          </VCardText>
        </VCard>

        <!-- 👉 Info -->
        <VCard class="mb-6">
          <VCardItem>
            <template #title>
              {{ $t('Label.Fornitori') }}
            </template>
            <template #append>
              <span
                class="text-primary font-weight-medium text-sm cursor-pointer"
                @click="nuovoFornitore"
              >{{ $t('Label.Nuovo-Fornitore') }}</span>
            </template>
          </VCardItem>
          <VCardText>
            <div class="d-flex flex-column gap-y-4">
              <VTable
                height="250"
                class="text-no-wrap"
              >
                <thead>
                  <tr>
                    <th>
                      {{ $t('Table.Tipologia') }}
                    </th>
                    <th>
                      {{ $t('Table.Sito') }}
                    </th>
                    <th>
                      {{ $t('Table.Prezzo') }}
                    </th>
                  </tr>
                </thead>

                <tbody>
                  <tr
                    v-for="item in objInfo"
                    :key="item.id"
                  >
                    <td>
                      {{ item.tipologia }}
                    </td>
                    <td>
                      <a
                        :href="item.link"
                        target="_blank"
                      > {{ item.sito }} </a>
                    </td>
                    <td>
                      {{ euro.format(item.prezzo) }}
                    </td>
                  </tr>
                </tbody>
              </VTable>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>

  <!-- Nuovo Fornitore -->
  <VDialog
    v-model="fornitoreVisible"
    max-width="600"
    persistent
  >
    <!-- Dialog close btn -->
    <DialogCloseBtn @click="fornitoreVisible = !fornitoreVisible" />

    <!-- Dialog Content -->
    <VCard :title="$t('Label.Fornitore')">
      <VCardText>
        <VRow>
          <VCol cols="12">
            <AppSelect
              v-model="newProvider.tipologia"
              :items="['Compatibile', 'Originale']"
              :label="$t('Label.Tipologia')"
              :placeholder="$t('Label.Tipologia')"
            />
          </VCol>
          <VCol cols="12">
            <AppTextField
              v-model="newProvider.sito"
              :label="$t('Label.Sito')"
              :placeholder="$t('Label.Sito')"
            />
          </VCol>
          <VCol cols="12">
            <AppTextField
              v-model="newProvider.link"
              :label="$t('Label.Link')"
              :placeholder="$t('Label.Link')"
            />
          </VCol>
          <VCol cols="12">
            <AppTextField
              v-model="newProvider.prezzo"
              type="number"
              :label="$t('Label.Prezzo')"
              :placeholder="$t('Label.Prezzo')"
            />
          </VCol>
        </VRow>
      </VCardText>

      <VCardText class="d-flex justify-end flex-wrap gap-3">
        <VBtn
          variant="tonal"
          color="secondary"
          @click="fornitoreVisible = false"
        >
          Close
        </VBtn>
        <VBtn @click="saveFornitore">
          Save
        </VBtn>
      </VCardText>
    </VCard>
  </VDialog>

  <!-- Nuovo Asset -->
  <VDialog
    v-model="assetDialog"
    max-width="1600"
    persistent
  >
    <!-- Dialog close btn -->
    <DialogCloseBtn @click="assetDialog = !assetDialog" />

    <!-- Dialog Content -->
    <VCard :title="$t('Label.Fornitore')">
      <VCardText>
        <VRow>
          <VCol cols="12">
            <AppSelect
              v-model="newProvider.tipologia"
              :items="['Compatibile', 'Originale']"
              :label="$t('Label.Tipologia')"
              :placeholder="$t('Label.Tipologia')"
            />
          </VCol>
          <VCol cols="12">
            <AppTextField
              v-model="newProvider.sito"
              :label="$t('Label.Sito')"
              :placeholder="$t('Label.Sito')"
            />
          </VCol>
          <VCol cols="12">
            <AppTextField
              v-model="newProvider.link"
              :label="$t('Label.Link')"
              :placeholder="$t('Label.Link')"
            />
          </VCol>
          <VCol cols="12">
            <AppTextField
              v-model="newProvider.prezzo"
              type="number"
              :label="$t('Label.Prezzo')"
              :placeholder="$t('Label.Prezzo')"
            />
          </VCol>
        </VRow>
      </VCardText>

      <VCardText class="d-flex justify-end flex-wrap gap-3">
        <VBtn
          variant="tonal"
          color="secondary"
          @click="assetDialog = false"
        >
          Close
        </VBtn>
        <VBtn @click="saveFornitore">
          Save
        </VBtn>
      </VCardText>
    </VCard>
  </VDialog>
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
