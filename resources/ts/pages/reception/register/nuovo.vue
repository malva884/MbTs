<script setup lang="ts">
import { VForm } from 'vuetify/components/VForm'
import { useI18n } from 'vue-i18n'
import authV1BottomShape from '@images/svg/auth-v1-bottom-shape.svg?raw'
import authV1TopShape from '@images/svg/auth-v1-top-shape.svg?raw'
import { VNodeRenderer } from '@layouts/components/VNodeRenderer'
import { themeConfig } from '@themeConfig'
import type { RpRegisterLog } from '@/views/reception/type'
import informativaIt from '@images/files/informativaIt.pdf'
import informativaEn from '@images/files/informativaEn.pdf'
import saluti from '@images/custom/saluti.png'
import benvenuto from '@images/custom/benvenuto.png'
import scanQrCode from '@images/custom/scanQrCode.png'
import registrazione from '@images/custom/registrazione.png'
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
const totemSettings = ref([])
const totemItem = useCookie('totemData')
const totemData = ref({})
const refCodeInput = ref<HTMLInputElement>()
const item = ref<RpRegisterLog>()
const refForm = ref<VForm>()
const homeDialog = ref(true)
const registerDialog = ref(false)
const authDialog = ref(false)
const settingsDialog = ref(false)
const salutiDialog = ref(false)
const benvenutoDialog = ref(false)
const informativaQrDialog = ref(false)
const informativaRgDialog = ref(false)
const qrcodeTesxt = ref()
const password = ref()
const isPasswordVisible = ref(false)
const erroreLettura = ref('')
const totems = ref([])
const guestsOptions = ref([])
const loadingPage = ref(false)
const professing = ref(false)

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
})

function new_defaultItem() {
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
    ip_stampante: totemItem.value.ipStampante,
  }
}

const registerItem = ref<any>(defaultItem.value)
const registerIndex = ref(-1)

if (totemItem.value?.totem === undefined) {
  homeDialog.value = false
  authDialog.value = true
}

const authSetting = () => {
  homeDialog.value = false
  authDialog.value = true
}

const closeSetting = () => {
  if (totemItem.value?.totem !== undefined) {
    authDialog.value = false
    settingsDialog.value = false
    homeDialog.value = true
  }
  else {
    authDialog.value = false
    settingsDialog.value = false
  }
  password.value = ''
}

const home = () => {
  qrcodeTesxt.value = ''
  registerItem.value = ''
  new_defaultItem()
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
}

const setTotem = () => {
  const totem = totemData.value.find(i => i.id === totemSettings.value.totem)

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
    informativa: '0',
  }

  settingsDialog.value = false
  homeDialog.value = true
  password.value = ''
}

const totemList = async () => {
  const resultData = await useApi<any>(createUrl('/totemList'))
  const arr = []

  totemData.value = resultData.data.value.totem
  resultData.data.value.totem.forEach(value => {
    arr.push({ full_name: value.nome, id: value.id })
  })
  totems.value = arr
}

totemList()

const hidenImage = () => {
  salutiDialog.value = false
  benvenutoDialog.value = false
  homeDialog.value = true
}

const processCode = async (event: any) => {
  if (event.length >= 18 && professing.value === false) {
    professing.value = true

    const { data: resultData } = await useApi<any>(createUrl('/getRegister', {
      query: {
        code: qrcodeTesxt.value,
      },
    }))

    if (resultData.value.success === true) {
      erroreLettura.value = ''

      if (resultData.value.azione === 'Entrata') {
        registerItem.value = { ...resultData.value.obj }
        homeDialog.value = false
        informativaQrDialog.value = true
      }
      else {
        homeDialog.value = false
        salutiDialog.value = true
        setTimeout(hidenImage, 5000)
      }
    }
    else {

      erroreLettura.value = t('Label.Errore-Lettura-Codice')
    }
    qrcodeTesxt.value = ''
    refCodeInput.value?.focus()
    professing.value = false
  }
}

