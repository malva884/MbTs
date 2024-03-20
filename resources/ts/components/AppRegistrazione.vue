<script setup lang="ts">
import welcome from '@images/custom/welcome.png'
import exit from '@images/custom/exit.png'
import {themeConfig} from "@themeConfig";
import {VNodeRenderer} from "@layouts/components/VNodeRenderer";


interface Props {
  visitatoreData: {
    id: number
    nome: string
    email: string
    cod_evento: string
  },
}

const props = defineProps<Props>()


const pricingPlans = [

  {
    name: 'Entrata',
    logo: welcome,
    entrata: true,
    stampaTessera: true,
  },
  {
    name: 'Uscita',
    logo: exit,
    entrata: false,
    stampaTessera: false,
  },
]
</script>

<template>
  <!-- 👉 Title and subtitle -->
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
  <div class="text-center mb-4">
    <h4>Benvenuto</h4><h3>{{props.visitatoreData.nome}}</h3>

  </div>

  <!-- SECTION pricing plans -->
  <VRow>
    <VCol
      v-for="plan in pricingPlans"
      :key="plan.logo"
      v-bind="{md: 6}"
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
            :height="140"
            :src="plan.logo"
            class="mx-auto mb-5"
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
          >
            Clicca Qui
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
