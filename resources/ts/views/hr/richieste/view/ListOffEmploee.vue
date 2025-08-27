<script setup lang="tsx">
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { useI18n } from 'vue-i18n'

interface Emit {
  (e: 'update:richiesta', value: string): void
  (e: 'update:filter', value: object): void
}

interface Props {
  richiesta: object
}

const props = defineProps<Props>()
const emit = defineEmits<Emit>()
const { t } = useI18n()
const isNavDrawerOpen = ref(false)
const view = ref(false)
const items = ref({})

const loadItems = async () => {
  const resultData = await useApi<any>(createUrl(`/hr/requests/list_off/${props.richiesta}`, {
    query: {

    },
  }))

  items.value = resultData.data.value
  view.value = true
}

loadItems()

const resolveTipologia = (tipologia: string) => {
  if (tipologia === '1')
    return { color: 'text-button  mb-1 text-success', text: 'Ferie' }
  else if (tipologia === '2')
    return { color: 'text-button  mb-1 text-cord', text: '104' }
  else if (tipologia === '101')
    return { color: 'warning', text: 'Annullamento Richiesta' }
  else if (tipologia === '102')
    return { color: 'warning', text: 'Annullamento 104' }
  else if (tipologia === '5')
    return { color: 'white', text: 'Permesso' }
  else
    return { color: 'warning', text: '---' }
}
</script>

<template>
  <div class="d-xl-block d-none">
    <VBtn
      icon
      size="small"
      class="app-customizer-toggler rounded-s-xl rounded-0"
      style="z-index: 1001;"
      @click="isNavDrawerOpen = true"
    >
      <VIcon
        size="22"
        icon="tabler-users-group"
      />
    </VBtn>

    <VNavigationDrawer
      v-model="isNavDrawerOpen"
      temporary
      touchless
      border="0"
      location="end"
      width="600"
      :scrim="false"
      class="app-customizer"
    >
      <!-- 👉 Header -->
      <div class="customizer-heading d-flex align-center justify-space-between">
        <div>
          <h6 class="text-h6">
            {{ $t('Label.Dipendeni-Off') }}
          </h6>
        </div>

        <div class="d-flex align-center gap-1">

          <VBtn
            icon
            variant="text"
            color="medium-emphasis"
            size="small"
            @click="isNavDrawerOpen = false"
          >
            <VIcon
              icon="tabler-x"
              size="22"
            />
          </VBtn>
        </div>
      </div>

      <VDivider />

      <PerfectScrollbar
        tag="ul"
        :options="{ wheelPropagation: false }"
      >
        <VTable
          height="1000"
          fixed-header
          class="text-no-wrap"
        >
          <thead>
          <tr>
            <th>
              {{ $t('Label.Dipendente') }}
            </th>
            <th>
              {{ $t('Label.Data') }}
            </th>
            <th>
              {{ $t('Label.Tipologia') }}
            </th>
            <th>
              {{ $t('Label.Ore') }}
            </th>
          </tr>
          </thead>

          <tbody>
          <tr
            v-for="item in items"
            :key="item.id"
          >
            <td>
              {{ item.dipendente_nome + ' ' + item.dipendente_cognome }}
            </td>
            <td>
              {{ item.data }}
            </td>
            <td>
              <p :class="resolveTipologia(item.tipologia).color">
                {{ resolveTipologia(item.tipologia).text }}
              </p>
            </td>
            <td>
              {{ item.ore_richieste }}
            </td>

          </tr>
          </tbody>
        </VTable>
        <!-- !SECTION -->
      </PerfectScrollbar>
    </VNavigationDrawer>
  </div>
</template>

<style lang="scss">
.app-customizer {
  .customizer-section {
    display: flex;
    flex-direction: column;
    padding: 1.25rem;
    gap: 1.5rem;
  }

  .customizer-heading {
    padding-block: 1.125rem;
    padding-inline: 1.25rem;
  }

  .v-navigation-drawer__content {
    display: flex;
    flex-direction: column;
  }
}

.app-customizer-toggler {
  position: fixed !important;
  inset-block-start: 50%;
  inset-inline-end: 0;
}
</style>
