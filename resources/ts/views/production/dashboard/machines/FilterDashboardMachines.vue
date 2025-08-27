<script setup lang="tsx">
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { useI18n } from 'vue-i18n'

interface Emit {
  (e: 'update:filter', value: object): void
}

interface Props {
  filter: object
}

const props = defineProps<Props>()
const emit = defineEmits<Emit>()
const { t } = useI18n()
const macchinaFilter = ref()
const categoriaFilter = ref()
const tipologiaFilter = ref()
const statoFilter = ref()
const isNavDrawerOpen = ref(false)
const view = ref(false)
const macchineOptions = []
const categorieOptions = []

const resolveCategorie = (cat: string) => {
  console.log(cat)
  if (cat === 'buffering')
    return 'Buffering'
  else if (cat === 'stranding')
    return 'Stranding'
  else if (cat === 'jacketing')
    return 'Jacketing'
  else if (cat === 'marck')
    return 'Marck'
}

const loadMacchine = async () => {
  const resultData = await useApi<any>(createUrl('/macchine/get_list', {
    query: {
      attivo: true,
    },
  }))

  macchineOptions.push({ id: null, titolo: 'Tutte' })
  resultData.data.value.forEach((value: any) => {
    macchineOptions.push({ id: value.name_gp, titolo: value.nome })
    categorieOptions.push({ id: value.categoria, titolo: resolveCategorie(value.categoria) })
  })

  view.value = true
}

loadMacchine()

const setFilter = () => {

  emit('update:filter', { macchina: macchinaFilter, categora: categoriaFilter, tipologia: tipologiaFilter, stato: statoFilter })
}
</script>

<template>
  <div class="d-lg-block d-none">
    <VBtn
      icon
      size="small"
      class="app-customizer-toggler rounded-s-lg rounded-0"
      style="z-index: 1001;"
      @click="isNavDrawerOpen = true"
    >
      <VIcon
        size="22"
        icon="tabler-adjustments-alt"
      />
    </VBtn>

    <VNavigationDrawer
      v-model="isNavDrawerOpen"
      temporary
      touchless
      border="0"
      location="end"
      width="400"
      :scrim="false"
      class="app-customizer"
    >
      <!-- 👉 Header -->
      <div class="customizer-heading d-flex align-center justify-space-between">
        <div>
          <h6 class="text-h6">
            {{ $t('Label.Filtri-Dashboard') }}
          </h6>
        </div>

        <div class="d-flex align-center gap-1">
          <VBtn
            icon
            variant="text"
            size="small"
            color="medium-emphasis"
            @click="resetCustomizer"
          >
            <VBadge
              v-show="isCookieHasAnyValue"
              dot
              color="error"
              offset-x="-30"
              offset-y="-15"
            />

            <VIcon
              size="22"
              icon="tabler-refresh"
            />
          </VBtn>

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
        <!-- SECTION Theming -->
        <CustomizerSection
          title=""
          :divider="false"
        >
          <!-- 👉 Primary Color -->
          <div class="d-flex flex-column gap-3">
            <div
              class="d-flex align-center gap-x-3"
              style="column-gap: 0.7rem;"
              :key="view"
            >
              <AppSelect
                v-model="macchinaFilter"
                :items="macchineOptions"
                :label="t('Label.Macchine')"
                :placeholder="t('Label.Macchine')"
                :item-title="item => item.titolo"
                :item-value="item => item.id"
                clearable
                clear-icon="tabler-x"
                persistent-hint
                @focusout="setFilter"
              />
            </div>
          </div>
          <!-- 👉 Semi Dark -->
          <div class="d-flex flex-column gap-3">
            <div
              :key="view"
              class="d-flex align-center gap-x-3"
              style="column-gap: 0.7rem;"
            >
              <AppSelect
                v-model="categoriaFilter"
                :items="categorieOptions"
                :label="t('Label.Categoria')"
                :placeholder="t('Label.Categoria')"
                :item-title="item => item.titolo"
                :item-value="item => item.id"
                clearable
                clear-icon="tabler-x"
                persistent-hint
                @focusout="setFilter"
              />
            </div>
          </div>
          <!-- 👉 Tipologia -->
          <div class="d-flex flex-column gap-3">
            <div
              class="d-flex align-center gap-x-3"
              style="column-gap: 0.7rem;"
            >
              <AppSelect
                v-model="tipologiaFilter"
                :items="[{ value: null, title: 'Tutti' }, { value: 1, title: 'Ottico' }, { value: 2, title: 'Rame' }]"

                :label="t('Label.Tipologia')"
                :placeholder="t('Label.Tipologia')"
                clearable
                clear-icon="tabler-x"
                persistent-hint
                @focusout="setFilter"
              />
            </div>
          </div>
          <!-- 👉 Stato -->
          <div class="d-flex flex-column gap-3">
            <div
              class="d-flex align-center gap-x-3"
              style="column-gap: 0.7rem;"
            >
              <AppSelect
                v-model="statoFilter"
                :items="[{ value: null, title: 'Tutte' }, { value: 'Run', title: 'Run' }, { value: 'Stop', title: 'Stop' }, { value: 'Fermo', title: 'Fermo Attivo' }]"

                :label="t('Label.Stato')"
                :placeholder="t('Label.Stato')"
                clearable
                clear-icon="tabler-x"
                persistent-hint
                @focusout="setFilter"
              />
            </div>
          </div>
        </CustomizerSection>
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
