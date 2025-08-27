<script setup lang="ts">
import { ref } from 'vue'
import RelationGraph from 'relation-graph-vue3'
import type {
  RGOptions,
  RelationGraphComponent,
} from 'relation-graph-vue3'
import { useI18n } from 'vue-i18n'
import AssetTabInfo from '@/views/plant/map/view/AssetTabInfo.vue'
import { can } from '@layouts/plugins/casl'
import DefineAbilities from '@/plugins/casl/DefineAbilities'

definePage({
  meta: {
    action: 'list',
    subject: 'Plant-Asset',
  },
})

const { t } = useI18n()
const buttonDisabled = ref(false)
const isInfoDialogVisible = ref(false)
const deleteDialog = ref(false)
const isAllertMessage = ref(false)
const loadingPage = ref(false)
const messaggioPopap = t('Messaggi.Asset-Gia-Allocato')
const graphRef = ref<RelationGraphComponent>()
const isDialogVisible = ref(false)
const seriale = ref()
const numero_serialeFilter = ref('')
const utenteFilter = ref('')
const utente = ref()
const isInfoVisible = ref(false)
const info_id = ref('')
const info_seriale = ref('')
const info_utente = ref('')
const info_ip = ref('')
const info_tipologia = ref('')
const info_online = ref('')
const posX = ref()
const posY = ref()
const assetMap = ref({})
const componentKey = ref(0)
const style = ref({})
const assetIdInfo = ref()
const assetTab = ref(null)
const message = ref()
const color = ref()
const tipologieList = ref({})
const viewTipologie = ref(false)

const tabs = [
  { icon: 'tabler-info-square-rounded', title: 'Info' },
  { icon: 'tabler-tool', title: 'Interventi' },
  { icon: 'tabler-book', title: 'Note' },
]

const route = useRoute('plant-asset-maps-id')

const loadMap = async () => {
  const { data: resultData } = await useApi<any>(createUrl(`/pl/map/view/${route.params.id}`, {
    query: {},
  }))

  // style="position: absolute;left: 0px;top: 0px;height: 800px;width:800px;background-image: url('');background-repeat: no-repeat;opacity: 0.5;" @contextmenu="position"
  style.value = {
    position: 'absolute',
    left: '0px',
    top: '0px',
    height: '800px',
    width: '1000px',
    backgroundImage: `url(/${resultData.value.map})`,
    backgroundRepeat: 'no-repeat',
    opacity: '0.5',
  }
}

loadMap()

const { data: assetList } = useApi<any>(createUrl('/pl/asset/get_list', {
  query: {
    stato: 'Allocated',
  },
}))

const loadTipologie = async () =>{
  const { data: tipologieData } = await useApi<any>(createUrl('/pl/typology/get_list', {
    query: {
      attivo: 1,
    },
  }))

  tipologieList.value = tipologieData.value
  viewTipologie.value = true
}

loadTipologie()

const loadAssetMap = async () => {
  const { data: resultData } = await useApi<any>(createUrl(`/pl/map/asset/${route.params.id}`, {
    query: {
      utente: utenteFilter.value,
      seriale: numero_serialeFilter.value,
      map: route.params.id,
    },
  }))

  assetMap.value = resultData.value

  for (const value of assetMap.value) {
    const index = assetMap.value.indexOf(value)

    const resultData = await useApi<boolean>(createUrl('/pl/asset/ping', {
      query: {
        ip: value.ip_address,
      },
    }))

    if (resultData.data.value != null)
      value.online = resultData.data.value.attivo === true ? 'success' : 'error'
  }
}

loadAssetMap()

const graphOptions: RGOptions = {
  debug: false,
  allowSwitchLineShape: true,
  allowSwitchJunctionPoint: true,
  allowShowDownloadButton: false,
  defaultJunctionPoint: 'border',
  allowShowZoomMenu: false,
  disableZoom: true,
  placeOtherNodes: false,
  placeSingleNode: false,
  graphOffset_x: 100,
  graphOffset_y: -300,
  layout: {
    layoutName: 'fixed',
  },
}

const position = (event: any) => {
  utente.value = ''
  seriale.value = ''

  const target = event.target
  const bounds = target.getBoundingClientRect()

  buttonDisabled.value = false

  posX.value = event.clientX - bounds.x
  posY.value = event.clientY - bounds.y

  isDialogVisible.value = true
}

const salvaPosizione = async () => {
  if (utente.value === seriale.value) {
    loadingPage.value = true
    const retuenData = await $api('/pl/map/store', {
      method: 'POST',
      body: {
        asset_id: utente.value,
        map: route.params.id,
        posX: posX.value,
        posY: posY.value,
        cordinate: `left:${posX.value}px; top:${posY.value}px;`,
      },
    })

    await loadAssetMap()
    componentKey.value += 1
    isDialogVisible.value = false
    loadingPage.value = false
  }
}

