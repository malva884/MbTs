<script lang="ts" setup>
import { defineComponent, ref, onMounted } from 'vue'
import RelationGraph from 'relation-graph-vue3'
import type { RGOptions, RGJsonData, RGNode, RGLine, RGLink, RGUserEvent, RelationGraphComponent } from 'relation-graph-vue3'


const checkedGroupId = ref('')
const checkedMemberId = ref('')
const interestGroups = ref([])
const graphRef = ref<RelationGraphComponent>()
const isDialogVisible = ref(false)
const matricola = ref('')
const ip = ref('')
const utente = ref('')
const tipologia = ref('')
const isSnackbarStartVisible = ref(false)
const info_id = ref('')
const info_matricola = ref('')
const info_utente = ref('')
const info_ip = ref('')
const posX = ref()
const posY = ref()
const tipologie = ['Desktop', 'Firewall', 'Laptop', 'Server', 'Storage', 'Switch', 'Printer', 'WIFI']
const db = []
const componentKey = ref(0)

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

const showGraph = async() => {
  interestGroups.value = [
    { groupId: 'a', groupName: 'Sports Group' },
    { groupId: 'b', groupName: 'Music Group' },
    { groupId: 'c', groupName: 'Arts and Crafts Group' },
    { groupId: 'd', groupName: 'Literature Reading Group' },
    { groupId: 'e', groupName: 'Volunteer Group' },
    { groupId: 'f', groupName: 'Science Research Group' },
  ];
  const graphInstance = graphRef.value.getInstance();
  graphInstance.addNodes([
   // { id: 'startNode', text: '', opacity: 0, x: -300, y: -300 },
    //{ id: 'endNode', text: '', opacity: 0, x: 700, y: 700 }
  ])
  setTimeout(() => {
    //onGroupClick('a')
  }, 200)
}

const position = (event: any) => {
  const target = event.target
  const bounds = target.getBoundingClientRect()

  posX.value = event.clientX - bounds.x
  posY.value = event.clientY - bounds.y

  isDialogVisible.value = true
  console.log(posX, posY)
}

const salva_posizione = async () => {

  db.push({ tipologia: tipologia.value, matricola: matricola.value, utente: utente.value, ip: ip.value, online: false, posX: posX.value, posY: posY.value, style: `left:${posX.value}px; top:${posY.value}px;` })

  for (const value of db) {
    const index = db.indexOf(value)
    const resultData = await useApi<boolean>(createUrl('/pl/asset/ping', {
      query: {
        ip: value.ip,
      },
    }))

    if (resultData.data.value != null)
      value.online = resultData.data.value.attivo === true ? 'success' : 'error'
  }
  componentKey.value += 1
  isDialogVisible.value = false
}

const info_asset = (id: string) => {

  const asset_info = db.find(asset => asset.matricola == id)

  info_id.value = id
  info_utente.value = asset_info.utente
  info_matricola.value = asset_info.matricola
  info_ip.value = asset_info.ip
  isSnackbarStartVisible.value = true
}

const icon = (tipologia: string) => {

  if (tipologia === 'Desktop')
    return 'tabler-device-imac'
  else if (tipologia === 'Laptop')
    return 'tabler-device-laptop'
  else if (tipologia === 'Server')
    return 'tabler-server'
  else if (tipologia === 'Storage')
    return 'tabler-database'
  else if (tipologia === 'Switch')
    return 'tabler-network'
  else if (tipologia === 'Firewall')
    return 'tabler-wall'
  else if (tipologia === 'WIFI')
    return 'tabler-wifi'
  else if (tipologia === 'Printer')
    return 'tabler-printer'

  return ''
}

const onGroupClick = (groupId: string) => {
  console.log('onGroupClick')
  checkedMemberId.value = ''
  checkedGroupId.value = groupId

  const htmlElementLines = []

  htmlElementLines.push({
    from: `group-${groupId}`,
    to: `location-${groupId}`,
    color: 'rgba(159,23,227,0.65)',
    lineWidth: 3,
    lineShape: 5,
    animation: 2,

  })

  const graphInstance = graphRef.value.getInstance();
  graphInstance.clearElementLines();
  graphInstance.addElementLines(htmlElementLines);
};


