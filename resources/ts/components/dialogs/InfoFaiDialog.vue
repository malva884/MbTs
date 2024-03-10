<script setup lang="ts">
import type { Fai } from '@/views/quality/fai/type'

// eslint-disable-next-line @typescript-eslint/consistent-type-imports

interface Emit {
  (e: 'update:isDrawerOpen', value: boolean): void
  (e: 'faiData', value: Fai): void
}

interface Props {
  faiData?: Fai
  isDrawerOpen: boolean
}

const props = withDefaults(defineProps<Props>(), {
  faiData: () => ({
    id: 0,
    nome: '',
    cognome: '',
    role: '',
    mobile: '',
    interno: '',
    email: '',
    sesso: '',
    stato: '',
    avatar: '',
    lingua: '',
  }),
})

// eslint-disable-next-line @typescript-eslint/no-unused-vars
const emit = defineEmits<Emit>()
const userData = ref<Fai>(structuredClone(toRaw(props.faiData)))

const closeNavigationDrawer = () => {
  emit('update:isDrawerOpen', false)
}

const handleDrawerModelValueUpdate = (val: boolean) => {
  emit('update:isDrawerOpen', val)
}

const formatRisultato = (risultato: number) => {
  if (risultato == 1)
    return { color: 'success', text: 'Positivo' }
  else if (risultato == 2)
    return { color: 'error', text: 'Negativo' }
  else
    return { color: '', text: risultato }
}

</script>

<template>
  <VDialog
    :width="$vuetify.display.smAndDown ? 'auto' : 1000"
    :model-value="props.isDrawerOpen"
    @update:model-value="handleDrawerModelValueUpdate"
  >
    <!-- 👉 Dialog close btn -->
    <DialogCloseBtn @click="closeNavigationDrawer"/>

    <VCard class="pa-sm-8 pa-5">
      <!-- 👉 Title -->
      <VCardItem class="text-center">
        <VCardTitle class="text-h3 mb-3">
          {{ $t('Label.Dettaglio Fai') }}
        </VCardTitle>

      </VCardItem>

      <VCardText class="d-flex justify-space-between flex-wrap flex-column flex-sm-row print-row">
        <div class="ma-sm-4">
          <table>
            <tbody>
            <tr>
              <td class="pe-6 pb-1">
                {{ $t('Label.Numero Fai') }}:
              </td>
              <td class="pb-1">
                      <span class="font-weight-medium">
                        {{ userData.numero_fai }}
                      </span>
              </td>
            </tr>
            <tr>
              <td class="pe-6 pb-1">
                {{ $t('Label.Ordine') }}:
              </td>
              <td class="pb-1">
                {{ userData.ol }}
              </td>
            </tr>
            <tr>
              <td class="pe-6 pb-1">
                {{ $t('Label.Materiale') }}:
              </td>
              <td class="pb-1">
                {{ userData.cod_materiale }}
              </td>
            </tr>
            <tr>
              <td class="pe-6 pb-1">
                {{ $t('Label.Cavo') }}:
              </td>
              <td class="pb-1">
                {{ userData.cod_cavo }}
              </td>
            </tr>
            <tr>
              <td class="pe-6 pb-1">
                {{ $t('Label.Risultato') }}:
              </td>
              <td class="pb-1">
                <VChip
                  label
                  :color="formatRisultato(userData.risultato).color"

                >
                  {{ formatRisultato(userData.risultato).text }}
                </VChip>
              </td>
            </tr>
            </tbody>
          </table>
        </div>

        <div class="mt-4 ma-sm-4">
          <table>
            <tbody>
            <tr>
              <td class="pe-6 pb-1">
                {{ $t('Label.Utente') }}:
              </td>
              <td class="pb-1">
                {{ userData.user }}
              </td>
            </tr>
            <tr>
              <td class="pe-6 pb-1">
                {{ $t('Label.Data Apertura') }}:
              </td>
              <td class="pb-1">
                {{ userData.data_creazione }}
              </td>
            </tr>
            <tr>
              <td class="pe-6 pb-1">
                {{ $t('Label.Data Chiusura') }}:
              </td>
              <td class="pb-1">
                {{ userData.data_chiusura }}
              </td>
            </tr>
            </tbody>
          </table>
        </div>
        <div class="ma-sm-4">
          <h6 class="text-base font-weight-medium mb-6">
            {{ $t('Label.Descrizione') }}:
          </h6>
          <p class="mb-1" v-html="userData.descrizione"></p>

        </div>
      </VCardText>
    </VCard>
  </VDialog>
</template>
