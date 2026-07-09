<script setup lang="ts">
import {VDataTableServer} from 'vuetify/labs/VDataTable'

interface PermissionListItem {
  id: number
  name: string
  guard_name: string
  created_at: string
  assigned_to?: string[]
}

interface PermissionFormData {
  id: number | null
  name: string
  module?: string
  permissionType?: string
}

interface PermissionsResponse {
  data: PermissionListItem[]
  total: number
}

definePage({
  meta: {
    action: 'read',
    subject: 'Permessi',
  },
})

// 👉 headers
const headers = computed(() => [
  {title: 'Name', key: 'name'},
  {title: 'Guard', key: 'guard_name', sortable: false},
  {title: 'Created Date', key: 'created_at', sortable: false},
  {title: 'Actions', key: 'actions', sortable: false},
])

const search = ref('')
const moduleFilter = ref('')
const permissionTypeFilter = ref('')

// Data table options
const itemsPerPage = ref(10)
const page = ref(1)
const sortBy = ref()
const orderBy = ref()

const isPermissionDialogVisible = ref(false)
const isAddPermissionDialogVisible = ref(false)
const isPermissionDialogVisibleDell = ref(false)
const permissionEdit = ref<PermissionFormData>({id: null, name: ''})
const permissionDell = ref<PermissionListItem | null>(null)
const permissions = ref<PermissionListItem[]>([])
const totalPermissions = ref(0)
const isCreateMissingVisible = ref(false)

const colors: any = {
  'support': {color: 'info', text: 'Support'},
  'users': {color: 'success', text: 'Users'},
  'manager': {color: 'warning', text: 'Manager'},
  'administrator': {color: 'primary', text: 'Administrator'},
  'restricted-user': {color: 'error', text: 'Restricted User'},
}

const fetchPermissions = async () => {
  const { data: resultData } = await useApi<PermissionsResponse>(createUrl('/admin/permissions', {
    query: {
      q: search,
      itemsPerPage,
      page,
      sortBy,
      orderBy,
      module: moduleFilter,
      permissionType: permissionTypeFilter,
    },
  }))

  permissions.value = resultData.value?.data ?? []
  totalPermissions.value = resultData.value?.total ?? 0
}

const editPermission = (item: PermissionListItem) => {
  const parts = item.name.split('.')
  // Last part is the permission type, everything before is the module
  const permissionType = parts.pop() || ''
  const module = parts.join('.')

  permissionEdit.value = {
    id: item.id,
    name: item.name,
    module,
    permissionType,
  }
  isPermissionDialogVisible.value = true
}

const dellPermission = (item: PermissionListItem) => {
  permissionDell.value = item
  isPermissionDialogVisibleDell.value = true
}

const saveItem = async (item: PermissionFormData) => {
  await $api('/admin/permissions/store', {
    method: 'POST',
    body: item,
  })

  await fetchPermissions()
}

const editItem = async (item: PermissionFormData) => {
  if (!item.id)
    return

  await $api(`/admin/permissions/update/${item.id}`, {
    method: 'POST',
    body: item,
  })

  await fetchPermissions()
}

const deletedItem = async () => {
  if (!permissionDell.value)
    return

  await $api(`/admin/permissions/delete/${permissionDell.value.id}`, {
    method: 'DELETE',
    body: permissionDell.value,
  })

  await fetchPermissions()
  isPermissionDialogVisibleDell.value = false
}

const createMissingPermissions = async () => {
  try {
    await $api('/admin/permissions/create-missing', {
      method: 'POST',
    })

    await fetchPermissions()
    isCreateMissingVisible.value = false
  } catch (error) {
    console.error('Error creating missing permissions:', error)
  }
}

onMounted(fetchPermissions)
</script>

