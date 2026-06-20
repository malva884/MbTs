<script setup lang="ts">
import authV1BottomShape from '@images/svg/auth-v1-bottom-shape.svg?raw'
import authV1TopShape from '@images/svg/auth-v1-top-shape.svg?raw'
import { VNodeRenderer } from '@layouts/components/VNodeRenderer'
import { themeConfig } from '@themeConfig'
import {a} from "unplugin-vue-router/dist/options-8dbadba3";

const emit = defineEmits([
  'update:newPassword',
  'update:passwordRepeat',
  'update:validity',
])

definePage({
  alias: '/pages/change-password',
  meta: {
    layout: 'blank',
    public: true,
  },
})

const form = ref({
  oldPassword: '',
  newPassword: '',
  confirmPassword: '',
})

const router = useRouter()
const ability = useAbility()
const path = import.meta.env.VITE_BASE_URL

// TODO: Get type from backend
const userData = useCookie<any>('userData')

const isOldPasswordVisible = ref(false)
const isPasswordVisible = ref(false)
const isConfirmPasswordVisible = ref(false)
const errors = ref({})
const isSnackbarScrollReverseVisible = ref(false)
const message = ref('')
const color = ref('')

const onSubmit = async () => {
  errors.value = []
  // eslint-disable-next-line no-self-compare
  if (form.value.newPassword !== form.value.confirmPassword){
    errors.value.newPassword = 'la nuova password e conferma password non conidono'
    errors.value.confirmPassword = 'la nuova password e conferma password non conidono'
  }

  if (!errors.value.newPassword && form.value.newPassword.length < 8)
    errors.value.newPassword = 'la nuova password deve essere almeno di 8 caratteri'

  const retuenData = await $api('/account/changePassword', {
    method: 'POST',
    body: form.value,
  })

  if (retuenData.success) {
    message.value = retuenData.message
    color.value = 'success'
    isSnackbarScrollReverseVisible.value = true

    // Remove "accessToken" from cookie
    useCookie('accessToken').value = null

    // Remove "userData" from cookie
    userData.value = null

    // Redirect to login page
    await router.push('/login')

    // ℹ️ We had to remove abilities in then block because if we don't nav menu items mutation is visible while redirecting user to login page
    // Remove "userAbilities" from localStorage
    localStorage.removeItem('userAbilityRules')

    // Reset ability to initial ability
    ability.update([])
  }
  else {
    message.value = retuenData.message
    color.value = 'error'
    isSnackbarScrollReverseVisible.value = true
  }
}

const passwordRequirements = computed(() => ([
  {
    name: 'Must contain uppercase letters',
    predicate: form.value.newPassword.toLowerCase() !== form.value.newPassword,
  },
  {
    name: 'Must contain lowercase letters',
    predicate: form.value.newPassword.toUpperCase() !== form.value.newPassword,
  },
  {
    name: 'Must contain numbers',
    predicate: /\d/.test(form.value.newPassword),
  },
  {
    name: 'Must contain symbols',
    predicate: /\W/.test(form.value.newPassword),
  },
  {
    name: 'Must be at least 8 characters long',
    predicate: form.value.newPassword.length >= 8,
  },
  {
    name: 'Must match',
    predicate: form.value.newPassword === form.value.confirmPassword,
  },
]))

const logout = async () => {
  // Remove "accessToken" from cookie
  useCookie('accessToken').value = null

  // Remove "userData" from cookie
  userData.value = null

  // Redirect to login page
  await router.push('/login')

  // ℹ️ We had to remove abilities in then block because if we don't nav menu items mutation is visible while redirecting user to login page
  // Remove "userAbilities" from cookie
  useCookie('userAbilityRules').value = null

  // Reset ability to initial ability
  ability.update([])
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

        <VCardText class="pt-2">
          <h4 class="text-h4 mb-1">
            Reset Password
          </h4>
          <p class="mb-0">
            <span class="font-weight-bold">Your password has expired, please change it.</span>
          </p>
        </VCardText>

        <VCardText>
          <VSnackbar
            v-model="isSnackbarScrollReverseVisible"
            transition="scroll-y-reverse-transition"
            location="top central"
            :color="color"
          >
            {{ $t(message) }}
          </VSnackbar>
          <VForm @submit.prevent="onSubmit">
            <VRow>
              <!-- old password -->
              <VCol cols="12">
                <AppTextField
                  v-model="form.oldPassword"
                  :rules="[requiredValidator]"
                  autofocus
                  label="Password Corrente"
                  placeholder="Password Corrente"
                  :type="isOldPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="isOldPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  @click:append-inner="isOldPasswordVisible = !isOldPasswordVisible"
                />
              </VCol>

              <!-- password -->
              <VCol cols="12">
                <AppTextField
                  v-model="form.newPassword"
                  :rules="[requiredValidator]"
                  label="New Password"
                  placeholder="Nuova Password"
                  :type="isPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="isPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  @click:append-inner="isPasswordVisible = !isPasswordVisible"
                  :error-messages="errors.newPassword"
                />
              </VCol>

              <!-- Confirm Password -->
              <VCol cols="12">
                <AppTextField
                  v-model="form.confirmPassword"
                  :rules="[requiredValidator]"
                  label="Confirm Password"
                  placeholder="Confirm Password"
                  :type="isConfirmPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="isConfirmPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  @click:append-inner="isConfirmPasswordVisible = !isConfirmPasswordVisible"
                  :error-messages="errors.confirmPassword"
                />
              </VCol>

              <!-- reset password -->
              <VCol cols="12">
                <VBtn
                  block
                  type="submit"
                >
                  Set New Password
                </VBtn>
              </VCol>

              <!-- back to login -->
              <VCol cols="12">
                  <VIcon
                    icon="tabler-chevron-left"
                    class="flip-in-rtl"
                    @click="logout"
                  />
                  <span @click="logout" >Back to login</span>

              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </div>
  </div>
</template>

<style lang="scss">
@use "@core-scss/template/pages/page-auth.scss";
</style>