const accessQr = async () => {
  loadingPage.value = true
  registerItem.value.ip_stampante = totemItem.value.ipStampante

  await $api('/reception/storeRegister', {
    method: 'POST',
    body: registerItem.value,
  })

  qrcodeTesxt.value = ''
  informativaQrDialog.value = false
  loadingPage.value = false
  benvenutoDialog.value = true
  setTimeout(hidenImage, 5000)
}

const userOptions = async () => {
  const resultData = await useApi<any>(createUrl('/getReferenti'))
  const arr = []

  resultData.data.value.forEach(value => {
    arr.push({ full_name: value.nome, id: value.email })
  })

  guestsOptions.value = arr
}

const searchVisitor = async () => {
  const { data: resultData } = await useApi<any>(createUrl('/register/searchVisitor', {
    query: {
      email: registerItem.value.email,
    },
  }))

  registerItem.value.id = resultData.value.id
  registerItem.value.nome = resultData.value.nome
  registerItem.value.azienda = resultData.value.azienda
}

const openRegister = () => {
  const today = new Date()

  userOptions()
  new_defaultItem()
  registerIndex.value = -1
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
    if (valid) {
      loadingPage.value = true
      await $api('/register/store', {
        method: 'POST',
        body: registerItem.value,
      })
      new_defaultItem()
      registerDialog.value = false
      informativaQrDialog.value = false
      loadingPage.value = false
      benvenutoDialog.value = true
      setTimeout(hidenImage, 5000)
      setTimeout(home, 7000)
    }
  })
}

const setFocus = () => {
  qrcodeTesxt.value = ''
  refCodeInput.value?.focus()
  setTimeout(setFocus, 2000)
}

const refresh = () => {
  location.reload()
}

onMounted(() => {

  refCodeInput.value?.focus()
  setTimeout(setFocus, 2000)

})
</script>

