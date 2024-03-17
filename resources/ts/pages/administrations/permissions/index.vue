<script setup lang="ts">
import {VDataTableServer} from 'vuetify/labs/VDataTable'
import type {Permission} from '@/views/administrations/permission/type'

definePage({
  meta: {
    action: 'read',
    subject: 'Permessi',
  },
})

// 👉 headers
const headers = [
  {title: 'Name', key: 'name'},
  {title: 'Guard', key: 'guard_name', sortable: false},
  {title: 'Created Date', key: 'created_at', sortable: false},
  {title: 'Actions', key: 'actions', sortable: false},
]

const search = ref('')

// Data table options
const itemsPerPage = ref(10)
const page = ref(1)
const sortBy = ref()
const orderBy = ref()

// Update data table options
const updateOptions = (options: any) => {
  page.value = options.page
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
}

const isPermissionDialogVisible = ref(false)
const isAddPermissionDialogVisible = ref(false)
const isPermissionDialogVisibleDell = ref(false)
const closeDelete = ref(false)
const permissionEdit = ref({})
const permissionDell = ref<Permission>
const permissions = ref<Permission[]>([])
let totalPermissions = ref(0)

const colors: any = {
  'support': {color: 'info', text: 'Support'},
  'users': {color: 'success', text: 'Users'},
  'manager': {color: 'warning', text: 'Manager'},
  'administrator': {color: 'primary', text: 'Administrator'},
  'restricted-user': {color: 'error', text: 'Restricted User'},
}

const fetchPermissions = async () => {
  const resultData = await useApi<Permission>(createUrl('/admin/permissions', {
    query: {
      q: search,
      itemsPerPage,
      page,
      sortBy,
      orderBy,
    },
  }))

  permissions.value = resultData.data.value.data
  totalPermissions = resultData.data.value.total
}

const editPermission = (item: object) => {
  permissionEdit.value = item
  isPermissionDialogVisible.value = true
}

const dellPermission = (item: object) => {
  permissionDell.value = item
  isPermissionDialogVisibleDell.value = true
}

const saveItem = async (item: Permission) => {
  await $api('/admin/permissions/store', {
    method: 'POST',
    body: item,
  })

  await fetchPermissions()
}

const deletedItem = async () => {
  console.log(permissionDell)
  await $api(`/admin/permissions/delete/${permissionDell.value.id}`, {
    method: 'DELETE',
    body: permissionDell.value,
  })

  await fetchPermissions()
}
</script>

<template>
  <VRow>
    <VCol cols="12">
      <h5 class="text-h4 mb-6">
        Permissions List
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
              placeholder="Search"
              density="compact"
              style="inline-size: 12.5rem;"
            />
            <VBtn
              density="default"
              @click="isAddPermissionDialogVisible = true"
              @permission-name="editPermission"
            >
              Add Permission
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
          <template #item.assignedTo="{ item }">
            <div class="d-flex gap-2">
              <VChip
                v-for="text in item.assignedTo"
                :key="text"
                label
                :color="colors[text].color"
                class="font-weight-medium"
              >
                {{ colors[text].text }}
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
                    Previous
                  </VBtn>
                </template>

                <template #next="slotProps">
                  <VBtn
                    variant="tonal"
                    color="default"
                    v-bind="slotProps"
                    :icon="false"
                  >
                    Next
                  </VBtn>
                </template>
              </VPagination>
            </div>
          </template>

          <template #item.createdDate="{ item }">
            <span>{{ item.createdDate }}</span>
          </template>

          <!-- Actions -->
          <template #item.actions="{ item }">
            <VBtn
              icon
              size="small"
              color="medium-emphasis"
              variant="text"
              @click="editPermission(item)"
            >
              <VIcon
                size="22"
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
                size="22"
                icon="tabler-trash"
              />
            </VBtn>
          </template>
        </VDataTableServer>
      </VCard>

      <AddEditPermissionDialog
        v-model:isDialogVisible="isPermissionDialogVisible"
        :permission-data="permissionEdit"
        @permission-data="editItem"
      />
      <AddEditPermissionDialog
        v-model:isDialogVisible="isAddPermissionDialogVisible"
        @permission-data="saveItem"
      />
    </VCol>
  </VRow>

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
          @click="closeDelete"
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
</template>
