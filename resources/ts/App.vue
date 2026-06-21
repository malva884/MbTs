<script setup lang="ts">
import { useTheme } from 'vuetify'
import ScrollToTop from '@core/components/ScrollToTop.vue'
import initCore from '@core/initCore'
import { initConfigStore, useConfigStore } from '@core/stores/config'
import { hexToRgb } from '@layouts/utils'
import { useVersionCheck } from '@/composables/useVersionCheck'

const { global } = useTheme()

// ℹ️ Sync current theme with initial loader theme
initCore()
initConfigStore()

const configStore = useConfigStore()

// Version check for deploy notifications
const { showUpdateDialog, refreshPage } = useVersionCheck()
</script>

<template>
  <VLocaleProvider :rtl="configStore.isAppRTL">
    <!-- ℹ️ This is required to set the background color of active nav link based on currently active global theme's primary -->
    <VApp :style="`--v-global-theme-primary: ${hexToRgb(global.current.value.colors.primary)}`">
      <RouterView />

      <ScrollToTop />

      <!-- Update Dialog -->
      <VDialog v-model="showUpdateDialog" max-width="500" persistent>
        <VCard>
          <VCardTitle class="d-flex align-center gap-3">
            <VIcon icon="tabler-refresh-alert" color="warning" size="28" />
            <span>Nuova Versione Disponibile</span>
          </VCardTitle>
          <VCardText>
            <p class="text-body-1 mb-4">
              È disponibile una nuova versione dell'applicazione. È necessario aggiornare la pagina per continuare a utilizzare il sistema.
            </p>
            <p class="text-body-2 text-disabled mb-0">
              Cliccando su "Aggiorna ora" la pagina verrà ricaricata automaticamente.
            </p>
          </VCardText>
          <VCardActions class="justify-end">
            <VBtn color="primary" @click="refreshPage">
              <VIcon icon="tabler-refresh" class="mr-1" />
              Aggiorna ora
            </VBtn>
          </VCardActions>
        </VCard>
      </VDialog>
    </VApp>
  </VLocaleProvider>
</template>
