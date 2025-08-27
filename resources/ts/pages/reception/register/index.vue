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
import NavBarI18n from '@core/components/I18n.vue'

definePage({
  meta: {
    action: '',
    subject: '',
    layout: 'blank',
    public: true,
  },
})

/** * detection handling */

const result = ref('')

/** * select camera */

const { t } = useI18n()

const refSearchInput = ref<HTMLInputElement>()

const { locale } = useI18n({ useScope: 'global' })
const attivaBenvenuto = ref(true)
const attivaDevice = ref(false)
const registrazione = ref(false)
const item = ref<RpRegisterLog>()
const registerDialog = ref(false)
const refForm = ref<VForm>()
const informativaDialog = ref(false)
const informativaQrDialog = ref(false)
const qrcodeTesxt = ref('')
const settingsDialog = ref(false)
const totemSettings = ref({})
const autenticazioneSettingsDialog = ref(false)
const password = ref('')
const guestsOptions = ref([])
const totems = ref([])
const totemData = ref({})
const totemItem = useCookie('totemData')
const isDialogErrorCode = ref(false)
const errors = ref({})
const loadingPage = ref(false)
const informativaRegisterDialog = ref(false)

if (totemItem.value?.totem === undefined) {
  attivaBenvenuto.value = false
  attivaDevice.value = false
  registerDialog.value = false
  informativaDialog.value = false
  settingsDialog.value = false
  autenticazioneSettingsDialog.value = true
}

console.log(locale.value)
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

const onDetect = async () => {
  if (qrcodeTesxt.value !== '') {
    const { data: resultData } = await useApi<any>(createUrl('/getRegister', {
      query: {
        code: qrcodeTesxt.value,
      },
    }))

    if (resultData.value.success === true) {
      errors.value.code = ''
      item.value = { ...resultData.value.obj }

      if (item.value.cod_riferimento === qrcodeTesxt.value)
        item.value.cod_tessera = item.value.cod_riferimento
      item.value.ip_stampante = totemItem.value.ipStampante
      attivaDevice.value = false
      registrazione.value = true
    }
    else {
      isDialogErrorCode.value = true
      errors.value.code = t('Label.Errore-Lettura-Codice')
    }
  }
  refSearchInput.value?.focus()
  qrcodeTesxt.value = ''

  // registrazione
  // result.value = value.content
}

const start_qrcode = () => {
  informativaQrDialog.value = false
  attivaBenvenuto.value = false
  attivaDevice.value = true
  registerDialog.value = false

  console.log()
}

const start_registrazione = () => {
  attivaBenvenuto.value = false
  attivaDevice.value = true
}

const authSetting = () => {
  attivaBenvenuto.value = false
  attivaDevice.value = false
  registerDialog.value = false
  informativaDialog.value = false
  settingsDialog.value = false
  autenticazioneSettingsDialog.value = true
}

