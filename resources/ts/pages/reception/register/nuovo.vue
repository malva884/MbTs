<script setup lang="ts">
import { VForm } from 'vuetify/components/VForm'
import { useI18n } from 'vue-i18n'
import { VNodeRenderer } from '@layouts/components/VNodeRenderer'
import { themeConfig } from '@themeConfig'
import informativaIt from '@images/files/informativaIt2.pdf'
import informativaEn from '@images/files/informativaEn2.pdf'
import saluti from '@images/custom/saluti.png'
import benvenuto from '@images/custom/benvenuto.png'
import scanQrCode from '@images/custom/scanQrCode.png'
import NavBarI18n from '@core/components/I18n.vue'

definePage({
  meta: {
    action: '',
    subject: '',
    layout: 'blank',
    public: true,
  },
})

const { t } = useI18n()
const { locale } = useI18n({ useScope: 'global' })
const totemSettings = ref<{ totem?: string }>({})
const totemItem = useCookie('totemData')
const totemData = ref<any[]>([])
const refCodeInput = ref<HTMLInputElement>()
const refForm = ref<VForm>()
const homeDialog = ref(true)
const registerDialog = ref(false)
const authDialog = ref(false)
const settingsDialog = ref(false)
const salutiDialog = ref(false)
const benvenutoDialog = ref(false)
const informativaQrDialog = ref(false)
const informativaRgDialog = ref(false)
const qrcodeText = ref()
const password = ref()
const isPasswordVisible = ref(false)
const erroreLettura = ref('')
const totems = ref([])
const guestsOptions = ref([])
const loadingPage = ref(false)
const processing = ref(false)
const snackbar = ref(false)
const snackbarMsg = ref('')
const snackbarColor = ref('error')
const passwordError = ref('')

const pdfInformativaUrl = computed(() => {
  const file = locale.value === 'it' ? informativaIt : informativaEn

  return `${file}#toolbar=0&navpanes=0&view=FitH`
})

const currentTime = ref('')
const currentDate = ref('')
let clockTimer: any = null

