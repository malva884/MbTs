<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import {useI18n} from "vue-i18n";
import {can} from "@layouts/plugins/casl";
import DefineAbilities from "@/plugins/casl/DefineAbilities";
import {VForm} from "vuetify/components/VForm";
interface Props {
  areaId: string
  responsabile: boolean
}

const props = defineProps<Props>()
const { t } = useI18n()
const itemsPerPage = ref(10)
const loading = ref(1)
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const page = ref(1)
const serverItems = ref<any>([])
const isSnackbarScrollReverseVisible = ref(false)
const editRow = ref(false)
const escudiMenbri = ref(false)
const message = ref('')
const color = ref('')
const search = ref('')
const q = ref('')

// headers
const headers = [
  { title: t('Table.Utenti'), key: 'full_name' },
  { title: t('Table.Responsabile'), key: 'responsabile' },
  { title: t('Table.Aprire-Task'), key: 'aprire_task' },
  { title: t('Table.Chiudere-Task'), key: 'chiudere_task' },
  { title: t('Table.Modificare-Task'), key: 'modificare_task' },
  { title: t('Table.Eliminare-Task'), key: 'eliminare_task' },
  { title: 'ACTIONS', key: 'actions', sortable: false },
]

const updateOptions = (options: any) => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
  page.value = options.page
  itemsPerPage.value = options.itemsPerPage
  q.value = options.search
  // eslint-disable-next-line @typescript-eslint/no-use-before-define
  loadItems()
}

const loadItems = async () => {
  loading.value = true

  const { data: resultData } = await useApi<any>(createUrl(`/task/aree/users/${props.areaId}`, {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      search: q.value,
    },
  }))

  if (resultData.value !== null) {
    serverItems.value = resultData.value.data
    totalItems.value = resultData.value.total
  }
  else {
    serverItems.value = []
    totalItems.value = 0
  }
  loading.value = false
}

const defaultItem = ref<any>({
  id: '',
  user_id: null,
  responsabile:false,
  aprire_task: true,
  chiudere_task: true,
  modificare_task: true,
  eliminare_task: true,
})

function new_defaultItem() {
  defaultItem.value = {
    id: '',
    user_id: null,
    responsabile:false,
    aprire_task: true,
    chiudere_task: true,
    modificare_task: true,
    eliminare_task: true,
  }
}

const editedItem = ref<any>(defaultItem.value)
const editedIndex = ref(-1)
const editDialog = ref(false)
const isLoading = ref(false)

const newItem = () => {
  new_defaultItem()
  editedIndex.value = -1
  editedItem.value = { ...defaultItem.value }
  escudiMenbri.value = true
  // eslint-disable-next-line @typescript-eslint/no-use-before-define
  getUsers()
  editRow.value = false
  editDialog.value = true
}

const close = () => {
  isLoading.value = false
  editDialog.value = false
  editedIndex.value = -1
  refForm.value?.reset()
}

const editItem = (item: object) => {
  editedIndex.value = serverItems.value.indexOf(item)
  editedItem.value = { ...item }
  editedItem.value.responsabile = !!Number.parseInt(editedItem.value.responsabile)
  editedItem.value.aprire_task = !!Number.parseInt(editedItem.value.aprire_task)
  editedItem.value.chiudere_task = !!Number.parseInt(editedItem.value.chiudere_task)
  editedItem.value.modificare_task = !!Number.parseInt(editedItem.value.modificare_task)
  editedItem.value.eliminare_task = !!Number.parseInt(editedItem.value.eliminare_task)
  editedItem.value.user_id = Number.parseInt(editedItem.value.user_id)
  escudiMenbri.value = false
  // eslint-disable-next-line @typescript-eslint/no-use-before-define
  getUsers()
  editRow.value = true
  editDialog.value = true
}

const { data: usersData, execute: getUsers } = useApi<any>(createUrl(`/task/aree/get_users/${props.areaId}`, {
  query: {
    escludiGiaMenbri: escudiMenbri.value,
  },
}))

const save = async () => {
  if (editedItem.value.user_id) {
    let path = `/task/aree/add_user/${props.areaId}`
    if (editedItem.value.id)
      path = `/task/aree/edit_user/${editedItem.value.id}`

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
    isSnackbarScrollReverseVisible.value = true

    isLoading.value = false
    editDialog.value = false
    await loadItems()
  }
}

watch(props, () => {
  loadItems()
})
</script>