const info_asset = (id: string) => {
  const asset_info = assetMap.value.find(asset => asset.numero_seriale == id)

  info_id.value = id
  info_utente.value = asset_info.utente
  info_seriale.value = asset_info.numero_seriale
  info_ip.value = asset_info.ip_address
  info_online.value = asset_info.online
  info_tipologia.value = asset_info.tipo_asset
  isInfoVisible.value = true
}

const nuovaAllocazione = (imput: string) => {
  if (imput === 'utente')
    seriale.value = utente.value
  else
    utente.value = seriale.value

  // eslint-disable-next-line @typescript-eslint/no-use-before-define
  checkAllocazione(utente.value)
}

const checkAllocazione = async (id: string) => {
  const { data: resultData } = await useApi<boolean>(createUrl('/pl/map/check/', {
    query: {
      asset_id: id,
    },
  }))

  buttonDisabled.value = resultData.value
}

const info = async (id: string) => {
  assetIdInfo.value = id
  isInfoDialogVisible.value = true
}

const eliminaPosizione = async (id: string) => {
  loadingPage.value = true

  const assetInfo = assetMap.value.find(asset => asset.asset_id == id)

  const retuenData = await $api(`/pl/map/asset/deleted/${assetInfo.id}`, {
    method: 'delete',
  })

  await loadAssetMap()
  message.value = retuenData.message
  color.value = retuenData.color
  deleteDialog.value = false
  isInfoDialogVisible.value = false
  isAllertMessage.value = true
  loadingPage.value = false
}
</script>

<template>
  <VCol cols="12">
    <VSnackbar
      v-model="isAllertMessage"
      transition="scroll-y-reverse-transition"
      location="top central"
      :color="color"
    >
      {{ $t(message) }}
    </VSnackbar>
    <VCard
      title="Filters"
      class="mb-6"
    >
      <VCardText>
        <VRow>
          <!-- 👉 Utente -->
          <VCol
            cols="12"
            sm="3"
          >
            <AppTextField
              v-model="utenteFilter"
              :label="$t('Label.User')"
              :placeholder="$t('Label.User')"
              clearable
              clear-icon="tabler-x"
              @focusout="loadAssetMap"
            />
          </VCol>
          <!-- 👉 Numero Seriale -->
          <VCol
            cols="12"
            sm="3"
          >
            <AppTextField
              v-model="numero_serialeFilter"
              :label="$t('Label.Numero-Seriale')"
              :placeholder="$t('Label.Numero-Seriale')"
              clearable
              clear-icon="tabler-x"
              @focusout="loadAssetMap"
            />
          </VCol>
        </VRow>
      </VCardText>
    </VCard>
  </VCol>
  <VSnackbar
    v-model="isInfoVisible"
    :timeout="-1"
    location="start center"
  >
    <p>Seriale: {{ info_seriale }}</p>
    <p>Utente: {{ info_utente }}</p>
    <p>tipologia: {{ info_tipologia }}</p>
    <p>Ip: {{ info_ip }}</p>
    <p>online: {{ info_online }}</p>
  </VSnackbar>
  <div>
    <div style="height:calc(100vh - 180px);">
      <RelationGraph
        ref="graphRef"
        :options="graphOptions"
      >
        <template #canvas-plug>
          <!-- behind others -->
          <div style="z-index:10;position: absolute;left: -700px;top: 0px;">
            <div
              v-if="can(DefineAbilities.asset_create.action, DefineAbilities.asset_create.subject)"
              id="test1"
              :style="style"
              @contextmenu="position"
            >
              <!-- display image -->
            </div>
            <div
              v-else
              id="test1"
              :style="style"
            >
              <!-- display image -->
            </div>
            <div
              :key="componentKey"
              style="position: relative;"
            >
              <div
                v-for="x in assetMap"
                id="location-a"
                :title="x.utente"
                class="c-i-location"
                :style="x.cordinate"
                @mouseover="info_asset(x.numero_seriale)"
                @click="info(x.asset_id)"
              >
                <span><VIcon
                  :color="x.online"
                  :icon="x.icona"
                  style=" height: 30px; width: 30px;"
                /></span>
              </div>
            </div>
          </div>
        </template>
      </RelationGraph>
    </div>
  </div>

  <VDialog
    v-model="isDialogVisible"
    max-width="600"
  >
    <!-- Dialog close btn -->
    <DialogCloseBtn @click="isDialogVisible = !isDialogVisible" />

    <!-- Dialog Content -->
    <VCard :title="$t('Label.Nuovo-Asset')">
      <VCardText>
        <VRow>
          <VCol
            cols="12"
            sm="6"
            md="6"
          >
            <AppAutocomplete
              v-model="utente"
              :label="$t('Label.Utente')"
              :placeholder="$t('Label.Utente')"
              :items="assetList"
              :item-title="item => item.utente"
              :item-value="item => item.id"
              clearable
              @focusout="nuovaAllocazione('utente')"
            />
          </VCol>
          <VCol
            cols="12"
            sm="6"
            md="6"
          >
            <AppAutocomplete
              v-model="seriale"
              :label="$t('Label.Seriale')"
              :placeholder="$t('Label.Seriale')"
              :items="assetList"
              :item-title="item => item.numero_seriale"
              :item-value="item => item.id"
              clearable
              @focusout="nuovaAllocazione('seriale')"
            />
          </VCol>
        </VRow>
        <VRow>
          <VCol
            cols="12"
            sm="12"
            md="12"
          >
            <span v-if="buttonDisabled" class="text-error">{{messaggioPopap}}</span>
          </VCol>
        </VRow>
      </VCardText>

      <VCardText class="d-flex justify-end flex-wrap gap-3">
        <VBtn
          variant="tonal"
          color="secondary"
          @click="isDialogVisible = false"
        >
          Close
        </VBtn>
        <VBtn @click="salvaPosizione" :disabled="buttonDisabled">
          Save
        </VBtn>
      </VCardText>
    </VCard>
  </VDialog>
  <VDialog
    v-model="isInfoDialogVisible"
    persistent
    class=""
    max-width="1600"
  >
    <!-- Dialog close btn -->
    <DialogCloseBtn @click="isInfoDialogVisible = !isInfoDialogVisible" />

    <!-- Dialog Content -->
    <VCard title="Info Asset">
      <VRow>
        <VCol
          cols="12"
        >
          <VTabs
            v-model="assetTab"
            class="v-tabs-pill"
          >
            <VTab
              v-for="tab in tabs"
              :key="tab.icon"
            >
              <VIcon
                :size="18"
                :icon="tab.icon"
                class="me-1"
              />
              <span>{{ tab.title }}</span>
            </VTab>
          </VTabs>

          <VWindow
            v-model="userTab"
            class="mt-6 disable-tab-transition"
            :touch="false"
          >

            <VWindowItem>
              <AssetTabInfo :id="assetIdInfo" />
            </VWindowItem>

            <VWindowItem>
              <AssetTabInfo :id="assetIdInfo" />
            </VWindowItem>
          </VWindow>
        </VCol>
      </VRow>
      <VCardText class="d-flex justify-end gap-3 flex-wrap">
        <VBtn
          color="error"
          @click="deleteDialog = true"
        >
          Elimina Allocazione
        </VBtn>
        <VBtn @click="isInfoDialogVisible = false">
          Agree
        </VBtn>
      </VCardText>
    </VCard>
  </VDialog>

  <!-- 👉 Delete Dialog  -->
  <VDialog
    v-model="deleteDialog"
    max-width="500px"
  >
    <VCard>
      <VCardTitle>
        Sei sicuro di voler eliminare?
      </VCardTitle>

      <VCardActions>
        <VSpacer />

        <VBtn
          color="error"
          variant="outlined"
          @click="deleteDialog = false"
        >
          Cancel
        </VBtn>

        <VBtn
          color="success"
          variant="elevated"
          @click="eliminaPosizione(assetIdInfo)"
        >
          OK
        </VBtn>

        <VSpacer />
      </VCardActions>
    </VCard>
  </VDialog>
  <LoadingStandBy v-model="loadingPage"></LoadingStandBy>
