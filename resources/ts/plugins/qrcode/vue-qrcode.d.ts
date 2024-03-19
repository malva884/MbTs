import  VueQrcodeReader  from 'vue3-qrcode-reader'
import type { App } from 'vue'


export default function (app: App) {
  app.use(VueQrcodeReader)
}
