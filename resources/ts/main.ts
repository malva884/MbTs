import { createApp } from 'vue'
import VCalendar from 'v-calendar'
import App from '@/App.vue'
import { registerPlugins } from '@core/utils/plugins'

// Styles
import '@core-scss/template/index.scss'
import '@styles/styles.scss'
import 'v-calendar/style.css'

// Create vue app
const app = createApp(App)

// Register plugins
registerPlugins(app)

// Mount vue app
app.mount('#app')
app.use(VCalendar, {})
