<script setup lang="ts">
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
// eslint-disable-next-line @typescript-eslint/consistent-type-imports
import type { VForm } from 'vuetify/components/VForm'

interface Emit {
  (e: 'update:isDrawerOpen', value: boolean): void
  (e: 'macchinaData', value: object): void
}

interface Props {
  isDrawerOpen: boolean
  macchinaData?: object
}

const props = defineProps<Props>()
const emit = defineEmits<Emit>()
const nome = ref('')
const id = ref('')
const nome_gp = ref('')
const lavorazione = ref('')
const attivo = ref(true)
const report_gp = ref()

const isFormValid = ref(false)
const refForm = ref<VForm>()

const defaultItem = ref<any>({
  id: '',
  lavorazione: 0,
  nome_gp: '',
  nome: '',
  report_gp: '',
  attivo: 0,

})

const editedItem = ref<any>(props.macchinaData)

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
      emit('macchinaData', {
        nome: nome.value,
        lavorazione: lavorazione.value,
        nome_gp: nome_gp.value,
        report_gp: report_gp.value,
        attivo: attivo.value,
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
      :title="editedItem ? `${$t('Label.Modifica-Macchina')} Fai` : `${$t('Label.Nuovo-Macchina')} `"
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
              <!-- 👉 Macchina -->
              <VCol cols="12">
                <AppTextField
                  v-model="nome"
                  :rules="[requiredValidator]"
                  :label="$t('Label.Macchina')"
                  :placeholder="$t('Label.Macchina')"
                />
              </VCol>

              <!-- 👉 Nome Gp -->
              <VCol cols="12">
                <AppTextField
                  v-model="nome_gp"
                  :label="$t('Label.Nome Gp')"
                  :placeholder="$t('Label.Nome Gp')"
                />
              </VCol>

              <!-- 👉 Lavorazione -->
              <VCol cols="12">
                <AppSelect
                  v-model="lavorazione"
                  :label="$t('Label.Lavorazione')"
                  :placeholder="$t('Label.Lavorazione')"
                  :items="[{ title: 'Rame', value: 1 }, { title: 'Ottico', value: 2 }, { title: 'Entrambi', value: 3 }]"
                />
              </VCol>

              <VCol cols="12" class="mt-8">
                <VSwitch
                  v-model="attivo"
                  :label="$t('Label.Attivo')"
                />
              </VCol>

              <!-- 👉 Report Gp -->
              <VCol cols="12" class="mt-8">
                <VSwitch
                  v-model="report_gp"
                  :label="$t('Label.Report Go')"
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
