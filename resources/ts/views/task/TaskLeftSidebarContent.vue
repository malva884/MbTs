<script setup lang="ts">
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { useI18n } from 'vue-i18n'
import { VForm } from 'vuetify/components/VForm'
import type { Area } from '@/views/task/aree/type'

defineOptions({
  inheritAttrs: false,
})

defineEmits<{
  (e: 'toggleComposeDialogVisibility'): void
}>()

interface Folder {
  title: string
  prependIcon: string
  to: any
  badge?: {
    content: string
    color: string
  }
}

const userData = useCookie<any>('userData')
const editDialog = ref(false)

const { t } = useI18n()
const refForm = ref<VForm>()
const editedItem = ref<Partial<Area>>({})
const editedIndex = ref()
const isLoading = ref(false)
const message = ref('')
const color = ref('')
const usersOptions = ref([])

const defaultItem = ref<Area>({
  id: '',
  area: '',
  colore: null,
  responsabile_id: '',
  topologia: '',
  sigla: '',
  nascosta: false,
})

function new_defaultItem() {
  defaultItem.value = {
    id: '',
    area: '',
    colore: null,
    responsabile_id: null,
    topologia: '',
    sigla: '',
    nascosta: false,
  }
}

const colors = [
  { value: 'success', title: 'Verde' },
  { value: 'primary', title: 'Viola' },
  { value: 'arancione', title: 'Arancione' },
  { value: 'rosa', title: 'Rosa' },
  { value: 'giallo', title: 'Giallo' },
  { value: 'bianco', title: 'Bianco' },
  { value: 'nero', title: 'nero' },
  { value: 'verdino', title: 'Verde Chiaro' },
  { value: 'blu', title: 'Blu' },
  { value: 'marrone', title: 'Marrone' },
  { value: 'celeste', title: 'Celeste' },
]

const nuovaArea = () => {
  new_defaultItem()
  editedIndex.value = -1
  editedItem.value = { ...defaultItem.value }
  editDialog.value = true
}

const close = () => {
  editDialog.value = false
  editedIndex.value = -1
  refForm.value?.reset()
}

const save = async () => {
  if (refForm.value) {
    const { valid } = await refForm.value.validate()
    if (!valid) return
  }

  let path = '/task/aree/store'
  if (editedItem.value.id)
    path = `/task/aree/update/${editedItem.value.id}`

  isLoading.value = true

  const retuenData = await $api(path, {
    method: 'POST',
    body: editedItem.value,
  })

  nextTick(() => {
    refForm.value?.reset()
    refForm.value?.resetValidation()
  })
  message.value = retuenData.message
  color.value = retuenData.color

  isLoading.value = false
  editDialog.value = false
  await loadItems()
}

const folders: Folder[] = [
  {
    title: t('Label.Home'),
    prependIcon: 'tabler-home',
    to: { name: 'task-home' },
  },
  {
    title: t('Label.Il-Mio-Lavoro'),
    prependIcon: 'tabler-calendar-check',
    to: { name: 'task-mylist', params: { mylist: 'mylist' } },
  },
]

const aree = ref<Area[]>([])

const loadItems = async () => {
  const { data: areeData } = await useApi<any>(createUrl('/task/aree/list', {
    query: {
      nascosto: 0,
      user_id: userData.value.id,
    },
  }))

  aree.value = areeData.value
}

loadItems()

const userOptions = async () => {
  try {
    const resultData = await useApi<any>(createUrl('/users/getUsers'))
    const arr = []

    if (resultData?.data?.value?.data) {
      resultData.data.value.data.forEach(value => {
        arr.push({ full_name: value.full_name, id: value.id })
      })
    }
    usersOptions.value = arr
  } catch (error) {
    console.error('Error loading users:', error)
    usersOptions.value = []
  }
}

userOptions()
</script>

