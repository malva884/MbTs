<script setup lang="ts">
import { QrcodeStream, QrcodeDropZone, QrcodeCapture } from 'vue3-qrcode-reader'
import AuthProvider from '@/views/pages/authentication/AuthProvider.vue'
import authV1BottomShape from '@images/svg/auth-v1-bottom-shape.svg?raw'
import authV1TopShape from '@images/svg/auth-v1-top-shape.svg?raw'
import { VNodeRenderer } from '@layouts/components/VNodeRenderer'
import { themeConfig } from '@themeConfig'
import { RpRegisterLog } from "@/views/reception/type"
import {ReprotChecker} from "@/views/quality/checker/type";

const MyComponent = {
  //
  components: {
    QrcodeStream,
    QrcodeDropZone,
    QrcodeCapture
  }
}



definePage({
  meta: {
    action: '',
    subject: '',
    layout: 'blank',
    public: true,
  },
})
/*** detection handling ***/

const result = ref('')
/*** select camera ***/

const selectedDevice = ref(null)
const devices = ref([])
const attivaBenvenuto = ref(true)
const attivaDevice = ref(false)
const registrazione = ref(false)
const item = ref<RpRegisterLog>()


const onDetect = async (detectedCodes: any) => {
  var code = ''
  await detectedCodes
    .then((value) => {
      code = value.content
    })
    .catch((error) => {
      result.value = 'Erorre Lettura'
    })

  const { data:result }= await useApi<any>(createUrl(`/reception/getRegister/${code}`))
  if(result.value.success === true){
    item.value = result.value.obj
    item.value.stampa = (result.value.obj.cod_riferimento === code ? true:false)
    attivaDevice.value = false
    registrazione.value = true
  }
  //registrazione
  //result.value = value.content

}



onMounted(async () => {

  devices.value = (await navigator.mediaDevices.enumerateDevices()).filter(
    ({ kind }) => kind === 'videoinput'
  )

  if (devices.value.length > 0) {
    selectedDevice.value = devices.value[0]
  }
})

/*** track functons ***/

function paintOutline(detectedCodes, ctx) {
  for (const detectedCode of detectedCodes) {
    const [firstPoint, ...otherPoints] = detectedCode.cornerPoints

    ctx.strokeStyle = 'red'

    ctx.beginPath()
    ctx.moveTo(firstPoint.x, firstPoint.y)
    for (const { x, y } of otherPoints) {
      ctx.lineTo(x, y)
    }
    ctx.lineTo(firstPoint.x, firstPoint.y)
    ctx.closePath()
    ctx.stroke()
  }
}
function paintBoundingBox(detectedCodes, ctx) {
  console.log(detectedCodes)
  for (const detectedCode of detectedCodes) {
    const {
      boundingBox: { x, y, width, height }
    } = detectedCode

    ctx.lineWidth = 2
    ctx.strokeStyle = '#007bff'
    ctx.strokeRect(x, y, width, height)
  }
}
function paintCenterText(detectedCodes, ctx) {
  for (const detectedCode of detectedCodes) {
    const { boundingBox, rawValue } = detectedCode

    const centerX = boundingBox.x + boundingBox.width / 2
    const centerY = boundingBox.y + boundingBox.height / 2

    const fontSize = Math.max(12, (50 * boundingBox.width) / ctx.canvas.width)

    ctx.font = `bold ${fontSize}px sans-serif`
    ctx.textAlign = 'center'

    ctx.lineWidth = 3
    ctx.strokeStyle = '#35495e'
    ctx.strokeText(detectedCode.rawValue, centerX, centerY)

    ctx.fillStyle = '#5cb984'
    ctx.fillText(rawValue, centerX, centerY)
  }
}
const trackFunctionOptions = [
  { text: 'nothing (default)', value: undefined },
  { text: 'outline', value: paintOutline },
  { text: 'centered text', value: paintCenterText },
  { text: 'bounding box', value: paintBoundingBox }
]
const trackFunctionSelected = ref(trackFunctionOptions[1])


/*** error handling ***/