<template>
  <VCardText class="d-flex flex-wrap py-4 gap-4 ">
    <VSnackbar
      v-model="isSnackbarScrollReverseVisible"
      transition="scroll-y-reverse-transition"
      location="top central"
      :color="color"
    >
      {{ $t(message) }}
    </VSnackbar>
  </VCardText>
  <VRow>
    <VCol cols="12">
      <VCard>
        <VCardText>
          <VRow>
            <VCol
              cols="12"
              offset-md="8"
              md="4"
            >
              <AppTextField
                v-model="search"
                placeholder="Search ..."
                append-inner-icon="tabler-search"
                single-line
                hide-details
                dense
                outlined
                density="compact"
              />
            </VCol>
          </VRow>
        </VCardText>
        <VDataTableServer
          v-model:items-per-page="itemsPerPage"
          :headers="headers"
          :items="serverItems"
          :items-length="totalItems"
          :loading="loading"
          @update:options="updateOptions"
          :search="search"
          density="compact"
        >
          <template #item.responsabile="{ item }">
            <div
              v-if="item.responsabile === '1'"
              class="d-flex gap-1"
            >
              <VIcon
                color="error"
                icon="tabler-check"
              />
            </div>
            <div
              v-else
              class="d-flex gap-1"
            />
          </template>
          <template #item.aprire_task="{ item }">
            <div
              v-if="item.aprire_task === '1'"
              class="d-flex gap-1"
            >
              <VIcon
                color="success"
                icon="tabler-check"
              />
            </div>
            <div
              v-else
              class="d-flex gap-1"
            />
          </template>
          <template #item.chiudere_task="{ item }">
            <div
              v-if="item.chiudere_task === '1'"
              class="d-flex gap-1"
            >
              <VIcon
                color="success"
                icon="tabler-check"
              />
            </div>
            <div
              v-else
              class="d-flex gap-1"
            />
          </template>
          <template #item.modificare_task="{ item }">
            <div
              v-if="item.modificare_task === '1'"
              class="d-flex gap-1"
            >
              <VIcon
                color="success"
                icon="tabler-check"
              />
            </div>
            <div
              v-else
              class="d-flex gap-1"
            />
          </template>
          <template #item.eliminare_task="{ item }">
            <div
              v-if="item.eliminare_task === '1'"
              class="d-flex gap-1"
            >
              <VIcon
                color="success"
                icon="tabler-check"
              />
            </div>
            <div
              v-else
              class="d-flex gap-1"
            />
          </template>

          <!-- Actions -->
          <template #item.actions="{ item }">
            <div class="d-flex gap-1">
              <IconBtn
                v-if="props.responsabile"
                color="warning"
                @click="editItem(item)"
              >
                <VIcon icon="tabler-edit" />
              </IconBtn>
              <IconBtn
                v-if="props.responsabile"
                color="error"
                @click="editItem(item)"
              >
                <VIcon icon="tabler-trash" />
              </IconBtn>
            </div>
          </template>
        </VDataTableServer>
      </VCard>
    </VCol>
  </VRow>

  <!-- 👉 Edit Dialog  -->
  <VDialog
    v-model="editDialog"
    max-width="1400px"
  >
    <AppCardActions
      v-model:loading="isLoading"
      :title="editedItem.id ? `${$t('Label.Modifica')}` : `${$t('Label.Aggiungi')} Utente`"
      no-actions
    >
      <VCard>
        <VCardText>
          <VContainer>
            <VForm
              ref="refForm"
              v-model="isFormValid"
            >
              <VRow>
                <!-- 👉 Utente -->
                <VCol cols="12">
                  <AppAutocomplete
                    v-model="editedItem.user_id"
                    :label="$t('Label.Utente')"
                    :placeholder="$t('Label.Utente')"
                    :items="usersData"
                    item-title="full_name"
                    item-value="id"
                    :readonly="editRow"
                  />
                </VCol>
                <VCol
                  cols="12"
                  class="mt-8"
                >
                  <VSwitch
                    v-model="editedItem.responsabile"
                    :label="$t('Label.Responsabile-Area')"
                    color="error"
                  />
                </VCol>
                <VCol
                  cols="12"
                  class="mt-8"
                >
                  <VSwitch
                    v-model="editedItem.aprire_task"
                    :label="$t('Label.Aprire-Task')"
                    color="success"
                  />
                </VCol>
                <VCol
                  cols="12"
                  class="mt-8"
                >
                  <VSwitch
                    v-model="editedItem.chiudere_task"
                    :label="$t('Label.Chiudere-Task')"
                    color="primary"
                  />
                </VCol>
                <VCol
                  cols="12"
                  class="mt-8"
                >
                  <VSwitch
                    v-model="editedItem.modificare_task"
                    :label="$t('Label.Modificare-Task')"
                    color="info"
                  />
                </VCol>
                <VCol
                  cols="12"
                  class="mt-8"
                >
                  <VSwitch
                    v-model="editedItem.eliminare_task"
                    :label="$t('Label.Eliminare-Task')"
                    color="warning"
                  />
                </VCol>

              </VRow>
            </VForm>
          </VContainer>
        </VCardText>

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
            @click="save"
          >
            Save
          </VBtn>
        </VCardActions>
      </VCard>
    </AppCardActions>
  </VDialog>
</template>

<style scoped lang="scss">

</style>
