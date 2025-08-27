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
const editedItem = ref({})
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
  if (editedItem.value.tipologia) {
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

const aree = ref<Area>({})

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
  const resultData = await useApi<any>(createUrl('/users/getUsers'))
  const arr = []

  resultData.data.value.data.forEach(value => {
    arr.push({ full_name: value.full_name, id: value.id })
  })
  usersOptions.value = arr
}

userOptions()
</script>

<template>
  <div class="d-flex flex-column h-100">
    <!-- 👉 Compose -->
    <div class="px-6 pb-5 pt-6">

    </div>

    <!-- 👉 Folders -->
    <PerfectScrollbar
      :options="{ wheelPropagation: false }"
      class="h-100"
    >
      <!-- Filters -->
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
            class="d-flex align-center cursor-pointer"
            @click="navigate"
          >
            <VIcon
              :icon="folder.prependIcon"
              class="me-2"
              size="20"
            />
            <span class="font-weight-medium">{{ folder.title }}</span>

            <VSpacer />

            <VChip
              v-if="folder.badge?.content"
              :color="folder.badge.color"
              pill
            >
              {{ folder.badge.content }}
            </VChip>
          </li>
        </RouterLink>
      </ul>

      <ul class="email-labels">
        <!-- 👉 Labels -->
        <li class="text-xs d-block text-uppercase text-disabled mt-6 mb-2">
          {{ $t('Label.Aree') }}
        </li>
        <VRow class="mb-1">
          <VCol cols="9">
            <AppTextField
              density="compact"
              :placeholder="$t('Label.Cerca')"
              class="ml-1"
            />
          </VCol>
          <VCol cols="3">
            <VBtn
              icon="tabler-plus"
              size="small"
              rounded
              @click="nuovaArea"
            />
          </VCol>
        </VRow>

        <RouterLink
          v-for="area in aree"
          :key="area.area"
          v-slot="{ isActive, href, navigate }"
          class="d-flex align-center"
          :to="{
            name: 'task-area',
            params: { area: area.id },
          }"
          custom
        >
          <li
            v-bind="$attrs"
            :href="href"
            :class="isActive && 'email-label-active text-primary'"
            class="cursor-pointer"
            @click="navigate"
          >
            <VRow class="mt--10 mb--10">
              <VCol cols="9">
                <VBadge
                  v-if="area.tipologia === '2'"
                  dot
                  :color="area.colore"
                  class="me-2 "
                >
                  <VIcon
                    size="19"
                    icon="tabler-lock"
                  />
                </VBadge>
                <VBadge
                  v-else
                  inline
                  dot
                  :color="area.colore"
                  class="me-2 "
                />
                <span class="font-weight-medium ">{{ area.area }}</span>
              </VCol>
              <VCol cols="3">
                <div>
                  <VBtn
                    variant="text"
                    size="small"
                    class=""
                  >
                    <VIcon icon="tabler-dots-vertical" />
                    <VMenu activator="parent">
                      <VList>
                        <VListItem>
                          <template #prepend>
                            <VIcon icon="tabler-window-maximize" />
                          </template>

                          <VListItemTitle>{{ $t('Label.Apri-Scheda-In-Una-Nuova-Finestra') }}</VListItemTitle>
                        </VListItem>
                        <RouterLink
                          v-if="area.responsabile === '1'"
                          class="d-flex align-center text-secondary"
                          :to="{
                              name: 'task-area-gestione',
                              params: { gestione: area.id },
                            }"
                        >
                          <VListItem>
                            <template #prepend>
                              <VIcon icon="tabler-settings" />
                            </template>

                            <VListItemTitle>{{ $t('Label.Gestione-Area-di-Lavoro') }}</VListItemTitle>
                          </VListItem>

                        </RouterLink>
                        <VListItem v-if="area.responsabile === '1'">
                          <template #prepend>
                            <VIcon icon="tabler-pencil" />
                          </template>
                          <VListItemTitle>{{ $t('Label.Modifica-Area-di-Lavoro') }}</VListItemTitle>
                        </VListItem>
                      </VList>
                    </VMenu>
                  </VBtn>
                </div>
              </VCol>
            </VRow>
          </li>
        </RouterLink>
      </ul>
    </PerfectScrollbar>
  </div>

  <!-- 👉New - Edit Dialog  -->
  <VDialog
    v-model="editDialog"
    width="800"
  >
    <AppCardActions
      v-model:loading="isLoading"
      :title="editedItem.id ? `${$t('Label.Modifica')} Area` : `${$t('Label.Nuova')} Area`"
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
                <!-- 👉 Area -->
                <VCol cols="12">
                  <AppTextField
                    v-model="editedItem.area"
                    :rules="[requiredValidator]"
                    :label="$t('Label.Area')"
                    :placeholder="$t('Label.Area')"
                  />
                </VCol>
                <!-- 👉 Sigla -->
                <VCol cols="5">
                  <AppTextField
                    v-model="editedItem.sigla"
                    :rules="[requiredValidator]"
                    :label="$t('Label.Sigla')"
                    :placeholder="$t('Label.Sigla')"
                  />
                </VCol>
                <!-- 👉 Responsabile -->
                <VCol cols="7">
                  <AppSelect
                    v-model="editedItem.responsabile_id"
                    :label="$t('Label.Responsabile')"
                    :placeholder="$t('Label.Responsabile')"
                    :items="usersOptions"
                    item-title="full_name"
                    item-value="id"
                    :rules="[requiredValidator]"
                  />
                </VCol>
                <!-- 👉 Sigla -->
                <VCol cols="5">
                  <AppSelect
                    v-model="editedItem.tipologia"
                    :label="$t('Label.Tipologia')"
                    :placeholder="$t('Label.Tipologia')"
                    :items="[{ title: 'Publico', value: 1 }, { title: 'Privato', value: 2 }]"
                    :rules="[requiredValidator]"
                  />
                </VCol>
                <!-- 👉 Id Cartella Drive -->
                <VCol cols="7">
                  <AppTextField
                    v-model="editedItem.cartella_drive"
                    :rules="[requiredValidator]"
                    :label="$t('Label.Cartella-Drive')"
                    :placeholder="$t('Label.Cartella-Drive')"
                  />
                </VCol>
                <!-- 👉 Colore -->
                <VCol cols="7">
                  <AppSelect
                    v-model="editedItem.colore"
                    :label="$t('Label.Colore')"
                    :placeholder="$t('Label.Colore')"
                    :items="colors"
                    :rules="[requiredValidator]"
                  />
                </VCol>
                <VCol
                  cols="12"
                  class="mt-8"
                >
                  <VSwitch
                    v-model="editedItem.nascosto"
                    :label="$t('Label.Nascosto')"
                  />
                </VCol>
              </VRow>
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
            </VForm>
          </VContainer>
        </VCardText>


      </VCard>
    </AppCardActions>
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

.email-labels {
  > li {
    position: relative;
    margin-block-end: 4px;
    padding-block: 4px;
    padding-inline: 24px;
  }
}
</style>