const error = ref('')

function onError(err) {
  error.value = `[${err.name}]: `

  if (err.name === 'NotAllowedError') {
    error.value += 'you need to grant camera access permission'
  } else if (err.name === 'NotFoundError') {
    error.value += 'no camera on this device'
  } else if (err.name === 'NotSupportedError') {
    error.value += 'secure context required (HTTPS, localhost)'
  } else if (err.name === 'NotReadableError') {
    error.value += 'is the camera already in use?'
  } else if (err.name === 'OverconstrainedError') {
    error.value += 'installed cameras are not suitable'
  } else if (err.name === 'StreamApiNotSupportedError') {
    error.value += 'Stream API is not supported in this browser'
  } else if (err.name === 'InsecureContextError') {
    error.value +=
      'Camera access is only permitted in secure context. Use HTTPS or localhost rather than HTTP.'
  } else {
    error.value += err.message
  }
}

const start = () =>{
  attivaBenvenuto.value = false
  attivaDevice.value = true
}

const back = () => {
  registrazione.value = false
  attivaBenvenuto.value = true
  attivaDevice.value = false
}

const end = async () =>{
  attivaBenvenuto.value = true
}


</script>

<template>
  <div class="auth-wrapper d-flex align-center justify-center pa-4">
    <div class="position-relative my-sm-16">
      <!-- 馃憠 Top shape -->
      <VNodeRenderer
        :nodes="h('div', { innerHTML: authV1TopShape })"
        class="text-primary auth-v1-top-shape d-none d-sm-block"
      />

      <!-- 馃憠 Bottom shape -->
      <VNodeRenderer
        :nodes="h('div', { innerHTML: authV1BottomShape })"
        class="text-primary auth-v1-bottom-shape d-none d-sm-block"
      />

      <!-- 馃憠 Auth Card -->
      <VCard
        v-if="!registrazione"
        class="auth-card pa-4"
        max-width="448"
      >
        <VCardItem class="justify-center">
          <template #prepend>
            <div class="d-flex">
              <VNodeRenderer :nodes="themeConfig.app.logo" />
            </div>
          </template>

          <VCardTitle class="font-weight-bold text-capitalize text-h3 py-1">
            {{ themeConfig.app.title }}
          </VCardTitle>
        </VCardItem>

        <VCardText class="pt-1" v-if="attivaBenvenuto">
          <h4 class="text-h4 mb-1">
            Benvento !
          </h4>
          <p class="mb-0">
            Scansionare il tuo Qr Code.
          </p>
          <VCol
            cols="12"
            sm="12"
          >
            <VBtn
              block
              @click="start"
            >
              Clicca Qui
            </VBtn>
          </VCol>
        </VCardText>

        <VCardText v-if="attivaDevice">
          <VCard>
            <qrcode-stream
              :constraints="{ deviceId: selectedDevice.deviceId }"
              :track="undefined"
              :formats="['qr_code']"
              @error="onError"
              @detect="onDetect"
              v-if="selectedDevice !== null"
            ></qrcode-stream>
            <VCol
              cols="12"
              sm="12"
            >
              <VBtn
                block
                variant="outlined"
                @click="back"
              >
                Esci
              </VBtn>
            </VCol>
          </VCard>
        </VCardText>

      </VCard>
      <VCard
        v-if="registrazione"
        class="auth-card pa-6"
        max-width="1200"
        min-width="1200"
      >
        <AppRegistrazione
          v-model:isDrawerOpen="registrazione"
          md="6"
          :visitatoreData="item"
          @visitatoreData="end"
        />
        <VCol
          cols="12"
          sm="12"
        >
          <VBtn
            block
            variant="outlined"
            @click="back"
          >
            Esci
          </VBtn>
        </VCol>
      </VCard>
    </div>
  </div>

</template>

<style lang="scss">
@use "@core-scss/template/pages/page-auth.scss";
</style>



<style scoped>
.error {
  font-weight: bold;
  color: red;
}
.barcode-format-checkbox {
  margin-right: 10px;
  white-space: nowrap;
}
</style>
