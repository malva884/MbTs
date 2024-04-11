<script lang="ts" setup>
import {VForm} from 'vuetify/components/VForm'
//import {Account} from "@/views/user/type"

const resultData = await useApi<any>(createUrl('/account/'))

const refInputEl = ref<HTMLElement>()
const path = import.meta.env.VITE_BASE_URL
const isConfirmDialogOpen = ref(false)
const accountDataLocal = ref(structuredClone({ ...resultData.data.value }))
const isAccountDeactivated = ref(false)
const isFormValid = ref(false)
const refForm = ref<VForm>()
const isSnackbarScrollReverseVisible = ref(false)
const message = ref('')
const color = ref('')

const validateAccountDeactivation = [(v: string) => !!v || 'Please confirm account deactivation']

const resetForm = () => {
  accountDataLocal.value = structuredClone({ ...resultData.data.value })
}

// changeAvatar function
const changeAvatar = (file: Event) => {
  const fileReader = new FileReader()
  const { files } = file.target as HTMLInputElement

  if (files && files.length) {
    fileReader.readAsDataURL(files[0])
    fileReader.onload = () => {
      if (typeof fileReader.result === 'string')
        accountDataLocal.value.avatarImg = fileReader.result
    }
  }
}

// reset avatar image
const resetAvatar = () => {
  accountDataLocal.value.avatarImg = resultData.data.value.avatar
}

const onSubmit = async () => {
  refForm.value?.validate().then(async ({ valid }) => {
    if (valid) {
      const retuenData = await $api('/account/update', {
        method: 'POST',
        body: accountDataLocal.value,
      })

      message.value = retuenData.message
      color.value = retuenData.color
      isSnackbarScrollReverseVisible.value = true

      nextTick(() => {
        refForm.value?.resetValidation()
      })
    }
  })
}
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VSnackbar
        v-model="isSnackbarScrollReverseVisible"
        transition="scroll-y-reverse-transition"
        location="top central"
        :color="color"
      >
        {{ $t(message) }}
      </VSnackbar>
      <VCard title="Profile Details">
        <VCardText class="d-flex">
          <!-- 👉 Avatar -->
          <VAvatar
            rounded
            size="100"
            class="me-6"
            :image="path + resultData.data.value.avatar"
          />

          <!-- 👉 Upload Photo -->
          <form class="d-flex flex-column justify-center gap-4">
            <div class="d-flex flex-wrap gap-2">
              <VBtn
                color="primary"
                @click="refInputEl?.click()"
              >
                <VIcon
                  icon="tabler-cloud-upload"
                  class="d-sm-none"
                />
                <span class="d-none d-sm-block">Upload new photo</span>
              </VBtn>

              <input
                ref="refInputEl"
                type="file"
                name="file"
                accept=".jpeg,.png,.jpg,GIF"
                hidden
                @input="changeAvatar"
              >

              <VBtn
                type="reset"
                color="secondary"
                variant="tonal"
                @click="resetAvatar"
              >
                <span class="d-none d-sm-block">Reset</span>
                <VIcon
                  icon="tabler-refresh"
                  class="d-sm-none"
                />
              </VBtn>
            </div>

            <p class="text-body-1 mb-0">
              Allowed JPG, GIF or PNG. Max size of 800K
            </p>
          </form>
        </VCardText>

        <VDivider />

        <VCardText class="pt-2">
          <!-- 👉 Form -->
          <VForm
            ref="refForm"
            v-model="isFormValid"
            @submit.prevent="onSubmit"
            class="mt-6">
            <VRow>
              <!-- 👉 First Name -->
              <VCol
                md="6"
                cols="12"
              >
                <AppTextField
                  v-model="accountDataLocal.nome"
                  :placeholder="$t('Label.Nome')"
                  :label="$t('Label.Nome')"
                />
              </VCol>

              <!-- 👉 Last Name -->
              <VCol
                md="6"
                cols="12"
              >
                <AppTextField
                  v-model="accountDataLocal.cognome"
                  :placeholder="$t('Label.Cognome')"
                  :label="$t('Label.Cognome')"
                />
              </VCol>

              <!-- 👉 Email -->
              <VCol
                cols="12"
                md="6"
              >
                <AppTextField
                  v-model="accountDataLocal.email"
                  :label="$t('Label.E-mail')"
                  type="email"
                  readonly
                />
              </VCol>

              <!-- 👉 Cell -->
              <VCol
                cols="12"
                md="6"
              >
                <AppTextField
                  v-model="accountDataLocal.mobile"
                  :label="$t('Label.Cell')"
                  :placeholder="$t('Label.Cell')"
                />
              </VCol>

              <!-- 👉 Phone -->
              <VCol
                cols="12"
                md="6"
              >
                <AppTextField
                  v-model="accountDataLocal.interno"
                  :label="$t('Label.Interno')"
                  :placeholder="$t('Label.Interno')"
                />
              </VCol>

              <!-- 👉 Language -->
              <VCol
                cols="12"
                md="6"
              >
                <AppSelect
                  v-model="accountDataLocal.lingua"
                  :label="$t('Label.Lingua')"
                  :placeholder="$t('Label.Lingua')"
                  :items="[{title: 'Italiano', value:'ita'},{title: 'Inglese', value:'eng'}]"
                />
              </VCol>

              <!-- 👉 Form Actions -->
              <VCol
                cols="12"
                class="d-flex flex-wrap gap-4"
              >
                <VBtn type="submit">
                  Salva
                </VBtn>

                <VBtn
                  color="secondary"
                  variant="tonal"
                  type="reset"
                  @click.prevent="resetForm"
                >
                  Reset
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </VCol>

  </VRow>

  <!-- Confirm Dialog -->
  <ConfirmDialog
    v-model:isDialogVisible="isConfirmDialogOpen"
    confirmation-question="Are you sure you want to deactivate your account?"
    confirm-title="Deactivated!"
    confirm-msg="Your account has been deactivated successfully."
    cancel-title="Cancelled"
    cancel-msg="Account Deactivation Cancelled!"
  />
</template>
