<script setup lang="ts">
import { VForm } from 'vuetify/components/VForm'
import DefineAbilities from '@/plugins/casl/DefineAbilities'
import { can } from '@layouts/plugins/casl'
import type {ReprotChecker} from "@/views/quality/checker/type";

definePage({
  meta: {
    action: 'list',
    subject: 'Plant-Asset',
  },
})

const maps = ref({})
const isFormValid = ref(false)
const refForm = ref<VForm>()
const path = import.meta.env.VITE_BASE_URL_PORTALE
const numeroSerialeFilter = ref()
const userFilter = ref()
const gruppoFilter = ref()
const gruppiList = ref({})
const gruppiView = ref(false)
const isEditMapVisible = ref(false)
const deleteDialog = ref(false)
const file = ref(null)
const data = ref({})
const deletedItem = ref({})
const fileName = computed(() => file.value?.name)
const fileExtension = computed(() => fileName.value?.substr(fileName.value?.lastIndexOf('.') + 1))
const fileMimeType = computed(() => file.value?.type)

const defaultItem = ref<any>({
  id: '',
  map: '',
  etichetta: '',
  gruppo: null,
})

function new_defaultItem() {
  defaultItem.value = {
    id: '',
    map: '',
    etichetta: '',
    gruppo: null,
  }
}

const editedItem = ref<any>(defaultItem.value)
const editedIndex = ref(-1)

//resources/images/custom/image.jpg

const loadList = async () => {
  const { data: resultData } = await useApi<any>(createUrl('/pl/map/maps', {
    query: {
      utente: userFilter.value,
      numero_serale: numeroSerialeFilter.value,
      gruppo: gruppoFilter.value,
    },
  }))

  maps.value = resultData.value
}

loadList()

const loadGruppo = async () => {
  const { data: gruppiData } = await useApi<any>(createUrl('/pl/group/get_list', {
    query: {
      attivo: 1,
    },
  }))

  gruppiList.value = gruppiData.value
  gruppiView.value = true
}

loadGruppo()

const editeMap = async (id?: string) => {
  if (id) {
    const { data: mapData } = await useApi<any>(createUrl(`/pl/map/view/${id}`, {
      query: {
      },
    }))

    editedIndex.value = maps.value.indexOf(mapData.value.id)
    editedItem.value = { ...mapData.value }
  }
  else {
    new_defaultItem()
    editedIndex.value = -1
    editedItem.value = { ...defaultItem.value }
  }
  isEditMapVisible.value = true
}

const uploadFile = (event: any) => {
  file.value = event.target.files[0]

  const reader = new FileReader()

  reader.readAsDataURL(file.value)
  reader.onload = async () => {
    const encodedFile = reader.result.split(',')[1]

    data.value = {
      file: encodedFile,
      fileName: fileName.value,
      fileExtension: fileExtension.value,
      fileMimeType: fileMimeType.value,
    }
  }
}

const save = async () => {
  if (editedItem.value.etichetta) {
    let path = '/pl/map/store_map/'
    if (editedItem.value.id)
      path = `/pl/map/update_map/${editedItem.value.id}`

    //isLoading.value = true

    const retuenData = await $api(path, {
      method: 'POST',
      body: {
        map: editedItem.value,
        file_upload: data.value,
      },
    })

    nextTick(() => {
      refForm.value?.reset()
      refForm.value?.resetValidation()
    })
    //message.value = retuenData.message
    //color.value = retuenData.color
    //isSnackbarScrollReverseVisible.value = true

    //isLoading.value = false
    isEditMapVisible.value = false
    await loadList()
  }
}

const deleteMap = (item: any) => {
  editedIndex.value = maps.value.indexOf(item)
  deletedItem.value = { ...item }
  deleteDialog.value = true

}

const deleted = async () => {
  const retuenData = await $api(`/pl/map/deleted/${deletedItem.value.id}`, {
    method: 'delete',
  })

  await loadList()
  deleteDialog.value = false
}
</script>

