<script setup lang="ts">
import { VDataTableServer } from 'vuetify/labs/VDataTable'
import { useI18n } from 'vue-i18n'
import { VForm } from 'vuetify/components/VForm'
import { onMounted, computed, ref } from 'vue'

definePage({
  meta: {
    action: 'list',
    subject: 'Competenze',
  },
})

const { t } = useI18n()
const itemsPerPage = ref(10)
const loading = ref(true)
const refForm = ref<VForm>()
const totalItems = ref(0)
const sortBy = ref()
const orderBy = ref()
const searchFilter = ref('')
const roleFilter = ref('')
const page = ref(1)
const serverItems = ref<any>([])
const isSnackbarScrollReverseVisible = ref(false)
const message = ref('')
const color = ref('')
const editDialog = ref(false)
const isLoading = ref(false)
const isFormValid = ref(false)
const rolesOptions = ref<any[]>([])
const activitiesOptions = ref<any[]>([])

const valutazioneOptions = [
  { title: 'Non richiesta', value: 0 },
  { title: 'Insufficiente', value: 1 },
  { title: 'Sufficiente', value: 2 },
  { title: 'Buona', value: 3 },
  { title: 'Ottima', value: 4 },
]

const defaultItem = ref<any>({
  id: '',
  attivita: '',
  disattivo: false,
  roles: [],
})

function new_defaultItem() {
  defaultItem.value = {
    id: '',
    attivita: '',
    disattivo: false,
    roles: [],
  }
}

const editedItem = ref<any>(defaultItem.value)

const updateOptions = (options: any) => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
  page.value = options.page
  itemsPerPage.value = options.itemsPerPage
  loadItems()
}

