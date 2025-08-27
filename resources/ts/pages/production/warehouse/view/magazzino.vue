<script setup lang="ts">
import {VDataTableServer} from 'vuetify/labs/VDataTable'
import {useI18n} from 'vue-i18n'
import {VForm} from 'vuetify/components/VForm'
import moment from 'moment/moment'
import {can} from '@layouts/plugins/casl'
import DefineAbilities from '@/plugins/casl/DefineAbilities'

definePage({
  meta: {
    action: 'read',
    subject: 'Produzione-Magazzino',
  },
})

const {t} = useI18n()
const isDialogLoading = ref(false)
const serverItems = ref<any>([])
const selected = ref([])
const panel = ref(0)

const loadItems = async () => {
  isDialogLoading.value = true

  const {data: resultData} = await useApi<any>(createUrl('/pr/magazzino/get_magazzono', {
    query: {
      class: [selected.value],
    },
  }))

  serverItems.value = resultData.value.objs

  panel.value = null
  isDialogLoading.value = false
}

function formatDate(date: string): string {
  return moment(String(date)).format('YYYY - MMMM')
}

let euro = new Intl.NumberFormat('it-IT', {
  style: 'currency',
  currency: 'EUR',
})
</script>

<template>
  <VRow>
    <VCol cols="12">
      <VExpansionPanels v-model="panel">
        <VExpansionPanel variant="accordion">
          <VExpansionPanelTitle>
            Filters
          </VExpansionPanelTitle>
          <VExpansionPanelText>
            <div class="me-3 d-flex gap-3"/>
            <VSpacer/>
            <VForm @submit.prevent="() => {}">
              <VRow>
                <!-- 👉 First Name -->
                <VCol cols="12">
                  <VCheckbox
                    v-model="selected"
                    label="Packaging"
                    color="success"
                    value="packaging"
                  />
                </VCol>

                <!-- 👉 Last Name -->

                <VCol cols="12">
                  <VCheckbox
                    v-model="selected"
                    label="Fiber Optics Ofc"
                    color="primary"
                    value="fiber_optics_ofc"
                  />
                </VCol>

                <!-- 👉 Email -->
                <VCol cols="12">
                  <VCheckbox
                    v-model="selected"
                    label="Finished Products Ofc"
                    color="primary"
                    value="finished_products_ofc"
                  />
                </VCol>

                <!-- 👉 City -->
                <VCol cols="12">
                  <VCheckbox
                    v-model="selected"
                    label="Finished Products Cc"
                    color="warning"
                    value="finished_products_cc"
                  />
                </VCol>

                <!-- 👉 Raw Materials Ofc -->
                <VCol cols="12">
                  <VCheckbox
                    v-model="selected"
                    label="Raw Materials Ofc"
                    color="primary"
                    value="raw_materials_ofc"
                  />
                </VCol>

                <!-- 👉 Raw Materials Cc -->
                <VCol cols="12">
                  <VCheckbox
                    v-model="selected"
                    label="Raw Materials Cc"
                    color="warning"
                    value="raw_materials_cc"
                  />
                </VCol>

                <!-- 👉 Wip Ofc -->
                <VCol cols="12">
                  <VCheckbox
                    v-model="selected"
                    label="Wip Ofc"
                    color="primary"
                    value="wip_ofc"
                  />
                </VCol>

                <!-- 👉 Wip Cc -->
                <VCol cols="12">
                  <VCheckbox
                    v-model="selected"
                    label="Wip Cc"
                    color="warning"
                    value="wip_cc"
                  />
                </VCol>

                <VCol
                  cols="12"
                  class="d-flex gap-4"
                >
                  <VBtn type="submit" @click="loadItems">
                    Submit
                  </VBtn>

                  <VBtn
                    type="reset"
                    color="secondary"
                    variant="tonal"
                  >
                    Reset
                  </VBtn>
                </VCol>
              </VRow>
            </VForm>
          </VExpansionPanelText>
        </VExpansionPanel>
      </VExpansionPanels>
    </VCol>
  </VRow>
  <VRow>
    <VCol cols="6">
      <VCard
        title="Categorie"
        class="mb-10"
      >
        <VTable
          theme=""
          class="text-no-wrap rounded-0"
        >
          <thead>
          <tr style="background-color: #0f7609">
            <th>
              <span class="text-white">Categorie</span>
            </th>
            <th>
              <span class="text-white">Quantità</span>
            </th>
            <th>
              <span class="text-white">Fkm</span>
            </th>
            <th>
              <span class="text-white">Totale</span>
            </th>
          </tr>
          </thead>

          <tbody>
          <tr
            v-for="(item, index) in serverItems"
            :key="index"
          >
            <td>
              {{ index }}
            </td>
            <td>
              {{ item.ckm }}
            </td>
            <td>
              {{ item.fkm }}
            </td>
            <td>
              {{ euro.format(item.valore) }}
            </td>
          </tr>
          </tbody>
        </VTable>
      </VCard>
    </VCol>
    <VCol cols="6">
      <VCard
        title="RAW MATERIALS"
        class="mb-10"
      >
        <VTable
          theme=""
          class="text-no-wrap rounded-0"
        >
          <thead>
          <tr style="background-color: #0f7609">
            <th>
              <span class="text-white">Categorie</span>
            </th>
            <th>
              <span class="text-white">Totale</span>
            </th>
          </tr>
          </thead>

          <tbody>
          <tr
            v-for="(item, index) in serverItems"
            :key="index"
          >
            <td v-if="index === 'Raw Materials OFC' || index === 'Fiber Optics OFC' || index === 'Raw Materials CC' || index === 'Packaging'">
              {{ index }}
            </td>
            <td v-if="index === 'Raw Materials OFC' || index === 'Fiber Optics OFC' || index === 'Raw Materials CC' || index === 'Packaging'">
              {{ euro.format(item.valore) }}
            </td>
          </tr>
          </tbody>
        </VTable>
      </VCard>

      <VCard
        title="WORK IN PROGRESS"
        class="mb-10"
      >
        <VTable
          theme=""
          class="text-no-wrap rounded-0"
        >
          <thead>
          <tr style="background-color: #0f7609">
            <th>
              <span class="text-white">Categorie</span>
            </th>
            <th>
              <span class="text-white">Totale</span>
            </th>
          </tr>
          </thead>

          <tbody>
          <tr
            v-for="(item, index) in serverItems"
            :key="index"
          >
            <td v-if="index === 'WIP OFC' || index === 'WIP CC' || index === 'Corso Lavori'">
              {{ index }}
            </td>
            <td v-if="index === 'WIP OFC' || index === 'WIP CC' || index === 'Corso Lavori'">
              {{ euro.format(item.valore) }}
            </td>
          </tr>
          </tbody>
        </VTable>
      </VCard>

      <VCard
        title="FINISHED PRODUCTS"
        class="mb-10"
      >
        <VTable
          theme=""
          class="text-no-wrap rounded-0"
        >
          <thead>
          <tr style="background-color: #0f7609">
            <th>
              <span class="text-white">Categorie</span>
            </th>
            <th>
              <span class="text-white">Totale</span>
            </th>
          </tr>
          </thead>

          <tbody>
          <tr
            v-for="(item, index) in serverItems"
            :key="index"
          >
            <td v-if="index === 'Finished Products CC' || index === 'Finished Products OFC'">
              {{ index }}
            </td>
            <td v-if="index === 'Finished Products CC' || index === 'Finished Products OFC'">
              {{ euro.format(item.valore) }}
            </td>
          </tr>
          </tbody>
        </VTable>
      </VCard>
    </VCol>
  </VRow>

  <!-- Dialog Loading -->
  <VDialog
    v-model="isDialogLoading"
    width="300"
  >
    <VCard
      color="primary"
      width="300"
    >
      <VCardText class="pt-3">
        <span class="ml-4 mb-3">Please stand by</span>
        <VProgressLinear
          :size="40"
          color="warning"
          class="mt-3"
          indeterminate
        />
      </VCardText>
    </VCard>
  </VDialog>
</template>
