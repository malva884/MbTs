import type { App } from 'vue'
import VueApexCharts from 'vue3-apexcharts'

export default function (app: App) {
  app.use(VueApexCharts)
}