const openSettings = async () => {
  const authData = await $api('/register/totem/auth_setting', {
    method: 'POST',
    body: {
      password: password.value,
    },
  })

  if (authData.success === true) {
    autenticazioneSettingsDialog.value = false
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
  registrazione.value = false
  password.value = ''
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

const userOptions = async () => {
  const resultData = await useApi<any>(createUrl('/getReferenti'))
  const arr = []

  resultData.data.value.forEach(value => {
    arr.push({ full_name: value.nome, id: value.email })
  })

  guestsOptions.value = arr
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

const saveRegister = async () => {
  loadingPage.value = true

  const retuenData = await $api('/register/store', {
    method: 'POST',
    body: registerItem.value,
  })

  new_defaultItem()
  registerDialog.value = false
  informativaDialog.value = false
  attivaBenvenuto.value = true
  attivaDevice.value = false
  loadingPage.value = false
}

const closeRegister = () => {
  informativaDialog.value = false
  informativaQrDialog.value = false
  informativaRegisterDialog.value = false
  registerDialog.value = false
  attivaBenvenuto.value = true
  attivaDevice.value = false
}

const newRegister = () => {
  informativaRegisterDialog.value = false

  const today = new Date()

  userOptions()
  new_defaultItem()
  registerIndex.value = -1
  registerItem.value = { ...defaultItem.value }
  registerItem.value.data_prevista = today.toISOString().slice(0, 10)
  registerItem.value.data_scadenza = today.toISOString().slice(0, 10)
  attivaBenvenuto.value = false
  attivaDevice.value = false
  registerDialog.value = true
}

const informativaRegister = async () => {
  refForm.value?.validate().then(async ({ valid }) => {
    if (valid) {
      registerDialog.value = false
      if (totemItem.value.informativa === '1')
        informativaDialog.value = true
    }
  })
}

const informativaQr = () => {
  attivaBenvenuto.value = false
  informativaQrDialog.value = true
  registerDialog.value = false
}

const informativaRegistrazione = () => {
  attivaBenvenuto.value = false
  informativaRegisterDialog.value = true
  registerDialog.value = false
}

const back = () => {
  informativaDialog.value = false
  informativaQrDialog.value = false
  registerDialog.value = false
  attivaDevice.value = false
  registrazione.value = false
  attivaBenvenuto.value = true
}

const end = async () => {
  attivaBenvenuto.value = true
}

const setFocus = () => {
  isDialogErrorCode.value = false
  refSearchInput.value?.focus()
}
</script>

<template>
  <VRow class="mt-2">
    <VCol cols="1">
      <p class="text-right">
        {{ totemItem?.totem }}
      </p>
    </VCol>
    <VCol cols="8" />
    <VCol cols="3">
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
    </VCol>
  </VRow>
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
      <VRow>
        <VCol cols="12 mb-0">
          <VCard
            v-if="!registrazione"
            class="auth-card pa-4"
            max-width="800"
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

            <VCardText
              v-if="attivaBenvenuto"
              class="pt-1"
            >
              <!--
                h4 class="text-h4 mb-1">
                Benvento !
                </h4
              -->
              <p class="mb-0 text-center">
                {{ $t('Label.Scansione-Qr-Code') }}
              </p>
              <VCol
                cols="12"
                sm="12"
              >
                <VBtn
                  block
                  @click="informativaQr"
                >
                  {{ $t('Label.Clicca-Qui') }}
                </VBtn>
              </VCol>
            </VCardText>

            <VCardText v-if="attivaDevice">
              <VCard>
                <VTextField
                  ref="refSearchInput"
                  v-model="qrcodeTesxt"
                  :placeholder="$t('Label.Scansiona')"
                  :error-messages="errors.code"
                  autofocus
                  input-el-custom-attributes="{ 'my-attribute': 'readonly' }"
                  @focusout="onDetect"
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
                    {{ $t('Label.Esci') }}
                  </VBtn>
                </VCol>
              </VCard>
            </VCardText>
          </VCard>
        </VCol>
        <VCol
          v-if="!attivaDevice"
          cols="12"
        >
          <VCard
            v-if="!registrazione"
            class="auth-card pa-4"
            max-width="800"
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

            <VCardText
              v-if="attivaBenvenuto"
              class="pt-1"
            >
              <p class="mb-0 text-center">
                {{ $t('Label.Registrati') }}
              </p>
              <VCol
                cols="12"
                sm="12"
              >
                <VBtn
                  block
                  @click="informativaRegistrazione"
                >
                  {{ $t('Label.Clicca-Qui') }}
                </VBtn>
              </VCol>
            </VCardText>

            <VCardText v-if="attivaDevice">
              <VCard>
                <VCol
                  cols="12"
                  sm="12"
                >
                  <VBtn
                    block
                    variant="outlined"
                    @click="back"
                  >
                    {{ $t('Label.Esci') }}
                  </VBtn>
                </VCol>
              </VCard>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>

      <VCard
        v-if="registrazione"
        class="auth-card pa-2"
        max-width="1000"
        min-width="1000"
      >
        <AppRegistrazione
          v-model:isDrawerOpen="registrazione"
          md="6"
          :visitatore-data="item"
          @visitatore-data="end"
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
            {{ $t('Label.Esci') }}
          </VBtn>
        </VCol>
      </VCard>
    </div>
  </div>

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
              @submit.prevent="informativaRegister"
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
                  @click="closeRegister"
                >
                  Cancel
                </VBtn>

                <VBtn
                  type="submit"
                  color="success"
                  variant="elevated"
                  @click="saveRegister"
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
            @click="closeRegister"
          >
            {{ $t('Label.Non-Acconsento') }}
          </VBtn>

          <VBtn
            type="submit"
            color="success"
            variant="elevated"
            @click="start_qrcode"
          >
            {{ $t('Label.Ho-Preso-Visione') }}
          </VBtn>
        </VCardActions>
      </VCard>
    </AppCardActions>
  </VDialog>

  <!-- 👉 Informativa Registrazione  -->
  <VDialog
    v-model="informativaRegisterDialog"
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
            @click="closeRegister"
          >
            {{ $t('Label.Non-Acconsento') }}
          </VBtn>

          <VBtn
            type="submit"
            color="success"
            variant="elevated"
            @click="newRegister"
          >
            {{ $t('Label.Ho-Preso-Visione') }}
          </VBtn>
        </VCardActions>
      </VCard>
    </AppCardActions>
  </VDialog>

  <!-- 👉 Autenticazione Settings  -->
  <VDialog
    v-model="autenticazioneSettingsDialog"
    persistent
    class="v-dialog-sm"
  >
    <AppCardActions
      :title="$t('Label.Impostazioni-Totem')"
      no-actions
    >
      <VCard>
        <VCardText>
          <VContainer>
            <VForm
              ref="refForm"
              @submit.prevent="informativaRegister"
            >
              <VRow>
                <!-- 👉 Password -->
                <VCol cols="12">
                  <AppTextField
                    v-model="password"
                    :rules="[requiredValidator]"
                    type="password"
                    :label="$t('Label.Password')"
                    :placeholder="$t('Label.Password')"
                  />
                </VCol>
              </VRow>
              <VCardActions>
                <VSpacer />
                <VSpacer />
                <VBtn
                  type="reset"
                  color="error"
                  variant="outlined"
                  @click="autenticazioneSettingsDialog = false"
                >
                  Cancel
                </VBtn>

                <VBtn
                  type="submit"
                  color="success"
                  variant="elevated"
                  @click="openSettings"
                >
                  {{ $t('Label.Accedi') }}
                </VBtn>
              </VCardActions>
            </VForm>
          </VContainer>
        </VCardText>
      </VCard>
    </AppCardActions>
  </VDialog>

  <!-- 👉 Settings  -->
  <VDialog
    v-model="settingsDialog"
    persistent
    class="v-dialog-sm"
  >
    <AppCardActions
      :title="$t('Label.Impostazioni-Totem')"
      no-actions
    >
      <VCard>
        <VCardText>
          <VContainer>
            <VForm
              ref="refForm"
              @submit.prevent="informativaRegister"
            >
              <VRow>
                <!-- 👉 Totem -->
                <VCol cols="12">
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
              <VCardActions>
                <VSpacer />
                <VSpacer />
                <VBtn
                  type="reset"
                  color="error"
                  variant="outlined"
                  @click="closeRegister"
                >
                  {{ $t('Label.Esci') }}
                </VBtn>

                <VBtn
                  type="submit"
                  color="success"
                  variant="elevated"
                  @click="setTotem"
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
  <LoadingStandBy v-model="loadingPage"></LoadingStandBy>
</template>

<style>
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
.error {
  font-weight: bold;
  color: red;
}
.barcode-format-checkbox {
  margin-right: 10px;
  white-space: nowrap;
}
</style>
