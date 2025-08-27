<script setup lang="ts">
import welcome from '@images/custom/welcome.png'
import entrata from '@images/suoni/entrata.mp3'
import uscita from '@images/suoni/uscita.mp3'
import exit from '@images/custom/exit.png'
import { themeConfig } from '@themeConfig'
import { VNodeRenderer } from '@layouts/components/VNodeRenderer'
import type { RpRegisterLog } from '@/views/reception/type'
import { useI18n } from 'vue-i18n'

interface Emit {
  (e: 'update:isDrawerOpen', value: boolean): void
  (e: 'visitatoreData', value: RpRegisterLog): void
}

interface Props {
  visitatoreData: RpRegisterLog
}

const props = defineProps<Props>()
const emit = defineEmits<Emit>()
const { t } = useI18n()

const pricingPlans = [

  {
    name: t('Label.Entrata'),
    logo: welcome,
    entrata: true,
    stampaTessera: true,
  },
  {
    name: t('Label.Uscita'),
    logo: exit,
    entrata: false,
    stampaTessera: false,
  },
]

const submit = async (azione: boolean) => {
  props.visitatoreData.entrata = azione

  const retuenData = await $api('/reception/storeRegister/' + props.visitatoreData.id, {
    method: 'POST',
    body: props.visitatoreData,
  })

  let audio = new Audio(entrata)
  if (!azione)
    audio = new Audio(uscita)
  audio.play()
  emit('visitatoreData', props.visitatoreData)
  emit('update:isDrawerOpen', false)
}
</script>

<template>
  <!-- 👉 Title and subtitle -->
  <VCardItem class="justify-center ">
    <template #prepend>
      <div class="d-flex">
        <VNodeRenderer :nodes="themeConfig.app.logo" />
      </div>
    </template>

    <VCardTitle class="font-weight-bold text-capitalize text-h3 py-1">
      {{ themeConfig.app.title }}
    </VCardTitle>
  </VCardItem>
  <div class="text-center mb-4">
    <h4> {{ $t('Label.Benvenuto') }}</h4><h3>{{props.visitatoreData.nome}}</h3>

  </div>

  <!-- SECTION pricing plans -->
  <VRow>
    <VCol
      v-for="plan in pricingPlans"
      :key="plan.logo"
      v-bind="{md: 12}"
      cols="12"
    >
      <!-- 👉  Card -->
      <VCard
        flat
        border
      >

        <!-- 👉 Plan logo -->
        <VCardText>
          <VImg
            :height="100"
            :src="plan.logo"
            class="mx-auto mb-1"
          />

          <!-- 👉 Plan name -->
          <h3 class="text-h3 mb-1 text-center">
            {{ plan.name }}
          </h3>

          <!-- 👉 Plan actions -->
          <VBtn
            block
            :color="plan.entrata ? 'success' : 'error'"
            variant="tonal"
            @click="submit(plan.entrata)"
          >
            {{ $t('Label.Clicca-Qui') }}
          </VBtn>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
  <!-- !SECTION  -->
</template>

<style lang="scss" scoped>

.card-list {
  --v-card-list-gap: 0.75rem;
}

.save-upto-chip {
  inset-block-start: -2rem;
  inset-inline-end: -7rem;
}

.annual-price-text{
  inset-block-end: 10%;
  inset-inline-start: 50%;
  transform: translateX(-50%);
}
</style>
