<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import TaskLeftSidebarContent from '@/views/task/TaskLeftSidebarContent.vue'
import AreaDiLavoro from '@/views/task/AreaDiLavoro.vue'
import GestioneArea from '@/views/task/GestioneArea.vue'
import HomeTask from '@/views/task/HomeTask.vue'
import MioLavoro from '@/views/task/MioLavoro.vue'

const userData = useCookie<any>('userData')
const { isLeftSidebarOpen } = useResponsiveLeftSidebar()
const { t } = useI18n()
const gestioneTab = ref(false)
const areaTab = ref(false)
const homeTab = ref(false)
const lavoroTab = ref(false)
const areaId = ref()
const gestioneId = ref()
const responsabile = ref(false)
const responsabileArea = ref(false)


// Composables
const route = useRoute< 'task-home' | 'task-mylist' | 'task-area' | 'task-area-gestione' >()

const checkResponsabile = async () => {
  const { data: responsabileData } = await useApi<any>(createUrl('/task/aree/responsabile', {
    query: {
      area_id: areaId.value,
      user_id: userData.value.id,
    },
  }))

  responsabile.value = responsabileData.value.responsabile
  responsabileArea.value = responsabileData.value.responsabile_area
}

const openTap = async () => {
  if (route.params.area) {
    areaId.value = route.params.area
    checkResponsabile()
    gestioneTab.value = false
    homeTab.value = false
    lavoroTab.value = false
    areaTab.value = true
  }
  else if (route.params.gestione) {
    gestioneId.value = route.params.gestione
    areaId.value = route.params.gestione
    checkResponsabile()
    areaTab.value = false
    homeTab.value = false
    lavoroTab.value = false
    gestioneTab.value = true
  }
  else if (route.params.mylist) {
    checkResponsabile()
    areaTab.value = false
    homeTab.value = false
    gestioneTab.value = false
    lavoroTab.value = true
  }
  else {
    gestioneTab.value = false
    areaTab.value = false
    lavoroTab.value = false
    homeTab.value = true
  }
}

openTap()

// Compose dialog
const isComposeDialogVisible = ref(false)

// Reset selected emails when filter or label is updated
watch(
  () => route.params,
  () => openTap(),
//  { deep: true },
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
      style="width: 310px!important;"
      :temporary="$vuetify.display.mdAndDown"
    >
      <TaskLeftSidebarContent @toggle-compose-dialog-visibility="isComposeDialogVisible = !isComposeDialogVisible" />
    </VNavigationDrawer>
    <VMain>
      <VCard
        flat
        class="email-content-list h-100 d-flex flex-column ml-16"
      >
        <HomeTask v-if="homeTab" />

        <MioLavoro v-if="lavoroTab" :responsabile="responsabile" />

        <GestioneArea
          v-if="gestioneTab"
          :area-id="gestioneId"
          :responsabile="responsabile"
          :responsabile-area="responsabileArea"
        />

        <AreaDiLavoro
          v-if="areaTab"
          :area-id="areaId"
          :responsabile="responsabileArea"
        />
        <VDivider />
      </VCard>
    </VMain>
  </VLayout>
</template>

<style lang="scss">
@use "@styles/variables/_vuetify.scss";
@use "@core-scss/base/_mixins.scss";

// ℹ️ Remove border. Using variant plain cause UI issue, caret isn't align in center
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