const updateClock = () => {
  const now = new Date()

  currentTime.value = now.toLocaleTimeString(locale.value === 'en' ? 'en-GB' : 'it-IT', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
  currentDate.value = now.toLocaleDateString(locale.value === 'en' ? 'en-GB' : 'it-IT', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' })
}

const showError = (msg: string) => {
  snackbarMsg.value = msg
  snackbarColor.value = 'error'
  snackbar.value = true
}

const defaultItem = ref<any>({
  id: '',
  email: '',
  nome: '',
  azienda: '',
  wifi: 0,
  data_prevista: '',
  data_scadenza: '',
  attivo: 1,
  user_interni: [],
  cod_riferimento: '',
  cod_tessera: '',
  entrata: true,
  informativa: true,
  notifica_inviata: true,
  ip_stampante: totemItem.value?.ipStampante,
})

function newDefaultItem() {
  defaultItem.value = {
    id: '',
    email: '',
    nome: '',
    azienda: '',
    wifi: 0,
    data_prevista: '',
    data_scadenza: '',
    attivo: 1,
    user_interni: [],
    cod_riferimento: '',
    cod_tessera: '',
    entrata: true,
    informativa: true,
    notifica_inviata: true,
    ip_stampante: totemItem.value?.ipStampante,
  }
}

const registerItem = ref<any>(defaultItem.value)

if (totemItem.value?.totem === undefined) {
  homeDialog.value = false
  authDialog.value = true
}

const authSetting = () => {
  homeDialog.value = false
  authDialog.value = true
}

const closeSetting = () => {
  settingsDialog.value = false
  passwordError.value = ''

  // senza totem configurato si resta nella schermata di autenticazione
  if (totemItem.value?.totem !== undefined) {
    authDialog.value = false
    homeDialog.value = true
  }
  password.value = ''
}

const home = () => {
  qrcodeText.value = ''
  newDefaultItem()
  registerItem.value = { ...defaultItem.value }
  erroreLettura.value = ''
  authDialog.value = false
  settingsDialog.value = false
  salutiDialog.value = false
  benvenutoDialog.value = false
  informativaQrDialog.value = false
  informativaRgDialog.value = false
  registerDialog.value = false
  homeDialog.value = true
}

const openSettings = async () => {
  passwordError.value = ''

  try {
    const authData = await $api('/register/totem/auth_setting', {
      method: 'POST',
      body: {
        password: password.value,
      },
    })

    if (authData.success === true) {
      authDialog.value = false
      settingsDialog.value = true
    }
    else {
      passwordError.value = t('Label.Password-Errata')
    }
  }
  catch {
    passwordError.value = t('Label.Errore')
  }
}

const setTotem = () => {
  const totem = totemData.value.find(i => i.id === totemSettings.value.totem)

  if (!totem)
    return

  useCookie('totemData').value = {
    totem: totem.nome,
    ipStampante: totem.ip_stampante,
    registrazione: totem.registrazione,
    informativa: totem.informativa,
  }

  totemItem.value = {
    totem: totem.nome,
    ipStampante: totem.ip_stampante,
    registrazione: totem.registrazione,
    informativa: totem.informativa,
  }

  settingsDialog.value = false
  homeDialog.value = true
  password.value = ''
}

const totemList = async () => {
  const resultData = await useApi<any>(createUrl('/totemList'))
  const arr = []

  totemData.value = resultData.data.value?.totem ?? []
  totemData.value.forEach(value => {
    arr.push({ full_name: value.nome, id: value.id })
  })
  totems.value = arr
}

totemList()

const hideImage = () => {
  salutiDialog.value = false
  benvenutoDialog.value = false
  homeDialog.value = true
}

const processCode = async (code: string) => {
  if (code.length >= 18 && !processing.value) {
    processing.value = true

    try {
      const { data: resultData } = await useApi<any>(createUrl('/getRegister', {
        query: { code },
      }))

      if (resultData.value?.success === true) {
        erroreLettura.value = ''

        if (resultData.value.azione === 'Entrata') {
          registerItem.value = { ...resultData.value.obj }
          homeDialog.value = false
          informativaQrDialog.value = true
        }
        else {
          homeDialog.value = false
          salutiDialog.value = true
          setTimeout(hideImage, 5000)
        }
      }
      else {
        erroreLettura.value = t('Label.Errore-Lettura-Codice')
      }
    }
    catch {
      erroreLettura.value = t('Label.Errore-Lettura-Codice')
    }
    finally {
      qrcodeText.value = ''
      refCodeInput.value?.focus()
      processing.value = false
    }
  }
}

const accessQr = async () => {
  loadingPage.value = true
  registerItem.value.ip_stampante = totemItem.value?.ipStampante

  try {
    const result = await $api('/reception/storeRegister', {
      method: 'POST',
      body: registerItem.value,
    })

    if (result?.success === false)
      throw new Error('storeRegister failed')

    qrcodeText.value = ''
    informativaQrDialog.value = false
    benvenutoDialog.value = true
    setTimeout(hideImage, 5000)
  }
  catch {
    informativaQrDialog.value = false
    homeDialog.value = true
    showError(t('Messaggi.Errore-Salvataggio'))
  }
  finally {
    loadingPage.value = false
  }
}

const userOptions = async () => {
  const resultData = await useApi<any>(createUrl('/getReferenti'))
  const arr = []
  const referenti = resultData.data.value ?? []

  referenti.forEach(value => {
    arr.push({ full_name: value.nome, id: value.email })
  })

  guestsOptions.value = arr
}

const searchVisitor = async () => {
  if (!registerItem.value.email)
    return

  const { data: resultData } = await useApi<any>(createUrl('/register/searchVisitor', {
    query: {
      email: registerItem.value.email,
    },
  }))

  // visitatore mai registrato: nessun dato da precompilare
  if (!resultData.value)
    return

  registerItem.value.id = resultData.value.id
  registerItem.value.nome = resultData.value.nome
  registerItem.value.azienda = resultData.value.azienda
}

const openRegister = () => {
  const today = new Date()

  userOptions()
  newDefaultItem()
  registerItem.value = { ...defaultItem.value }
  registerItem.value.data_prevista = today.toISOString().slice(0, 10)
  registerItem.value.data_scadenza = today.toISOString().slice(0, 10)

  homeDialog.value = false
  informativaRgDialog.value = false
  registerDialog.value = true
}

const informativaRegistrazione = () => {
  informativaRgDialog.value = true
}

const saveRegister = async () => {
  refForm.value?.validate().then(async ({ valid }) => {
    if (!valid)
      return

    loadingPage.value = true

    try {
      const result = await $api('/register/store', {
        method: 'POST',
        body: registerItem.value,
      })

      // il backend ritorna sempre success:true: l'errore è segnalato con color
      if (result?.color === 'error')
        throw new Error('store failed')

      newDefaultItem()
      registerDialog.value = false
      informativaQrDialog.value = false
      benvenutoDialog.value = true
      setTimeout(hideImage, 5000)
      setTimeout(home, 7000)
    }
    catch {
      showError(t('Messaggi.Errore-Salvataggio'))
    }
    finally {
      loadingPage.value = false
    }
  })
}

const setFocus = () => {
  // il campo scansione esiste solo nella home: evita di rubare il focus dai dialog
  if (homeDialog.value) {
    qrcodeText.value = ''
    refCodeInput.value?.focus()
  }
  setTimeout(setFocus, 2000)
}

const refresh = () => {
  location.reload()
}

onMounted(() => {
  updateClock()
  clockTimer = setInterval(updateClock, 1000)
  refCodeInput.value?.focus()
  setTimeout(setFocus, 2000)
})

onUnmounted(() => {
  if (clockTimer)
    clearInterval(clockTimer)
})
</script>

<template>
  <div class="kiosk-container">
    <!-- 👉 Kiosk Header Bar -->
    <header class="kiosk-header">
      <div class="d-flex align-center gap-3">
        <VNodeRenderer :nodes="themeConfig.app.logo" />
        <div>
          <div class="text-subtitle-1 font-weight-bold text-high-emphasis">
            {{ themeConfig.app.title }}
          </div>
          <VChip
            size="x-small"
            color="primary"
            variant="tonal"
            prepend-icon="tabler-device-desktop"
            class="font-weight-medium"
          >
            {{ totemItem?.totem || $t('Label.Totem') }}
          </VChip>
        </div>
      </div>

      <!-- 👉 Digital Clock & Date -->
      <div class="kiosk-clock text-center">
        <div class="text-h4 font-weight-bold tracking-wide font-mono">
          {{ currentTime }}
        </div>
        <div class="text-caption text-medium-emphasis text-capitalize">
          {{ currentDate }}
        </div>
      </div>

      <!-- 👉 Header Controls -->
      <div class="d-flex align-center gap-2">
        <NavBarI18n
          v-if="themeConfig.app.i18n.enable && themeConfig.app.i18n.langConfig?.length"
          :languages="themeConfig.app.i18n.langConfig"
        />
        <VBtn
          icon="tabler-refresh"
          variant="tonal"
          color="secondary"
          size="small"
          @click="refresh"
        />
        <VBtn
          icon="tabler-settings"
          variant="tonal"
          color="secondary"
          size="small"
          @click="authSetting"
        />
      </div>
    </header>

    <!-- 👉 Kiosk Main Body: Home View -->
    <main
      v-if="homeDialog"
      class="kiosk-body"
    >
      <div class="kiosk-content-wrapper">
        <!-- Hero Header -->
        <div class="text-center mb-5">
          <h1 class="text-h4 font-weight-bold text-high-emphasis mb-1">
            {{ $t('Label.Benvenuto') }}
          </h1>
          <p class="text-body-1 text-medium-emphasis mb-0">
            {{ $t('Totem.Scansiona-O-Registrati') }}
          </p>
        </div>

        <!-- 1. Card Scanner QR (Azione Principale) -->
        <VCard
          elevation="3"
          class="kiosk-action-card scanner-card mb-4"
        >
          <VCardText class="pa-6 text-center">
            <!-- Mirino animato stile scanner -->
            <div class="scanner-viewfinder mb-4">
              <div class="scanner-corner top-left" />
              <div class="scanner-corner top-right" />
              <div class="scanner-corner bottom-left" />
              <div class="scanner-corner bottom-right" />
              <div class="scanner-laser" />
              <VImg
                :src="scanQrCode"
                width="84"
                class="mx-auto"
              />
            </div>

            <div class="text-h6 font-weight-bold text-high-emphasis mb-1">
              {{ $t('Totem.Hai-Invito') }}
            </div>
            <p class="text-body-2 text-medium-emphasis mb-4">
              {{ $t('Totem.Mostra-Qr') }}
            </p>

            <VTextField
              ref="refCodeInput"
              v-model="qrcodeText"
              variant="outlined"
              density="comfortable"
              prepend-inner-icon="tabler-scan"
              :placeholder="$t('Label.Scansiona')"
              :error-messages="erroreLettura"
              class="scanner-input"
              autofocus
              @input="processCode($event.target.value)"
            />
          </VCardText>
        </VCard>

        <!-- Divisore "OPPURE" -->
        <div class="d-flex align-center gap-3 my-2 px-6">
          <VDivider />
          <span class="text-caption text-uppercase font-weight-bold text-medium-emphasis tracking-wider">
            {{ $t('Totem.Oppure') }}
          </span>
          <VDivider />
        </div>

        <!-- 2. Card Registrazione Manuale (Azione Secondaria) -->
        <VCard
          elevation="3"
          class="kiosk-action-card register-card mt-2"
        >
          <VCardText class="pa-6 text-center">
            <div class="mb-3">
              <VAvatar
                color="primary"
                variant="tonal"
                size="60"
              >
                <VIcon
                  icon="tabler-user-plus"
                  size="32"
                />
              </VAvatar>
            </div>

            <div class="text-h6 font-weight-bold text-high-emphasis mb-1">
              {{ $t('Totem.Prima-Visita') }}
            </div>
            <p class="text-body-2 text-medium-emphasis mb-5">
              {{ $t('Totem.No-Prenotazione') }}
            </p>

            <VBtn
              block
              size="x-large"
              color="primary"
              variant="elevated"
              rounded="lg"
              class="kiosk-touch-btn text-h6 font-weight-bold"
              @click="informativaRegistrazione"
            >
              <VIcon
                icon="tabler-pencil"
                class="me-2"
              />
              {{ $t('Label.Registrati') }}
              <VIcon
                icon="tabler-arrow-right"
                class="ms-2"
              />
            </VBtn>
          </VCardText>
        </VCard>

        <!-- Nota footer assistenza -->
        <div class="text-center mt-5 text-caption text-medium-emphasis d-flex align-center justify-center gap-1">
          <VIcon
            icon="tabler-info-circle"
            size="16"
          />
          <span>{{ $t('Totem.Assistenza') }}</span>
        </div>
      </div>
    </main>

    <!-- 👉 Benvenuto Feedback View -->
    <main
      v-if="benvenutoDialog"
      class="kiosk-body d-flex align-center justify-center pa-6"
    >
      <VCard
        elevation="4"
        class="pa-8 text-center kiosk-feedback-card"
      >
        <VAvatar
          color="success"
          variant="tonal"
          size="84"
          class="mb-4"
        >
          <VIcon
            icon="tabler-circle-check"
            size="52"
            color="success"
          />
        </VAvatar>

        <h2 class="text-h3 font-weight-bold text-success mb-2">
          {{ $t('Label.Benvenuto') }}!
        </h2>

        <div
          v-if="registerItem?.nome"
          class="text-h5 font-weight-bold text-high-emphasis mb-1"
        >
          {{ registerItem.nome }}
        </div>
        <div
          v-if="registerItem?.azienda"
          class="text-subtitle-1 text-medium-emphasis mb-3"
        >
          {{ registerItem.azienda }}
        </div>

        <VAlert
          color="success"
          variant="tonal"
          density="comfortable"
          icon="tabler-bell-check"
          class="text-start my-4"
        >
          {{ $t('Totem.Ingresso-Registrato') }}
        </VAlert>

        <VImg
          :src="benvenuto"
          max-width="320"
          class="mx-auto my-3 rounded-lg"
        />

        <div class="d-flex align-center justify-center gap-2 text-caption text-medium-emphasis mt-4">
          <VProgressCircular
            indeterminate
            size="18"
            width="2"
            color="primary"
          />
          <span>{{ $t('Totem.Ritorno-Home') }}</span>
        </div>

        <VBtn
          variant="text"
          color="secondary"
          class="mt-3"
          @click="home"
        >
          {{ $t('Totem.Torna-Home') }}
        </VBtn>
      </VCard>
    </main>

    <!-- 👉 Saluti Feedback View -->
    <main
      v-if="salutiDialog"
      class="kiosk-body d-flex align-center justify-center pa-6"
    >
      <VCard
        elevation="4"
        class="pa-8 text-center kiosk-feedback-card"
      >
        <VAvatar
          color="info"
          variant="tonal"
          size="84"
          class="mb-4"
        >
          <VIcon
            icon="tabler-door-exit"
            size="52"
            color="info"
          />
        </VAvatar>

        <h2 class="text-h3 font-weight-bold text-primary mb-2">
          {{ $t('Totem.Uscita-Registrata') }}
        </h2>

        <p class="text-subtitle-1 text-medium-emphasis mb-3">
          {{ $t('Totem.Arrivederci') }}
        </p>

        <VImg
          :src="saluti"
          max-width="320"
          class="mx-auto my-3 rounded-lg"
        />

        <div class="d-flex align-center justify-center gap-2 text-caption text-medium-emphasis mt-4">
          <VProgressCircular
            indeterminate
            size="18"
            width="2"
            color="primary"
          />
          <span>{{ $t('Totem.Ritorno-Home') }}</span>
        </div>

        <VBtn
          variant="text"
          color="secondary"
          class="mt-3"
          @click="home"
        >
          {{ $t('Totem.Torna-Home') }}
        </VBtn>
      </VCard>
    </main>

    <!-- 👉 Auth Settings Dialog -->
    <VDialog
      v-model="authDialog"
      max-width="460"
    >
      <VCard>
        <VCardItem class="pb-2">
          <template #prepend>
            <VAvatar
              color="primary"
              variant="tonal"
              size="40"
              class="me-2"
            >
              <VIcon icon="tabler-lock" />
            </VAvatar>
          </template>
          <VCardTitle>{{ $t('Label.Impostazioni-Totem') }}</VCardTitle>
          <VCardSubtitle>{{ $t('Totem.Auth-Richiesta') }}</VCardSubtitle>
        </VCardItem>

        <VCardText class="pt-4">
          <AppTextField
            v-model="password"
            :label="$t('Label.Password')"
            :placeholder="$t('Label.Password')"
            :type="isPasswordVisible ? 'text' : 'password'"
            :append-inner-icon="isPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
            :rules="[requiredValidator]"
            :error-messages="passwordError"
            autofocus
            @click:append-inner="isPasswordVisible = !isPasswordVisible"
            @keyup.enter="openSettings"
          />
        </VCardText>

        <VCardActions class="px-6 pb-6 pt-2 gap-3 justify-end">
          <VBtn
            variant="tonal"
            color="secondary"
            size="large"
            @click="closeSetting"
          >
            {{ $t('Label.Chiudi') }}
          </VBtn>
          <VBtn
            variant="elevated"
            color="primary"
            size="large"
            @click="openSettings"
          >
            {{ $t('Label.Accedi') }}
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- 👉 Totem Settings Dialog -->
    <VDialog
      v-model="settingsDialog"
      max-width="460"
    >
      <VCard>
        <VCardItem class="pb-2">
          <template #prepend>
            <VAvatar
              color="primary"
              variant="tonal"
              size="40"
              class="me-2"
            >
              <VIcon icon="tabler-device-desktop-cog" />
            </VAvatar>
          </template>
          <VCardTitle>{{ $t('Label.Impostazioni-Totem') }}</VCardTitle>
          <VCardSubtitle>{{ $t('Totem.Seleziona-Postazione') }}</VCardSubtitle>
        </VCardItem>

        <VCardText class="pt-4">
          <AppSelect
            v-model="totemSettings.totem"
            :label="$t('Label.Totem')"
            :placeholder="$t('Label.Totem')"
            :items="totems"
            :item-title="it => it.full_name"
            :item-value="it => it.id"
            chips
            eager
          />
        </VCardText>

        <VCardActions class="px-6 pb-6 pt-2 gap-3 justify-end">
          <VBtn
            variant="tonal"
            color="secondary"
            size="large"
            @click="closeSetting"
          >
            {{ $t('Label.Chiudi') }}
          </VBtn>
          <VBtn
            variant="elevated"
            color="primary"
            size="large"
            @click="setTotem"
          >
            {{ $t('Label.Salva') }}
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- 👉 Informativa QR Dialog (Fullscreen) -->
    <VDialog
      v-model="informativaQrDialog"
      fullscreen
      persistent
      transition="dialog-bottom-transition"
    >
      <VCard class="d-flex flex-column h-100 rounded-0">
        <!-- Header -->
        <VToolbar
          color="surface"
          density="comfortable"
          class="border-b px-4 flex-grow-0"
        >
          <VAvatar
            color="primary"
            variant="tonal"
            size="40"
            class="me-3"
          >
            <VIcon icon="tabler-shield-check" />
          </VAvatar>
          <div>
            <div class="text-h6 font-weight-bold">
              {{ $t('Label.Informativa') }}
            </div>
            <div class="text-caption text-medium-emphasis">
              {{ $t('Totem.Informativa-Subtitle') }}
            </div>
          </div>
        </VToolbar>

        <!-- PDF Container a tutta altezza -->
        <div
          class="flex-grow-1 w-100"
          style="block-size: calc(100vh - 144px); background: #525659; overflow: hidden;"
        >
          <iframe
            :src="pdfInformativaUrl"
            style="inline-size: 100%; block-size: 100%; border: none; display: block;"
          />
        </div>

        <!-- Barra pulsanti fissa in basso -->
        <div class="border-t px-6 py-3 d-flex justify-space-between align-center bg-surface flex-grow-0">
          <VBtn
            color="error"
            variant="outlined"
            size="x-large"
            style="min-inline-size: 220px; min-block-size: 56px;"
            class="font-weight-bold text-body-1"
            @click="home"
          >
            <VIcon
              icon="tabler-x"
              class="me-2"
            />
            {{ $t('Label.Non-Acconsento') }}
          </VBtn>

          <VBtn
            color="success"
            variant="elevated"
            size="x-large"
            style="min-inline-size: 260px; min-block-size: 56px;"
            class="font-weight-bold text-body-1"
            @click="accessQr"
          >
            <VIcon
              icon="tabler-check"
              class="me-2"
            />
            {{ $t('Label.Ho-Preso-Visione') }}
          </VBtn>
        </div>
      </VCard>
    </VDialog>

    <!-- 👉 Informativa Registrazione Dialog (Fullscreen) -->
    <VDialog
      v-model="informativaRgDialog"
      fullscreen
      persistent
      transition="dialog-bottom-transition"
    >
      <VCard class="d-flex flex-column h-100 rounded-0">
        <!-- Header -->
        <VToolbar
          color="surface"
          density="comfortable"
          class="border-b px-4 flex-grow-0"
        >
          <VAvatar
            color="primary"
            variant="tonal"
            size="40"
            class="me-3"
          >
            <VIcon icon="tabler-shield-check" />
          </VAvatar>
          <div>
            <div class="text-h6 font-weight-bold">
              {{ $t('Label.Informativa') }}
            </div>
            <div class="text-caption text-medium-emphasis">
              {{ $t('Totem.Informativa-Subtitle') }}
            </div>
          </div>
        </VToolbar>

        <!-- PDF Container a tutta altezza -->
        <div
          class="flex-grow-1 w-100"
          style="block-size: calc(100vh - 144px); background: #525659; overflow: hidden;"
        >
          <iframe
            :src="pdfInformativaUrl"
            style="inline-size: 100%; block-size: 100%; border: none; display: block;"
          />
        </div>

        <!-- Barra pulsanti fissa in basso -->
        <div class="border-t px-6 py-3 d-flex justify-space-between align-center bg-surface flex-grow-0">
          <VBtn
            color="error"
            variant="outlined"
            size="x-large"
            style="min-inline-size: 220px; min-block-size: 56px;"
            class="font-weight-bold text-body-1"
            @click="home"
          >
            <VIcon
              icon="tabler-x"
              class="me-2"
            />
            {{ $t('Label.Non-Acconsento') }}
          </VBtn>

          <VBtn
            color="success"
            variant="elevated"
            size="x-large"
            style="min-inline-size: 260px; min-block-size: 56px;"
            class="font-weight-bold text-body-1"
            @click="openRegister"
          >
            <VIcon
              icon="tabler-check"
              class="me-2"
            />
            {{ $t('Label.Ho-Preso-Visione') }}
          </VBtn>
        </div>
      </VCard>
    </VDialog>

    <!-- 👉 Register User Dialog -->
    <VDialog
      v-model="registerDialog"
      max-width="780px"
      persistent
    >
      <VCard class="kiosk-dialog-card">
        <VCardItem class="border-b pa-4">
          <template #prepend>
            <VAvatar
              color="primary"
              variant="tonal"
              size="40"
              class="me-2"
            >
              <VIcon icon="tabler-id-badge-2" />
            </VAvatar>
          </template>
          <VCardTitle class="text-h6 font-weight-bold">
            {{ $t('Label.Registrazione-Visitatore') }}
          </VCardTitle>
          <VCardSubtitle>{{ $t('Totem.Registrazione-Subtitle') }}</VCardSubtitle>
        </VCardItem>

        <VCardText class="pa-6">
          <VForm
            ref="refForm"
            @submit.prevent="saveRegister"
          >
            <VRow>
              <!-- 👉 Email -->
              <VCol cols="12">
                <AppTextField
                  v-model="registerItem.email"
                  :label="$t('Label.Email')"
                  :placeholder="$t('Label.Email')"
                  prepend-inner-icon="tabler-mail"
                  :rules="[requiredValidator, emailValidator]"
                  @focusout="searchVisitor"
                />
              </VCol>

              <!-- 👉 Nome & Cognome -->
              <VCol cols="12">
                <AppTextField
                  v-model="registerItem.nome"
                  :rules="[requiredValidator]"
                  :label="$t('Label.Nome-Cognome')"
                  :placeholder="$t('Label.Nome-Cognome')"
                  prepend-inner-icon="tabler-user"
                />
              </VCol>

              <!-- 👉 Azienda -->
              <VCol cols="12">
                <AppTextField
                  v-model="registerItem.azienda"
                  :label="$t('Label.Azienda')"
                  :placeholder="$t('Label.Azienda')"
                  prepend-inner-icon="tabler-building"
                />
              </VCol>

              <!-- 👉 Referente -->
              <VCol cols="12">
                <AppSelect
                  v-model="registerItem.user_interni"
                  :label="$t('Label.Referente')"
                  :placeholder="$t('Label.Referente')"
                  prepend-inner-icon="tabler-users"
                  :rules="[requiredValidator]"
                  :items="guestsOptions"
                  :item-title="it => it.full_name"
                  :item-value="it => it.id"
                  chips
                  eager
                />
              </VCol>

              <!-- 👉 Wi-Fi Switch Card -->
              <VCol cols="12">
                <VCard
                  variant="tonal"
                  color="primary"
                  class="pa-4 rounded-lg d-flex align-center justify-space-between"
                >
                  <div class="d-flex align-center gap-3">
                    <VIcon
                      icon="tabler-wifi"
                      size="28"
                    />
                    <div>
                      <div class="font-weight-bold text-body-1">
                        {{ $t('Label.Accesso-Wifi') }}
                      </div>
                      <div class="text-caption text-medium-emphasis">
                        {{ $t('Totem.Wifi-Subtitle') }}
                      </div>
                    </div>
                  </div>
                  <VSwitch
                    v-model="registerItem.wifi"
                    inset
                    hide-details
                  />
                </VCard>
              </VCol>
            </VRow>

            <div class="d-flex justify-space-between gap-3 pt-6 mt-4 border-t">
              <VBtn
                color="error"
                variant="outlined"
                size="large"
                class="kiosk-action-btn"
                @click="home"
              >
                {{ $t('Label.Annulla') }}
              </VBtn>

              <VBtn
                type="submit"
                color="success"
                variant="elevated"
                size="large"
                class="kiosk-action-btn font-weight-bold"
                @click="refForm?.validate()"
              >
                <VIcon
                  icon="tabler-check"
                  class="me-2"
                />
                {{ $t('Label.Registrati') }}
              </VBtn>
            </div>
          </VForm>
        </VCardText>
      </VCard>
    </VDialog>

    <!-- 👉 Feedback StandBy & Notifications -->
    <LoadingStandBy v-model="loadingPage" />
    <VSnackbar
      v-model="snackbar"
      :color="snackbarColor"
      location="top"
      :timeout="4000"
    >
      {{ snackbarMsg }}
    </VSnackbar>
  </div>
</template>

<style lang="scss">
.kiosk-container {
  display: flex;
  flex-direction: column;
  block-size: 100dvh;
  inline-size: 100vw;
  max-block-size: 100dvh;
  max-inline-size: 100vw;
  overflow: hidden;
  user-select: none;
  background: rgb(var(--v-theme-background));
  -webkit-tap-highlight-color: transparent;

  .kiosk-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-block: 0.85rem;
    padding-inline: 1.5rem;
    border-block-end: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    background: rgba(var(--v-theme-surface), 0.85);
    backdrop-filter: blur(12px);
    z-index: 10;

    .kiosk-clock {
      .font-mono {
        font-family: monospace, sans-serif;
        letter-spacing: 0.05em;
      }
    }
  }

  .kiosk-body {
    flex: 1;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 1.5rem;

    .kiosk-content-wrapper {
      inline-size: 100%;
      max-inline-size: 720px;
      margin-inline: auto;
    }
  }

  .kiosk-action-card {
    border-radius: 20px;
    border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    transition: transform 0.2s ease, box-shadow 0.2s ease;

    &:active {
      transform: scale(0.99);
    }
  }

  .scanner-viewfinder {
    position: relative;
    inline-size: 160px;
    block-size: 160px;
    margin-inline: auto;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 16px;
    background: rgba(var(--v-theme-primary), 0.04);

    .scanner-corner {
      position: absolute;
      inline-size: 24px;
      block-size: 24px;
      border-color: rgb(var(--v-theme-primary));
      border-style: solid;

      &.top-left {
        inset-block-start: 0;
        inset-inline-start: 0;
        border-width: 3px 0 0 3px;
        border-start-start-radius: 12px;
      }

      &.top-right {
        inset-block-start: 0;
        inset-inline-end: 0;
        border-width: 3px 3px 0 0;
        border-start-end-radius: 12px;
      }

      &.bottom-left {
        inset-block-end: 0;
        inset-inline-start: 0;
        border-width: 0 0 3px 3px;
        border-end-start-radius: 12px;
      }

      &.bottom-right {
        inset-block-end: 0;
        inset-inline-end: 0;
        border-width: 0 3px 3px 0;
        border-end-end-radius: 12px;
      }
    }

    .scanner-laser {
      position: absolute;
      inset-inline-start: 10px;
      inset-inline-end: 10px;
      block-size: 2px;
      background: linear-gradient(90deg, transparent, rgb(var(--v-theme-primary)), transparent);
      box-shadow: 0 0 8px rgb(var(--v-theme-primary));
      animation: scan-line 2.5s ease-in-out infinite;
    }
  }

  .scanner-input {
    .v-field {
      border-radius: 12px;
      font-size: 1.1rem;
    }
  }

  .kiosk-touch-btn {
    min-block-size: 58px;
    font-size: 1.15rem;
    letter-spacing: 0.02em;
  }

  .kiosk-feedback-card {
    inline-size: 100%;
    max-inline-size: 640px;
    border-radius: 24px;
  }

  .kiosk-pdf-iframe {
    inline-size: 100%;
    block-size: 58vh;
    border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    border-radius: 12px;
  }

  .kiosk-action-btn {
    min-inline-size: 180px;
    min-block-size: 50px;
  }

  .kiosk-dialog-card {
    border-radius: 20px;
  }
}

@keyframes scan-line {
  0%,
  100% {
    inset-block-start: 12px;
    opacity: 0.2;
  }

  50% {
    inset-block-start: calc(100% - 14px);
    opacity: 1;
  }
}
</style>