const loadItems = async () => {
  loading.value = true

  const { data: resultData } = await useApi<any>(createUrl('/hr/competenze/attivita/list', {
    query: {
      page: page.value,
      itemsPerPage: itemsPerPage.value,
      sortBy: sortBy.value,
      orderBy: orderBy.value,
      search: searchFilter.value,
      hr_role_id: roleFilter.value,
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

const loadRoles = async () => {
  const { data: resultData } = await useApi<any>(createUrl('/hr/gestione/ruoli/get_list'))
  if (resultData.value) {
    rolesOptions.value = resultData.value.map((r: any) => ({ title: r.ruolo, value: r.id }))
  }
}

const loadActivities = async () => {
  const { data: resultData } = await useApi<any>(createUrl('/hr/competenze/attivita/list', {
    query: { disattivo: 0, itemsPerPage: 1000 },
  }))
  if (resultData.value?.data) {
    activitiesOptions.value = resultData.value.data.map((a: any) => ({ title: a.attivita, value: a.attivita }))
  }
}

const headers = computed(() => [
  { title: 'Attività', key: 'attivita' },
  { title: 'Mansioni', key: 'roles' },
  { title: 'ACTIONS', key: 'actions', sortable: false },
])

const valutazioneLabel = (val: number) => {
  const item = valutazioneOptions.find(v => v.value === val)
  return item ? item.title : '-'
}

const valutazioneColor = (val: number) => {
  const colors = ['grey', 'error', 'warning', 'info', 'success']
  return colors[val] || 'grey'
}

const save = async () => {
  const attivitaValue = typeof editedItem.value.attivita === 'object' ? editedItem.value.attivita.value : editedItem.value.attivita

  if (attivitaValue) {
    let path = '/hr/competenze/attivita/store'
    if (editedItem.value.id)
      path = `/hr/competenze/attivita/update/${editedItem.value.id}`

    isLoading.value = true

    const payload = {
      attivita: attivitaValue,
      disattivo: editedItem.value.disattivo,
      roles: editedItem.value.roles.map((r: any) => ({
        hr_role_id: r.hr_role_id,
        valutazione_ideale: r.valutazione_ideale,
      })),
    }

    const retuenData = await $api(path, {
      method: 'POST',
      body: payload,
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
    await loadActivities()
    await loadItems()
  }
}

const newItem = () => {
  new_defaultItem()
  editedItem.value = { ...defaultItem.value }
  editDialog.value = true
}

const close = () => {
  isLoading.value = false
  editDialog.value = false
  refForm.value?.reset()
}

const editItem = (item: any) => {
  editedItem.value = {
    id: item.id,
    attivita: item.attivita,
    disattivo: item.disattivo,
    roles: (item.roles || []).map((r: any) => ({
      hr_role_id: r.id,
      valutazione_ideale: r.pivot?.valutazione_ideale ?? 0,
    })),
  }
  editDialog.value = true
}

const deleteItem = async (item: any) => {
  if (confirm('Sei sicuro di voler disattivare questa attività?')) {
    const retuenData = await $api(`/hr/competenze/attivita/destroy/${item.id}`, {
      method: 'DELETE',
    })

    message.value = retuenData.message
    color.value = retuenData.color
    isSnackbarScrollReverseVisible.value = true
    await loadItems()
  }
}

const addRoleRow = () => {
  editedItem.value.roles.push({ hr_role_id: '', valutazione_ideale: 0 })
}

const removeRoleRow = (index: number) => {
  editedItem.value.roles.splice(index, 1)
}

const getRoleTitle = (roleId: string) => {
  const role = rolesOptions.value.find(r => r.value === roleId)
  return role ? role.title : roleId
}

onMounted(() => {
  loadRoles()
  loadActivities()
  loadItems()
})
</script>

<template>
  <div class="workspace-container w-100 d-flex flex-column pa-4 gap-3">
    <VCard variant="outlined" class="bg-surface border-thin rounded-lg">
      <VCardText class="d-flex align-center justify-space-between flex-wrap py-3 gap-3">
        <div class="d-flex align-center gap-2">
          <VAvatar color="primary" variant="tonal" size="38">
            <VIcon icon="tabler-target" size="20" />
          </VAvatar>
          <div>
            <div class="text-h6 font-weight-medium">Gestione Attività Competenze</div>
            <div class="text-caption text-medium-emphasis">Definisci le attività e le valutazioni ideali per ogni mansione</div>
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
            Nuova Attività
          </VBtn>
        </div>
      </VCardText>
      <VDivider />

      <VCardText class="pa-3">
        <VRow class="mb-2">
          <VCol cols="12" sm="6">
            <AppTextField
              v-model="searchFilter"
              label="Attività"
              placeholder="Cerca attività..."
              clearable
              clear-icon="tabler-x"
              prepend-inner-icon="tabler-search"
              @keyup.enter="loadItems"
              @click:clear="loadItems"
            />
          </VCol>
          <VCol cols="12" sm="6">
            <AppSelect
              v-model="roleFilter"
              label="Mansione"
              placeholder="Filtra per mansione"
              :items="rolesOptions"
              clearable
              clear-icon="tabler-x"
              @update:model-value="loadItems"
            />
          </VCol>
        </VRow>
      </VCardText>
      <VDivider />

      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        :headers="headers"
        :items="serverItems"
        :items-length="totalItems"
        :loading="loading"
        @update:options="updateOptions"
      >
        <template #item.roles="{ item }">
          <div class="d-flex flex-wrap gap-1">
            <VChip
              v-for="role in (item.roles || [])"
              :key="role.id"
              label
              size="small"
              :color="role.tipo === 'macchina' ? 'warning' : 'info'"
              variant="tonal"
            >
              {{ role.ruolo }}
              <span class="ml-1 text-caption">({{ valutazioneLabel(role.pivot?.valutazione_ideale ?? 0) }})</span>
            </VChip>
          </div>
        </template>

        <template #item.actions="{ item }">
          <div class="d-flex gap-1">
            <IconBtn
              color="warning"
              @click="editItem(item)"
            >
              <VIcon icon="tabler-edit" />
            </IconBtn>
            <IconBtn
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

  <VDialog
    v-model="editDialog"
    max-width="650px"
    persistent
  >
    <DialogCloseBtn @click="close" />

    <VCard :title="editedItem.id ? 'Modifica Attività' : 'Nuova Attività'">
      <VCardText>
        <VForm
          ref="refForm"
          v-model="isFormValid"
        >
          <VRow>
            <VCol cols="12">
              <VCombobox
                v-model="editedItem.attivita"
                :items="activitiesOptions"
                :rules="[requiredValidator]"
                label="Attività"
                placeholder="Seleziona o crea nuova attività"
                prepend-inner-icon="tabler-list-check"
                density="comfortable"
                clearable
                chips
                closable-chips
              />
            </VCol>
          </VRow>

          <div class="text-subtitle-2 mt-4 mb-2">Mansioni associate</div>
          <div
            v-for="(roleRow, index) in editedItem.roles"
            :key="index"
            class="d-flex align-center gap-2 mb-3"
          >
            <AppSelect
              v-model="roleRow.hr_role_id"
              :items="rolesOptions"
              label="Mansione"
              density="comfortable"
              class="flex-grow-1"
            />
            <AppSelect
              v-model="roleRow.valutazione_ideale"
              :items="valutazioneOptions"
              label="Valutazione ideale"
              density="comfortable"
              style="width: 200px;"
            />
            <IconBtn
              color="error"
              @click="removeRoleRow(index)"
            >
              <VIcon icon="tabler-x" />
            </IconBtn>
          </div>

          <VBtn
            variant="tonal"
            color="primary"
            size="small"
            prepend-icon="tabler-plus"
            @click="addRoleRow"
          >
            Aggiungi mansione
          </VBtn>

          <VRow class="mt-4">
            <VCol cols="12">
              <VSwitch
                v-model="editedItem.disattivo"
                label="Disattivo"
                color="primary"
                inset
              />
            </VCol>
          </VRow>
        </VForm>
      </VCardText>

      <VCardText class="d-flex justify-end flex-wrap gap-3">
        <VBtn
          variant="tonal"
          color="secondary"
          @click="close"
        >
          Annulla
        </VBtn>
        <VBtn
          color="primary"
          variant="elevated"
          :loading="isLoading"
          @click="save"
        >
          Salva
        </VBtn>
      </VCardText>
    </VCard>
  </VDialog>
</template>