<template>
  <VRow class="mt-0">
    <VCol cols="1">
      <p class="text-right">
        {{ totemItem?.totem }}
      </p>
    </VCol>
    <VCol cols="7" />
    <VCol cols="4">
      <NavBarI18n
        v-if="themeConfig.app.i18n.enable && themeConfig.app.i18n.langConfig?.length"
        :languages="themeConfig.app.i18n.langConfig"
      />
      <VBtn
        icon="tabler-settings"
        variant="text"
        color="secondary"
        @click="authSetting"
      />
      <VBtn
        icon="tabler-refresh"
        variant="text"
        color="secondary"
        @click="refresh"
      />
    </VCol>
  </VRow>
  <div class="auth-wrapper d-flex align-center justify-center pa-1">
    <div
      v-if="homeDialog"
      class="position-relative my-sm-16"
    >
      <!-- 👉 Top shape -->
      <VNodeRenderer
        :nodes="h('div', { innerHTML: authV1TopShape })"
        class="text-primary auth-v1-top-shape d-none d-sm-block"
      />

      <!-- 👉 Bottom shape -->
      <VNodeRenderer
        :nodes="h('div', { innerHTML: authV1BottomShape })"
        class="text-primary auth-v1-bottom-shape d-none d-sm-block"
      />

      <VRow>
        <VCol cols="12 mb-0">
          <VCard
            v-if="homeDialog"
            class="auth-card pa-1"
            max-width="800"
          >
            <VCardItem class="justify-center mb--4">
              <template #prepend>
                <div>
                  <VNodeRenderer :nodes="themeConfig.app.logo" />
                </div>
                <div>
                  <VImg
                    :src="scanQrCode"
                    width="150"
                    class="mx-auto d-block mt--10"
                  />
                </div>
                <VCol
                  cols="12"
                  sm="12"
                  class="mb-0"
                >
                  <VTextField
                    ref="refCodeInput"
                    v-model="qrcodeTesxt"
                    variant="underlined"
                    :placeholder="$t('Label.Scansiona')"
                    :error-messages="erroreLettura"
                    @input="processCode($event.target.value)"
                  />
                </VCol>
              </template>
            </VCardItem>
          </VCard>
        </VCol>
        <VCol cols="12">
          <VCard
            class="auth-card pa-4"
            max-width="800"
          >
            <VCardItem class="justify-center">
              <template #prepend>
                <div>
                  <VImg
                    :src="registrazione"
                    width="150"
                    class="mx-auto d-block mt--10"
                  />
                </div>
              </template>

              <VCardTitle class="font-weight-bold text-capitalize text-h3 py-1">
                {{ themeConfig.app.title }}
              </VCardTitle>
            </VCardItem>

            <VCardText class="pt-1">
              <VCol
                cols="12"
                sm="12"
              >
                <VBtn
                  block
                  @click="informativaRegistrazione"
                >
                  {{ $t('Label.Registrati') }}
                </VBtn>
              </VCol>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>
    </div>
    <div
      v-if="benvenutoDialog"
      class="position-relative my-sm-16"
    >
      <VRow>
        <VCol cols="12">
          <VCard
            class="auth-card pa-4"
            max-width="800"
          >
            <VImg
              :src="benvenuto"
              width="600"
            />
          </VCard>
        </VCol>
      </VRow>
    </div>
    <div
      v-if="salutiDialog"
      class="position-relative my-sm-16"
    >
      <VRow>
        <VCol cols="12">
          <VCard
            class="auth-card pa-4"
            max-width="800"
          >
            <VImg
              :src="saluti"
              width="600"
            />
          </VCard>
        </VCol>
      </VRow>
    </div>
  </div>

  <!-- 👉 Auth Settings  -->
  <VDialog
    v-model="authDialog"
    max-width="400"
  >
    <!-- Dialog Content -->
    <VCard :title="$t('Label.Impostazioni-Totem')">
      <VCardText>
        <VRow>
          <VCol
            cols="12"
            md="12"
          >
            <AppTextField
              v-model="password"
              :label="$t('Label.Password')"
              :placeholder="$t('Label.Password')"
              :type="isPasswordVisible ? 'text' : 'password'"
              :append-inner-icon="isPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
              :rules="[requiredValidator]"
              @click:append-inner="isPasswordVisible = !isPasswordVisible"
            />
          </VCol>
        </VRow>
      </VCardText>

      <VCardText class="d-flex justify-end flex-wrap gap-3">
        <VBtn
          variant="tonal"
          color="secondary"
          @click="closeSetting"
        >
          Close
        </VBtn>
        <VBtn @click="openSettings">
          {{ $t('Label.Accedi') }}
        </VBtn>
      </VCardText>
    </VCard>
  </VDialog>

  <!-- 👉 Settings Totem  -->
  <VDialog
    v-model="settingsDialog"
    max-width="400"
  >
    <!-- Dialog Content -->
    <VCard :title="$t('Label.Impostazioni-Totem')">
      <VCardText>
        <VRow>
          <VCol
            cols="12"
            md="12"
          >
            <AppSelect
              v-model="totemSettings.totem"
              :label="$t('Label.Totem')"
              :placeholder="$t('Label.Totem')"
              :items="totems"
              :item-title="item => item.full_name"
              :item-value="item => item.id"
              chips
              eager
            />
          </VCol>
        </VRow>
      </VCardText>

      <VCardText class="d-flex justify-end flex-wrap gap-3">
        <VBtn
          variant="tonal"
          color="secondary"
          @click="closeSetting"
        >
          Close
        </VBtn>
        <VBtn @click="setTotem">
          {{ $t('Label.Salva') }}
        </VBtn>
      </VCardText>
    </VCard>
  </VDialog>

  <!-- 👉 Informativa  -->
  <VDialog
    v-model="informativaQrDialog"
    max-width="1800px"
    persistent
  >
    <AppCardActions
      :title="$t('Label.Informativa')"
      no-actions
    >
      <VCard>
        <VCardText>
          <VContainer>
            <div>
              <iframe
                :src="locale === 'it' ? informativaIt : informativaEn"
                class="mt--1"
                width="100%"
                height="1300px%"
              />
            </div>
          </VContainer>
        </VCardText>

        <VCardActions>
          <VSpacer />
          <VBtn
            type="reset"
            color="error"
            variant="outlined"
            @click="home"
          >
            {{ $t('Label.Non-Acconsento') }}
          </VBtn>

          <VBtn
            type="submit"
            color="success"
            variant="elevated"
            @click="accessQr"
          >
            {{ $t('Label.Ho-Preso-Visione') }}
          </VBtn>
        </VCardActions>
      </VCard>
    </AppCardActions>
  </VDialog>

  <!-- 👉 Informativa Registrazione  -->
  <VDialog
    v-model="informativaRgDialog"
    max-width="1800px"
    persistent
  >
    <AppCardActions
      :title="$t('Label.Informativa')"
      no-actions
    >
      <VCard>
        <VCardText>
          <VContainer>
            <div>
              <iframe
                :src="locale === 'it' ? informativaIt : informativaEn"
                class="mt--1"
                width="100%"
                height="1300px%"
              />
            </div>
          </VContainer>
        </VCardText>

        <VCardActions>
          <VSpacer />
          <VBtn
            type="reset"
            color="error"
            variant="outlined"
            @click="home"
          >
            {{ $t('Label.Non-Acconsento') }}
          </VBtn>

          <VBtn
            type="submit"
            color="success"
            variant="elevated"
            @click="openRegister"
          >
            {{ $t('Label.Ho-Preso-Visione') }}
          </VBtn>
        </VCardActions>
      </VCard>
    </AppCardActions>
  </VDialog>

  <!-- 👉 Register User  -->
  <VDialog
    v-model="registerDialog"
    max-width="900px"
    persistent
  >
    <AppCardActions
      :title="$t('Label.Registrazione-Visitatore')"
      no-actions
    >
      <VCard>
        <VCardText>
          <VContainer>
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
                    :rules="[requiredValidator, emailValidator]"
                    @focusout="searchVisitor"
                  />
                </VCol>

                <!-- 👉 Nome -->
                <VCol cols="12">
                  <AppTextField
                    v-model="registerItem.nome"
                    :rules="[requiredValidator]"
                    :label="$t('Label.Nome-Cognome')"
                    :placeholder="$t('Label.Nome-Cognome')"
                  />
                </VCol>

                <!-- 👉Azienda -->
                <VCol cols="12">
                  <AppTextField
                    v-model="registerItem.azienda"
                    :label="$t('Label.Azienda')"
                    :placeholder="$t('Label.Azienda')"
                  />
                </VCol>

                <!-- 👉 Interni -->
                <VCol cols="12">
                  <AppSelect
                    v-model="registerItem.user_interni"
                    :label="$t('Label.Referente')"
                    :placeholder="$t('Label.Referente')"
                    :rules="[requiredValidator]"
                    :items="guestsOptions"
                    :item-title="item => item.full_name"
                    :item-value="item => item.id"
                    chips
                    eager
                  />
                </VCol>

                <VCol
                  cols="12"
                  class="mt-8"
                >
                  <VSwitch
                    v-model="registerItem.wifi"
                    :label="$t('Label.Accesso-Wifi')"
                  />
                </VCol>
              </VRow>
              <VCardActions>
                <VSpacer />
                <VBtn
                  type="reset"
                  color="error"
                  variant="outlined"
                  @click="home"
                >
                  Cancel
                </VBtn>

                <VBtn
                  type="submit"
                  color="success"
                  variant="elevated"
                  @click="refForm?.validate()"
                >
                  {{ $t('Label.Registrati') }}
                </VBtn>
              </VCardActions>
            </VForm>
          </VContainer>
        </VCardText>
      </VCard>
    </AppCardActions>
  </VDialog>
  <LoadingStandBy v-model="loadingPage" />
</template>

<style lang="scss">
.layout-blank {
  .auth-wrapper {
    min-block-size: 70dvh;
  }

  .auth-v1-top-shape,
  .auth-v1-bottom-shape {
    position: absolute;
    block-size: 233px;
    inline-size: 238px;
  }

  .auth-footer-mask {
    position: absolute;
    inset-block-end: 0;
    min-inline-size: 100%;
  }

  .auth-card {
    z-index: 1 !important;
  }

  .auth-illustration {
    z-index: 1;
  }

  .auth-v1-top-shape {
    inset-block-start: -77px;
    inset-inline-start: -40px;
  }

  .auth-v1-bottom-shape {
    inset-block-end: -55px;
    inset-inline-end: -55px;
  }
}
</style>
