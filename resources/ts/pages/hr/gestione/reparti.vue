<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { useI18n } from 'vue-i18n'
import { VForm } from 'vuetify/components/VForm'
import { onMounted, computed } from 'vue'

definePage({
  meta: {
    action: 'list',
    subject: 'Reparti',
  },
})

const { t } = useI18n()
const itemsPerPage = ref(10)
const loading = ref(true)
const refForm = ref<VForm>()
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const repartoFilter = ref('')
const disattivoFilter = ref('')
const page = ref(1)
const serverItems = ref<any>([])
const isSnackbarScrollReverseVisible = ref(false)
const message = ref('')
const color = ref('')
const editDialog = ref(false)
const isLoading = ref(false)
const isFormValid = ref(false)

const defaultItem = ref<any>({
  id: '',
  reparto: '',
  disattivo: null,
})

function new_defaultItem() {
  defaultItem.value = {
    id: '',
    reparto: '',
    disattivo: null,
  }
}

const editedItem = ref<any>(defaultItem.value)
const editedIndex = ref(-1)

const updateOptions = (options: any) => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
  page.value = options.page
  itemsPerPage.value = options.itemsPerPage

  // eslint-disable-next-line @typescript-eslint/no-use-before-define
  loadItems()
}

const loadItems = async () => {
  loading.value = true

  const { data: resultData } = await useApi<any>(createUrl('/hr/gestione/reparti/list', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      reparto: repartoFilter.value,
      disattivo: disattivoFilter.value,
    },
  }))

  if (resultData.value !== null) {
    serverItems.value = resultData.value.data || resultData.value
    totalItems.value = resultData.value.total || (Array.isArray(resultData.value) ? resultData.value.length : 0)
  }
  else {
    serverItems.value = []
    totalItems.value = 0
  }
  loading.value = false
}

const headers = computed(() => [
  { title: t('Label.Reparto'), key: 'reparto' },
  { title: t('Label.Disattivo'), key: 'disattivo' },
  { title: 'ACTIONS', key: 'actions', sortable: false },
])

const save = async () => {
  if (editedItem.value.reparto) {
    let path = '/hr/gestione/reparti/store'
    if (editedItem.value.id)
      path = `/hr/gestione/reparti/update/${editedItem.value.id}`

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

const newItem = () => {
  new_defaultItem()
  editedIndex.value = -1
  editedItem.value = { ...defaultItem.value }
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
  editedItem.value.disattivo = editedItem.value.disattivo === '1'
  editDialog.value = true
}

const deleteItem = async (item: any) => {
  if (confirm('Sei sicuro di voler disattivare questo reparto?')) {
    const retuenData = await $api(`/hr/gestione/reparti/destroy/${item.id}`, {
      method: 'DELETE',
    })

    message.value = retuenData.message
    color.value = retuenData.color
    isSnackbarScrollReverseVisible.value = true
    await loadItems()
  }
}

onMounted(() => {
  loadItems()
})
</script>

<template>
  <div class="workspace-container w-100 d-flex flex-column pa-4 gap-3">
    <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
      <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
        <div class="d-flex align-center gap-2">
          <VAvatar color="primary" variant="tonal" size="38">
            <VIcon icon="tabler-hierarchy-3" size="20" />
          </VAvatar>
          <div>
            <div class="text-h6 font-weight-medium">Gestione Reparti</div>
            <div class="text-caption text-medium-emphasis">Gestisci i reparti e le divisioni aziendali</div>
          </div>
        </div>
        <div class="d-flex align-center gap-2">
          <VBtn
            prepend-icon="tabler-plus"
            color="primary"
            variant="flat"
            density="comfortable"
            class="px-3"
            @click="newItem"
          >
            Nuovo Reparto
          </VBtn>
        </div>
      </VCardText>
      <VDivider />

      <VCardText class="pa-3">
        <VRow class="mb-2">
          <!-- 👉 Reparto -->
          <VCol cols="12" sm="4">
            <AppTextField
              v-model="repartoFilter"
              :label="$t('Label.Reparto')"
              placeholder="Cerca reparto"
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-search"
              @keyup.enter="loadItems"
              @click:clear="loadItems"
            />
          </VCol>

          <!-- 👉 Disattivo -->
          <VCol cols="12" sm="4">
            <AppSelect
              v-model="disattivoFilter"
              :label="$t('Label.Disattivo')"
              placeholder="Filtra per stato"
              :items="[{ title: 'Si', value: 1 }, { title: 'No', value: 0 }]"
              clearable
              clear-icon="tabler-x"
              @update:model-value="loadItems"
            />
          </VCol>
        </VRow>
      </VCardText>
      <VDivider />

      <!-- 👉 Datatable  -->
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        :headers="headers"
        :items="serverItems"
        :items-length="totalItems"
        :loading="loading"
        @update:options="updateOptions"
      >
        <template #item.disattivo="{ item }">
          <VIcon
            v-if="item.disattivo"
            color="error"
            icon="tabler-x"
          />
          <VIcon
            v-else
            color="success"
            icon="tabler-check"
          />
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }">
          <div class="d-flex gap-1">
            <IconBtn
              color="warning"
              @click="editItem(item)"
            >
              <VIcon icon="tabler-edit" />
            </IconBtn>
            <IconBtn
              v-if="!item.disattivo"
              color="error"
              @click="deleteItem(item)"
            >
              <VIcon icon="tabler-trash" />
            </IconBtn>
          </div>
        </template>
      </VDataTableServer>
    </VCard>

    <VSnackbar
      v-model="isSnackbarScrollReverseVisible"
      transition="scroll-y-reverse-transition"
      location="top central"
      :color="color"
    >
      {{ $t(message) }}
    </VSnackbar>
  </div>

  <!-- 👉 Edit Dialog  -->
  <VDialog
    v-model="editDialog"
    max-width="800px"
  >
    <AppCardActions
      v-model:loading="isLoading"
      :title="editedItem.id ? `${$t('Label.Modifica')} Reparto` : `${$t('Label.Nuovo')} Reparto`"
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
                <!-- 👉 Reparto -->
                <VCol cols="12">
                  <AppTextField
                    v-model="editedItem.reparto"
                    :rules="[requiredValidator]"
                    :label="$t('Label.Reparto')"
                    :placeholder="$t('Label.Reparto')"
                  />
                </VCol>

                <VCol
                  cols="12"
                  class="mt-2"
                >
                  <VSwitch
                    v-model="editedItem.disattivo"
                    :label="$t('Label.Disattivo')"
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
