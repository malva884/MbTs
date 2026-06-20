<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import TaskLeftSidebarContent from '@/views/task/TaskLeftSidebarContent.vue'
import AreaDiLavoro from '@/views/task/AreaDiLavoro.vue'
import GestioneArea from '@/views/task/GestioneArea.vue'
import HomeTask from "@/views/task/HomeTask.vue"
import MioLavoro from "@/views/task/MioLavoro.vue"

// Tipi per la gestione dei tab
type TabType = 'home' | 'area' | 'gestione' | 'lavoro'

// Composables
const { isLeftSidebarOpen } = useResponsiveLeftSidebar()
const { t } = useI18n()
const route = useRoute<'task-home' | 'task-mylist' | 'task-area' | 'task-area-gestione'>()
const userData = useCookie<any>('userData')

// Stato Tab e ID
const currentTab = ref<TabType>('home')
const areaId = ref<string | null>(null)
const gestioneId = ref<string | null>(null)

// Stato Permessi/Ruoli
const responsabile = ref(false)
const responsabileArea = ref(false)

// Compose dialog
const isComposeDialogVisible = ref(false)

// Funzione per verificare i ruoli sui task
const checkResponsabile = async () => {
  if (!areaId.value || !userData.value?.id) return

  try {
    const { data: responsabileData } = await useApi<any>(
      createUrl('/task/aree/responsabile', {
        query: {
          area_id: areaId.value,
          user_id: userData.value.id,
        },
      })
    )

    if (responsabileData.value) {
      responsabile.value = responsabileData.value.responsabile
      // CORRETTO: cambiato da responsableData a responsabileData (con la "i")
      responsabileArea.value = responsabileData.value.responsabile_area
    }
  } catch (error) {
    console.error("Errore durante il controllo del responsabile:", error)
  }
}

// Navigazione logica interna basata sulla rotta
const updateActiveTab = async () => {
  const params = route.params

  if (params.area) {
    areaId.value = params.area as string
    currentTab.value = 'area'
    await checkResponsabile()
  }
  else if (params.gestione) {
    gestioneId.value = params.gestione as string
    areaId.value = params.gestione as string // Mantenuto come logica originale
    currentTab.value = 'gestione'
    await checkResponsabile()
  }
  else if (params.mylist) {
    currentTab.value = 'lavoro'
    await checkResponsabile()
  }
  else {
    currentTab.value = 'home'
  }
}

// Esegui al caricamento del componente
onMounted(() => {
  updateActiveTab()
})

// Monitora il cambio dei parametri della rotta
watch(
  () => route.params,
  () => updateActiveTab()
)
</script>

<template>
  <VLayout
    style="min-block-size: 100%;"
    class="email-app-layout"
  >
    <VNavigationDrawer
      v-model="isLeftSidebarOpen"
      absolute
      touchless
      location="start"
      style="width: 310px !important;"
      :temporary="$vuetify.display.mdAndDown"
    >
      <TaskLeftSidebarContent @toggle-compose-dialog-visibility="isComposeDialogVisible = !isComposeDialogVisible" />
    </VNavigationDrawer>

    <VMain>
      <VCard
        flat
        class="email-content-list h-100 d-flex flex-column ml-16"
      >
        <div class="flex-grow-1 overflow-y-auto">

          <HomeTask v-if="currentTab === 'home'" />

          <MioLavoro
            v-if="currentTab === 'lavoro'"
            :responsabile="responsabile"
          />

          <GestioneArea
            v-if="currentTab === 'gestione'"
            :area-id="gestioneId"
            :responsabile="responsabile"
            :responsabile-area="responsabileArea"
          />

          <AreaDiLavoro
            v-if="currentTab === 'area'"
            :area-id="areaId"
            :responsabile="responsabileArea"
          />

        </div>

        <VDivider />
      </VCard>
    </VMain>
  </VLayout>
</template>

<style lang="scss">
@use "@styles/variables/_vuetify.scss";
@use "@core-scss/base/_mixins.scss";

.email-search {
  .v-field__outline {
    display: none;
  }
}

.email-app-layout {
  border-radius: vuetify.$card-border-radius;
  @include mixins.elevation(vuetify.$card-elevation);
  $sel-email-app-layout: &;

  @at-root {
    .skin--bordered {
      @include mixins.bordered-skin($sel-email-app-layout);
    }
  }
}

.email-content-list {
  border-end-start-radius: 0;
  border-start-start-radius: 0;
}

.email-list {
  white-space: nowrap;

  .email-item {
    block-size: 3.75rem;
    transition: all 0.2s ease-in-out;
    will-change: transform, box-shadow;

    &.email-read {
      background-color: rgba(var(--v-theme-on-surface), var(--v-hover-opacity));
    }

    & + .email-item {
      border-block-start: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    }
  }

  .email-item:hover {
    transform: translateY(-2px);
    @include mixins.elevation(3);

    .email-actions {
      display: block !important;
    }

    .email-meta {
      display: none;
    }

    + .email-item {
      border-color: transparent;
    }

    @media screen and (max-width: 600px) {
      .email-actions {
        display: none !important;
      }
    }
  }
}

.email-compose-dialog {
  position: absolute;
  inset-block-end: 0;
  inset-inline-end: 0;
  min-inline-size: 100%;

  @media only screen and (min-width: 800px) {
    min-inline-size: 712px;
  }
}
</style>
