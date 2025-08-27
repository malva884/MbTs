<script setup lang="ts">

import { VForm } from 'vuetify/components/VForm'
import type { Fornitore } from '@/views/quality/fornitore/type'

interface Emit {
  (e: 'update:isDrawerOpen', value: boolean): void
  (e: 'fornitore', value: Fornitore): void
}

interface Props {
  fornitore: Fornitore
  isDrawerOpen: boolean
}

const props = defineProps<Props>()
const emit = defineEmits<Emit>()
const refForm = ref<VForm>()

const save = () => {
  refForm.value?.validate().then(async ({ valid }) => {
    if (valid) {
      emit('fornitore', props.fornitore)
      emit('update:isDrawerOpen', false)
    }
  })
}

const close = () => {
  emit('fornitore', props.fornitore)
  emit('update:isDrawerOpen', false)
}
</script>

<template>
  <VDialog
    :model-value="props.isDrawerOpen"
    max-width="1400px"
    persistent=""
  >
    <AppCardActions
      :title="props.fornitore.id ? `${$t('Label.Modifica-Fornitore')} ` : `${$t('Label.Nuovo-Fornitore')}`"
      no-actions
    >
      <VCard>
        <VCardText>
          <VContainer>
            <VForm
              ref="refForm"
              @submit.prevent="save"
            >
              <VRow>
                <!-- 👉 Fornitore -->
                <VCol cols="4">
                  <AppTextField
                    v-model="props.fornitore.ragioneSociale"
                    :rules="[requiredValidator]"
                    :label="$t('Label.Fornitore')"
                    :placeholder="$t('Label.Fornitore')"
                    :readonly="!!props.fornitore.id"
                  />
                </vcol>

                <!-- 👉 Email -->
                <VCol cols="4">
                  <AppTextField
                    v-model="props.fornitore.email"
                    :rules="[requiredValidator]"
                    :label="$t('Label.Email')"
                    :placeholder="$t('Label.Email')"
                  />
                </VCol>

                <!-- 👉 Cidice - Sap -->
                <VCol cols="4">
                  <AppTextField
                    v-model="props.fornitore.codiceSap"
                    :rules="[requiredValidator]"
                    :label="$t('Label.Cidice-Sap')"
                    :placeholder="$t('Label.Cidice-Sap')"
                    :readonly="!!props.fornitore.id"
                  />
                </VCol>

                <!-- 👉 Nazione -->
                <VCol cols="2">
                  <AppTextField
                    v-model="props.fornitore.nazione"
                    :rules="[requiredValidator]"
                    :label="$t('Label.Nazione')"
                    :placeholder="$t('Label.Nazione')"
                  />
                </VCol>

                <!-- 👉 Citta -->
                <VCol cols="4">
                  <AppTextField
                    v-model="props.fornitore.citta"
                    :label="$t('Label.Citta')"
                    :placeholder="$t('Label.Citta')"
                  />
                </VCol>

                <!-- 👉 Indirizzo -->
                <VCol cols="4">
                  <AppTextField
                    v-model="props.fornitore.indirizzo"
                    :label="$t('Label.Indirizzo')"
                    :placeholder="$t('Label.Indirizzo')"
                  />
                </VCol>

                <!-- 👉 Cap -->
                <VCol cols="2">
                  <AppTextField
                    v-model="props.fornitore.cap"
                    :label="$t('Label.Cap')"
                    :placeholder="$t('Label.Cap')"
                  />
                </VCol>

                <!-- 👉 Categoria -->
                <VCol cols="4">
                  <AppSelect
                    v-model="props.fornitore.categoria"
                    :label="$t('Label.Categoria')"
                    :placeholder="$t('Label.Categoria')"
                    :items="[{ title: 'Ausiliari, connessioni', value: 'AUS' }, { value: 'BOB', title: 'Bobine legno e plastica' },
                             { value: 'CCU', title: 'Catodi di rame e vergella' }, { value: 'COL', title: 'Coloranti in granuli e polvere' }, { value: 'FIA', title: 'Filo acciaio ' },
                             { value: 'FIL', title: 'Filo di rame e multifilo rosso, stagnato, argentato' }, { value: 'FIO', title: 'Fibre ottiche monomodali e multimodali' }, { value: 'FIR', title: 'Filati di rinforzo aramidici e tessili' },
                             { value: 'JEL', title: 'Jelly e altri composti per FO' }, { value: 'LEG', title: 'Fili in lega per termocoppie e coax' }, { value: 'MEG', title: 'Mescole in gomma in strisce e granuli' },
                             { title: 'Prodotti per mescole PVC', value: 'MES' }, { title: 'Nastri e semilavorati in alluminio e leghe', value: 'NAA' }, { title: 'Nastro ferro e semilavorati in ferro e zincati', value: 'NAF' },
                             { title: 'Nastro rame', value: 'NAR' }, { title: 'Nastri in materiale sintetico e tessile', value: 'NAS' }, { title: 'Servizi', value: 'SER' },
                             { title: 'Semilavorati e varie', value: 'SLA' }, { title: 'Granuli termoplastici per isolamento e guaina', value: 'TER' }, { title: 'Tubetti in rame e altri profili', value: 'TUB' },
                             { title: 'Vernici ed inchiostri', value: 'VER' },
                    ]"
                  />
                </VCol>

                <!-- 👉 Prezzo -->
                <VCol cols="2">
                  <AppSelect
                    v-model="props.fornitore.prezzo"
                    :label="$t('Label.Prezzo')"
                    :placeholder="$t('Label.Prezzo')"
                    :items="['1', '2', '3', '4']"
                  />
                </VCol>

                <!-- 👉 Servizio -->
                <VCol cols="2">
                  <AppSelect
                    v-model="props.fornitore.servizio"
                    :label="$t('Label.Servizio')"
                    :placeholder="$t('Label.Servizio')"
                    :items="['1', '2', '3', '4']"
                  />
                </VCol>

                <!-- 👉 Critico -->
                <VCol cols="2">
                  <AppSelect
                    v-model="props.fornitore.critico"
                    :label="$t('Label.Critico')"
                    :placeholder="$t('Label.Critico')"
                    :items="[{ title: 'Si', value: '1' }, { title: 'No', value: '0' }]"
                  />
                </VCol>

                <!-- 👉 Qualificato -->
                <VCol cols="2">
                  <AppSelect
                    v-model="props.fornitore.qualificato"
                    :label="$t('Label.Qualificato')"
                    :placeholder="$t('Label.Qualificato')"
                    :items="[{ title: 'Si', value: '1' }, { title: 'No', value: '0' }]"
                  />
                </VCol>
              </VRow>
              <VRow>
                <VCardActions>
                  <VSpacer />

                  <VBtn
                    type="reset"
                    color="error"
                    variant="outlined"
                    @click="close"
                  >
                    Cancel
                  </VBtn>

                  <VBtn
                    type="submit"
                    color="success"
                    variant="elevated"
                    @click="refForm?.validate()"
                  >
                    Save
                  </VBtn>
                </VCardActions>
              </VRow>
            </VForm>
          </VContainer>
        </VCardText>
      </VCard>
    </AppCardActions>
  </VDialog>
</template>

<style scoped lang="scss">

</style>
