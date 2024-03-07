<script setup lang="ts">
import { VDataTableServer } from 'vuetify/components/VDataTable'

definePage({
  meta: {
    action: 'read',
    subject: 'user',
  },
})

// 👉 headers
const headers = [
  { title: 'Name', key: 'name' },
  { title: 'Guard', key: 'guard_name', sortable: false },
  { title: 'Created Date', key: 'created_at', sortable: false },
  { title: 'Actions', key: 'actions', sortable: false },
]

const search = ref('')

// Data table options
const itemsPerPage = ref(10)
const page = ref(1)
const sortBy = ref()
const orderBy = ref()
const isUserInfoEditDialogVisible = ref(false)
const message = ref('')
const color = ref('')

// Update data table options
const updateOptions = (options: any) => {
  page.value = options.page
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
}

const isPermissionDialogVisible = ref(false)
const isAddPermissionDialogVisible = ref(false)
const permissionName = ref('')
let permissions = ref({})
let totalPermissions = ref(0)

const colors: any = {
  'support': { color: 'info', text: 'Support' },
  'users': { color: 'success', text: 'Users' },
  'manager': { color: 'warning', text: 'Manager' },
  'administrator': { color: 'primary', text: 'Administrator' },
  'restricted-user': { color: 'error', text: 'Restricted User' },
}

const fetchPermissions = async () => {
  const { data: resultData } = await useApi<any>(createUrl('/admin/permissions', {
    query: {
      q: search,
      itemsPerPage,
      page,
      sortBy,
      orderBy,
    },
  }))

  permissions.value = resultData.value.data
  totalPermissions = resultData.value.total
}

await fetchPermissions()

const addPermission = (name: string) => {
  alert('new')
  isPermissionDialogVisible.value = true
  permissionName.value = name
}

const editPermission = (name: string) => {
  isPermissionDialogVisible.value = true
  permissionName.value = name
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
              @user-data="addPermission"
            >
              Add Permission
            </VBtn>
          </div>
        </VCardText>

        <VDivider />

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
          @update:options="updateOptions"
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
            <VDivider />

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
              @click="editPermission(item.name)"
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
        v-model:permission-name="permissionName"
      />
      <AddEditPermissionDialog v-model:isDialogVisible="isAddPermissionDialogVisible"  @user-data="addPermission"  />
    </VCol>
  </VRow>
</template>