</template>

<style lang="scss" scoped>
.c-i-group {
  background-color: rgba(159, 23, 227, 0.65);
  color: #ffffff;
  border-radius: 5px;
  padding: 5px;
  padding-left: 10px;
  margin-top: 10px;
  cursor: pointer;

  :hover {
    opacity: 0.7;
  }

  .c-i-name {
    width: 100%;
  }

  .c-i-count {
    background-color: #ffffff;
    color: rgba(159, 23, 227, 0.65);
  }
}

.c-i-group-checked {
  transition: background-color 200ms ease, outline 200ms ease, color 200ms ease, -webkit-box-shadow 200ms ease;
  box-shadow: 0 0 0 8px rgba(159, 23, 227, 0.3);
}

.c-i-location {
  position: absolute;
  font-size: 40px;
  color: rgba(159, 23, 227, 0.65);
  width: 19px;
  height: 19px;
  cursor: pointer;
  overflow: visible;
  background-color: rgba(97, 4, 142, 0.65);
  border-radius: 50%;
  transition: background-color 200ms ease, outline 200ms ease, color 200ms ease, -webkit-box-shadow 200ms ease;
  box-shadow: 0 0 0 13px rgba(255, 255, 255, 0.5);
  animation: jumpingLocation 2s infinite;
  opacity: 0.9;

  span {
    position: absolute;
    margin-top: -40px;
    margin-left: -15px;
  }

  :hover {
    color: rgba(159, 23, 227, 1);
  }
}

.c-i-location-active {
  opacity: 1;
}

@keyframes jumpingLocation {
  0%, 100% {
    color: rgba(159, 23, 227, 1);
  }
  50% {
    color: rgb(245, 87, 29);
  }
}
</style>
