<script setup lang="ts">
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
// eslint-disable-next-line @typescript-eslint/consistent-type-imports
import type { VForm } from 'vuetify/components/VForm'


interface Emit {
  (e: 'update:isDrawerOpen', value: boolean): void
  (e: 'userData', value: UserProperties): void
}

interface Props {
  isDrawerOpen: boolean
}

const props = defineProps<Props>()
const emit = defineEmits<Emit>()

const isFormValid = ref(false)
const refForm = ref<VForm>()
const nome = ref('')
const cognome = ref('')
const email = ref('')
const sesso = ref('')
const mobile = ref()
const interno = ref('')
const lingua = ref('')
const stato = ref()
const password = ref()
const role = ref()

// 👉 drawer close
const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false)

  nextTick(() => {
    refForm.value?.reset()
    refForm.value?.resetValidation()
  })
}

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      emit('userData', {
        id: 0,
        nome: nome.value,
        cognome: cognome.value,
        role: role.value,
        sesso: sesso.value,
        mobile: mobile.value,
        interno: interno.value,
        email: email.value,
        stato: stato.value,
        password: password.value,
        lingua: lingua.value,
      })
      emit('update:isDrawerOpen', false)
      nextTick(() => {
        refForm.value?.reset()
        refForm.value?.resetValidation()
      })

    }
  })
}

const handleDrawerModelValueUpdate = (val: boolean) => {
  emit('update:isDrawerOpen', val)
}
</script>

<template>
  <VDialog
      :width="$vuetify.display.smAndDown ? 'auto' : 1000"
      :model-value="props.isDrawerOpen"
      @update:model-value="handleDrawerModelValueUpdate"
  >
    <!-- 👉 Dialog close btn -->
    <DialogCloseBtn @click="closeNavigationDrawer" />

    <VCard class="pa-sm-8 pa-5">
      <!-- 👉 Title -->
      <VCardItem class="text-center">
        <VCardTitle class="text-h3 mb-3">
          New User
        </VCardTitle>

      </VCardItem>

      <VCardText class="mt-6">
        <!-- 👉 Form -->
        <VForm
            ref="refForm"
            v-model="isFormValid"
            @submit.prevent="onSubmit"
        >
          <VRow>
            <!-- 👉 Full name -->
            <VCol cols="6">
              <AppTextField
                  v-model="nome"
                  :rules="[requiredValidator]"
                  label="Nome"
                  placeholder="Nome"
              />
            </VCol>

            <!-- 👉 Username -->
            <VCol cols="6">
              <AppTextField
                  v-model="cognome"
                  :rules="[requiredValidator]"
                  label="Cognome"
                  placeholder="Cognome"
              />
            </VCol>

            <!-- 👉 Email -->
            <VCol cols="6">
              <AppTextField
                  v-model="email"
                  :rules="[requiredValidator, emailValidator]"
                  label="Email"
                  placeholder="Email"
              />
            </VCol>

            <VCol cols="6">
              <AppTextField
                  v-model="password"
                  :rules="[requiredValidator]"
                  label="Password"
                  placeholder="Password"
              />
            </VCol>

            <!-- 👉 mobile -->
            <VCol cols="6">
              <AppTextField
                  v-model="mobile"
                  label="Cellulare"
                  placeholder="Cellulare"
              />
            </VCol>

            <!-- 👉 Interno -->
            <VCol cols="6">
              <AppTextField
                  v-model="interno"
                  label="Interno"
                  placeholder="Interno"
              />
            </VCol>

            <!-- 👉 Sesso -->
            <VCol cols="6">
              <AppSelect
                  v-model="sesso"
                  label="Sesso"
                  placeholder="Seleziona Sesso"
                  :rules="[requiredValidator]"
                  :items="[{title: 'Maschio', value:'m'},{title: 'Femmina', value:'f'}]"
              />
            </VCol>

            <!-- 👉 Lingua -->
            <VCol cols="6">
              <AppSelect
                  v-model="lingua"
                  label="Lingua Sistema"
                  placeholder="Seleziona Lingua"
                  :rules="[requiredValidator]"
                  :items="[{title: 'Italiano', value:'ita'},{title: 'Inglese', value:'eng'}]"
              />
            </VCol>

            <!-- 👉 Role -->
            <VCol cols="6">
              <AppSelect
                  v-model="role"
                  label="Select Role"
                  placeholder="Seleziona Role"
                  :rules="[requiredValidator]"
                  :items="[{title: 'User', value:'user'},{title: 'Admin', value:'admin'},{title: 'Super Admin', value:'super admin'}]"
              />
            </VCol>

            <!-- 👉 Status -->
            <VCol cols="6">
              <AppSelect
                  v-model="stato"
                  label="Seleziona Stato"
                  placeholder="Seleziona Stato"
                  :rules="[requiredValidator]"
                  :items="[{ title: 'Attivo', value: '1' }, { title: 'Disattivo', value: '0' }]"
              />
            </VCol>

            <!-- 👉 Submit and Cancel -->
            <VCol cols="12">
              <VBtn
                  type="submit"
                  class="me-3"
              >
                Submit
              </VBtn>
              <VBtn
                  type="reset"
                  variant="outlined"
                  color="secondary"
                  @click="closeNavigationDrawer"
              >
                Cancel
              </VBtn>
            </VCol>
          </VRow>
        </VForm>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<style lang="scss">
.permission-table {
  td {
    border-block-end: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    padding-block: 0.5rem;

    .v-checkbox {
      min-inline-size: 4.75rem;
    }

    &:not(:first-child) {
      padding-inline: 0.5rem;
    }

    .v-label {
      white-space: nowrap;
    }
  }
}
</style>