onMounted(() => {
  showGraph();
});
</script>

<template>
  <VSnackbar
    v-model="isSnackbarStartVisible"
    :timeout="-1"
    location="start center"
  >
    <p>Matricola: {{info_matricola}}</p>
    <p>Utente: {{info_utente}}</p>
    <p>Ip: {{info_ip}}</p>
    <p>Id: {{info_id}}</p>
  </VSnackbar>
  <div>
    <div style="height:calc(100vh - 180px);">
      <RelationGraph ref="graphRef" :options="graphOptions">
        <template #canvas-plug><!-- behind others-->
          <div style="z-index:10;position: absolute;left: -700px;top: 0px;">
            <div style="position: absolute;left: 0px;top: 0px;height: 800px;width:800px;background-image: url('/images/custom/image.jpg');background-repeat: no-repeat;opacity: 0.5;" @contextmenu="position">
              <!-- display image -->
            </div>
            <div :key="componentKey" style="position: relative;" >
              <div v-for="x in db" :title="x.utente" id="location-a" class="c-i-location" :style="x.style" @mouseover="info_asset(x.matricola)" @click="position"><span><VIcon :color="x.online" :icon="icon(x.tipologia)" style=" height: 30px; width: 30px;"/></span></div>

              <!--div id="location-a" class="c-i-location"  style="left:364px;top:115.265625px;" @mouseover="info_asset('5321')"><span><VIcon color="success" stroke-width="225" icon="tabler-device-laptop" style=" height: 30px; width: 30px;"/></span></div -->
              <!--div id="location-c" class="c-i-location"  style="left:180px;top:40px;"  @click=""><span><i class="el-icon-location" /></span></div>
              <div id="location-d" class="c-i-location"  style="left:430px;top:200px;" @click=""><span><i class="el-icon-location" /></span></div>
              <div id="location-e" class="c-i-location"  style="left:530px;top:130px;" @click=""><span><i class="el-icon-location" /></span></div>
              <div id="location-f" class="c-i-location"  style="left:600px;top:240px;" @click=""><span><i class="el-icon-location" /></span></div -->
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
            md="4"
          >
            <AppSelect
              v-model="tipologia"
              :items="tipologie"
              :label="$t('Label.Tipologia')"
              :placeholder="$t('Label.Tipologia')"
            />
          </VCol>
          <VCol
            cols="12"
            sm="6"
            md="4"
          >
            <AppTextField
              v-model="matricola"
              :label="$t('Label.Matricola')"
              :placeholder="$t('Label.Matricola')"
            />
          </VCol>
          <VCol
            cols="12"
            sm="6"
            md="4"
          >
            <AppTextField
              v-model="utente"
              :label="$t('Label.Utente')"
              :placeholder="$t('Label.Utente')"
            />
          </VCol>
          <VCol cols="12">
            <AppTextField
              v-model="ip"
              :label="$t('Label.IP')"
              :placeholder="$t('Label.IP')"
            />
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
        <VBtn @click="salva_posizione">
          Save
        </VBtn>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<style lang="scss" scoped>
.c-i-group{
  background-color: rgba(159,23,227,0.65);
  color: #ffffff;
  border-radius: 5px;
  padding: 5px;
  padding-left: 10px;
  margin-top: 10px;
  cursor: pointer;
  :hover {
    opacity: 0.7;
  }
  .c-i-name{
    width: 100%;
  }
  .c-i-count{
    background-color: #ffffff;
    color: rgba(159,23,227,0.65);
  }
}
.c-i-group-checked{
  transition: background-color 200ms ease, outline 200ms ease, color 200ms ease, -webkit-box-shadow 200ms ease;
  box-shadow: 0 0 0 8px rgba(159,23,227, 0.3);
}
.c-i-location {
  position: absolute;font-size: 40px;color: rgba(159,23,227,0.65);width:19px;height:19px;cursor: pointer;
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
    color: rgba(159,23,227,1);
  }
}
.c-i-location-active{
  opacity: 1;
}
@keyframes jumpingLocation {
  0%, 100% {
    color: rgba(159,23,227,1);
  }
  50% {
    color: rgb(245, 87, 29);
  }
}
</style>