<template>
  <VRow>
    <VCol cols="12">
      <h5 class="text-h4 mb-6">
        {{ $t('Label.Lista-Permessi') }}
      </h5>
    </VCol>

    <VCol cols="12">
      <VCard>
        <VCardText class="d-flex align-center justify-space-between flex-wrap gap-4">
          <AppSelect
            :model-value="itemsPerPage"
            :items="[
              { value: 10, title: '10' },
              { value: 25, title: '25' },
              { value: 50, title: '50' },
              { value: 100, title: '100' },
              { value: -1, title: 'All' },
            ]"
            style="inline-size: 5rem;"
            @update:model-value="itemsPerPage = parseInt($event, 10)"
          />

          <div class="d-flex align-center gap-4 flex-wrap">
            <AppTextField
              v-model="search"
              :placeholder="$t('Label.Cerca')"
              density="compact"
              style="inline-size: 12.5rem;"
            />
            <AppTextField
              v-model="moduleFilter"
              :placeholder="$t('Label.Filtro-Modulo')"
              density="compact"
              clearable
              hide-details
              style="inline-size: 10rem;"
            />
            <AppTextField
              v-model="permissionTypeFilter"
              :placeholder="$t('Label.Filtro-Tipo')"
              density="compact"
              clearable
              hide-details
              style="inline-size: 10rem;"
            />
            <VBtn
              density="default"
              @click="fetchPermissions"
            >
              {{ $t('Label.Applica') }}
            </VBtn>
            <VBtn
              density="default"
              color="warning"
              @click="isCreateMissingVisible = true"
            >
              {{ $t('Label.Crea-Mancanti') }}
            </VBtn>
            <VBtn
              density="default"
              @click="isAddPermissionDialogVisible = true"
            >
              {{ $t('Label.Aggiungi-Permesso') }}
            </VBtn>
          </div>
        </VCardText>

        <VDivider/>

        <VDataTableServer
          v-model:items-per-page="itemsPerPage"
          v-model:page="page"
          :items-length="totalPermissions"
          :items-per-page-options="[
            { value: 5, title: '5' },
            { value: 10, title: '10' },
            { value: -1, title: '$vuetify.dataFooter.itemsPerPageAll' },
          ]"
          :headers="headers"
          :items="permissions"
          class="text-no-wrap"
          @update:options="fetchPermissions"
        >
          <!-- Assigned To -->
          <template #item.assigned_to="{ item }">
            <div class="d-flex gap-2">
              <VChip
                v-for="text in item.assigned_to"
                :key="text"
                label
                :color="colors[text]?.color"
                class="font-weight-medium"
              >
                {{ colors[text]?.text ?? text }}
              </VChip>
            </div>
          </template>

          <template #bottom>
            <VDivider/>

            <div class="d-flex align-center justify-space-between flex-wrap gap-3 pa-5 pt-3">
              <p class="text-sm text-medium-emphasis mb-0">

              </p>

              <VPagination
                v-model="page"
                :length="Math.ceil(totalPermissions / itemsPerPage)"
                :total-visible="$vuetify.display.xs ? 1 : Math.min(Math.ceil(totalPermissions / itemsPerPage), 5)"
              >
                <template #prev="slotProps">
                  <VBtn
                    variant="tonal"
                    color="default"
                    v-bind="slotProps"
                    :icon="false"
                  >
                    {{ $t('Label.Precedente') }}
                  </VBtn>
                </template>

                <template #next="slotProps">
                  <VBtn
                    variant="tonal"
                    color="default"
                    v-bind="slotProps"
                    :icon="false"
                  >
                    {{ $t('Label.Successivo') }}
                  </VBtn>
                </template>
              </VPagination>
            </div>
          </template>

          <template #item.created_at="{ item }">
            <span>{{ item.created_at }}</span>
          </template>

          <!-- Actions -->
          <template #item.actions="{ item }">
            <div class="d-flex gap-1">
              <VBtn
                icon
                size="small"
                color="medium-emphasis"
                variant="text"
                @click="editPermission(item)"
              >
                <VIcon
                  size="20"
                  icon="tabler-edit"
                />
              </VBtn>
              <VBtn
                icon
                size="small"
                variant="text"
                color="medium-emphasis"
                @click="dellPermission(item)"
              >
                <VIcon
                  size="20"
                  icon="tabler-trash"
                />
              </VBtn>
            </div>
          </template>
        </VDataTableServer>
        <AddEditPermissionDialog
          v-model:isDialogVisible="isPermissionDialogVisible"
          :permission-data="permissionEdit"
          @permission-data="editItem"
        />
        <AddEditPermissionDialog
          v-model:isDialogVisible="isAddPermissionDialogVisible"
          @permission-data="saveItem"
        />
      </VCard>
    </VCol>

    <!-- 👉 Delete Dialog  -->
  <VDialog
    v-model="isPermissionDialogVisibleDell"
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
          @click="isPermissionDialogVisibleDell = false"
        >
          Cancel
        </VBtn>

        <VBtn
          color="success"
          variant="elevated"
          @click="deletedItem"
        >
          OK
        </VBtn>

        <VSpacer/>
      </VCardActions>
    </VCard>
  </VDialog>

  <!-- 👉 Create Missing Permissions Dialog  -->
  <VDialog
    v-model="isCreateMissingVisible"
    max-width="500px"
  >
    <VCard>
      <VCardTitle>
        Create Missing Permissions
      </VCardTitle>

      <VCardText>
        This will create all permissions defined in the system that are missing from the database. Are you sure you want to proceed?
      </VCardText>

      <VCardActions>
        <VSpacer/>

        <VBtn
          color="error"
          variant="outlined"
          @click="isCreateMissingVisible = false"
        >
          Cancel
        </VBtn>

        <VBtn
          color="success"
          variant="elevated"
          @click="createMissingPermissions"
        >
          Create
        </VBtn>

        <VSpacer/>
      </VCardActions>
    </VCard>
  </VDialog>
</VRow>
</template>

