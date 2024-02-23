<script setup lang="ts">
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
// eslint-disable-next-line @typescript-eslint/consistent-type-imports
import type { VForm } from 'vuetify/components/VForm'

interface UserData {
    id: number | null
    nome: string
    cognome: string
    role: string
    mobile: string
    interno: string
    email: string
    sesso: string
    stato: string
    avatar: string
    lingua: string
}

interface Emit {
  (e: 'update:isDrawerOpen', value: boolean): void
  (e: 'userData', value: UserData): void
}

interface Props {
    userData?: UserData
    isDrawerOpen: boolean
}

const props = defineProps<Props>()
const emit = defineEmits<Emit>()
const userData = ref<UserData>(structuredClone(toRaw(props.userData)))

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
    emit('update:isDrawerOpen', false)
    emit('userData', userData.value)
}

const onFormReset = () => {

    userData.value = structuredClone(toRaw(props.userData))

    emit('update:isDrawerOpen', false)
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
                <!-- 👉 First Name -->
                <VCol
                        cols="12"
                        md="6"
                >
                    <AppTextField
                            v-model="userData.nome"
                            label="Nome"
                            placeholder="Nome"
                    />
                </VCol>

                <!-- 👉 Last Name -->
                <VCol
                        cols="12"
                        md="6"
                >
                    <AppTextField
                            v-model="userData.cognome"
                            label="Cognome"
                            placeholder="Cognome"
                    />
                </VCol>

                <!-- 👉 Billing Email -->
                <VCol
                        cols="12"
                        md="6"
                >
                    <AppTextField
                            v-model="userData.email"
                            label="Email"
                            placeholder="Email"
                    />
                </VCol>

                <!-- 👉 Status -->
                <VCol
                        cols="12"
                        md="6"
                >
                    <AppSelect
                            v-model="userData.role"
                            label="Select Role"
                            placeholder="Seleziona Role"
                            :rules="[requiredValidator]"
                            :items="[{title: 'User', value:'user'},{title: 'Admin', value:'admin'},{title: 'Super Admin', value:'super admin'}]"
                    />
                </VCol>

                <!-- 👉 Tax Id -->
                <VCol
                        cols="12"
                        md="6"
                >
                    <AppTextField
                            v-model="userData.mobile"
                            label="Cellulare"
                            placeholder="Cellulare"
                    />
                </VCol>

                <!-- 👉 Contact -->
                <VCol
                        cols="12"
                        md="6"
                >
                    <AppTextField
                            v-model="userData.interno"
                            label="Interno"
                            placeholder="Interno"
                    />
                </VCol>

                <!-- 👉 Language -->
                <VCol
                        cols="12"
                        md="6"
                >
                    <AppSelect
                            v-model="userData.lingua"
                            label="Lingua Sistema"
                            placeholder="Seleziona Lingua"
                            :rules="[requiredValidator]"
                            :items="[{title: 'Italiano', value:'ita'},{title: 'Inglese', value:'eng'}]"
                    />
                </VCol>

                <!-- 👉 Country -->
                <VCol
                        cols="12"
                        md="6"
                >
                    <AppSelect
                            v-model="userData.stato"
                            label="Seleziona Stato"
                            placeholder="Seleziona Stato"
                            :rules="[requiredValidator]"
                            :items="[{ title: 'Attivo', value: '1' }, { title: 'Disattivo', value: '0' }]"
                    />
                </VCol>

                <!-- 👉 Submit and Cancel -->
                <VCol
                        cols="12"
                        class="d-flex flex-wrap justify-center gap-4"
                >
                    <VBtn type="submit">
                        Submit
                    </VBtn>

                    <VBtn
                            color="secondary"
                            variant="tonal"
                            @click="onFormReset"
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