<template>
  <div class="d-flex flex-column h-100">
    <div class="px-6 pb-1 pt-2"></div>

    <PerfectScrollbar :options="{ wheelPropagation: false }" class="h-100">
      <ul class="email-filters">
        <RouterLink
          v-for="folder in folders"
          :key="folder.title"
          v-slot="{ isActive, href, navigate }"
          class="d-flex align-center cursor-pointer"
          :to="folder.to"
          custom
        >
          <li
            v-bind="$attrs"
            :href="href"
            :class="isActive && 'email-filter-active text-primary'"
            class="d-flex align-center cursor-pointer w-100"
            @click="navigate"
          >
            <VIcon :icon="folder.prependIcon" class="me-2" size="20" />
            <span class="font-weight-medium">{{ folder.title }}</span>
            <VSpacer />
            <VChip v-if="folder.badge?.content" :color="folder.badge.color" pill size="small">
              {{ folder.badge.content }}
            </VChip>
          </li>
        </RouterLink>
      </ul>

      <ul class="email-labels">
        <li class="text-xs d-block text-uppercase text-disabled mt-5 mb-3 px-6 font-weight-bold">
          {{ $t('Label.Aree') }}
        </li>

        <div class="d-flex align-center gap-2 px-4 mb-3">
          <AppTextField
            density="compact"
            :placeholder="$t('Label.Cerca')"
            prepend-inner-icon="tabler-search"
            hide-details
            class="flex-grow-1"
          />
          <VBtn
            icon="tabler-plus"
            color="primary"
            variant="tonal"
            size="32"
            class="rounded"
            @click="nuovaArea"
          />
        </div>

        <RouterLink
          v-for="area in aree"
          :key="area.area"
          v-slot="{ isActive, href, navigate }"
          class="d-flex align-center"
          :to="{ name: 'task-area', params: { area: area.id } }"
          custom
        >
          <li
            v-bind="$attrs"
            :href="href"
            :class="isActive && 'email-label-active bg-action-selected text-primary'"
            class="cursor-pointer px-4 py-2 d-flex align-center justify-space-between w-100 layout-item-area"
            @click="navigate"
          >
            <div class="d-flex align-center overflow-hidden">
              <VBadge
                v-if="area.tipologia === '2'"
                dot
                location="bottom right"
                offset-x="3"
                offset-y="3"
                :color="area.colore || 'primary'"
                class="me-3"
              >
                <VIcon size="18" icon="tabler-lock" />
              </VBadge>
              <VBadge
                v-else
                inline
                dot
                :color="area.colore || 'success'"
                class="me-3"
              />
              <span class="font-weight-medium text-truncate text-body-2">{{ area.area }}</span>
            </div>

            <VBtn
              variant="text"
              icon="tabler-dots-vertical"
              size="28"
              density="comfortable"
              class="text-disabled action-menu-trigger"
              @click.stop
            >
              <VIcon size="18" icon="tabler-dots-vertical" />
              <VMenu activator="parent" transition="slide-y-transition">
                <VList density="compact">
                  <VListItem value="open">
                    <template #prepend>
                      <VIcon size="18" icon="tabler-window-maximize" />
                    </template>
                    <VListItemTitle class="text-body-2">{{ $t('Label.Apri-Scheda-In-Una-Nuova-Finestra') }}</VListItemTitle>
                  </VListItem>

                  <RouterLink
                    v-if="area.responsabile === '1'"
                    :to="{ name: 'task-area-gestione', params: { gestione: area.id } }"
                    class="text-decoration-none text-high-emphasis"
                  >
                    <VListItem value="manage">
                      <template #prepend>
                        <VIcon size="18" icon="tabler-settings" />
                      </template>
                      <VListItemTitle class="text-body-2">{{ $t('Label.Gestione-Area-di-Lavoro') }}</VListItemTitle>
                    </VListItem>
                  </RouterLink>

                  <VListItem v-if="area.responsabile === '1'" value="edit" @click="nuovaArea">
                    <template #prepend>
                      <VIcon size="18" icon="tabler-pencil" />
                    </template>
                    <VListItemTitle class="text-body-2">{{ $t('Label.Modifica-Area-di-Lavoro') }}</VListItemTitle>
                  </VListItem>
                </VList>
              </VMenu>
            </VBtn>
          </li>
        </RouterLink>
      </ul>
    </PerfectScrollbar>
  </div>

  <VDialog v-model="editDialog" persistent max-width="700" class="v-dialog-xl">
    <VCard class="bg-background rounded-sm overflow-hidden" :loading="isLoading">

      <v-toolbar color="surface" elevation="1" class="flex-shrink-0">
        <div class="d-flex align-center justify-space-between w-100 px-4">
          <div class="d-flex align-center gap-2">
            <VIcon :icon="editedItem.id ? 'tabler-layout-grid-edit' : 'tabler-layout-grid-add'" color="primary" />
            <span class="text-h6 font-weight-bold">
              {{ editedItem.id ? `${$t('Label.Modifica')} Area` : `${$t('Label.Nuova')} Area` }}
            </span>
          </div>
          <DialogCloseBtn @click="close" class="position-static ma-0" />
        </div>
      </v-toolbar>

      <VForm ref="refForm" @submit.prevent="save" class="d-flex flex-column">

        <VCardText class="pa-5 bg-background">
          <VRow>
            <VCol cols="12">
              <AppTextField
                v-model="editedItem.area"
                :rules="[requiredValidator]"
                :label="$t('Label.Area')"
                placeholder="Es. Sviluppo Software"
              />
            </VCol>

            <VCol cols="12" sm="4">
              <AppTextField
                v-model="editedItem.sigla"
                :rules="[requiredValidator]"
                :label="$t('Label.Sigla')"
                placeholder="Es. DEV"
              />
            </VCol>

            <VCol cols="12" sm="8">
              <AppSelect
                v-model="editedItem.responsabile_id"
                :label="$t('Label.Responsabile')"
                placeholder="Seleziona Responsabile"
                :items="usersOptions"
                item-title="full_name"
                item-value="id"
                :rules="[requiredValidator]"
              />
            </VCol>

            <VCol cols="12" sm="4">
              <AppSelect
                v-model="editedItem.tipologia"
                :label="$t('Label.Tipologia')"
                :items="[{ title: 'Pubblico', value: 1 }, { title: 'Privato', value: 2 }]"
                :rules="[requiredValidator]"
              />
            </VCol>

            <VCol cols="12" sm="8">
              <AppTextField
                v-model="editedItem.cartella_drive"
                :rules="[requiredValidator]"
                :label="$t('Label.Cartella-Drive')"
                placeholder="ID o link cartella condivisa"
              />
            </VCol>

            <VCol cols="12" sm="6">
              <AppSelect
                v-model="editedItem.colore"
                :label="$t('Label.Colore')"
                placeholder="Scegli un colore per l'area"
                :items="colors"
                :rules="[requiredValidator]"
              />
            </VCol>

            <VCol cols="12" class="pt-2">
              <VSwitch
                v-model="editedItem.nascosta"
                :label="$t('Label.Nascosto')"
                color="primary"
                inset
                hide-details
              />
            </VCol>
          </VRow>
        </VCardText>

        <VCardText class="d-flex justify-end flex-wrap gap-3 border-t bg-surface py-3 px-4">
          <VBtn type="button" variant="tonal" color="secondary" @click="close">
            Annulla
          </VBtn>
          <VBtn type="submit" color="primary">
            {{ $t('Label.Salva') }}
          </VBtn>
        </VCardText>
      </VForm>

    </VCard>
  </VDialog>
</template>

<style lang="scss">
.email-filters,
.email-labels {
  > li {
    position: relative;
    margin-block-end: 4px;
    padding-block: 8px;
    padding-inline: 24px;
  }

  .email-filter-active,
  .email-label-active {
    &::after {
      position: absolute;
      background: currentcolor;
      block-size: 100%;
      content: "";
      inline-size: 3px;
      inset-block-start: 0;
      inset-inline-start: 0;
    }
  }
}

.layout-item-area {
  transition: background-color 0.2s ease;
  &:hover {
    background-color: rgba(var(--v-theme-on-surface), 0.04);

    .action-menu-trigger {
      opacity: 1 !important;
    }
  }
}
</style>
