<script setup lang="ts">
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
// eslint-disable-next-line @typescript-eslint/consistent-type-imports
import type { VForm } from 'vuetify/components/VForm'
import type { RpRegisterLog, RpRegisterLogNotifiche } from '@/views/reception/type'

interface Emit {
  (e: 'update:isDrawerOpen', value: boolean): void
  (e: 'visitatoreData', value: RpRegisterLog): void
}

interface Props {
  isDrawerOpen: boolean
}

const props = defineProps<Props>()
const emit = defineEmits<Emit>()
const nome = ref('')
const email = ref('')
const azienda = ref('')
const wifi = ref(false)
const dataInizio = ref()
const dataFine = ref()
const users = ref([])

const isFormValid = ref(false)
const refForm = ref<VForm>()

const defaultReferenti = ref<RpRegisterLogNotifiche>({
  id: '',
  user: 0,
})

const defaultItem = ref<RpRegisterLog>({
  data_prevista: '',
  data_scadenza: '',
  nome: '',
  email: '',
  wifi: false,
  user_interni: defaultReferenti,

})

const editedItem = ref<RpRegisterLog>(defaultItem.value)

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
      emit('visitatoreData', {
        nome: nome.value,
        email: email.value,
        azienda: azienda.value,
        data_prevista: dataInizio.value,
        data_scadenza: dataFine.value,
        user_interni: users.value,
        wifi: wifi.value,
      })
      emit('update:isDrawerOpen', false)
      nextTick(() => {
        refForm.value?.reset()
        refForm.value?.resetValidation()
      })
    }
  })
}

const guestsOptions = ref([])

const userOptions = async () => {
  const resultData = await useApi<any>(createUrl('/users/getUsers'))
  const arr = []

  resultData.data.value.data.forEach(value => {
    arr.push({ full_name: value.full_name, id: value.email })
  })
  guestsOptions.value = arr
}

userOptions()

const handleDrawerModelValueUpdate = (val: boolean) => {
  emit('update:isDrawerOpen', val)
}
</script>

<template>
  <VNavigationDrawer
    temporary
    :width="600"
    location="end"
    class="scrollable-content"
    :model-value="props.isDrawerOpen"
    @update:model-value="handleDrawerModelValueUpdate"
  >
    <!-- 👉 Title -->
    <AppDrawerHeaderSection
      :title="editedItem.id ? `${$t('Label.Modifica-Visitatore')} Fai` : `${$t('Label.Nuovo-Visitatore')} `"
      @cancel="closeNavigationDrawer"
    />

    <PerfectScrollbar :options="{ wheelPropagation: false }">
      <VCard flat>
        <VCardText>
          <!-- 👉 Form -->
          <VForm
            ref="refForm"
            v-model="isFormValid"
            @submit.prevent="onSubmit"
          >
            <VRow>
              <!-- 👉 Visitatore -->
              <VCol cols="12">
                <AppTextField
                  v-model="nome"
                  :rules="[requiredValidator]"
                  :label="$t('Label.Visitatore')"
                  :placeholder="$t('Label.Visitatore')"
                />
              </VCol>

              <!-- 👉 Email -->
              <VCol cols="12">
                <AppTextField
                  v-model="email"
                  :rules="[requiredValidator, emailValidator]"
                  :label="$t('Label.E-mail')"
                  :placeholder="$t('Label.E-mail')"
                />
              </VCol>

              <!-- 👉 Azienda -->
              <VCol cols="12">
                <AppTextField
                  v-model="azienda"
                  :label="$t('Label.Azienda')"
                  :placeholder="$t('Label.Azienda')"
                />
              </VCol>

              <VCol cols="12" class="mt-8">
                <VSwitch
                  v-model="wifi"
                  :label="$t('Label.Wifi')"
                />
              </VCol>

              <!-- 👉 Start date -->
              <VCol cols="12">
                <AppDateTimePicker
                  v-model="dataInizio"
                  :rules="[requiredValidator]"
                  label="Data Inizio"
                  placeholder="Select Date"
                />
              </VCol>

              <!-- 👉 End date -->
              <VCol cols="12">
                <AppDateTimePicker
                  v-model="dataFine"
                  :rules="[requiredValidator]"
                  label="Data Fine"
                  placeholder="Select End Date"
                />
              </VCol>

              <!-- 👉 Interni -->
              <VCol cols="12">
                <AppSelect
                  v-model="users"
                  label="Utenti Interni Da Avvisare"
                  placeholder="Select Utenti Interni"
                  :items="guestsOptions"
                  :item-title="item => item.full_name"
                  :item-value="item => item.id"
                  chips
                  multiple
                  eager
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
    </PerfectScrollbar>
  </VNavigationDrawer>
</template>