<template>
  <VCol cols="12">
    <VCard
      title="Filters"
      class="mb-6"
    >
      <VCardText>
        <VRow>
          <!-- 👉 Utente -->
          <VCol
            cols="12"
            sm="3"
          >
            <AppTextField
              v-model="userFilter"
              :label="$t('Label.User')"
              :placeholder="$t('Label.User')"
              clearable
              clear-icon="tabler-x"
              @focusout="loadList"
            />
          </VCol>
          <!-- 👉 Numero Seriale -->
          <VCol
            cols="12"
            sm="3"
          >
            <AppTextField
              v-model="numeroSerialeFilter"
              :label="$t('Label.Numero-Seriale')"
              :placeholder="$t('Label.Numero-Seriale')"
              clearable
              clear-icon="tabler-x"
              @focusout="loadList"
            />
          </VCol>
          <!-- 👉 Gruppi -->
          <VCol
            cols="12"
            sm="3"
            v-if="gruppiView"
          >
            <AppSelect
              v-model="gruppoFilter"
              :label="$t('Label.Gruppi')"
              :placeholder="$t('Label.Gruppi')"
              :items="gruppiList"
              :item-title="item => item.titolo"
              :item-value="item => item.id"
              clearable
              clear-icon="tabler-x"
              @focusout="loadList"
            />
          </VCol>
          <div class="app-user-search-filter d-flex align-center flex-wrap gap-4">
            <!-- 👉 Add user button -->
            <VBtn
              v-if="can(DefineAbilities.asset_create.action, DefineAbilities.asset_create.subject)"
              prepend-icon="tabler-plus"
              color="success"
              @click="editeMap(null)"
            >
              Nuova Mappa
            </VBtn>
          </div>
        </VRow>
      </VCardText>
    </VCard>
  </VCol>
  <VRow>
    <VCol v-for="map in maps" cols="2">
      <VCard
        :title="map.etichetta"
        class="mb-10"
      >
        <template #append>
          <div class="mt-n4 me-n2">
            <VBtn
              icon
              variant="text"
              size="small"
              color="medium-emphasis"
            >
              <VIcon
                size="24"
                icon="tabler-dots-vertical"
              />
              <VMenu activator="parent">
                <VList>
                  <VListItem
                    v-if="can(DefineAbilities.asset_create.action, DefineAbilities.asset_create.subject)"
                    :to="{ name: 'plant-asset-maps-id', params: { id: map.id } }"
                  >
                    <template #prepend>
                      <VIcon icon="tabler-eye" />
                    </template>

                    <VListItemTitle>View</VListItemTitle>
                  </VListItem>

                  <VListItem
                    v-if="can(DefineAbilities.asset_create.action, DefineAbilities.asset_create.subject)"
                    @click="editeMap(map.id)"
                  >
                    <template #prepend>
                      <VIcon icon="tabler-pencil" />
                    </template>
                    <VListItemTitle>Edit</VListItemTitle>
                  </VListItem>

                  <VListItem
                    v-if="can(DefineAbilities.asset_admin.action, DefineAbilities.asset_admin.subject)"
                    @click="deleteMap(map)"
                  >
                    <template #prepend>
                      <VIcon icon="tabler-trash" />
                    </template>
                    <VListItemTitle>Delete</VListItemTitle>
                  </VListItem>
                </VList>
              </VMenu>
            </VBtn>
          </div>
        </template>

        <RouterLink
          :to="{ name: 'plant-asset-maps-id', params: { id: map.id } }"
          class="font-weight-medium text-link"
        >
          <VImg :src="path + map.map" class="center-block" height="400" width="300" />
        </RouterLink>
      </VCard>
    </VCol>
  </VRow>
  <!-- Edit Map -->
  <VDialog
    v-model="isEditMapVisible"
    max-width="800"
  >
    <!-- Dialog close btn -->
    <DialogCloseBtn @click="isEditMapVisible = !isEditMapVisible" />

    <!-- Dialog Content -->
    <VCard :title="editedItem.id ? `${$t('Label.Modifica-Mappa')}` : `${$t('Label.Nuova-Mappa')}`">
      <VCardText>
        <VForm
          ref="refForm"
          v-model="isFormValid"
        >
        <VRow>
          <!-- 👉 Upload -->
          <VCol
            cols="12"
            md="12"
            v-if="!editedItem.id"
          >
            <VFileInput
              accept=".png, .jpeg, .svg,"
              :label="$t('Label.Upload-Mappa')"
              :rules="[requiredValidator]"
              @change="uploadFile"
            />
          </VCol>
          <VCol
            cols="12"
            sm="6"
            md="6"
          >
            <AppTextField
              v-model="editedItem.etichetta"
              :label="$t('Label.Etichetta')"
              :placeholder="$t('Label.Etichetta')"
              clearable
              clear-icon="tabler-x"
              :rules="[requiredValidator]"
            />
          </VCol>
          <VCol
            cols="12"
            sm="6"
            md="6"
          >
            <AppAutocomplete
              v-model="editedItem.gruppo"
              :label="$t('Label.Gruppi')"
              :placeholder="$t('Label.Gruppi')"
              :items="gruppiList"
              :item-title="item => item.titolo"
              :item-value="item => item.id"
              clearable
            />
          </VCol>
        </VRow>
          <VCardText class="d-flex justify-end flex-wrap gap-3">
            <VBtn
              variant="tonal"
              color="secondary"
              @click="isEditMapVisible = false"
            >
              Close
            </VBtn>
            <VBtn @click="save">
              Save
            </VBtn>
          </VCardText>
        </VForm>
      </VCardText>
    </VCard>
  </VDialog>

  <!-- 👉 Delete Dialog  -->
  <VDialog
    v-model="deleteDialog"
    max-width="500px"
  >
    <VCard>
      <VCardTitle>
        Sei sicuro di voler eliminare?
      </VCardTitle>

      <VCardActions>
        <VSpacer/>

        <VBtn
          color="error"
          variant="outlined"
          @click="deleteDialog = false"
        >
          Cancel
        </VBtn>

        <VBtn
          color="success"
          variant="elevated"
          @click="deleted"
        >
          OK
        </VBtn>

        <VSpacer/>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<style>
.center-block {
  display: block;
  margin-left: auto;
  margin-right: auto;
}
</style>
